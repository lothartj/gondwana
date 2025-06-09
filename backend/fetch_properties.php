<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Set headers for cross-origin requests
$origins = getAllowedOrigins();
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (in_array($origin, $origins) ? $origin : $origins[0]));
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Initialize cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://gondwana-collection.com/_hcms/api/disco/updatePropertiesBasedOnFilter',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Origin: https://gondwana-collection.com',
            'Referer: https://gondwana-collection.com/',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('API request failed: ' . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("API returned non-200 status code: $httpCode");
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Failed to parse API response: ' . json_last_error_msg());
    }
    
    // Extract properties and images
    $properties = [];
    
    if (isset($data['html'])) {
        $dom = new DOMDocument();
        @$dom->loadHTML($data['html'], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);
        
        $sections = $xpath->query("//section[contains(@class, 'property-list-section')]");
        
        if ($sections && $sections->length > 0) {
            foreach ($sections as $section) {
                $titleNode = $xpath->query(".//h4[contains(@class, 'pl_content_title')]", $section)->item(0);
                $title = $titleNode ? trim($titleNode->textContent) : '';
                
                $imageBlock = $xpath->query(".//div[contains(@class, 'pl_image_block')]", $section)->item(0);
                $imageUrl = '';
                if ($imageBlock) {
                    $style = $imageBlock->getAttribute('style');
                    if (preg_match('/url\((.*?)\)/', $style, $matches)) {
                        $imageUrl = trim($matches[1], "'\"");
                    }
                }
                
                // Generate a unique ID for the property (for unit type ID)
                $locationId = $section->getAttribute('id');
                $locationId = str_replace('main-', '', $locationId);
                
                // Generate a unit type ID that follows the expected format
                // Real unit type IDs are typically negative 10-digit numbers
                // We'll generate a consistent ID based on the title to ensure it's stable between page loads
                $unitTypeId = -2147480000 - abs(crc32($title)) % 3000; // Generate a unique ID in the expected range
                
                $properties[] = [
                    'title' => $title,
                    'image_url' => $imageUrl,
                    'unit_type_id' => $unitTypeId,
                    'location_id' => $locationId
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $properties
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching properties: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 
<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Only set headers if not in test environment
if (php_sapi_name() !== 'cli') {
    $origins = getAllowedOrigins();
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . (in_array($origin, $origins) ? $origin : $origins[0]));
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Required environment variables
$required_env_vars = [
    'GONDWANA_API_URL',
    'GONDWANA_ORIGIN',
    'GONDWANA_REFERER'
];

try {
    // Get environment variables from multiple sources
    foreach ($required_env_vars as $var) {
        // Try all possible sources
        $value = $_ENV[$var] ?? getenv($var) ?? $_SERVER[$var] ?? null;
        
        if (empty($value)) {
            throw new Exception("Required environment variable {$var} is not set or empty");
        }
        
        // Store in $_ENV for consistent access
        $_ENV[$var] = $value;
    }
    
    $api_url = $_ENV['GONDWANA_API_URL'];
    $origin = $_ENV['GONDWANA_ORIGIN'];
    $referer = $_ENV['GONDWANA_REFERER'];
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Origin: ' . $origin,
            'Referer: ' . $referer,
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
    
    if (!isset($data['html'])) {
        throw new Exception('API response missing HTML content');
    }
    
    $propertyData = extractPropertiesFromHTML($data['html']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $data['total'] ?? count($propertyData),
            'properties' => $propertyData
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error in search_all.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'env_vars_status' => array_map(function($var) {
                return [
                    'name' => $var,
                    'env' => $_ENV[$var] ?? null,
                    'getenv' => getenv($var),
                    'server' => $_SERVER[$var] ?? null,
                    'docker_env' => getenv($var, true) // true = local only
                ];
            }, $required_env_vars),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
        ]
    ]);
    exit;
}

function extractPropertiesFromHTML($html) {
    try {
        $properties = [];
        
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);
        
        $sections = $xpath->query("//section[contains(@class, 'property-list-section')]");
        
        if (!$sections || $sections->length === 0) {
            throw new Exception('No properties found in the response');
        }
        
        foreach ($sections as $section) {
            $title = $xpath->query(".//h4[contains(@class, 'pl_content_title')]", $section)->item(0);
            $features = $xpath->query(".//div[contains(@class, 'pl_content_desc')]//p", $section);
            $price = $xpath->query(".//span[contains(@class, 'pl_price')]", $section)->item(0);
            $priceDesc = $xpath->query(".//span[contains(@class, 'price_description')]", $section)->item(0);
            
            $featureList = [];
            foreach ($features as $feature) {
                $featureText = trim(strip_tags($feature->textContent));
                if (!empty($featureText) && $featureText !== '&nbsp;') {
                    $featureList[] = $featureText;
                }
            }
            
            $locationId = $section->getAttribute('id');
            $locationId = str_replace('main-', '', $locationId);
            
            $imageBlock = $xpath->query(".//div[contains(@class, 'pl_image_block')]", $section)->item(0);
            $imageUrl = '';
            if ($imageBlock) {
                $style = $imageBlock->getAttribute('style');
                if (preg_match('/url\((.*?)\)/', $style, $matches)) {
                    $imageUrl = trim($matches[1], "'\"");
                }
            }
            
            $properties[] = [
                'title' => $title ? trim($title->textContent) : '',
                'features' => $featureList,
                'price' => $price ? trim(str_replace(['N$', ' '], '', $price->textContent)) : '',
                'price_description' => $priceDesc ? trim($priceDesc->textContent) : '',
                'image_url' => $imageUrl,
                'locationId' => $locationId
            ];
        }
        
        return $properties;
        
    } catch (Exception $e) {
        error_log("Error parsing HTML: " . $e->getMessage());
        throw $e;
    }
}

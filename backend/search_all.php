<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load environment variables from .env file
function loadEnv() {
    $env_file = __DIR__ . '/../.env';
    if (!file_exists($env_file)) {
        throw new Exception('.env file not found');
    }
    
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Skip comments
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!empty($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load environment variables
loadEnv();

function fetchProperties() {
    try {
        $api_url = $_ENV['GONDWANA_API_URL'];
        $origin = $_ENV['GONDWANA_ORIGIN'];
        $referer = $_ENV['GONDWANA_REFERER'];
        
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
        
        return [
            'success' => true,
            'data' => [
                'total' => $data['total'],
                'properties' => $propertyData
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Error in fetchProperties: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
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

// Execute and return response
$result = fetchProperties();
echo json_encode($result, JSON_PRETTY_PRINT);

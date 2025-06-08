<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function getProperties() {
    $url = 'https://gondwana-collection.com/accommodation/';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Failed to fetch properties page. Status code: " . $httpCode);
    }
    
    $dom = new DOMDocument();
    @$dom->loadHTML($response, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    
    $properties = [];
    
    // Find all property cards/sections
    $propertyNodes = $xpath->query("//div[contains(@class, 'property-card')] | //div[contains(@class, 'accommodation-item')]");
    
    foreach ($propertyNodes as $node) {
        $property = [];
        
        // Get title
        $titleNode = $xpath->query(".//h3 | .//h2", $node)->item(0);
        $property['title'] = $titleNode ? trim($titleNode->textContent) : '';
        
        // Get image
        $imgNode = $xpath->query(".//img", $node)->item(0);
        if ($imgNode) {
            $property['image_url'] = $imgNode->getAttribute('src');
            if (strpos($property['image_url'], '//') === 0) {
                $property['image_url'] = 'https:' . $property['image_url'];
            }
        }
        
        // Get features
        $features = [];
        $featureNodes = $xpath->query(".//ul[contains(@class, 'features')]/li | .//div[contains(@class, 'features')]/span", $node);
        foreach ($featureNodes as $feature) {
            $features[] = trim($feature->textContent);
        }
        $property['features'] = $features;
        
        // Get price
        $priceNode = $xpath->query(".//div[contains(@class, 'price')] | .//span[contains(@class, 'price')]", $node)->item(0);
        $property['price'] = $priceNode ? trim($priceNode->textContent) : 'Price on request';
        
        // Generate location ID from title
        $property['locationId'] = strtolower(str_replace(' ', '-', $property['title']));
        
        // Get booking URL
        $linkNode = $xpath->query(".//a[contains(@class, 'book-now') or contains(@href, 'booking')]", $node)->item(0);
        $property['booking_url'] = $linkNode ? $linkNode->getAttribute('href') : 'https://gondwana-collection.com/accommodation/' . $property['locationId'];
        
        // Get location
        $locationNode = $xpath->query(".//div[contains(@class, 'location')]", $node)->item(0);
        $property['location'] = $locationNode ? trim($locationNode->textContent) : '';
        
        $properties[] = $property;
    }
    
    return $properties;
}

try {
    $properties = getProperties();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'properties' => $properties,
            'total' => count($properties)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'API Error',
        'details' => $e->getMessage()
    ]);
}
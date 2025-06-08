<?php
// Load configuration first to get allowed origins
require_once 'config.php';
require_once __DIR__ . '/helpers.php';

// Only set headers if not in test environment
if (php_sapi_name() !== 'cli') {
    $origins = getAllowedOrigins();
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . (in_array($origin, $origins) ? $origin : $origins[0]));
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$postData = json_decode(file_get_contents('php://input'), true);

if (!$postData) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$requiredFields = ['Arrival', 'Departure', 'Guests'];
$missingFields = array_filter($requiredFields, function($field) use ($postData) {
    return !isset($postData[$field]) || empty($postData[$field]);
});

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required fields',
        'missing' => $missingFields
    ]);
    exit;
}

// Convert dates from DD/MM/YYYY to YYYY-MM-DD
function convertDate($date) {
    if (strpos($date, '/') !== false) {
        // Handle DD/MM/YYYY format
        $parts = explode('/', $date);
        if (count($parts) !== 3) {
            throw new Exception("Invalid date format: $date");
        }
        return sprintf('%s-%s-%s', $parts[2], $parts[1], $parts[0]);
    } else if (strpos($date, '-') !== false) {
        // Already in YYYY-MM-DD format
        return $date;
    }
    throw new Exception("Unsupported date format: $date");
}

function makeApiCall($url, $data = null, $sessionCookie = '') {
    $ch = curl_init($url);
    
    $verbose = fopen('php://temp', 'w+');
    
    // Base headers that work well with most APIs
    $headers = [
        'Accept: application/json',
        'Accept-Language: en-US,en;q=0.9',
        'Origin: https://dev.gondwana-collection.com',
        'Referer: https://dev.gondwana-collection.com/',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko)',
        'Connection: keep-alive'
    ];

    // Add Content-Type only for POST requests with data
    if ($data) {
        $headers[] = 'Content-Type: application/json';
    }

    // Add session cookie if provided
    if ($sessionCookie) {
        $headers[] = 'Cookie: PHPSESSID=' . $sessionCookie;
        error_log("Using session cookie: " . $sessionCookie);
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => $verbose,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,  // Important: Keep this to get Set-Cookie header
        CURLOPT_ENCODING => '',  // Accept all encodings
        CURLOPT_AUTOREFERER => true  // Set Referer automatically on redirects
    ];

    if ($data) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($ch, $options);

    // Execute request and get response
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("Curl error: " . curl_error($ch));
        throw new Exception("Failed to connect to API: " . curl_error($ch));
    }

    // Parse response
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    // Get verbose debug information
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    error_log("Verbose log for $url: " . $verboseLog);
    error_log("Response headers: " . $headers);

    // Extract session cookie if present
    $newSessionId = '';
    if (preg_match('/Set-Cookie: PHPSESSID=([^;]+)/', $headers, $matches)) {
        $newSessionId = $matches[1];
        error_log("Extracted new session ID: " . $newSessionId);
    }

    curl_close($ch);

    // Check for specific error conditions
    if ($httpCode === 403) {
        throw new Exception("Access forbidden. Check Referer and Origin headers.");
    }
    
    if ($httpCode === 429) {
        throw new Exception("Too many requests. Please try again later.");
    }

    return [
        'body' => $body,
        'httpCode' => $httpCode,
        'contentType' => $contentType,
        'verboseLog' => $verboseLog,
        'sessionId' => $newSessionId,
        'headers' => $headers
    ];
}

try {
    // Check if property is provided
    if (!isset($postData['Property']) || empty($postData['Property'])) {
        throw new Exception("Property selection is required");
    }

    // Check if UnitTypeID is provided
    if (!isset($postData['UnitTypeID'])) {
        throw new Exception("Invalid property selection: UnitTypeID not found for " . $postData['Property']);
    }

    // Transform the request to match Gondwana's API format
    $apiRequest = [
        'UnitTypeID' => $postData['UnitTypeID'],
        'Arrival' => $postData['Arrival'],
        'Departure' => $postData['Departure'],
        'Guests' => array_map(function($guest) {
            return ['AgeGroup' => $guest['AgeGroup']];
        }, $postData['Guests']),
        'Currency' => 'NAD',
        'Source' => 'Web',
        'Language' => 'en'
    ];

    error_log("Original request: " . json_encode($apiRequest));

    // Convert dates to YYYY-MM-DD format for the API request
    $apiRequest['Arrival'] = convertDate($apiRequest['Arrival']);
    $apiRequest['Departure'] = convertDate($apiRequest['Departure']);

    error_log("Transformed request with formatted dates: " . json_encode($apiRequest));

    // Make the rates request directly - the API should handle session initialization
    $ratesResponse = makeApiCall(
        'https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php',
        $apiRequest
    );

    error_log("Rates API Response: " . json_encode($ratesResponse));

    if ($ratesResponse['httpCode'] === 500) {
        throw new Exception("Gondwana API returned 500 error. Please ensure all required fields are provided and try again later.");
    }

    if ($ratesResponse['httpCode'] === 404) {
        // Try alternative endpoint
        error_log("Primary endpoint not found, trying alternative...");
        $ratesResponse = makeApiCall(
            'https://dev.gondwana-collection.com/api/rates',
            $apiRequest
        );
    }

    if ($ratesResponse['httpCode'] !== 200) {
        throw new Exception("Unexpected HTTP code: " . $ratesResponse['httpCode']);
    }

    if (empty($ratesResponse['body'])) {
        throw new Exception("Empty response from Gondwana API");
    }

    $responseData = json_decode($ratesResponse['body'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON response: " . json_last_error_msg());
    }

    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'API Error',
        'details' => $e->getMessage(),
        'request' => [
            'original' => $postData ?? null,
            'transformed' => $apiRequest ?? null
        ],
        'debug' => [
            'rates_response' => $ratesResponse ?? null
        ]
    ]);
} 
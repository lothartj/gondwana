<?php
// Load configuration first to get allowed origins
require_once 'config.php';

// Set content type
header('Content-Type: application/json');

// CORS headers with proper origin checking
$allowedOrigins = getAllowedOrigins();
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    header('Access-Control-Max-Age: 86400'); // 24 hours cache
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get JSON payload
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!$data || !validateInput($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Unit Type ID mapping as per assignment
$unitTypeMapping = [
    'Okapuka Safari Lodge' => -2147483637,
    'The Weinberg' => -2147483456
];

// Convert date format from dd/mm/yyyy to yyyy-mm-dd
function convertDate($date) {
    $parts = explode('/', $date);
    if (count($parts) !== 3) return false;
    return sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
}

// Determine age group
function getAgeGroup($age) {
    return $age >= 12 ? 'Adult' : 'Child';
}

// Input validation
function validateInput($data) {
    return isset($data['Unit Name']) &&
           isset($data['Arrival']) &&
           isset($data['Departure']) &&
           isset($data['Occupants']) &&
           isset($data['Ages']) &&
           is_array($data['Ages']);
}

try {
    // Transform the payload
    $unitTypeId = $unitTypeMapping[$data['Unit Name']] ?? null;
    
    if ($unitTypeId === null) {
        throw new Exception('Invalid Unit Name');
    }
    
    // Convert dates from dd/mm/yyyy to yyyy-mm-dd
    $arrival = convertDate($data['Arrival']);
    $departure = convertDate($data['Departure']);
    
    if (!$arrival || !$departure) {
        throw new Exception('Invalid date format. Expected dd/mm/yyyy');
    }
    
    // Transform guests array to required format
    $guests = array_map(function($age) {
        return ['Age Group' => getAgeGroup($age)];
    }, $data['Ages']);
    
    // Prepare payload for Gondwana API
    $payload = [
        'Unit Type ID' => $unitTypeId,
        'Arrival' => $arrival,
        'Departure' => $departure,
        'Guests' => $guests
    ];
    
    // Initialize cURL session
    $ch = curl_init(GONDWANA_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        throw new Exception('Remote API error: HTTP ' . $httpCode);
    }
    
    if (curl_errno($ch)) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Parse response
    $responseData = json_decode($response, true);
    if (!$responseData) {
        throw new Exception('Invalid response from remote API');
    }
    
    // Format response
    $formattedResponse = [
        'success' => true,
        'data' => [
            'unitName' => $data['Unit Name'],
            'rate' => $responseData['rate'] ?? null,
            'dateRange' => [
                'arrival' => $data['Arrival'],
                'departure' => $data['Departure']
            ],
            'availability' => $responseData['availability'] ?? 'Available',
            'occupants' => $data['Occupants'],
            'guests' => array_map(function($guest) {
                return $guest['Age Group'];
            }, $guests)
        ]
    ];
    
    echo json_encode($formattedResponse);
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 
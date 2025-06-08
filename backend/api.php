<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get JSON payload
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!$data || !validateInput($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

// Unit Type ID mapping (you should expand this based on your needs)
$unitTypeMapping = [
    'Test Unit 1' => -2147483637,
    'Test Unit 2' => -2147483456
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
    $unitTypeId = $unitTypeMapping[$data['Unit Name']] ?? -2147483637; // Default to first test ID if not found
    
    // Convert dates
    $arrival = convertDate($data['Arrival']);
    $departure = convertDate($data['Departure']);
    
    if (!$arrival || !$departure) {
        throw new Exception('Invalid date format');
    }
    
    // Transform guests array
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
    $ch = curl_init('https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ]
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        throw new Exception('Remote API error');
    }
    
    // Parse response
    $responseData = json_decode($response, true);
    if (!$responseData) {
        throw new Exception('Invalid response from remote API');
    }
    
    // Add availability status and format response
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 
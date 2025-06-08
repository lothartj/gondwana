<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

function convertDate($date) {
    $parts = explode('/', $date);
    return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
}

function getAgeGroup($age) {
    return $age >= 13 ? 'Adult' : 'Child';
}

$ages = array_map('intval', $input['Ages']);
$guests = array_map(function($age) {
    return ['Age Group' => getAgeGroup($age)];
}, $ages);

$payload = [
    'Unit Type ID' => -2147483637,
    'Arrival' => convertDate($input['Arrival']),
    'Departure' => convertDate($input['Departure']),
    'Guests' => $guests
];

$ch = curl_init('https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'External API error']);
    exit;
}

$result = json_decode($response, true);
$result['unitName'] = $input['Unit Name'];

echo json_encode($result); 
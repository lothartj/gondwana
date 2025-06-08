<?php
// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// API Configuration
define('GONDWANA_API_URL', 'https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php');

// Constants
define('MIN_AGE_ADULT', 12);

// Time zone
date_default_timezone_set('Africa/Windhoek');

// CORS Configuration
$allowedOrigins = [
    'http://localhost:8000',
    'http://127.0.0.1:8000'
];

function getAllowedOrigins() {
    global $allowedOrigins;
    return $allowedOrigins;
}

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
} 
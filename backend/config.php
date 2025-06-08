<?php
// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// API Configuration
define('GONDWANA_API_ENDPOINT', 'https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php');

// Constants
define('MIN_AGE_ADULT', 12);

// Time zone
date_default_timezone_set('Africa/Windhoek');

/**
 * Get allowed origins for CORS
 * In production, this should be loaded from environment variables
 * @return array
 */
function getAllowedOrigins() {
    // For development
    if (getenv('APP_ENV') === 'development') {
        return [
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost:3000'  // If using a different port
        ];
    }
    
    // For production
    return [
        'https://your-production-domain.com',
        'https://gondwana-collection.com'
    ];
}

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
} 
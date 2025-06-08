<?php

/**
 * Convert date between formats
 */
function convertDate($date) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj) {
        return false;
    }
    return $dateObj->format('d/m/Y');
}

/**
 * Determine age group
 */
function determineAgeGroup($age) {
    return $age >= 12 ? 'Adult' : 'Child';
}

/**
 * Validate booking input
 */
function validateBookingInput($input) {
    $required = ['arrival', 'departure', 'occupants', 'ages'];
    
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            return false;
        }
    }
    
    if (count($input['ages']) !== $input['occupants']) {
        return false;
    }
    
    return true;
}

/**
 * Get allowed origins based on environment
 */
function getAllowedOrigins() {
    $origins = ['https://gondwana-collection.com'];
    
    if (getenv('APP_ENV') === 'development') {
        $origins = array_merge($origins, [
            'http://localhost:8000',
            'http://127.0.0.1:8000'
        ]);
    }
    
    return $origins;
} 
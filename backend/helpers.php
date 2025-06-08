<?php

/**
 * Convert date between formats
 * If input is Y-m-d, converts to d/m/Y
 * If input is d/m/Y, converts to Y-m-d
 * Returns false if invalid format
 */
function convertDate($date) {
    // Try Y-m-d to d/m/Y first
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if ($dateObj) {
        return $dateObj->format('d/m/Y');
    }
    
    // Try d/m/Y to Y-m-d
    if (strpos($date, '/') !== false) {
        $parts = explode('/', $date);
        if (count($parts) === 3) {
            return sprintf('%s-%s-%s', $parts[2], $parts[1], $parts[0]);
        }
    }
    
    return false;
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
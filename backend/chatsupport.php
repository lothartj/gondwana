<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Only set headers if not in test environment
if (php_sapi_name() !== 'cli') {
    $origins = getAllowedOrigins();
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . (in_array($origin, $origins) ? $origin : $origins[0]));
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Required environment variables
$required_env_vars = [
    'API_KEY',
    'MODEL_NAME',
    'API_URL'
];

try {
    // Get environment variables from multiple sources
    foreach ($required_env_vars as $var) {
        // Try all possible sources
        $value = $_ENV[$var] ?? getenv($var) ?? $_SERVER[$var] ?? null;
        
        if (empty($value)) {
            throw new Exception("Required environment variable {$var} is not set or empty");
        }
        
        // Store in $_ENV for consistent access
        $_ENV[$var] = $value;
    }
    
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }
    
    // Get the raw POST data
    $post_data = file_get_contents('php://input');
    $data = json_decode($post_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON payload: ' . json_last_error_msg());
    }
    
    // Validate required fields
    if (!isset($data['message']) || empty(trim($data['message']))) {
        throw new Exception('Message is required');
    }
    
    $user_message = trim($data['message']);
    $conversation_history = $data['history'] ?? [];
    
    // Prepare message history for AI
    $messages = [];
    
    // System message
    $messages[] = [
        'role' => 'system',
        'content' => 'You are a knowledgeable and friendly customer service assistant for Gondwana Collection Namibia, a premier luxury lodge operator in Namibia. Your role is to provide concise, accurate, and helpful responses exclusively about Gondwana\'s offerings, including accommodations, activities, booking information, and travel tips. 
    
    Key Guidelines:
    
    1. Scope of Information:
    - Respond only to inquiries related to Gondwana Collection Namibia.
    - If a question falls outside this scope, politely suggest contacting Gondwana\'s reservations team at info@gondwana-collection.com or call +264 61 427 200.
    
    2. Tone and Style:
    - Maintain a friendly, professional, and approachable tone.
    - Provide clear and concise information.
    
    3. Office and Contact Information:
    - Main Office Address: 42 Nelson Mandela Avenue (Entrance on Bassingthwaighte Street), Klein Windhoek, Namibia.
    - Booking & Gondwana Card Office: Eaton Heights Building, 9 Bassingthwaighte Street, Klein Windhoek.
    - Contact Numbers: +264 61 427 200 | After Hours: +264 81 129 2424 / +264 81 165 2805
    - Emails:
      - General: info@gondwana-collection.com
      - Reservations: directres@gcnam.com
      - Individual Travel: travel@gcnam.com
      - Tour Operator/DMC: tours@gcnam.com
      - Corporate Travel: corporate@gcnam.com
    
    4. Accommodations:
    - Gondwana offers over 40 unique properties including lodges, hotels, self-catering units, and campsites.
    - Categories:
      - Lodges & Hotels: e.g., The Weinberg Windhoek, Kalahari Anib Lodge, Canyon Lodge.
      - Self-Catering: e.g., Kalahari Anib Camping2Go, Namib Desert Camping2Go.
      - Campsites: e.g., Kalahari Anib Campsite, Namib Desert Campsite.
    - Each property reflects Namibia\'s diverse landscapes and culture.
    
    5. Activities:
    - Include: Guided nature drives, hiking trails, cultural experiences, river cruises, and stargazing.
    - Availability varies by property (e.g., Namushasha River Lodge offers river cruises).
    
    6. Booking Info:
    - Book via the official site: www.gondwana-collection.com
    - Reservations are confirmed after payment within 72 hours of receiving a Booking Summary.
    
    7. Travel Tips:
    - Pack for temperature changes: light layers, sunscreen, hat, insect repellent, good walking shoes.
    - Carry copies of travel documents.
    - Stay hydrated and follow safety advice around wildlife.
    
    8. Sustainability:
    - Gondwana supports conservation, community development, and eco-friendly operations.
    
    9. Gondwana Card:
    - Membership gives access to discounted rates and exclusive offers.
    - Sign up via the website or customer service.
    
    10. Scheduled Tours:
    - Examples:
      - 12-Day Go Discover Tour: Kalahari, Sossusvlei, Swakopmund, Damaraland, Etosha.
      - 13-Day Go Epic Tour: Grand tour of Namibia\'s highlights.
    - Tours include lodging, activities, and transportation.
    
    Only provide information related to Gondwana Collection Namibia. For anything else, refer users to: info@gondwana-collection.com or +264 61 427 200.'
    ];
    
    
    // Add conversation history
    foreach ($conversation_history as $message) {
        $messages[] = [
            'role' => $message['role'],
            'content' => $message['content']
        ];
    }
    
    // Add the current user message
    $messages[] = [
        'role' => 'user',
        'content' => $user_message
    ];
    
    // Prepare payload for GROQ API
    $payload = [
        'model' => $_ENV['MODEL_NAME'],
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 300
    ];
    
    // Fix the API URL by removing any trailing % character
    $api_url = rtrim($_ENV['API_URL'], '%');
    
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_ENV['API_KEY']
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
    
    $response_data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Failed to parse API response: ' . json_last_error_msg());
    }
    
    // Extract the assistant's response
    $assistant_response = $response_data['choices'][0]['message']['content'] ?? null;
    
    if (!$assistant_response) {
        throw new Exception('No response from AI assistant');
    }
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'message' => $assistant_response,
        'timestamp' => date('H:i')
    ]);
    
} catch (Exception $e) {
    error_log("Error in chatsupport.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'env_vars_status' => array_map(function($var) {
                return [
                    'name' => $var,
                    'env' => isset($_ENV[$var]) ? 'SET' : 'NOT SET',
                    'getenv' => getenv($var) ? 'SET' : 'NOT SET',
                    'server' => isset($_SERVER[$var]) ? 'SET' : 'NOT SET'
                ];
            }, $required_env_vars),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
        ]
    ]);
}

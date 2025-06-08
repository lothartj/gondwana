<?php
// Get the requested URI
$request_uri = $_SERVER['REQUEST_URI'];

// Remove query string if present
$request_uri = strtok($request_uri, '?');

// If requesting the root, redirect to frontend/index.html
if ($request_uri === '/' || $request_uri === '/index.php') {
    header('Location: /frontend/index.html');
    exit();
}

// If requesting .well-known/appspecific, return 404
if (strpos($request_uri, '/.well-known/appspecific') === 0) {
    http_response_code(404);
    exit();
}

// For all other requests, serve the file if it exists
$file_path = __DIR__ . $request_uri;
if (file_exists($file_path) && is_file($file_path)) {
    // Set content type based on file extension
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    $content_types = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif'
    ];
    
    if (isset($content_types[$extension])) {
        header('Content-Type: ' . $content_types[$extension]);
    }
    
    readfile($file_path);
    exit();
}

// If file not found, return 404
http_response_code(404);
?> 
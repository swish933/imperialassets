<?php

// Allow requests from your local domain
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, access_token, x-tenantid');
header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours

// Handle preflight OPTIONS requests explicitly
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Just return the headers and exit
    exit(0);
}

// Get the API URL from the request
$apiUrl = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($apiUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'No API URL provided']);
    exit;
}

// Get request method and body
$method = $_SERVER['REQUEST_METHOD'];
$requestBody = file_get_contents('php://input');

// Initialize cURL
$ch = curl_init($apiUrl);

// Set common cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); // Important: Set the correct HTTP method

// Set request body for methods that support it
if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($requestBody)) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
}

// Forward request headers
$headers = [];
foreach (getallheaders() as $key => $value) {
    // Skip certain headers
    if (!in_array(strtolower($key), ['host', 'origin', 'referer', 'content-length'])) {
        $headers[] = "$key: $value";
    }
}

// Add content-type if not provided
if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($requestBody)) {
    $contentTypeFound = false;
    foreach ($headers as $header) {
        if (stripos($header, 'content-type:') === 0) {
            $contentTypeFound = true;
            break;
        }
    }
    if (!$contentTypeFound) {
        $headers[] = 'Content-Type: application/json';
    }
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Output debugging information
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug) {
    echo "Debug info:<br>";
    echo "Method: $method<br>";
    echo "URL: $apiUrl<br>";
    echo "Headers: " . print_r($headers, true) . "<br>";
    echo "Body: $requestBody<br><hr>";
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
}

// Execute the request
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);

// Debug output
if ($debug) {
    echo "Response code: " . $info['http_code'] . "<br>";
    if ($error) {
        echo "cURL Error: $error<br>";
    }
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    echo "Verbose log:<br><pre>" . htmlspecialchars($verboseLog) . "</pre><hr>";
}

// Close cURL
curl_close($ch);

// Return the API response with the same status code
http_response_code($info['http_code']);

// Forward content-type header
if (isset($info['content_type'])) {
    header('Content-Type: ' . $info['content_type']);
}

if (!$debug) {
    echo $response;
}

?>

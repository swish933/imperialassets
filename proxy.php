<?php

// // Allow requests from your local domain
// header('Access-Control-Allow-Origin: *'); 
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, access_token, x-tenantid');
// header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours

// // Handle preflight OPTIONS requests explicitly
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     // Just return the headers and exit
//     exit(0);
// }

// // Get the API URL from the request
// $apiUrl = isset($_GET['url']) ? $_GET['url'] : '';

// if (empty($apiUrl)) {
//     http_response_code(400);
//     echo json_encode(['error' => 'No API URL provided']);
//     exit;
// }

// // Get request method and body
// $method = $_SERVER['REQUEST_METHOD'];
// $requestBody = file_get_contents('php://input');

// // Initialize cURL
// $ch = curl_init($apiUrl);

// // Set common cURL options
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); // Important: Set the correct HTTP method

// // Set request body for methods that support it
// if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($requestBody)) {
//     curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
// }

// // Forward request headers
// $headers = [];
// foreach (getallheaders() as $key => $value) {
//     // Skip certain headers
//     if (!in_array(strtolower($key), ['host', 'origin', 'referer', 'content-length'])) {
//         $headers[] = "$key: $value";
//     }
// }

// // Add content-type if not provided
// if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($requestBody)) {
//     $contentTypeFound = false;
//     foreach ($headers as $header) {
//         if (stripos($header, 'content-type:') === 0) {
//             $contentTypeFound = true;
//             break;
//         }
//     }
//     if (!$contentTypeFound) {
//         $headers[] = 'Content-Type: application/json';
//     }
// }

// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// // Output debugging information
// $debug = isset($_GET['debug']) && $_GET['debug'] === '1';
// if ($debug) {
//     echo "Debug info:<br>";
//     echo "Method: $method<br>";
//     echo "URL: $apiUrl<br>";
//     echo "Headers: " . print_r($headers, true) . "<br>";
//     echo "Body: $requestBody<br><hr>";
//     curl_setopt($ch, CURLOPT_VERBOSE, true);
//     $verbose = fopen('php://temp', 'w+');
//     curl_setopt($ch, CURLOPT_STDERR, $verbose);
// }

// // Execute the request
// $response = curl_exec($ch);
// $info = curl_getinfo($ch);
// $error = curl_error($ch);

// // Debug output
// if ($debug) {
//     echo "Response code: " . $info['http_code'] . "<br>";
//     if ($error) {
//         echo "cURL Error: $error<br>";
//     }
//     rewind($verbose);
//     $verboseLog = stream_get_contents($verbose);
//     echo "Verbose log:<br><pre>" . htmlspecialchars($verboseLog) . "</pre><hr>";
// }

// // Close cURL
// curl_close($ch);

// // Return the API response with the same status code
// http_response_code($info['http_code']);

// // Forward content-type header
// if (isset($info['content_type'])) {
//     header('Content-Type: ' . $info['content_type']);
// }

// if (!$debug) {
//     echo $response;
// }


// Allow requests from your local domain
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, access_token, x-tenantid');
header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours

// Handle preflight OPTIONS requests explicitly
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the API URL from the request
$apiUrl = isset($_GET['url']) ? $_GET['url'] : '';
if (empty($apiUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'No API URL provided']);
    exit;
}

// Get all query parameters sent to the proxy
$proxyQueryParams = $_GET;

// Remove the 'url' and 'debug' parameters as they are handled separately
unset($proxyQueryParams['url']);
unset($proxyQueryParams['debug']);

// If there are additional query parameters, append them to the target API URL
if (!empty($proxyQueryParams)) {
    // Check if the target API URL already has query parameters
    $separator = (strpos($apiUrl, '?') === false) ? '?' : '&';
    $apiUrl .= $separator . http_build_query($proxyQueryParams);
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Initialize cURL
$ch = curl_init($apiUrl);

// Set common cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Handle different content types
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isMultipart = strpos($contentType, 'multipart/form-data') !== false;

// Forward request headers (excluding problematic ones)
$headers = [];
$skipHeaders = ['host', 'origin', 'referer', 'content-length'];

foreach (getallheaders() as $key => $value) {
    $lowerKey = strtolower($key);
    
    // Skip problematic headers
    if (in_array($lowerKey, $skipHeaders)) {
        continue;
    }
    
    // For multipart, skip content-type to let cURL handle it
    if ($isMultipart && $lowerKey === 'content-type') {
        continue;
    }
    
    $headers[] = "$key: $value";
}

// Handle request body based on content type
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    if ($isMultipart) {
        // Handle multipart/form-data
        $postData = [];
        
        // Process $_POST data
        foreach ($_POST as $key => $value) {
            $postData[$key] = $value;
        }
        
        // Process $_FILES data
        foreach ($_FILES as $key => $file) {
            if (is_array($file['name'])) {
                // Handle multiple files with same name
                $postData[$key] = [];
                for ($i = 0; $i < count($file['name']); $i++) {
                    if ($file['error'][$i] === UPLOAD_ERR_OK) {
                        $postData[$key][] = new CURLFile(
                            $file['tmp_name'][$i],
                            $file['type'][$i],
                            $file['name'][$i]
                        );
                    }
                }
            } else {
                // Handle single file
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $postData[$key] = new CURLFile(
                        $file['tmp_name'],
                        $file['type'],
                        $file['name']
                    );
                }
            }
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        // Don't set content-type header - let cURL handle multipart boundary
        
    } else {
        // Handle other content types (JSON, XML, etc.)
        $requestBody = file_get_contents('php://input');
        
        if (!empty($requestBody)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        }
        
        // Add content-type if not provided and body exists
        if (!empty($requestBody)) {
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
    }
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Output debugging information
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug) {
    echo "<h3>Debug Information:</h3>";
    echo "<strong>Method:</strong> $method<br>";
    echo "<strong>URL:</strong> $apiUrl<br>";
    echo "<strong>Content-Type:</strong> $contentType<br>";
    echo "<strong>Is Multipart:</strong> " . ($isMultipart ? 'Yes' : 'No') . "<br>";
    echo "<strong>Headers:</strong><br><pre>" . print_r($headers, true) . "</pre>";
    
    if ($isMultipart) {
        echo "<strong>POST Data:</strong><br><pre>" . print_r($_POST, true) . "</pre>";
        echo "<strong>FILES Data:</strong><br><pre>" . print_r($_FILES, true) . "</pre>";
    } else {
        $requestBody = file_get_contents('php://input');
        echo "<strong>Request Body:</strong><br><pre>" . htmlspecialchars($requestBody) . "</pre>";
    }
    echo "<hr>";
    
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
}

// Execute the request
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);

// Debug output for response
if ($debug) {
    echo "<h3>Response Information:</h3>";
    echo "<strong>HTTP Code:</strong> " . $info['http_code'] . "<br>";
    echo "<strong>Content Type:</strong> " . ($info['content_type'] ?? 'N/A') . "<br>";
    
    if ($error) {
        echo "<strong>cURL Error:</strong> $error<br>";
    }
    
    if (isset($verbose)) {
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        echo "<strong>Verbose Log:</strong><br><pre>" . htmlspecialchars($verboseLog) . "</pre>";
    }
    
    echo "<strong>Response:</strong><br><pre>" . htmlspecialchars($response) . "</pre>";
    echo "<hr>";
}

// Close cURL
curl_close($ch);

// Return the API response with the same status code
http_response_code($info['http_code']);

// Forward content-type header from response
if (isset($info['content_type'])) {
    header('Content-Type: ' . $info['content_type']);
}

// Output response (unless in debug mode where we already showed it)
if (!$debug) {
    echo $response;
}

// Clean up verbose resource if it exists
if (isset($verbose)) {
    fclose($verbose);
}


?>

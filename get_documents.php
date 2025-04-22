<?php
header('Content-Type: application/json');

$documentsFile = 'documents.json';

// Check if file exists and create it if not
if (!file_exists($documentsFile)) {
    file_put_contents($documentsFile, json_encode([]));
    echo json_encode([]);
    exit;
}

// Read documents from file
$documentData = file_get_contents($documentsFile);

// Check if data is valid JSON
$documents = json_decode($documentData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Error reading documents data']);
    exit;
}

// Return documents list
echo json_encode($documents);
?>
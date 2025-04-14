<?php
header('Content-Type: application/json');

$documentsFile = 'documents.json';

// Check if document ID is provided
$docId = $_GET['id'] ?? null;
if (!$docId) {
    http_response_code(400);
    echo json_encode(['error' => 'Document ID is required']);
    exit;
}

// Load documents from file
if (!file_exists($documentsFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'No documents found']);
    exit;
}

$documents = json_decode(file_get_contents($documentsFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Error reading documents data']);
    exit;
}

// Find the requested document
$document = null;
foreach ($documents as $doc) {
    if ($doc['id'] === $docId) {
        $document = $doc;
        break;
    }
}

if (!$document) {
    http_response_code(404);
    echo json_encode(['error' => 'Document not found']);
    exit;
}

// Return the document data
echo json_encode($document);
?>

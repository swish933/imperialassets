<?php
header('Content-Type: application/json');

// Check if required parameters are provided
if (!isset($_POST['id']) || !isset($_POST['fileUrl'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Document ID and file URL are required']);
    exit;
}

$docId = $_POST['id'];
$fileUrl = $_POST['fileUrl'];
$documentsFile = 'documents.json';

// Check if documents file exists
if (!file_exists($documentsFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Documents file not found']);
    exit;
}

// Load documents from file
$documents = json_decode(file_get_contents($documentsFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error reading documents data']);
    exit;
}

// Find document index
$documentIndex = -1;
foreach ($documents as $index => $doc) {
    if ($doc['id'] === $docId) {
        $documentIndex = $index;
        break;
    }
}

// If document not found
if ($documentIndex === -1) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Document not found']);
    exit;
}

// Delete the physical file
if (file_exists($fileUrl) && is_file($fileUrl)) {
    unlink($fileUrl);
}

// Remove document from array
array_splice($documents, $documentIndex, 1);

// Save updated documents to file
if (file_put_contents($documentsFile, json_encode($documents, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update documents data']);
}
?>



<?php
header('Content-Type: application/json');

$documentsFile = 'documents.json';

// Get input data
$docId = $_POST['id'] ?? null;
$fileUrl = $_POST['fileUrl'] ?? null;

if (!$docId || !$fileUrl) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Load existing documents
if (!file_exists($documentsFile)) {
    echo json_encode(['success' => false, 'error' => 'Documents file not found']);
    exit;
}

$documents = json_decode(file_get_contents($documentsFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Error reading documents']);
    exit;
}

// Find and remove the document
$newDocuments = array_filter($documents, function($doc) use ($docId) {
    return $doc['id'] !== $docId;
});

// Try to delete the physical file
$fileDeleted = true;
if (file_exists($fileUrl)) {
    $fileDeleted = unlink($fileUrl);
}

// Save the updated documents list
if (file_put_contents($documentsFile, json_encode(array_values($newDocuments))) === false) {
    echo json_encode(['success' => false, 'error' => 'Could not save documents']);
    exit;
}

echo json_encode([
    'success' => true,
    'fileDeleted' => $fileDeleted
]);
?>

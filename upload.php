<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Document upload directory
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Documents metadata file
$documentsFile = 'documents.json';

// Load existing documents
if (file_exists($documentsFile)) {
    $documents = json_decode(file_get_contents($documentsFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $documents = [];
    }
} else {
    $documents = [];
}

// Check if file was uploaded
if (!isset($_FILES['user-file']) || $_FILES['user-file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

// Check if title was provided
if (!isset($_POST['title']) || empty($_POST['title'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Document title is required']);
    exit;
}

$title = $_POST['title'];
$file = $_FILES['user-file'];
$fileName = $file['name'];
$fileTmpPath = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];

// Generate a unique ID for the document
$docId = uniqid();

// Generate unique filename to prevent overwriting
$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
$newFileName = $docId . '.' . $fileExtension;
$destination = $uploadDir . $newFileName;

// Move the uploaded file to our upload directory
if (!move_uploaded_file($fileTmpPath, $destination)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
    exit;
}

// Create document metadata
$newDocument = [
    'id' => $docId,
    'title' => $title,
    'uploadDate' => date('Y-m-d H:i:s'),
    'fileUrl' => $destination,
    'fileType' => $fileType
];

// Add to documents array
$documents[] = $newDocument;

// Save updated documents to JSON file
if (file_put_contents($documentsFile, json_encode($documents, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'docId' => $docId,
        'fileUrl' => $destination
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save document metadata']);
    
    // Clean up the uploaded file if metadata couldn't be saved
    if (file_exists($destination)) {
        unlink($destination);
    }
}
?>






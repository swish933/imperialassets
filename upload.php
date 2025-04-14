<?php
header('Content-Type: application/json');

// Ensure documents.json exists
if (!file_exists('documents.json')) {
    file_put_contents('documents.json', '[]');
}

// File to store document metadata
$documentsFile = 'documents.json';

// Check if a file is uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['user-file'])) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['user-file'];
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);

    // Validate file type (allow images and PDFs)
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array(strtolower($fileExt), $allowedExts)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type.']);
        exit;
    }

    // Generate a random file name
    $randomFileName = bin2hex(random_bytes(10)) . '.' . $fileExt;

    // Move the file to the uploads directory
    $filePath = $uploadDir . $randomFileName;
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Create document metadata
        $docId = 'doc_' . time();
        $document = [
            'id' => $docId,
            'title' => $_POST['title'] ?? $file['name'],
            'uploadDate' => date('Y-m-d H:i:s'),
            'fileUrl' => $filePath,
            'fileType' => $file['type']
        ];

        // Load existing documents or create new array
        $documents = file_exists($documentsFile) ? json_decode(file_get_contents($documentsFile), true) : [];
        
        // Add new document
        $documents[] = $document;
        
        // Save back to file
        file_put_contents($documentsFile, json_encode($documents));

        // Return success with both file URL and document ID
        echo json_encode([
            'success' => true,
            'fileUrl' => $filePath,
            'docId' => $docId
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload file.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
}
?>

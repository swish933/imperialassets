<?php
header('Content-Type: application/json');

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
        echo json_encode(['success' => true, 'fileUrl' => $filePath]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload file.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
}
?>

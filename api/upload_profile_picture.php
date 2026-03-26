<?php
session_start();
include_once '../config/config.php';
include_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$file = $_FILES['profile_picture'];
$user_id = $_SESSION['user_id'];

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error: ' . $file['error']]);
    exit();
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit();
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit();
}

// Create upload directory if it doesn't exist - using absolute path
$upload_dir = dirname(__DIR__) . '/uploads/profile_pictures/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit();
    }
}

// Ensure directory is writable
if (!is_writable($upload_dir)) {
    chmod($upload_dir, 0777);
}

// Generate unique filename
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Delete old profile picture if exists
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && !empty($result['profile_picture'])) {
        $old_file = dirname(__DIR__) . '/' . $result['profile_picture'];
        if (file_exists($old_file) && is_file($old_file)) {
            unlink($old_file);
        }
    }

    // Update database with new profile picture path - store relative path from root
    $relative_path = 'uploads/profile_pictures/' . $filename;
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $relative_path, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile picture uploaded successfully', 'path' => $relative_path]);
    } else {
        // Delete the file if database update fails
        unlink($upload_path);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile picture in database: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file. Upload dir: ' . $upload_dir]);
}
?>



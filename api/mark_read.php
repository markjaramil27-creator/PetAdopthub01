<?php
// api/mark_read.php - Mark messages as read

header('Content-Type: application/json');
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$sender_id = isset($_POST['sender_id']) ? intval($_POST['sender_id']) : 0;
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;

if (!$sender_id || !$receiver_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

// Mark messages as read
$stmt = $conn->prepare("
    UPDATE messages 
    SET is_read = TRUE 
    WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
");
$stmt->bind_param("ii", $sender_id, $receiver_id);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Messages marked as read']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to mark messages as read']);
}
?>

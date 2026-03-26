<?php
header('Content-Type: application/json');
session_start();

include_once '../config/database.php';
include_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get preference data
$animal_types = isset($_POST['animal_types']) ? json_decode($_POST['animal_types'], true) : [];
$sizes = isset($_POST['sizes']) ? json_decode($_POST['sizes'], true) : [];
$ages = isset($_POST['ages']) ? json_decode($_POST['ages'], true) : [];

// Validate inputs
if (!is_array($animal_types) || !is_array($sizes) || !is_array($ages)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid preference data']);
    exit();
}

// Convert arrays to JSON strings for storage
$animal_types_json = json_encode($animal_types);
$sizes_json = json_encode($sizes);
$ages_json = json_encode($ages);

// Update preferences in database
$stmt = $conn->prepare("
    UPDATE users 
    SET preferred_animal_types = ?, preferred_sizes = ?, preferred_ages = ?
    WHERE id = ?
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("sssi", $animal_types_json, $sizes_json, $ages_json, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Preferences saved successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save preferences']);
}

$stmt->close();
$conn->close();
?>

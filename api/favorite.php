<?php
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add to favorites.']);
    exit();
}

// Check if pet ID is provided
if (!isset($_GET['pet_id']) || empty($_GET['pet_id'])) {
    echo json_encode(['success' => false, 'message' => 'Pet ID is required.']);
    exit();
}

$pet_id = $_GET['pet_id'];
$user_id = $_SESSION['user_id'];
$remove = isset($_GET['remove']) && $_GET['remove'] == 1;

// Check if pet exists
$stmt = $conn->prepare("SELECT id FROM pets WHERE id = ?");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Pet not found.']);
    exit();
}

if ($remove) {
    // Remove from favorites
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND pet_id = ?");
    $stmt->bind_param("ii", $user_id, $pet_id);
    
    if ($stmt->execute()) {
        // Redirect back to the referrer page
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        header("Location: " . $referrer);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites.']);
        exit();
    }
} else {
    // Add to favorites
    $stmt = $conn->prepare("INSERT INTO favorites (user_id, pet_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $pet_id);
    
    if ($stmt->execute()) {
        // Redirect back to the referrer page
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        header("Location: " . $referrer);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to favorites.']);
        exit();
    }
}
?>
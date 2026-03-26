<?php
// Prevent accidental PHP warnings/notice output which would break JSON responses
@ini_set('display_errors', '0');
@error_reporting(0);
header('Content-Type: application/json');
include_once '../config/database.php';
include_once '../config/config.php';
include_once '../includes/functions.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Pet ID required']);
    exit;
}

$pet_id = $_GET['id'];

$stmt = $conn->prepare("SELECT id, name, type, age, breed, gender, size, weight, description, personality, health_status, shelter_name, shelter_email, shelter_phone, shelter_address, status, image FROM pets WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $pet = $result->fetch_assoc();
    
    // Handle single image or check if it's JSON array of images
    $images = [];
    if (!empty($pet['image'])) {
        // Try to decode as JSON array
        $decoded = json_decode($pet['image'], true);
        if (is_array($decoded)) {
            // Normalize each image path
            foreach ($decoded as $img) {
                $images[] = normalizePetImagePath($img, DEFAULT_PET_IMAGE_URL);
            }
        } else {
            // Single image - normalize it
            $images = [normalizePetImagePath($pet['image'], DEFAULT_PET_IMAGE_URL)];
        }
    }
    
    // If no images, use default
    if (empty($images)) {
        $images = [DEFAULT_PET_IMAGE_URL];
    }
    
    // Add normalized images array to pet data
    $pet['images'] = $images;
    
    echo json_encode(['success' => true, 'pet' => $pet]);
} else {
    echo json_encode(['success' => false, 'message' => 'Pet not found']);
}

$stmt->close();
$conn->close();
?>

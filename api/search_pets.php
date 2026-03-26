<?php
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Get search parameters
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$age = isset($_GET['age']) ? trim($_GET['age']) : '';

// Search pets
$pets = searchPets($keyword, $type, $age);

// Format results
$results = [];
foreach ($pets as $pet) {
    $img = normalizePetImagePath($pet['image'] ?? '', DEFAULT_PET_IMAGE_URL);
    $results[] = [
        'id' => $pet['id'],
        'name' => $pet['name'],
        'type' => $pet['type'],
        'breed' => $pet['breed'],
        'age' => $pet['age'],
        'gender' => $pet['gender'],
        'size' => $pet['size'],
        'weight' => $pet['weight'],
        'description' => $pet['description'],
        'image' => $img
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($results);
?>
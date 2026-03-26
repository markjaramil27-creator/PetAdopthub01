<?php
include_once '../../config/config.php';
include_once '../../config/database.php';
include_once '../../includes/functions.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $pet_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pet = $result->fetch_assoc();
        // normalize image paths and expose as an array to simplify client code
        $pet['images'] = [];
        if (!empty($pet['image'])) {
            $imgs = json_decode($pet['image'], true);
            if (!is_array($imgs)) {
                $imgs = [$pet['image']];
            }
            foreach ($imgs as $img) {
                if ($img !== null && $img !== '') {
                    $pet['images'][] = normalizePetImagePath($img);
                }
            }
        }
        echo json_encode([
            'success' => true,
            'pet' => $pet
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Pet not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>

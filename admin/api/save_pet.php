<?php
include_once '../../config/config.php';
include_once '../../config/database.php';

// Log all errors to a file for debugging
$log_file = '../../logs/pet_save.log';
if (!is_dir('../../logs')) {
    mkdir('../../logs', 0777, true);
}

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

log_message("=== New Pet Save Request ===");
log_message("Method: " . $_SERVER['REQUEST_METHOD']);
log_message("POST Data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id = isset($_POST['pet_id']) && $_POST['pet_id'] !== '' ? (int)$_POST['pet_id'] : null;
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $type = htmlspecialchars(trim($_POST['type'] ?? ''));
    $breed = htmlspecialchars(trim($_POST['breed'] ?? ''));
    $age = (float)($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? 'Male';
    $size = $_POST['size'] ?? 'Medium';
    $weight = htmlspecialchars(trim($_POST['weight'] ?? ''));
    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
    $personality = htmlspecialchars(trim($_POST['personality'] ?? ''));
    $health_status = htmlspecialchars(trim($_POST['health_status'] ?? ''));
    $shelter_name = htmlspecialchars(trim($_POST['shelter_name'] ?? ''));
    $shelter_email = htmlspecialchars(trim($_POST['shelter_email'] ?? ''));
    $shelter_phone = htmlspecialchars(trim($_POST['shelter_phone'] ?? ''));
    $shelter_address = htmlspecialchars(trim($_POST['shelter_address'] ?? ''));
    $status = trim($_POST['status'] ?? 'Available');

    log_message("Pet ID: $pet_id, Pet Name: $name, Type: $type, Breed: $breed, Age: $age, Status: '$status'");

    $image_paths = array(); // Store all image paths

    // Handle multiple image uploads
    if (isset($_FILES['image']) && is_array($_FILES['image']['name'])) {
        log_message("Multiple image upload detected: " . count($_FILES['image']['name']) . " file(s)");
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Use DOCUMENT_ROOT to find the absolute path to the project root
        $root_dir = $_SERVER['DOCUMENT_ROOT'];
        $upload_dir = $root_dir . '/uploads/pets/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                log_message("Failed to create upload directory: " . $upload_dir);
            }
        }

        // Ensure directory is writable
        if (!is_writable($upload_dir)) {
            chmod($upload_dir, 0777);
        }
        
        // Process each file
        for ($i = 0; $i < count($_FILES['image']['name']); $i++) {
            if ($_FILES['image']['error'][$i] === UPLOAD_ERR_OK && $_FILES['image']['size'][$i] > 0) {
                $file_name = $_FILES['image']['name'][$i];
                $file_tmp = $_FILES['image']['tmp_name'][$i];
                $file_size = $_FILES['image']['size'][$i];
                
                log_message("Processing image $i: " . $file_name);
                
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, $allowed_extensions) && $file_size <= 5 * 1024 * 1024) {
                    $filename = 'pet_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $filepath = $upload_dir . $filename;

                    if (move_uploaded_file($file_tmp, $filepath)) {
                        // Save relative path for database storage
                        $image_path = 'uploads/pets/' . $filename;
                        // ensure no leading slash stored
                        $image_path = ltrim($image_path, '/');
                        $image_paths[] = $image_path;
                        log_message("Image $i saved successfully: " . $image_path);
                    } else {
                        log_message("Failed to move uploaded file $i to: " . $filepath);
                    }
                } else {
                    log_message("Invalid file type or size for file $i. Extension: $file_ext, Size: " . $file_size);
                }
            } else if ($_FILES['image']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                log_message("File upload error $i: " . $_FILES['image']['error'][$i]);
            }
        }
    } else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
        // Handle single file upload (backward compatibility)
        log_message("Single image upload detected: " . $_FILES['image']['name']);
        $file = $_FILES['image'];
        
        // Validate file extension
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_extensions) && $file['size'] <= 5 * 1024 * 1024) {
            
            // Use DOCUMENT_ROOT to find the absolute path to the project root
            $root_dir = $_SERVER['DOCUMENT_ROOT'];
            $upload_dir = $root_dir . '/uploads/pets/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    log_message("Failed to create upload directory: " . $upload_dir);
                }
            }

            // Ensure directory is writable
            if (!is_writable($upload_dir)) {
                chmod($upload_dir, 0777);
            }

            $filename = 'pet_' . time() . '_' . uniqid() . '.' . $file_ext;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save relative path for database storage
                $image_path = 'uploads/pets/' . $filename;
                $image_path = ltrim($image_path, '/');
                $image_paths[] = $image_path;
                log_message("Image saved successfully: " . $image_path);
            } else {
                log_message("Failed to move uploaded file to: " . $filepath);
            }
        } else {
            log_message("Invalid file type or size. Extension: $file_ext, Size: " . $file['size']);
        }
    }
    
    // Use first image as primary image, or store all as JSON
    $image_path = !empty($image_paths) ? $image_paths[0] : null;
    $all_images_json = !empty($image_paths) ? json_encode($image_paths) : null;

    if ($pet_id) {
        // Update existing pet
        log_message("Updating existing pet ID: $pet_id");
        log_message("Status value: '$status' (lowercase: '" . strtolower($status) . "')");
        
        // Sanitize status
        $status = htmlspecialchars($status);
        
        // Determine what image to save
        $image_to_save = null;
        
        if (!empty($image_paths)) {
            // New images were uploaded - use all images as JSON
            $image_to_save = !empty($all_images_json) ? $all_images_json : $image_path;
            
            // Delete old images
            $old_stmt = $conn->prepare("SELECT image FROM pets WHERE id = ?");
            if ($old_stmt) {
                $old_stmt->bind_param("i", $pet_id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result()->fetch_assoc();
                if ($old_result && !empty($old_result['image'])) {
                    $root_dir = $_SERVER['DOCUMENT_ROOT'];
                    
                    // Try to parse as JSON array
                    $old_images = json_decode($old_result['image'], true);
                    if (!is_array($old_images)) {
                        // Single image path
                        $old_images = [$old_result['image']];
                    }
                    
                    // Delete each old image file
                    foreach ($old_images as $old_img) {
                        if (!empty($old_img)) {
                            $old_file = $root_dir . (strpos($old_img, '/') === 0 ? '' : '/') . $old_img;
                            if (file_exists($old_file) && is_file($old_file)) {
                                unlink($old_file);
                                log_message("Old image deleted: " . $old_img);
                            }
                        }
                    }
                }
                $old_stmt->close();
            }
            
            // Update with new images
            $stmt = $conn->prepare("UPDATE pets SET name=?, type=?, breed=?, age=?, gender=?, size=?, weight=?, description=?, personality=?, health_status=?, shelter_name=?, shelter_email=?, shelter_phone=?, shelter_address=?, status=?, image=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("sssdssssssssssssi", $name, $type, $breed, $age, $gender, $size, $weight, $description, $personality, $health_status, $shelter_name, $shelter_email, $shelter_phone, $shelter_address, $status, $image_to_save, $pet_id);
                if ($stmt->execute()) {
                    log_message("Pet updated successfully with new images");
                    $stmt->close();
                    header("Location: ../manage_pets.php?success=1");
                    exit;
                } else {
                    log_message("Update failed: " . $stmt->error);
                    $stmt->close();
                }
            }
        } else {
            // No new images uploaded - keep existing images
            $stmt = $conn->prepare("UPDATE pets SET name=?, type=?, breed=?, age=?, gender=?, size=?, weight=?, description=?, personality=?, health_status=?, shelter_name=?, shelter_email=?, shelter_phone=?, shelter_address=?, status=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("sssdsssssssssssi", $name, $type, $breed, $age, $gender, $size, $weight, $description, $personality, $health_status, $shelter_name, $shelter_email, $shelter_phone, $shelter_address, $status, $pet_id);
                if ($stmt->execute()) {
                    log_message("Pet updated successfully (no image changes)");
                    $stmt->close();
                    header("Location: ../manage_pets.php?success=1");
                    exit;
                } else {
                    log_message("Update failed: " . $stmt->error);
                    $stmt->close();
                }
            }
        }
        header("Location: ../manage_pets.php?error=database_failed");
        exit;
    } else {
        // Add new pet
        log_message("Creating new pet");
        // Use JSON array if multiple images, otherwise use first image
        $image_to_save = !empty($all_images_json) ? $all_images_json : $image_path;
        $stmt = $conn->prepare("INSERT INTO pets (name, type, breed, age, gender, size, weight, description, personality, health_status, shelter_name, shelter_email, shelter_phone, shelter_address, status, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("sssdssssssssssss", $name, $type, $breed, $age, $gender, $size, $weight, $description, $personality, $health_status, $shelter_name, $shelter_email, $shelter_phone, $shelter_address, $status, $image_to_save);
            
            if ($stmt->execute()) {
                log_message("New pet created successfully. ID: " . $conn->insert_id);
                $stmt->close();
                header("Location: ../manage_pets.php?success=1");
                exit;
            } else {
                log_message("SQL Insert Error: " . $stmt->error);
                $stmt->close();
                header("Location: ../manage_pets.php?error=database_failed");
                exit;
            }
        } else {
            log_message("SQL Prepare Error: " . $conn->error);
            header("Location: ../manage_pets.php?error=database_prepare_failed");
            exit;
        }
    }
}
?>

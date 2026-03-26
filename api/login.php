<?php
include_once '../config/config.php';
include_once '../config/database.php';
include_once '../includes/functions.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
        exit();
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, fullname, email, username, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_role'] = $user['role'];
                
                echo json_encode(['success' => true, 'message' => 'Login successful!']);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
?>
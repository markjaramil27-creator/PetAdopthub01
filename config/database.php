<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'petadopthub');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add meeting_link column to applications table if it doesn't exist
$check_column = $conn->query("SHOW COLUMNS FROM applications LIKE 'meeting_link'");
if ($check_column && $check_column->num_rows === 0) {
    $add_column = "ALTER TABLE applications ADD COLUMN meeting_link VARCHAR(500) DEFAULT NULL";
    if (!$conn->query($add_column)) {
        // Silently fail if column can't be added (might already exist or other issue)
        error_log("Warning: Could not add meeting_link column: " . $conn->error);
    }
}
?>
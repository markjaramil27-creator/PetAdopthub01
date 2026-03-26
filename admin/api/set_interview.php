<?php
// set_interview.php - Handles setting interview date/time/type for an application
include_once '../../config/config.php';
include_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = isset($_POST['app_id']) ? (int)$_POST['app_id'] : 0;
    $interview_type = isset($_POST['interview_type']) ? trim($_POST['interview_type']) : '';
    $interview_date = isset($_POST['interview_date']) ? trim($_POST['interview_date']) : '';
    $interview_time = isset($_POST['interview_time']) ? trim($_POST['interview_time']) : '';
    $meeting_link = isset($_POST['meeting_link']) ? trim($_POST['meeting_link']) : '';

    if ($app_id && $interview_type && $interview_date && $interview_time) {
        // Check if meeting_link column exists
        $check_column = $conn->query("SHOW COLUMNS FROM applications LIKE 'meeting_link'");
        $has_meeting_link_column = $check_column->num_rows > 0;

        // Save interview details to the applications table
        if ($has_meeting_link_column && $interview_type === 'Online' && !empty($meeting_link)) {
            // Include meeting link if Online interview and column exists
            $stmt = $conn->prepare("UPDATE applications SET interview_type=?, interview_date=?, interview_time=?, meeting_link=? WHERE id=?");
            $stmt->bind_param("ssssi", $interview_type, $interview_date, $interview_time, $meeting_link, $app_id);
        } else {
            // Standard update without meeting link
            $stmt = $conn->prepare("UPDATE applications SET interview_type=?, interview_date=?, interview_time=? WHERE id=?");
            $stmt->bind_param("sssi", $interview_type, $interview_date, $interview_time, $app_id);
        }

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: ../applications.php?interview_set=1");
            exit;
        } else {
            $stmt->close();
            header("Location: ../applications.php?interview_set=0");
            exit;
        }
    } else {
        header("Location: ../applications.php?interview_set=0");
        exit;
    }
} else {
    header("Location: ../applications.php");
    exit;
}

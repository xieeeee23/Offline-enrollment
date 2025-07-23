<?php
// This script captures form data from teachers.php and logs it for debugging
session_start();

// Create a log file
$log_file = fopen("teacher_form_debug.log", "a");

// Log the timestamp
fwrite($log_file, "=== Form submission at " . date('Y-m-d H:i:s') . " ===\n");

// Log POST data
fwrite($log_file, "POST data:\n");
foreach ($_POST as $key => $value) {
    fwrite($log_file, "$key: $value\n");
}
fwrite($log_file, "\n");

// Include config to check the user_id
include 'includes/config.php';

// Check if user_id exists
if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    fwrite($log_file, "Checking user_id: $user_id\n");
    
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        fwrite($log_file, "User found: ID={$user['id']}, Username={$user['username']}, Role={$user['role']}\n");
    } else {
        fwrite($log_file, "ERROR: No user found with ID $user_id\n");
    }
} else {
    fwrite($log_file, "No user_id provided in the form\n");
}

// Log available teacher users
$query = "SELECT id, username, role FROM users WHERE role = 'teacher'";
$result = mysqli_query($conn, $query);
fwrite($log_file, "\nAvailable teacher users:\n");
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        fwrite($log_file, "ID: {$row['id']}, Username: {$row['username']}, Role: {$row['role']}\n");
    }
} else {
    fwrite($log_file, "No users with 'teacher' role found\n");
}

// Close the log file
fwrite($log_file, "\n");
fclose($log_file);

// Redirect back to the form
header("Location: modules/teacher/teachers.php");
exit;
?> 
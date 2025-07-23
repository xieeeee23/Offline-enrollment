<?php
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Create a log file
$log_file = fopen($relative_path . "teacher_debug.log", "a");

// Log the timestamp
fwrite($log_file, "=== Form submission at " . date('Y-m-d H:i:s') . " ===\n");

// Log POST data
fwrite($log_file, "POST data:\n");
foreach ($_POST as $key => $value) {
    fwrite($log_file, "$key: $value\n");
}
fwrite($log_file, "\n");

// Get form data
$user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$first_name = cleanInput($_POST['first_name']);
$last_name = cleanInput($_POST['last_name']);
$email = cleanInput($_POST['email']);
$department = cleanInput($_POST['department']);
$subject = cleanInput($_POST['subject']);
$grade_level = cleanInput($_POST['grade_level']);
$contact_number = cleanInput($_POST['contact_number']);
$qualification = cleanInput($_POST['qualification']);
$status = cleanInput($_POST['status']);

// Log the cleaned data
fwrite($log_file, "Cleaned data:\n");
fwrite($log_file, "user_id: " . ($user_id === null ? "NULL" : $user_id) . "\n");
fwrite($log_file, "first_name: $first_name\n");
fwrite($log_file, "last_name: $last_name\n");
fwrite($log_file, "email: $email\n");
fwrite($log_file, "department: $department\n");
fwrite($log_file, "subject: $subject\n");
fwrite($log_file, "grade_level: $grade_level\n");
fwrite($log_file, "contact_number: $contact_number\n");
fwrite($log_file, "qualification: $qualification\n");
fwrite($log_file, "status: $status\n\n");

// Check if user_id exists in the users table
if ($user_id !== null) {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        fwrite($log_file, "User found: ID={$user['id']}, Username={$user['username']}, Role={$user['role']}\n\n");
    } else {
        fwrite($log_file, "ERROR: No user found with ID $user_id\n\n");
    }
}

// Try to insert the teacher record
fwrite($log_file, "Attempting to insert teacher record...\n");

try {
    // Check if contact_number column exists
    $has_contact_column = true;
    $check_contact_column = "SHOW COLUMNS FROM teachers LIKE 'contact_number'";
    $contact_result = mysqli_query($conn, $check_contact_column);
    if (mysqli_num_rows($contact_result) == 0) {
        $has_contact_column = false;
        fwrite($log_file, "Contact number column doesn't exist, adding it...\n");
        $alter_query = "ALTER TABLE teachers ADD COLUMN contact_number VARCHAR(20) DEFAULT NULL";
        mysqli_query($conn, $alter_query);
    }
    
    // Insert the teacher record
    if ($has_contact_column) {
        $query = "INSERT INTO teachers (user_id, first_name, last_name, email, department, subject, grade_level, contact_number, qualification, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($user_id === null) {
            // Handle NULL user_id
            fwrite($log_file, "Binding NULL for user_id\n");
            mysqli_stmt_bind_param($stmt, "isssssssss", $null_value, $first_name, $last_name, $email, $department, $subject, $grade_level, $contact_number, $qualification, $status);
        } else {
            fwrite($log_file, "Binding user_id: $user_id\n");
            mysqli_stmt_bind_param($stmt, "isssssssss", $user_id, $first_name, $last_name, $email, $department, $subject, $grade_level, $contact_number, $qualification, $status);
        }
    } else {
        $query = "INSERT INTO teachers (user_id, first_name, last_name, email, department, subject, grade_level, qualification, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($user_id === null) {
            // Handle NULL user_id
            fwrite($log_file, "Binding NULL for user_id\n");
            mysqli_stmt_bind_param($stmt, "issssssss", $null_value, $first_name, $last_name, $email, $department, $subject, $grade_level, $qualification, $status);
        } else {
            fwrite($log_file, "Binding user_id: $user_id\n");
            mysqli_stmt_bind_param($stmt, "issssssss", $user_id, $first_name, $last_name, $email, $department, $subject, $grade_level, $qualification, $status);
        }
    }
    
    fwrite($log_file, "Executing query: " . $query . "\n");
    
    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        $teacher_id = mysqli_insert_id($conn);
        fwrite($log_file, "SUCCESS: Teacher added successfully with ID: $teacher_id\n");
        
        echo '<div class="container mt-4">';
        echo '<div class="alert alert-success">';
        echo '<h4>Success!</h4>';
        echo '<p>Teacher added successfully with ID: ' . $teacher_id . '</p>';
        echo '<p>Check the log file for details.</p>';
        echo '<a href="debug_form.php" class="btn btn-primary">Back to Debug Form</a>';
        echo '</div>';
        echo '</div>';
    } else {
        $error = mysqli_error($conn);
        fwrite($log_file, "ERROR: " . $error . "\n");
        
        echo '<div class="container mt-4">';
        echo '<div class="alert alert-danger">';
        echo '<h4>Error!</h4>';
        echo '<p>Failed to add teacher: ' . $error . '</p>';
        echo '<p>Check the log file for details.</p>';
        echo '<a href="debug_form.php" class="btn btn-primary">Back to Debug Form</a>';
        echo '</div>';
        echo '</div>';
    }
} catch (Exception $e) {
    fwrite($log_file, "EXCEPTION: " . $e->getMessage() . "\n");
    
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">';
    echo '<h4>Exception!</h4>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '<p>Check the log file for details.</p>';
    echo '<a href="debug_form.php" class="btn btn-primary">Back to Debug Form</a>';
    echo '</div>';
    echo '</div>';
}

// Close the log file
fwrite($log_file, "\n");
fclose($log_file);

require_once $relative_path . 'includes/footer.php';
?> 
<?php
include 'includes/config.php';

// Create a second teacher user
$username = "teacher2";
$password = password_hash("teacher123", PASSWORD_DEFAULT); // Default password: teacher123
$role = "teacher";
$name = "Second Teacher";
$email = "teacher2@example.com";
$status = "active";

// Check if username already exists
$check_query = "SELECT id FROM users WHERE username = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "s", $username);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    echo "A user with username '$username' already exists.\n";
} else {
    // Insert new user
    $insert_query = "INSERT INTO users (username, password, role, name, email, status) VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, "ssssss", $username, $password, $role, $name, $email, $status);
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $user_id = mysqli_insert_id($conn);
        echo "Teacher user created successfully with ID: $user_id\n";
    } else {
        echo "Error creating teacher user: " . mysqli_error($conn) . "\n";
    }
}

// List all users
echo "\nAll users in the database:\n";
$result = mysqli_query($conn, "SELECT id, username, role, name, email FROM users");
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . ", Username: " . $row['username'] . ", Role: " . $row['role'] . ", Name: " . $row['name'] . "\n";
    }
} else {
    echo "No users found.\n";
}

// Now try to add a teacher record with the new user ID
if (isset($user_id)) {
    echo "\nTrying to add a teacher record with user ID $user_id...\n";
    
    $first_name = "Second";
    $last_name = "Teacher";
    $email = "teacher2@example.com";
    $department = "Senior High School";
    $subject = "English";
    $grade_level = "12";
    $contact_number = "9876543210";
    $qualification = "Bachelor of Education";
    $status = "active";
    
    $query = "INSERT INTO teachers (user_id, first_name, last_name, email, department, subject, grade_level, contact_number, qualification, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isssssssss", $user_id, $first_name, $last_name, $email, $department, $subject, $grade_level, $contact_number, $qualification, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        $teacher_id = mysqli_insert_id($conn);
        echo "Teacher added successfully with ID: $teacher_id\n";
    } else {
        echo "Error adding teacher: " . mysqli_error($conn) . "\n";
    }
}
?> 
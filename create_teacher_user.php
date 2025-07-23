<?php
include 'includes/config.php';

// Create a teacher user
$username = "teacher1";
$password = password_hash("teacher123", PASSWORD_DEFAULT); // Default password: teacher123
$role = "teacher";
$name = "Teacher User";
$email = "teacher@example.com";
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
?> 
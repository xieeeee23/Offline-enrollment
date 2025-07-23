<?php
require_once 'includes/config.php';

// Display header
echo "<h1>Updating Users Table - Removing Student and Parent Roles</h1>";

// Check if users table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($table_check) == 0) {
    echo "<p>Error: Users table does not exist.</p>";
    exit;
}

// First, check if there are any users with student or parent roles
$check_query = "SELECT id, username, role FROM users WHERE role IN ('student', 'parent')";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    echo "<h2>The following users have student or parent roles and will be affected:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Current Role</th></tr>";
    
    while ($row = mysqli_fetch_assoc($check_result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Update these users to have the 'teacher' role
    $update_query = "UPDATE users SET role = 'teacher' WHERE role IN ('student', 'parent')";
    if (mysqli_query($conn, $update_query)) {
        $affected = mysqli_affected_rows($conn);
        echo "<p>Updated $affected users from student/parent role to teacher role.</p>";
    } else {
        echo "<p>Error updating user roles: " . mysqli_error($conn) . "</p>";
    }
}

// Modify the users table to remove student and parent from the enum
$alter_query = "ALTER TABLE users MODIFY COLUMN role ENUM('admin','registrar','teacher') NOT NULL";
if (mysqli_query($conn, $alter_query)) {
    echo "<p>Successfully updated users table role column to remove student and parent roles.</p>";
} else {
    echo "<p>Error updating users table: " . mysqli_error($conn) . "</p>";
}

// Log the change
$log_query = "INSERT INTO logs (user_id, action, description) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $log_query);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to admin if not logged in
$action = "UPDATE";
$description = "Modified users table to remove student and parent roles";
mysqli_stmt_bind_param($stmt, "iss", $user_id, $action, $description);
mysqli_stmt_execute($stmt);

echo "<p>Changes have been logged.</p>";
echo "<p><a href='dashboard.php'>Return to Dashboard</a></p>";
?> 
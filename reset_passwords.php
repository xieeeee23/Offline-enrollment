<?php
// Password reset script for LocalEnroll Pro

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access. Only administrators can run this script.");
}

// Set default password
$default_password = 'admin123';
$hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

// Update all users with the new hashed password
$query = "UPDATE users SET password = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $hashed_password);

if (mysqli_stmt_execute($stmt)) {
    echo "<h2>Password Reset Successful</h2>";
    echo "<p>All user passwords have been reset to: <strong>admin123</strong></p>";
    echo "<p>The following users were updated:</p>";
    
    // Get all users
    $users_query = "SELECT id, username, name, role FROM users";
    $users_result = mysqli_query($conn, $users_query);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th></tr>";
    
    while ($user = mysqli_fetch_assoc($users_result)) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "</tr>";
        
        // Log the action
        logAction($_SESSION['user_id'], 'UPDATE', "Reset password for user: " . $user['username']);
    }
    
    echo "</table>";
    echo "<p><a href='dashboard.php'>Return to Dashboard</a></p>";
} else {
    echo "<h2>Password Reset Failed</h2>";
    echo "<p>Error: " . mysqli_error($conn) . "</p>";
}
?> 
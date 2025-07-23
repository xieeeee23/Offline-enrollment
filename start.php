<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include required files directly
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set session variables for admin user
$_SESSION['user_id'] = 1; // Assuming admin has ID 1
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['name'] = 'Administrator';

// Log the login action
try {
    logAction(1, 'LOGIN', 'Direct login for troubleshooting');
    echo "<p>Login successful! Redirecting to dashboard...</p>";
    echo "<script>setTimeout(function() { window.location.href = 'dashboard.php'; }, 2000);</script>";
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // Display database connection info
    echo "<h2>Database Connection</h2>";
    echo "<p>Host: " . DB_HOST . "</p>";
    echo "<p>User: " . DB_USER . "</p>";
    echo "<p>Database: " . DB_NAME . "</p>";
    
    if (isset($conn)) {
        echo "<p>Connection: " . ($conn ? "Success" : "Failed") . "</p>";
        if (!$conn) {
            echo "<p>Error: " . mysqli_connect_error() . "</p>";
        }
    } else {
        echo "<p>Connection variable not set</p>";
    }
    
    // Check if users table exists
    if (isset($conn) && $conn) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        echo "<p>Users table: " . (mysqli_num_rows($result) > 0 ? "Exists" : "Does not exist") . "</p>";
    }
    
    // Show links to other test files
    echo "<h2>Links</h2>";
    echo "<p><a href='simple_test.php'>Run System Test</a></p>";
    echo "<p><a href='phpinfo.php'>PHP Info</a></p>";
    echo "<p><a href='test_db.php'>Test Database</a></p>";
}
?> 
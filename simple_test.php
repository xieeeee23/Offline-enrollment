<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

echo "<h1>System Test</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Check PHP version
echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check session
echo "<h2>Session Information</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Username: " . $_SESSION['username'] . "</p>";
    echo "<p>Role: " . $_SESSION['role'] . "</p>";
} else {
    echo "<p>No active session</p>";
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once 'includes/config.php';
    
    if (isset($conn) && $conn) {
        echo "<p>Database connection successful!</p>";
        
        // Check if users table exists
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        if (mysqli_num_rows($result) > 0) {
            echo "<p>Users table exists!</p>";
            
            // Check if there are any users
            $result = mysqli_query($conn, "SELECT * FROM users");
            echo "<p>Number of users: " . mysqli_num_rows($result) . "</p>";
            
            // Display first user
            if ($row = mysqli_fetch_assoc($result)) {
                echo "<p>First user: " . $row['username'] . " (Role: " . $row['role'] . ")</p>";
            }
        } else {
            echo "<p>Users table does not exist!</p>";
        }
    } else {
        echo "<p>Database connection failed!</p>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Check file paths
echo "<h2>File Paths</h2>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>PHP_SELF: " . $_SERVER['PHP_SELF'] . "</p>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";

// Check if important files exist
echo "<h2>File Existence</h2>";
$files_to_check = [
    'includes/config.php',
    'includes/functions.php',
    'includes/header.php',
    'dashboard.php',
    'login.php'
];

foreach ($files_to_check as $file) {
    echo "<p>" . $file . ": " . (file_exists($file) ? "Exists" : "Does not exist") . "</p>";
}

// Check BASE_URL
echo "<h2>Configuration</h2>";
if (defined('BASE_URL')) {
    echo "<p>BASE_URL: " . BASE_URL . "</p>";
} else {
    echo "<p>BASE_URL is not defined!</p>";
}

// Provide links to important pages
echo "<h2>Links</h2>";
echo "<p><a href='login.php'>Login Page</a></p>";
echo "<p><a href='direct_login.php'>Direct Login (Bypass)</a></p>";
echo "<p><a href='dashboard.php'>Dashboard</a></p>";
echo "<p><a href='phpinfo.php'>PHP Info</a></p>";
?> 
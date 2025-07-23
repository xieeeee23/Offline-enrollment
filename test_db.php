<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shs_enrollment');

// Establish database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    echo "Database connection successful!<br>";
    
    // Check if users table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if (mysqli_num_rows($result) > 0) {
        echo "Users table exists!<br>";
        
        // Check if there are any users
        $result = mysqli_query($conn, "SELECT * FROM users");
        echo "Number of users: " . mysqli_num_rows($result) . "<br>";
        
        // Display first user
        if ($row = mysqli_fetch_assoc($result)) {
            echo "First user: " . $row['username'] . " (Role: " . $row['role'] . ")<br>";
        }
    } else {
        echo "Users table does not exist!<br>";
    }
}
?> 
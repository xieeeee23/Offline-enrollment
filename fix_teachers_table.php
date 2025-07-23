<?php
// Set path to config and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Function to create teachers table
function create_teachers_table($conn) {
    // Create teachers table
    $query = "CREATE TABLE IF NOT EXISTS teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        department VARCHAR(100) DEFAULT NULL,
        subject VARCHAR(100) DEFAULT NULL,
        grade_level VARCHAR(50) DEFAULT NULL,
        qualification TEXT DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $query)) {
        echo "<p>Teachers table created successfully!</p>";
    } else {
        echo "<p>Error creating teachers table: " . mysqli_error($conn) . "</p>";
    }
}

// Check if the teachers table exists
$check_query = "SHOW TABLES LIKE 'teachers'";
$check_result = mysqli_query($conn, $check_query);

echo "<html><head><title>Fix Teachers Table</title></head><body>";
echo "<h2>Teachers Table Fix</h2>";

if (mysqli_num_rows($check_result) == 0) {
    echo "<p>Teachers table does not exist. Creating it now...</p>";
    create_teachers_table($conn);
} else {
    echo "<p>Teachers table already exists.</p>";
}

echo "<p><a href='modules/teacher/teachers.php'>Go to Teachers Page</a></p>";
echo "</body></html>";
?> 
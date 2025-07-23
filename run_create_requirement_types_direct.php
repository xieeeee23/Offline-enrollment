<?php
// Database connection details
$db_host = 'localhost';
$db_user = 'root'; // Default XAMPP MySQL user
$db_pass = ''; // Default XAMPP MySQL password is empty
$db_name = 'shs_enrollment'; // Your database name

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected to database successfully.<br>";

// SQL to create requirement_types table
$sql = "CREATE TABLE IF NOT EXISTS `requirement_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `is_required` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Execute query
if (mysqli_query($conn, $sql)) {
    echo "Requirement types table created successfully.<br>";
    
    // Check if requirement types already exist
    $check_sql = "SELECT COUNT(*) as count FROM `requirement_types`";
    $result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        echo "Requirement types already exist in the table. No new types added.<br>";
    } else {
        // Insert default requirement types
        $default_types = [
            ['Document', 'Official documents required for enrollment', 1],
            ['Payment', 'Payment receipts and financial requirements', 1],
            ['Form', 'Forms that need to be filled out', 1],
            ['Other', 'Other miscellaneous requirements', 1]
        ];
        
        $success_count = 0;
        foreach ($default_types as $type) {
            $name = $type[0];
            $description = $type[1];
            $is_required = $type[2];
            
            $insert_sql = "INSERT INTO `requirement_types` (`name`, `description`, `is_required`) 
                          VALUES ('$name', '$description', $is_required)";
            
            if (mysqli_query($conn, $insert_sql)) {
                $success_count++;
            } else {
                echo "Error inserting type: " . mysqli_error($conn) . "<br>";
            }
        }
        
        echo "$success_count default requirement types added successfully.<br>";
    }
} else {
    echo "Error creating requirement_types table: " . mysqli_error($conn) . "<br>";
}

// Close connection
mysqli_close($conn);
echo "Done!";
?> 
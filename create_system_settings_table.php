<?php
/**
 * Create System Settings Table
 * 
 * This script creates the system_settings table if it doesn't exist.
 * The table is used to store various system settings, including backup settings.
 */

$relative_path = __DIR__ . '/';
require_once $relative_path . 'includes/config.php';

// Connect to database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if table exists
$table_exists = false;
$result = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
if ($result && mysqli_num_rows($result) > 0) {
    $table_exists = true;
}

// Create table if it doesn't exist
if (!$table_exists) {
    $sql = "CREATE TABLE `system_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_key` varchar(255) NOT NULL,
        `setting_value` text NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (mysqli_query($conn, $sql)) {
        echo "Table 'system_settings' created successfully.<br>";
        
        // Insert default backup settings
        $default_settings = json_encode([
            'enabled' => false,
            'frequency' => 'daily',
            'retention' => 30,
            'last_backup' => null
        ]);
        
        $insert_sql = "INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES ('backup_settings', ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "s", $default_settings);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Default backup settings inserted successfully.<br>";
        } else {
            echo "Error inserting default backup settings: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Table 'system_settings' already exists.<br>";
    
    // Check if backup_settings exist
    $result = mysqli_query($conn, "SELECT * FROM `system_settings` WHERE `setting_key` = 'backup_settings'");
    if ($result && mysqli_num_rows($result) == 0) {
        // Insert default backup settings
        $default_settings = json_encode([
            'enabled' => false,
            'frequency' => 'daily',
            'retention' => 30,
            'last_backup' => null
        ]);
        
        $insert_sql = "INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES ('backup_settings', ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "s", $default_settings);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Default backup settings inserted successfully.<br>";
        } else {
            echo "Error inserting default backup settings: " . mysqli_error($conn) . "<br>";
        }
    }
}

// Close connection
mysqli_close($conn);

echo "System settings table setup completed.<br>";
echo "<a href='dashboard.php'>Return to Dashboard</a>";
?> 
<?php
/**
 * Cron Job Script for Automatically Generating School Years
 * 
 * This script should be set up to run once a year via cron job or scheduled task.
 * It will automatically generate school years and set the current one.
 * 
 * Example cron job (run on January 1st at midnight):
 * 0 0 1 1 * php /path/to/cron_auto_generate_school_years.php
 */

// Include database configuration
require_once 'includes/config.php';

// Number of future years to ensure are available
$future_years = 5;
$generated_count = 0;

// Get current year
$current_year = (int)date('Y');

// Check if school_years table exists
$query = "SHOW TABLES LIKE 'school_years'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // Create school_years table if it doesn't exist
    $create_table_query = "CREATE TABLE school_years (
        id INT AUTO_INCREMENT PRIMARY KEY,
        year_start INT NOT NULL,
        year_end INT NOT NULL,
        school_year VARCHAR(20) NOT NULL UNIQUE,
        is_current TINYINT(1) DEFAULT 0,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    mysqli_query($conn, $create_table_query);
    echo "Created school_years table.\n";
}

// Get existing school years
$existing_years = [];
$query = "SELECT school_year FROM school_years";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $existing_years[] = $row['school_year'];
    }
}

// Generate school years
for ($i = 0; $i <= $future_years; $i++) {
    $year_start = $current_year + $i;
    $year_end = $year_start + 1;
    $school_year = $year_start . '-' . $year_end;
    
    // Check if this school year already exists
    if (!in_array($school_year, $existing_years)) {
        $query = "INSERT INTO school_years (year_start, year_end, school_year, is_current, status) 
                  VALUES (?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        $is_current = ($year_start == $current_year) ? 1 : 0;
        $status = 'Active';
        
        mysqli_stmt_bind_param($stmt, "iisss", $year_start, $year_end, $school_year, $is_current, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            $generated_count++;
            echo "Generated school year: {$school_year}\n";
            
            // Log action to system log file
            $log_message = date('Y-m-d H:i:s') . " - Auto-generated school year: {$school_year}\n";
            file_put_contents('logs/system_log.txt', $log_message, FILE_APPEND);
        } else {
            echo "Error generating school year {$school_year}: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "School year {$school_year} already exists.\n";
    }
}

// Set the current year
$current_school_year = $current_year . '-' . ($current_year + 1);
$query = "UPDATE school_years SET is_current = 0";
mysqli_query($conn, $query);

$query = "UPDATE school_years SET is_current = 1 WHERE school_year = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $current_school_year);
mysqli_stmt_execute($stmt);
echo "Set {$current_school_year} as the current school year.\n";

echo "Completed. Generated {$generated_count} new school years.\n";
?> 
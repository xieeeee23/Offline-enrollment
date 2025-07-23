<?php
/**
 * Auto Check School Years
 * 
 * This script automatically checks and generates school years when the system starts up.
 * It ensures that there are always enough future school years available in the system.
 */

// Don't run this script during AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    return;
}

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
}

// Get current year
$current_year = (int)date('Y');

// Get existing school years
$existing_years = [];
$query = "SELECT school_year FROM school_years";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $existing_years[] = $row['school_year'];
    }
}

// Number of future years to ensure are available
$future_years = 3;
$years_generated = 0;

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
            $years_generated++;
            
            // Log action if a user is logged in
            if (isset($_SESSION['user_id'])) {
                $log_desc = "System auto-generated school year: {$school_year}";
                logAction($_SESSION['user_id'], 'CREATE', $log_desc);
            }
        }
    }
}

// Ensure the current year is marked as current
$current_school_year = $current_year . '-' . ($current_year + 1);
$query = "UPDATE school_years SET is_current = 0";
mysqli_query($conn, $query);

$query = "UPDATE school_years SET is_current = 1 WHERE school_year = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $current_school_year);
mysqli_stmt_execute($stmt);

// If years were generated and a user is logged in, show a notification
if ($years_generated > 0 && isset($_SESSION['user_id'])) {
    $_SESSION['auto_school_year_notice'] = "The system automatically generated {$years_generated} new school years.";
}
?> 
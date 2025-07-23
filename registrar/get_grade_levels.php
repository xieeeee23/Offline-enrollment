<?php
// Calculate the relative path to the includes directory
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to log debug information
function debug_log($message, $data = null) {
    $log_file = __DIR__ . '/grade_levels_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $log_message .= ': ' . json_encode($data, JSON_PRETTY_PRINT);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    debug_log('User not logged in');
    echo json_encode([]);
    exit;
}

// Check if education_level_id is provided
if (!isset($_GET['education_level_id'])) {
    debug_log('No education_level_id parameter provided');
    echo json_encode([]);
    exit;
}

try {
    $education_level_id = mysqli_real_escape_string($conn, $_GET['education_level_id']);
    debug_log('Requested education_level_id', $education_level_id);

    // Get education level name
    $query = "SELECT name FROM education_levels WHERE id = ? AND status = 'Active'";
    debug_log('Query', $query);
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $education_level_id);
    debug_log('Executing query with education_level_id', $education_level_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Database execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $education_level_name = $row['name'];
        debug_log('Found education level name', $education_level_name);
        
        // Define grade levels based on education level
        $grade_levels = [];
        
        if ($education_level_name == 'Kindergarten') {
            debug_log('Processing Kindergarten level');
            $grade_levels = ['Kindergarten'];
        } elseif ($education_level_name == 'Elementary') {
            debug_log('Processing Elementary level');
            $grade_levels = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        } elseif ($education_level_name == 'Junior High School') {
            debug_log('Processing Junior High School level');
            $grade_levels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
        } elseif ($education_level_name == 'Senior High School') {
            debug_log('Processing Senior High School level');
            $grade_levels = ['Grade 11', 'Grade 12'];
        } else {
            debug_log('Unknown education level, returning all grade levels');
            // If education level is not recognized, return all grade levels
            $grade_levels = [
                'Kindergarten',
                'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10',
                'Grade 11', 'Grade 12'
            ];
        }
        
        debug_log('Returning grade levels', $grade_levels);
        echo json_encode($grade_levels);
    } else {
        debug_log('No education level found with id', $education_level_id);
        echo json_encode([]);
    }
} catch (Exception $e) {
    // Log the error
    $error_message = "Error in get_grade_levels.php: " . $e->getMessage();
    error_log($error_message);
    debug_log('Error', $error_message);
    
    // Return empty array
    echo json_encode([]);
}
?> 
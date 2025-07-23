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
    $log_file = __DIR__ . '/sections_debug.log';
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

// Check parameters
if (!isset($_GET['grade_level'])) {
    debug_log('No grade_level parameter provided');
    echo json_encode([]);
    exit;
}

try {
    $grade_level = mysqli_real_escape_string($conn, $_GET['grade_level']);
    debug_log('Requested grade level', $grade_level);

    // Normalize grade level format (handle both "11" and "Grade 11" formats)
    if ($grade_level === '11' || $grade_level === '12') {
        $grade_level = 'Grade ' . $grade_level;
    }
    
    // Query the sections table directly - get all fields to match manage_sections.php
    $query = "SELECT s.*, ss.strand_name
              FROM sections s 
              LEFT JOIN shs_strands ss ON s.strand = ss.strand_code 
              WHERE s.grade_level = ? AND s.status = 'Active' 
              ORDER BY s.name";
    
    $sections = [];
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $grade_level);
        mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
        while ($row = mysqli_fetch_assoc($result)) {
            // Use the exact section name from the database
            $section_name = $row['name'];
            
            // Create a formatted display name for UI only
            $display_name = $section_name;
            
            $sections[] = [
                'id' => $row['id'],
                'name' => $section_name,
                'display' => $display_name,
                'strand' => $row['strand'],
                'strand_name' => $row['strand_name'],
                'school_year' => $row['school_year'],
                'semester' => $row['semester'],
                'max_students' => $row['max_students'],
                'status' => $row['status']
            ];
        }
    }
    
    debug_log('Sections found in database', $sections);
    
    // If no sections were found, provide some default sections based on grade level
    if (empty($sections)) {
        debug_log('No sections found, creating defaults');
        
        // Default sections based on grade level with exact names as in manage_sections.php
        if ($grade_level === 'Grade 11') {
            $sections = [
                ['id' => 'default-11A', 'name' => 'ABM-11A', 'display' => 'ABM-11A', 'strand' => 'ABM', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active'],
                ['id' => 'default-11B', 'name' => 'STEM-11A', 'display' => 'STEM-11A', 'strand' => 'STEM', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active'],
                ['id' => 'default-11C', 'name' => 'HUMSS-11A', 'display' => 'HUMSS-11A', 'strand' => 'HUMSS', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active']
            ];
        } else if ($grade_level === 'Grade 12') {
            $sections = [
                ['id' => 'default-12A', 'name' => 'ABM-12A', 'display' => 'ABM-12A', 'strand' => 'ABM', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active'],
                ['id' => 'default-12B', 'name' => 'STEM-12A', 'display' => 'STEM-12A', 'strand' => 'STEM', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active'],
                ['id' => 'default-12C', 'name' => 'HUMSS-12A', 'display' => 'HUMSS-12A', 'strand' => 'HUMSS', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active']
            ];
        } else {
            $sections = [
                ['id' => 'default-A', 'name' => 'Section A', 'display' => 'Section A', 'strand' => 'GEN', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active']
            ];
        }
        debug_log('Using default sections', $sections);
    }
    
    debug_log('Returning sections', $sections);
    echo json_encode($sections);
} catch (Exception $e) {
    // Log the error
    $error_message = "Error in get_sections.php: " . $e->getMessage();
    error_log($error_message);
    debug_log('Error', $error_message);
    
    // Return default sections rather than exposing error details
    $default_sections = [
        ['id' => 'default-A', 'name' => 'ABM-11A', 'display' => 'ABM-11A', 'strand' => 'ABM', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active'],
        ['id' => 'default-B', 'name' => 'STEM-11A', 'display' => 'STEM-11A', 'strand' => 'STEM', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active'],
        ['id' => 'default-C', 'name' => 'HUMSS-11A', 'display' => 'HUMSS-11A', 'strand' => 'HUMSS', 'school_year' => '2023-2024', 'semester' => 'First', 'max_students' => 40, 'status' => 'Active']
    ];
    debug_log('Returning default sections due to error', $default_sections);
    echo json_encode($default_sections);
}
?> 
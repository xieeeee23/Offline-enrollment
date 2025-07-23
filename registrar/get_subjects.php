<?php
// Calculate the relative path to the includes directory
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to log debug information
function debug_log($message, $data = null) {
    $log_file = __DIR__ . '/subjects_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    
    if ($data !== null) {
        $log_message .= ': ' . json_encode($data, JSON_PRETTY_PRINT);
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

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

    // Use the new getSubjectsByGradeLevel function
    $subjects = getSubjectsByGradeLevel($grade_level, $conn);
    debug_log('Subjects found using function', $subjects);
    
    // If no subjects were found, provide some default subjects based on grade level
    if (empty($subjects)) {
        debug_log('No subjects found in database, creating default subjects');
        
        // Normalize grade level format (handle both "11" and "Grade 11" formats)
        if ($grade_level === '11' || $grade_level === '12') {
            $grade_level = 'Grade ' . $grade_level;
        }
        
        // Default subjects based on grade level
        if ($grade_level === 'Grade 11') {
            // Insert default subjects into the database
            $default_subjects = [
                ['code' => 'OC', 'name' => 'Oral Communication', 'grade' => 'Grade 11'],
                ['code' => 'RW', 'name' => 'Reading and Writing', 'grade' => 'Grade 11'],
                ['code' => 'ES', 'name' => 'Earth Science', 'grade' => 'Grade 11'],
                ['code' => 'GM', 'name' => 'General Mathematics', 'grade' => 'Grade 11'],
                ['code' => 'SP', 'name' => 'Statistics and Probability', 'grade' => 'Grade 11'],
                ['code' => 'PD', 'name' => 'Personal Development', 'grade' => 'Grade 11'],
                ['code' => 'PE1', 'name' => 'Physical Education and Health 1', 'grade' => 'Grade 11'],
                ['code' => 'IP', 'name' => 'Introduction to Philosophy', 'grade' => 'Grade 11'],
                ['code' => 'UCSP', 'name' => 'Understanding Culture, Society and Politics', 'grade' => 'Grade 11'],
                ['code' => 'FPL', 'name' => 'Filipino sa Piling Larang', 'grade' => 'Grade 11']
            ];
            
            foreach ($default_subjects as $subject) {
                $insert_query = "INSERT INTO subjects (code, name, grade_level, education_level, status) 
                                VALUES (?, ?, ?, 'Senior High School', 'active')";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "sss", $subject['code'], $subject['name'], $subject['grade']);
                mysqli_stmt_execute($stmt);
                
                $new_id = mysqli_insert_id($conn);
                if ($new_id) {
                    $subjects[] = [
                        'id' => $new_id,
                        'code' => $subject['code'],
                        'name' => $subject['name'],
                        'display' => $subject['code'] . ' - ' . $subject['name']
                    ];
                }
            }
        } else if ($grade_level === 'Grade 12') {
            // Insert default subjects into the database
            $default_subjects = [
                ['code' => 'MIL', 'name' => 'Media and Information Literacy', 'grade' => 'Grade 12'],
                ['code' => '21CL', 'name' => '21st Century Literature', 'grade' => 'Grade 12'],
                ['code' => 'PS', 'name' => 'Physical Science', 'grade' => 'Grade 12'],
                ['code' => 'CPA', 'name' => 'Contemporary Philippine Arts', 'grade' => 'Grade 12'],
                ['code' => 'PE2', 'name' => 'Physical Education and Health 2', 'grade' => 'Grade 12'],
                ['code' => 'DRRR', 'name' => 'Disaster Readiness and Risk Reduction', 'grade' => 'Grade 12'],
                ['code' => 'ENTREP', 'name' => 'Entrepreneurship', 'grade' => 'Grade 12'],
                ['code' => 'RP', 'name' => 'Research Project', 'grade' => 'Grade 12'],
                ['code' => 'FPL2', 'name' => 'Filipino sa Piling Larang 2', 'grade' => 'Grade 12']
            ];
            
            foreach ($default_subjects as $subject) {
                $insert_query = "INSERT INTO subjects (code, name, grade_level, education_level, status) 
                                VALUES (?, ?, ?, 'Senior High School', 'active')";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "sss", $subject['code'], $subject['name'], $subject['grade']);
                mysqli_stmt_execute($stmt);
                
                $new_id = mysqli_insert_id($conn);
                if ($new_id) {
                    $subjects[] = [
                        'id' => $new_id,
                        'code' => $subject['code'],
                        'name' => $subject['name'],
                        'display' => $subject['code'] . ' - ' . $subject['name']
            ];
                }
            }
        } else {
            // If we don't recognize the grade level, provide a default subject
            $subjects = [
                ['id' => 'default-1', 'code' => 'SUB', 'name' => 'Default Subject', 'display' => 'SUB - Default Subject']
            ];
        }
        debug_log('Using default subjects', $subjects);
    }
    
    debug_log('Returning subjects', $subjects);
    echo json_encode($subjects);
} catch (Exception $e) {
    // Log the error
    $error_message = "Error in get_subjects.php: " . $e->getMessage();
    error_log($error_message);
    debug_log('Error', $error_message);
    
    // Return default subjects rather than exposing error details
    $default_subjects = [
        ['id' => 'default-1', 'code' => 'SUB1', 'name' => 'Subject 1', 'display' => 'SUB1 - Subject 1'],
        ['id' => 'default-2', 'code' => 'SUB2', 'name' => 'Subject 2', 'display' => 'SUB2 - Subject 2'],
        ['id' => 'default-3', 'code' => 'SUB3', 'name' => 'Subject 3', 'display' => 'SUB3 - Subject 3']
    ];
    debug_log('Returning default subjects due to error', $default_subjects);
    echo json_encode($default_subjects);
}
?> 
<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "localenroll_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful\n\n";

// Get all students with their SHS details
$query = "SELECT s.id, s.lrn, s.first_name, s.last_name, s.grade_level, s.section, 
          shsd.track, shsd.strand, shsd.semester, shsd.school_year 
          FROM students s 
          LEFT JOIN senior_highschool_details shsd ON s.id = shsd.student_id 
          ORDER BY s.id";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "=== STUDENT DATA VERIFICATION ===\n";
    echo "--------------------------------\n\n";
    
    echo "Total students: " . $result->num_rows . "\n\n";
    
    // Check for missing SHS details
    $missing_shs = 0;
    
    // Check for incorrect grade levels
    $incorrect_grade = 0;
    
    // Check for sections not matching available sections
    $incorrect_section = 0;
    
    // Get available sections
    $sections_query = "SELECT name FROM sections";
    $sections_result = $conn->query($sections_query);
    $valid_sections = [];
    
    if ($sections_result->num_rows > 0) {
        while($section = $sections_result->fetch_assoc()) {
            $valid_sections[] = $section['name'];
        }
    }
    
    // Display student data
    echo "STUDENT DETAILS:\n";
    echo "---------------\n";
    
    while($student = $result->fetch_assoc()) {
        echo "ID: " . $student['id'] . " | Name: " . $student['first_name'] . " " . $student['last_name'] . " | LRN: " . $student['lrn'] . "\n";
        echo "  Grade Level: " . $student['grade_level'] . " | Section: " . $student['section'] . "\n";
        
        if (empty($student['track']) || empty($student['strand'])) {
            echo "  SHS Details: MISSING\n";
            $missing_shs++;
        } else {
            echo "  Track: " . $student['track'] . " | Strand: " . $student['strand'] . " | Semester: " . $student['semester'] . " | School Year: " . $student['school_year'] . "\n";
        }
        
        // Check grade level format
        if ($student['grade_level'] != 'Grade 11' && $student['grade_level'] != 'Grade 12') {
            echo "  WARNING: Incorrect grade level format. Should be 'Grade 11' or 'Grade 12'.\n";
            $incorrect_grade++;
        }
        
        // Check if section exists in valid sections
        if (!in_array($student['section'], $valid_sections)) {
            echo "  WARNING: Section '" . $student['section'] . "' does not match any valid section in the sections table.\n";
            $incorrect_section++;
        }
        
        echo "\n";
    }
    
    // Summary
    echo "\nVERIFICATION SUMMARY:\n";
    echo "---------------------\n";
    echo "Total students: " . $result->num_rows . "\n";
    echo "Students missing SHS details: " . $missing_shs . "\n";
    echo "Students with incorrect grade level format: " . $incorrect_grade . "\n";
    echo "Students with invalid section names: " . $incorrect_section . "\n";
    
    if ($missing_shs == 0 && $incorrect_grade == 0 && $incorrect_section == 0) {
        echo "\nALL DATA IS CORRECTLY FORMATTED!\n";
    } else {
        echo "\nSOME ISSUES WERE FOUND. Please run the update script again or fix manually.\n";
    }
    
} else {
    echo "No students found in the database.\n";
}

echo "\nVerification completed.";
?> 
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

// First, get all existing students
$query = "SELECT * FROM students";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " students to update\n\n";
    
    // Set default values for SHS students
    $current_year = date('Y');
    $school_year = $current_year . '-' . ($current_year + 1);
    $default_track = 'Academic';
    $default_strand = 'STEM'; // Default to STEM
    $default_semester = 'First';
    
    // Process each student
    while($student = $result->fetch_assoc()) {
        echo "Processing student: " . $student['first_name'] . " " . $student['last_name'] . " (ID: " . $student['id'] . ")\n";
        
        // 1. Update grade_level to proper enum format for SHS
        $grade_level = $student['grade_level'];
        
        // For SHS, we need either Grade 11 or Grade 12
        if ($grade_level == 'Grade 11' || $grade_level == 'Grade 12') {
            $new_grade_level = $grade_level; // Already correct format
        } else if (preg_match('/11/', $grade_level)) {
            $new_grade_level = 'Grade 11';
        } else if (preg_match('/12/', $grade_level)) {
            $new_grade_level = 'Grade 12';
        } else if ($grade_level == '11') {
            $new_grade_level = 'Grade 11';
        } else if ($grade_level == '12') {
            $new_grade_level = 'Grade 12';
        } else if (preg_match('/grade 11/i', $grade_level)) {
            $new_grade_level = 'Grade 11';
        } else if (preg_match('/grade 12/i', $grade_level)) {
            $new_grade_level = 'Grade 12';
        } else {
            // If grade level is < 11 (like 7,8,9,10), assign to Grade 11
            // If grade level is > 12, assign to Grade 12
            if (is_numeric($grade_level) && $grade_level < 11) {
                $new_grade_level = 'Grade 11';
            } else {
                // Default to Grade 11 for any other format
                $new_grade_level = 'Grade 11';
            }
        }
        
        // 2. Determine strand based on current section if possible
        $section = $student['section'];
        $new_section = $section;
        $strand = $default_strand;
        
        // Try to extract strand from section name (e.g., STEM-11A, ABM-12B)
        if (strpos($section, 'STEM') !== false) {
            $strand = 'STEM';
        } else if (strpos($section, 'ABM') !== false) {
            $strand = 'ABM';
        } else if (strpos($section, 'HUMSS') !== false) {
            $strand = 'HUMSS';
        } else if (strpos($section, 'GAS') !== false) {
            $strand = 'GAS';
        } else if (strpos($section, 'TVL') !== false) {
            if (strpos($section, 'HE') !== false) {
                $strand = 'TVL-HE';
            } else if (strpos($section, 'ICT') !== false) {
                $strand = 'TVL-ICT';
            } else if (strpos($section, 'IA') !== false) {
                $strand = 'TVL-IA';
            } else {
                $strand = 'TVL-HE'; // Default TVL strand
            }
        } else {
            // Match with valid section names from new sections table
            $section_query = "SELECT * FROM sections WHERE grade_level = '$new_grade_level' ORDER BY name LIMIT 1";
            $section_result = $conn->query($section_query);
            if ($section_result->num_rows > 0) {
                $section_row = $section_result->fetch_assoc();
                $new_section = $section_row['name'];
                $strand = $section_row['strand'];
            } else {
                // If no matching section, assign appropriate section based on grade level
                if ($new_grade_level == 'Grade 11') {
                    $new_section = 'STEM-11A';
                    $strand = 'STEM';
                } else {
                    $new_section = 'STEM-12A';
                    $strand = 'STEM';
                }
            }
        }
        
        // 3. Update the student's grade level and section
        $update_query = "UPDATE students SET 
                         grade_level = '$new_grade_level', 
                         section = '$new_section' 
                         WHERE id = " . $student['id'];
        
        if ($conn->query($update_query) === TRUE) {
            echo "  - Updated grade level from '$grade_level' to '$new_grade_level'\n";
            echo "  - Updated section from '$section' to '$new_section'\n";
        } else {
            echo "  - Error updating student: " . $conn->error . "\n";
        }
        
        // 4. Check if student already has SHS details
        $check_query = "SELECT * FROM senior_highschool_details WHERE student_id = " . $student['id'];
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows == 0) {
            // Add new SHS details
            $insert_query = "INSERT INTO senior_highschool_details 
                           (student_id, track, strand, semester, school_year, previous_school) 
                           VALUES 
                           (" . $student['id'] . ", '$default_track', '$strand', '$default_semester', '$school_year', 'Previous School')";
            
            if ($conn->query($insert_query) === TRUE) {
                echo "  - Added SHS details with strand: $strand\n";
            } else {
                echo "  - Error adding SHS details: " . $conn->error . "\n";
            }
        } else {
            echo "  - Student already has SHS details\n";
        }
        
        echo "\n";
    }
    
    echo "All students updated successfully!\n";
} else {
    echo "No students found in the database.\n";
}

echo "\nProcess completed.";
?> 
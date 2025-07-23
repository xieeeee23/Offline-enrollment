<?php
// Database connection
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get database connection
$conn = getConnection();

echo "<h2>Fixing Student Section References</h2>";

// Check if section_id column exists in students table
$check_section_id = $conn->query("SHOW COLUMNS FROM students LIKE 'section_id'");
if ($check_section_id->num_rows == 0) {
    // Add section_id column if it doesn't exist
    $alter_sql = "ALTER TABLE students ADD COLUMN section_id INT AFTER section";
    if ($conn->query($alter_sql)) {
        echo "Added section_id column to students table.<br>";
    } else {
        echo "Error adding section_id column: " . $conn->error . "<br>";
    }
}

// Get all students with section names but no section_id
$students_sql = "SELECT id, section, grade_level FROM students WHERE section IS NOT NULL AND section != '' AND (section_id IS NULL OR section_id = 0)";
$students_result = $conn->query($students_sql);

if ($students_result->num_rows > 0) {
    echo "Found " . $students_result->num_rows . " students with section names but no section_id.<br>";
    
    // Get all sections
    $sections = [];
    $sections_sql = "SELECT id, name, grade_level FROM sections";
    $sections_result = $conn->query($sections_sql);
    while ($section = $sections_result->fetch_assoc()) {
        $key = $section['name'] . '_' . $section['grade_level'];
        $sections[$key] = $section['id'];
    }
    
    // Update students with section IDs
    $updated_count = 0;
    $missing_count = 0;
    
    while ($student = $students_result->fetch_assoc()) {
        $section_name = $student['section'];
        $grade_level = $student['grade_level'];
        
        // Try to find matching section
        $key = $section_name . '_' . $grade_level;
        if (isset($sections[$key])) {
            $section_id = $sections[$key];
            
            // Update student record
            $update_sql = "UPDATE students SET section_id = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $section_id, $student['id']);
            
            if ($stmt->execute()) {
                $updated_count++;
            } else {
                echo "Error updating student ID " . $student['id'] . ": " . $conn->error . "<br>";
            }
            $stmt->close();
        } else {
            $missing_count++;
            echo "Could not find matching section for student ID " . $student['id'] . " with section '" . $section_name . "' and grade level '" . $grade_level . "'.<br>";
        }
    }
    
    echo "Updated " . $updated_count . " students with section IDs.<br>";
    echo "Could not find matching sections for " . $missing_count . " students.<br>";
    
    // If there are missing sections, create them
    if ($missing_count > 0) {
        echo "<h3>Creating Missing Sections</h3>";
        
        // Get unique section name and grade level combinations that are missing
        $missing_sections_sql = "SELECT DISTINCT s.section, s.grade_level 
                               FROM students s 
                               LEFT JOIN sections sec ON s.section = sec.name AND s.grade_level = sec.grade_level 
                               WHERE sec.id IS NULL AND s.section IS NOT NULL AND s.section != ''";
        $missing_sections_result = $conn->query($missing_sections_sql);
        
        $created_count = 0;
        while ($missing = $missing_sections_result->fetch_assoc()) {
            $section_name = $missing['section'];
            $grade_level = $missing['grade_level'];
            
            // Check if this is a valid grade level for sections table
            if ($grade_level == 'Grade 11' || $grade_level == 'Grade 12') {
                // Extract strand from section name (assuming format like "ABM-11A")
                $strand = "";
                if (preg_match('/^([A-Z]+)-/', $section_name, $matches)) {
                    $strand = $matches[1];
                } else {
                    $strand = "GAS"; // Default to General Academic Strand if not specified
                }
                
                // Current school year
                $current_year = date('Y');
                $school_year = $current_year . '-' . ($current_year + 1);
                
                // Insert the missing section
                $insert_sql = "INSERT INTO sections (name, grade_level, strand, max_students, school_year, semester, status) 
                             VALUES (?, ?, ?, 40, ?, 'First', 'Active')";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ssss", $section_name, $grade_level, $strand, $school_year);
                
                if ($stmt->execute()) {
                    $section_id = $conn->insert_id;
                    $created_count++;
                    
                    // Update students with this section
                    $update_students_sql = "UPDATE students SET section_id = ? WHERE section = ? AND grade_level = ?";
                    $update_stmt = $conn->prepare($update_students_sql);
                    $update_stmt->bind_param("iss", $section_id, $section_name, $grade_level);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    echo "Created section '" . $section_name . "' for grade level '" . $grade_level . "' with strand '" . $strand . "'.<br>";
                } else {
                    echo "Error creating section '" . $section_name . "': " . $conn->error . "<br>";
                }
                $stmt->close();
            } else {
                echo "Skipping creation of section '" . $section_name . "' for grade level '" . $grade_level . "' as it's not a senior high school grade level.<br>";
            }
        }
        
        echo "Created " . $created_count . " missing sections.<br>";
    }
} else {
    echo "No students found with section names but missing section_id.<br>";
}

// Display summary of students and their sections
$summary_sql = "SELECT s.grade_level, sec.name as section_name, COUNT(*) as student_count 
               FROM students s 
               LEFT JOIN sections sec ON s.section_id = sec.id 
               WHERE s.section_id IS NOT NULL 
               GROUP BY s.grade_level, sec.name 
               ORDER BY s.grade_level, sec.name";
$summary_result = $conn->query($summary_sql);

echo "<h2>Student Distribution by Section</h2>";
if ($summary_result->num_rows > 0) {
    echo "<table border='1'><tr><th>Grade Level</th><th>Section</th><th>Student Count</th></tr>";
    while ($row = $summary_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['grade_level']) . "</td>";
        echo "<td>" . htmlspecialchars($row['section_name']) . "</td>";
        echo "<td>" . $row['student_count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No students assigned to sections.";
}

echo "<p>Section reference fix process completed.</p>";
?> 
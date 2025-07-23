<?php
// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Make a direct call to get sections data
function getDirectSections($grade_level, $conn) {
    // Normalize grade level format (handle both "11" and "Grade 11" formats)
    if ($grade_level === '11' || $grade_level === '12') {
        $grade_level = 'Grade ' . $grade_level;
    }
    
    // Query the sections table directly
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
            $sections[] = $row;
        }
    }
    
    return $sections;
}

// Get sections for Grade 11 and Grade 12
$grade11Sections = getDirectSections('Grade 11', $conn);
$grade12Sections = getDirectSections('Grade 12', $conn);

// Display the results
echo "<h1>Sections Test</h1>";

echo "<h2>Grade 11 Sections</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Strand</th><th>School Year</th><th>Semester</th><th>Status</th></tr>";
foreach ($grade11Sections as $section) {
    echo "<tr>";
    echo "<td>" . $section['id'] . "</td>";
    echo "<td>" . $section['name'] . "</td>";
    echo "<td>" . $section['strand'] . "</td>";
    echo "<td>" . $section['school_year'] . "</td>";
    echo "<td>" . $section['semester'] . "</td>";
    echo "<td>" . $section['status'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Grade 12 Sections</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Strand</th><th>School Year</th><th>Semester</th><th>Status</th></tr>";
foreach ($grade12Sections as $section) {
    echo "<tr>";
    echo "<td>" . $section['id'] . "</td>";
    echo "<td>" . $section['name'] . "</td>";
    echo "<td>" . $section['strand'] . "</td>";
    echo "<td>" . $section['school_year'] . "</td>";
    echo "<td>" . $section['semester'] . "</td>";
    echo "<td>" . $section['status'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?> 
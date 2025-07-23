<?php
// Include configuration and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get all sections
$query = "SELECT s.*, ss.strand_name 
          FROM sections s 
          LEFT JOIN shs_strands ss ON s.strand = ss.strand_code 
          ORDER BY s.grade_level, s.name";
$result = mysqli_query($conn, $query);

// Display results
echo "<h1>All Sections</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Grade Level</th><th>Strand</th><th>Strand Name</th><th>School Year</th><th>Semester</th><th>Status</th><th>Formatted Display</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['grade_level'] . "</td>";
    echo "<td>" . $row['strand'] . "</td>";
    echo "<td>" . $row['strand_name'] . "</td>";
    echo "<td>" . $row['school_year'] . "</td>";
    echo "<td>" . $row['semester'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "<td>" . formatSectionDisplay($row['name'], $row['grade_level'], $conn) . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 
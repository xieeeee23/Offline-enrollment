<?php
// Include database connection
require_once 'includes/config.php';

// Set student ID
$student_id = 7;

echo "<h1>Student Requirements Check for ID: $student_id</h1>";

// Get student information
$query = "SELECT * FROM students WHERE id = $student_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    echo "<p>Student not found with ID: $student_id</p>";
    exit;
}

$student = mysqli_fetch_assoc($result);
echo "<h2>Student: " . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . " (ID: $student_id)</h2>";

// Get student requirements
$req_query = "SELECT * FROM student_requirements WHERE student_id = $student_id";
$req_result = mysqli_query($conn, $req_query);

if (mysqli_num_rows($req_result) === 0) {
    echo "<p>No requirements record found for this student.</p>";
    exit;
}

$requirements = mysqli_fetch_assoc($req_result);
echo "<h3>Requirements Data:</h3>";
echo "<pre>";
print_r($requirements);
echo "</pre>";

// Get all requirement types from the database
$types_query = "SHOW COLUMNS FROM student_requirements WHERE Field NOT IN ('id', 'student_id', 'remarks', 'created_at', 'updated_at') AND Field NOT LIKE '%_file'";
$types_result = mysqli_query($conn, $types_query);

$requirement_types = [];
while ($column = mysqli_fetch_assoc($types_result)) {
    // Skip columns that end with _file using substr check for PHP 7.x compatibility
    if (substr($column['Field'], -5) !== '_file') {
        // Format the field name for display
        $display_name = str_replace('_', ' ', $column['Field']);
        $display_name = ucwords($display_name);
        $requirement_types[$column['Field']] = $display_name;
    }
}

echo "<h3>Requirement Types Found:</h3>";
echo "<pre>";
print_r($requirement_types);
echo "</pre>";

// Calculate requirements completion
$completed = 0;
$total = count($requirement_types);

echo "<h3>Requirements Status:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Requirement</th><th>Display Name</th><th>Status</th><th>Value</th></tr>";

foreach ($requirement_types as $key => $label) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($key) . "</td>";
    echo "<td>" . htmlspecialchars($label) . "</td>";
    
    if (isset($requirements[$key]) && $requirements[$key] == 1) {
        echo "<td style='background-color: #d4edda;'>Submitted</td>";
        echo "<td>" . htmlspecialchars($requirements[$key]) . "</td>";
        $completed++;
    } else {
        echo "<td style='background-color: #f8d7da;'>Not Submitted</td>";
        echo "<td>" . (isset($requirements[$key]) ? htmlspecialchars($requirements[$key]) : 'not set') . "</td>";
    }
    
    echo "</tr>";
}
echo "</table>";

echo "<h3>Summary:</h3>";
echo "<p>$completed of $total requirements submitted</p>";
echo "<p>Completion percentage: " . ($total > 0 ? round(($completed / $total) * 100, 2) : 0) . "%</p>";
?> 
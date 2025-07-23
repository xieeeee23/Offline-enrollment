<?php
// Include database connection
require_once 'includes/config.php';

echo "<h1>Fix Student Requirements</h1>";

// Get all students
$query = "SELECT id, first_name, last_name FROM students";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    echo "<p>No students found.</p>";
    exit;
}

// Get all requirement types from the database
$types_query = "SHOW COLUMNS FROM student_requirements WHERE Field NOT IN ('id', 'student_id', 'remarks', 'created_at', 'updated_at') AND Field NOT LIKE '%_file'";
$types_result = mysqli_query($conn, $types_query);

$requirement_types = [];
while ($column = mysqli_fetch_assoc($types_result)) {
    // Skip columns that end with _file using substr check for PHP 7.x compatibility
    if (substr($column['Field'], -5) !== '_file') {
        $requirement_types[] = $column['Field'];
    }
}

echo "<p>Found " . count($requirement_types) . " requirement types: " . implode(", ", $requirement_types) . "</p>";

echo "<h2>Processing Students:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Requirements Found</th><th>Completed</th><th>Total</th><th>Status</th></tr>";

while ($student = mysqli_fetch_assoc($result)) {
    $student_id = $student['id'];
    $student_name = $student['first_name'] . ' ' . $student['last_name'];
    
    // Check if student has requirements record
    $req_check_query = "SELECT id FROM student_requirements WHERE student_id = $student_id";
    $req_check_result = mysqli_query($conn, $req_check_query);
    
    if (mysqli_num_rows($req_check_result) === 0) {
        // Create requirements record
        $create_query = "INSERT INTO student_requirements (student_id) VALUES ($student_id)";
        if (mysqli_query($conn, $create_query)) {
            echo "<tr><td>$student_id</td><td>$student_name</td><td>No</td><td>0</td><td>" . count($requirement_types) . "</td><td style='background-color: #d4edda;'>Created</td></tr>";
        } else {
            echo "<tr><td>$student_id</td><td>$student_name</td><td>No</td><td>0</td><td>" . count($requirement_types) . "</td><td style='background-color: #f8d7da;'>Error: " . mysqli_error($conn) . "</td></tr>";
        }
        continue;
    }
    
    // Get student requirements
    $req_query = "SELECT * FROM student_requirements WHERE student_id = $student_id";
    $req_result = mysqli_query($conn, $req_query);
    $requirements = mysqli_fetch_assoc($req_result);
    
    // Calculate requirements completion
    $completed = 0;
    $total = count($requirement_types);
    
    foreach ($requirement_types as $key) {
        if (isset($requirements[$key]) && $requirements[$key] == 1) {
            $completed++;
        }
    }
    
    echo "<tr><td>$student_id</td><td>$student_name</td><td>Yes</td><td>$completed</td><td>$total</td><td style='background-color: #d4edda;'>OK</td></tr>";
}

echo "</table>";

echo "<p>Requirements check completed.</p>";
echo "<p><a href='modules/registrar/view_student.php?id=7'>View Student ID 7</a></p>";
?> 
<?php
// Include database connection
require_once 'includes/config.php';

// Get student ID from URL parameter
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

echo "<h1>Student Requirements Check</h1>";

if ($student_id <= 0) {
    echo "<p>Please provide a valid student ID using the 'id' parameter.</p>";
    echo "<p>Example: <a href='check_requirements.php?id=7'>check_requirements.php?id=7</a></p>";
    exit;
}

// Get student information
$query = "SELECT * FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo "<p>Student not found with ID: $student_id</p>";
    exit;
}

$student = mysqli_fetch_assoc($result);
echo "<h2>Student: " . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . " (ID: $student_id)</h2>";

// Get student requirements
$req_query = "SELECT * FROM student_requirements WHERE student_id = ?";
$req_stmt = mysqli_prepare($conn, $req_query);
mysqli_stmt_bind_param($req_stmt, "i", $student_id);
mysqli_stmt_execute($req_stmt);
$req_result = mysqli_stmt_get_result($req_stmt);

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

// Check for any deleted requirements
echo "<h3>Check for Deleted Requirements:</h3>";
$deleted_req_query = "SELECT * FROM student_requirements WHERE student_id = ? AND (";
$conditions = [];

foreach ($requirement_types as $key => $label) {
    $conditions[] = "$key = 0";
}

$deleted_req_query .= implode(" OR ", $conditions) . ")";
$deleted_req_stmt = mysqli_prepare($conn, $deleted_req_query);
mysqli_stmt_bind_param($deleted_req_stmt, "i", $student_id);
mysqli_stmt_execute($deleted_req_stmt);
$deleted_req_result = mysqli_stmt_get_result($deleted_req_stmt);

if (mysqli_num_rows($deleted_req_result) > 0) {
    echo "<p>Found requirements with status = 0 (possibly deleted):</p>";
    $deleted_req = mysqli_fetch_assoc($deleted_req_result);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Requirement</th><th>Status</th></tr>";
    
    foreach ($requirement_types as $key => $label) {
        if (isset($deleted_req[$key]) && $deleted_req[$key] == 0) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($label) . "</td>";
            echo "<td>0 (Deleted/Not Submitted)</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
} else {
    echo "<p>No deleted requirements found.</p>";
}
?> 
<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// This file returns students based on filters
header('Content-Type: application/json');

if (!checkAccess(['admin', 'teacher'])) {
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Get parameters
$grade_level = isset($_GET['grade_level']) ? cleanInput($_GET['grade_level']) : '';
$section = isset($_GET['section']) ? cleanInput($_GET['section']) : '';

// Build the query
$where_clauses = ["s.status = 'Active'"];
$query_params = [];

if (!empty($grade_level)) {
    $where_clauses[] = "s.grade_level = ?";
    $query_params[] = $grade_level;
}

if (!empty($section)) {
    $where_clauses[] = "s.section = ?";
    $query_params[] = $section;
}

$sql = "SELECT 
            s.id, 
            s.lrn, 
            s.first_name, 
            s.middle_name, 
            s.last_name, 
            s.grade_level, 
            s.section
        FROM 
            students s
        WHERE 
            " . implode(' AND ', $where_clauses) . "
        ORDER BY 
            s.grade_level, s.section, s.last_name, s.first_name";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);

if (!empty($query_params)) {
    $types = str_repeat('s', count($query_params));
    mysqli_stmt_bind_param($stmt, $types, ...$query_params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Format the results
$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = [
        'id' => $row['id'],
        'lrn' => $row['lrn'],
        'first_name' => $row['first_name'],
        'middle_name' => $row['middle_name'],
        'last_name' => $row['last_name'],
        'grade_level' => $row['grade_level'],
        'section' => $row['section'],
        'full_name' => $row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name']
    ];
}

echo json_encode($students);
?> 
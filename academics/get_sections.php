<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// This file returns section options for a given grade level
header('Content-Type: application/json');

if (!isset($_GET['grade_level']) || empty($_GET['grade_level'])) {
    echo json_encode([]);
    exit();
}

$grade_level = cleanInput($_GET['grade_level']);

$sections_query = "SELECT DISTINCT section FROM students WHERE grade_level = ? ORDER BY section";
$sections_stmt = mysqli_prepare($conn, $sections_query);
mysqli_stmt_bind_param($sections_stmt, 's', $grade_level);
mysqli_stmt_execute($sections_stmt);
$sections_result = mysqli_stmt_get_result($sections_stmt);

$sections = [];
while ($row = mysqli_fetch_assoc($sections_result)) {
    $sections[] = $row['section'];
}

echo json_encode($sections);
?> 
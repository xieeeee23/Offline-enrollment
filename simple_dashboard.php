<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>Not logged in. <a href='start.php'>Login first</a></p>";
    exit;
}

// Display user information
echo "<h1>Simple Dashboard</h1>";
echo "<p>Welcome, " . $_SESSION['name'] . " (" . $_SESSION['role'] . ")</p>";

// Include database connection
require_once 'includes/config.php';

// Get counts
$students_count = 0;
$teachers_count = 0;
$users_count = 0;

// Count students
$query = "SELECT COUNT(*) as count FROM students";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $students_count = $row['count'];
}

// Count teachers
$query = "SELECT COUNT(*) as count FROM teachers";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $teachers_count = $row['count'];
}

// Count users
$query = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $users_count = $row['count'];
}

// Display counts
echo "<h2>System Overview</h2>";
echo "<p>Students: " . $students_count . "</p>";
echo "<p>Teachers: " . $teachers_count . "</p>";
echo "<p>Users: " . $users_count . "</p>";

// Show navigation links
echo "<h2>Navigation</h2>";
echo "<ul>";
echo "<li><a href='modules/admin/users.php'>Manage Users</a></li>";
echo "<li><a href='modules/registrar/students.php'>Manage Students</a></li>";
echo "<li><a href='modules/teacher/teachers.php'>Manage Teachers</a></li>";
echo "</ul>";

// Logout link
echo "<p><a href='logout.php'>Logout</a></p>";
?> 
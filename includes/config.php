<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shs_enrollment');

// Establish database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base URL - updated to match the actual URL structure
define('BASE_URL', 'http://localhost/Offline%20enrollment/');

// System name
define('SYSTEM_NAME', 'INTRODUCING EFFICIENT STUDENT REGISTRATION: THE KRISLIZZ INTERNATIONAL ACADEMY INC. ENROLLMENT SYSTEM');

// Auto check and generate school years
require_once __DIR__ . '/auto_check_school_years.php';
?> 
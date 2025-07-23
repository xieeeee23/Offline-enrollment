<?php
// Include configuration
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set a user session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
}

// Function to make API request
function fetchSections($gradeLevel) {
    $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    $url = $baseUrl . "/modules/registrar/get_sections.php?grade_level=" . urlencode($gradeLevel);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Test Grade 11 sections
echo "<h2>Grade 11 Sections</h2>";
$grade11Sections = fetchSections('Grade 11');
echo "<pre>";
print_r($grade11Sections);
echo "</pre>";

// Test Grade 12 sections
echo "<h2>Grade 12 Sections</h2>";
$grade12Sections = fetchSections('Grade 12');
echo "<pre>";
print_r($grade12Sections);
echo "</pre>";
?> 
<?php
// Debug script to help diagnose form submission issues
session_start();

echo "<h1>Form Submission Debug</h1>";

echo "<h2>POST Data</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>GET Data</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>SESSION Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>SERVER Data</h2>";
echo "<pre>";
$server_info = [
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
    'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
    'PHP_SELF' => $_SERVER['PHP_SELF'] ?? 'N/A',
    'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? 'N/A',
    'HTTP_REFERER' => $_SERVER['HTTP_REFERER'] ?? 'N/A',
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
    'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'N/A',
    'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'N/A',
    'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'N/A'
];
print_r($server_info);
echo "</pre>";

echo "<h2>Test Form</h2>";
echo "<form method='post' action='debug_form.php'>";
echo "<input type='text' name='test_field' value='test value'>";
echo "<button type='submit' name='test_submit' value='1'>Submit Test</button>";
echo "</form>";

echo "<h2>Link to Users Page</h2>";
echo "<a href='modules/admin/users.php'>Go to Users Page</a>";
?> 
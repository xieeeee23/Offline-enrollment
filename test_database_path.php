<?php
$relative_path = '';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

// Check if user is logged in and has admin role
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}

echo "<h1>Testing Database Path</h1>";

// Display the current path
echo "<p>Current script: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>Current path: " . $_SERVER['PHP_SELF'] . "</p>";

// Test path to database.php
$database_path = $relative_path . 'modules/admin/database.php';
echo "<p>Database path: " . $database_path . "</p>";

// Check if the file exists
if (file_exists($database_path)) {
    echo "<p style='color:green'>Database file exists!</p>";
} else {
    echo "<p style='color:red'>Database file does not exist!</p>";
}

// Try to include the file
echo "<p>Trying to include the file...</p>";
try {
    include_once $database_path;
    echo "<p style='color:green'>File included successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error including file: " . $e->getMessage() . "</p>";
}

// Create a link to the database page
echo "<p><a href='" . $relative_path . "modules/admin/database.php'>Click here to go to database.php</a></p>";

// Create a link using different path formats
echo "<p><a href='modules/admin/database.php'>Relative link: modules/admin/database.php</a></p>";
echo "<p><a href='/modules/admin/database.php'>Root link: /modules/admin/database.php</a></p>";
echo "<p><a href='" . BASE_URL . "modules/admin/database.php'>BASE_URL link: " . BASE_URL . "modules/admin/database.php</a></p>";
?> 
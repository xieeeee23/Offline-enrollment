<?php
// This file fixes the URL duplication issue by directly including the database.php file
// instead of redirecting to it

// Define the relative path
$relative_path = './';

// Include the necessary files
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';
require_once $relative_path . 'includes/Database.php';

// Check if user is logged in and has admin role
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    header("Location: {$relative_path}dashboard.php");
    exit;
}

// Include the database.php file directly
include $relative_path . 'modules/admin/database.php';
?> 
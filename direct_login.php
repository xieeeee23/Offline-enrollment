<?php
// Start session
session_start();

// Include required files
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set session variables for admin user
$_SESSION['user_id'] = 1; // Assuming admin has ID 1
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['name'] = 'Administrator';

// Log the login action
logAction(1, 'LOGIN', 'Direct login for troubleshooting');

// Redirect to dashboard
header("Location: dashboard.php");
exit;
?> 
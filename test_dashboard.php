<?php
// Start session
session_start();

// Set session variables to simulate logged in user
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['name'] = 'Administrator';
$_SESSION['logged_in'] = true;

// Redirect to dashboard
header("Location: dashboard.php");
exit;
?> 
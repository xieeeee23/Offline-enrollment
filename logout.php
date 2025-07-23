<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Log logout action
    logAction($_SESSION['user_id'], 'LOGOUT', 'User logged out');
    
    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to login page
redirect('login.php');
?>
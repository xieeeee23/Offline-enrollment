<?php
// This file provides direct access to the users page
// It helps bypass URL duplication issues

// Include database connection and functions
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and has admin role
if (!isLoggedIn() || !checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('login.php');
    exit;
}

// Process form submission if any
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store POST data in session to pass to the users.php page
    $_SESSION['user_form_data'] = $_POST;
    
    // Log the form submission
    error_log('Form submitted through direct_users.php: ' . print_r($_POST, true));
    
    // Redirect to the actual users.php page
    redirect('modules/admin/users.php');
    exit;
}

// If not a form submission, just redirect to the users page
redirect('modules/admin/users.php');
exit;
?> 
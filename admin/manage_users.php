<?php
// Redirect to users.php while preserving any query parameters
$query_string = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';

// Calculate the relative path to ensure correct redirection
$relative_path = '../../';
require_once $relative_path . 'includes/functions.php';

// Use the redirect function to ensure proper redirection
redirect('users.php' . $query_string);
exit;
?> 
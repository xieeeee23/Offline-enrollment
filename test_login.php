<?php
// Start session
session_start();

// Clear any existing session data
session_unset();
session_destroy();
session_start();

// Redirect to login page
header("Location: login.php");
exit;
?> 
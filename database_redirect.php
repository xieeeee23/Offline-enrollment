<?php
// This script redirects to the correct database management file

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to the fix_url.php file which will handle URL duplication issues
header("Location: fix_url.php");
exit;
?> 
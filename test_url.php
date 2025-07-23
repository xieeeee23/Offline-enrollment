<?php
// This script tests and fixes URL issues

// Display the current URL
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
echo "<p>Current URL: " . htmlspecialchars($current_url) . "</p>";

// Parse the URL
$parsed_url = parse_url($current_url);
echo "<p>Parsed path: " . htmlspecialchars($parsed_url['path']) . "</p>";

// Fix duplicate path segments
$fixed_path = preg_replace('/\/offline%20enrollment\/offline%20enrollment\//', '/offline%20enrollment/', $parsed_url['path']);
echo "<p>Fixed path: " . htmlspecialchars($fixed_path) . "</p>";

// Reconstruct the URL
$fixed_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$fixed_path";
echo "<p>Fixed URL: " . htmlspecialchars($fixed_url) . "</p>";

// Show a link to the database page
echo "<p><a href='modules/admin/database.php'>Go to database.php</a></p>";
echo "<p><a href='database_redirect.php'>Go to database_redirect.php</a></p>";

// Show server information
echo "<h3>Server Information</h3>";
echo "<pre>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "</pre>";
?> 
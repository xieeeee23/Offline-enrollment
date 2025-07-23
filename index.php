<?php
// Check if database exists and has required tables
$host = 'localhost';
$user = 'root';
$password = '';
$db_name = 'shs_enrollment';

// Try to connect to the database
$conn = @mysqli_connect($host, $user, $password, $db_name);

// If connection fails or tables don't exist, redirect to setup
$setup_needed = true;

if ($conn) {
    // Check if users table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if (mysqli_num_rows($result) > 0) {
        $setup_needed = false;
    }
    mysqli_close($conn);
}

// Check if this is a module request
if (isset($_GET['url'])) {
    $url = $_GET['url'];
    
    // Check for module pattern (e.g., modules/registrar/requirements.php)
    if (preg_match('/^modules\/(\w+)\/(\w+)\.php/', $url, $matches)) {
        $module = $matches[1];
        $page = basename($matches[2], '.php');
        
        // Rebuild the URL with our redirect system
        $redirect_url = "redirect.php?module=$module&page=$page";
        
        // Append any query string
        $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if ($query && $query !== 'url=' . urlencode($url)) {
            // Parse the query string
            parse_str($query, $query_params);
            
            // Remove the 'url' parameter
            if (isset($query_params['url'])) {
                unset($query_params['url']);
            }
            
            // If there are remaining parameters, add them to the redirect URL
            if (!empty($query_params)) {
                $redirect_url .= '&' . http_build_query($query_params);
            }
        }
        
        header("Location: $redirect_url");
        exit;
    }
}

if ($setup_needed) {
    header("Location: setup_database.php");
} else {
    header("Location: login.php");
}
exit;
?> 
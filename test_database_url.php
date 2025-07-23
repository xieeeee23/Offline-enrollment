<?php
// Test file to verify database URL redirection

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the base URL and path
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['SCRIPT_NAME']);

// Ensure path ends with a slash
if (substr($script_dir, -1) !== '/') {
    $script_dir .= '/';
}

// Build the absolute URL
$redirect_url = $protocol . $host . $script_dir . 'modules/admin/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database URL Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h2>Database URL Test</h2>
            </div>
            <div class="card-body">
                <p>This page tests the correct URL redirection for the database management page.</p>
                <p>Current script path: <code><?php echo $_SERVER['SCRIPT_NAME']; ?></code></p>
                <p>Generated database URL: <code><?php echo htmlspecialchars($redirect_url); ?></code></p>
                
                <div class="mt-4">
                    <h4>Test Links:</h4>
                    <ul class="list-group mb-3">
                        <li class="list-group-item">
                            <a href="database.php" class="btn btn-primary">Test Root Database Redirect</a>
                            <small class="text-muted ms-2">Uses database.php in root</small>
                        </li>
                        <li class="list-group-item">
                            <a href="database_redirect.php" class="btn btn-secondary">Test Database Redirect</a>
                            <small class="text-muted ms-2">Uses database_redirect.php</small>
                        </li>
                        <li class="list-group-item">
                            <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="btn btn-success">Direct Link to Database Page</a>
                            <small class="text-muted ms-2">Uses absolute URL</small>
                        </li>
                    </ul>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> All these links should take you to the database management page without showing a "Not Found" error.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
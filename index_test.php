<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #4e73df; }
        .card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .card h2 { margin-top: 0; color: #224abe; }
        ul { padding-left: 20px; }
        a { color: #4e73df; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .btn { display: inline-block; padding: 8px 15px; background: #4e73df; color: white; border-radius: 5px; margin-right: 10px; }
        .btn:hover { background: #224abe; text-decoration: none; }
    </style>
</head>
<body>
    <h1>THE KRISLIZZ INTERNATIONAL ACADEMY INC. ENROLLMENT SYSTEM</h1>
    <p>System Test Dashboard</p>
    
    <div class="card">
        <h2>Quick Access</h2>
        <p>Use these links to access the system:</p>
        <a href="start.php" class="btn">Quick Login</a>
        <a href="simple_dashboard.php" class="btn">Simple Dashboard</a>
        <a href="login.php" class="btn">Normal Login</a>
    </div>
    
    <div class="card">
        <h2>System Tests</h2>
        <ul>
            <li><a href="simple_test.php">Complete System Test</a></li>
            <li><a href="test_db.php">Database Connection Test</a></li>
            <li><a href="phpinfo.php">PHP Information</a></li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Direct Module Access</h2>
        <ul>
            <li><a href="modules/admin/users.php">Users Management</a></li>
            <li><a href="modules/registrar/students.php">Students Management</a></li>
            <li><a href="modules/teacher/teachers.php">Teachers Management</a></li>
        </ul>
    </div>
    
    <div class="card">
        <h2>System Information</h2>
        <p>PHP Version: <?php echo phpversion(); ?></p>
        <p>Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
        <p>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
    </div>
</body>
</html> 
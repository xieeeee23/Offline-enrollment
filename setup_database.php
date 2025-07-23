<?php
// Database setup script for LocalEnroll Pro

// Define database connection parameters
$host = 'localhost';
$user = 'root';
$password = '';
$db_name = 'localenroll_db';

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalEnroll Pro - Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .step {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .step-success {
            background-color: #d1e7dd;
        }
        .step-error {
            background-color: #f8d7da;
        }
        .step-pending {
            background-color: #e2e3e5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <h1 class="text-center mb-4">LocalEnroll Pro - Database Setup</h1>
            
            <?php
            // Step 1: Connect to MySQL server
            echo '<div class="step" id="step1">';
            echo '<h3><i class="fas fa-database me-2"></i>Step 1: Connecting to MySQL Server</h3>';
            
            $conn = mysqli_connect($host, $user, $password);
            
            if (!$conn) {
                echo '<div class="alert alert-danger">Connection failed: ' . mysqli_connect_error() . '</div>';
                echo '<p>Please check your MySQL configuration and try again.</p>';
                echo '</div>';
                exit;
            }
            
            echo '<div class="alert alert-success">Connected to MySQL server successfully!</div>';
            echo '</div>';
            
            // Step 2: Drop existing database if it exists
            echo '<div class="step" id="step2">';
            echo '<h3><i class="fas fa-trash me-2"></i>Step 2: Dropping Existing Database (if exists)</h3>';
            
            if (mysqli_query($conn, "DROP DATABASE IF EXISTS $db_name")) {
                echo '<div class="alert alert-success">Existing database dropped or did not exist.</div>';
            } else {
                echo '<div class="alert alert-danger">Error dropping database: ' . mysqli_error($conn) . '</div>';
            }
            echo '</div>';
            
            // Step 3: Create new database
            echo '<div class="step" id="step3">';
            echo '<h3><i class="fas fa-plus-circle me-2"></i>Step 3: Creating New Database</h3>';
            
            if (mysqli_query($conn, "CREATE DATABASE $db_name")) {
                echo '<div class="alert alert-success">Database created successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error creating database: ' . mysqli_error($conn) . '</div>';
                echo '</div>';
                exit;
            }
            echo '</div>';
            
            // Step 4: Select the database
            echo '<div class="step" id="step4">';
            echo '<h3><i class="fas fa-check-circle me-2"></i>Step 4: Selecting Database</h3>';
            
            if (mysqli_select_db($conn, $db_name)) {
                echo '<div class="alert alert-success">Database selected successfully!</div>';
            } else {
                echo '<div class="alert alert-danger">Error selecting database: ' . mysqli_error($conn) . '</div>';
                echo '</div>';
                exit;
            }
            echo '</div>';
            
            // Step 5: Create tables and insert data
            echo '<div class="step" id="step5">';
            echo '<h3><i class="fas fa-table me-2"></i>Step 5: Creating Tables and Inserting Data</h3>';
            
            // Read SQL file
            $sql_file = file_get_contents('database/localenroll_db.sql');
            
            // Remove DROP DATABASE, CREATE DATABASE, and USE statements as we've already handled those
            $sql_file = preg_replace('/DROP DATABASE.*?;/s', '', $sql_file);
            $sql_file = preg_replace('/CREATE DATABASE.*?;/s', '', $sql_file);
            $sql_file = preg_replace('/USE.*?;/s', '', $sql_file);
            
            // Split SQL commands by semicolon
            $commands = explode(';', $sql_file);
            
            $success = true;
            $error_messages = [];
            $success_count = 0;
            
            foreach ($commands as $command) {
                $command = trim($command);
                if (!empty($command)) {
                    if (mysqli_query($conn, $command)) {
                        $success_count++;
                    } else {
                        $success = false;
                        $error_messages[] = "Error: " . mysqli_error($conn) . "<br>Command: " . htmlspecialchars(substr($command, 0, 100)) . "...";
                    }
                }
            }
            
            if ($success) {
                echo '<div class="alert alert-success">All database commands executed successfully! (' . $success_count . ' commands)</div>';
            } else {
                echo '<div class="alert alert-danger">Some database commands failed. ' . $success_count . ' commands were successful.</div>';
                echo '<div class="mt-3"><strong>Errors:</strong></div>';
                echo '<ul>';
                foreach ($error_messages as $error) {
                    echo '<li>' . $error . '</li>';
                }
                echo '</ul>';
            }
            echo '</div>';
            
            // Close connection
            mysqli_close($conn);
            ?>
            
            <div class="text-center mt-4">
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <h4><i class="fas fa-check-circle me-2"></i>Database Setup Completed Successfully!</h4>
                    <p>You can now login to the system using the default credentials:</p>
                    <table class="table table-bordered mt-3">
                        <thead class="table-primary">
                            <tr>
                                <th>Role</th>
                                <th>Username</th>
                                <th>Password</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Admin</td>
                                <td>admin</td>
                                <td>admin123</td>
                            </tr>
                            <tr>
                                <td>Registrar</td>
                                <td>registrar1</td>
                                <td>admin123</td>
                            </tr>
                            <tr>
                                <td>Teacher</td>
                                <td>teacher1</td>
                                <td>admin123</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <a href="login.php" class="btn btn-primary btn-lg mt-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Go to Login Page
                </a>
                <?php else: ?>
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle me-2"></i>Database Setup Failed</h4>
                    <p>Please check the errors above and try again.</p>
                </div>
                <button onclick="location.reload()" class="btn btn-danger btn-lg mt-3">
                    <i class="fas fa-redo me-2"></i>Try Again
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-4 text-muted">
            <p>&copy; <?php echo date('Y'); ?> LocalEnroll Pro. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
<?php
ob_end_flush();
?> 
<?php
// Password fix script for LocalEnroll Pro

// Define database connection parameters
$host = 'localhost';
$user = 'root';
$password = '';
$db_name = 'localenroll_db';

// Connect to database
$conn = mysqli_connect($host, $user, $password, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalEnroll Pro - Fix Passwords</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 50px 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">LocalEnroll Pro - Fix Passwords</h1>
        
        <?php
        // Check if form is submitted
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            // Set default password
            $default_password = 'admin123';
            $hashed_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // Hash for 'admin123'
            
            // Update all users with the hardcoded hashed password
            $query = "UPDATE users SET password = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                echo '<div class="alert alert-success">';
                echo '<h4><i class="fas fa-check-circle me-2"></i>Password Fix Successful!</h4>';
                echo '<p>All user passwords have been reset to: <strong>admin123</strong></p>';
                echo '<p>The following users were updated:</p>';
                
                // Get all users
                $users_query = "SELECT id, username, name, role FROM users";
                $users_result = mysqli_query($conn, $users_query);
                
                echo '<table class="table table-striped">';
                echo '<thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th></tr></thead>';
                echo '<tbody>';
                
                while ($user = mysqli_fetch_assoc($users_result)) {
                    echo "<tr>";
                    echo "<td>" . $user['id'] . "</td>";
                    echo "<td>" . $user['username'] . "</td>";
                    echo "<td>" . $user['name'] . "</td>";
                    echo "<td><span class='badge bg-" . getRoleBadgeColor($user['role']) . "'>" . ucfirst($user['role']) . "</span></td>";
                    echo "</tr>";
                }
                
                echo '</tbody></table>';
                echo '</div>';
                
                echo '<div class="text-center mt-4">';
                echo '<a href="login.php" class="btn btn-primary btn-lg">';
                echo '<i class="fas fa-sign-in-alt me-2"></i>Go to Login Page';
                echo '</a>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger">';
                echo '<h4><i class="fas fa-exclamation-triangle me-2"></i>Password Fix Failed</h4>';
                echo '<p>Error: ' . mysqli_error($conn) . '</p>';
                echo '</div>';
                
                echo '<div class="text-center mt-4">';
                echo '<button onclick="location.reload()" class="btn btn-danger btn-lg">';
                echo '<i class="fas fa-redo me-2"></i>Try Again';
                echo '</button>';
                echo '</div>';
            }
        } else {
            // Show confirmation form
            ?>
            <div class="alert alert-warning">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>Warning</h4>
                <p>This script will reset ALL user passwords in the database to <strong>admin123</strong>.</p>
                <p>This should only be used if you are having issues with user login.</p>
            </div>
            
            <form method="post" action="">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                    <label class="form-check-label" for="confirmCheck">
                        I understand that this will reset all passwords
                    </label>
                </div>
                
                <input type="hidden" name="confirm" value="yes">
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger" id="submitBtn" disabled>
                        <i class="fas fa-key me-2"></i>Reset All Passwords
                    </button>
                </div>
            </form>
            
            <div class="mt-4">
                <a href="login.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
            </div>
            
            <script>
                document.getElementById('confirmCheck').addEventListener('change', function() {
                    document.getElementById('submitBtn').disabled = !this.checked;
                });
            </script>
            <?php
        }
        ?>
    </div>
    
    <div class="text-center mt-4 text-muted">
        <p>&copy; <?php echo date('Y'); ?> LocalEnroll Pro. All rights reserved.</p>
    </div>
</body>
</html>
<?php
// Helper function to get badge color based on role
function getRoleBadgeColor($role) {
    switch ($role) {
        case 'admin':
            return 'primary';
        case 'registrar':
            return 'success';
        case 'teacher':
            return 'info';
        default:
            return 'secondary';
    }
}

// Close connection
mysqli_close($conn);
ob_end_flush();
?> 
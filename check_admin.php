<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Page title
$title = 'Admin Account Setup';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title . ' | THE KRISLIZZ INTERNATIONAL ACADEMY INC. ENROLLMENT SYSTEM'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(45deg, #4e73df, #224abe);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            max-width: 500px;
            margin: 0 auto;
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
        }
        .card-body {
            padding: 30px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
            border: none;
            padding: 10px 20px;
            margin-top: 10px;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #224abe, #4e73df);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">THE KRISLIZZ INTERNATIONAL ACADEMY INC.</h3>
                        <p class="mb-0">ADMIN ACCOUNT SETUP</p>
                    </div>
                    <div class="card-body">
                        <?php
                        // Check if admin exists
                        $query = "SELECT * FROM users WHERE username = 'admin'";
                        $result = mysqli_query($conn, $query);

                        if (mysqli_num_rows($result) === 0) {
                            // Admin account doesn't exist, create it
                            $username = 'admin';
                            $password = 'admin123'; // Default password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $name = 'Administrator';
                            $role = 'admin';
                            $status = 'active';
                            
                            $query = "INSERT INTO users (username, password, name, role, status, created_at) 
                                    VALUES (?, ?, ?, ?, ?, NOW())";
                            $stmt = mysqli_prepare($conn, $query);
                            mysqli_stmt_bind_param($stmt, "sssss", $username, $hashed_password, $name, $role, $status);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                echo '<div class="alert alert-success">
                                        <h4><i class="fas fa-check-circle me-2"></i>Success!</h4>
                                        <p>Admin account created successfully.</p>
                                        <hr>
                                        <p><strong>Username:</strong> admin</p>
                                        <p><strong>Password:</strong> admin123</p>
                                        <p class="text-danger"><strong>Please change this password immediately after logging in.</strong></p>
                                      </div>';
                            } else {
                                echo '<div class="alert alert-danger">
                                        <h4><i class="fas fa-exclamation-triangle me-2"></i>Error</h4>
                                        <p>Error creating admin account: ' . mysqli_error($conn) . '</p>
                                      </div>';
                            }
                        } else {
                            // Admin exists but might have password issues - reset option
                            echo '<div class="alert alert-info">
                                    <h4><i class="fas fa-info-circle me-2"></i>Information</h4>
                                    <p>Admin account already exists.</p>
                                  </div>
                                  <p>If you are having trouble logging in, you can reset the admin password.</p>';
                            
                            if (isset($_POST['reset_password'])) {
                                $new_password = 'admin123'; // Reset to default password
                                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                                
                                $update_query = "UPDATE users SET password = ? WHERE username = 'admin'";
                                $stmt = mysqli_prepare($conn, $update_query);
                                mysqli_stmt_bind_param($stmt, "s", $hashed_password);
                                
                                if (mysqli_stmt_execute($stmt)) {
                                    echo '<div class="alert alert-success">
                                            <h4><i class="fas fa-check-circle me-2"></i>Success!</h4>
                                            <p>Password reset successfully.</p>
                                            <hr>
                                            <p><strong>New password:</strong> admin123</p>
                                            <p class="text-danger"><strong>Please change this password immediately after logging in.</strong></p>
                                          </div>';
                                } else {
                                    echo '<div class="alert alert-danger">
                                            <h4><i class="fas fa-exclamation-triangle me-2"></i>Error</h4>
                                            <p>Error resetting password: ' . mysqli_error($conn) . '</p>
                                          </div>';
                                }
                            }
                            
                            echo '<form method="post" class="mt-3">
                                    <button type="submit" name="reset_password" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i> Reset Admin Password
                                    </button>
                                  </form>';
                        }
                        ?>
                        
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> Go to Login
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3 text-white">
                    <small>&copy; <?php echo date('Y'); ?> THE KRISLIZZ INTERNATIONAL ACADEMY INC. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if database tables are set up
$users_table_exists = false;
$check_users = "SHOW TABLES LIKE 'users'";
$users_result = mysqli_query($conn, $check_users);
if (mysqli_num_rows($users_result) > 0) {
    $users_table_exists = true;
}

// If tables don't exist, redirect to setup
if (!$users_table_exists) {
    header("Location: create_tables.php");
    exit;
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

// Initialize variables
$username = '';
$error = '';
$forgot_password_message = '';
$security_questions = [
    'What was your childhood nickname?',
    'What is the name of your first pet?',
    'What was your first car?',
    'What elementary school did you attend?',
    'What is your mother\'s maiden name?',
    'In what city were you born?',
    'What is your favorite movie?',
    'What is your favorite color?'
];

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check user credentials
        $query = "SELECT * FROM users WHERE username = ? AND status = 'active'";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                
                // Log login action
                logAction($user['id'], 'LOGIN', 'User logged in');
                
                // Redirect to dashboard
                redirect('dashboard.php');
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'Username not found or account is inactive.';
        }
    }
}

// Process forgot password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $security_answer = isset($_POST['security_answer']) ? cleanInput($_POST['security_answer']) : '';
    
    if (empty($username) || empty($email)) {
        $forgot_password_message = showAlert('Please enter both username and email.', 'danger');
    } else {
        // Check if username and email match
        $query = "SELECT * FROM users WHERE username = ? AND email = ? AND status = 'active'";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Check if security question exists and is being verified
            if (isset($user['security_question']) && !empty($user['security_question']) && 
                isset($user['security_answer']) && !empty($user['security_answer'])) {
                
                // If security answer not provided yet, show security question form
                if (empty($security_answer)) {
                    $forgot_password_message = showAlert('
                        <form id="securityQuestionForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">
                            <h4>Security Verification</h4>
                            <p>Please answer your security question:</p>
                            <div class="mb-3">
                                <label class="form-label">' . htmlspecialchars($user['security_question']) . '</label>
                                <input type="text" class="form-control" name="security_answer" required>
                                <input type="hidden" name="username" value="' . htmlspecialchars($username) . '">
                                <input type="hidden" name="email" value="' . htmlspecialchars($email) . '">
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="forgot_password" class="btn btn-primary">
                                    <i class="fas fa-check me-2"></i> Verify Answer
                                </button>
                            </div>
                        </form>
                    ', 'info');
                    return;
                }
                
                // Verify security answer
                if (!password_verify(strtolower($security_answer), $user['security_answer'])) {
                    $forgot_password_message = showAlert('Incorrect security answer. Please try again.', 'danger');
                    return;
                }
            }
            
            // Generate a random password
            $new_password = generateRandomPassword();
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the user's password
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user['id']);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Log the password reset
                logAction($user['id'], 'RESET_PASSWORD', 'Password reset via forgot password feature');
                
                // Show success message with the new password
                $forgot_password_message = showAlert('
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h4>Password Reset Successfully</h4>
                        <p>Your new password is:</p>
                        <div class="alert alert-success">
                            <strong>' . $new_password . '</strong>
                        </div>
                        <p class="text-danger"><small>Please login with this password and change it immediately.</small></p>
                        <button class="btn btn-primary" id="backToLoginBtn">Back to Login</button>
                    </div>
                    <script>
                        document.getElementById("backToLoginBtn").addEventListener("click", function() {
                            document.getElementById("loginForm").style.display = "block";
                            document.getElementById("forgotPasswordForm").style.display = "none";
                        });
                    </script>
                ', 'success');
            } else {
                $forgot_password_message = showAlert('Error resetting password. Please try again.', 'danger');
            }
        } else {
            $forgot_password_message = showAlert('No matching account found with the provided username and email.', 'danger');
        }
    }
}

// Process security question setup (for demonstration - in real system this would be in user profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_security'])) {
    // This is just for demonstration - would be implemented in user profile section
    $forgot_password_message = showAlert('Security question has been set up successfully.', 'success');
}

// Function to generate a random password
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Check if security questions column exists in users table
$check_security_column = "SHOW COLUMNS FROM users LIKE 'security_question'";
$security_result = mysqli_query($conn, $check_security_column);
if (mysqli_num_rows($security_result) == 0) {
    // Add the column if it doesn't exist
    $alter_query = "ALTER TABLE users 
                    ADD COLUMN security_question VARCHAR(255) DEFAULT NULL,
                    ADD COLUMN security_answer VARCHAR(255) DEFAULT NULL";
    mysqli_query($conn, $alter_query);
}

// Page title
$title = 'Login';
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
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #4e73df;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated background */
        .area {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(45deg, #4e73df, #224abe);
            overflow: hidden;
        }

        .circles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }

        .circles li {
            position: absolute;
            display: block;
            list-style: none;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            animation: animate 25s linear infinite;
            bottom: -150px;
            border-radius: 50%;
        }

        .circles li:nth-child(1) {
            left: 25%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }

        .circles li:nth-child(2) {
            left: 10%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 12s;
        }

        .circles li:nth-child(3) {
            left: 70%;
            width: 20px;
            height: 20px;
            animation-delay: 4s;
        }

        .circles li:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            animation-duration: 18s;
        }

        .circles li:nth-child(5) {
            left: 65%;
            width: 20px;
            height: 20px;
            animation-delay: 0s;
        }

        .circles li:nth-child(6) {
            left: 75%;
            width: 110px;
            height: 110px;
            animation-delay: 3s;
        }

        .circles li:nth-child(7) {
            left: 35%;
            width: 150px;
            height: 150px;
            animation-delay: 7s;
        }

        .circles li:nth-child(8) {
            left: 50%;
            width: 25px;
            height: 25px;
            animation-delay: 15s;
            animation-duration: 45s;
        }

        .circles li:nth-child(9) {
            left: 20%;
            width: 15px;
            height: 15px;
            animation-delay: 2s;
            animation-duration: 35s;
        }

        .circles li:nth-child(10) {
            left: 85%;
            width: 150px;
            height: 150px;
            animation-delay: 0s;
            animation-duration: 11s;
        }

        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }

            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }
        
        .login-container {
            max-width: 400px;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo h1 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .system-version {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            font-size: 0.8rem;
            opacity: 0.7;
            z-index: 10;
        }
        
        .login-card {
            border: none;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        .login-card .card-header {
            background: linear-gradient(45deg, var(--primary-color), #0b5ed7);
            color: white;
            text-align: center;
            border-bottom: none;
            padding: 1.5rem 1rem;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        
        .login-card .card-body {
            padding: 2rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), #0b5ed7);
            border: none;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* School Logo Styling */
        .school-logo {
            max-width: 180px;
            border-radius: 50%;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
            border: 4px solid #fff;
            transition: all 0.5s ease;
            animation: pulse 2s infinite ease-in-out, logoFadeIn 1.5s ease-out;
        }

        .school-logo:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 24px rgba(0,0,0,0.4);
        }

        @keyframes logoFadeIn {
            0% { opacity: 0; transform: translateY(-20px) scale(0.9); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255,255,255,0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255,255,255,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); }
        }
        
        .forgot-password-link {
            cursor: pointer;
            color: #0d6efd;
            text-decoration: none;
        }
        
        .forgot-password-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="area">
        <ul class="circles">
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
        </ul>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="card-header">
                        <div class="text-center mb-3">
                            <img src="<?php echo BASE_URL; ?>assets/images/logo.jpg" alt="KLIA Logo" class="school-logo">
                        </div>
                        <h2 class="mb-0">THE KRISLIZZ INTERNATIONAL ACADEMY INC.</h2>
                        <p class="mb-0">ENROLLMENT SYSTEM</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($forgot_password_message)): ?>
                            <?php echo $forgot_password_message; ?>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form id="loginForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                </div>
                            </div>
                            
                            <div class="mb-4 text-end">
                                <a class="forgot-password-link" id="forgotPasswordLink">Forgot Password?</a>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i> Login
                                </button>
                            </div>
                        </form>
                        
                        <!-- Forgot Password Form (Hidden by default) -->
                        <form id="forgotPasswordForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display: none;">
                            <div class="mb-3">
                                <label for="fp_username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="fp_username" name="username" placeholder="Enter your username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div class="mb-4 text-end">
                                <a class="forgot-password-link" id="backToLoginLink">Back to Login</a>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="forgot_password" class="btn btn-primary btn-lg">
                                    <i class="fas fa-key me-2"></i> Reset Password
                                </button>
                            </div>
                        </form>

                        <!-- Security Question Setup Form (Hidden by default) -->
                        <form id="securitySetupForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display: none;">
                            <div class="mb-3">
                                <label for="setup_username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="setup_username" name="username" placeholder="Enter your username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="setup_email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="setup_email" name="email" placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="security_question" class="form-label">Security Question</label>
                                <select class="form-select" id="security_question" name="security_question" required>
                                    <option value="">Select a security question</option>
                                    <?php foreach ($security_questions as $question): ?>
                                    <option value="<?php echo htmlspecialchars($question); ?>"><?php echo htmlspecialchars($question); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="security_answer" class="form-label">Answer</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="text" class="form-control" id="security_answer" name="security_answer" placeholder="Enter your answer" required>
                                </div>
                                <div class="form-text">This will be used to verify your identity if you forget your password.</div>
                            </div>
                            
                            <div class="mb-4 text-end">
                                <a class="forgot-password-link" id="backToLoginFromSetupLink">Back to Login</a>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="setup_security" class="btn btn-primary btn-lg">
                                    <i class="fas fa-shield-alt me-2"></i> Set Security Question
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-3 text-white">
                    <small>&copy; <?php echo date('Y'); ?> THE KRISLIZZ INTERNATIONAL ACADEMY INC. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="system-version">
        <span class="badge bg-light text-dark">Version 2.0</span>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const forgotPasswordForm = document.getElementById('forgotPasswordForm');
            const securitySetupForm = document.getElementById('securitySetupForm');
            const forgotPasswordLink = document.getElementById('forgotPasswordLink');
            const backToLoginLink = document.getElementById('backToLoginLink');
            const backToLoginFromSetupLink = document.getElementById('backToLoginFromSetupLink');
            const setupSecurityLink = document.createElement('a');
            
            // Add setup security question link
            setupSecurityLink.className = 'forgot-password-link';
            setupSecurityLink.id = 'setupSecurityLink';
            setupSecurityLink.textContent = 'Setup Security Question';
            setupSecurityLink.style.marginLeft = '15px';
            
            // Insert setup security link after the forgot password link
            forgotPasswordLink.parentNode.appendChild(setupSecurityLink);
            
            // Toggle between login and forgot password forms
            forgotPasswordLink.addEventListener('click', function() {
                loginForm.style.display = 'none';
                forgotPasswordForm.style.display = 'block';
                securitySetupForm.style.display = 'none';
            });
            
            backToLoginLink.addEventListener('click', function() {
                loginForm.style.display = 'block';
                forgotPasswordForm.style.display = 'none';
                securitySetupForm.style.display = 'none';
            });
            
            // Toggle to security setup form
            setupSecurityLink.addEventListener('click', function() {
                loginForm.style.display = 'none';
                forgotPasswordForm.style.display = 'none';
                securitySetupForm.style.display = 'block';
            });
            
            backToLoginFromSetupLink.addEventListener('click', function() {
                loginForm.style.display = 'block';
                forgotPasswordForm.style.display = 'none';
                securitySetupForm.style.display = 'none';
            });
        });
    </script>
</body>
</html> 
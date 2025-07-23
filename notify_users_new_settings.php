<?php
/**
 * Notify Users About New Settings
 * 
 * This script creates notifications for all users about the new settings features.
 * It inserts a notification into the database that will be shown to users on their next login.
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admin privileges required.");
}

// Start tracking results
$success = false;
$message = '';
$error = '';

// Check if the notifications table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
$table_exists = mysqli_num_rows($check_table) > 0;

// Create notifications table if it doesn't exist
if (!$table_exists) {
    $create_table_query = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        role VARCHAR(20) NULL,
        title VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(20) DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expire_at TIMESTAMP NULL,
        INDEX (user_id),
        INDEX (role),
        INDEX (is_read)
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        $error = "Failed to create notifications table: " . mysqli_error($conn);
    } else {
        $message = "Created notifications table. ";
    }
}

// Only proceed if there was no error or the table already exists
if (empty($error)) {
    // Create notification message
    $title = "Enhanced User Settings Now Available!";
    $notification_message = "We've updated the system with new personalization options! Visit your Settings page to customize:
    
    • Theme preferences (Light/Dark/System)
    • High contrast and color blind modes
    • Font size adjustments
    • Table display preferences
    • Animation controls
    • And more!
    
    Click on your profile and select 'Settings' to explore all options.";
    
    // Insert notification for all users
    $insert_query = "INSERT INTO notifications (user_id, title, message, type, expire_at) 
                    SELECT id, ?, ?, 'success', DATE_ADD(NOW(), INTERVAL 30 DAY)
                    FROM users";
    
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, "ss", $title, $notification_message);
    
    if (mysqli_stmt_execute($stmt)) {
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        $success = true;
        $message .= "Successfully created notification for $affected_rows users.";
        
        // Log the action
        $admin_id = $_SESSION['user_id'];
        logAction($admin_id, "Notification Created", "Created system-wide notification about new settings features");
    } else {
        $error = "Failed to create notifications: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}

// Display result
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notify Users - LocalEnroll Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { padding: 2rem; }
        .success-icon { color: #198754; font-size: 3rem; }
        .error-icon { color: #dc3545; font-size: 3rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-bell me-2"></i> User Notification System</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="text-center mb-4">
                                <i class="fas fa-check-circle success-icon mb-3"></i>
                                <h5>Notifications Created Successfully!</h5>
                                <p><?php echo htmlspecialchars($message); ?></p>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i> Notification Preview:</h6>
                                <div class="card mt-2">
                                    <div class="card-header bg-success text-white">
                                        <strong><?php echo htmlspecialchars($title); ?></strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($notification_message)); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <i class="fas fa-exclamation-triangle error-icon mb-3"></i>
                                <h5>Error Creating Notifications</h5>
                                <p class="text-danger"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="apply_settings_system_wide.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cogs me-1"></i> Back to Settings Update
                            </a>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-home me-1"></i> Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
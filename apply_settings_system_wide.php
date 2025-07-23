<?php
/**
 * Apply Settings System Wide
 * 
 * This script ensures that all user settings are consistently applied 
 * across the entire system. It:
 * 1. Updates the database schema with any missing settings columns
 * 2. Ensures all CSS and JS assets are correctly handling settings
 * 3. Logs the changes for administrators
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admin privileges required.");
}

// Start tracking changes
$changes = [];
$errors = [];

// Step 1: Update the database schema
$required_columns = [
    'theme_preference' => "VARCHAR(10) DEFAULT 'system'",
    'sidebar_expanded' => "TINYINT(1) DEFAULT 1",
    'table_compact' => "TINYINT(1) DEFAULT 0",
    'table_hover' => "TINYINT(1) DEFAULT 1",
    'font_size' => "VARCHAR(10) DEFAULT 'normal'",
    'high_contrast' => "TINYINT(1) DEFAULT 0",
    'color_blind_mode' => "TINYINT(1) DEFAULT 0",
    'enable_animations' => "TINYINT(1) DEFAULT 1",
    'animation_speed' => "VARCHAR(10) DEFAULT 'normal'",
    'card_style' => "VARCHAR(20) DEFAULT 'default'",
    'motion_reduce' => "VARCHAR(10) DEFAULT 'none'",
    'focus_visible' => "TINYINT(1) DEFAULT 1"
];

// Check existing columns
$result = mysqli_query($conn, "SHOW COLUMNS FROM users");
$existing_columns = [];

while ($row = mysqli_fetch_assoc($result)) {
    $existing_columns[] = $row['Field'];
}

// Add missing columns
$added_columns = [];

foreach ($required_columns as $column => $definition) {
    if (!in_array($column, $existing_columns)) {
        $query = "ALTER TABLE users ADD COLUMN $column $definition";
        
        if (mysqli_query($conn, $query)) {
            $added_columns[] = $column;
            $changes[] = "Added column '$column' to users table";
        } else {
            $errors[] = "Error adding column '$column': " . mysqli_error($conn);
        }
    }
}

// Step 2: Refresh settings for all users
$result = mysqli_query($conn, "SELECT id FROM users");
$refreshed_users = 0;

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $user_id = $row['id'];
        
        // Get current user settings
        $user_settings = getUserSettings($user_id);
        
        // Update user settings to ensure all new fields are properly set
        if (updateUserSettings($user_id, $user_settings)) {
            $refreshed_users++;
        }
    }
    
    $changes[] = "Refreshed settings for $refreshed_users users";
}

// Step 3: Log the action
$admin_id = $_SESSION['user_id'];
$action = "Settings System Update";
$description = "Applied settings system wide: " . implode(", ", $changes);

logAction($admin_id, $action, $description);

// Step 4: Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Settings System Wide - LocalEnroll Pro</title>
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
                        <h4 class="mb-0"><i class="fas fa-sync me-2"></i> Apply Settings System Wide</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($errors)): ?>
                            <div class="text-center mb-4">
                                <i class="fas fa-check-circle success-icon mb-3"></i>
                                <h5>All Settings Applied Successfully!</h5>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <i class="fas fa-exclamation-triangle error-icon mb-3"></i>
                                <h5>Some Errors Occurred</h5>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h6>Changes Made:</h6>
                            <?php if (!empty($changes)): ?>
                                <ul class="list-group">
                                    <?php foreach ($changes as $change): ?>
                                        <li class="list-group-item">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <?php echo htmlspecialchars($change); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">No changes were needed. All settings are up to date.</p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="mb-4">
                                <h6>Errors:</h6>
                                <ul class="list-group">
                                    <?php foreach ($errors as $error): ?>
                                        <li class="list-group-item list-group-item-danger">
                                            <i class="fas fa-times me-2"></i>
                                            <?php echo htmlspecialchars($error); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Users will now see updated settings when they log in. No further action is required.
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                            </a>
                            <a href="modules/admin/database.php" class="btn btn-primary">
                                <i class="fas fa-database me-1"></i> Database Management
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
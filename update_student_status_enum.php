<?php
$title = 'Update Student Status Enum';
$relative_path = './';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin role
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Check if the enrollment_status column exists and get its current definition
$check_column_query = "SHOW COLUMNS FROM students LIKE 'enrollment_status'";
$check_result = mysqli_query($conn, $check_column_query);

if (mysqli_num_rows($check_result) > 0) {
    $column_info = mysqli_fetch_assoc($check_result);
    $current_type = $column_info['Type'];
    
    // Check if the current type already includes 'irregular' and 'graduated'
    $has_irregular = strpos($current_type, 'irregular') !== false;
    $has_graduated = strpos($current_type, 'graduated') !== false;
    
    if ($has_irregular && $has_graduated) {
        $_SESSION['alert'] = showAlert('The enrollment_status column already includes "irregular" and "graduated" values.', 'info');
    } else {
        // Update the enrollment_status column to include new values
        $alter_query = "ALTER TABLE students MODIFY COLUMN enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated') DEFAULT 'pending'";
        
        if (mysqli_query($conn, $alter_query)) {
            $_SESSION['alert'] = showAlert('The enrollment_status column has been updated to include "irregular" and "graduated" values.', 'success');
            
            // Log the action
            logAction($_SESSION['user_id'], 'UPDATE', 'Updated enrollment_status enum to include irregular and graduated statuses');
        } else {
            $_SESSION['alert'] = showAlert('Error updating enrollment_status column: ' . mysqli_error($conn), 'danger');
        }
    }
} else {
    $_SESSION['alert'] = showAlert('The enrollment_status column does not exist in the students table.', 'danger');
}

// Update enrollment_history table if it exists
$check_table_query = "SHOW TABLES LIKE 'enrollment_history'";
$check_table_result = mysqli_query($conn, $check_table_query);

if (mysqli_num_rows($check_table_result) > 0) {
    // Check if the enrollment_status column exists and get its current definition
    $check_column_query = "SHOW COLUMNS FROM enrollment_history LIKE 'enrollment_status'";
    $check_result = mysqli_query($conn, $check_column_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $column_info = mysqli_fetch_assoc($check_result);
        $current_type = $column_info['Type'];
        
        // Check if the current type already includes 'irregular'
        $has_irregular = strpos($current_type, 'irregular') !== false;
        
        if (!$has_irregular) {
            // Update the enrollment_status column to include new values
            $alter_query = "ALTER TABLE enrollment_history MODIFY COLUMN enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'graduated', 'transferred', 'irregular') NOT NULL DEFAULT 'enrolled'";
            
            if (mysqli_query($conn, $alter_query)) {
                $_SESSION['alert'] = showAlert($_SESSION['alert'] . '<br>The enrollment_history table has also been updated.', 'success');
                
                // Log the action
                logAction($_SESSION['user_id'], 'UPDATE', 'Updated enrollment_history enrollment_status enum to include irregular status');
            } else {
                $_SESSION['alert'] = showAlert($_SESSION['alert'] . '<br>Error updating enrollment_history table: ' . mysqli_error($conn), 'danger');
            }
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Update Student Status Enum</h5>
                </div>
                <div class="card-body">
                    <p>This script updates the enrollment_status column in the students table to include "irregular" and "graduated" values.</p>
                    <p>Current status values now include:</p>
                    <ul>
                        <li><strong>Enrolled</strong> - Student is currently enrolled</li>
                        <li><strong>Pending</strong> - Enrollment is pending</li>
                        <li><strong>Withdrawn</strong> - Student has withdrawn</li>
                        <li><strong>Irregular</strong> - Student has irregular status</li>
                        <li><strong>Graduated</strong> - Student has graduated</li>
                    </ul>
                    
                    <div class="mt-4">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i> Return to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once $relative_path . 'includes/footer.php';
?> 
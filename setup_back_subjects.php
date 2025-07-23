<?php
// Setup script for back subjects table and irregular status
$relative_path = './';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You must be logged in as an admin to run this script.', 'danger');
    redirect('index.php');
    exit;
}

// Create the back_subjects table if it doesn't exist
$check_table_query = "SHOW TABLES LIKE 'back_subjects'";
$check_table_result = mysqli_query($conn, $check_table_query);

if (mysqli_num_rows($check_table_result) == 0) {
    // Table doesn't exist, create it
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS back_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_code VARCHAR(20) NOT NULL,
        subject_name VARCHAR(100) NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        grade DECIMAL(5,2) DEFAULT NULL,
        status ENUM('pending', 'retaking', 'passed', 'failed') NOT NULL DEFAULT 'pending',
        remarks TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        // Create indexes for better performance
        mysqli_query($conn, "CREATE INDEX idx_back_subjects_student ON back_subjects(student_id)");
        mysqli_query($conn, "CREATE INDEX idx_back_subjects_subject ON back_subjects(subject_code)");
        
        $_SESSION['alert'] = showAlert('Back subjects table created successfully.', 'success');
    } else {
        $_SESSION['alert'] = showAlert('Error creating back subjects table: ' . mysqli_error($conn), 'danger');
    }
} else {
    $_SESSION['alert'] = showAlert('Back subjects table already exists.', 'info');
}

// Check if irregular_status column exists in students table
$check_column_query = "SHOW COLUMNS FROM students LIKE 'irregular_status'";
$check_column_result = mysqli_query($conn, $check_column_query);

if (mysqli_num_rows($check_column_result) == 0) {
    // Column doesn't exist, add it
    $add_column_query = "ALTER TABLE students ADD COLUMN irregular_status BOOLEAN DEFAULT FALSE AFTER enrollment_status";
    
    if (mysqli_query($conn, $add_column_query)) {
        $_SESSION['alert'] = showAlert('Irregular status column added to students table.', 'success');
    } else {
        $_SESSION['alert'] = showAlert('Error adding irregular status column: ' . mysqli_error($conn), 'danger');
    }
} else {
    $_SESSION['alert'] = showAlert('Irregular status column already exists in students table.', 'info');
}

// Redirect back to admin dashboard
redirect('modules/admin/dashboard.php');
?> 
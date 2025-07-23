<?php
// Setup script for enrollment history table
$relative_path = './';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You must be logged in as an admin to run this script.', 'danger');
    redirect('index.php');
    exit;
}

// Create the enrollment_history table if it doesn't exist
$check_table_query = "SHOW TABLES LIKE 'enrollment_history'";
$check_table_result = mysqli_query($conn, $check_table_query);

if (mysqli_num_rows($check_table_result) == 0) {
    // Table doesn't exist, create it
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS enrollment_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        section VARCHAR(50) NOT NULL,
        strand VARCHAR(50) DEFAULT NULL,
        enrollment_date DATE NOT NULL,
        enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'graduated', 'transferred') NOT NULL DEFAULT 'enrolled',
        remarks TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        // Create indexes for better performance
        mysqli_query($conn, "CREATE INDEX idx_enrollment_history_student ON enrollment_history(student_id)");
        mysqli_query($conn, "CREATE INDEX idx_enrollment_history_school_year ON enrollment_history(school_year)");
        mysqli_query($conn, "CREATE INDEX idx_enrollment_history_semester ON enrollment_history(semester)");
        
        $_SESSION['alert'] = showAlert('Enrollment history table created successfully.', 'success');
        
        // Populate with existing enrollment data
        $populate_query = "INSERT INTO enrollment_history 
                          (student_id, school_year, semester, grade_level, section, strand, enrollment_date, enrollment_status, created_by)
                          SELECT 
                              s.id, 
                              COALESCE(shs.school_year, CONCAT(YEAR(s.date_enrolled), '-', YEAR(s.date_enrolled) + 1)), 
                              COALESCE(shs.semester, 'First'), 
                              s.grade_level, 
                              s.section, 
                              COALESCE(shs.strand, s.strand), 
                              s.date_enrolled,
                              s.enrollment_status,
                              s.enrolled_by
                          FROM 
                              students s
                          LEFT JOIN 
                              senior_highschool_details shs ON s.id = shs.student_id
                          WHERE 
                              s.enrollment_status = 'enrolled' AND s.date_enrolled IS NOT NULL";
        
        if (mysqli_query($conn, $populate_query)) {
            $rows_affected = mysqli_affected_rows($conn);
            $_SESSION['alert'] = showAlert("Enrollment history table created and populated with $rows_affected existing student records.", 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error populating enrollment history: ' . mysqli_error($conn), 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('Error creating enrollment history table: ' . mysqli_error($conn), 'danger');
    }
} else {
    $_SESSION['alert'] = showAlert('Enrollment history table already exists.', 'info');
}

// Redirect back to admin dashboard
redirect('modules/admin/dashboard.php');
?> 
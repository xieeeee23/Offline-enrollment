<?php
$title = 'Create Enrollment History Table';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}

// Check if enrollment_history table exists
$query = "SHOW TABLES LIKE 'enrollment_history'";
$result = mysqli_query($conn, $query);
$table_exists = mysqli_num_rows($result) > 0;

if (!$table_exists) {
    // Create the enrollment_history table
    $query = "CREATE TABLE enrollment_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        strand VARCHAR(50),
        section VARCHAR(50) NOT NULL,
        enrollment_status VARCHAR(20) NOT NULL,
        date_enrolled DATE NOT NULL,
        enrolled_by INT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Enrollment history table created successfully.', 'success');
        
        // Populate the enrollment_history table with existing student data
        $query = "INSERT INTO enrollment_history (student_id, school_year, semester, grade_level, strand, section, enrollment_status, date_enrolled, enrolled_by)
                  SELECT 
                      s.id, 
                      IFNULL(sh.school_year, '2025-2026') AS school_year, 
                      IFNULL(sh.semester, 'First') AS semester, 
                      s.grade_level, 
                      s.strand, 
                      s.section, 
                      s.enrollment_status, 
                      s.date_enrolled, 
                      s.enrolled_by
                  FROM students s
                  LEFT JOIN senior_highschool_details sh ON s.id = sh.student_id";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['alert'] = showAlert('Enrollment history table created and populated successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error populating enrollment history table: ' . mysqli_error($conn), 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('Error creating enrollment history table: ' . mysqli_error($conn), 'danger');
    }
} else {
    $_SESSION['alert'] = showAlert('Enrollment history table already exists.', 'info');
}

// Redirect back to students page
redirect($relative_path . 'modules/registrar/students.php');
?> 
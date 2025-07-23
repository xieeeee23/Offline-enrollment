<?php
// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('../../dashboard.php');
    exit;
}

// Function to update student status based on requirements
function updateStudentStatusBasedOnRequirements($student_id) {
    global $conn;
    
    // Get all requirements for this student
    $req_query = "SELECT * FROM student_requirements WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $req_query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $req_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($req_result) == 0) {
        // No requirements found, set status to Pending Requirements
        $new_status = 'Pending Requirements';
    } else {
        $req_row = mysqli_fetch_assoc($req_result);
        
        // Check mandatory requirements (birth certificate and report card are considered mandatory)
        $total_requirements = 0;
        $submitted_requirements = 0;
        $missing_mandatory = false;
        
        // Define mandatory requirements
        $mandatory_requirements = ['birth_certificate', 'report_card'];
        
        // Check all requirements
        foreach ($req_row as $field => $value) {
            // Skip non-requirement fields
            if (in_array($field, ['id', 'student_id', 'remarks', 'created_at', 'updated_at']) || 
                strpos($field, '_file') !== false) {
                continue;
            }
            
            $total_requirements++;
            
            if ($value == 1) {
                $submitted_requirements++;
            } elseif (in_array($field, $mandatory_requirements)) {
                $missing_mandatory = true;
            }
        }
        
        // Determine student status
        if ($missing_mandatory) {
            // Missing mandatory requirements
            $new_status = 'Pending Requirements';
        } elseif ($submitted_requirements < $total_requirements) {
            // Some non-mandatory requirements missing
            $new_status = 'Incomplete Requirements';
        } else {
            // All requirements submitted
            $new_status = 'Enrolled';
        }
    }
    
    // Update student status
    $update_query = "UPDATE students SET enrollment_status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $student_id);
    if (mysqli_stmt_execute($stmt)) {
        return true;
    }
    return false;
}

// Process POST request to update a specific student's status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update a specific student
    if (isset($_POST['student_id'])) {
        $student_id = (int) $_POST['student_id'];
        
        if (updateStudentStatusBasedOnRequirements($student_id)) {
            $_SESSION['alert'] = showAlert('Student status updated successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error updating student status.', 'danger');
        }
    }
    // Update all students
    elseif (isset($_POST['update_all']) && $_POST['update_all'] == 1) {
        // Get all students
        $students_query = "SELECT id FROM students";
        $students_result = mysqli_query($conn, $students_query);
        
        $updated_count = 0;
        $error_count = 0;
        
        while ($student = mysqli_fetch_assoc($students_result)) {
            if (updateStudentStatusBasedOnRequirements($student['id'])) {
                $updated_count++;
            } else {
                $error_count++;
            }
        }
        
        $_SESSION['alert'] = showAlert("Status update completed: $updated_count students updated successfully, $error_count errors.", 'success');
    }
    
    // Redirect back to the referring page
    if (isset($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        redirect('requirements.php');
    }
    exit;
}

// If no valid action specified, redirect to requirements page
redirect('requirements.php');
exit;
?> 
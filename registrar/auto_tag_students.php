<?php
$title = 'Auto Tag Students';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}

// Check if semester column exists in school_years table and add it if it doesn't exist
$check_semester_column = "SHOW COLUMNS FROM school_years LIKE 'semester'";
$semester_column_result = mysqli_query($conn, $check_semester_column);
if (mysqli_num_rows($semester_column_result) == 0) {
    // Add semester column to school_years table
    $add_semester_query = "ALTER TABLE school_years ADD COLUMN semester ENUM('First', 'Second', 'Summer') DEFAULT 'First' AFTER school_year";
    if (mysqli_query($conn, $add_semester_query)) {
        $_SESSION['alert'] = showAlert('Added semester column to school_years table.', 'success');
    } else {
        $_SESSION['alert'] = showAlert('Error adding semester column: ' . mysqli_error($conn), 'danger');
    }
}

// Function to update student status based on requirements
function updateStudentStatus() {
    global $conn;
    
    // Get all students
    $query = "SELECT s.id, s.first_name, s.last_name, s.lrn, s.enrollment_status 
              FROM students s 
              WHERE s.enrollment_status != 'withdrawn'";
    $result = mysqli_query($conn, $query);
    
    $updated_count = 0;
    $students_with_missing_requirements = [];
    
    while ($student = mysqli_fetch_assoc($result)) {
        $student_id = $student['id'];
        
        // Check if student has requirements record
        $req_query = "SELECT * FROM student_requirements WHERE student_id = ?";
        $req_stmt = mysqli_prepare($conn, $req_query);
        mysqli_stmt_bind_param($req_stmt, "i", $student_id);
        mysqli_stmt_execute($req_stmt);
        $req_result = mysqli_stmt_get_result($req_stmt);
        
        // Define requirement types
        $requirement_types = [
            'birth_certificate' => 'Birth Certificate',
            'report_card' => 'Report Card / Form 138',
            'good_moral' => 'Good Moral Certificate',
            'medical_certificate' => 'Medical Certificate',
            'id_picture' => '2x2 ID Picture',
            'enrollment_form' => 'Enrollment Form',
            'parent_id' => 'Parent/Guardian ID'
        ];
        
        $missing_requirements = [];
        $all_requirements_complete = true;
        
        if ($requirements = mysqli_fetch_assoc($req_result)) {
            // Check each requirement
            foreach ($requirement_types as $key => $label) {
                if (!isset($requirements[$key]) || $requirements[$key] != 1) {
                    $all_requirements_complete = false;
                    $missing_requirements[] = $label;
                }
            }
        } else {
            // No requirements record found
            $all_requirements_complete = false;
            $missing_requirements = array_values($requirement_types);
        }
        
        // Update student status based on requirements
        $new_status = $all_requirements_complete ? 'enrolled' : 'pending';
        $status_changed = false;
        
        if ($student['enrollment_status'] != $new_status) {
            $update_query = "UPDATE students SET enrollment_status = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $student_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $status_changed = true;
                $updated_count++;
                
                // Log the status change
                $log_desc = "Auto-updated student status from {$student['enrollment_status']} to {$new_status} for student: {$student['first_name']} {$student['last_name']} (LRN: {$student['lrn']})";
                logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
                
                // Record in enrollment history
                $school_year_query = "SELECT school_year FROM school_years WHERE is_current = 1 LIMIT 1";
                $school_year_result = mysqli_query($conn, $school_year_query);
                $school_year = '2025-2026'; // Default if not found
                
                if (mysqli_num_rows($school_year_result) > 0) {
                    $school_year_row = mysqli_fetch_assoc($school_year_result);
                    $school_year = $school_year_row['school_year'];
                }
                
                // Get student details
                $student_query = "SELECT grade_level, strand, section FROM students WHERE id = ?";
                $student_stmt = mysqli_prepare($conn, $student_query);
                mysqli_stmt_bind_param($student_stmt, "i", $student_id);
                mysqli_stmt_execute($student_stmt);
                $student_result = mysqli_stmt_get_result($student_stmt);
                $student_details = mysqli_fetch_assoc($student_result);
                
                // Get current semester - check if column exists first
                $semester = 'First'; // Default if not found
                $check_semester_column = "SHOW COLUMNS FROM school_years LIKE 'semester'";
                $semester_column_result = mysqli_query($conn, $check_semester_column);
                
                if (mysqli_num_rows($semester_column_result) > 0) {
                    $semester_query = "SELECT semester FROM school_years WHERE is_current = 1 LIMIT 1";
                    $semester_result = mysqli_query($conn, $semester_query);
                    
                    if ($semester_result && mysqli_num_rows($semester_result) > 0) {
                        $semester_row = mysqli_fetch_assoc($semester_result);
                        if (!empty($semester_row['semester'])) {
                            $semester = $semester_row['semester'];
                        }
                    }
                }
                
                // Check if enrollment_history table exists
                $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'enrollment_history'");
                if (mysqli_num_rows($table_check) > 0) {
                    $notes = "Auto-updated status from {$student['enrollment_status']} to {$new_status} based on requirements";
                    
                    $enrollment_history_data = [
                        'student_id' => $student_id,
                        'school_year' => $school_year,
                        'semester' => $semester,
                        'grade_level' => $student_details['grade_level'],
                        'strand' => $student_details['strand'],
                        'section' => $student_details['section'],
                        'enrollment_status' => $new_status,
                        'date_enrolled' => date('Y-m-d'),
                        'enrolled_by' => $_SESSION['user_id'],
                        'notes' => $notes
                    ];
                    
                    // Insert enrollment history
                    $history_result = safeInsert('enrollment_history', $enrollment_history_data, [
                        'entity_name' => 'enrollment history',
                        'log_action' => true
                    ]);
                }
            }
        }
        
        // Store students with missing requirements for display
        if (!$all_requirements_complete) {
            $students_with_missing_requirements[] = [
                'id' => $student_id,
                'name' => $student['first_name'] . ' ' . $student['last_name'],
                'lrn' => $student['lrn'],
                'status' => $new_status,
                'status_changed' => $status_changed,
                'missing_requirements' => $missing_requirements
            ];
        }
    }
    
    return [
        'updated_count' => $updated_count,
        'students_with_missing_requirements' => $students_with_missing_requirements
    ];
}

// Process auto-tagging
$result = updateStudentStatus();
$updated_count = $result['updated_count'];
$students_with_missing_requirements = $result['students_with_missing_requirements'];

// Show success message
if ($updated_count > 0) {
    $_SESSION['alert'] = showAlert("Successfully updated {$updated_count} student(s) status based on requirements.", 'success');
} else {
    $_SESSION['alert'] = showAlert("No student status updates needed.", 'info');
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Auto Tag Students</h1>
        <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-sm btn-primary">
            <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Students
        </a>
    </div>

    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Students with Missing Requirements</h6>
        </div>
        <div class="card-body">
            <?php if (count($students_with_missing_requirements) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="missingRequirementsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>LRN</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Missing Requirements</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_with_missing_requirements as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $student['status'] === 'enrolled' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                        <?php if ($student['status_changed']): ?>
                                            <span class="badge bg-info">Updated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <ul class="mb-0">
                                            <?php foreach ($student['missing_requirements'] as $requirement): ?>
                                                <li><?php echo htmlspecialchars($requirement); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <a href="<?php echo $relative_path; ?>modules/registrar/requirements.php?student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-clipboard-list me-1"></i> Manage Requirements
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i> All students have complete requirements.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Schedule Automatic Tagging</h6>
        </div>
        <div class="card-body">
            <p>You can set up automatic tagging to run on a schedule using a cron job. This will automatically update student statuses based on their requirements.</p>
            
            <h5>Sample Cron Job Command</h5>
            <div class="bg-light p-3 mb-3">
                <code>0 0 * * * php <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/modules/registrar/cron_auto_tag_students.php</code>
            </div>
            
            <p>This will run the auto-tagging process once a day at midnight. You can adjust the schedule as needed.</p>
            
            <div class="mt-3">
                <a href="<?php echo $relative_path; ?>modules/registrar/create_auto_tag_cron.php" class="btn btn-primary">
                    <i class="fas fa-clock me-1"></i> Create Cron Script
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#missingRequirementsTable').DataTable({
        responsive: true
    });
});
</script> 
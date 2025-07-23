<?php
$title = 'Create Auto Tag Cron Script';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}

// Create cron script
$cron_script_content = '<?php
/**
 * Cron script for automatically tagging students based on requirements status
 * This script is designed to be run via a cron job
 */

// Set absolute path to the system
$absolute_path = "' . $_SERVER['DOCUMENT_ROOT'] . '";
chdir($absolute_path);

// Include necessary files
require_once "includes/config.php";
require_once "includes/functions.php";

// Function to update student status based on requirements
function updateStudentStatus() {
    global $conn;
    
    // Get all students
    $query = "SELECT s.id, s.first_name, s.last_name, s.lrn, s.enrollment_status 
              FROM students s 
              WHERE s.enrollment_status != \'withdrawn\'";
    $result = mysqli_query($conn, $query);
    
    $updated_count = 0;
    
    while ($student = mysqli_fetch_assoc($result)) {
        $student_id = $student[\'id\'];
        
        // Check if student has requirements record
        $req_query = "SELECT * FROM student_requirements WHERE student_id = ?";
        $req_stmt = mysqli_prepare($conn, $req_query);
        mysqli_stmt_bind_param($req_stmt, "i", $student_id);
        mysqli_stmt_execute($req_stmt);
        $req_result = mysqli_stmt_get_result($req_stmt);
        
        // Define requirement types
        $requirement_types = [
            \'birth_certificate\' => \'Birth Certificate\',
            \'report_card\' => \'Report Card / Form 138\',
            \'good_moral\' => \'Good Moral Certificate\',
            \'medical_certificate\' => \'Medical Certificate\',
            \'id_picture\' => \'2x2 ID Picture\',
            \'enrollment_form\' => \'Enrollment Form\',
            \'parent_id\' => \'Parent/Guardian ID\'
        ];
        
        $all_requirements_complete = true;
        
        if ($requirements = mysqli_fetch_assoc($req_result)) {
            // Check each requirement
            foreach ($requirement_types as $key => $label) {
                if (!isset($requirements[$key]) || $requirements[$key] != 1) {
                    $all_requirements_complete = false;
                    break;
                }
            }
        } else {
            // No requirements record found
            $all_requirements_complete = false;
        }
        
        // Update student status based on requirements
        $new_status = $all_requirements_complete ? \'enrolled\' : \'pending\';
        
        if ($student[\'enrollment_status\'] != $new_status) {
            $update_query = "UPDATE students SET enrollment_status = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "si", $new_status, $student_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $updated_count++;
                
                // Log the status change
                $log_desc = "Auto-updated student status from {$student[\'enrollment_status\']} to {$new_status} for student: {$student[\'first_name\']} {$student[\'last_name\']} (LRN: {$student[\'lrn\']})";
                logAction(1, \'UPDATE\', $log_desc); // Using user_id 1 (admin) for cron actions
                
                // Record in enrollment history
                $school_year_query = "SELECT school_year FROM school_years WHERE is_current = 1 LIMIT 1";
                $school_year_result = mysqli_query($conn, $school_year_query);
                $school_year = \'2025-2026\'; // Default if not found
                
                if (mysqli_num_rows($school_year_result) > 0) {
                    $school_year_row = mysqli_fetch_assoc($school_year_result);
                    $school_year = $school_year_row[\'school_year\'];
                }
                
                // Get student details
                $student_query = "SELECT grade_level, strand, section FROM students WHERE id = ?";
                $student_stmt = mysqli_prepare($conn, $student_query);
                mysqli_stmt_bind_param($student_stmt, "i", $student_id);
                mysqli_stmt_execute($student_stmt);
                $student_result = mysqli_stmt_get_result($student_stmt);
                $student_details = mysqli_fetch_assoc($student_result);
                
                // Get current semester
                $semester_query = "SELECT semester FROM school_years WHERE is_current = 1 LIMIT 1";
                $semester_result = mysqli_query($conn, $semester_query);
                $semester = \'First\'; // Default if not found
                
                if (mysqli_num_rows($semester_result) > 0) {
                    $semester_row = mysqli_fetch_assoc($semester_result);
                    if (!empty($semester_row[\'semester\'])) {
                        $semester = $semester_row[\'semester\'];
                    }
                }
                
                // Check if enrollment_history table exists
                $table_check = mysqli_query($conn, "SHOW TABLES LIKE \'enrollment_history\'");
                if (mysqli_num_rows($table_check) > 0) {
                    $notes = "Auto-updated status from {$student[\'enrollment_status\']} to {$new_status} based on requirements";
                    
                    $enrollment_history_data = [
                        \'student_id\' => $student_id,
                        \'school_year\' => $school_year,
                        \'semester\' => $semester,
                        \'grade_level\' => $student_details[\'grade_level\'],
                        \'strand\' => $student_details[\'strand\'],
                        \'section\' => $student_details[\'section\'],
                        \'enrollment_status\' => $new_status,
                        \'date_enrolled\' => date(\'Y-m-d\'),
                        \'enrolled_by\' => 1, // Using user_id 1 (admin) for cron actions
                        \'notes\' => $notes
                    ];
                    
                    // Insert enrollment history
                    $history_result = safeInsert(\'enrollment_history\', $enrollment_history_data, [
                        \'entity_name\' => \'enrollment history\',
                        \'log_action\' => true
                    ]);
                }
            }
        }
    }
    
    return $updated_count;
}

// Run the update function
$updated_count = updateStudentStatus();

// Log the result
$log_message = "Cron job: Auto-tagged students completed. Updated $updated_count student(s) status.";
logAction(1, \'SYSTEM\', $log_message);

// Output result (will be captured in the cron job log)
echo date(\'Y-m-d H:i:s\') . " - $log_message\\n";
';

// Create the cron script file
$cron_script_path = $relative_path . 'modules/registrar/cron_auto_tag_students.php';
$file_created = file_put_contents($cron_script_path, $cron_script_content);

if ($file_created !== false) {
    $_SESSION['alert'] = showAlert('Cron script created successfully at: ' . $cron_script_path, 'success');
} else {
    $_SESSION['alert'] = showAlert('Error creating cron script. Please check file permissions.', 'danger');
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Create Auto Tag Cron Script</h1>
        <a href="<?php echo $relative_path; ?>modules/registrar/auto_tag_students.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Auto Tag
        </a>
    </div>

    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Cron Script Setup Instructions</h6>
        </div>
        <div class="card-body">
            <p>The cron script has been created at:</p>
            <div class="bg-light p-3 mb-3">
                <code><?php echo $cron_script_path; ?></code>
            </div>
            
            <h5>How to Set Up the Cron Job</h5>
            <ol>
                <li>Access your server's cron job manager (crontab)</li>
                <li>Add the following line to run the script daily at midnight:</li>
            </ol>
            
            <div class="bg-light p-3 mb-3">
                <code>0 0 * * * php <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/modules/registrar/cron_auto_tag_students.php</code>
            </div>
            
            <h5>Cron Schedule Explanation</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Minute</td>
                        <td>0</td>
                        <td>Run at minute 0 (top of the hour)</td>
                    </tr>
                    <tr>
                        <td>Hour</td>
                        <td>0</td>
                        <td>Run at hour 0 (midnight)</td>
                    </tr>
                    <tr>
                        <td>Day of Month</td>
                        <td>*</td>
                        <td>Run every day of the month</td>
                    </tr>
                    <tr>
                        <td>Month</td>
                        <td>*</td>
                        <td>Run every month</td>
                    </tr>
                    <tr>
                        <td>Day of Week</td>
                        <td>*</td>
                        <td>Run every day of the week</td>
                    </tr>
                </tbody>
            </table>
            
            <h5>Other Common Cron Schedule Examples</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Schedule</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>0 0 * * *</code></td>
                        <td>Run once a day at midnight</td>
                    </tr>
                    <tr>
                        <td><code>0 */6 * * *</code></td>
                        <td>Run every 6 hours</td>
                    </tr>
                    <tr>
                        <td><code>0 8 * * 1-5</code></td>
                        <td>Run at 8 AM on weekdays (Monday to Friday)</td>
                    </tr>
                    <tr>
                        <td><code>0 0 1 * *</code></td>
                        <td>Run once a month (1st day of the month at midnight)</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i> If you don't have access to set up cron jobs, please contact your server administrator.
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
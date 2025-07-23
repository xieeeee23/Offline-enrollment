<?php
$title = 'Manage Back Subjects';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] == 1;

// Debug function
function debug_log($message, $data = null) {
    global $debug;
    if ($debug) {
        echo '<div class="alert alert-info">';
        echo '<strong>' . $message . ':</strong> ';
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                echo '<pre>' . print_r($data, true) . '</pre>';
            } else {
                echo $data;
            }
        }
        echo '</div>';
    }
}

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = showAlert('Student ID is required.', 'danger');
    redirect('modules/registrar/students.php');
}

$student_id = (int) $_GET['id'];

// Get student data
$query = "SELECT * FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = showAlert('Student not found.', 'danger');
    redirect('modules/registrar/students.php');
}

$student = mysqli_fetch_assoc($result);

// Check if back_subjects table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'back_subjects'");
$table_exists = mysqli_num_rows($table_check) > 0;
debug_log('back_subjects table exists', $table_exists ? 'Yes' : 'No');

if (!$table_exists) {
    // Create the back_subjects table
    $create_table_query = "CREATE TABLE back_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_code VARCHAR(20) NOT NULL,
        subject_name VARCHAR(100) NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        $_SESSION['alert'] = showAlert('Error creating back subjects table: ' . mysqli_error($conn), 'danger');
    }
} else {
    // Check if the remarks column exists, if not add it
    $check_remarks = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'remarks'");
    $has_remarks = mysqli_num_rows($check_remarks) > 0;
    debug_log('back_subjects has remarks column', $has_remarks ? 'Yes' : 'No');
    
    if (mysqli_num_rows($check_remarks) === 0) {
        // Add remarks column
        $add_remarks_query = "ALTER TABLE back_subjects ADD COLUMN remarks TEXT AFTER status";
        if (!mysqli_query($conn, $add_remarks_query)) {
            $_SESSION['alert'] = showAlert('Error adding remarks column: ' . mysqli_error($conn), 'warning');
        } else {
            error_log('Added remarks column to back_subjects table');
            debug_log('Added remarks column to back_subjects table');
        }
    }
    
    // Check if the grade_level column exists, if not add it
    $check_grade_level = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'grade_level'");
    $has_grade_level = mysqli_num_rows($check_grade_level) > 0;
    debug_log('back_subjects has grade_level column', $has_grade_level ? 'Yes' : 'No');
    
    if (mysqli_num_rows($check_grade_level) === 0) {
        // Add grade_level column
        $add_grade_level_query = "ALTER TABLE back_subjects ADD COLUMN grade_level VARCHAR(20) AFTER semester";
        if (!mysqli_query($conn, $add_grade_level_query)) {
            $_SESSION['alert'] = showAlert('Error adding grade_level column: ' . mysqli_error($conn), 'warning');
        } else {
            error_log('Added grade_level column to back_subjects table');
            debug_log('Added grade_level column to back_subjects table');
        }
    }
    
    // Show the complete table structure for debugging
    if ($debug) {
        $table_structure_query = "SHOW COLUMNS FROM back_subjects";
        $table_structure_result = mysqli_query($conn, $table_structure_query);
        $table_structure = [];
        while ($column = mysqli_fetch_assoc($table_structure_result)) {
            $table_structure[] = $column;
        }
        debug_log('back_subjects table structure', $table_structure);
    }
}

// Process form submission for adding new back subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_back_subject'])) {
    $subject_code = cleanInput($_POST['subject_code'], 'uppercase');
    $subject_name = cleanInput($_POST['subject_name'], 'text');
    $school_year = cleanInput($_POST['school_year'], 'text');
    $semester = cleanInput($_POST['semester'], 'text');
    $grade_level = cleanInput($_POST['grade_level'], 'text');
    $remarks = cleanInput($_POST['remarks'], 'text');
    
    // Validation
    $errors = [];
    
    if (empty($subject_code)) {
        $errors[] = 'Subject code is required';
    }
    
    if (empty($subject_name)) {
        $errors[] = 'Subject name is required';
    }
    
    if (empty($school_year)) {
        $errors[] = 'School year is required';
    }
    
    if (empty($semester)) {
        $errors[] = 'Semester is required';
    }
    
    if (empty($grade_level)) {
        $errors[] = 'Grade level is required';
    }
    
    // If no errors, insert back subject
    if (empty($errors)) {
        // Check if the back_subjects table has subject_code column
        $check_subject_code = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'subject_code'");
        $has_subject_code = mysqli_num_rows($check_subject_code) > 0;
        
        // Check if the back_subjects table has subject_name column
        $check_subject_name = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'subject_name'");
        $has_subject_name = mysqli_num_rows($check_subject_name) > 0;
        
        // Check if the back_subjects table has subject_id column
        $check_subject_id = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'subject_id'");
        $has_subject_id = mysqli_num_rows($check_subject_id) > 0;
        
        // Check if the back_subjects table has remarks column
        $check_remarks = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'remarks'");
        $has_remarks = mysqli_num_rows($check_remarks) > 0;
        
        // Check if the back_subjects table has grade_level column
        $check_grade_level = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'grade_level'");
        $has_grade_level = mysqli_num_rows($check_grade_level) > 0;
        
        if ($has_subject_code && $has_subject_name) {
            // Table has both subject_code and subject_name columns
            if ($has_remarks && $has_grade_level) {
        $query = "INSERT INTO back_subjects (student_id, subject_code, subject_name, school_year, semester, grade_level, remarks)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issssss", $student_id, $subject_code, $subject_name, $school_year, $semester, $grade_level, $remarks);
            } elseif ($has_grade_level) {
                $query = "INSERT INTO back_subjects (student_id, subject_code, subject_name, school_year, semester, grade_level)
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "isssss", $student_id, $subject_code, $subject_name, $school_year, $semester, $grade_level);
            } elseif ($has_remarks) {
                $query = "INSERT INTO back_subjects (student_id, subject_code, subject_name, school_year, semester, remarks)
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "isssss", $student_id, $subject_code, $subject_name, $school_year, $semester, $remarks);
            } else {
                $query = "INSERT INTO back_subjects (student_id, subject_code, subject_name, school_year, semester)
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "issss", $student_id, $subject_code, $subject_name, $school_year, $semester);
            }
        } elseif ($has_subject_id) {
            // Table has subject_id column
            // Get subject ID from subjects table or use a default value
            $subject_id = 0;
            $subject_query = "SELECT id FROM subjects WHERE code = ? OR name = ? LIMIT 1";
            $subject_stmt = mysqli_prepare($conn, $subject_query);
            mysqli_stmt_bind_param($subject_stmt, "ss", $subject_code, $subject_name);
            mysqli_stmt_execute($subject_stmt);
            $subject_result = mysqli_stmt_get_result($subject_stmt);
            
            if ($subject_row = mysqli_fetch_assoc($subject_result)) {
                $subject_id = $subject_row['id'];
            }
            
            if ($has_remarks && $has_grade_level) {
                $query = "INSERT INTO back_subjects (student_id, subject_id, school_year, semester, grade_level, status, remarks)
                          VALUES (?, ?, ?, ?, ?, 'pending', ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iissss", $student_id, $subject_id, $school_year, $semester, $grade_level, $remarks);
            } elseif ($has_grade_level) {
                $query = "INSERT INTO back_subjects (student_id, subject_id, school_year, semester, grade_level, status)
                          VALUES (?, ?, ?, ?, ?, 'pending')";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iisss", $student_id, $subject_id, $school_year, $semester, $grade_level);
            } elseif ($has_remarks) {
                $query = "INSERT INTO back_subjects (student_id, subject_id, school_year, semester, status, remarks)
                          VALUES (?, ?, ?, ?, 'pending', ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iisss", $student_id, $subject_id, $school_year, $semester, $remarks);
            } else {
                $query = "INSERT INTO back_subjects (student_id, subject_id, school_year, semester, status)
                          VALUES (?, ?, ?, ?, 'pending')";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iiss", $student_id, $subject_id, $school_year, $semester);
            }
        } else {
            // Unknown table structure, show error
            $_SESSION['alert'] = showAlert('Error: The back_subjects table structure is not recognized.', 'danger');
            return;
        }
        
        if (mysqli_stmt_execute($stmt)) {
            // Log action
            $log_desc = "Added back subject for student: {$student['first_name']} {$student['last_name']} (LRN: {$student['lrn']})";
            logAction($_SESSION['user_id'], 'CREATE', $log_desc);
            
            $_SESSION['alert'] = showAlert('Back subject added successfully.', 'success');
            // Redirect with submitted parameter to prevent form resubmission
            header("Location: back_subjects.php?id={$student_id}&submitted=1");
            exit;
        } else {
            $_SESSION['alert'] = showAlert('Error adding back subject: ' . mysqli_error($conn), 'danger');
        }
    } else {
        // Display errors
        $error_list = '<ul>';
        foreach ($errors as $error) {
            $error_list .= '<li>' . $error . '</li>';
        }
        $error_list .= '</ul>';
        $_SESSION['alert'] = showAlert('Please fix the following errors:' . $error_list, 'danger');
    }
}

// Process form submission for updating back subject status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $back_subject_id = (int) $_POST['back_subject_id'];
    $status = cleanInput($_POST['status'], 'text');
    $remarks = cleanInput($_POST['remarks'], 'text');
    
    // Check if the back_subjects table has remarks column
    $check_remarks = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'remarks'");
    $has_remarks = mysqli_num_rows($check_remarks) > 0;
    
    if ($has_remarks) {
    $query = "UPDATE back_subjects SET status = ?, remarks = ? WHERE id = ? AND student_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssii", $status, $remarks, $back_subject_id, $student_id);
    } else {
        $query = "UPDATE back_subjects SET status = ? WHERE id = ? AND student_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sii", $status, $back_subject_id, $student_id);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        // Log action
        $log_desc = "Updated back subject status for student: {$student['first_name']} {$student['last_name']} (LRN: {$student['lrn']})";
        logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
        
        $_SESSION['alert'] = showAlert('Back subject status updated successfully.', 'success');
        // Redirect with submitted parameter to prevent form resubmission
        header("Location: back_subjects.php?id={$student_id}&submitted=1");
        exit;
    } else {
        $_SESSION['alert'] = showAlert('Error updating back subject status: ' . mysqli_error($conn), 'danger');
    }
}

// Process form submission for deleting back subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_back_subject'])) {
    $back_subject_id = (int) $_POST['back_subject_id'];
    
    $query = "DELETE FROM back_subjects WHERE id = ? AND student_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $back_subject_id, $student_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Log action
        $log_desc = "Deleted back subject for student: {$student['first_name']} {$student['last_name']} (LRN: {$student['lrn']})";
        logAction($_SESSION['user_id'], 'DELETE', $log_desc);
        
        $_SESSION['alert'] = showAlert('Back subject deleted successfully.', 'success');
        // Redirect with submitted parameter to prevent form resubmission
        header("Location: back_subjects.php?id={$student_id}&submitted=1");
        exit;
    } else {
        $_SESSION['alert'] = showAlert('Error deleting back subject: ' . mysqli_error($conn), 'danger');
    }
}

// Get back subjects for the student
$query = "SELECT * FROM back_subjects WHERE student_id = ? ORDER BY school_year DESC, semester DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$back_subjects_result = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Back Subjects</h1>
        <div>
            <?php if (checkAccess(['admin'])): ?>
            <?php endif; ?>
            <a href="<?php echo $relative_path; ?>modules/registrar/view_student.php?id=<?php echo $student_id; ?>" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Back to Student Details
            </a>
            <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-sm btn-primary">
                <i class="fas fa-users fa-sm text-white-50 me-1"></i> All Students
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary text-white">Student Information</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 text-center mb-3">
                    <?php if (!empty($student['photo'])): ?>
                        <img src="<?php echo $relative_path . htmlspecialchars($student['photo']); ?>" 
                             alt="Student Photo" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                    <?php else: ?>
                        <img src="<?php echo $relative_path; ?>assets/images/default-user.png" 
                             alt="Default Photo" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                    <?php endif; ?>
                </div>
                <div class="col-md-10">
                    <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?></h4>
                    <p class="mb-1"><strong>LRN:</strong> <?php echo htmlspecialchars($student['lrn']); ?></p>
                    <p class="mb-1"><strong>Current Grade Level:</strong> <?php echo htmlspecialchars($student['grade_level']); ?></p>
                    <p class="mb-1"><strong>Current Section:</strong> <?php echo htmlspecialchars($student['section']); ?></p>
                    <p class="mb-1"><strong>Current Strand:</strong> <?php echo htmlspecialchars($student['strand']); ?></p>
                    <p class="mb-1">
                        <strong>Current Status:</strong> 
                        <span class="badge <?php 
                            switch($student['enrollment_status']) {
                                case 'enrolled': echo 'bg-success'; break;
                                case 'pending': echo 'bg-warning text-dark'; break;
                                case 'withdrawn': echo 'bg-danger'; break;
                                default: echo 'bg-secondary';
                            }
                        ?>">
                            <?php echo ucfirst($student['enrollment_status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary text-white">Add New Back Subject</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $student_id; ?>">
                        <div class="mb-3">
                            <label for="subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject_code" name="subject_code" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject_name" name="subject_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="school_year" class="form-label">School Year <span class="text-danger">*</span></label>
                            <select class="form-control" id="school_year" name="school_year" required>
                                <option value="">Select School Year</option>
                                <?php
                                // Get all school years
                                $query = "SELECT school_year FROM school_years ORDER BY school_year DESC";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<option value='" . htmlspecialchars($row['school_year']) . "'>" . htmlspecialchars($row['school_year']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="First">First Semester</option>
                                <option value="Second">Second Semester</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="grade_level" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="grade_level" name="grade_level" required>
                                <option value="">Select Grade Level</option>
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="add_back_subject" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Add Back Subject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary text-white">Back Subjects List</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="printBackSubjects()">
                        <i class="fas fa-print fa-sm me-1"></i> Print List
                    </button>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($back_subjects_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="backSubjectsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>School Year</th>
                                        <th>Semester</th>
                                        <th>Grade Level</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Check table structure once
                                    $has_subject_code = false;
                                    $has_subject_name = false;
                                    $has_subject_id = false;
                                    
                                    $check_subject_code = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'subject_code'");
                                    $has_subject_code = mysqli_num_rows($check_subject_code) > 0;
                                    
                                    $check_subject_name = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'subject_name'");
                                    $has_subject_name = mysqli_num_rows($check_subject_name) > 0;
                                    
                                    $check_subject_id = mysqli_query($conn, "SHOW COLUMNS FROM back_subjects LIKE 'subject_id'");
                                    $has_subject_id = mysqli_num_rows($check_subject_id) > 0;
                                    
                                    while ($back_subject = mysqli_fetch_assoc($back_subjects_result)): 
                                        // Get subject info if using subject_id
                                        $subject_code_display = '';
                                        $subject_name_display = '';
                                        
                                        if ($has_subject_code && isset($back_subject['subject_code'])) {
                                            $subject_code_display = $back_subject['subject_code'];
                                        }
                                        
                                        if ($has_subject_name && isset($back_subject['subject_name'])) {
                                            $subject_name_display = $back_subject['subject_name'];
                                        }
                                        
                                        if ($has_subject_id && isset($back_subject['subject_id'])) {
                                            // Try to get subject info from subjects table
                                            $subject_query = "SELECT code as subject_code, name as subject_name FROM subjects WHERE id = ?";
                                            $subject_stmt = mysqli_prepare($conn, $subject_query);
                                            mysqli_stmt_bind_param($subject_stmt, "i", $back_subject['subject_id']);
                                            mysqli_stmt_execute($subject_stmt);
                                            $subject_result = mysqli_stmt_get_result($subject_stmt);
                                            
                                            if ($subject_row = mysqli_fetch_assoc($subject_result)) {
                                                $subject_code_display = $subject_row['subject_code'] ?? 'N/A';
                                                $subject_name_display = $subject_row['subject_name'] ?? 'N/A';
                                            } else {
                                                $subject_code_display = "ID: " . $back_subject['subject_id'];
                                                $subject_name_display = "Unknown Subject";
                                            }
                                            
                                            mysqli_stmt_close($subject_stmt);
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject_code_display); ?></td>
                                            <td><?php echo htmlspecialchars($subject_name_display); ?></td>
                                            <td><?php echo htmlspecialchars($back_subject['school_year']); ?></td>
                                            <td><?php echo htmlspecialchars($back_subject['semester']); ?></td>
                                            <td><?php echo htmlspecialchars($back_subject['grade_level'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    switch($back_subject['status']) {
                                                        case 'completed': echo 'bg-success'; break;
                                                        case 'pending': echo 'bg-warning text-dark'; break;
                                                        case 'failed': echo 'bg-danger'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                ?>">
                                                    <?php echo ucfirst($back_subject['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($back_subject['remarks'] ?? ''); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary edit-status" data-id="<?php echo $back_subject['id']; ?>" data-status="<?php echo $back_subject['status']; ?>" data-remarks="<?php echo htmlspecialchars($back_subject['remarks'] ?? ''); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-subject" data-id="<?php echo $back_subject['id']; ?>" data-name="<?php echo htmlspecialchars($subject_name_display); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No back subjects found for this student.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Status Modal -->
<div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStatusModalLabel">Update Back Subject Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="back_subjects.php?id=<?php echo $student_id; ?>">
                <div class="modal-body">
                    <input type="hidden" name="back_subject_id" id="edit_back_subject_id">
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-control" id="edit_status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="edit_remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Subject Modal -->
<div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSubjectModalLabel">Delete Back Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="back_subjects.php?id=<?php echo $student_id; ?>">
                <div class="modal-body">
                    <input type="hidden" name="back_subject_id" id="delete_back_subject_id">
                    <p>Are you sure you want to delete this back subject? This action cannot be undone.</p>
                    <p><strong>Subject: </strong><span id="delete_subject_name"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_back_subject" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#backSubjectsTable').DataTable({
        responsive: true
    });
    
    // Edit status modal
    $('.edit-status').click(function() {
        const id = $(this).data('id');
        const status = $(this).data('status');
        const remarks = $(this).data('remarks');
        
        $('#edit_back_subject_id').val(id);
        $('#edit_status').val(status);
        $('#edit_remarks').val(remarks);
        
        $('#editStatusModal').modal('show');
    });
    
    // Delete subject modal
    $('.delete-subject').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        $('#delete_back_subject_id').val(id);
        $('#delete_subject_name').text(name);
        
        $('#deleteSubjectModal').modal('show');
    });
    
    // Prevent form resubmission when page is refreshed
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Close modals on form submission
    $('form').on('submit', function() {
        $('.modal').modal('hide');
    });
    
    // Check for URL parameter to prevent duplicate submissions
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('submitted')) {
        // Remove the parameter to prevent issues on refresh
        const newUrl = window.location.pathname + '?id=' + urlParams.get('id');
        window.history.replaceState({}, document.title, newUrl);
    }
});

function printBackSubjects() {
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Write the print content
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Back Subjects - ${<?php echo json_encode($student['first_name'] . ' ' . $student['last_name']); ?>}</title>
            <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/vendor/bootstrap/css/bootstrap.min.css">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .header { text-align: center; margin-bottom: 20px; }
                .school-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
                .page-title { font-size: 16px; margin-bottom: 15px; }
                .student-info { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; }
                @media print {
                    .no-print { display: none; }
                    button { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="school-name">THE KRISLIZZ INTERNATIONAL ACADEMY INC.</div>
                <div class="page-title">STUDENT BACK SUBJECTS</div>
            </div>
            
            <div class="student-info">
                <p><strong>Student Name:</strong> ${<?php echo json_encode($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?>}</p>
                <p><strong>LRN:</strong> ${<?php echo json_encode($student['lrn']); ?>}</p>
                <p><strong>Grade Level:</strong> ${<?php echo json_encode($student['grade_level']); ?>}</p>
                <p><strong>Section:</strong> ${<?php echo json_encode($student['section']); ?>}</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>School Year</th>
                        <th>Semester</th>
                        <th>Grade Level</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    ${document.querySelectorAll('#backSubjectsTable tbody tr').length > 0 ? 
                      Array.from(document.querySelectorAll('#backSubjectsTable tbody tr')).map(row => {
                        const cells = Array.from(row.cells);
                        return `<tr>
                            <td>${cells[0].textContent}</td>
                            <td>${cells[1].textContent}</td>
                            <td>${cells[2].textContent}</td>
                            <td>${cells[3].textContent}</td>
                            <td>${cells[4].textContent}</td>
                            <td>${cells[5].textContent}</td>
                            <td>${cells[6].textContent}</td>
                        </tr>`;
                      }).join('') : 
                      '<tr><td colspan="7" class="text-center">No back subjects found</td></tr>'
                    }
                </tbody>
            </table>
            
            <div class="footer">
                <p>Printed on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
                <p>Generated by: <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            </div>
            
            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button onclick="window.print();" class="btn btn-primary">Print</button>
                <button onclick="window.close();" class="btn btn-secondary">Close</button>
            </div>
        </body>
        </html>
    `);
    
    // Finish writing and focus the window
    printWindow.document.close();
    printWindow.focus();
}
</script> 
<?php
$title = 'Auto Assign Sections';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}

// Process auto assignment
$assigned_count = 0;
$error_count = 0;
$log_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_assign'])) {
    $grade_level = cleanInput($_POST['grade_level'] ?? '');
    $school_year = cleanInput($_POST['school_year'] ?? '');
    $semester = cleanInput($_POST['semester'] ?? '');
    
    if (empty($grade_level) || empty($school_year) || empty($semester)) {
        $_SESSION['alert'] = showAlert('Please select grade level, school year, and semester.', 'danger');
    } else {
        // Get students without sections or with pending enrollment status
        $query = "SELECT s.id, s.first_name, s.last_name, s.grade_level, s.enrollment_status, 
                        shsd.strand
                  FROM students s
                  LEFT JOIN senior_highschool_details shsd ON s.id = shsd.student_id
                  WHERE s.grade_level = ? 
                  AND ((s.section = '' OR s.section IS NULL) OR s.enrollment_status = 'pending')
                  AND shsd.school_year = ? 
                  AND shsd.semester = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sss", $grade_level, $school_year, $semester);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $students = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }
        
        if (count($students) === 0) {
            $_SESSION['alert'] = showAlert('No students found that need section assignment.', 'info');
        } else {
            // Get available sections for the grade level
            $query = "SELECT id, name, strand, max_students, 
                      (SELECT COUNT(*) FROM students WHERE section = sections.name AND grade_level = sections.grade_level) as current_count
                      FROM sections 
                      WHERE grade_level = ? 
                      AND school_year = ? 
                      AND semester = ? 
                      AND status = 'Active'";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sss", $grade_level, $school_year, $semester);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $sections = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $sections[$row['strand']][] = $row;
            }
            
            // Assign students to sections
            foreach ($students as $student) {
                $strand = $student['strand'];
                
                // Skip if student has no strand
                if (empty($strand)) {
                    $log_messages[] = "Student ID {$student['id']} ({$student['first_name']} {$student['last_name']}) has no strand assigned. Skipping.";
                    $error_count++;
                    continue;
                }
                
                // Check if sections exist for this strand
                if (!isset($sections[$strand]) || empty($sections[$strand])) {
                    $log_messages[] = "No sections available for strand {$strand}. Student ID {$student['id']} ({$student['first_name']} {$student['last_name']}) not assigned.";
                    $error_count++;
                    continue;
                }
                
                // Find section with available space
                $assigned = false;
                foreach ($sections[$strand] as &$section) {
                    if ($section['current_count'] < $section['max_students']) {
                        // Update student section and status
                        $update_query = "UPDATE students SET section = ?, enrollment_status = 'enrolled' WHERE id = ?";
                        $update_stmt = mysqli_prepare($conn, $update_query);
                        mysqli_stmt_bind_param($update_stmt, "si", $section['name'], $student['id']);
                        
                        if (mysqli_stmt_execute($update_stmt)) {
                            $section['current_count']++;
                            $assigned_count++;
                            $log_messages[] = "Student ID {$student['id']} ({$student['first_name']} {$student['last_name']}) assigned to section {$section['name']}.";
                            
                            // Log action
                            $log_desc = "Auto-assigned student ID {$student['id']} ({$student['first_name']} {$student['last_name']}) to section {$section['name']}.";
                            logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
                            
                            $assigned = true;
                            break;
                        } else {
                            $log_messages[] = "Error updating student ID {$student['id']}: " . mysqli_error($conn);
                            $error_count++;
                        }
                    }
                }
                
                if (!$assigned) {
                    $log_messages[] = "No available space in any section for strand {$strand}. Student ID {$student['id']} ({$student['first_name']} {$student['last_name']}) not assigned.";
                    $error_count++;
                }
            }
            
            if ($assigned_count > 0) {
                $_SESSION['alert'] = showAlert("Successfully assigned {$assigned_count} students to sections. {$error_count} errors occurred.", 'success');
            } else {
                $_SESSION['alert'] = showAlert("No students were assigned to sections. {$error_count} errors occurred.", 'warning');
            }
        }
    }
}

// Get available school years (current year and next 3 years)
$school_years = [];
$current_year = (int)date('Y');
for ($i = 0; $i < 4; $i++) {
    $year_start = $current_year + $i;
    $year_end = $year_start + 1;
    $school_years[] = $year_start . '-' . $year_end;
}

// Get available school years from the database
$school_years = [];
$school_years_query = "SELECT school_year, is_current FROM school_years WHERE status = 'Active' ORDER BY year_start DESC";
$school_years_result = mysqli_query($conn, $school_years_query);

if ($school_years_result) {
    while ($row = mysqli_fetch_assoc($school_years_result)) {
        $school_years[] = $row;
    }
}

// If no school years found in the database, create default ones
if (empty($school_years)) {
    $current_year = (int)date('Y');
    for ($i = 0; $i < 4; $i++) {
        $year_start = $current_year + $i;
        $year_end = $year_start + 1;
        $school_years[] = [
            'school_year' => $year_start . '-' . $year_end,
            'is_current' => ($i === 0) ? 1 : 0
        ];
    }
}
?>

<div class="container-fluid">
    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>
    
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Auto Assign Students to Sections</h1>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Assignment Parameters</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="mb-3">
                            <label for="grade_level" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="grade_level" name="grade_level" required>
                                <option value="">Select Grade Level</option>
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="school_year" class="form-label">School Year <span class="text-danger">*</span></label>
                            <select class="form-select" id="school_year" name="school_year" required>
                                <option value="">Select School Year</option>
                                <?php foreach ($school_years as $year): ?>
                                    <option value="<?php echo $year['school_year']; ?>"><?php echo $year['school_year']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="First">First</option>
                                <option value="Second">Second</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="auto_assign" class="btn btn-primary" onclick="return confirm('This will automatically assign students to sections based on their grade level and strand. Continue?')">
                                <i class="fas fa-magic me-1"></i> Auto Assign Sections
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">How It Works</h5>
                </div>
                <div class="card-body">
                    <p>The auto-assignment process works as follows:</p>
                    <ol>
                        <li>Finds all students in the selected grade level who either:
                            <ul>
                                <li>Have no section assigned</li>
                                <li>Have enrollment status set to 'pending'</li>
                            </ul>
                        </li>
                        <li>For each student, finds a section that:
                            <ul>
                                <li>Matches their strand</li>
                                <li>Has available space (below maximum capacity)</li>
                                <li>Is active and matches the selected school year and semester</li>
                            </ul>
                        </li>
                        <li>Assigns the student to the section and sets enrollment status to 'enrolled'</li>
                    </ol>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i> Students must have a strand assigned in their SHS details to be auto-assigned to a section.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($log_messages)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Assignment Log</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p><strong>Summary:</strong> <?php echo $assigned_count; ?> students assigned successfully. <?php echo $error_count; ?> errors occurred.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($log_messages as $index => $message): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $message; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
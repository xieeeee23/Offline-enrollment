<?php
$title = 'Grade Management';
$page_header = 'Grade Management';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'teacher'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
    exit();
}

// Create the table if it doesn't exist
$check_periods_table = "SHOW TABLES LIKE 'grading_periods'";
$periods_exists = mysqli_query($conn, $check_periods_table);

if (mysqli_num_rows($periods_exists) == 0) {
    $create_periods_table = "CREATE TABLE grading_periods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        is_current BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_periods_table);
    
    // Insert default grading periods
    $insert_periods = "INSERT INTO grading_periods (name, start_date, end_date, school_year, is_current) VALUES
        ('First Quarter', '2023-06-05', '2023-08-11', '2023-2024', TRUE),
        ('Second Quarter', '2023-08-14', '2023-10-20', '2023-2024', FALSE),
        ('Third Quarter', '2023-10-23', '2023-12-22', '2023-2024', FALSE),
        ('Fourth Quarter', '2024-01-08', '2024-03-22', '2023-2024', FALSE)";
    mysqli_query($conn, $insert_periods);
}

$check_grades_table = "SHOW TABLES LIKE 'grades'";
$grades_exists = mysqli_query($conn, $check_grades_table);

if (mysqli_num_rows($grades_exists) == 0) {
    $create_grades_table = "CREATE TABLE grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id VARCHAR(100) NOT NULL,
        teacher_id INT,
        grading_period_id INT NOT NULL,
        grade DECIMAL(5,2) NOT NULL,
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
        FOREIGN KEY (grading_period_id) REFERENCES grading_periods(id) ON DELETE CASCADE,
        UNIQUE KEY (student_id, subject_id, grading_period_id)
    )";
    mysqli_query($conn, $create_grades_table);
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Record grades
        if ($action === 'record_grades') {
            $grading_period_id = (int)$_POST['grading_period_id'];
            $subject_id = cleanInput($_POST['subject_id']);
            $grade_level = cleanInput($_POST['grade_level']);
            $section = cleanInput($_POST['section']);
            $student_ids = isset($_POST['student_id']) ? $_POST['student_id'] : [];
            $grades = isset($_POST['grade']) ? $_POST['grade'] : [];
            $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : [];
            
            // Get teacher ID from current user
            $teacher_id = null;
            if ($_SESSION['role'] == 'teacher') {
                $teacher_query = "SELECT id FROM teachers WHERE user_id = ?";
                $teacher_stmt = mysqli_prepare($conn, $teacher_query);
                mysqli_stmt_bind_param($teacher_stmt, 'i', $_SESSION['user_id']);
                mysqli_stmt_execute($teacher_stmt);
                $teacher_result = mysqli_stmt_get_result($teacher_stmt);
                $teacher_row = mysqli_fetch_assoc($teacher_result);
                $teacher_id = $teacher_row ? $teacher_row['id'] : null;
            } else {
                // Admin user - get selected teacher_id
                $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
            }
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Loop through each student and insert/update grade
                foreach ($student_ids as $index => $student_id) {
                    $student_id = (int)$student_id;
                    $grade = (float)$grades[$index];
                    $remark = isset($remarks[$index]) ? cleanInput($remarks[$index]) : '';
                    
                    // Validate grade
                    if ($grade < 0 || $grade > 100) {
                        throw new Exception("Invalid grade value for student ID $student_id. Grade must be between 0 and 100.");
                    }
                    
                    // Check if grade record already exists
                    $check_query = "SELECT id FROM grades WHERE student_id = ? AND subject_id = ? AND grading_period_id = ?";
                    $check_stmt = mysqli_prepare($conn, $check_query);
                    mysqli_stmt_bind_param($check_stmt, 'isi', $student_id, $subject_id, $grading_period_id);
                    mysqli_stmt_execute($check_stmt);
                    mysqli_stmt_store_result($check_stmt);
                    $exists = mysqli_stmt_num_rows($check_stmt) > 0;
                    mysqli_stmt_close($check_stmt);
                    
                    if ($exists) {
                        // Update existing record
                        $update_query = "UPDATE grades SET grade = ?, remarks = ?, teacher_id = ? 
                                        WHERE student_id = ? AND subject_id = ? AND grading_period_id = ?";
                        $stmt = mysqli_prepare($conn, $update_query);
                        mysqli_stmt_bind_param($stmt, 'dsiisi', $grade, $remark, $teacher_id, $student_id, $subject_id, $grading_period_id);
                    } else {
                        // Insert new record
                        $insert_query = "INSERT INTO grades (student_id, subject_id, teacher_id, grading_period_id, grade, remarks) 
                                        VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_query);
                        mysqli_stmt_bind_param($stmt, 'isiids', $student_id, $subject_id, $teacher_id, $grading_period_id, $grade, $remark);
                    }
                    
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                $_SESSION['alert'] = showAlert('Grades recorded successfully!', 'success');
                logAction($_SESSION['user_id'], 'RECORD', "Recorded grades for $subject_id, $grade_level-$section");
            } catch (Exception $e) {
                // Rollback in case of error
                mysqli_rollback($conn);
                $_SESSION['alert'] = showAlert('Error recording grades: ' . $e->getMessage(), 'danger');
            }
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF'] . '?grading_period_id=' . $grading_period_id . '&subject_id=' . $subject_id . '&grade_level=' . $grade_level . '&section=' . $section);
            exit();
        }
    }
}

// Get current grading period
$current_period_query = "SELECT * FROM grading_periods WHERE is_current = 1";
$current_period_result = mysqli_query($conn, $current_period_query);
$current_period = mysqli_fetch_assoc($current_period_result);

if (!$current_period) {
    // If no current period set, get the first one
    $first_period_query = "SELECT * FROM grading_periods ORDER BY start_date LIMIT 1";
    $first_period_result = mysqli_query($conn, $first_period_query);
    $current_period = mysqli_fetch_assoc($first_period_result);
}

// Get all grading periods
$periods_query = "SELECT * FROM grading_periods ORDER BY start_date";
$periods_result = mysqli_query($conn, $periods_query);
$grading_periods = [];

while ($row = mysqli_fetch_assoc($periods_result)) {
    $grading_periods[] = $row;
}

// Get parameters from form or URL
$grading_period_id = isset($_GET['grading_period_id']) ? (int)$_GET['grading_period_id'] : ($current_period ? $current_period['id'] : 0);
$subject_id = isset($_GET['subject_id']) ? cleanInput($_GET['subject_id']) : '';
$grade_level = isset($_GET['grade_level']) ? cleanInput($_GET['grade_level']) : '';
$section = isset($_GET['section']) ? cleanInput($_GET['section']) : '';

// Get list of subjects
$subjects_query = "SELECT DISTINCT subject FROM schedules ORDER BY subject";
$subjects_result = mysqli_query($conn, $subjects_query);
$subjects = [];

while ($row = mysqli_fetch_assoc($subjects_result)) {
    $subjects[] = $row['subject'];
}

// Get list of grade levels
$grade_levels_query = "SELECT DISTINCT grade_level FROM students ORDER BY grade_level";
$grade_levels_result = mysqli_query($conn, $grade_levels_query);
$grade_levels = [];

while ($row = mysqli_fetch_assoc($grade_levels_result)) {
    $grade_levels[] = $row['grade_level'];
}

// Get list of sections for selected grade level
$sections = [];
if (!empty($grade_level)) {
    $sections_query = "SELECT DISTINCT section FROM students WHERE grade_level = ? ORDER BY section";
    $sections_stmt = mysqli_prepare($conn, $sections_query);
    mysqli_stmt_bind_param($sections_stmt, 's', $grade_level);
    mysqli_stmt_execute($sections_stmt);
    $sections_result = mysqli_stmt_get_result($sections_stmt);
    
    while ($row = mysqli_fetch_assoc($sections_result)) {
        $sections[] = $row['section'];
    }
}

// Get students for selected grade and section
$students = [];
if (!empty($grade_level) && !empty($section) && !empty($subject_id) && $grading_period_id > 0) {
    $students_query = "SELECT s.*, 
                        IFNULL(g.grade, '') AS current_grade, 
                        IFNULL(g.remarks, '') AS grade_remarks 
                       FROM students s 
                       LEFT JOIN grades g ON s.id = g.student_id AND g.subject_id = ? AND g.grading_period_id = ? 
                       WHERE s.grade_level = ? AND s.section = ? AND s.status = 'Active'
                       ORDER BY s.last_name, s.first_name";
    $students_stmt = mysqli_prepare($conn, $students_query);
    mysqli_stmt_bind_param($students_stmt, 'siss', $subject_id, $grading_period_id, $grade_level, $section);
    mysqli_stmt_execute($students_stmt);
    $students_result = mysqli_stmt_get_result($students_stmt);
    
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}

// Get selected grading period details
$selected_period = null;
if ($grading_period_id > 0) {
    foreach ($grading_periods as $period) {
        if ($period['id'] == $grading_period_id) {
            $selected_period = $period;
            break;
        }
    }
}

// Get list of teachers for admin user
$teachers = [];
if ($_SESSION['role'] == 'admin') {
    $teachers_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM teachers ORDER BY last_name, first_name";
    $teachers_result = mysqli_query($conn, $teachers_query);
    
    while ($row = mysqli_fetch_assoc($teachers_result)) {
        $teachers[] = $row;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <?php if (isset($_SESSION['alert'])) echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i> Select Class and Subject</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="grading_period_id" class="form-label">Grading Period</label>
                        <select class="form-select" id="grading_period_id" name="grading_period_id" required>
                            <?php foreach ($grading_periods as $period): ?>
                            <option value="<?php echo $period['id']; ?>" <?php echo $grading_period_id == $period['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($period['name'] . ' (' . $period['school_year'] . ')'); ?>
                                <?php echo $period['is_current'] ? ' - Current' : ''; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="subject_id" class="form-label">Subject</label>
                        <select class="form-select" id="subject_id" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject; ?>" <?php echo $subject_id == $subject ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="grade_level" class="form-label">Grade Level</label>
                        <select class="form-select" id="grade_level" name="grade_level" required>
                            <option value="">Select Grade</option>
                            <?php foreach ($grade_levels as $grade): ?>
                            <option value="<?php echo $grade; ?>" <?php echo $grade_level == $grade ? 'selected' : ''; ?>>
                                Grade <?php echo htmlspecialchars($grade); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section" <?php echo empty($grade_level) ? 'disabled' : ''; ?> required>
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo $sec; ?>" <?php echo $section == $sec ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sec); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i> Filter Students
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($grade_level) && !empty($section) && !empty($subject_id) && $grading_period_id > 0): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-star me-2"></i> 
                    <?php echo htmlspecialchars($subject_id); ?> Grades for Grade <?php echo htmlspecialchars($grade_level); ?> - <?php echo htmlspecialchars($section); ?> 
                    (<?php echo htmlspecialchars($selected_period['name']); ?>)
                </h5>
            </div>
            
            <?php if (count($students) > 0): ?>
            <div class="card-body">
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="action" value="record_grades">
                    <input type="hidden" name="grading_period_id" value="<?php echo $grading_period_id; ?>">
                    <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject_id); ?>">
                    <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($grade_level); ?>">
                    <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                    
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="teacher_id" class="form-label">Select Teacher</label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">LRN</th>
                                    <th width="35%">Student Name</th>
                                    <th width="15%">Grade</th>
                                    <th width="30%">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?>
                                        <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" name="grade[]" 
                                               value="<?php echo $student['current_grade'] !== '' ? $student['current_grade'] : ''; ?>" 
                                               placeholder="0-100" min="0" max="100" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="remarks[]" 
                                               value="<?php echo htmlspecialchars($student['grade_remarks']); ?>" 
                                               placeholder="Optional remarks">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" id="clearGrades">
                            <i class="fas fa-eraser me-2"></i> Clear All
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Grades
                        </button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i> No students found for Grade <?php echo $grade_level; ?> - <?php echo $section; ?>.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Grade Reports Section -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-file-alt me-2"></i> Grade Reports</h5>
            </div>
            <div class="card-body">
                <form method="get" action="grade_report.php" id="gradeReportForm" class="row g-3 mb-4" target="_blank">
                    <div class="col-md-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="class">Class Report</option>
                            <option value="student">Student Report</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 student-select-group" style="display: none;">
                        <label for="student_id" class="form-label">Student</label>
                        <select class="form-select" id="student_id" name="student_id">
                            <option value="">Select Student</option>
                            <!-- Will be populated via AJAX -->
                        </select>
                    </div>
                    
                    <div class="col-md-3 class-select-group">
                        <label for="report_grade_level" class="form-label">Grade Level</label>
                        <select class="form-select" id="report_grade_level" name="report_grade_level">
                            <option value="">All Grade Levels</option>
                            <?php foreach ($grade_levels as $grade): ?>
                            <option value="<?php echo $grade; ?>">Grade <?php echo $grade; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 class-select-group">
                        <label for="report_section" class="form-label">Section</label>
                        <select class="form-select" id="report_section" name="report_section" disabled>
                            <option value="">All Sections</option>
                            <!-- Will be populated via AJAX -->
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="report_period" class="form-label">Grading Period</label>
                        <select class="form-select" id="report_period" name="report_period" required>
                            <?php foreach ($grading_periods as $period): ?>
                            <option value="<?php echo $period['id']; ?>" <?php echo $period['is_current'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($period['name'] . ' (' . $period['school_year'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                            <option value="all">All Grading Periods</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-file-excel me-2"></i> Generate Excel Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic section dropdown based on grade level selection
    const gradeLevelSelect = document.getElementById('grade_level');
    const sectionSelect = document.getElementById('section');
    
    gradeLevelSelect.addEventListener('change', function() {
        const gradeLevel = this.value;
        
        // Clear current options
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        sectionSelect.disabled = gradeLevel === '';
        
        if (gradeLevel !== '') {
            // Fetch sections for this grade level via AJAX
            fetch(`get_sections.php?grade_level=${gradeLevel}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        sectionSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching sections:', error));
        }
    });
    
    // Clear grades button functionality
    const clearGradesBtn = document.getElementById('clearGrades');
    if (clearGradesBtn) {
        clearGradesBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all grades? This will not delete saved grades.')) {
                document.querySelectorAll('input[name="grade[]"]').forEach(input => {
                    input.value = '';
                });
                document.querySelectorAll('input[name="remarks[]"]').forEach(input => {
                    input.value = '';
                });
            }
        });
    }
    
    // Report type toggle
    const reportType = document.getElementById('report_type');
    const studentSelectGroup = document.querySelector('.student-select-group');
    const classSelectGroups = document.querySelectorAll('.class-select-group');
    
    reportType.addEventListener('change', function() {
        const isStudentReport = this.value === 'student';
        
        studentSelectGroup.style.display = isStudentReport ? 'block' : 'none';
        classSelectGroups.forEach(group => {
            group.style.display = isStudentReport ? 'none' : 'block';
        });
        
        // If student report is selected, populate student dropdown
        if (isStudentReport) {
            fetch('get_students.php')
                .then(response => response.json())
                .then(data => {
                    const studentSelect = document.getElementById('student_id');
                    studentSelect.innerHTML = '<option value="">Select Student</option>';
                    
                    data.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.id;
                        option.textContent = `${student.last_name}, ${student.first_name} (Grade ${student.grade_level}-${student.section})`;
                        studentSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching students:', error));
        }
    });
    
    // Grade level and section for report
    const reportGradeLevel = document.getElementById('report_grade_level');
    const reportSection = document.getElementById('report_section');
    
    reportGradeLevel.addEventListener('change', function() {
        const gradeLevel = this.value;
        
        // Clear current options
        reportSection.innerHTML = '<option value="">All Sections</option>';
        reportSection.disabled = gradeLevel === '';
        
        if (gradeLevel !== '') {
            // Fetch sections for this grade level
            fetch(`get_sections.php?grade_level=${gradeLevel}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        reportSection.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching sections:', error));
        }
    });
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
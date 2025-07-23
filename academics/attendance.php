<?php
$title = 'Attendance Management';
$page_header = 'Attendance Management';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'teacher'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
    exit();
}

// Create the table if it doesn't exist
$check_table = "SHOW TABLES LIKE 'attendance'";
$table_exists = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_exists) == 0) {
    $create_table = "CREATE TABLE attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        date DATE NOT NULL,
        status ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present',
        remarks TEXT,
        recorded_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY (student_id, date)
    )";
    mysqli_query($conn, $create_table);
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Record attendance
        if ($action === 'record') {
            $date = cleanInput($_POST['date']);
            $grade_level = cleanInput($_POST['grade_level']);
            $section = cleanInput($_POST['section']);
            $student_ids = isset($_POST['student_id']) ? $_POST['student_id'] : [];
            $statuses = isset($_POST['status']) ? $_POST['status'] : [];
            $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : [];
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Loop through each student and insert/update attendance
                foreach ($student_ids as $index => $student_id) {
                    $student_id = (int)$student_id;
                    $status = cleanInput($statuses[$index]);
                    $remark = isset($remarks[$index]) ? cleanInput($remarks[$index]) : NULL;
                    
                    // Check if attendance record already exists
                    $check_query = "SELECT id FROM attendance WHERE student_id = ? AND date = ?";
                    $check_stmt = mysqli_prepare($conn, $check_query);
                    mysqli_stmt_bind_param($check_stmt, 'is', $student_id, $date);
                    mysqli_stmt_execute($check_stmt);
                    mysqli_stmt_store_result($check_stmt);
                    $exists = mysqli_stmt_num_rows($check_stmt) > 0;
                    mysqli_stmt_close($check_stmt);
                    
                    if ($exists) {
                        // Update existing record
                        $update_query = "UPDATE attendance SET status = ?, remarks = ?, recorded_by = ? 
                                        WHERE student_id = ? AND date = ?";
                        $stmt = mysqli_prepare($conn, $update_query);
                        mysqli_stmt_bind_param($stmt, 'ssiss', $status, $remark, $_SESSION['user_id'], $student_id, $date);
                    } else {
                        // Insert new record
                        $insert_query = "INSERT INTO attendance (student_id, date, status, remarks, recorded_by) 
                                        VALUES (?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $insert_query);
                        mysqli_stmt_bind_param($stmt, 'isssi', $student_id, $date, $status, $remark, $_SESSION['user_id']);
                    }
                    
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                $_SESSION['alert'] = showAlert('Attendance recorded successfully!', 'success');
                logAction($_SESSION['user_id'], 'RECORD', "Recorded attendance for $grade_level-$section on $date");
            } catch (Exception $e) {
                // Rollback in case of error
                mysqli_rollback($conn);
                $_SESSION['alert'] = showAlert('Error recording attendance: ' . $e->getMessage(), 'danger');
            }
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF'] . '?date=' . $date . '&grade_level=' . $grade_level . '&section=' . $section);
            exit();
        }
    }
}

// Get date for attendance (default to today)
$date = isset($_GET['date']) ? cleanInput($_GET['date']) : date('Y-m-d');

// Get grade level and section filter
$grade_level = isset($_GET['grade_level']) ? cleanInput($_GET['grade_level']) : '';
$section = isset($_GET['section']) ? cleanInput($_GET['section']) : '';

// Get list of grade levels
$grade_levels_query = "SELECT DISTINCT grade_level FROM students ORDER BY grade_level";
$grade_levels_result = mysqli_query($conn, $grade_levels_query);
$grade_levels = [];

if ($grade_levels_result) {
    while ($row = mysqli_fetch_assoc($grade_levels_result)) {
        $grade_levels[] = $row['grade_level'];
    }
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
if (!empty($grade_level) && !empty($section)) {
    $students_query = "SELECT s.*, 
                        IFNULL(a.status, '') AS attendance_status, 
                        IFNULL(a.remarks, '') AS attendance_remarks 
                       FROM students s 
                       LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ? 
                       WHERE s.grade_level = ? AND s.section = ? AND s.status = 'Active'
                       ORDER BY s.last_name, s.first_name";
    $students_stmt = mysqli_prepare($conn, $students_query);
    mysqli_stmt_bind_param($students_stmt, 'sss', $date, $grade_level, $section);
    mysqli_stmt_execute($students_stmt);
    $students_result = mysqli_stmt_get_result($students_stmt);
    
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}

// Get attendance statistics
$attendance_stats = [
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'excused' => 0,
    'total' => count($students)
];

foreach ($students as $student) {
    if (!empty($student['attendance_status'])) {
        $attendance_stats[$student['attendance_status']]++;
    } else {
        // Default to present if no record
        $attendance_stats['present']++;
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
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i> Filter Students</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $date; ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="grade_level" class="form-label">Grade Level</label>
                        <select class="form-select" id="grade_level" name="grade_level" required>
                            <option value="">Select Grade Level</option>
                            <?php foreach ($grade_levels as $grade): ?>
                            <option value="<?php echo $grade; ?>" <?php echo $grade_level == $grade ? 'selected' : ''; ?>><?php echo $grade; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section" <?php echo empty($grade_level) ? 'disabled' : ''; ?> required>
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo $sec; ?>" <?php echo $section == $sec ? 'selected' : ''; ?>><?php echo $sec; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i> Filter Students
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($grade_level) && !empty($section)): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-check me-2"></i> 
                    Attendance for Grade <?php echo $grade_level; ?> - <?php echo $section; ?> 
                    (<?php echo date('F d, Y', strtotime($date)); ?>)
                </h5>
                
                <?php if (count($students) > 0): ?>
                <div>
                    <button type="button" class="btn btn-sm btn-light" id="markAllPresent">
                        <i class="fas fa-check-circle me-1"></i> Mark All Present
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (count($students) > 0): ?>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="attendance-summary d-flex justify-content-center">
                            <div class="mx-2 px-3 py-2 bg-success bg-opacity-10 border border-success rounded text-center">
                                <h5 class="mb-0"><?php echo $attendance_stats['present']; ?></h5>
                                <small>Present</small>
                            </div>
                            <div class="mx-2 px-3 py-2 bg-danger bg-opacity-10 border border-danger rounded text-center">
                                <h5 class="mb-0"><?php echo $attendance_stats['absent']; ?></h5>
                                <small>Absent</small>
                            </div>
                            <div class="mx-2 px-3 py-2 bg-warning bg-opacity-10 border border-warning rounded text-center">
                                <h5 class="mb-0"><?php echo $attendance_stats['late']; ?></h5>
                                <small>Late</small>
                            </div>
                            <div class="mx-2 px-3 py-2 bg-info bg-opacity-10 border border-info rounded text-center">
                                <h5 class="mb-0"><?php echo $attendance_stats['excused']; ?></h5>
                                <small>Excused</small>
                            </div>
                            <div class="mx-2 px-3 py-2 bg-secondary bg-opacity-10 border border-secondary rounded text-center">
                                <h5 class="mb-0"><?php echo $attendance_stats['total']; ?></h5>
                                <small>Total</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="action" value="record">
                    <input type="hidden" name="date" value="<?php echo $date; ?>">
                    <input type="hidden" name="grade_level" value="<?php echo $grade_level; ?>">
                    <input type="hidden" name="section" value="<?php echo $section; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">LRN</th>
                                    <th width="35%">Student Name</th>
                                    <th width="15%">Status</th>
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
                                        <select class="form-select form-select-sm status-select" name="status[]">
                                            <option value="present" <?php echo ($student['attendance_status'] == 'present' || $student['attendance_status'] == '') ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo $student['attendance_status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                            <option value="late" <?php echo $student['attendance_status'] == 'late' ? 'selected' : ''; ?>>Late</option>
                                            <option value="excused" <?php echo $student['attendance_status'] == 'excused' ? 'selected' : ''; ?>>Excused</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="remarks[]" 
                                               value="<?php echo htmlspecialchars($student['attendance_remarks']); ?>" 
                                               placeholder="Optional remarks">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Attendance
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

<!-- Monthly Attendance Report Section -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i> Monthly Attendance Report</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="monthlyReportForm" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="report_month" class="form-label">Month</label>
                        <select class="form-select" id="report_month" name="report_month">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($_GET['report_month']) && $_GET['report_month'] == $i) || (!isset($_GET['report_month']) && date('n') == $i) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="report_year" class="form-label">Year</label>
                        <select class="form-select" id="report_year" name="report_year">
                            <?php for ($i = date('Y') - 2; $i <= date('Y'); $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($_GET['report_year']) && $_GET['report_year'] == $i) || (!isset($_GET['report_year']) && date('Y') == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="report_grade_section" class="form-label">Grade & Section</label>
                        <select class="form-select" id="report_grade_section" name="report_grade_section">
                            <option value="">All Grades & Sections</option>
                            <?php
                            $grade_sections_query = "SELECT DISTINCT CONCAT(grade_level, '-', section) as grade_section FROM students ORDER BY grade_level, section";
                            $grade_sections_result = mysqli_query($conn, $grade_sections_query);
                            
                            while ($row = mysqli_fetch_assoc($grade_sections_result)) {
                                $selected = (isset($_GET['report_grade_section']) && $_GET['report_grade_section'] == $row['grade_section']) ? 'selected' : '';
                                echo "<option value='" . $row['grade_section'] . "' $selected>" . $row['grade_section'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i> Generate Report
                        </button>
                    </div>
                </form>
                
                <div id="report-container">
                    <!-- Monthly report will be loaded here via AJAX -->
                    <div class="text-center py-5">
                        <p class="text-muted">Select month, year, and grade/section to generate the report.</p>
                    </div>
                </div>
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
    
    // Mark all present button
    const markAllPresentBtn = document.getElementById('markAllPresent');
    if (markAllPresentBtn) {
        markAllPresentBtn.addEventListener('click', function() {
            document.querySelectorAll('.status-select').forEach(select => {
                select.value = 'present';
            });
        });
    }
    
    // Change row color based on status selection
    document.querySelectorAll('.status-select').forEach(select => {
        // Set initial row color
        updateRowColor(select);
        
        // Update color when status changes
        select.addEventListener('change', function() {
            updateRowColor(this);
        });
    });
    
    function updateRowColor(select) {
        const row = select.closest('tr');
        
        // Remove all status-related classes
        row.classList.remove('table-success', 'table-danger', 'table-warning', 'table-info');
        
        // Add appropriate class based on status
        switch(select.value) {
            case 'present':
                row.classList.add('table-success');
                break;
            case 'absent':
                row.classList.add('table-danger');
                break;
            case 'late':
                row.classList.add('table-warning');
                break;
            case 'excused':
                row.classList.add('table-info');
                break;
        }
    }
    
    // Monthly report form submission
    const monthlyReportForm = document.getElementById('monthlyReportForm');
    if (monthlyReportForm) {
        monthlyReportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const reportContainer = document.getElementById('report-container');
            
            // Show loading indicator
            reportContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            // Build query string
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            // Fetch report data
            fetch(`attendance_report.php?${params.toString()}`)
                .then(response => response.text())
                .then(data => {
                    reportContainer.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error generating report:', error);
                    reportContainer.innerHTML = '<div class="alert alert-danger">Error generating report. Please try again.</div>';
                });
        });
    }
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
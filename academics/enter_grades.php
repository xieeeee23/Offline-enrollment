<?php
$title = 'Enter Grades';
$page_header = 'Enter Student Grades';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'teacher', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit();
}

// Get parameters - use consistent parameter names
$subject_id = isset($_GET['subject']) ? (int) $_GET['subject'] : 0;
$section = isset($_GET['section']) ? cleanInput($_GET['section']) : '';
$school_year = isset($_GET['year']) ? cleanInput($_GET['year']) : '';
$semester = isset($_GET['semester']) ? cleanInput($_GET['semester']) : '';

// For debugging
if (!$subject_id || empty($section) || empty($school_year) || empty($semester)) {
    echo "<div class='alert alert-danger'>Debug info: subject=$subject_id, section=$section, year=$school_year, semester=$semester</div>";
    echo "<div class='alert alert-danger'>Raw GET data: " . print_r($_GET, true) . "</div>";
    
    $_SESSION['alert'] = showAlert('Invalid parameters. Please select subject, section, school year, and semester.', 'danger');
    redirect($relative_path . 'modules/academics/grading.php');
    exit();
}

// Get subject details
$subject_query = "SELECT * FROM subjects WHERE id = ?";
$stmt = mysqli_prepare($conn, $subject_query);
mysqli_stmt_bind_param($stmt, "i", $subject_id);
mysqli_stmt_execute($stmt);
$subject_result = mysqli_stmt_get_result($stmt);

if (!$subject_result || mysqli_num_rows($subject_result) == 0) {
    $_SESSION['alert'] = showAlert('Subject not found.', 'danger');
    redirect($relative_path . 'modules/academics/grading.php');
    exit();
}

$subject = mysqli_fetch_assoc($subject_result);

// Get students in the section
$students_query = "SELECT s.id, s.first_name, s.middle_name, s.last_name 
                  FROM students s 
                  WHERE s.section = ? 
                  ORDER BY s.last_name, s.first_name";
$stmt = mysqli_prepare($conn, $students_query);
mysqli_stmt_bind_param($stmt, "s", $section);
mysqli_stmt_execute($stmt);
$students_result = mysqli_stmt_get_result($stmt);

$students = [];
if ($students_result && mysqli_num_rows($students_result) > 0) {
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}

// Get grading periods
$periods_query = "SELECT * FROM grading_periods ORDER BY id";
$periods_result = mysqli_query($conn, $periods_query);

$periods = [];
if ($periods_result && mysqli_num_rows($periods_result) > 0) {
    while ($row = mysqli_fetch_assoc($periods_result)) {
        $periods[] = $row;
    }
}

// If no periods are defined, create default ones
if (empty($periods)) {
    $default_periods = [
        ['name' => 'First Quarter', 'period_number' => 1],
        ['name' => 'Second Quarter', 'period_number' => 2],
        ['name' => 'Third Quarter', 'period_number' => 3],
        ['name' => 'Fourth Quarter', 'period_number' => 4]
    ];
    
    foreach ($default_periods as $period) {
        $insert_query = "INSERT INTO grading_periods (name, period_number) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "si", $period['name'], $period['period_number']);
        mysqli_stmt_execute($stmt);
        $period['id'] = mysqli_insert_id($conn);
        $periods[] = $period;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $period_id = (int) $_POST['period_id'];
    $success = true;
    $message = '';
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        foreach ($students as $student) {
            $student_id = $student['id'];
            $written_work = isset($_POST['written_work'][$student_id]) ? (float) $_POST['written_work'][$student_id] : 0;
            $performance_tasks = isset($_POST['performance_tasks'][$student_id]) ? (float) $_POST['performance_tasks'][$student_id] : 0;
            $quarterly_assessment = isset($_POST['quarterly_assessment'][$student_id]) ? (float) $_POST['quarterly_assessment'][$student_id] : 0;
            
            // Calculate final grade (30% written work + 50% performance tasks + 20% quarterly assessment)
            $final_grade = ($written_work * 0.3) + ($performance_tasks * 0.5) + ($quarterly_assessment * 0.2);
            $final_grade = round($final_grade, 2);
            
            // Check if grade already exists
            $check_query = "SELECT id FROM student_grades 
                           WHERE student_id = ? AND subject_id = ? AND period_id = ? 
                           AND school_year = ? AND semester = ?";
            $stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($stmt, "iisss", $student_id, $subject_id, $period_id, $school_year, $semester);
            mysqli_stmt_execute($stmt);
            $check_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                // Update existing grade
                $grade_row = mysqli_fetch_assoc($check_result);
                $grade_id = $grade_row['id'];
                
                $update_query = "UPDATE student_grades 
                               SET written_work = ?, performance_tasks = ?, quarterly_assessment = ?, final_grade = ? 
                               WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "ddddi", $written_work, $performance_tasks, $quarterly_assessment, $final_grade, $grade_id);
                mysqli_stmt_execute($stmt);
            } else {
                // Insert new grade
                $insert_query = "INSERT INTO student_grades 
                               (student_id, subject_id, period_id, school_year, semester, 
                                written_work, performance_tasks, quarterly_assessment, final_grade) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "iisssdddd", $student_id, $subject_id, $period_id, 
                                     $school_year, $semester, $written_work, $performance_tasks, 
                                     $quarterly_assessment, $final_grade);
                mysqli_stmt_execute($stmt);
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        $message = 'Grades saved successfully.';
        $alert_type = 'success';
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $success = false;
        $message = 'Error saving grades: ' . $e->getMessage();
        $alert_type = 'danger';
    }
    
    $_SESSION['alert'] = showAlert($message, $alert_type);
    
    // Redirect to refresh the page
    redirect($_SERVER['REQUEST_URI']);
    exit();
}

// Get existing grades for the selected period
$period_id = isset($_GET['period']) ? (int) $_GET['period'] : (isset($periods[0]) ? $periods[0]['id'] : 0);
$grades = [];

if ($period_id > 0) {
    $grades_query = "SELECT * FROM student_grades 
                    WHERE subject_id = ? AND period_id = ? AND school_year = ? AND semester = ?";
    $stmt = mysqli_prepare($conn, $grades_query);
    mysqli_stmt_bind_param($stmt, "iiss", $subject_id, $period_id, $school_year, $semester);
    mysqli_stmt_execute($stmt);
    $grades_result = mysqli_stmt_get_result($stmt);
    
    if ($grades_result && mysqli_num_rows($grades_result) > 0) {
        while ($row = mysqli_fetch_assoc($grades_result)) {
            $grades[$row['student_id']] = $row;
        }
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_header; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>modules/academics/grading.php">Grading System</a></li>
        <li class="breadcrumb-item active">Enter Grades</li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])) echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="card-title mb-0"><i class="fas fa-edit me-2"></i> Enter Grades</h5>
                </div>
                <div class="col-auto">
                    <a href="<?php echo $relative_path; ?>modules/academics/grading.php" class="btn btn-sm btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Back to Grading
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Class Information</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Subject:</th>
                            <td><?php echo htmlspecialchars($subject['code'] . ' - ' . $subject['name']); ?></td>
                        </tr>
                        <tr>
                            <th>Section:</th>
                            <td><?php echo htmlspecialchars($section); ?></td>
                        </tr>
                        <tr>
                            <th>School Year:</th>
                            <td><?php echo htmlspecialchars($school_year); ?></td>
                        </tr>
                        <tr>
                            <th>Semester:</th>
                            <td><?php echo htmlspecialchars($semester); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h5>Grading Period</h5>
                    <form method="get" action="">
                        <input type="hidden" name="subject" value="<?php echo $subject_id; ?>">
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                        <input type="hidden" name="year" value="<?php echo htmlspecialchars($school_year); ?>">
                        <input type="hidden" name="semester" value="<?php echo htmlspecialchars($semester); ?>">
                        
                        <div class="input-group mb-3">
                            <select class="form-select" name="period" id="period">
                                <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['id']; ?>" <?php echo ($period['id'] == $period_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($period['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-primary" type="submit">Select Period</button>
                        </div>
                    </form>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Enter grades for each component. Final grade will be calculated automatically.
                    </div>
                </div>
            </div>
            
            <?php if (empty($students)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i> No students found in this section.
            </div>
            <?php else: ?>
            <form method="post" action="">
                <input type="hidden" name="period_id" value="<?php echo $period_id; ?>">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-light">
                                <th width="5%">#</th>
                                <th width="15%">Student ID</th>
                                <th width="30%">Name</th>
                                <th width="12%">Written Work<br><small class="text-muted">(30%)</small></th>
                                <th width="12%">Performance Tasks<br><small class="text-muted">(50%)</small></th>
                                <th width="12%">Quarterly Assessment<br><small class="text-muted">(20%)</small></th>
                                <th width="14%">Final Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($student['id']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']);
                                    if (!empty($student['middle_name'])) {
                                        echo ' ' . htmlspecialchars(substr($student['middle_name'], 0, 1) . '.');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm written-work" 
                                           name="written_work[<?php echo $student['id']; ?>]" 
                                           value="<?php echo isset($grades[$student['id']]) ? $grades[$student['id']]['written_work'] : ''; ?>"
                                           min="0" max="100" step="0.01" data-student-id="<?php echo $student['id']; ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm performance-tasks" 
                                           name="performance_tasks[<?php echo $student['id']; ?>]" 
                                           value="<?php echo isset($grades[$student['id']]) ? $grades[$student['id']]['performance_tasks'] : ''; ?>"
                                           min="0" max="100" step="0.01" data-student-id="<?php echo $student['id']; ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm quarterly-assessment" 
                                           name="quarterly_assessment[<?php echo $student['id']; ?>]" 
                                           value="<?php echo isset($grades[$student['id']]) ? $grades[$student['id']]['quarterly_assessment'] : ''; ?>"
                                           min="0" max="100" step="0.01" data-student-id="<?php echo $student['id']; ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm final-grade" 
                                           id="final_grade_<?php echo $student['id']; ?>" 
                                           value="<?php echo isset($grades[$student['id']]) ? number_format($grades[$student['id']]['final_grade'], 2) : ''; ?>"
                                           readonly>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3 text-end">
                    <button type="submit" name="save_grades" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Grades
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calculate final grade when any input changes
    const calculateFinalGrade = function(studentId) {
        const writtenWork = parseFloat(document.querySelector(`.written-work[data-student-id="${studentId}"]`).value) || 0;
        const performanceTasks = parseFloat(document.querySelector(`.performance-tasks[data-student-id="${studentId}"]`).value) || 0;
        const quarterlyAssessment = parseFloat(document.querySelector(`.quarterly-assessment[data-student-id="${studentId}"]`).value) || 0;
        
        // Calculate final grade (30% written work + 50% performance tasks + 20% quarterly assessment)
        const finalGrade = (writtenWork * 0.3) + (performanceTasks * 0.5) + (quarterlyAssessment * 0.2);
        
        // Update final grade field
        document.getElementById(`final_grade_${studentId}`).value = finalGrade.toFixed(2);
    };
    
    // Add event listeners to all input fields
    document.querySelectorAll('.written-work, .performance-tasks, .quarterly-assessment').forEach(function(input) {
        input.addEventListener('input', function() {
            calculateFinalGrade(this.dataset.studentId);
        });
    });
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
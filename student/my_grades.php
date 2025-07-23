<?php
$title = 'My Grades';
$page_header = 'My Grades';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['student'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
    exit();
}

// Ensure the tables exist
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

// Get the student's ID from the logged-in user
$student_query = "SELECT id FROM students WHERE lrn = (SELECT username FROM users WHERE id = ?)";
$student_stmt = mysqli_prepare($conn, $student_query);
mysqli_stmt_bind_param($student_stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($student_stmt);
$student_result = mysqli_stmt_get_result($student_stmt);
$student = mysqli_fetch_assoc($student_result);

if (!$student) {
    echo '<div class="alert alert-danger">Your student record was not found. Please contact the administrator.</div>';
    require_once $relative_path . 'includes/footer.php';
    exit();
}

$student_id = $student['id'];

// Get parameters from URL
$grading_period_id = isset($_GET['grading_period_id']) ? (int)$_GET['grading_period_id'] : ($current_period ? $current_period['id'] : 0);

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

// Get the student's detailed information
$student_details_query = "SELECT * FROM students WHERE id = ?";
$student_details_stmt = mysqli_prepare($conn, $student_details_query);
mysqli_stmt_bind_param($student_details_stmt, 'i', $student_id);
mysqli_stmt_execute($student_details_stmt);
$student_details_result = mysqli_stmt_get_result($student_details_stmt);
$student_details = mysqli_fetch_assoc($student_details_result);

// Get student's grades for the selected period
$grades_query = "SELECT g.*, t.first_name as teacher_first_name, t.last_name as teacher_last_name 
                FROM grades g
                LEFT JOIN teachers t ON g.teacher_id = t.id
                WHERE g.student_id = ? AND g.grading_period_id = ?
                ORDER BY g.subject_id";
$grades_stmt = mysqli_prepare($conn, $grades_query);
mysqli_stmt_bind_param($grades_stmt, 'ii', $student_id, $grading_period_id);
mysqli_stmt_execute($grades_stmt);
$grades_result = mysqli_stmt_get_result($grades_stmt);

$grades = [];
$total_grade = 0;
$subject_count = 0;

while ($row = mysqli_fetch_assoc($grades_result)) {
    $grades[] = $row;
    $total_grade += $row['grade'];
    $subject_count++;
}

$average_grade = $subject_count > 0 ? round($total_grade / $subject_count, 2) : 0;

// Function to get the letter grade
function getLetterGrade($grade) {
    if ($grade >= 90) return 'A';
    if ($grade >= 80) return 'B';
    if ($grade >= 70) return 'C';
    if ($grade >= 60) return 'D';
    return 'F';
}

// Function to get the remarks
function getRemarks($grade) {
    if ($grade >= 90) return 'Outstanding';
    if ($grade >= 80) return 'Very Satisfactory';
    if ($grade >= 70) return 'Satisfactory';
    if ($grade >= 60) return 'Fairly Satisfactory';
    return 'Needs Improvement';
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <?php if (isset($_SESSION['alert'])) echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-user-graduate me-2"></i> Student Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student_details['last_name'] . ', ' . $student_details['first_name'] . ' ' . $student_details['middle_name']); ?></p>
                        <p><strong>LRN:</strong> <?php echo htmlspecialchars($student_details['lrn']); ?></p>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($student_details['gender']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Grade Level:</strong> <?php echo htmlspecialchars($student_details['grade_level']); ?></p>
                        <p><strong>Section:</strong> <?php echo htmlspecialchars($student_details['section']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($student_details['status']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-alt me-2"></i> Select Grading Period</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="mb-0">
                    <div class="mb-3">
                        <label for="grading_period_id" class="form-label">Grading Period</label>
                        <select class="form-select" id="grading_period_id" name="grading_period_id" onchange="this.form.submit()">
                            <?php foreach ($grading_periods as $period): ?>
                            <option value="<?php echo $period['id']; ?>" <?php echo $grading_period_id == $period['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($period['name'] . ' (' . $period['school_year'] . ')'); ?>
                                <?php echo $period['is_current'] ? ' - Current' : ''; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($selected_period): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-star me-2"></i> 
                    Grades for <?php echo htmlspecialchars($selected_period['name']); ?> (<?php echo htmlspecialchars($selected_period['school_year']); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($grades) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="30%">Subject</th>
                                <th width="25%">Teacher</th>
                                <th width="10%">Grade</th>
                                <th width="10%">Letter</th>
                                <th width="20%">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $index => $grade): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($grade['subject_id']); ?></td>
                                <td>
                                    <?php 
                                    if ($grade['teacher_id']) {
                                        echo htmlspecialchars($grade['teacher_first_name'] . ' ' . $grade['teacher_last_name']); 
                                    } else {
                                        echo '<em>Not assigned</em>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $grade['grade']; ?></td>
                                <td><?php echo getLetterGrade($grade['grade']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($grade['remarks'])) {
                                        echo htmlspecialchars($grade['remarks']);
                                    } else {
                                        echo getRemarks($grade['grade']);
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <th colspan="3" class="text-end">Average:</th>
                                <th><?php echo $average_grade; ?></th>
                                <th><?php echo getLetterGrade($average_grade); ?></th>
                                <th><?php echo getRemarks($average_grade); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-3">
                    <a href="<?php echo $relative_path; ?>modules/academics/grade_report.php?report_type=student&student_id=<?php echo $student_id; ?>&report_period=<?php echo $grading_period_id; ?>&format=excel" target="_blank" class="btn btn-primary">
                        <i class="fas fa-file-excel me-2"></i> Export Report Card
                    </a>
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i> No grades have been recorded for you in this grading period yet.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
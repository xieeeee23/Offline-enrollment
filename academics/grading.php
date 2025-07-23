<?php
$title = 'Grading System';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has appropriate role
if (!checkAccess(['admin', 'teacher', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}

// Get current school year and semester
$current_school_year = getCurrentSchoolYear($conn);
$current_semester = getCurrentSemester($conn);

// Get grading periods
$grading_periods = [];
$query = "SELECT * FROM grading_periods WHERE status = 'active' ORDER BY id";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $grading_periods[] = $row;
    }
}

// Get teacher's subjects if teacher
$teacher_subjects = [];
if ($_SESSION['role'] == 'teacher') {
    $teacher_id = getTeacherIdByUserId($conn, $_SESSION['user_id']);
    if ($teacher_id) {
        $query = "SELECT s.id, s.name, s.grade_level, sc.section, s.description
                  FROM schedules sc 
                  JOIN subjects s ON sc.subject_id = s.id 
                  WHERE sc.teacher_id = ? 
                  AND sc.school_year = ? 
                  AND sc.semester = ?
                  GROUP BY s.id, sc.section
                  ORDER BY s.grade_level, s.name";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iss", $teacher_id, $current_school_year, $current_semester);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $teacher_subjects[] = $row;
        }
    }
}

// Get all subjects for admin and registrar
$all_subjects = [];
if (in_array($_SESSION['role'], ['admin', 'registrar'])) {
    $query = "SELECT id, name, grade_level, description FROM subjects WHERE status = 'active' ORDER BY grade_level, name";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $all_subjects[] = $row;
    }
}

// Get all sections
$all_sections = [];
$query = "SELECT DISTINCT section FROM students WHERE section IS NOT NULL AND section != '' ORDER BY section";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $all_sections[] = $row['section'];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $title; ?></li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>
    
    <div class="row">
        <!-- Enter Grades Card -->
        <div class="col-xl-6 col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-edit me-2"></i> Enter Student Grades</h5>
                </div>
                <div class="card-body">
                    <form id="enterGradesForm" action="enter_grades.php" method="get">
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-control" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php if ($_SESSION['role'] == 'teacher'): ?>
                                    <?php foreach ($teacher_subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['name'] . ' - ' . $subject['grade_level'] . ' (' . $subject['section'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($all_subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['name'] . ' - ' . $subject['grade_level']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <?php if (in_array($_SESSION['role'], ['admin', 'registrar'])): ?>
                        <div class="mb-3">
                            <label for="section" class="form-label">Section</label>
                            <select class="form-control" id="section" name="section" required>
                                <option value="">Select Section</option>
                                <?php foreach ($all_sections as $section): ?>
                                    <option value="<?php echo $section; ?>"><?php echo htmlspecialchars($section); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="grading_period" class="form-label">Grading Period</label>
                            <select class="form-control" id="grading_period" name="grading_period" required>
                                <option value="">Select Grading Period</option>
                                <?php foreach ($grading_periods as $period): ?>
                                    <option value="<?php echo $period['id']; ?>"><?php echo htmlspecialchars($period['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="school_year" class="form-label">School Year</label>
                            <select class="form-control" id="school_year" name="school_year" required>
                                <option value="">Select School Year</option>
                                <?php
                                $query = "SELECT school_year FROM school_years ORDER BY school_year DESC";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $selected = ($row['school_year'] == $current_school_year) ? 'selected' : '';
                                    echo "<option value='" . $row['school_year'] . "' $selected>" . $row['school_year'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-control" id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="First" <?php echo ($current_semester == 'First') ? 'selected' : ''; ?>>First Semester</option>
                                <option value="Second" <?php echo ($current_semester == 'Second') ? 'selected' : ''; ?>>Second Semester</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Enter Grades
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- View Grades Card -->
        <div class="col-xl-6 col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-search me-2"></i> View Student Grades</h5>
                </div>
                <div class="card-body">
                    <form id="viewGradesForm" action="view_grades.php" method="get">
                        <div class="mb-3">
                            <label for="view_subject_id" class="form-label">Subject</label>
                            <select class="form-control" id="view_subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php if ($_SESSION['role'] == 'teacher'): ?>
                                    <?php foreach ($teacher_subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['name'] . ' - ' . $subject['grade_level'] . ' (' . $subject['section'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($all_subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['name'] . ' - ' . $subject['grade_level']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <?php if (in_array($_SESSION['role'], ['admin', 'registrar'])): ?>
                        <div class="mb-3">
                            <label for="view_section" class="form-label">Section</label>
                            <select class="form-control" id="view_section" name="section" required>
                                <option value="">Select Section</option>
                                <?php foreach ($all_sections as $section): ?>
                                    <option value="<?php echo $section; ?>"><?php echo htmlspecialchars($section); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="view_grading_period" class="form-label">Grading Period</label>
                            <select class="form-control" id="view_grading_period" name="grading_period" required>
                                <option value="">Select Grading Period</option>
                                <?php foreach ($grading_periods as $period): ?>
                                    <option value="<?php echo $period['id']; ?>"><?php echo htmlspecialchars($period['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="view_school_year" class="form-label">School Year</label>
                            <select class="form-control" id="view_school_year" name="school_year" required>
                                <option value="">Select School Year</option>
                                <?php
                                $query = "SELECT school_year FROM school_years ORDER BY school_year DESC";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $selected = ($row['school_year'] == $current_school_year) ? 'selected' : '';
                                    echo "<option value='" . $row['school_year'] . "' $selected>" . $row['school_year'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="view_semester" class="form-label">Semester</label>
                            <select class="form-control" id="view_semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="First" <?php echo ($current_semester == 'First') ? 'selected' : ''; ?>>First Semester</option>
                                <option value="Second" <?php echo ($current_semester == 'Second') ? 'selected' : ''; ?>>Second Semester</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-search me-1"></i> View Grades
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Basic Grading Card -->
    <div class="col-xl-6 col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="fas fa-calculator me-2"></i> Basic Grading Calculator</h5>
                </div>
                <div class="card-body">
                <form id="basicGradingForm">
                    <div class="mb-3">
                        <label for="written_works" class="form-label">Written Works (30%)</label>
                        <input type="number" class="form-control" id="written_works" name="written_works" min="0" max="100" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="performance_tasks" class="form-label">Performance Tasks (50%)</label>
                        <input type="number" class="form-control" id="performance_tasks" name="performance_tasks" min="0" max="100" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quarterly_assessment" class="form-label">Quarterly Assessment (20%)</label>
                        <input type="number" class="form-control" id="quarterly_assessment" name="quarterly_assessment" min="0" max="100" step="0.01" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="button" class="btn btn-success" id="calculateGradeBtn">
                            <i class="fas fa-calculator me-1"></i> Calculate Grade
                        </button>
                    </div>
                    
                    <div class="mt-4 result-container d-none">
                        <div class="alert alert-info">
                            <h5 class="alert-heading">Grade Calculation Result</h5>
                    <div class="row">
                        <div class="col-md-6">
                                    <p class="mb-1"><strong>Initial Grade:</strong> <span id="initialGrade">0.00</span></p>
                                    <p class="mb-1"><strong>Transmuted Grade:</strong> <span id="transmutedGrade">0.00</span></p>
                        </div>
                        <div class="col-md-6">
                                    <p class="mb-1"><strong>Remarks:</strong> <span id="remarks">N/A</span></p>
                                    <p class="mb-1"><strong>Letter Grade:</strong> <span id="letterGrade">N/A</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation for Enter Grades form
document.addEventListener('DOMContentLoaded', function() {
    // Enter Grades form validation
    const enterGradesForm = document.getElementById('enterGradesForm');
    if (enterGradesForm) {
        enterGradesForm.addEventListener('submit', function(event) {
            if (!validateForm('enterGradesForm')) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }
    
    // View Grades form validation
    const viewGradesForm = document.getElementById('viewGradesForm');
    if (viewGradesForm) {
        viewGradesForm.addEventListener('submit', function(event) {
            if (!validateForm('viewGradesForm')) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }
    
    // Basic Grading Calculator
    const calculateGradeBtn = document.getElementById('calculateGradeBtn');
    if (calculateGradeBtn) {
        calculateGradeBtn.addEventListener('click', function() {
            calculateGrade();
        });
        
        // Add enter key support
        document.getElementById('basicGradingForm').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                calculateGrade();
            }
        });
    }
    
    // Calculate grade function
    function calculateGrade() {
        const writtenWorks = parseFloat(document.getElementById('written_works').value) || 0;
        const performanceTasks = parseFloat(document.getElementById('performance_tasks').value) || 0;
        const quarterlyAssessment = parseFloat(document.getElementById('quarterly_assessment').value) || 0;
        
        // Validate inputs
        if (writtenWorks < 0 || writtenWorks > 100 || 
            performanceTasks < 0 || performanceTasks > 100 || 
            quarterlyAssessment < 0 || quarterlyAssessment > 100) {
            alert('Please enter valid scores between 0 and 100.');
            return;
        }
        
        // Calculate weighted grade
        const initialGrade = (writtenWorks * 0.3) + (performanceTasks * 0.5) + (quarterlyAssessment * 0.2);
        const roundedGrade = Math.round(initialGrade * 100) / 100; // Round to 2 decimal places
        
        // Transmute grade to 100-point scale
        let transmutedGrade = 0;
        if (roundedGrade >= 96) transmutedGrade = 100;
        else if (roundedGrade >= 90) transmutedGrade = 95;
        else if (roundedGrade >= 85) transmutedGrade = 90;
        else if (roundedGrade >= 80) transmutedGrade = 85;
        else if (roundedGrade >= 75) transmutedGrade = 80;
        else if (roundedGrade >= 70) transmutedGrade = 75;
        else if (roundedGrade >= 65) transmutedGrade = 70;
        else if (roundedGrade >= 60) transmutedGrade = 65;
        else if (roundedGrade >= 55) transmutedGrade = 60;
        else if (roundedGrade >= 50) transmutedGrade = 55;
        else if (roundedGrade >= 45) transmutedGrade = 50;
        else if (roundedGrade >= 40) transmutedGrade = 45;
        else if (roundedGrade >= 35) transmutedGrade = 40;
        else if (roundedGrade >= 30) transmutedGrade = 35;
        else if (roundedGrade >= 25) transmutedGrade = 30;
        else if (roundedGrade >= 20) transmutedGrade = 25;
        else if (roundedGrade >= 15) transmutedGrade = 20;
        else if (roundedGrade >= 10) transmutedGrade = 15;
        else if (roundedGrade >= 5) transmutedGrade = 10;
        else transmutedGrade = 5;
        
        // Determine remarks and letter grade
        let remarks = '';
        let letterGrade = '';
        
        if (transmutedGrade >= 75) {
            remarks = 'Passed';
            if (transmutedGrade >= 90) letterGrade = 'A';
            else if (transmutedGrade >= 85) letterGrade = 'B+';
            else if (transmutedGrade >= 80) letterGrade = 'B';
            else letterGrade = 'C';
        } else {
            remarks = 'Failed';
            letterGrade = 'F';
        }
        
        // Display results
        document.getElementById('initialGrade').textContent = roundedGrade.toFixed(2);
        document.getElementById('transmutedGrade').textContent = transmutedGrade.toFixed(0);
        document.getElementById('remarks').textContent = remarks;
        document.getElementById('letterGrade').textContent = letterGrade;
        
        // Show result container
        document.querySelector('.result-container').classList.remove('d-none');
    }
    
    // Form validation function
    function validateForm(formId) {
        const form = document.getElementById(formId);
        let isValid = true;
        
        // Check required fields
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
            
            // Add event listener to remove invalid class when user enters data
            field.addEventListener('change', function() {
                if (this.value) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                }
            });
        });
        
        return isValid;
    }
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
<?php
$title = 'Check Grades URL';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'teacher', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit();
}

// Get a sample subject
$subject_query = "SELECT id FROM subjects LIMIT 1";
$subject_result = mysqli_query($conn, $subject_query);
$subject_id = mysqli_fetch_assoc($subject_result)['id'] ?? 1;

// Get a sample section
$section_query = "SELECT DISTINCT section FROM students LIMIT 1";
$section_result = mysqli_query($conn, $section_query);
$section = mysqli_fetch_assoc($section_result)['section'] ?? 'Sample-Section';

$school_year = '2023-2024';
$semester = 'First';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Check Grades URL Parameters</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>modules/academics/grading.php">Grading System</a></li>
        <li class="breadcrumb-item active">Check Grades URL</li>
    </ol>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Test Form Submission</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="grading.php" id="testForm">
                        <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                        <input type="hidden" name="section" value="<?php echo $section; ?>">
                        <input type="hidden" name="school_year" value="<?php echo $school_year; ?>">
                        <input type="hidden" name="semester" value="<?php echo $semester; ?>">
                        
                        <div class="mb-3">
                            <p><strong>Subject ID:</strong> <?php echo $subject_id; ?></p>
                            <p><strong>Section:</strong> <?php echo $section; ?></p>
                            <p><strong>School Year:</strong> <?php echo $school_year; ?></p>
                            <p><strong>Semester:</strong> <?php echo $semester; ?></p>
                        </div>
                        
                        <button type="submit" name="enter_grades" class="btn btn-primary mb-2">
                            <i class="fas fa-pen me-1"></i> Test Enter Grades
                        </button>
                        <br>
                        <button type="submit" name="view_grades" class="btn btn-info">
                            <i class="fas fa-search me-1"></i> Test View Grades
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Direct URL Tests</h5>
                </div>
                <div class="card-body">
                    <h5>Enter Grades URLs:</h5>
                    <p>
                        <a href="enter_grades.php?subject=<?php echo $subject_id; ?>&section=<?php echo urlencode($section); ?>&year=<?php echo urlencode($school_year); ?>&semester=<?php echo urlencode($semester); ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-2">
                            Test Relative URL
                        </a>
                    </p>
                    <p>
                        <a href="<?php echo BASE_URL; ?>modules/academics/enter_grades.php?subject=<?php echo $subject_id; ?>&section=<?php echo urlencode($section); ?>&year=<?php echo urlencode($school_year); ?>&semester=<?php echo urlencode($semester); ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-2">
                            Test BASE_URL
                        </a>
                    </p>
                    
                    <h5 class="mt-4">View Grades URLs:</h5>
                    <p>
                        <a href="view_grades.php?subject=<?php echo $subject_id; ?>&section=<?php echo urlencode($section); ?>&year=<?php echo urlencode($school_year); ?>&semester=<?php echo urlencode($semester); ?>" target="_blank" class="btn btn-sm btn-outline-info mb-2">
                            Test Relative URL
                        </a>
                    </p>
                    <p>
                        <a href="<?php echo BASE_URL; ?>modules/academics/view_grades.php?subject=<?php echo $subject_id; ?>&section=<?php echo urlencode($section); ?>&year=<?php echo urlencode($school_year); ?>&semester=<?php echo urlencode($semester); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                            Test BASE_URL
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">System Information</h5>
        </div>
        <div class="card-body">
            <h5>Configuration:</h5>
            <ul>
                <li><strong>BASE_URL:</strong> <?php echo htmlspecialchars(BASE_URL); ?></li>
                <li><strong>$relative_path:</strong> <?php echo htmlspecialchars($relative_path); ?></li>
                <li><strong>PHP_SELF:</strong> <?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?></li>
                <li><strong>REQUEST_URI:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></li>
            </ul>
            
            <h5>URL Encoding Tests:</h5>
            <ul>
                <li><strong>Raw section:</strong> <?php echo $section; ?></li>
                <li><strong>urlencode(section):</strong> <?php echo urlencode($section); ?></li>
                <li><strong>rawurlencode(section):</strong> <?php echo rawurlencode($section); ?></li>
            </ul>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
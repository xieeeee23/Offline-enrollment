<?php
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit;
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = showAlert('Student ID is required.', 'danger');
    redirect('modules/registrar/students.php');
    exit;
}

$student_id = (int) $_GET['id'];

// Get student data
$query = "SELECT s.*, 
          shsd.track, shsd.previous_school, shsd.previous_track, shsd.previous_strand,
          shsd.semester, shsd.school_year
          FROM students s
          LEFT JOIN senior_highschool_details shsd ON s.id = shsd.student_id
          WHERE s.id = ?
          ORDER BY shsd.id DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = showAlert('Student not found.', 'danger');
    redirect('modules/registrar/students.php');
    exit;
}

$student = mysqli_fetch_assoc($result);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semester = $_POST['semester'];
    $school_year = $_POST['school_year'];
    $grade_level = $_POST['grade_level'];
    $strand = $_POST['strand'];
    $section = $_POST['section'];
    $enrollment_status = $_POST['enrollment_status'];
    
    // Update senior_highschool_details
    $shs_query = "UPDATE senior_highschool_details 
                 SET semester = ?, school_year = ? 
                 WHERE student_id = ?";
    $shs_stmt = mysqli_prepare($conn, $shs_query);
    mysqli_stmt_bind_param($shs_stmt, "ssi", $semester, $school_year, $student_id);
    
    if (mysqli_stmt_execute($shs_stmt)) {
        // Add to enrollment history
        $notes = "Updated semester to $semester and school year to $school_year";
        
        $enrollment_history_data = [
            'student_id' => $student_id,
            'school_year' => $school_year,
            'semester' => $semester,
            'grade_level' => $grade_level,
            'strand' => $strand,
            'section' => $section,
            'enrollment_status' => $enrollment_status,
            'date_enrolled' => date('Y-m-d'),
            'enrolled_by' => $_SESSION['user_id'],
            'notes' => $notes
        ];
        
        $history_result = safeInsert('enrollment_history', $enrollment_history_data, [
            'entity_name' => 'enrollment history',
            'log_action' => true
        ]);
        
        if ($history_result['success']) {
            $_SESSION['alert'] = showAlert('Student enrollment history updated successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error updating enrollment history: ' . $history_result['message'], 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('Error updating student details: ' . mysqli_error($conn), 'danger');
    }
    
    redirect('modules/registrar/view_enrollment_history.php?id=' . $student_id);
    exit;
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Update Enrollment History</h1>
        <a href="<?php echo $relative_path; ?>modules/registrar/view_enrollment_history.php?id=<?php echo $student_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Enrollment History
        </a>
    </div>

    <?php 
    if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    }
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Update Enrollment Details for <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="semester">Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="semester" name="semester" required>
                                <option value="First" <?php echo ($student['semester'] === 'First') ? 'selected' : ''; ?>>First Semester</option>
                                <option value="Second" <?php echo ($student['semester'] === 'Second') ? 'selected' : ''; ?>>Second Semester</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="school_year">School Year <span class="text-danger">*</span></label>
                            <select class="form-control" id="school_year" name="school_year" required>
                                <?php
                                // Get all school years from the school_years table
                                $query = "SELECT school_year FROM school_years ORDER BY school_year DESC";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $selected = ($student['school_year'] === $row['school_year']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['school_year']) . "' $selected>" . htmlspecialchars($row['school_year']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="grade_level">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="grade_level" name="grade_level" required>
                                <option value="Grade 11" <?php echo ($student['grade_level'] === 'Grade 11') ? 'selected' : ''; ?>>Grade 11</option>
                                <option value="Grade 12" <?php echo ($student['grade_level'] === 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="strand">Strand</label>
                            <input type="text" class="form-control" id="strand" name="strand" value="<?php echo htmlspecialchars($student['strand']); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="section">Section</label>
                            <input type="text" class="form-control" id="section" name="section" value="<?php echo htmlspecialchars($student['section']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="enrollment_status">Enrollment Status <span class="text-danger">*</span></label>
                    <select class="form-control" id="enrollment_status" name="enrollment_status" required>
                        <option value="enrolled" <?php echo ($student['enrollment_status'] === 'enrolled') ? 'selected' : ''; ?>>Enrolled</option>
                        <option value="pending" <?php echo ($student['enrollment_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="withdrawn" <?php echo ($student['enrollment_status'] === 'withdrawn') ? 'selected' : ''; ?>>Withdrawn</option>
                        <option value="irregular" <?php echo ($student['enrollment_status'] === 'irregular') ? 'selected' : ''; ?>>Irregular</option>
                        <option value="graduated" <?php echo ($student['enrollment_status'] === 'graduated') ? 'selected' : ''; ?>>Graduated</option>
                    </select>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update Enrollment History</button>
                    <a href="<?php echo $relative_path; ?>modules/registrar/view_enrollment_history.php?id=<?php echo $student_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
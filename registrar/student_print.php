<?php
$title = 'Student Profile';
$page_header = 'Student Profile';
$relative_path = '../../';
require_once $relative_path . 'includes/report_header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'registrar', 'teacher'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit();
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Validate student ID
if (!$student_id) {
    $_SESSION['alert'] = showAlert('Invalid student ID.', 'danger');
    redirect($relative_path . 'modules/registrar/students.php');
    exit();
}

// Get student data
$student_query = "SELECT s.*, 
                  CONCAT(s.last_name, ', ', s.first_name, ' ', SUBSTRING(s.middle_name, 1, 1), '.') AS full_name,
                  g.name AS grade_level_name, 
                  st.name AS strand_name
                  FROM students s 
                  LEFT JOIN grade_levels g ON s.grade_level = g.id
                  LEFT JOIN strands st ON s.strand = st.id
                  WHERE s.id = ?";
$stmt = mysqli_prepare($conn, $student_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    $_SESSION['alert'] = showAlert('Student not found.', 'danger');
    redirect($relative_path . 'modules/registrar/students.php');
    exit();
}

$student = mysqli_fetch_assoc($result);

// Get enrollment history
$history_query = "SELECT eh.*, g.name AS grade_level_name, st.name AS strand_name
                 FROM enrollment_history eh
                 LEFT JOIN grade_levels g ON eh.grade_level = g.id
                 LEFT JOIN strands st ON eh.strand = st.id
                 WHERE eh.student_id = ?
                 ORDER BY eh.school_year DESC, eh.semester DESC";
$stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$history_result = mysqli_stmt_get_result($stmt);

$enrollment_history = [];
if ($history_result && mysqli_num_rows($history_result) > 0) {
    while ($row = mysqli_fetch_assoc($history_result)) {
        $enrollment_history[] = $row;
    }
}

// Get student requirements
$requirements_query = "SELECT r.*, rt.name AS requirement_type
                      FROM requirements r
                      JOIN requirement_types rt ON r.requirement_type_id = rt.id
                      WHERE r.student_id = ?
                      ORDER BY rt.name";
$stmt = mysqli_prepare($conn, $requirements_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$requirements_result = mysqli_stmt_get_result($stmt);

$requirements = [];
if ($requirements_result && mysqli_num_rows($requirements_result) > 0) {
    while ($row = mysqli_fetch_assoc($requirements_result)) {
        $requirements[] = $row;
    }
}

// Get irregular status and back subjects if applicable
$irregular_query = "SELECT i.*, s.code AS subject_code, s.name AS subject_name
                   FROM irregular_students i
                   JOIN subjects s ON i.subject_id = s.id
                   WHERE i.student_id = ?";
$stmt = mysqli_prepare($conn, $irregular_query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$irregular_result = mysqli_stmt_get_result($stmt);

$back_subjects = [];
$is_irregular = mysqli_num_rows($irregular_result) > 0;

if ($is_irregular) {
    while ($row = mysqli_fetch_assoc($irregular_result)) {
        $back_subjects[] = $row;
    }
}

// Format date function
function formatDate($date) {
    return $date ? date('F d, Y', strtotime($date)) : 'N/A';
}

// Get student photo
$photo_path = $relative_path . 'assets/images/default-user.png';
if (!empty($student['photo']) && file_exists($relative_path . $student['photo'])) {
    $photo_path = $relative_path . $student['photo'];
}
?>

<div class="container-fluid px-4 py-4">
    <div class="text-center mb-4">
        <h2 class="report-title">STUDENT PROFILE</h2>
        <h4 class="report-subtitle">School Year: <?php echo $student['school_year']; ?> - <?php echo $student['semester']; ?> Semester</h4>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3 text-center">
            <img src="<?php echo $photo_path; ?>" alt="Student Photo" class="img-thumbnail student-photo mb-3" style="max-width: 150px;">
        </div>
        <div class="col-md-9">
            <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
            <h5>
                <?php echo htmlspecialchars($student['student_id']); ?>
                <?php if ($is_irregular): ?>
                <span class="badge bg-warning text-dark">Irregular</span>
                <?php endif; ?>
            </h5>
            <p class="mb-1">
                <strong>Grade Level:</strong> <?php echo htmlspecialchars($student['grade_level_name']); ?>
            </p>
            <p class="mb-1">
                <strong>Section:</strong> <?php echo htmlspecialchars($student['section']); ?>
            </p>
            <p class="mb-1">
                <strong>Strand:</strong> <?php echo htmlspecialchars($student['strand_name']); ?>
            </p>
            <p class="mb-1">
                <strong>Status:</strong> <?php echo htmlspecialchars($student['status']); ?>
            </p>
            <p class="mb-1">
                <strong>Date Enrolled:</strong> <?php echo formatDate($student['date_enrolled']); ?>
            </p>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Date of Birth:</strong> <?php echo formatDate($student['birthdate']); ?></p>
                            <p class="mb-1"><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
                            <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($student['address']); ?></p>
                            <p class="mb-1"><strong>Contact Number:</strong> <?php echo htmlspecialchars($student['contact_number']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>LRN:</strong> <?php echo htmlspecialchars($student['lrn']); ?></p>
                            <p class="mb-1"><strong>Religion:</strong> <?php echo htmlspecialchars($student['religion']); ?></p>
                            <p class="mb-1"><strong>Nationality:</strong> <?php echo htmlspecialchars($student['nationality']); ?></p>
                            <p class="mb-1"><strong>Mother Tongue:</strong> <?php echo htmlspecialchars($student['mother_tongue']); ?></p>
                            <p class="mb-1"><strong>Indigenous Group:</strong> <?php echo htmlspecialchars($student['indigenous_group']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Family Information</h5>
                </div>
                <div class="card-body">
                    <h6>Father's Information</h6>
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($student['father_name']); ?></p>
                    <p class="mb-1"><strong>Occupation:</strong> <?php echo htmlspecialchars($student['father_occupation']); ?></p>
                    <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($student['father_contact']); ?></p>
                    
                    <h6 class="mt-3">Mother's Information</h6>
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($student['mother_name']); ?></p>
                    <p class="mb-1"><strong>Occupation:</strong> <?php echo htmlspecialchars($student['mother_occupation']); ?></p>
                    <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($student['mother_contact']); ?></p>
                    
                    <h6 class="mt-3">Guardian's Information</h6>
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($student['guardian_name']); ?></p>
                    <p class="mb-1"><strong>Relationship:</strong> <?php echo htmlspecialchars($student['guardian_relationship']); ?></p>
                    <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($student['guardian_contact']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Educational Background</h5>
                </div>
                <div class="card-body">
                    <h6>Previous School Information</h6>
                    <p class="mb-1"><strong>Elementary School:</strong> <?php echo htmlspecialchars($student['elementary_school']); ?></p>
                    <p class="mb-1"><strong>Year Graduated:</strong> <?php echo htmlspecialchars($student['elementary_year_graduated']); ?></p>
                    
                    <p class="mb-1"><strong>Junior High School:</strong> <?php echo htmlspecialchars($student['junior_high_school']); ?></p>
                    <p class="mb-1"><strong>Year Graduated:</strong> <?php echo htmlspecialchars($student['junior_high_year_graduated']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($enrollment_history)): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Enrollment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>School Year</th>
                                    <th>Semester</th>
                                    <th>Grade Level</th>
                                    <th>Section</th>
                                    <th>Strand</th>
                                    <th>Status</th>
                                    <th>Date Enrolled</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollment_history as $history): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($history['school_year']); ?></td>
                                    <td><?php echo htmlspecialchars($history['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($history['grade_level_name']); ?></td>
                                    <td><?php echo htmlspecialchars($history['section']); ?></td>
                                    <td><?php echo htmlspecialchars($history['strand_name']); ?></td>
                                    <td><?php echo htmlspecialchars($history['status']); ?></td>
                                    <td><?php echo formatDate($history['date_enrolled']); ?></td>
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
    
    <?php if ($is_irregular): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Irregular Status - Back Subjects</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Subject Code</th>
                                    <th>Subject Name</th>
                                    <th>School Year</th>
                                    <th>Semester</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($back_subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['school_year']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['remarks']); ?></td>
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
    
    <?php if (!empty($requirements)): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Requirements Submitted</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Requirement</th>
                                    <th>Status</th>
                                    <th>Date Submitted</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requirements as $req): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($req['requirement_type']); ?></td>
                                    <td><?php echo htmlspecialchars($req['status']); ?></td>
                                    <td><?php echo formatDate($req['date_submitted']); ?></td>
                                    <td><?php echo htmlspecialchars($req['notes']); ?></td>
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
    
    <div class="row mt-5">
        <div class="col-md-6">
            <p>_______________________________</p>
            <p>Student Signature</p>
        </div>
        <div class="col-md-6 text-end">
            <p>_______________________________</p>
            <p>Registrar Signature</p>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-12 text-center">
            <p><small>Printed on: <?php echo date('F d, Y h:i A'); ?></small></p>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        font-size: 12pt;
    }
    
    .container-fluid {
        width: 100%;
        padding: 0;
    }
    
    .card {
        border: 1px solid #ddd !important;
        margin-bottom: 20px !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
        padding: 10px !important;
    }
    
    .table {
        width: 100% !important;
        font-size: 10pt !important;
    }
    
    .student-photo {
        max-width: 100px !important;
    }
    
    .badge {
        border: 1px solid #000;
        padding: 3px 6px;
    }
    
    .badge.bg-warning {
        background-color: #fff !important;
        color: #000 !important;
    }
    
    .report-title {
        font-size: 18pt !important;
        font-weight: bold !important;
        margin-bottom: 5px !important;
    }
    
    .report-subtitle {
        font-size: 14pt !important;
        margin-bottom: 20px !important;
    }
}
</style>

<script>
// Automatically print when page loads
window.onload = function() {
    window.print();
};
</script>

<?php require_once $relative_path . 'includes/report_footer.php'; ?> 
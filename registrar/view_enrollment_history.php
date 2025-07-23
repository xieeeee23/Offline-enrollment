<?php
$title = 'Student Enrollment History';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

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

// Check if enrollment_history table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'enrollment_history'");
if (mysqli_num_rows($table_check) === 0) {
    // Create the enrollment_history table
    $create_table_query = "CREATE TABLE enrollment_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        strand VARCHAR(50),
        section VARCHAR(50) NOT NULL,
        enrollment_status VARCHAR(20) NOT NULL,
        date_enrolled DATE NOT NULL,
        enrolled_by INT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        $_SESSION['alert'] = showAlert('Error creating enrollment history table: ' . mysqli_error($conn), 'danger');
    }
}

// Get enrollment history
$history_query = "SELECT eh.*, u.name as enrolled_by_name 
                 FROM enrollment_history eh 
                 LEFT JOIN users u ON eh.enrolled_by = u.id 
                 WHERE eh.student_id = ? 
                 ORDER BY eh.created_at DESC";
$history_stmt = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($history_stmt, "i", $student_id);
mysqli_stmt_execute($history_stmt);
$history_result = mysqli_stmt_get_result($history_stmt);

// Get current enrollment from senior_highschool_details
$current_query = "SELECT * FROM senior_highschool_details WHERE student_id = ? ORDER BY id DESC LIMIT 1";
$current_stmt = mysqli_prepare($conn, $current_query);
mysqli_stmt_bind_param($current_stmt, "i", $student_id);
mysqli_stmt_execute($current_stmt);
$current_result = mysqli_stmt_get_result($current_stmt);
$current_enrollment = mysqli_fetch_assoc($current_result);

// Get the most recent enrollment history record to ensure consistency
$recent_history_query = "SELECT * FROM enrollment_history WHERE student_id = ? ORDER BY date_enrolled DESC, id DESC LIMIT 1";
$recent_history_stmt = mysqli_prepare($conn, $recent_history_query);
mysqli_stmt_bind_param($recent_history_stmt, "i", $student_id);
mysqli_stmt_execute($recent_history_stmt);
$recent_history_result = mysqli_stmt_get_result($recent_history_stmt);
$recent_history = mysqli_fetch_assoc($recent_history_result);

// Use the most recent enrollment history data if available
if ($recent_history) {
    $current_enrollment['semester'] = $recent_history['semester'];
    $current_enrollment['school_year'] = $recent_history['school_year'];
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Student Enrollment History</h1>
        <div>
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
            <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
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
                    <?php if (!empty($current_enrollment)): ?>
                    <p class="mb-1"><strong>Current School Year:</strong> <?php echo htmlspecialchars($current_enrollment['school_year']); ?></p>
                    <p class="mb-1"><strong>Current Semester:</strong> <?php echo htmlspecialchars($current_enrollment['semester']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Enrollment History</h6>
            <button class="btn btn-sm btn-outline-primary" onclick="printHistory()">
                <i class="fas fa-print fa-sm me-1"></i> Print History
            </button>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($history_result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="historyTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>School Year</th>
                                <th>Semester</th>
                                <th>Grade Level</th>
                                <th>Strand</th>
                                <th>Section</th>
                                <th>Status</th>
                                <th>Processed By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($history = mysqli_fetch_assoc($history_result)): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($history['date_enrolled'])); ?></td>
                                    <td><?php echo htmlspecialchars($history['school_year']); ?></td>
                                    <td><?php echo htmlspecialchars($history['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($history['grade_level']); ?></td>
                                    <td><?php echo htmlspecialchars($history['strand']); ?></td>
                                    <td><?php echo htmlspecialchars($history['section']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            switch($history['enrollment_status']) {
                                                case 'enrolled': echo 'bg-success'; break;
                                                case 'pending': echo 'bg-warning text-dark'; break;
                                                case 'withdrawn': echo 'bg-danger'; break;
                                                case 'irregular': echo 'bg-warning'; break;
                                                case 'graduated': echo 'bg-info'; break;
                                                default: echo 'bg-secondary';
                                            }
                                        ?>">
                                            <?php echo ucfirst($history['enrollment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($history['enrolled_by_name'] ?? 'System'); ?></td>
                                    <td><?php echo htmlspecialchars($history['notes']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No enrollment history found for this student.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?>

<script>
function printHistory() {
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Write the print content
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Enrollment History - ${<?php echo json_encode($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?>}</title>
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
                <div class="page-title">STUDENT ENROLLMENT HISTORY</div>
            </div>
            
            <div class="student-info">
                <p><strong>Student Name:</strong> ${<?php echo json_encode($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?>}</p>
                <p><strong>LRN:</strong> ${<?php echo json_encode($student['lrn']); ?>}</p>
                <p><strong>Current Grade Level:</strong> ${<?php echo json_encode($student['grade_level']); ?>}</p>
                <p><strong>Current Section:</strong> ${<?php echo json_encode($student['section']); ?>}</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>School Year</th>
                        <th>Semester</th>
                        <th>Grade Level</th>
                        <th>Strand</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    ${document.querySelectorAll('#historyTable tbody tr').length > 0 ? 
                      Array.from(document.querySelectorAll('#historyTable tbody tr')).map(row => {
                        const cells = Array.from(row.cells);
                        return `<tr>
                            <td>${cells[0].textContent}</td>
                            <td>${cells[1].textContent}</td>
                            <td>${cells[2].textContent}</td>
                            <td>${cells[3].textContent}</td>
                            <td>${cells[4].textContent}</td>
                            <td>${cells[5].textContent}</td>
                            <td>${cells[6].textContent}</td>
                            <td>${cells[8].textContent}</td>
                        </tr>`;
                      }).join('') : 
                      '<tr><td colspan="8" class="text-center">No enrollment history found</td></tr>'
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
<?php
$title = 'Test Print Functionality';
$relative_path = './';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'registrar', 'teacher'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Test Print Functionality</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Click the links below to test different print filters:</p>
                    
                    <h6 class="fw-bold">Print by School Year</h6>
                    <div class="mb-3">
                        <?php
                        // Get available school years
                        $query = "SELECT DISTINCT school_year FROM students ORDER BY school_year DESC";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $school_year = $row['school_year'];
                            echo '<a href="modules/registrar/students_print.php?school_year=' . urlencode($school_year) . '&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">';
                            echo $school_year;
                            echo '</a>';
                        }
                        ?>
                    </div>
                    
                    <h6 class="fw-bold">Print by Semester</h6>
                    <div class="mb-3">
                        <a href="modules/registrar/students_print.php?semester=First&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">First Semester</a>
                        <a href="modules/registrar/students_print.php?semester=Second&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">Second Semester</a>
                    </div>
                    
                    <h6 class="fw-bold">Print by Status</h6>
                    <div class="mb-3">
                        <a href="modules/registrar/students_print.php?status=enrolled&show_summary=1" target="_blank" class="btn btn-sm btn-outline-success me-2 mb-2">Enrolled</a>
                        <a href="modules/registrar/students_print.php?status=pending&show_summary=1" target="_blank" class="btn btn-sm btn-outline-warning me-2 mb-2">Pending</a>
                        <a href="modules/registrar/students_print.php?status=withdrawn&show_summary=1" target="_blank" class="btn btn-sm btn-outline-danger me-2 mb-2">Withdrawn</a>
                        <a href="modules/registrar/students_print.php?status=irregular&show_summary=1" target="_blank" class="btn btn-sm btn-outline-secondary me-2 mb-2">Irregular</a>
                        <a href="modules/registrar/students_print.php?status=graduated&show_summary=1" target="_blank" class="btn btn-sm btn-outline-info me-2 mb-2">Graduated</a>
                    </div>
                    
                    <h6 class="fw-bold">Print by Student Type</h6>
                    <div class="mb-3">
                        <a href="modules/registrar/students_print.php?student_type=new&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">New Students</a>
                        <a href="modules/registrar/students_print.php?student_type=old&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">Old Students</a>
                    </div>
                    
                    <h6 class="fw-bold">Print by Voucher Status</h6>
                    <div class="mb-3">
                        <a href="modules/registrar/students_print.php?has_voucher=1&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">With Voucher</a>
                        <a href="modules/registrar/students_print.php?has_voucher=0&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">Without Voucher</a>
                    </div>
                    
                    <h6 class="fw-bold">Combined Filters</h6>
                    <div class="mb-3">
                        <a href="modules/registrar/students_print.php?grade=Grade 11&status=enrolled&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">Grade 11 Enrolled</a>
                        <a href="modules/registrar/students_print.php?grade=Grade 12&status=enrolled&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">Grade 12 Enrolled</a>
                        <a href="modules/registrar/students_print.php?status=enrolled&has_voucher=1&show_summary=1" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">Enrolled with Voucher</a>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="modules/registrar/students.php" class="btn btn-secondary">Back to Students</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
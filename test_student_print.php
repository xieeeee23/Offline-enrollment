<?php
$title = 'Test Student Print';
$relative_path = './';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'registrar', 'teacher'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit();
}

// Get a list of students for testing
$query = "SELECT id, CONCAT(last_name, ', ', first_name, ' ', 
          IF(middle_name IS NULL OR middle_name = '', '', CONCAT(LEFT(middle_name, 1), '.'))) AS full_name, 
          lrn, grade_level, section, enrollment_status 
          FROM students 
          ORDER BY last_name, first_name 
          LIMIT 10";
$result = mysqli_query($conn, $query);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Test Student Print Functionality</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Click on a student to test the individual student print functionality:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Name</th>
                                    <th>Grade Level</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($student = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['grade_level']); ?></td>
                                            <td><?php echo htmlspecialchars($student['section']); ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($student['enrollment_status'])); ?></td>
                                            <td>
                                                <a href="modules/registrar/student_print.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-success" target="_blank" title="Print Student Profile">
                                                    <i class="fas fa-print"></i> Print Profile
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No students found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
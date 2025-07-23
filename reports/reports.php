<?php
$title = 'Reports';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Get statistics for dashboard
$total_students = 0;
$total_teachers = 0;
$total_sections = 0;

// Count students
$query = "SELECT COUNT(*) as count FROM students";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_students = $row['count'];
}

// Count teachers
$query = "SELECT COUNT(*) as count FROM teachers";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_teachers = $row['count'];
}

// Count unique sections
$query = "SELECT COUNT(DISTINCT CONCAT(grade_level, section)) as count FROM students";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_sections = $row['count'];
}

// Get enrollment statistics by grade level
$enrollment_by_grade = [];
$query = "SELECT grade_level, COUNT(*) as count 
          FROM students 
          WHERE enrollment_status = 'enrolled' 
          GROUP BY grade_level 
          ORDER BY grade_level";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $enrollment_by_grade[] = $row;
    }
}

// Get enrollment statistics by section
$enrollment_by_section = [];
$query = "SELECT grade_level, section, COUNT(*) as count 
          FROM students 
          WHERE enrollment_status = 'enrolled' 
          GROUP BY grade_level, section 
          ORDER BY grade_level, section";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $enrollment_by_section[] = $row;
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Reports</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="dashboard-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h5 class="card-title">Total Students</h5>
                <h2 class="card-text"><?php echo $total_students; ?></h2>
                <a href="<?php echo $relative_path; ?>modules/reports/student_list.php" class="btn btn-sm btn-primary">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="dashboard-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h5 class="card-title">Total Teachers</h5>
                <h2 class="card-text"><?php echo $total_teachers; ?></h2>
                <a href="<?php echo $relative_path; ?>modules/reports/teacher_list.php" class="btn btn-sm btn-primary">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="dashboard-icon">
                    <i class="fas fa-users-class"></i>
                </div>
                <h5 class="card-title">Total Sections</h5>
                <h2 class="card-text"><?php echo $total_sections; ?></h2>
                <a href="<?php echo $relative_path; ?>modules/registrar/manage_sections.php" class="btn btn-sm btn-primary">View Sections</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Available Reports</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="list-group mb-4">
                            <div class="list-group-item list-group-item-primary">
                                <h5 class="mb-0">Student Reports</h5>
                            </div>
                            <a href="<?php echo $relative_path; ?>modules/reports/student_list.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                Student Master List
                                <span class="badge bg-primary rounded-pill"><?php echo $total_students; ?></span>
                            </a>
                            <a href="<?php echo $relative_path; ?>modules/reports/student_list.php?status=enrolled" class="list-group-item list-group-item-action">
                                Enrolled Students List
                            </a>
                            <a href="<?php echo $relative_path; ?>modules/reports/student_list.php?status=pending" class="list-group-item list-group-item-action">
                                Pending Enrollment List
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="list-group mb-4">
                            <div class="list-group-item list-group-item-primary">
                                <h5 class="mb-0">Teacher Reports</h5>
                            </div>
                            <a href="<?php echo $relative_path; ?>modules/reports/teacher_list.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                Teacher Master List
                                <span class="badge bg-primary rounded-pill"><?php echo $total_teachers; ?></span>
                            </a>
                            <a href="<?php echo $relative_path; ?>modules/reports/teacher_list.php?status=active" class="list-group-item list-group-item-action">
                                Active Teachers List
                            </a>
                        </div>
                        
                        <div class="list-group">
                            <div class="list-group-item list-group-item-primary">
                                <h5 class="mb-0">Schedule Reports</h5>
                            </div>
                            <a href="<?php echo $relative_path; ?>modules/reports/schedule_report.php" class="list-group-item list-group-item-action">
                                Class Schedule Report
                            </a>
                            <a href="<?php echo $relative_path; ?>modules/reports/schedule_report.php?day=Monday" class="list-group-item list-group-item-action">
                                Monday Schedule
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Enrollment by Grade Level</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Grade Level</th>
                                <th class="text-center">Number of Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($enrollment_by_grade)): ?>
                                <tr>
                                    <td colspan="2" class="text-center">No data available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($enrollment_by_grade as $grade): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade['grade_level']); ?></td>
                                        <td class="text-center"><?php echo $grade['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo $relative_path; ?>modules/reports/student_list.php" class="btn btn-primary">
                        <i class="fas fa-print"></i> Print Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Enrollment by Section</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Grade Level</th>
                                <th>Section</th>
                                <th class="text-center">Number of Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($enrollment_by_section)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No data available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($enrollment_by_section as $section): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($section['grade_level']); ?></td>
                                        <td><?php echo htmlspecialchars($section['section']); ?></td>
                                        <td class="text-center"><?php echo $section['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo $relative_path; ?>modules/reports/sections_list.php" class="btn btn-primary">
                        <i class="fas fa-print"></i> Print Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
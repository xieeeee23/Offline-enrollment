<?php
// Set the relative path for includes
$relative_path = '';

$title = 'Dashboard';
require_once 'includes/header.php';

// Check if user is logged in
if (!checkAccess()) {
    $_SESSION['alert'] = showAlert('You must log in to access this page.', 'danger');
    redirect('login.php');
}

// Get counts for dashboard cards
$students_count = 0;
$teachers_count = 0;
$users_count = 0;
$today_logs_count = 0;
$sections_count = 0;
$active_students = 0;

// Count students
$query = "SELECT COUNT(*) as count FROM students";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $students_count = $row['count'];
}

// Count active students
$query = "SELECT COUNT(*) as count FROM students WHERE enrollment_status = 'enrolled'";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $active_students = $row['count'];
}

// Count teachers
$query = "SELECT COUNT(*) as count FROM teachers";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $teachers_count = $row['count'];
}

// Count users
$query = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $users_count = $row['count'];
}

// Count sections
$query = "SELECT COUNT(DISTINCT section) as count FROM students";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $sections_count = $row['count'];
}

// Count today's logs
$query = "SELECT COUNT(*) as count FROM logs WHERE DATE(timestamp) = CURDATE()";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $today_logs_count = $row['count'];
}

// Get recent logs
$recent_logs = [];
$query = "SELECT l.*, u.name FROM logs l 
          LEFT JOIN users u ON l.user_id = u.id 
          ORDER BY l.timestamp DESC LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_logs[] = $row;
    }
}

// Get current user's full name
$user_fullname = $current_user['name'];

// Get time-based greeting
$hour = date('H');
$greeting = '';
if ($hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour < 18) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}

// Get current date formatted
$current_date = date('l, F j, Y');
?>

<div class="container-fluid">
    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>
    
    <?php if (isset($_SESSION['auto_school_year_notice'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-calendar-plus me-2"></i> <?php echo $_SESSION['auto_school_year_notice']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['auto_school_year_notice']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Dashboard</h1>
        </div>
    </div>

    <div class="welcome-card" data-aos="fade-up" data-aos-duration="1000">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-1"><?php echo $greeting; ?>, <?php echo htmlspecialchars($user_fullname); ?>!</h2>
                <p class="mb-0">Welcome to LocalEnroll Pro Dashboard - <?php echo htmlspecialchars($current_date); ?></p>
                <div class="d-block d-md-none mt-3">
                    <a href="<?php echo $relative_path; ?>profile.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-user-circle me-1"></i> My Profile
                    </a>
                </div>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <a href="<?php echo $relative_path; ?>profile.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-user-circle me-1"></i> My Profile
                </a>
                <span class="badge bg-light text-primary p-2 rounded-pill">
                    <i class="fas fa-user-circle me-1"></i> 
                    <?php echo ucfirst($_SESSION['role']); ?>
                </span>
            </div>
        </div>
    </div>

    <?php if ($_SESSION['role'] == 'admin' && function_exists('is_tcpdf_installed') && !is_tcpdf_installed()): ?>
    <div class="alert alert-warning" data-aos="fade-up" data-aos-delay="200">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
            </div>
            <div class="flex-grow-1">
                <h4 class="alert-heading">TCPDF Library Not Installed</h4>
                <p class="mb-2">The TCPDF library is required for PDF generation in reports. Please install it to enable PDF export functionality.</p>
                <a href="<?php echo $relative_path; ?>install_tcpdf.php" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i> Install TCPDF Library
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'registrar'): ?>
        <?php
        // Count all students (you already have this)
        $query = "SELECT COUNT(*) as count FROM students";
        $result = mysqli_query($conn, $query);
        $students_count = $result ? mysqli_fetch_assoc($result)['count'] : 0;

        // Count enrolled students (you already have this)
        $query = "SELECT COUNT(*) as count FROM students WHERE enrollment_status = 'enrolled'";
        $result = mysqli_query($conn, $query);
        $active_students = $result ? mysqli_fetch_assoc($result)['count'] : 0;

        // NEW: Count irregular students
        $query = "SELECT COUNT(*) as count FROM students WHERE enrollment_status = 'irregular'";
        $result = mysqli_query($conn, $query);
        $irregular_students = $result ? mysqli_fetch_assoc($result)['count'] : 0;
        ?>
        <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="dashboard-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h5 class="card-title">Students</h5>
                    <h2 class="card-text"><?php echo $students_count; ?></h2>
                    <div class="mt-2">
                        <span class="badge bg-success"><?php echo $active_students; ?> Enrolled</span>
                        <span class="badge bg-warning"><?php echo $irregular_students; ?> Irregular</span>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-cog"></i> Manage Students
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

        
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'registrar'): ?>
        <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="dashboard-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h5 class="card-title">Sections</h5>
                    <h2 class="card-text"><?php echo $sections_count; ?></h2>
                    <div class="mt-3">
                        <a href="<?php echo $relative_path; ?>modules/registrar/sections.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-cog"></i> Manage Sections
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="500">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="dashboard-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5 class="card-title">Users</h5>
                    <h2 class="card-text"><?php echo $users_count; ?></h2>
                    <div class="mt-3">
                        <a href="<?php echo $relative_path; ?>modules/admin/users.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-cog"></i> Manage Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'registrar'): ?>
        <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="800">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="dashboard-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h5 class="card-title">Requirements</h5>
                    <p class="card-text">Track student requirements</p>
                    <div class="mt-3">
                        <a href="<?php echo $relative_path; ?>modules/registrar/requirements.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-tasks"></i> Manage Requirements
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] == 'teacher'): ?>
        <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="dashboard-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h5 class="card-title">Teachers</h5>
                    <p class="card-text">View teacher information</p>
                    <div class="mt-3">
                        <a href="<?php echo $relative_path; ?>modules/teacher/teachers.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-users"></i> View Teachers
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="dashboard-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h5 class="card-title">My Schedule</h5>
                    <p class="card-text">View your teaching schedule</p>
                    <div class="mt-3">
                        <a href="<?php echo $relative_path; ?>modules/teacher/schedule.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-clock"></i> View Schedule
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>

    <?php if ($_SESSION['role'] == 'admin'): ?>
    <div class="row" data-aos="fade-up" data-aos-delay="900">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i> Recent Activity Logs</h5>
                    <a href="<?php echo $relative_path; ?>modules/admin/logs.php" class="btn btn-sm btn-light">
                        <i class="fas fa-list me-1"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_logs)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No recent logs found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_logs as $log): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2">
                                                        <span class="avatar-initials"><?php echo substr($log['name'] ?? 'U', 0, 1); ?></span>
                                                    </div>
                                                    <?php echo htmlspecialchars($log['name'] ?? 'Unknown'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                switch ($log['action']) {
                                                    case 'login':
                                                        $badge_class = 'bg-success';
                                                        break;
                                                    case 'logout':
                                                        $badge_class = 'bg-warning text-dark';
                                                        break;
                                                    case 'create':
                                                        $badge_class = 'bg-primary';
                                                        break;
                                                    case 'update':
                                                        $badge_class = 'bg-info';
                                                        break;
                                                    case 'delete':
                                                        $badge_class = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($log['action']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                                            <td><?php echo formatDate($log['timestamp'], 'M d, Y h:i A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 
<?php
$title = 'Setup Grading System';
$page_header = 'Setup Grading System';
$relative_path = './';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit();
}

// Define tables to be created
$tables = [
    'grading_periods' => "
        CREATE TABLE IF NOT EXISTS `grading_periods` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `period_number` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ",
    'subjects' => "
        CREATE TABLE IF NOT EXISTS `subjects` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(20) NOT NULL,
            `name` varchar(100) NOT NULL,
            `description` text,
            `units` int(11) NOT NULL DEFAULT '1',
            `grade_level` varchar(20) DEFAULT NULL,
            `strand` varchar(50) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ",
    'class_records' => "
        CREATE TABLE IF NOT EXISTS `class_records` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `subject_id` int(11) NOT NULL,
            `teacher_id` int(11) NOT NULL,
            `section` varchar(50) NOT NULL,
            `school_year` varchar(20) NOT NULL,
            `semester` varchar(20) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `subject_id` (`subject_id`),
            KEY `teacher_id` (`teacher_id`),
            CONSTRAINT `class_records_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
            CONSTRAINT `class_records_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ",
    'student_grades' => "
        CREATE TABLE IF NOT EXISTS `student_grades` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `student_id` int(11) NOT NULL,
            `subject_id` int(11) NOT NULL,
            `period_id` int(11) NOT NULL,
            `school_year` varchar(20) NOT NULL,
            `semester` varchar(20) NOT NULL,
            `written_work` decimal(5,2) NOT NULL DEFAULT '0.00',
            `performance_tasks` decimal(5,2) NOT NULL DEFAULT '0.00',
            `quarterly_assessment` decimal(5,2) NOT NULL DEFAULT '0.00',
            `final_grade` decimal(5,2) NOT NULL DEFAULT '0.00',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `student_subject_period` (`student_id`,`subject_id`,`period_id`,`school_year`,`semester`),
            KEY `subject_id` (`subject_id`),
            KEY `period_id` (`period_id`),
            CONSTRAINT `student_grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
            CONSTRAINT `student_grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
            CONSTRAINT `student_grades_ibfk_3` FOREIGN KEY (`period_id`) REFERENCES `grading_periods` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    "
];

// Default data to be inserted
$default_data = [
    'grading_periods' => [
        ['name' => 'First Quarter', 'period_number' => 1],
        ['name' => 'Second Quarter', 'period_number' => 2],
        ['name' => 'Third Quarter', 'period_number' => 3],
        ['name' => 'Fourth Quarter', 'period_number' => 4]
    ],
    'subjects' => [
        ['code' => 'MATH11', 'name' => 'General Mathematics', 'description' => 'Basic concepts of mathematics', 'units' => 3, 'grade_level' => 'Grade 11', 'strand' => 'STEM'],
        ['code' => 'SCI11', 'name' => 'Earth Science', 'description' => 'Study of the Earth and its processes', 'units' => 3, 'grade_level' => 'Grade 11', 'strand' => 'STEM'],
        ['code' => 'ENG11', 'name' => 'Oral Communication', 'description' => 'Development of speaking skills', 'units' => 3, 'grade_level' => 'Grade 11', 'strand' => 'All'],
        ['code' => 'FIL11', 'name' => 'Komunikasyon at Pananaliksik', 'description' => 'Filipino language and research', 'units' => 3, 'grade_level' => 'Grade 11', 'strand' => 'All'],
        ['code' => 'MATH12', 'name' => 'Statistics and Probability', 'description' => 'Basic concepts of statistics', 'units' => 3, 'grade_level' => 'Grade 12', 'strand' => 'STEM'],
        ['code' => 'SCI12', 'name' => 'Physics', 'description' => 'Study of matter and energy', 'units' => 3, 'grade_level' => 'Grade 12', 'strand' => 'STEM'],
        ['code' => 'ENG12', 'name' => 'Reading and Writing', 'description' => 'Development of literacy skills', 'units' => 3, 'grade_level' => 'Grade 12', 'strand' => 'All'],
        ['code' => 'FIL12', 'name' => 'Pagsulat sa Filipino', 'description' => 'Filipino writing skills', 'units' => 3, 'grade_level' => 'Grade 12', 'strand' => 'All']
    ]
];

// Process setup
$results = [];
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    // Create tables
    foreach ($tables as $table_name => $query) {
        $result = mysqli_query($conn, $query);
        $results[$table_name]['create'] = $result ? 'success' : 'error';
        $results[$table_name]['error'] = $result ? '' : mysqli_error($conn);
        
        if (!$result) {
            $success = false;
        }
    }
    
    // Insert default data if tables were created successfully
    if ($success) {
        // Insert grading periods
        $check_query = "SELECT COUNT(*) as count FROM grading_periods";
        $check_result = mysqli_query($conn, $check_query);
        $row = mysqli_fetch_assoc($check_result);
        
        if ($row['count'] == 0) {
            foreach ($default_data['grading_periods'] as $period) {
                $insert_query = "INSERT INTO grading_periods (name, period_number) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "si", $period['name'], $period['period_number']);
                $result = mysqli_stmt_execute($stmt);
                
                if (!$result) {
                    $results['grading_periods']['insert'] = 'error';
                    $results['grading_periods']['insert_error'] = mysqli_error($conn);
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                $results['grading_periods']['insert'] = 'success';
            }
        } else {
            $results['grading_periods']['insert'] = 'skipped';
        }
        
        // Insert subjects
        $check_query = "SELECT COUNT(*) as count FROM subjects";
        $check_result = mysqli_query($conn, $check_query);
        $row = mysqli_fetch_assoc($check_result);
        
        if ($row['count'] == 0) {
            foreach ($default_data['subjects'] as $subject) {
                $insert_query = "INSERT INTO subjects (code, name, description, units, grade_level, strand) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "ssssss", $subject['code'], $subject['name'], $subject['description'], $subject['units'], $subject['grade_level'], $subject['strand']);
                $result = mysqli_stmt_execute($stmt);
                
                if (!$result) {
                    $results['subjects']['insert'] = 'error';
                    $results['subjects']['insert_error'] = mysqli_error($conn);
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                $results['subjects']['insert'] = 'success';
            }
        } else {
            $results['subjects']['insert'] = 'skipped';
        }
    }
    
    // Set alert message
    if ($success) {
        $_SESSION['alert'] = showAlert('Grading system setup completed successfully!', 'success');
    } else {
        $_SESSION['alert'] = showAlert('There were errors during the setup process. Please check the results below.', 'danger');
    }
}

// Check if tables exist
$check_tables = [
    'grading_periods' => false,
    'subjects' => false,
    'class_records' => false,
    'student_grades' => false
];

foreach ($check_tables as $table => $exists) {
    $check_query = "SHOW TABLES LIKE '$table'";
    $check_result = mysqli_query($conn, $check_query);
    $check_tables[$table] = mysqli_num_rows($check_result) > 0;
}

$all_tables_exist = !in_array(false, $check_tables);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_header; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>modules/academics/grading.php">Grading System</a></li>
        <li class="breadcrumb-item active">Setup</li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])) echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-cogs me-2"></i> Grading System Setup</h5>
        </div>
        <div class="card-body">
            <?php if ($all_tables_exist): ?>
            <div class="alert alert-success">
                <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i> Setup Complete</h4>
                <p>All required tables for the grading system have been created.</p>
                <hr>
                <p class="mb-0">
                    <a href="<?php echo $relative_path; ?>modules/academics/grading.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right me-1"></i> Go to Grading System
                    </a>
                </p>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <h4 class="alert-heading"><i class="fas fa-info-circle me-2"></i> Setup Required</h4>
                <p>The grading system requires several database tables to be created. Click the button below to set up the necessary tables.</p>
                <hr>
                <form method="post" action="">
                    <button type="submit" name="setup" class="btn btn-primary">
                        <i class="fas fa-cogs me-1"></i> Run Setup
                    </button>
                </form>
            </div>
            
            <div class="mt-4">
                <h5>Tables Status</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($check_tables as $table => $exists): ?>
                        <tr>
                            <td><?php echo $table; ?></td>
                            <td>
                                <?php if ($exists): ?>
                                <span class="badge bg-success">Exists</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Missing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($results)): ?>
            <div class="mt-4">
                <h5>Setup Results</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Create</th>
                            <th>Insert Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $table => $result): ?>
                        <tr>
                            <td><?php echo $table; ?></td>
                            <td>
                                <?php if ($result['create'] === 'success'): ?>
                                <span class="badge bg-success">Success</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Error</span>
                                <br>
                                <small class="text-danger"><?php echo $result['error']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($result['insert'])): ?>
                                    <?php if ($result['insert'] === 'success'): ?>
                                    <span class="badge bg-success">Success</span>
                                    <?php elseif ($result['insert'] === 'skipped'): ?>
                                    <span class="badge bg-warning text-dark">Skipped (Data exists)</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Error</span>
                                    <br>
                                    <small class="text-danger"><?php echo $result['insert_error']; ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                <span class="badge bg-secondary">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i> Grading System Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Database Tables</h5>
                    <ul class="list-group mb-4">
                        <li class="list-group-item">
                            <strong>grading_periods</strong> - Stores grading periods (quarters, semesters)
                        </li>
                        <li class="list-group-item">
                            <strong>subjects</strong> - Stores subject information
                        </li>
                        <li class="list-group-item">
                            <strong>class_records</strong> - Stores class record information
                        </li>
                        <li class="list-group-item">
                            <strong>student_grades</strong> - Stores student grades for each subject and period
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h5>Default Data</h5>
                    <p>The setup will create the following default data:</p>
                    
                    <h6>Grading Periods</h6>
                    <ul>
                        <li>First Quarter</li>
                        <li>Second Quarter</li>
                        <li>Third Quarter</li>
                        <li>Fourth Quarter</li>
                    </ul>
                    
                    <h6>Sample Subjects</h6>
                    <p>8 sample subjects for Grade 11 and Grade 12</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
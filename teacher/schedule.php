<?php
$title = 'My Schedule';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Debug mode - set to true to see debug information
$debug_mode = false;

// Debug function
function debug_log($message, $data = null) {
    global $debug_mode;
    if ($debug_mode) {
        echo '<div class="alert alert-info">';
        echo '<strong>Debug:</strong> ' . htmlspecialchars($message);
        if ($data !== null) {
            echo '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';
        }
        echo '</div>';
    }
}

// Check if print view is requested
$print_view = isset($_GET['print']) && $_GET['print'] == 1;

// If print view, include a simplified header
if ($print_view) {
    // Override the header inclusion
    ob_clean(); // Clear the output buffer
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Teacher Schedule - <?php echo SYSTEM_NAME; ?></title>
        <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/bootstrap.min.css">
        <style>
            body {
                font-family: Arial, sans-serif;
            }
            .print-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .print-header h2 {
                margin-bottom: 5px;
            }
            .print-header p {
                margin-bottom: 0;
            }
            .schedule-table {
                width: 100%;
                border-collapse: collapse;
            }
            .schedule-table th, .schedule-table td {
                border: 1px solid #ddd;
                padding: 8px;
            }
            .schedule-table th {
                background-color: #f2f2f2;
            }
            .schedule-time {
                white-space: nowrap;
            }
            @media print {
                .no-print {
                    display: none;
                }
                body {
                    padding: 0;
                    margin: 0;
                }
                .container {
                    width: 100%;
                    max-width: 100%;
                    padding: 0;
                    margin: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="container mt-4">
            <div class="print-header">
                <h2><?php echo SYSTEM_NAME; ?></h2>
                <p>Teacher Schedule</p>
                <p><?php echo date('F d, Y'); ?></p>
            </div>
    <?php
}

// Check if user is logged in
if (!checkAccess()) {
    $_SESSION['alert'] = showAlert('You must log in to access this page.', 'danger');
    redirect('login.php');
}

// Get teacher ID for the logged-in user
$teacher_id = null;
$teacher = null;

if ($_SESSION['role'] === 'teacher') {
    // Get teacher record for the logged-in user
    $query = "SELECT * FROM teachers WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $teacher = mysqli_fetch_assoc($result);
        $teacher_id = $teacher['id'];
    }
} elseif ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar') {
    // For admin/registrar, allow viewing any teacher's schedule
    if (isset($_GET['teacher_id']) && is_numeric($_GET['teacher_id'])) {
        $teacher_id = (int) $_GET['teacher_id'];
        
        // Get teacher details
        $query = "SELECT t.*, u.name FROM teachers t 
                  LEFT JOIN users u ON t.user_id = u.id 
                  WHERE t.id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $teacher_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $teacher = mysqli_fetch_assoc($result);
        } else {
            $_SESSION['alert'] = showAlert('Teacher not found.', 'danger');
            redirect('dashboard.php');
        }
    }
}

// If no teacher ID is found and user is not admin/registrar, show error
if (!$teacher_id && $_SESSION['role'] === 'teacher') {
    $_SESSION['alert'] = showAlert('No teacher profile found for your account. Please contact the administrator.', 'danger');
    redirect('dashboard.php');
}

// Get filter parameters
$day_filter = isset($_GET['day']) ? cleanInput($_GET['day']) : null;

// Get all teachers for dropdown (admin/registrar only)
$teachers = [];
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar') {
    $query = "SELECT t.id, t.first_name, t.last_name, u.name 
              FROM teachers t 
              LEFT JOIN users u ON t.user_id = u.id 
              ORDER BY t.last_name, t.first_name";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $teachers[] = $row;
        }
    }
}

// Get schedule for the teacher
$schedules = [];
if ($teacher_id) {
    // First, get schedules from the regular schedule table
    $query = "SELECT *, 'schedule' as source FROM schedule WHERE teacher_id = ?";
    
    // Add day filter if set
    if (!empty($day_filter)) {
        $query .= " AND day = ?";
    }
    
    $query .= " ORDER BY day, time_start";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!empty($day_filter)) {
            mysqli_stmt_bind_param($stmt, "is", $teacher_id, $day_filter);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $teacher_id);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
            $schedules[] = $row;
        }
    }
    
    // Next, get schedules from the SHS_Schedule_List table
    // Check if the table exists first
    $table_exists = false;
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'SHS_Schedule_List'");
    if ($check_table && mysqli_num_rows($check_table) > 0) {
        $table_exists = true;
    }
    
    if ($table_exists) {
        // First check if teacher_name column exists in SHS_Schedule_List
        $column_exists = false;
        $check_column = mysqli_query($conn, "SHOW COLUMNS FROM SHS_Schedule_List LIKE 'teacher_name'");
        if ($check_column && mysqli_num_rows($check_column) > 0) {
            $column_exists = true;
        }
        
        // Map teacher_id to teacher_name in SHS_Schedule_List
        $teacher_name = '';
        $teacher_query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM teachers WHERE id = ?";
        $teacher_stmt = mysqli_prepare($conn, $teacher_query);
        if ($teacher_stmt) {
            mysqli_stmt_bind_param($teacher_stmt, "i", $teacher_id);
            mysqli_stmt_execute($teacher_stmt);
            $teacher_result = mysqli_stmt_get_result($teacher_stmt);
            if ($teacher_row = mysqli_fetch_assoc($teacher_result)) {
                $teacher_name = $teacher_row['full_name'];
            }
            mysqli_stmt_close($teacher_stmt);
        }
        
        if (!empty($teacher_name)) {
            // Now query the SHS_Schedule_List table
            if ($column_exists) {
                // If teacher_name column exists
                $shs_query = "SELECT 
                    id, 
                    day_of_week as day, 
                    start_time as time_start, 
                    end_time as time_end, 
                    subject, 
                    section, 
                    grade_level, 
                    teacher_name, 
                    room,
                    'SHS_Schedule_List' as source 
                    FROM SHS_Schedule_List 
                    WHERE teacher_name LIKE ?";
                
                // Add day filter if set
                if (!empty($day_filter)) {
                    $shs_query .= " AND day_of_week = ?";
                }
                
                $shs_query .= " ORDER BY day_of_week, start_time";
                $shs_stmt = mysqli_prepare($conn, $shs_query);
                
                $teacher_name_param = "%$teacher_name%";
                
                if (!empty($day_filter)) {
                    mysqli_stmt_bind_param($shs_stmt, "ss", $teacher_name_param, $day_filter);
                } else {
                    mysqli_stmt_bind_param($shs_stmt, "s", $teacher_name_param);
                }
            } else {
                // If teacher_name column doesn't exist, try using teacher_id if it exists
                $check_teacher_id = mysqli_query($conn, "SHOW COLUMNS FROM SHS_Schedule_List LIKE 'teacher_id'");
                if ($check_teacher_id && mysqli_num_rows($check_teacher_id) > 0) {
                    // Use teacher_id column
                    $shs_query = "SELECT 
                        id, 
                        day_of_week as day, 
                        start_time as time_start, 
                        end_time as time_end, 
                        subject, 
                        section, 
                        grade_level, 
                        ? as teacher_name, 
                        room,
                        'SHS_Schedule_List' as source 
                        FROM SHS_Schedule_List 
                        WHERE teacher_id = ?";
                    
                    // Add day filter if set
                    if (!empty($day_filter)) {
                        $shs_query .= " AND day_of_week = ?";
                    }
                    
                    $shs_query .= " ORDER BY day_of_week, start_time";
                    $shs_stmt = mysqli_prepare($conn, $shs_query);
                    
                    if (!empty($day_filter)) {
                        mysqli_stmt_bind_param($shs_stmt, "sis", $teacher_name, $teacher_id, $day_filter);
                    } else {
                        mysqli_stmt_bind_param($shs_stmt, "si", $teacher_name, $teacher_id);
                    }
                } else {
                    // Neither teacher_name nor teacher_id exists, use a generic query
                    $shs_query = "SELECT 
                        id, 
                        day_of_week as day, 
                        start_time as time_start, 
                        end_time as time_end, 
                        subject, 
                        section, 
                        grade_level, 
                        ? as teacher_name, 
                        room,
                        'SHS_Schedule_List' as source 
                        FROM SHS_Schedule_List";
                    
                    // Add day filter if set
                    if (!empty($day_filter)) {
                        $shs_query .= " WHERE day_of_week = ?";
                        $shs_stmt = mysqli_prepare($conn, $shs_query);
                        mysqli_stmt_bind_param($shs_stmt, "ss", $teacher_name, $day_filter);
                    } else {
                        $shs_stmt = mysqli_prepare($conn, $shs_query);
                        mysqli_stmt_bind_param($shs_stmt, "s", $teacher_name);
                    }
                }
            }
            
            mysqli_stmt_execute($shs_stmt);
            $shs_result = mysqli_stmt_get_result($shs_stmt);
            
            if ($shs_result) {
                while ($row = mysqli_fetch_assoc($shs_result)) {
                $schedules[] = $row;
                }
            }
            mysqli_stmt_close($shs_stmt);
        }
    }
    
    // Also check the schedules table (if it exists)
    $schedules_table_exists = false;
    $check_schedules_table = mysqli_query($conn, "SHOW TABLES LIKE 'schedules'");
    if ($check_schedules_table && mysqli_num_rows($check_schedules_table) > 0) {
        $schedules_table_exists = true;
    }
    
    if ($schedules_table_exists) {
        // First check if day column exists in schedules table
        $day_column_exists = false;
        $check_day = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day'");
        if ($check_day && mysqli_num_rows($check_day) > 0) {
            $day_column_exists = true;
        }
        
        // Check if day_of_week column exists in schedules table
        $day_of_week_column_exists = false;
        $check_day_of_week = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day_of_week'");
        if ($check_day_of_week && mysqli_num_rows($check_day_of_week) > 0) {
            $day_of_week_column_exists = true;
        }
        
        // Determine which day column to use
        $day_column = $day_column_exists ? 'day' : ($day_of_week_column_exists ? 'day_of_week' : 'day_of_week');
        
        // Check for time column variations
        $time_start_exists = false;
        $check_time_start = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'time_start'");
        if ($check_time_start && mysqli_num_rows($check_time_start) > 0) {
            $time_start_exists = true;
        }
        
        $start_time_exists = false;
        $check_start_time = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'start_time'");
        if ($check_start_time && mysqli_num_rows($check_start_time) > 0) {
            $start_time_exists = true;
        }
        
        // Determine which time columns to use
        $start_time_column = $time_start_exists ? 'time_start' : ($start_time_exists ? 'start_time' : 'start_time');
        $end_time_column = $time_start_exists ? 'time_end' : ($start_time_exists ? 'end_time' : 'end_time');
        
        $schedules_query = "SELECT 
            id,
            " . $day_column . " as day,
            " . $start_time_column . " as time_start,
            " . $end_time_column . " as time_end,
            subject,
            section,
            grade_level,
            teacher_id,
            room,
            'schedules' as source
            FROM schedules 
            WHERE teacher_id = ?";
        
        // Add day filter if set
        if (!empty($day_filter)) {
            $schedules_query .= " AND " . $day_column . " = ?";
        }
        
        $schedules_query .= " ORDER BY " . $day_column . ", " . $start_time_column;
        $schedules_stmt = mysqli_prepare($conn, $schedules_query);
        
        if (!empty($day_filter)) {
            mysqli_stmt_bind_param($schedules_stmt, "is", $teacher_id, $day_filter);
        } else {
            mysqli_stmt_bind_param($schedules_stmt, "i", $teacher_id);
        }
        
        mysqli_stmt_execute($schedules_stmt);
        $schedules_result = mysqli_stmt_get_result($schedules_stmt);
        
        if ($schedules_result) {
            while ($row = mysqli_fetch_assoc($schedules_result)) {
                $schedules[] = $row;
            }
        }
        mysqli_stmt_close($schedules_stmt);
    }
}

// Group schedules by day for better display
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$schedules_by_day = [];

foreach ($days as $day) {
    $schedules_by_day[$day] = [];
}

foreach ($schedules as $schedule) {
    if (isset($schedule['day']) && in_array($schedule['day'], $days)) {
        $schedules_by_day[$schedule['day']][] = $schedule;
    }
}

// Debug information
debug_log('Teacher ID', $teacher_id);
debug_log('Total schedules found', count($schedules));
debug_log('Schedules by day', $schedules_by_day);

// If no schedules found and teacher exists, create a sample schedule for demonstration
if (empty($schedules) && $teacher_id && isset($_GET['create_sample']) && $_GET['create_sample'] == 1) {
    // Create a sample schedule for Monday
    $sample_schedule = [
        'id' => 'sample',
        'day' => 'Monday',
        'time_start' => '08:00:00',
        'time_end' => '09:30:00',
        'subject' => 'Sample Subject',
        'section' => 'Sample Section',
        'grade_level' => '11',
        'room' => 'Room 101',
        'source' => 'sample'
    ];
    
    $schedules[] = $sample_schedule;
    $schedules_by_day['Monday'][] = $sample_schedule;
    
    // Create another sample for Tuesday
    $sample_schedule2 = [
        'id' => 'sample2',
        'day' => 'Tuesday',
        'time_start' => '10:00:00',
        'time_end' => '11:30:00',
        'subject' => 'Another Subject',
        'section' => 'Another Section',
        'grade_level' => '12',
        'room' => 'Room 202',
        'source' => 'sample'
    ];
    
    $schedules[] = $sample_schedule2;
    $schedules_by_day['Tuesday'][] = $sample_schedule2;
    
    debug_log('Created sample schedules', $schedules);
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <?php if ($_SESSION['role'] === 'teacher'): ?>
                My Schedule
            <?php else: ?>
                Teacher Schedule
            <?php endif; ?>
        </h1>
    </div>
</div>

<?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar'): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Select Teacher</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-6">
                        <label for="teacher_id" class="form-label">Teacher</label>
                        <select class="form-select" id="teacher_id" name="teacher_id" required>
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo ($teacher_id == $t['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['last_name'] . ', ' . $t['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="day" class="form-label">Day</label>
                        <select class="form-select" id="day" name="day">
                            <option value="">All Days</option>
                            <option value="Monday" <?php echo ($day_filter === 'Monday') ? 'selected' : ''; ?>>Monday</option>
                            <option value="Tuesday" <?php echo ($day_filter === 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                            <option value="Wednesday" <?php echo ($day_filter === 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                            <option value="Thursday" <?php echo ($day_filter === 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                            <option value="Friday" <?php echo ($day_filter === 'Friday') ? 'selected' : ''; ?>>Friday</option>
                            <option value="Saturday" <?php echo ($day_filter === 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> View Schedule
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Filter Schedule</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-6">
                        <label for="day" class="form-label">Day</label>
                        <select class="form-select" id="day" name="day">
                            <option value="">All Days</option>
                            <option value="Monday" <?php echo ($day_filter === 'Monday') ? 'selected' : ''; ?>>Monday</option>
                            <option value="Tuesday" <?php echo ($day_filter === 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                            <option value="Wednesday" <?php echo ($day_filter === 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                            <option value="Thursday" <?php echo ($day_filter === 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                            <option value="Friday" <?php echo ($day_filter === 'Friday') ? 'selected' : ''; ?>>Friday</option>
                            <option value="Saturday" <?php echo ($day_filter === 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="<?php echo $relative_path; ?>modules/teacher/schedule.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($teacher): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="<?php echo $print_view ? '' : 'card'; ?>">
            <?php if (!$print_view): ?>
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar'): ?>
                        Schedule for <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                    <?php else: ?>
                        My Teaching Schedule
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
            <?php else: ?>
            <div>
                <h4>
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar'): ?>
                        Schedule for <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                    <?php else: ?>
                        Schedule for <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                    <?php endif; ?>
                </h4>
            </div>
            <?php endif; ?>
                <?php if (empty($schedules)): ?>
                    <div class="alert alert-info">
                        No schedule found<?php echo !empty($day_filter) ? ' for ' . $day_filter : ''; ?>.
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <div class="mt-3">
                                <a href="<?php echo $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'create_sample=1'; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Create Sample Schedule
                                </a>
                                <a href="<?php echo $relative_path; ?>modules/admin/schedule.php" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-calendar-alt"></i> Manage Schedules
                                </a>
                                <a href="<?php echo $relative_path; ?>setup_schedule_tables.php" class="btn btn-sm btn-info">
                                    <i class="fas fa-database"></i> Setup Schedule Tables
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if (empty($day_filter)): ?>
                        <!-- Display schedule by day -->
                        <?php if (!$print_view): ?>
                        <ul class="nav nav-tabs mb-3" id="scheduleTabs" role="tablist">
                            <?php foreach ($days as $index => $day): ?>
                                <?php if (!empty($schedules_by_day[$day])): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                id="<?php echo strtolower($day); ?>-tab" 
                                                data-bs-toggle="tab" 
                                                data-bs-target="#<?php echo strtolower($day); ?>" 
                                                type="button" 
                                                role="tab" 
                                                aria-controls="<?php echo strtolower($day); ?>" 
                                                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                                            <?php echo $day; ?>
                                        </button>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="tab-content" id="scheduleTabsContent">
                        <?php endif; ?>
                            <?php foreach ($days as $index => $day): ?>
                                <?php if (!empty($schedules_by_day[$day])): ?>
                                    <?php if (!$print_view): ?>
                                    <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                                         id="<?php echo strtolower($day); ?>" 
                                         role="tabpanel" 
                                         aria-labelledby="<?php echo strtolower($day); ?>-tab">
                                    <?php else: ?>
                                    <div class="mb-4">
                                        <h5><?php echo $day; ?></h5>
                                    <?php endif; ?>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover schedule-table">
                                                <thead>
                                                    <tr>
                                                        <th>Time</th>
                                                        <th>Subject</th>
                                                        <th>Grade & Section</th>
                                                        <th>Room</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($schedules_by_day[$day] as $schedule): ?>
                                                        <tr>
                                                            <td class="schedule-time">
                                                                <?php 
                                                                // Handle different time formats between tables
                                                                $time_start = isset($schedule['time_start']) ? $schedule['time_start'] : '';
                                                                $time_end = isset($schedule['time_end']) ? $schedule['time_end'] : '';
                                                                
                                                                // Ensure time is in proper format
                                                                if (!empty($time_start)) {
                                                                    echo date('h:i A', strtotime($time_start));
                                                                echo ' - '; 
                                                                    if (!empty($time_end)) {
                                                                        echo date('h:i A', strtotime($time_end));
                                                                    } else {
                                                                        echo 'N/A';
                                                                    }
                                                                } else {
                                                                    echo 'N/A';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                                            <td>
                                                                <?php 
                                                                // Use the formatSectionDisplay function with proper error handling
                                                                $section = isset($schedule['section']) ? $schedule['section'] : '';
                                                                $grade_level = isset($schedule['grade_level']) ? $schedule['grade_level'] : '';
                                                                
                                                                if (!empty($section)) {
                                                                    try {
                                                                    echo htmlspecialchars(formatSectionDisplay($section, $grade_level, $conn));
                                                                    } catch (Exception $e) {
                                                                        // Fallback to raw section name if function fails
                                                                        echo htmlspecialchars($section);
                                                                    }
                                                                } else {
                                                                    echo 'N/A';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php if (!$print_view): ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Display schedule for specific day -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover schedule-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Grade & Section</th>
                                        <th>Room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($schedule['day']); ?></td>
                                            <td class="schedule-time">
                                                <?php 
                                                    echo date('h:i A', strtotime($schedule['time_start'])); 
                                                    echo ' - '; 
                                                    echo date('h:i A', strtotime($schedule['time_end']));
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                            <td>
                                                <?php 
                                                // Use the new formatSectionDisplay function
                                                echo htmlspecialchars(formatSectionDisplay($schedule['section'], $schedule['grade_level'], $conn));
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$print_view): ?>
                    <div class="mt-3 no-print">
                        <!-- Print Schedule button removed -->
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
if ($print_view) {
    // Add print script and close HTML for print view
    ?>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    </div>
    </body>
    </html>
    <?php
    exit; // Stop further execution
} else {
    // Include regular footer
    require_once $relative_path . 'includes/footer.php';
}
?> 
<?php
$title = 'SHS Schedule Management';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] == 1;

// Debug function
function debug_log($message, $data = null) {
    global $debug;
    if ($debug) {
        echo '<div class="alert alert-info">';
        echo '<strong>' . $message . ':</strong> ';
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                echo '<pre>' . print_r($data, true) . '</pre>';
            } else {
                echo $data;
            }
        }
        echo '</div>';
    }
}

// Process synchronization between tables
if (isset($_GET['sync']) && $_GET['sync'] == 1 && checkAccess(['admin'])) {
    // Check if SHS_Schedule_List table exists
    $check_shs_table_query = "SHOW TABLES LIKE 'SHS_Schedule_List'";
    $check_shs_table_result = mysqli_query($conn, $check_shs_table_query);
    $shs_table_exists = mysqli_num_rows($check_shs_table_result) > 0;
    
    if ($shs_table_exists) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // 1. Copy from schedule to SHS_Schedule_List
            $sync_query = "INSERT INTO SHS_Schedule_List (subject, section, grade_level, day_of_week, start_time, end_time, teacher_name, teacher_id, room)
                          SELECT s.subject, s.section, s.grade_level, s.day, s.time_start, s.time_end, 
                                 CONCAT(t.first_name, ' ', t.last_name), s.teacher_id, s.room
                          FROM schedule s
                          LEFT JOIN teachers t ON s.teacher_id = t.id
                          WHERE NOT EXISTS (
                              SELECT 1 FROM SHS_Schedule_List shs 
                              WHERE shs.subject = s.subject 
                              AND shs.section = s.section
                              AND shs.grade_level = s.grade_level
                              AND shs.day_of_week = s.day
                              AND shs.start_time = s.time_start
                              AND shs.end_time = s.time_end
                              AND shs.room = s.room
                          )";
            
            $result = mysqli_query($conn, $sync_query);
            $schedule_to_shs_count = mysqli_affected_rows($conn);
            
            // 2. Copy from SHS_Schedule_List to schedule
            $sync_query = "INSERT INTO schedule (subject, section, grade_level, day, time_start, time_end, teacher_id, room)
                          SELECT s.subject, s.section, s.grade_level, s.day_of_week, s.start_time, s.end_time, 
                                 s.teacher_id, s.room
                          FROM SHS_Schedule_List s
                          WHERE s.teacher_id IS NOT NULL
                          AND NOT EXISTS (
                              SELECT 1 FROM schedule sch 
                              WHERE sch.subject = s.subject 
                              AND sch.section = s.section
                              AND sch.grade_level = s.grade_level
                              AND sch.day = s.day_of_week
                              AND sch.time_start = s.start_time
                              AND sch.time_end = s.end_time
                              AND sch.room = s.room
                          )";
            
            $result = mysqli_query($conn, $sync_query);
            $shs_to_schedule_count = mysqli_affected_rows($conn);
            
            // Commit transaction
            mysqli_commit($conn);
            
            $_SESSION['alert'] = showAlert("Synchronization completed successfully. Copied $schedule_to_shs_count records from schedule to SHS_Schedule_List and $shs_to_schedule_count records from SHS_Schedule_List to schedule.", 'success');
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $_SESSION['alert'] = showAlert('Error synchronizing tables: ' . $e->getMessage(), 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('SHS_Schedule_List table does not exist. Cannot synchronize.', 'danger');
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
        <title>SHS Schedule - <?php echo SYSTEM_NAME; ?></title>
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
            .table {
                width: 100%;
                border-collapse: collapse;
            }
            .table th, .table td {
                border: 1px solid #ddd;
                padding: 8px;
            }
            .table th {
                background-color: #f2f2f2;
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
                <p>Senior High School Schedule</p>
                <p><?php echo date('F d, Y'); ?></p>
            </div>
    <?php
}

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Check if schedule table exists, if not create it
$check_table_query = "SHOW TABLES LIKE 'schedule'";
$check_table_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_table_result) == 0) {
    // Table doesn't exist, create it now
    $create_table_query = "CREATE TABLE schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade_level VARCHAR(20) NOT NULL,
        section VARCHAR(50) NOT NULL,
        subject VARCHAR(100) NOT NULL,
        teacher_id INT NOT NULL,
        day VARCHAR(20) NOT NULL,
        time_start TIME NOT NULL,
        time_end TIME NOT NULL,
        room VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        $_SESSION['alert'] = showAlert('The schedule table has been created successfully.', 'success');
    } else {
        $_SESSION['alert'] = showAlert('Error creating schedule table: ' . mysqli_error($conn), 'danger');
    }
}

// Process delete schedule
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    $from_shs = isset($_GET['shs']) && $_GET['shs'] == '1';
    
    if ($from_shs) {
        // Delete from SHS_Schedule_List
        $query = "SELECT * FROM SHS_Schedule_List WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $schedule = mysqli_fetch_assoc($result);
            
            // Delete schedule
            $query = "DELETE FROM SHS_Schedule_List WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $delete_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Log action
                $log_desc = "Deleted SHS schedule: {$schedule['subject']} for {$schedule['grade_level']} {$schedule['section']}";
                logAction($_SESSION['user_id'], 'DELETE', $log_desc);
                
                $_SESSION['alert'] = showAlert('SHS Schedule deleted successfully.', 'success');
            } else {
                $_SESSION['alert'] = showAlert('Error deleting SHS schedule: ' . mysqli_error($conn), 'danger');
            }
        } else {
            $_SESSION['alert'] = showAlert('SHS Schedule not found.', 'danger');
        }
    } else {
        // Delete from regular schedule table
        $query = "SELECT * FROM schedule WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $schedule = mysqli_fetch_assoc($result);
        
        // Delete schedule
        $query = "DELETE FROM schedule WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log action
                $log_desc = "Deleted schedule: {$schedule['subject']} for {$schedule['grade_level']} {$schedule['section']}";
            logAction($_SESSION['user_id'], 'DELETE', $log_desc);
            
            $_SESSION['alert'] = showAlert('Schedule deleted successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error deleting schedule: ' . mysqli_error($conn), 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('Schedule not found.', 'danger');
    }
    }
    
    // Redirect to schedule page
    redirect('modules/admin/schedule.php');
}

// Process form submission for adding/editing schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : null;
    $grade_level = cleanInput($_POST['grade_level']);
    $section = cleanInput($_POST['section']);
    $subject = cleanInput($_POST['subject']);
    $teacher_id = (int) $_POST['teacher_id'];
    $day = cleanInput($_POST['day']);
    $time_start = cleanInput($_POST['time_start']);
    $time_end = cleanInput($_POST['time_end']);
    $room = cleanInput($_POST['room']);
    
    // Validate input
    $errors = [];
    
    if (empty($grade_level)) {
        $errors[] = 'Grade level is required.';
    }
    
    if (empty($section)) {
        $errors[] = 'Section is required.';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required.';
    }
    
    if (empty($teacher_id)) {
        $errors[] = 'Teacher is required.';
    }
    
    if (empty($day)) {
        $errors[] = 'Day is required.';
    }
    
    if (empty($time_start)) {
        $errors[] = 'Start time is required.';
    }
    
    if (empty($time_end)) {
        $errors[] = 'End time is required.';
    }
    
    if (empty($room)) {
        $errors[] = 'Room is required.';
    }
    
    // Check if time_start and time_end are within allowed range (7:00 AM - 5:00 PM)
    $min_time = strtotime('07:00:00');
    $max_time = strtotime('17:00:00');
    $start_time = strtotime($time_start);
    $end_time = strtotime($time_end);
    
    if ($start_time < $min_time || $start_time > $max_time) {
        $errors[] = 'Start time must be between 7:00 AM and 5:00 PM.';
    }
    
    if ($end_time < $min_time || $end_time > $max_time) {
        $errors[] = 'End time must be between 7:00 AM and 5:00 PM.';
    }
    
    // Check if time_end is after time_start
    if (!empty($time_start) && !empty($time_end)) {
        if (strtotime($time_end) <= strtotime($time_start)) {
            $errors[] = 'End time must be after start time.';
        }
    }
    
    // Check for schedule conflicts (same room, same day, overlapping time)
    if (empty($errors)) {
        $conflict_query = "SELECT * FROM schedule 
                          WHERE room = ? AND day = ? 
                          AND ((time_start BETWEEN ? AND ?) 
                               OR (time_end BETWEEN ? AND ?)
                               OR (? BETWEEN time_start AND time_end)
                               OR (? BETWEEN time_start AND time_end))";
        
        if ($edit_id !== null) {
            $conflict_query .= " AND id != ?";
        }
        
        $stmt = mysqli_prepare($conn, $conflict_query);
        
        if ($edit_id !== null) {
            mysqli_stmt_bind_param($stmt, "sssssssi", $room, $day, $time_start, $time_end, $time_start, $time_end, $time_start, $time_end, $edit_id);
        } else {
            mysqli_stmt_bind_param($stmt, "ssssssss", $room, $day, $time_start, $time_end, $time_start, $time_end, $time_start, $time_end);
        }
        
        mysqli_stmt_execute($stmt);
        $conflict_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($conflict_result) > 0) {
            $errors[] = 'Schedule conflict: The room is already booked during this time.';
        }
    }
    
    // Check for teacher conflicts (same teacher, same day, overlapping time)
    if (empty($errors)) {
        $teacher_conflict_query = "SELECT * FROM schedule 
                                  WHERE teacher_id = ? AND day = ? 
                                  AND ((time_start BETWEEN ? AND ?) 
                                       OR (time_end BETWEEN ? AND ?)
                                       OR (? BETWEEN time_start AND time_end)
                                       OR (? BETWEEN time_start AND time_end))";
        
        if ($edit_id !== null) {
            $teacher_conflict_query .= " AND id != ?";
        }
        
        $stmt = mysqli_prepare($conn, $teacher_conflict_query);
        
        if ($edit_id !== null) {
            mysqli_stmt_bind_param($stmt, "isssssssi", $teacher_id, $day, $time_start, $time_end, $time_start, $time_end, $time_start, $time_end, $edit_id);
        } else {
            mysqli_stmt_bind_param($stmt, "isssssss", $teacher_id, $day, $time_start, $time_end, $time_start, $time_end, $time_start, $time_end);
        }
        
        mysqli_stmt_execute($stmt);
        $teacher_conflict_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($teacher_conflict_result) > 0) {
            $errors[] = 'Schedule conflict: The teacher is already assigned during this time.';
        }
    }
    
    // If no errors, add or update schedule
    if (empty($errors)) {
        if ($edit_id === null) {
            // Add new schedule
            $query = "INSERT INTO schedule (grade_level, section, subject, teacher_id, day, time_start, time_end, room) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssissss", $grade_level, $section, $subject, $teacher_id, $day, $time_start, $time_end, $room);
            
            if (mysqli_stmt_execute($stmt)) {
                // Log action
                $log_desc = "Added new schedule: {$subject} for {$grade_level} {$section}";
                logAction($_SESSION['user_id'], 'CREATE', $log_desc);
                
                $_SESSION['alert'] = showAlert('Schedule added successfully.', 'success');
            } else {
                $_SESSION['alert'] = showAlert('Error adding schedule: ' . mysqli_error($conn), 'danger');
            }
        } else {
            // Update existing schedule
            $query = "UPDATE schedule SET grade_level = ?, section = ?, subject = ?, teacher_id = ?, 
                      day = ?, time_start = ?, time_end = ?, room = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssissssi", $grade_level, $section, $subject, $teacher_id, $day, $time_start, $time_end, $room, $edit_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Log action
                $log_desc = "Updated schedule: {$subject} for {$grade_level} {$section}";
                logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
                
                $_SESSION['alert'] = showAlert('Schedule updated successfully.', 'success');
            } else {
                $_SESSION['alert'] = showAlert('Error updating schedule: ' . mysqli_error($conn), 'danger');
            }
        }
    } else {
        // Display errors
        $error_list = '<ul>';
        foreach ($errors as $error) {
            $error_list .= '<li>' . $error . '</li>';
        }
        $error_list .= '</ul>';
        $_SESSION['alert'] = showAlert('Please fix the following errors:' . $error_list, 'danger');
    }
}

// Get schedule to edit if edit parameter is set
$edit_schedule = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    
    $query = "SELECT * FROM schedule WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $edit_schedule = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['alert'] = showAlert('Schedule not found.', 'danger');
        redirect('modules/admin/schedule.php');
    }
}

// Get all teachers for dropdown
$teachers = [];
$teacher_query = "SELECT id, first_name, last_name FROM teachers WHERE department = 'Senior High School' ORDER BY last_name, first_name";
$teacher_result = mysqli_query($conn, $teacher_query);
if ($teacher_result) {
    while ($row = mysqli_fetch_assoc($teacher_result)) {
        $teachers[] = $row;
    }
}

// Get filter parameters
$grade_filter = isset($_GET['grade']) ? cleanInput($_GET['grade']) : null;
$section_filter = isset($_GET['section']) ? cleanInput($_GET['section']) : null;
$day_filter = isset($_GET['day']) ? cleanInput($_GET['day']) : null;

// Check if SHS_Schedule_List table exists
$check_shs_table_query = "SHOW TABLES LIKE 'SHS_Schedule_List'";
$check_shs_table_result = mysqli_query($conn, $check_shs_table_query);
$shs_table_exists = mysqli_num_rows($check_shs_table_result) > 0;

debug_log('SHS_Schedule_List table exists', $shs_table_exists ? 'Yes' : 'No');

// Initialize schedules array
$schedules = [];

// Get schedules from regular schedule table with filters
$query = "SELECT s.*, t.first_name, t.last_name, 'schedule' as source 
          FROM schedule s 
          LEFT JOIN teachers t ON s.teacher_id = t.id 
          WHERE 1=1";

// Add filters
if (!empty($grade_filter)) {
    $query .= " AND s.grade_level = '" . mysqli_real_escape_string($conn, $grade_filter) . "'";
}

if (!empty($section_filter)) {
    $query .= " AND s.section = '" . mysqli_real_escape_string($conn, $section_filter) . "'";
}

if (!empty($day_filter)) {
    $query .= " AND s.day = '" . mysqli_real_escape_string($conn, $day_filter) . "'";
}

$query .= " ORDER BY s.day, s.time_start, s.grade_level, s.section";
debug_log('Schedule query', $query);

$result = mysqli_query($conn, $query);
if ($result) {
    $schedule_count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $schedules[] = $row;
        $schedule_count++;
    }
    debug_log('Regular schedule records found', $schedule_count);
} else {
    debug_log('Error in regular schedule query', mysqli_error($conn));
}

// If SHS_Schedule_List table exists, get schedules from it as well
if ($shs_table_exists) {
    $shs_query = "SELECT s.*, 
                 s.teacher_name, 
                 NULL as first_name,
                 NULL as last_name,
                 s.day_of_week as day,
                 s.start_time as time_start,
                 s.end_time as time_end,
                 'SHS_Schedule_List' as source 
                 FROM SHS_Schedule_List s 
                 WHERE 1=1";
    
    // Add filters
    if (!empty($grade_filter)) {
        $shs_query .= " AND s.grade_level = '" . mysqli_real_escape_string($conn, $grade_filter) . "'";
    }
    
    if (!empty($section_filter)) {
        $shs_query .= " AND s.section = '" . mysqli_real_escape_string($conn, $section_filter) . "'";
    }
    
    if (!empty($day_filter)) {
        $shs_query .= " AND s.day_of_week = '" . mysqli_real_escape_string($conn, $day_filter) . "'";
    }
    
    $shs_query .= " ORDER BY s.day_of_week, s.start_time, s.grade_level, s.section";
    debug_log('SHS_Schedule_List query', $shs_query);
    
    $shs_result = mysqli_query($conn, $shs_query);
    if ($shs_result) {
        $shs_count = 0;
        while ($row = mysqli_fetch_assoc($shs_result)) {
            $schedules[] = $row;
            $shs_count++;
        }
        debug_log('SHS_Schedule_List records found', $shs_count);
    } else {
        debug_log('Error in SHS_Schedule_List query', mysqli_error($conn));
    }
}

debug_log('Total schedules found', count($schedules));

// Get unique grade levels for filter
$grades = [];
$grade_query = "SELECT DISTINCT grade_level FROM schedule ORDER BY grade_level";
$grade_result = mysqli_query($conn, $grade_query);
if ($grade_result) {
    while ($row = mysqli_fetch_assoc($grade_result)) {
        $grades[] = $row['grade_level'];
    }
}

// If SHS_Schedule_List table exists, get grade levels from it as well
if ($shs_table_exists) {
    $shs_grade_query = "SELECT DISTINCT grade_level FROM SHS_Schedule_List ORDER BY grade_level";
    $shs_grade_result = mysqli_query($conn, $shs_grade_query);
    if ($shs_grade_result) {
        while ($row = mysqli_fetch_assoc($shs_grade_result)) {
            if (!in_array($row['grade_level'], $grades)) {
                $grades[] = $row['grade_level'];
            }
        }
    }
}

// Get unique sections for filter
$sections = [];
$section_query = "SELECT DISTINCT section FROM schedule ORDER BY section";
$section_result = mysqli_query($conn, $section_query);
if ($section_result) {
    while ($row = mysqli_fetch_assoc($section_result)) {
        $sections[] = $row['section'];
    }
}

// If SHS_Schedule_List table exists, get sections from it as well
if ($shs_table_exists) {
    $shs_section_query = "SELECT DISTINCT section FROM SHS_Schedule_List ORDER BY section";
    $shs_section_result = mysqli_query($conn, $shs_section_query);
    if ($shs_section_result) {
        while ($row = mysqli_fetch_assoc($shs_section_result)) {
            if (!in_array($row['section'], $sections)) {
                $sections[] = $row['section'];
            }
        }
    }
    }
    ?>

    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Manage SHS Schedule</h1>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><?php echo $edit_schedule ? 'Edit SHS Schedule' : 'Add New SHS Schedule'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <?php if ($edit_schedule): ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_schedule['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="grade_level" class="form-label">Grade Level</label>
                                <select class="form-select" id="grade_level" name="grade_level" required>
                                    <option value="">Select Grade Level</option>
                                    <option value="11" <?php echo ($edit_schedule && $edit_schedule['grade_level'] == '11') ? 'selected' : ''; ?>>Grade 11</option>
                                    <option value="12" <?php echo ($edit_schedule && $edit_schedule['grade_level'] == '12') ? 'selected' : ''; ?>>Grade 12</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="section" class="form-label">Section</label>
                                <select class="form-select" id="section" name="section" required>
                                    <option value="">Select Section</option>
                                    <!-- Will be populated based on grade level -->
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Select Subject</option>
                                <!-- Will be populated based on grade level -->
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" <?php echo ($edit_schedule && $edit_schedule['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['last_name'] . ', ' . $teacher['first_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="day" class="form-label">Day</label>
                            <select class="form-select" id="day" name="day" required>
                                <option value="">Select Day</option>
                                <option value="Monday" <?php echo ($edit_schedule && $edit_schedule['day'] === 'Monday') ? 'selected' : ''; ?>>Monday</option>
                                <option value="Tuesday" <?php echo ($edit_schedule && $edit_schedule['day'] === 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                                <option value="Wednesday" <?php echo ($edit_schedule && $edit_schedule['day'] === 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                                <option value="Thursday" <?php echo ($edit_schedule && $edit_schedule['day'] === 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                                <option value="Friday" <?php echo ($edit_schedule && $edit_schedule['day'] === 'Friday') ? 'selected' : ''; ?>>Friday</option>
                                <option value="Saturday" <?php echo ($edit_schedule && $edit_schedule['day'] === 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="time_start" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="time_start" name="time_start" 
                                       value="<?php echo isset($edit_schedule['time_start']) ? htmlspecialchars($edit_schedule['time_start']) : ''; ?>" 
                                       min="07:00" max="17:00" required>
                                <small class="text-muted">Schedule times are limited to 7:00 AM - 5:00 PM</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="time_end" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="time_end" name="time_end" 
                                       value="<?php echo isset($edit_schedule['time_end']) ? htmlspecialchars($edit_schedule['time_end']) : ''; ?>" 
                                       min="07:00" max="17:00" required>
                                <small class="text-muted">Schedule times are limited to 7:00 AM - 5:00 PM</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="room" class="form-label">Room</label>
                            <input type="text" class="form-control" id="room" name="room" value="<?php echo $edit_schedule ? htmlspecialchars($edit_schedule['room']) : ''; ?>" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo $edit_schedule ? 'Update SHS Schedule' : 'Add SHS Schedule'; ?>
                            </button>
                            
                            <?php if ($edit_schedule): ?>
                                <a href="<?php echo $relative_path; ?>modules/admin/schedule.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Filter SHS Schedule</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                        <div class="col-md-3">
                            <label for="grade" class="form-label">Grade Level</label>
                            <select class="form-select" id="grade" name="grade">
                                <option value="">All Grades</option>
                                <option value="11" <?php echo ($grade_filter == '11') ? 'selected' : ''; ?>>Grade 11</option>
                                <option value="12" <?php echo ($grade_filter == '12') ? 'selected' : ''; ?>>Grade 12</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="section" class="form-label">Section</label>
                            <select class="form-select" id="section" name="section">
                                <option value="">All Sections</option>
                                <?php 
                                // We'll populate this dynamically with JavaScript 
                                ?>
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
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="<?php echo $relative_path; ?>modules/admin/schedule.php<?php echo $debug ? '?debug=1' : ''; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                                <?php if ($debug): ?>
                                <input type="hidden" name="debug" value="1">
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">SHS Schedule List</h5>
                        <?php if (!$print_view): ?>
                        <div>
                            <?php if ($debug): ?>
                            <a href="<?php echo $relative_path; ?>modules/admin/schedule.php" class="btn btn-sm btn-warning me-2">
                                <i class="fas fa-bug me-1"></i> Disable Debug
                            </a>
                            <?php else: ?>
                            <a href="<?php echo $relative_path; ?>modules/admin/schedule.php?debug=1" class="btn btn-sm btn-light me-2">
                                <i class="fas fa-bug me-1"></i> Debug
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!$print_view): ?>
                    <div class="mb-3 no-print">
                        <button type="button" class="btn btn-sm btn-primary btn-export-excel" data-table-id="schedule-list-table" data-filename="schedule_list_<?php echo date('Y-m-d'); ?>">
                            <i class="fas fa-file-excel me-1"></i> Export to Excel
                        </button>
                        <?php if ($shs_table_exists && $debug): ?>
                        <a href="<?php echo $relative_path; ?>modules/admin/schedule.php?sync=1&debug=1" class="btn btn-sm btn-warning ms-2">
                            <i class="fas fa-sync me-1"></i> Sync Schedule Tables
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <table id="schedule-list-table" class="table table-striped table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Grade & Section</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Room</th>
                                    <?php if (!$print_view): ?>
                                    <th>Source</th>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($schedules)): ?>
                                    <tr>
                                    <td colspan="<?php echo $print_view ? '6' : '8'; ?>" class="text-center">No schedules found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                            <td><?php echo htmlspecialchars($schedule['day']); ?></td>
                                            <td>
                                                <?php 
                                                echo date('h:i A', strtotime($schedule['time_start'])); 
                                                echo ' - '; 
                                                echo date('h:i A', strtotime($schedule['time_end'])); 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                echo htmlspecialchars($schedule['grade_level'] . ' ' . $schedule['section']);
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                            <td>
                                                <?php 
                                                if ($schedule['source'] === 'SHS_Schedule_List' && !empty($schedule['teacher_name'])) {
                                                    echo htmlspecialchars($schedule['teacher_name']);
                                                } elseif ($schedule['first_name'] && $schedule['last_name']) {
                                                    echo htmlspecialchars($schedule['last_name'] . ', ' . $schedule['first_name']);
                                                } else {
                                                    echo '<span class="text-muted">Not assigned</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                            <?php if (!$print_view): ?>
                                            <td>
                                                <?php if ($schedule['source'] === 'SHS_Schedule_List'): ?>
                                                <span class="badge bg-info">SHS List</span>
                                                <?php else: ?>
                                                <span class="badge bg-secondary">Schedule</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($schedule['source'] === 'schedule'): ?>
                                                    <a href="<?php echo $relative_path; ?>modules/admin/schedule.php?edit=<?php echo $schedule['id']; ?>" class="btn btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" onclick="confirmDeleteSchedule(<?php echo $schedule['id']; ?>, '0')" class="btn btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <a href="<?php echo $relative_path; ?>modules/admin/manage_shs_schedule.php?edit=<?php echo $schedule['id']; ?>" class="btn btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" onclick="confirmDeleteSchedule(<?php echo $schedule['id']; ?>, '1')" class="btn btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <?php endif; ?>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="scheduleTypeText">this schedule</span>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i>Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Simple delete confirmation function
function confirmDeleteSchedule(id, isShs) {
    let message = 'Are you sure you want to delete this schedule? This action cannot be undone.';
    if (isShs == '1') {
        message = 'Are you sure you want to delete this SHS Schedule? This action cannot be undone.';
    }
    
    if (confirm(message)) {
        window.location.href = '<?php echo $relative_path; ?>modules/admin/schedule.php?delete=' + id + (isShs == '1' ? '&shs=1' : '');
    }
    return false;
}

// Add CSS for fade effect and loading indicator
const style = document.createElement('style');
style.textContent = `
    .fade {
        opacity: 0;
        transition: opacity 0.5s ease-out;
    }
    
    .loading {
        position: relative;
    }
    
    .loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10;
    }
    
    .loading::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 30px;
        height: 30px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        z-index: 11;
    }
    
    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
`;
document.head.appendChild(style);

// JavaScript validation for time range
document.addEventListener('DOMContentLoaded', function() {
    const timeStartInput = document.getElementById('time_start');
    const timeEndInput = document.getElementById('time_end');
    const scheduleForm = document.querySelector('form');
    const gradeLevelSelect = document.getElementById('grade_level');
    const sectionSelect = document.getElementById('section');
    const subjectSelect = document.getElementById('subject');
    
    // Ensure Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.warn('Bootstrap is not loaded. Loading it dynamically...');
        
        // Load Bootstrap JS if not already loaded
        const bootstrapScript = document.createElement('script');
        bootstrapScript.src = '<?php echo $relative_path; ?>assets/js/bootstrap.bundle.min.js';
        bootstrapScript.onload = function() {
            console.log('Bootstrap loaded successfully');
            // Initialize delete buttons after Bootstrap is loaded
            initializeDeleteButtons();
        };
        bootstrapScript.onerror = function() {
            console.error('Failed to load Bootstrap');
            // Initialize delete buttons with fallback
            initializeDeleteButtons(true);
        };
        document.head.appendChild(bootstrapScript);
    } else {
        // Bootstrap is already loaded, initialize delete buttons
        initializeDeleteButtons();
    }
    
    // Function to initialize delete buttons
    function initializeDeleteButtons(useFallback = false) {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const type = this.getAttribute('data-type');
                const deleteUrl = this.getAttribute('href');
                
                // Use fallback or try Bootstrap modal
                if (useFallback) {
                    // Use simple JavaScript confirm
                    if (confirm('Are you sure you want to delete ' + (type === 'shs' ? 'this SHS schedule' : 'this schedule') + '? This action cannot be undone.')) {
                        window.location.href = deleteUrl;
                    }
                } else {
                    try {
                        // Update modal content
                        document.getElementById('scheduleTypeText').textContent = type === 'shs' ? 'this SHS schedule' : 'this schedule';
                        
                        // Set delete button URL
                        document.getElementById('confirmDeleteBtn').href = deleteUrl;
                        
                        // Show the modal
                        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                        deleteModal.show();
                    } catch (error) {
                        console.error("Modal error:", error);
                        // Fallback to simple JavaScript confirm
                        if (confirm('Are you sure you want to delete ' + (type === 'shs' ? 'this SHS schedule' : 'this schedule') + '? This action cannot be undone.')) {
                            window.location.href = deleteUrl;
                        }
                    }
                }
            });
        });
    }
    
    // Helper function to show grade level message
    function showGradeLevelMessage(gradeLevel) {
        // Clear any previous messages
        const existingMessage = document.getElementById('grade-level-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        if (gradeLevel) {
            // Add message about sections being filtered by grade level
            const message = document.createElement('div');
            message.id = 'grade-level-message';
            message.className = 'alert alert-info mt-2 mb-2';
            message.innerHTML = `<i class="fas fa-info-circle me-1"></i> Showing only Grade ${gradeLevel} sections in the dropdown.`;
            sectionSelect.parentNode.appendChild(message);
            
            // Auto-hide the message after 5 seconds
            setTimeout(() => {
                if (document.getElementById('grade-level-message')) {
                    document.getElementById('grade-level-message').classList.add('fade');
                    setTimeout(() => {
                        if (document.getElementById('grade-level-message')) {
                            document.getElementById('grade-level-message').remove();
                        }
                    }, 500);
                }
            }, 5000);
        }
    }
    
    // Define confirmDelete function
    window.confirmDelete = function(deleteUrl, itemName) {
        // Create a Bootstrap modal for confirmation
        const modalId = 'deleteConfirmModal';
        let modal = document.getElementById(modalId);
        
        // If modal doesn't exist, create it
        if (!modal) {
            modal = document.createElement('div');
            modal.id = modalId;
            modal.className = 'modal fade';
            modal.tabIndex = '-1';
            modal.setAttribute('aria-labelledby', 'deleteConfirmModalLabel');
            modal.setAttribute('aria-hidden', 'true');
            
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete <strong id="itemToDelete"></strong>?</p>
                            <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Delete
                            </a>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
        
        // Set the item name and delete URL
        document.getElementById('itemToDelete').textContent = itemName;
        document.getElementById('confirmDeleteBtn').href = deleteUrl;
        
        // Show the modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // If the modal doesn't work, fallback to a simple confirm dialog
        document.getElementById('confirmDeleteBtn').addEventListener('click', function(e) {
            // This is a fallback in case the modal doesn't work
            if (!document.querySelector('.modal.show')) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete ' + itemName + '? This action cannot be undone.')) {
                    window.location.href = deleteUrl;
                }
            }
        });
        
        // Prevent the default anchor click behavior
        return false;
    };
    
    // Grade-specific subjects
    let grade11Subjects = [];
    let grade12Subjects = [];
    
    // Fetch sections from the database instead of hardcoding
    let grade11Sections = [];
    let grade12Sections = [];
    
    // Function to fetch subjects from the server
    async function fetchSubjects(gradeLevel) {
        try {
            // Add a timestamp to prevent caching
            const timestamp = new Date().getTime();
            const response = await fetch('<?php echo $relative_path; ?>modules/registrar/get_subjects.php?grade_level=' + encodeURIComponent(gradeLevel) + '&_=' + timestamp);
            const data = await response.json();
            
            console.log('Fetched subjects:', data); // Debug log
            
            if (data && Array.isArray(data)) {
                // Return the full subject objects to preserve all data
                return data;
            }
            
            return [];
        } catch (error) {
            console.error('Error fetching subjects:', error);
            return [];
        }
    }
    
    // Function to populate subjects based on grade level
    async function populateSubjects(gradeLevel) {
        // Clear current options
        subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
        
        try {
            // Get subjects for the selected grade
            let subjects = [];
            
            if (gradeLevel === '11') {
                // Always fetch fresh data to get newly added subjects
                grade11Subjects = await fetchSubjects('Grade 11');
                subjects = grade11Subjects;
            } else if (gradeLevel === '12') {
                // Always fetch fresh data to get newly added subjects
                grade12Subjects = await fetchSubjects('Grade 12');
                subjects = grade12Subjects;
            }
            
            // Clear and add the default option
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
        
            // Add options
            if (subjects && subjects.length > 0) {
                subjects.forEach(subject => {
                    const option = document.createElement('option');
                    // Check if subject is an object or a string
                    if (typeof subject === 'object' && subject !== null) {
                        option.value = subject.display || subject.name;
                        option.textContent = subject.display || subject.name;
                        // Store the subject ID as a data attribute if available
                        if (subject.id) {
                            option.dataset.subjectId = subject.id;
                        }
                    } else {
                        option.value = subject;
                        option.textContent = subject;
                    }
                    subjectSelect.appendChild(option);
                });
            } else {
                console.warn('No subjects found for grade level:', gradeLevel);
            }
            
            // If editing, try to restore the original value
            if (scheduleForm.elements['edit_id']) {
                const originalSubject = "<?php echo $edit_schedule ? htmlspecialchars($edit_schedule['subject']) : ''; ?>";
                if (originalSubject) {
                    // Try to find and select the option with this value
                    for (let i = 0; i < subjectSelect.options.length; i++) {
                        if (subjectSelect.options[i].value === originalSubject) {
                            subjectSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error populating subjects:', error);
            subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
        }
    }
    
    // Function to fetch sections from the server
    async function fetchSections(gradeLevel) {
        try {
            // Add a timestamp to prevent caching
            const timestamp = new Date().getTime();
            const response = await fetch('<?php echo $relative_path; ?>modules/registrar/get_sections.php?grade_level=' + encodeURIComponent(gradeLevel) + '&_=' + timestamp);
            const data = await response.json();
            
            console.log('Fetched sections:', data); // Debug log
            
            if (data && Array.isArray(data)) {
                return data; // Return the full objects with display property
            }
            
            return [];
        } catch (error) {
            console.error('Error fetching sections:', error);
            return [];
        }
    }
    
    // Function to populate sections based on grade level
    async function populateSections(gradeLevel) {
        // Clear current options
        sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
        
        try {
            // Get sections for the selected grade - only sections matching this grade level will be shown
            let sections = [];
            
            if (gradeLevel === '11') {
                // Always fetch fresh data to get newly added sections for Grade 11 only
                grade11Sections = await fetchSections('Grade 11');
                sections = grade11Sections;
                console.log('Showing only Grade 11 sections');
            } else if (gradeLevel === '12') {
                // Always fetch fresh data to get newly added sections for Grade 12 only
                grade12Sections = await fetchSections('Grade 12');
                sections = grade12Sections;
                console.log('Showing only Grade 12 sections');
            } else {
                console.log('No grade level selected, no sections will be shown');
            }
            
            // Clear and add the default option
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            // Add options - these will only be sections for the selected grade level
            sections.forEach(section => {
                const option = document.createElement('option');
                // Use the exact section name from the database for both display and value
                option.value = section.name;
                option.textContent = section.name;
                // Add additional data attributes for reference
                option.dataset.sectionId = section.id;
                option.dataset.strand = section.strand;
                option.dataset.schoolYear = section.school_year;
                option.dataset.semester = section.semester;
                option.dataset.gradeLevel = section.grade_level; // Add grade level as data attribute
                sectionSelect.appendChild(option);
            });
            
            // If editing, try to restore the original value
            if (scheduleForm.elements['edit_id']) {
                const originalSection = "<?php echo $edit_schedule ? htmlspecialchars($edit_schedule['section']) : ''; ?>";
                if (originalSection) {
                    // Try to find and select the option with this value
                    for (let i = 0; i < sectionSelect.options.length; i++) {
                        if (sectionSelect.options[i].value === originalSection) {
                            sectionSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error populating sections:', error);
            sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
        }
    }
    
    // Function to populate filter sections based on grade level
    async function populateFilterSections(gradeLevel) {
        // Store current selection
        const currentSelection = filterSectionSelect.value;
        console.log('Current filter section selection before populating:', currentSelection);
        
        // Clear current options
        filterSectionSelect.innerHTML = '<option value="">Loading sections...</option>';
        
        try {
            let sections = [];
            
            if (!gradeLevel) {
                // If no grade selected, show all sections from both grades
                console.log('No grade filter selected, showing ALL sections from both Grade 11 and 12');
                try {
                    // Always fetch fresh data to get newly added sections
                    grade11Sections = await fetchSections('Grade 11');
                    grade12Sections = await fetchSections('Grade 12');
                    sections = [...grade11Sections, ...grade12Sections];
                } catch (error) {
                    console.error('Error fetching all sections:', error);
                }
            } else {
                // Get sections for the selected grade only
                if (gradeLevel === '11') {
                    console.log('Grade 11 filter selected, showing ONLY Grade 11 sections');
                    try {
                        // Always fetch fresh data to get newly added sections for Grade 11 only
                        grade11Sections = await fetchSections('Grade 11');
                        sections = grade11Sections;
                    } catch (error) {
                        console.error('Error fetching Grade 11 sections:', error);
                    }
                } else if (gradeLevel === '12') {
                    console.log('Grade 12 filter selected, showing ONLY Grade 12 sections');
                    try {
                        // Always fetch fresh data to get newly added sections for Grade 12 only
                        grade12Sections = await fetchSections('Grade 12');
                        sections = grade12Sections;
                    } catch (error) {
                        console.error('Error fetching Grade 12 sections:', error);
                    }
                }
            }
            
            // Clear and add default option
            filterSectionSelect.innerHTML = '<option value="">All Sections</option>';
            
            // Add options, removing duplicates
            const uniqueSections = [];
            const uniqueSectionNames = new Set();
            
            if (sections && sections.length > 0) {
                sections.forEach(section => {
                    if (section && section.name) {
                        const sectionName = section.name;
                        if (!uniqueSectionNames.has(sectionName)) {
                            uniqueSectionNames.add(sectionName);
                            uniqueSections.push(section);
                        }
                    }
                });
                
                uniqueSections.forEach(section => {
                    const option = document.createElement('option');
                    // Use the exact section name from the database for both display and value
                    option.value = section.name;
                    option.textContent = section.name;
                    option.dataset.gradeLevel = section.grade_level || ''; // Add grade level as data attribute
                    
                    // Restore previous selection if it exists
                    if (currentSelection && currentSelection === section.name) {
                        option.selected = true;
                        console.log('Restored previous section selection:', currentSelection);
                    }
                    
                    filterSectionSelect.appendChild(option);
                });
                
                console.log(`Filter populated with ${uniqueSections.length} sections for grade level: ${gradeLevel || 'All'}`);
            } else {
                console.warn('No sections found for filter');
                // Add a disabled option to indicate no sections found
                const option = document.createElement('option');
                option.textContent = 'No sections found';
                option.disabled = true;
                filterSectionSelect.appendChild(option);
            }
            
            // Check if we need to restore URL parameter selection
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('section');
            if (sectionParam && !currentSelection) {
                // Find and select the option with this value from URL parameters
                for (let i = 0; i < filterSectionSelect.options.length; i++) {
                    if (filterSectionSelect.options[i].value === sectionParam) {
                        filterSectionSelect.selectedIndex = i;
                        console.log('Selected section from URL parameter:', sectionParam);
                        break;
                    }
                }
            }
        } catch (error) {
            console.error('Error populating filter sections:', error);
            filterSectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            
            // Add a retry button
            const retryOption = document.createElement('option');
            retryOption.textContent = '↻ Retry loading sections';
            retryOption.value = 'retry';
            filterSectionSelect.appendChild(retryOption);
            
            // Add event listener for retry
            filterSectionSelect.addEventListener('change', function(event) {
                if (event.target.value === 'retry') {
                    console.log('Retrying section load');
                    populateFilterSections(gradeLevel);
                }
            }, { once: true }); // Remove after first use
        }
    }
    
    // Add event listener for grade level change
    gradeLevelSelect.addEventListener('change', function() {
        const selectedGrade = this.value;
        
        showGradeLevelMessage(selectedGrade);
        
        populateSections(selectedGrade);
        populateSubjects(selectedGrade);
    });
    
    // Initialize with current grade level if editing
    const currentGradeLevel = gradeLevelSelect.value;
    if (currentGradeLevel) {
        showGradeLevelMessage(currentGradeLevel);
        
        populateSections(currentGradeLevel);
        populateSubjects(currentGradeLevel);
    } else {
        // If not editing and no grade level is selected yet, initialize the dropdowns
        // This ensures the section dropdown is populated when the page loads
        const defaultGradeLevel = '11'; // Default to Grade 11
        gradeLevelSelect.value = defaultGradeLevel;
        
        showGradeLevelMessage(defaultGradeLevel);
        
        populateSections(defaultGradeLevel);
        populateSubjects(defaultGradeLevel);
    }

    // Time validation code
    scheduleForm.addEventListener('submit', function(event) {
        const minTime = '07:00';
        const maxTime = '17:00';
        const timeStart = timeStartInput.value;
        const timeEnd = timeEndInput.value;
        
        let hasError = false;
        
        // Reset previous error messages
        const errorMessages = document.querySelectorAll('.time-error');
        errorMessages.forEach(el => el.remove());
        
        // Validate start time
        if (timeStart < minTime || timeStart > maxTime) {
            event.preventDefault();
            hasError = true;
            const errorElement = document.createElement('div');
            errorElement.className = 'alert alert-danger time-error mt-2';
            errorElement.textContent = 'Start time must be between 7:00 AM and 5:00 PM';
            timeStartInput.parentNode.appendChild(errorElement);
        }
        
        // Validate end time
        if (timeEnd < minTime || timeEnd > maxTime) {
            event.preventDefault();
            hasError = true;
            const errorElement = document.createElement('div');
            errorElement.className = 'alert alert-danger time-error mt-2';
            errorElement.textContent = 'End time must be between 7:00 AM and 5:00 PM';
            timeEndInput.parentNode.appendChild(errorElement);
        }
        
        // Validate end time is after start time
        if (timeStart && timeEnd && timeEnd <= timeStart) {
            event.preventDefault();
            hasError = true;
            const errorElement = document.createElement('div');
            errorElement.className = 'alert alert-danger time-error mt-2';
            errorElement.textContent = 'End time must be after start time';
            timeEndInput.parentNode.appendChild(errorElement);
        }
        
        if (hasError) {
            // Scroll to the first error
            const firstError = document.querySelector('.time-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // Add filter functionality for the filter form
    const filterGradeSelect = document.getElementById('grade');
    const filterSectionSelect = document.getElementById('section');
    
    // Add event listener for grade level change in filter form
    if (filterGradeSelect) {
        filterGradeSelect.addEventListener('change', function() {
            const selectedGrade = this.value;
            console.log('Filter grade level changed to:', selectedGrade);
            
            // Show loading indicator for the filter form
            const filterForm = document.querySelector('form[method="get"]');
            if (filterForm) {
                filterForm.classList.add('loading');
            }

            populateFilterSections(selectedGrade)
                .then(() => {
                    // Hide loading indicator after successful population
                    if (filterForm) {
                        filterForm.classList.remove('loading');
                    }
                })
                .catch(error => {
                    console.error('Error populating filter sections:', error);
                    // Optionally, show an error message to the user
                    if (filterForm) {
                        filterForm.classList.remove('loading');
                        // Add a retry button if needed
                        const retryOption = document.createElement('option');
                        retryOption.textContent = '↻ Retry loading sections';
                        retryOption.value = 'retry';
                        filterSectionSelect.appendChild(retryOption);
                        filterSectionSelect.addEventListener('change', function(event) {
                            if (event.target.value === 'retry') {
                                console.log('Retrying section load');
                                populateFilterSections(selectedGrade);
                            }
                        }, { once: true });
                    }
                });
        });
        
        // Initialize with current grade level filter
        const currentGradeFilter = filterGradeSelect.value;
        console.log('Initializing filter with grade level:', currentGradeFilter || 'All Grades');
        
        // Always populate the filter sections dropdown when the page loads
        // If a grade filter is selected, show only sections for that grade
        // If no grade filter is selected, show all sections
        populateFilterSections(currentGradeFilter);
        
        // Show active filters message if any filters are applied
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('grade') || urlParams.has('section') || urlParams.has('day')) {
            // Create active filters array
            const activeFilters = [];
            
            if (urlParams.has('grade')) {
                const grade = urlParams.get('grade');
                activeFilters.push(`Grade: ${grade === '11' ? 'Grade 11' : 'Grade 12'}`);
            }
            
            if (urlParams.has('section')) {
                const section = urlParams.get('section');
                activeFilters.push(`Section: ${section}`);
            }
            
            if (urlParams.has('day')) {
                const day = urlParams.get('day');
                activeFilters.push(`Day: ${day}`);
            }
            
            if (activeFilters.length > 0) {
                // Find the filter card
                const filterCards = document.querySelectorAll('.card-header');
                let filterCard = null;
                
                // Find the filter card by its text content
                for (let i = 0; i < filterCards.length; i++) {
                    if (filterCards[i].textContent.includes('Filter SHS Schedule')) {
                        filterCard = filterCards[i];
                        break;
                    }
                }
                
                if (filterCard) {
                    // Check if we already have an active filters message
                    const existingMessage = filterCard.parentNode.querySelector('.active-filters');
                    if (!existingMessage) {
                        const filtersMessage = document.createElement('div');
                        filtersMessage.className = 'alert alert-primary mt-2 active-filters';
                        filtersMessage.innerHTML = '<strong>Active Filters:</strong> ' + activeFilters.join(', ');
                        filterCard.parentNode.insertBefore(filtersMessage, filterCard.nextSibling);
                    }
                }
            }
        }
        
        // Check if there's a section filter value in the URL
        const sectionParam = urlParams.get('section');
        if (sectionParam) {
            console.log('Section filter found in URL:', sectionParam);
            // We'll select this value once the dropdown is populated
            setTimeout(() => {
                if (filterSectionSelect) {
                    // Find the option with this value
                    for (let i = 0; i < filterSectionSelect.options.length; i++) {
                        if (filterSectionSelect.options[i].value === sectionParam) {
                            filterSectionSelect.selectedIndex = i;
                            console.log('Selected section in filter dropdown:', sectionParam);
                            break;
                        }
                    }
                }
            }, 500); // Give time for the dropdown to be populated
        }
    }
    
    // Add event listener for filter form submission
    const filterForm = document.querySelector('form[method="get"]');
    if (filterForm) {
        filterForm.addEventListener('submit', function(event) {
            // Log the filter values for debugging
            console.log('Filter form submitted with values:', {
                grade: filterGradeSelect ? filterGradeSelect.value : 'not found',
                section: filterSectionSelect ? filterSectionSelect.value : 'not found',
                day: document.getElementById('day') ? document.getElementById('day').value : 'not found'
            });
            
            // Show loading indicator
            filterForm.classList.add('loading');
            
            // The form will submit normally, and the page will reload with the new filter values
            // We'll add a small delay to show the loading indicator
            setTimeout(() => {
                // If we're still on the same page after 2 seconds, remove the loading indicator
                filterForm.classList.remove('loading');
            }, 2000);
        });
        
        // Add clear button functionality
        const clearButton = filterForm.querySelector('a.btn-secondary');
        if (clearButton) {
            clearButton.addEventListener('click', function(event) {
                // Prevent the default link behavior
                event.preventDefault();
                
                // Show loading indicator
                filterForm.classList.add('loading');
                
                // Reset all filter dropdowns
                if (filterGradeSelect) filterGradeSelect.value = '';
                if (filterSectionSelect) filterSectionSelect.value = '';
                if (document.getElementById('day')) document.getElementById('day').value = '';
                
                // Submit the form with cleared values
                filterForm.submit();
            });
        }
    }
    
    // Check if there are any schedules in the table after filtering
    const scheduleTable = document.getElementById('schedule-list-table');
    if (scheduleTable) {
        const scheduleRows = scheduleTable.querySelectorAll('tbody tr');
        const noSchedulesRow = scheduleTable.querySelector('tbody tr td[colspan]');
        
        if (noSchedulesRow && noSchedulesRow.textContent.includes('No schedules found')) {
            // No schedules found after filtering
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('grade') || urlParams.has('section') || urlParams.has('day')) {
                // Create a message to show that no schedules were found with the current filters
                const filterCards = document.querySelectorAll('.card-header');
                let filterCard = null;
                
                // Find the filter card by its text content
                for (let i = 0; i < filterCards.length; i++) {
                    if (filterCards[i].textContent.includes('Filter SHS Schedule')) {
                        filterCard = filterCards[i];
                        break;
                    }
                }
                
                if (filterCard) {
                    // Check if we already have an alert
                    const existingAlert = filterCard.parentNode.querySelector('.alert-info');
                    if (!existingAlert) {
                        const filterAlert = document.createElement('div');
                        filterAlert.className = 'alert alert-info mt-2';
                        filterAlert.innerHTML = '<i class="fas fa-info-circle me-1"></i> No schedules found with the current filters. Try different filter options or <a href="<?php echo $relative_path; ?>modules/admin/schedule.php">clear all filters</a>.';
                        filterCard.parentNode.insertBefore(filterAlert, filterCard.nextSibling);
                    }
                }
            }
        }
    }
});
</script>

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
<?php
$title = 'Setup Schedule Tables';
$relative_path = './';
require_once $relative_path . 'includes/header.php';

// Check if user is admin
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    header("Location: {$relative_path}dashboard.php");
    exit;
}

// Function to create schedule table if it doesn't exist
function createScheduleTable($conn) {
    $query = "CREATE TABLE IF NOT EXISTS `schedule` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `teacher_id` int(11) NOT NULL,
        `subject` varchar(255) NOT NULL,
        `section` varchar(255) NOT NULL,
        `grade_level` varchar(50) NOT NULL,
        `day` varchar(20) NOT NULL,
        `time_start` time NOT NULL,
        `time_end` time NOT NULL,
        `room` varchar(50) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `teacher_id` (`teacher_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (mysqli_query($conn, $query)) {
        return true;
    } else {
        return false;
    }
}

// Function to create SHS_Schedule_List table if it doesn't exist
function createSHSScheduleTable($conn) {
    $query = "CREATE TABLE IF NOT EXISTS `SHS_Schedule_List` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `subject` varchar(255) NOT NULL,
        `section` varchar(255) NOT NULL,
        `grade_level` varchar(50) NOT NULL,
        `day_of_week` varchar(20) NOT NULL,
        `start_time` time NOT NULL,
        `end_time` time NOT NULL,
        `teacher_name` varchar(255) NOT NULL,
        `teacher_id` int(11) DEFAULT NULL,
        `room` varchar(50) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (mysqli_query($conn, $query)) {
        // Check if teacher_name column exists
        $check_teacher_name = mysqli_query($conn, "SHOW COLUMNS FROM SHS_Schedule_List LIKE 'teacher_name'");
        if (!$check_teacher_name || mysqli_num_rows($check_teacher_name) == 0) {
            // Add teacher_name column if it doesn't exist
            mysqli_query($conn, "ALTER TABLE SHS_Schedule_List ADD COLUMN teacher_name varchar(255) NOT NULL AFTER end_time");
        }
        
        // Check if teacher_id column exists
        $check_teacher_id = mysqli_query($conn, "SHOW COLUMNS FROM SHS_Schedule_List LIKE 'teacher_id'");
        if (!$check_teacher_id || mysqli_num_rows($check_teacher_id) == 0) {
            // Add teacher_id column if it doesn't exist
            mysqli_query($conn, "ALTER TABLE SHS_Schedule_List ADD COLUMN teacher_id int(11) DEFAULT NULL AFTER teacher_name");
        }
        
        return true;
    } else {
        return false;
    }
}

// Function to create schedules table if it doesn't exist (legacy table)
function createLegacySchedulesTable($conn) {
    $query = "CREATE TABLE IF NOT EXISTS `schedules` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `teacher_id` int(11) NOT NULL,
        `subject` varchar(255) NOT NULL,
        `section` varchar(255) NOT NULL,
        `grade_level` varchar(50) NOT NULL,
        `day_of_week` varchar(20) NOT NULL,
        `start_time` time NOT NULL,
        `end_time` time NOT NULL,
        `room` varchar(50) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (mysqli_query($conn, $query)) {
        // Check if day column exists
        $check_day = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day'");
        if (!$check_day || mysqli_num_rows($check_day) == 0) {
            // Add day column if it doesn't exist (for compatibility)
            mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN day varchar(20) NOT NULL");
            
            // Update day column to match day_of_week
            mysqli_query($conn, "UPDATE schedules SET day = day_of_week");
        }
        
        // Check if day_of_week column exists
        $check_day_of_week = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day_of_week'");
        if (!$check_day_of_week || mysqli_num_rows($check_day_of_week) == 0) {
            // If day_of_week doesn't exist but day does, rename day to day_of_week
            $check_day_again = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day'");
            if ($check_day_again && mysqli_num_rows($check_day_again) > 0) {
                mysqli_query($conn, "ALTER TABLE schedules CHANGE COLUMN day day_of_week varchar(20) NOT NULL");
            } else {
                // Neither exists, add day_of_week
                mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN day_of_week varchar(20) NOT NULL AFTER grade_level");
            }
        }
        
        // Check for time column variations
        // Check if time_start exists
        $check_time_start = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'time_start'");
        if (!$check_time_start || mysqli_num_rows($check_time_start) == 0) {
            // Check if start_time exists
            $check_start_time = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'start_time'");
            if ($check_start_time && mysqli_num_rows($check_start_time) > 0) {
                // Add time_start as an alias to start_time
                mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN time_start time NOT NULL");
                mysqli_query($conn, "UPDATE schedules SET time_start = start_time");
            } else {
                // Neither exists, add both
                mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN start_time time NOT NULL AFTER day_of_week");
                mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN time_start time NOT NULL AFTER start_time");
            }
        } else {
            // time_start exists, check if start_time exists
            $check_start_time = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'start_time'");
            if (!$check_start_time || mysqli_num_rows($check_start_time) == 0) {
                // Add start_time as an alias to time_start
                mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN start_time time NOT NULL AFTER day_of_week");
                mysqli_query($conn, "UPDATE schedules SET start_time = time_start");
            }
        }
        
        // Check if time_end exists
        $check_time_end = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'time_end'");
        if (!$check_time_end || mysqli_num_rows($check_time_end) == 0) {
            // Check if end_time exists
            $check_end_time = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'end_time'");
            if ($check_end_time && mysqli_num_rows($check_end_time) > 0) {
                // Add time_end as an alias to end_time
                mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN time_end time NOT NULL");
                mysqli_query($conn, "UPDATE schedules SET time_end = end_time");
            } else {
                // Neither exists, add both
                mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN end_time time NOT NULL AFTER start_time");
                mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN time_end time NOT NULL AFTER end_time");
            }
        } else {
            // time_end exists, check if end_time exists
            $check_end_time = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'end_time'");
            if (!$check_end_time || mysqli_num_rows($check_end_time) == 0) {
                // Add end_time as an alias to time_end
                mysqli_query($conn, "ALTER TABLE schedules ADD COLUMN end_time time NOT NULL AFTER time_start");
                mysqli_query($conn, "UPDATE schedules SET end_time = time_end");
            }
        }
        
        return true;
    } else {
        return false;
    }
}

// Function to synchronize time columns in schedules table
function synchronizeTimeColumns($conn) {
    // Check if all time columns exist
    $check_time_start = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'time_start'");
    $check_time_end = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'time_end'");
    $check_start_time = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'start_time'");
    $check_end_time = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'end_time'");
    
    $time_start_exists = $check_time_start && mysqli_num_rows($check_time_start) > 0;
    $time_end_exists = $check_time_end && mysqli_num_rows($check_time_end) > 0;
    $start_time_exists = $check_start_time && mysqli_num_rows($check_start_time) > 0;
    $end_time_exists = $check_end_time && mysqli_num_rows($check_end_time) > 0;
    
    // Only proceed if at least one pair of columns exists
    if (($time_start_exists && $time_end_exists) || ($start_time_exists && $end_time_exists)) {
        // Synchronize time_start and start_time if both exist
        if ($time_start_exists && $start_time_exists) {
            mysqli_query($conn, "UPDATE schedules SET time_start = start_time WHERE time_start != start_time OR time_start IS NULL");
            mysqli_query($conn, "UPDATE schedules SET start_time = time_start WHERE start_time != time_start OR start_time IS NULL");
        }
        
        // Synchronize time_end and end_time if both exist
        if ($time_end_exists && $end_time_exists) {
            mysqli_query($conn, "UPDATE schedules SET time_end = end_time WHERE time_end != end_time OR time_end IS NULL");
            mysqli_query($conn, "UPDATE schedules SET end_time = time_end WHERE end_time != time_end OR end_time IS NULL");
        }
        
        return true;
    }
    
    return false;
}

// Function to synchronize day columns in schedules table
function synchronizeDayColumns($conn) {
    // Check if both day and day_of_week columns exist
    $check_day = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day'");
    $check_day_of_week = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day_of_week'");
    
    if ($check_day && mysqli_num_rows($check_day) > 0 && 
        $check_day_of_week && mysqli_num_rows($check_day_of_week) > 0) {
        
        // Update day to match day_of_week
        mysqli_query($conn, "UPDATE schedules SET day = day_of_week WHERE day != day_of_week OR day IS NULL");
        
        // Update day_of_week to match day
        mysqli_query($conn, "UPDATE schedules SET day_of_week = day WHERE day_of_week != day OR day_of_week IS NULL");
        
        return true;
    }
    
    return false;
}

// Function to sync data between schedule tables
function syncScheduleData($conn) {
    $result = [
        'success' => false,
        'imported' => 0,
        'errors' => []
    ];
    
    // Check which tables exist
    $schedule_exists = tableExists($conn, 'schedule');
    $shs_schedule_exists = tableExists($conn, 'SHS_Schedule_List');
    $schedules_exists = tableExists($conn, 'schedules');
    
    if (!$schedule_exists && !$shs_schedule_exists && !$schedules_exists) {
        $result['errors'][] = "No schedule tables found to sync data from.";
        return $result;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Import data from schedules to schedule if schedules exists and schedule exists
        if ($schedules_exists && $schedule_exists) {
            // Check if day column exists in schedules
            $day_column = 'day_of_week';
            $check_day = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day'");
            if ($check_day && mysqli_num_rows($check_day) > 0) {
                $day_column = 'day';
            }
            
            // Check which time columns exist
            $start_time_col = 'start_time';
            $end_time_col = 'end_time';
            $check_time_start = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'time_start'");
            if ($check_time_start && mysqli_num_rows($check_time_start) > 0) {
                $start_time_col = 'time_start';
                $end_time_col = 'time_end';
            }
            
            // Get existing IDs from schedule table to avoid duplicates
            $existing_ids = [];
            $existing_query = "SELECT teacher_id, subject, section, day, time_start, time_end, room FROM schedule";
            $existing_result = mysqli_query($conn, $existing_query);
            if ($existing_result) {
                while ($row = mysqli_fetch_assoc($existing_result)) {
                    $key = $row['teacher_id'] . '|' . $row['subject'] . '|' . $row['section'] . '|' . 
                           $row['day'] . '|' . $row['time_start'] . '|' . $row['time_end'] . '|' . $row['room'];
                    $existing_ids[$key] = true;
                }
            }
            
            // Import data from schedules to schedule
            $import_query = "INSERT INTO schedule (teacher_id, subject, section, grade_level, day, time_start, time_end, room) 
                             SELECT teacher_id, subject, section, grade_level, $day_column, $start_time_col, $end_time_col, room 
                             FROM schedules";
            $import_stmt = mysqli_prepare($conn, $import_query);
            
            if ($import_stmt) {
                mysqli_stmt_execute($import_stmt);
                $imported = mysqli_stmt_affected_rows($import_stmt);
                $result['imported'] += $imported;
                mysqli_stmt_close($import_stmt);
            } else {
                $result['errors'][] = "Error preparing import from schedules: " . mysqli_error($conn);
            }
        }
        
        // 2. Import data from SHS_Schedule_List to schedule if SHS_Schedule_List exists and schedule exists
        if ($shs_schedule_exists && $schedule_exists) {
            // Check if teacher_id column exists in SHS_Schedule_List
            $teacher_id_exists = false;
            $check_teacher_id = mysqli_query($conn, "SHOW COLUMNS FROM SHS_Schedule_List LIKE 'teacher_id'");
            if ($check_teacher_id && mysqli_num_rows($check_teacher_id) > 0) {
                $teacher_id_exists = true;
            }
            
            // Get existing IDs from schedule table to avoid duplicates
            $existing_ids = [];
            $existing_query = "SELECT teacher_id, subject, section, day, time_start, time_end, room FROM schedule";
            $existing_result = mysqli_query($conn, $existing_query);
            if ($existing_result) {
                while ($row = mysqli_fetch_assoc($existing_result)) {
                    $key = $row['teacher_id'] . '|' . $row['subject'] . '|' . $row['section'] . '|' . 
                           $row['day'] . '|' . $row['time_start'] . '|' . $row['time_end'] . '|' . $row['room'];
                    $existing_ids[$key] = true;
                }
            }
            
            // Import data from SHS_Schedule_List to schedule
            $import_query = "INSERT INTO schedule (teacher_id, subject, section, grade_level, day, time_start, time_end, room) 
                             SELECT " . ($teacher_id_exists ? "teacher_id" : "0") . ", 
                             subject, section, grade_level, day_of_week, start_time, end_time, room 
                             FROM SHS_Schedule_List";
            $import_stmt = mysqli_prepare($conn, $import_query);
            
            if ($import_stmt) {
                mysqli_stmt_execute($import_stmt);
                $imported = mysqli_stmt_affected_rows($import_stmt);
                $result['imported'] += $imported;
                mysqli_stmt_close($import_stmt);
            } else {
                $result['errors'][] = "Error preparing import from SHS_Schedule_List: " . mysqli_error($conn);
            }
        }
        
        // 3. Import data from schedule to SHS_Schedule_List if schedule exists and SHS_Schedule_List exists
        if ($schedule_exists && $shs_schedule_exists) {
            // Check if teacher_id column exists in SHS_Schedule_List
            $teacher_id_exists = false;
            $check_teacher_id = mysqli_query($conn, "SHOW COLUMNS FROM SHS_Schedule_List LIKE 'teacher_id'");
            if ($check_teacher_id && mysqli_num_rows($check_teacher_id) > 0) {
                $teacher_id_exists = true;
            }
            
            // Get existing IDs from SHS_Schedule_List table to avoid duplicates
            $existing_ids = [];
            $existing_query = "SELECT subject, section, day_of_week, start_time, end_time, room FROM SHS_Schedule_List";
            $existing_result = mysqli_query($conn, $existing_query);
            if ($existing_result) {
                while ($row = mysqli_fetch_assoc($existing_result)) {
                    $key = $row['subject'] . '|' . $row['section'] . '|' . $row['day_of_week'] . '|' . 
                           $row['start_time'] . '|' . $row['end_time'] . '|' . $row['room'];
                    $existing_ids[$key] = true;
                }
            }
            
            // Get teacher names for teacher_id values
            $teacher_names = [];
            $teachers_query = "SELECT id, CONCAT(firstname, ' ', lastname) as full_name FROM teachers";
            $teachers_result = mysqli_query($conn, $teachers_query);
            if ($teachers_result) {
                while ($row = mysqli_fetch_assoc($teachers_result)) {
                    $teacher_names[$row['id']] = $row['full_name'];
                }
            }
            
            // Import data from schedule to SHS_Schedule_List
            $import_query = "INSERT INTO SHS_Schedule_List (subject, section, grade_level, day_of_week, start_time, end_time, teacher_name" . 
                            ($teacher_id_exists ? ", teacher_id" : "") . ", room) 
                            SELECT s.subject, s.section, s.grade_level, s.day, s.time_start, s.time_end, 
                            COALESCE((SELECT CONCAT(t.firstname, ' ', t.lastname) FROM teachers t WHERE t.id = s.teacher_id), 'Unknown Teacher')" .
                            ($teacher_id_exists ? ", s.teacher_id" : "") . ", s.room 
                            FROM schedule s";
            $import_stmt = mysqli_prepare($conn, $import_query);
            
            if ($import_stmt) {
                mysqli_stmt_execute($import_stmt);
                $imported = mysqli_stmt_affected_rows($import_stmt);
                $result['imported'] += $imported;
                mysqli_stmt_close($import_stmt);
            } else {
                $result['errors'][] = "Error preparing import from schedule: " . mysqli_error($conn);
            }
        }
        
        // Commit transaction if successful
        mysqli_commit($conn);
        $result['success'] = true;
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $result['errors'][] = "Error syncing schedule data: " . $e->getMessage();
    }
    
    return $result;
}

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return $result && mysqli_num_rows($result) > 0;
}

// Function to add sample data to schedule table (DEPRECATED - use syncScheduleData instead)
function addSampleDataToSchedule($conn) {
    // This function is kept for backward compatibility
    // We now recommend using syncScheduleData instead
    return syncScheduleData($conn)['success'];
}

// Function to add sample data to SHS_Schedule_List table (DEPRECATED - use syncScheduleData instead)
function addSampleDataToSHSSchedule($conn) {
    // This function is kept for backward compatibility
    // We now recommend using syncScheduleData instead
    return syncScheduleData($conn)['success'];
}

// Process form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_tables'])) {
        $schedule_created = createScheduleTable($conn);
        $shs_schedule_created = createSHSScheduleTable($conn);
        $legacy_schedules_created = createLegacySchedulesTable($conn);
        
        // Synchronize columns if needed
        $day_columns_synced = synchronizeDayColumns($conn);
        $time_columns_synced = synchronizeTimeColumns($conn);
        
        if ($schedule_created && $shs_schedule_created && $legacy_schedules_created) {
            $message = 'All schedule tables created successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error creating one or more tables.';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['add_sample_data'])) {
        // Use the sync function instead of adding sample data
        $sync_result = syncScheduleData($conn);
        
        // Synchronize columns after adding data
        $day_columns_synced = synchronizeDayColumns($conn);
        $time_columns_synced = synchronizeTimeColumns($conn);
        
        if ($sync_result['success']) {
            $message = 'Schedule data synchronized successfully! ' . $sync_result['imported'] . ' records imported.';
            $message_type = 'success';
            
            if (!empty($sync_result['errors'])) {
                $message .= ' Some non-critical errors occurred.';
                $message_type = 'warning';
            }
        } else {
            $message = 'Error synchronizing schedule data.';
            if (!empty($sync_result['errors'])) {
                $message .= ' ' . implode(' ', $sync_result['errors']);
            }
            $message_type = 'danger';
        }
    } elseif (isset($_POST['sync_day_columns'])) {
        // Synchronize day columns
        $day_columns_synced = synchronizeDayColumns($conn);
        
        if ($day_columns_synced) {
            $message = 'Day columns synchronized successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error synchronizing day columns or columns do not exist.';
            $message_type = 'warning';
        }
    } elseif (isset($_POST['sync_time_columns'])) {
        // Synchronize time columns
        $time_columns_synced = synchronizeTimeColumns($conn);
        
        if ($time_columns_synced) {
            $message = 'Time columns synchronized successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error synchronizing time columns or columns do not exist.';
            $message_type = 'warning';
        }
    } elseif (isset($_POST['sync_all_columns'])) {
        // Synchronize all columns
        $day_columns_synced = synchronizeDayColumns($conn);
        $time_columns_synced = synchronizeTimeColumns($conn);
        
        if ($day_columns_synced && $time_columns_synced) {
            $message = 'All columns synchronized successfully!';
            $message_type = 'success';
        } elseif ($day_columns_synced || $time_columns_synced) {
            $message = 'Some columns synchronized successfully.';
            $message_type = 'warning';
        } else {
            $message = 'Error synchronizing columns or columns do not exist.';
            $message_type = 'warning';
        }
    }
}

// Check if tables exist
$schedule_exists = false;
$shs_schedule_exists = false;
$legacy_schedules_exists = false;

$check_schedule = mysqli_query($conn, "SHOW TABLES LIKE 'schedule'");
if ($check_schedule && mysqli_num_rows($check_schedule) > 0) {
    $schedule_exists = true;
}

$check_shs_schedule = mysqli_query($conn, "SHOW TABLES LIKE 'SHS_Schedule_List'");
if ($check_shs_schedule && mysqli_num_rows($check_shs_schedule) > 0) {
    $shs_schedule_exists = true;
}

$check_legacy_schedules = mysqli_query($conn, "SHOW TABLES LIKE 'schedules'");
if ($check_legacy_schedules && mysqli_num_rows($check_legacy_schedules) > 0) {
    $legacy_schedules_exists = true;
}

// Count records in each table
$schedule_count = 0;
$shs_schedule_count = 0;
$legacy_schedules_count = 0;

if ($schedule_exists) {
    $count_query = "SELECT COUNT(*) as count FROM schedule";
    $result = mysqli_query($conn, $count_query);
    $row = mysqli_fetch_assoc($result);
    $schedule_count = $row['count'];
}

if ($shs_schedule_exists) {
    $count_query = "SELECT COUNT(*) as count FROM SHS_Schedule_List";
    $result = mysqli_query($conn, $count_query);
    $row = mysqli_fetch_assoc($result);
    $shs_schedule_count = $row['count'];
}

if ($legacy_schedules_exists) {
    $count_query = "SELECT COUNT(*) as count FROM schedules";
    $result = mysqli_query($conn, $count_query);
    $row = mysqli_fetch_assoc($result);
    $legacy_schedules_count = $row['count'];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Setup Schedule Tables</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Setup Schedule Tables</li>
    </ol>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i> Schedule Tables Status
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Status</th>
                                <th>Records</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>schedule</code> (Main)</td>
                                <td>
                                    <?php if ($schedule_exists): ?>
                                        <span class="badge bg-success">Exists</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $schedule_count; ?></td>
                            </tr>
                            <tr>
                                <td><code>SHS_Schedule_List</code> (SHS)</td>
                                <td>
                                    <?php if ($shs_schedule_exists): ?>
                                        <span class="badge bg-success">Exists</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $shs_schedule_count; ?></td>
                            </tr>
                            <tr>
                                <td><code>schedules</code> (Legacy)</td>
                                <td>
                                    <?php if ($legacy_schedules_exists): ?>
                                        <span class="badge bg-success">Exists</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $legacy_schedules_count; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-cog me-1"></i> Setup Actions
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-3">
                        <div class="d-grid gap-2">
                            <button type="submit" name="create_tables" class="btn btn-primary">
                                <i class="fas fa-table me-1"></i> Create Missing Tables
                            </button>
                        </div>
                    </form>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-3">
                        <div class="d-grid gap-2">
                            <button type="submit" name="add_sample_data" class="btn btn-primary">
                                <i class="fas fa-exchange-alt me-1"></i> Synchronize Schedule Data Between Tables
                            </button>
                        </div>
                        <small class="text-muted">This will copy existing schedule data between tables to ensure consistency.</small>
                    </form>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-3">
                        <div class="d-grid gap-2">
                            <button type="submit" name="sync_day_columns" class="btn btn-warning">
                                <i class="fas fa-sync me-1"></i> Synchronize Day Columns
                            </button>
                        </div>
                    </form>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-3">
                        <div class="d-grid gap-2">
                            <button type="submit" name="sync_time_columns" class="btn btn-warning">
                                <i class="fas fa-clock me-1"></i> Synchronize Time Columns
                            </button>
                        </div>
                    </form>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="d-grid gap-2">
                            <button type="submit" name="sync_all_columns" class="btn btn-info">
                                <i class="fas fa-sync-alt me-1"></i> Synchronize All Columns
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-link me-1"></i> Related Links
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?php echo $relative_path; ?>modules/admin/schedule.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar me-1"></i> Manage Schedule
                        </a>
                        <a href="<?php echo $relative_path; ?>modules/teacher/schedule.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-tie me-1"></i> Teacher Schedule View
                        </a>
                        <a href="<?php echo $relative_path; ?>modules/reports/schedule_report.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt me-1"></i> Schedule Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once $relative_path . 'includes/footer.php';
?> 
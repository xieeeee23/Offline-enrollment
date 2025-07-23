<?php
$title = 'Class Schedule Report';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin, registrar or teacher role
if (!checkAccess(['admin', 'registrar', 'teacher'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Get filter parameters
$grade_filter = isset($_GET['grade']) ? cleanInput($_GET['grade']) : null;
$section_filter = isset($_GET['section']) ? cleanInput($_GET['section']) : null;
$teacher_filter = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : null;
$day_filter = isset($_GET['day']) ? cleanInput($_GET['day']) : null;

// Check if this is an Excel export request
$export_excel = isset($_GET['export']) && $_GET['export'] === 'excel';

// Debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Function to log debug information
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

// Check if tables exist
$check_table_query = "SHOW TABLES LIKE 'SHS_Schedule_List'";
$check_table_result = mysqli_query($conn, $check_table_query);
$use_shs_schedule_list = ($check_table_result && mysqli_num_rows($check_table_result) > 0);

$check_schedule_table_query = "SHOW TABLES LIKE 'schedule'";
$check_schedule_table_result = mysqli_query($conn, $check_schedule_table_query);
$use_schedule_table = ($check_schedule_table_result && mysqli_num_rows($check_schedule_table_result) > 0);

$check_schedules_table_query = "SHOW TABLES LIKE 'schedules'";
$check_schedules_table_result = mysqli_query($conn, $check_schedules_table_query);
$use_schedules_table = ($check_schedules_table_result && mysqli_num_rows($check_schedules_table_result) > 0);

// Check teacher table structure for column names
$first_name_column = 'first_name';
$last_name_column = 'last_name';

$check_firstname = mysqli_query($conn, "SHOW COLUMNS FROM teachers LIKE 'firstname'");
if ($check_firstname && mysqli_num_rows($check_firstname) > 0) {
    $first_name_column = 'firstname';
}

$check_lastname = mysqli_query($conn, "SHOW COLUMNS FROM teachers LIKE 'lastname'");
if ($check_lastname && mysqli_num_rows($check_lastname) > 0) {
    $last_name_column = 'lastname';
}

debug_log('Teacher name columns', "First name: $first_name_column, Last name: $last_name_column");
debug_log('Tables', "SHS_Schedule_List: " . ($use_shs_schedule_list ? 'Yes' : 'No') . 
                    ", schedule: " . ($use_schedule_table ? 'Yes' : 'No') .
                    ", schedules: " . ($use_schedules_table ? 'Yes' : 'No'));

// Initialize schedules array
$schedules = [];

// Build the query based on filters
if ($use_shs_schedule_list) {
    // Using SHS_Schedule_List table
    $query = "SELECT s.*, 
              CONCAT(t.$first_name_column, ' ', t.$last_name_column) as teacher_name,
              t.id as teacher_id,
              s.day_of_week as day,
              s.start_time as time_start,
              s.end_time as time_end,
              'SHS_Schedule_List' as source
              FROM SHS_Schedule_List s
              LEFT JOIN teachers t ON s.teacher_id = t.id
              WHERE 1=1";

    if (!empty($grade_filter)) {
        $query .= " AND s.grade_level = '" . mysqli_real_escape_string($conn, $grade_filter) . "'";
    }

    if (!empty($section_filter)) {
        $query .= " AND s.section = '" . mysqli_real_escape_string($conn, $section_filter) . "'";
    }

    if (!empty($teacher_filter)) {
        $query .= " AND s.teacher_id = " . $teacher_filter;
    }

    if (!empty($day_filter)) {
        $query .= " AND s.day_of_week = '" . mysqli_real_escape_string($conn, $day_filter) . "'";
    }

    $query .= " ORDER BY s.day_of_week, s.start_time, s.grade_level, s.section";
    
    debug_log('SHS_Schedule_List query', $query);
    
    // Execute query for SHS_Schedule_List
    $result = mysqli_query($conn, $query);
    if (!$result) {
        debug_log('Error fetching SHS schedules', mysqli_error($conn));
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            $schedules[] = $row;
        }
        debug_log('SHS_Schedule_List records found', count($schedules));
    }
}

if ($use_schedule_table) {
    // Using original schedule table
    $query = "SELECT s.*, 
              CONCAT(t.$first_name_column, ' ', t.$last_name_column) as teacher_name,
              t.id as teacher_id,
              'schedule' as source
              FROM schedule s
              LEFT JOIN teachers t ON s.teacher_id = t.id
              WHERE 1=1";

    if (!empty($grade_filter)) {
        $query .= " AND s.grade_level = '" . mysqli_real_escape_string($conn, $grade_filter) . "'";
    }

    if (!empty($section_filter)) {
        $query .= " AND s.section = '" . mysqli_real_escape_string($conn, $section_filter) . "'";
    }

    if (!empty($teacher_filter)) {
        $query .= " AND s.teacher_id = " . $teacher_filter;
    }

    if (!empty($day_filter)) {
        $query .= " AND s.day = '" . mysqli_real_escape_string($conn, $day_filter) . "'";
    }

    $query .= " ORDER BY s.day, s.time_start, s.grade_level, s.section";
    
    debug_log('Schedule query', $query);
    
    // Execute query for schedule table
    $result = mysqli_query($conn, $query);
    if (!$result) {
        debug_log('Error fetching regular schedules', mysqli_error($conn));
} else {
        $schedule_count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $schedules[] = $row;
            $schedule_count++;
        }
        debug_log('Schedule records found', $schedule_count);
    }
}

if ($use_schedules_table) {
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
    
    // Check for day column variations
    $day_column_exists = false;
    $check_day = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day'");
    if ($check_day && mysqli_num_rows($check_day) > 0) {
        $day_column_exists = true;
    }
    
    $day_of_week_column_exists = false;
    $check_day_of_week = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day_of_week'");
    if ($check_day_of_week && mysqli_num_rows($check_day_of_week) > 0) {
        $day_of_week_column_exists = true;
    }
    
    // Determine which day column to use
    $day_column = $day_column_exists ? 'day' : ($day_of_week_column_exists ? 'day_of_week' : 'day_of_week');
    
    debug_log('Schedules table columns', "Day column: $day_column, Start time column: $start_time_column, End time column: $end_time_column");
    
    // Using legacy schedules table
    $query = "SELECT s.*, 
              CONCAT(t.$first_name_column, ' ', t.$last_name_column) as teacher_name,
              t.id as teacher_id,
              s.$day_column as day,
              s.$start_time_column as time_start,
              s.$end_time_column as time_end,
              'schedules' as source
              FROM schedules s
              LEFT JOIN teachers t ON s.teacher_id = t.id
              WHERE 1=1";

    if (!empty($grade_filter)) {
        $query .= " AND s.grade_level = '" . mysqli_real_escape_string($conn, $grade_filter) . "'";
    }

    if (!empty($section_filter)) {
        $query .= " AND s.section = '" . mysqli_real_escape_string($conn, $section_filter) . "'";
    }

    if (!empty($teacher_filter)) {
        $query .= " AND s.teacher_id = " . $teacher_filter;
    }

    if (!empty($day_filter)) {
        $query .= " AND s.$day_column = '" . mysqli_real_escape_string($conn, $day_filter) . "'";
    }

    $query .= " ORDER BY s.$day_column, s.$start_time_column, s.grade_level, s.section";
    
    debug_log('Schedules query', $query);
    
    // Execute query for schedules table
$result = mysqli_query($conn, $query);
if (!$result) {
        debug_log('Error fetching legacy schedules', mysqli_error($conn));
} else {
        $schedules_count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $schedules[] = $row;
            $schedules_count++;
        }
        debug_log('Schedules records found', $schedules_count);
    }
}

debug_log('Total schedules found', count($schedules));

// Get unique grade levels for filter
$grades = [];

// Get grades from SHS_Schedule_List if it exists
if ($use_shs_schedule_list) {
    $grade_query = "SELECT DISTINCT grade_level FROM SHS_Schedule_List ORDER BY grade_level";
$grade_result = mysqli_query($conn, $grade_query);
if ($grade_result) {
    while ($row = mysqli_fetch_assoc($grade_result)) {
        $grades[] = $row['grade_level'];
    }
}
}

// Get grades from schedule table if it exists
if ($use_schedule_table) {
    $grade_query = "SELECT DISTINCT grade_level FROM schedule ORDER BY grade_level";
    $grade_result = mysqli_query($conn, $grade_query);
    if ($grade_result) {
        while ($row = mysqli_fetch_assoc($grade_result)) {
            if (!in_array($row['grade_level'], $grades)) {
                $grades[] = $row['grade_level'];
            }
        }
    }
}

// Get grades from schedules table if it exists
if ($use_schedules_table) {
    $grade_query = "SELECT DISTINCT grade_level FROM schedules ORDER BY grade_level";
    $grade_result = mysqli_query($conn, $grade_query);
    if ($grade_result) {
        while ($row = mysqli_fetch_assoc($grade_result)) {
            if (!in_array($row['grade_level'], $grades)) {
                $grades[] = $row['grade_level'];
            }
        }
    }
}

// Sort grades
sort($grades);
debug_log('Available grades', $grades);

// Get unique sections for filter
$sections = [];

// Get sections from SHS_Schedule_List if it exists
if ($use_shs_schedule_list) {
    $section_query = "SELECT DISTINCT section FROM SHS_Schedule_List ORDER BY section";
$section_result = mysqli_query($conn, $section_query);
if ($section_result) {
    while ($row = mysqli_fetch_assoc($section_result)) {
        $sections[] = $row['section'];
    }
}
}

// Get sections from schedule table if it exists
if ($use_schedule_table) {
    $section_query = "SELECT DISTINCT section FROM schedule ORDER BY section";
    $section_result = mysqli_query($conn, $section_query);
    if ($section_result) {
        while ($row = mysqli_fetch_assoc($section_result)) {
            if (!in_array($row['section'], $sections)) {
                $sections[] = $row['section'];
            }
        }
    }
}

// Get sections from schedules table if it exists
if ($use_schedules_table) {
    $section_query = "SELECT DISTINCT section FROM schedules ORDER BY section";
    $section_result = mysqli_query($conn, $section_query);
    if ($section_result) {
        while ($row = mysqli_fetch_assoc($section_result)) {
            if (!in_array($row['section'], $sections)) {
                $sections[] = $row['section'];
            }
        }
    }
}

// Sort sections
sort($sections);
debug_log('Available sections', $sections);

// Get all teachers for filter
$teachers = [];
$teacher_query = "SELECT id, $first_name_column as first_name, $last_name_column as last_name FROM teachers WHERE status = 'active' ORDER BY $last_name_column, $first_name_column";
$teacher_result = mysqli_query($conn, $teacher_query);
if ($teacher_result) {
    while ($row = mysqli_fetch_assoc($teacher_result)) {
        $teachers[] = $row;
    }
}
debug_log('Available teachers', count($teachers));

// Days of the week for filter
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Export to Excel if requested
if ($export_excel) {
    // Set headers for Excel file download
    $filename = 'class_schedule_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Set UTF-8 BOM for Excel to recognize UTF-8 encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write report title and metadata
    fputcsv($output, [SYSTEM_NAME]);
    fputcsv($output, ['CLASS SCHEDULE REPORT']);
    fputcsv($output, ['Date Generated: ' . date('F d, Y')]);
    
    // Filter information
    $filter_text = '';
    
    if (!empty($grade_filter)) {
        $filter_text .= 'Grade: ' . $grade_filter;
    }
    
    if (!empty($section_filter)) {
        $filter_text .= ($filter_text ? ' | ' : '') . 'Section: ' . $section_filter;
    }
    
    if (!empty($teacher_filter)) {
        foreach ($teachers as $teacher) {
            if ($teacher['id'] == $teacher_filter) {
                $filter_text .= ($filter_text ? ' | ' : '') . 'Teacher: ' . $teacher['last_name'] . ', ' . $teacher['first_name'];
                break;
            }
        }
    }
    
    if (!empty($day_filter)) {
        $filter_text .= ($filter_text ? ' | ' : '') . 'Day: ' . $day_filter;
    }
    
    if (empty($filter_text)) {
        $filter_text = 'All Schedules';
    }
    
    fputcsv($output, ['Filters: ' . $filter_text]);
    fputcsv($output, []); // Empty line
    
    // Table header
    fputcsv($output, ['Day', 'Start Time', 'End Time', 'Grade Level', 'Section', 'Subject', 'Teacher', 'Room']);
    
    // Table data
    foreach ($schedules as $schedule) {
        $day = isset($schedule['day']) ? $schedule['day'] : '';
        $start_time = isset($schedule['time_start']) ? formatTime($schedule['time_start']) : '';
        $end_time = isset($schedule['time_end']) ? formatTime($schedule['time_end']) : '';
        
        fputcsv($output, [
            $day,
            $start_time,
            $end_time,
            $schedule['grade_level'],
            $schedule['section'],
            $schedule['subject'],
            $schedule['teacher_name'] ?? 'Not Assigned',
            $schedule['room'] ?? 'TBA'
        ]);
    }
    
    // Close output stream
    fclose($output);
    exit;
}

// Helper function to format time
function formatTime($time) {
    if (empty($time)) return '';
    return date('h:i A', strtotime($time));
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Class Schedule Report</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Filter Schedules</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="grade" class="form-label">Grade Level</label>
                        <select class="form-select" id="grade" name="grade">
                            <option value="">All Grades</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?php echo $grade; ?>" <?php echo ($grade_filter == $grade) ? 'selected' : ''; ?>>
                                    <?php echo $grade; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo $section; ?>" <?php echo ($section_filter == $section) ? 'selected' : ''; ?>>
                                    <?php echo $section; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="day" class="form-label">Day</label>
                        <select class="form-select" id="day" name="day">
                            <option value="">All Days</option>
                            <?php foreach ($days as $day): ?>
                                <option value="<?php echo $day; ?>" <?php echo ($day_filter == $day) ? 'selected' : ''; ?>>
                                    <?php echo $day; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="teacher_id" class="form-label">Teacher</label>
                        <select class="form-select" id="teacher_id" name="teacher_id">
                            <option value="">All Teachers</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php echo ($teacher_filter == $teacher['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['last_name'] . ', ' . $teacher['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-12 d-flex justify-content-end">
                        <input type="hidden" name="debug" value="<?php echo $debug ? '1' : '0'; ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Class Schedule</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2 btn-print" data-print-target="#schedule-table">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-light btn-export-excel" data-table-id="schedule-table" data-filename="schedule_report_<?php echo date('Y-m-d'); ?>">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="schedule-table" class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Grade & Section</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($schedules)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No schedules found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($schedule['day'] ?? ''); ?></td>
                                        <td>
                                            <?php 
                                            $start_time = $schedule['time_start'] ?? '';
                                            $end_time = $schedule['time_end'] ?? '';
                                            if (!empty($start_time) && !empty($end_time)) {
                                                echo formatTime($start_time) . ' - ' . formatTime($end_time);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['grade_level'] . ' ' . $schedule['section']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                        <td>
                                            <?php if (!empty($schedule['teacher_name'])): ?>
                                                <?php echo htmlspecialchars($schedule['teacher_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($schedule['room'] ?? 'TBA'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted">
                Total classes: <?php echo count($schedules); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
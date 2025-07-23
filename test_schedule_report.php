<?php
require_once 'includes/config.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Schedule Report Test</h1>";

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

echo "<h2>Tables</h2>";
echo "SHS_Schedule_List exists: " . ($use_shs_schedule_list ? 'Yes' : 'No') . "<br>";
echo "schedule exists: " . ($use_schedule_table ? 'Yes' : 'No') . "<br>";
echo "schedules exists: " . ($use_schedules_table ? 'Yes' : 'No') . "<br>";

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

echo "<h2>Teacher Table Structure</h2>";
echo "First name column: $first_name_column<br>";
echo "Last name column: $last_name_column<br>";

// Initialize schedules array
$schedules = [];
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : null;
$section_filter = isset($_GET['section']) ? $_GET['section'] : null;
$teacher_filter = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : null;
$day_filter = isset($_GET['day']) ? $_GET['day'] : null;

echo "<h2>Current Filters</h2>";
echo "Grade: " . ($grade_filter ?: 'All') . "<br>";
echo "Section: " . ($section_filter ?: 'All') . "<br>";
echo "Teacher ID: " . ($teacher_filter ?: 'All') . "<br>";
echo "Day: " . ($day_filter ?: 'All') . "<br>";

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
    
    echo "<h3>SHS_Schedule_List Query</h3>";
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
    
    // Execute query for SHS_Schedule_List
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo "<div style='color: red;'>Error fetching SHS schedules: " . mysqli_error($conn) . "</div>";
    } else {
        $shs_count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $schedules[] = $row;
            $shs_count++;
        }
        echo "SHS_Schedule_List records found: $shs_count<br>";
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
    
    echo "<h3>Schedule Query</h3>";
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
    
    // Execute query for schedule table
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo "<div style='color: red;'>Error fetching regular schedules: " . mysqli_error($conn) . "</div>";
    } else {
        $schedule_count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $schedules[] = $row;
            $schedule_count++;
        }
        echo "Schedule records found: $schedule_count<br>";
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
    
    echo "<h3>Schedules Table Columns</h3>";
    echo "Day column: $day_column<br>";
    echo "Start time column: $start_time_column<br>";
    echo "End time column: $end_time_column<br>";
    
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
    
    echo "<h3>Schedules Query</h3>";
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
    
    // Execute query for schedules table
    $result = mysqli_query($conn, $query);
    if (!$result) {
        echo "<div style='color: red;'>Error fetching legacy schedules: " . mysqli_error($conn) . "</div>";
    } else {
        $schedules_count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $schedules[] = $row;
            $schedules_count++;
        }
        echo "Schedules records found: $schedules_count<br>";
    }
}

echo "<h2>Total Schedules Found: " . count($schedules) . "</h2>";

if (count($schedules) > 0) {
    echo "<h3>Schedule Data</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Source</th><th>Day</th><th>Time</th><th>Grade & Section</th><th>Subject</th><th>Teacher</th><th>Room</th></tr>";
    
    foreach ($schedules as $schedule) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($schedule['source'] ?? 'Unknown') . "</td>";
        echo "<td>" . htmlspecialchars($schedule['day'] ?? '') . "</td>";
        echo "<td>";
        $start_time = $schedule['time_start'] ?? '';
        $end_time = $schedule['time_end'] ?? '';
        if (!empty($start_time) && !empty($end_time)) {
            echo date('h:i A', strtotime($start_time)) . ' - ' . date('h:i A', strtotime($end_time));
        } else {
            echo 'N/A';
        }
        echo "</td>";
        echo "<td>" . htmlspecialchars($schedule['grade_level'] . ' ' . $schedule['section']) . "</td>";
        echo "<td>" . htmlspecialchars($schedule['subject']) . "</td>";
        echo "<td>";
        if (!empty($schedule['teacher_name'])) {
            echo htmlspecialchars($schedule['teacher_name']);
        } else {
            echo "<span style='color: gray;'>Not Assigned</span>";
        }
        echo "</td>";
        echo "<td>" . htmlspecialchars($schedule['room'] ?? 'TBA') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

mysqli_close($conn);
?> 
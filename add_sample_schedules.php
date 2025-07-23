<?php
require_once 'includes/config.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Adding Sample Schedule Data</h1>";

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

// Get a teacher ID
$teacher_id = 0;
$teacher_query = "SELECT id FROM teachers LIMIT 1";
$teacher_result = mysqli_query($conn, $teacher_query);
if ($teacher_result && mysqli_num_rows($teacher_result) > 0) {
    $teacher_id = mysqli_fetch_assoc($teacher_result)['id'];
} else {
    // Create a teacher if none exists
    $insert_teacher = "INSERT INTO teachers (firstname, lastname, email, phone, status) 
                      VALUES ('John', 'Doe', 'john.doe@example.com', '1234567890', 'active')";
    if (mysqli_query($conn, $insert_teacher)) {
        $teacher_id = mysqli_insert_id($conn);
        echo "Created teacher with ID: $teacher_id<br>";
    } else {
        echo "Error creating teacher: " . mysqli_error($conn) . "<br>";
    }
}

// Sample schedule data
$sample_schedules = [
    [
        'grade_level' => 'Grade 11',
        'section' => 'ABM-11A',
        'subject' => 'Business Math',
        'day' => 'Monday',
        'time_start' => '08:00:00',
        'time_end' => '09:30:00',
        'room' => '101'
    ],
    [
        'grade_level' => 'Grade 11',
        'section' => 'ABM-11A',
        'subject' => 'English',
        'day' => 'Monday',
        'time_start' => '10:00:00',
        'time_end' => '11:30:00',
        'room' => '102'
    ],
    [
        'grade_level' => 'Grade 11',
        'section' => 'STEM-11A',
        'subject' => 'Physics',
        'day' => 'Tuesday',
        'time_start' => '08:00:00',
        'time_end' => '09:30:00',
        'room' => '103'
    ],
    [
        'grade_level' => 'Grade 12',
        'section' => 'ABM-12A',
        'subject' => 'Economics',
        'day' => 'Wednesday',
        'time_start' => '13:00:00',
        'time_end' => '14:30:00',
        'room' => '201'
    ]
];

// Add to schedule table
if ($use_schedule_table) {
    echo "<h2>Adding to schedule table</h2>";
    
    // First check if there are existing records
    $count_query = "SELECT COUNT(*) as count FROM schedule";
    $count_result = mysqli_query($conn, $count_query);
    $count = mysqli_fetch_assoc($count_result)['count'];
    
    if ($count > 0) {
        echo "Schedule table already has $count records. Skipping insertion.<br>";
    } else {
        foreach ($sample_schedules as $schedule) {
            $insert_query = "INSERT INTO schedule (teacher_id, subject, section, grade_level, day, time_start, time_end, room) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "isssssss", 
                $teacher_id, 
                $schedule['subject'], 
                $schedule['section'], 
                $schedule['grade_level'], 
                $schedule['day'], 
                $schedule['time_start'], 
                $schedule['time_end'], 
                $schedule['room']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                echo "Added schedule for {$schedule['subject']} to schedule table<br>";
            } else {
                echo "Error adding schedule: " . mysqli_stmt_error($stmt) . "<br>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Add to SHS_Schedule_List table
if ($use_shs_schedule_list) {
    echo "<h2>Adding to SHS_Schedule_List table</h2>";
    
    // First check if there are existing records
    $count_query = "SELECT COUNT(*) as count FROM SHS_Schedule_List";
    $count_result = mysqli_query($conn, $count_query);
    $count = mysqli_fetch_assoc($count_result)['count'];
    
    if ($count > 0) {
        echo "SHS_Schedule_List table already has $count records. Skipping insertion.<br>";
    } else {
        // Get teacher name
        $teacher_name = "Unknown Teacher";
        $teacher_query = "SELECT CONCAT(firstname, ' ', lastname) as name FROM teachers WHERE id = ?";
        $stmt = mysqli_prepare($conn, $teacher_query);
        mysqli_stmt_bind_param($stmt, "i", $teacher_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $teacher_name = $row['name'];
        }
        mysqli_stmt_close($stmt);
        
        foreach ($sample_schedules as $schedule) {
            $insert_query = "INSERT INTO SHS_Schedule_List (subject, section, grade_level, day_of_week, start_time, end_time, teacher_name, teacher_id, room) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "sssssssss", 
                $schedule['subject'], 
                $schedule['section'], 
                $schedule['grade_level'], 
                $schedule['day'], 
                $schedule['time_start'], 
                $schedule['time_end'],
                $teacher_name,
                $teacher_id,
                $schedule['room']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                echo "Added schedule for {$schedule['subject']} to SHS_Schedule_List table<br>";
            } else {
                echo "Error adding schedule: " . mysqli_stmt_error($stmt) . "<br>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Add to schedules table
if ($use_schedules_table) {
    echo "<h2>Adding to schedules table</h2>";
    
    // First check if there are existing records
    $count_query = "SELECT COUNT(*) as count FROM schedules";
    $count_result = mysqli_query($conn, $count_query);
    $count = mysqli_fetch_assoc($count_result)['count'];
    
    if ($count > 0) {
        echo "Schedules table already has $count records. Skipping insertion.<br>";
    } else {
        // Check column names
        $day_column = 'day_of_week';
        $check_day = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'day'");
        if ($check_day && mysqli_num_rows($check_day) > 0) {
            $day_column = 'day';
        }
        
        $start_time_column = 'start_time';
        $end_time_column = 'end_time';
        $check_time_start = mysqli_query($conn, "SHOW COLUMNS FROM schedules LIKE 'time_start'");
        if ($check_time_start && mysqli_num_rows($check_time_start) > 0) {
            $start_time_column = 'time_start';
            $end_time_column = 'time_end';
        }
        
        echo "Using columns: $day_column, $start_time_column, $end_time_column<br>";
        
        foreach ($sample_schedules as $schedule) {
            $insert_query = "INSERT INTO schedules (teacher_id, subject, section, grade_level, $day_column, $start_time_column, $end_time_column, room) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "isssssss", 
                $teacher_id, 
                $schedule['subject'], 
                $schedule['section'], 
                $schedule['grade_level'], 
                $schedule['day'], 
                $schedule['time_start'], 
                $schedule['time_end'], 
                $schedule['room']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                echo "Added schedule for {$schedule['subject']} to schedules table<br>";
            } else {
                echo "Error adding schedule: " . mysqli_stmt_error($stmt) . "<br>";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

echo "<h2>Done!</h2>";
echo "<p>Now you can test the schedule report with sample data.</p>";
echo "<p><a href='modules/reports/schedule_report.php?debug=1'>Go to Schedule Report</a></p>";

mysqli_close($conn);
?> 
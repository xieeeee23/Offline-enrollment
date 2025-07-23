<?php
// Include database connection
require_once 'includes/config.php';

// Define helper functions
function getCurrentSchoolYear($conn) {
    $query = "SELECT school_year FROM school_years WHERE is_current = 1 LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['school_year'];
    } else {
        // Default to current year - next year format if not found
        $current_year = date('Y');
        return $current_year . '-' . ($current_year + 1);
    }
}

function getCurrentSemester($conn) {
    $month = date('n');
    
    // First semester: June to October (6-10)
    // Second semester: November to March (11-3)
    // Summer: April to May (4-5)
    
    if ($month >= 6 && $month <= 10) {
        return 'First';
    } else if ($month >= 11 || $month <= 3) {
        return 'Second';
    } else {
        // Default to First semester if it's summer
        return 'First';
    }
}

echo "<h1>SHS Schedule List Setup</h1>";

// Check if the table exists
$check_table_query = "SHOW TABLES LIKE 'SHS_Schedule_List'";
$check_table_result = mysqli_query($conn, $check_table_query);

if ($check_table_result && mysqli_num_rows($check_table_result) > 0) {
    echo "<p>SHS_Schedule_List table already exists.</p>";
    
    // Check if it has data
    $count_query = "SELECT COUNT(*) as count FROM SHS_Schedule_List";
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    
    if ($count_row['count'] > 0) {
        echo "<p>Table already has {$count_row['count']} records.</p>";
    } else {
        echo "<p>Table exists but has no data. Adding sample data...</p>";
        // We'll add sample data below
    }
} else {
    echo "<p>SHS_Schedule_List table does not exist. Creating it now...</p>";
    
    // Create the table
    $create_table_query = "CREATE TABLE SHS_Schedule_List (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        section VARCHAR(20) NOT NULL,
        subject VARCHAR(100) NOT NULL,
        teacher_id INT,
        room VARCHAR(20),
        school_year VARCHAR(20),
        semester ENUM('First', 'Second') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<p>SHS_Schedule_List table created successfully.</p>";
    } else {
        echo "<p>Error creating table: " . mysqli_error($conn) . "</p>";
        exit;
    }
}

// Check if we need to add sample data
$count_query = "SELECT COUNT(*) as count FROM SHS_Schedule_List";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);

if ($count_row['count'] == 0) {
    echo "<p>Adding sample schedule data...</p>";
    
    // Get teacher IDs
    $teacher_ids = [];
    $teacher_query = "SELECT id FROM teachers WHERE status = 'active' LIMIT 5";
    $teacher_result = mysqli_query($conn, $teacher_query);
    
    while ($row = mysqli_fetch_assoc($teacher_result)) {
        $teacher_ids[] = $row['id'];
    }
    
    // If no teachers found, create a placeholder
    if (empty($teacher_ids)) {
        echo "<p>No active teachers found. Creating a placeholder teacher...</p>";
        
        $insert_teacher_query = "INSERT INTO teachers (first_name, last_name, email, contact_number, status) 
                               VALUES ('John', 'Doe', 'john.doe@example.com', '1234567890', 'active')";
        
        if (mysqli_query($conn, $insert_teacher_query)) {
            $teacher_ids[] = mysqli_insert_id($conn);
            echo "<p>Placeholder teacher created with ID: {$teacher_ids[0]}</p>";
        } else {
            echo "<p>Error creating placeholder teacher: " . mysqli_error($conn) . "</p>";
        }
    }
    
    // Get current school year and semester
    $current_school_year = getCurrentSchoolYear($conn);
    $current_semester = getCurrentSemester($conn);
    
    // Sample data
    $sample_data = [
        // Grade 11 ABM-11A
        [
            'day_of_week' => 'Monday',
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
            'grade_level' => 'Grade 11',
            'section' => 'ABM-11A',
            'subject' => 'Business Mathematics',
            'room' => 'Room 101'
        ],
        [
            'day_of_week' => 'Monday',
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
            'grade_level' => 'Grade 11',
            'section' => 'ABM-11A',
            'subject' => 'Organization and Management',
            'room' => 'Room 101'
        ],
        [
            'day_of_week' => 'Monday',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'grade_level' => 'Grade 11',
            'section' => 'ABM-11A',
            'subject' => 'English for Academic Purposes',
            'room' => 'Room 101'
        ],
        [
            'day_of_week' => 'Tuesday',
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
            'grade_level' => 'Grade 11',
            'section' => 'ABM-11A',
            'subject' => 'Fundamentals of Accountancy',
            'room' => 'Room 101'
        ],
        
        // Grade 11 STEM-11A
        [
            'day_of_week' => 'Monday',
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
            'grade_level' => 'Grade 11',
            'section' => 'STEM-11A',
            'subject' => 'Pre-Calculus',
            'room' => 'Room 102'
        ],
        [
            'day_of_week' => 'Monday',
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
            'grade_level' => 'Grade 11',
            'section' => 'STEM-11A',
            'subject' => 'General Chemistry 1',
            'room' => 'Room 102'
        ],
        
        // Grade 12 ABM-12A
        [
            'day_of_week' => 'Wednesday',
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
            'grade_level' => 'Grade 12',
            'section' => 'ABM-12A',
            'subject' => 'Business Finance',
            'room' => 'Room 201'
        ],
        [
            'day_of_week' => 'Wednesday',
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
            'grade_level' => 'Grade 12',
            'section' => 'ABM-12A',
            'subject' => 'Business Ethics',
            'room' => 'Room 201'
        ],
        
        // Grade 12 STEM-12A
        [
            'day_of_week' => 'Thursday',
            'start_time' => '07:30:00',
            'end_time' => '08:30:00',
            'grade_level' => 'Grade 12',
            'section' => 'STEM-12A',
            'subject' => 'Calculus',
            'room' => 'Room 202'
        ],
        [
            'day_of_week' => 'Thursday',
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
            'grade_level' => 'Grade 12',
            'section' => 'STEM-12A',
            'subject' => 'Physics 2',
            'room' => 'Room 202'
        ]
    ];
    
    // Insert sample data
    $success_count = 0;
    $error_count = 0;
    
    foreach ($sample_data as $data) {
        // Assign a random teacher from the available ones
        $teacher_id = $teacher_ids[array_rand($teacher_ids)];
        
        $insert_query = "INSERT INTO SHS_Schedule_List 
                        (day_of_week, start_time, end_time, grade_level, section, subject, teacher_id, room, school_year, semester) 
                        VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssssssisss", 
            $data['day_of_week'], 
            $data['start_time'], 
            $data['end_time'], 
            $data['grade_level'], 
            $data['section'], 
            $data['subject'], 
            $teacher_id, 
            $data['room'],
            $current_school_year,
            $current_semester
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $success_count++;
        } else {
            $error_count++;
            echo "<p>Error inserting record: " . mysqli_error($conn) . "</p>";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    echo "<p>Sample data insertion complete. Success: $success_count, Errors: $error_count</p>";
} else {
    echo "<p>No need to add sample data. Table already has records.</p>";
}

// Show sample of the data
$sample_query = "SELECT s.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name 
                FROM SHS_Schedule_List s 
                LEFT JOIN teachers t ON s.teacher_id = t.id 
                ORDER BY s.day_of_week, s.start_time 
                LIMIT 10";
$sample_result = mysqli_query($conn, $sample_query);

if ($sample_result && mysqli_num_rows($sample_result) > 0) {
    echo "<h2>Sample Data (up to 10 records):</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
            <th>ID</th>
            <th>Day</th>
            <th>Time</th>
            <th>Grade & Section</th>
            <th>Subject</th>
            <th>Teacher</th>
            <th>Room</th>
          </tr>";
    
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['day_of_week']) . "</td>";
        echo "<td>" . date('h:i A', strtotime($row['start_time'])) . " - " . date('h:i A', strtotime($row['end_time'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['grade_level'] . ' ' . $row['section']) . "</td>";
        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
        echo "<td>" . htmlspecialchars($row['teacher_name'] ?? 'Not Assigned') . "</td>";
        echo "<td>" . htmlspecialchars($row['room']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No data to display or error: " . mysqli_error($conn) . "</p>";
}

echo "<p>Setup complete. <a href='modules/reports/schedule_report.php?debug=1'>View Schedule Report</a></p>";
?> 
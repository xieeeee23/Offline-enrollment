<?php
$title = 'Test Schedule Tables';
$relative_path = './';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin role
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

echo "<h1>Schedule Tables Test</h1>";

// Check if SHS_Schedule_List table exists
$check_table_query = "SHOW TABLES LIKE 'SHS_Schedule_List'";
$check_table_result = mysqli_query($conn, $check_table_query);
$use_shs_schedule_list = ($check_table_result && mysqli_num_rows($check_table_result) > 0);

echo "<h2>SHS_Schedule_List Table</h2>";
echo "<p>Table exists: " . ($use_shs_schedule_list ? 'Yes' : 'No') . "</p>";

if ($use_shs_schedule_list) {
    // Check structure
    echo "<h3>Table Structure</h3>";
    $structure_query = "DESCRIBE SHS_Schedule_List";
    $structure_result = mysqli_query($conn, $structure_query);
    
    if ($structure_result) {
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
        echo "<tbody>";
        while ($row = mysqli_fetch_assoc($structure_result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        // Check data
        $count_query = "SELECT COUNT(*) as count FROM SHS_Schedule_List";
        $count_result = mysqli_query($conn, $count_query);
        if ($count_result) {
            $count_row = mysqli_fetch_assoc($count_result);
            echo "<p>Records in table: " . $count_row['count'] . "</p>";
            
            if ($count_row['count'] > 0) {
                echo "<h3>Sample Data (10 rows)</h3>";
                $data_query = "SELECT * FROM SHS_Schedule_List LIMIT 10";
                $data_result = mysqli_query($conn, $data_query);
                
                if ($data_result) {
                    echo "<table class='table table-bordered table-striped'>";
                    echo "<thead><tr>";
                    $fields = mysqli_fetch_fields($data_result);
                    foreach ($fields as $field) {
                        echo "<th>" . htmlspecialchars($field->name) . "</th>";
                    }
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    while ($row = mysqli_fetch_assoc($data_result)) {
                        echo "<tr>";
                        foreach ($row as $key => $value) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                }
            }
        }
    } else {
        echo "<p class='text-danger'>Error getting table structure: " . mysqli_error($conn) . "</p>";
    }
} else {
    // Create table if it doesn't exist
    echo "<div class='alert alert-warning'>SHS_Schedule_List table does not exist. Would you like to create it?</div>";
    echo "<form method='post' action=''>";
    echo "<button type='submit' name='create_table' class='btn btn-primary'>Create SHS_Schedule_List Table</button>";
    echo "</form>";
    
    if (isset($_POST['create_table'])) {
        $create_table_query = "CREATE TABLE SHS_Schedule_List (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            subject VARCHAR(100) NOT NULL,
            section VARCHAR(50) NOT NULL,
            grade_level VARCHAR(20) NOT NULL,
            day_of_week VARCHAR(20) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            room VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        )";
        
        if (mysqli_query($conn, $create_table_query)) {
            echo "<div class='alert alert-success'>SHS_Schedule_List table created successfully!</div>";
            echo "<meta http-equiv='refresh' content='2;url=" . $_SERVER['PHP_SELF'] . "'>";
        } else {
            echo "<div class='alert alert-danger'>Error creating table: " . mysqli_error($conn) . "</div>";
        }
    }
}

// Check if schedules table exists
$check_schedules_query = "SHOW TABLES LIKE 'schedules'";
$check_schedules_result = mysqli_query($conn, $check_schedules_query);
$use_schedules = ($check_schedules_result && mysqli_num_rows($check_schedules_result) > 0);

echo "<h2>schedules Table</h2>";
echo "<p>Table exists: " . ($use_schedules ? 'Yes' : 'No') . "</p>";

if ($use_schedules) {
    // Check structure
    echo "<h3>Table Structure</h3>";
    $structure_query = "DESCRIBE schedules";
    $structure_result = mysqli_query($conn, $structure_query);
    
    if ($structure_result) {
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
        echo "<tbody>";
        while ($row = mysqli_fetch_assoc($structure_result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        // Check data
        $count_query = "SELECT COUNT(*) as count FROM schedules";
        $count_result = mysqli_query($conn, $count_query);
        if ($count_result) {
            $count_row = mysqli_fetch_assoc($count_result);
            echo "<p>Records in table: " . $count_row['count'] . "</p>";
            
            if ($count_row['count'] > 0) {
                echo "<h3>Sample Data (10 rows)</h3>";
                $data_query = "SELECT * FROM schedules LIMIT 10";
                $data_result = mysqli_query($conn, $data_query);
                
                if ($data_result) {
                    echo "<table class='table table-bordered table-striped'>";
                    echo "<thead><tr>";
                    $fields = mysqli_fetch_fields($data_result);
                    foreach ($fields as $field) {
                        echo "<th>" . htmlspecialchars($field->name) . "</th>";
                    }
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    while ($row = mysqli_fetch_assoc($data_result)) {
                        echo "<tr>";
                        foreach ($row as $key => $value) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                }
            }
        }
    } else {
        echo "<p class='text-danger'>Error getting table structure: " . mysqli_error($conn) . "</p>";
    }
} else {
    // Create table if it doesn't exist
    echo "<div class='alert alert-warning'>schedules table does not exist. Would you like to create it?</div>";
    echo "<form method='post' action=''>";
    echo "<button type='submit' name='create_schedules' class='btn btn-primary'>Create schedules Table</button>";
    echo "</form>";
    
    if (isset($_POST['create_schedules'])) {
        $create_table_query = "CREATE TABLE schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id INT NOT NULL,
            subject VARCHAR(100) NOT NULL,
            section VARCHAR(50) NOT NULL,
            grade_level VARCHAR(20) NOT NULL,
            day_of_week VARCHAR(20) NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            room VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
        )";
        
        if (mysqli_query($conn, $create_table_query)) {
            echo "<div class='alert alert-success'>schedules table created successfully!</div>";
            echo "<meta http-equiv='refresh' content='2;url=" . $_SERVER['PHP_SELF'] . "'>";
        } else {
            echo "<div class='alert alert-danger'>Error creating table: " . mysqli_error($conn) . "</div>";
        }
    }
}

require_once $relative_path . 'includes/footer.php';
?> 
<?php
// Include database connection
require_once 'includes/config.php';

echo "<h1>Schedules Table Structure</h1>";

// Check table structure
$query = "DESCRIBE schedules";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "<h2>Table Structure:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>Error checking table structure: " . mysqli_error($conn) . "</p>";
}

// Check sample data
$query = "SELECT * FROM schedules LIMIT 10";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "<h2>Sample Data (up to 10 rows):</h2>";
    
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5'>";
        
        // Get field names
        $fields = mysqli_fetch_fields($result);
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        echo "</tr>";
        
        // Get data rows
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No data found in the schedules table.</p>";
    }
} else {
    echo "<p>Error fetching sample data: " . mysqli_error($conn) . "</p>";
}

// Check if there's a SHS_Schedule_List table
$query = "SHOW TABLES LIKE 'SHS_Schedule_List'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h2>SHS_Schedule_List Table Exists!</h2>";
    
    // Check structure
    $query = "DESCRIBE SHS_Schedule_List";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Check sample data
        $query = "SELECT * FROM SHS_Schedule_List LIMIT 10";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            echo "<h3>Sample Data (up to 10 rows):</h3>";
            
            if (mysqli_num_rows($result) > 0) {
                echo "<table border='1' cellpadding='5'>";
                
                // Get field names
                $fields = mysqli_fetch_fields($result);
                echo "<tr>";
                foreach ($fields as $field) {
                    echo "<th>" . htmlspecialchars($field->name) . "</th>";
                }
                echo "</tr>";
                
                // Get data rows
                mysqli_data_seek($result, 0);
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No data found in the SHS_Schedule_List table.</p>";
            }
        } else {
            echo "<p>Error fetching sample data: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>Error checking table structure: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<h2>SHS_Schedule_List Table Does Not Exist</h2>";
    
    // Check all tables to find schedule-related tables
    echo "<h3>Available Tables:</h3>";
    $query = "SHOW TABLES";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        echo "<ul>";
        while ($row = mysqli_fetch_row($result)) {
            echo "<li>" . htmlspecialchars($row[0]) . "</li>";
        }
        echo "</ul>";
    }
}
?> 
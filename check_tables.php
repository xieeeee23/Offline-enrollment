<?php
require_once 'includes/config.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h2>Teachers Table Structure</h2>";
$result = mysqli_query($conn, "DESCRIBE teachers");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

echo "<h2>Schedule Tables</h2>";
$tables = ['schedule', 'SHS_Schedule_List', 'schedules'];

foreach ($tables as $table) {
    echo "<h3>$table</h3>";
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if ($result && mysqli_num_rows($result) > 0) {
        echo "Table exists<br>";
        
        // Show structure
        $result = mysqli_query($conn, "DESCRIBE $table");
        if ($result) {
            echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Count records
            $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
            if ($count_result) {
                $count = mysqli_fetch_assoc($count_result)['count'];
                echo "Total records: $count<br>";
            }
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Table does not exist<br>";
    }
}

mysqli_close($conn);
?> 
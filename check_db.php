<?php
// Include database configuration
require_once 'includes/config.php';

echo "<h2>Database Structure Check</h2>";

// Check if students table exists
$query = "SHOW TABLES LIKE 'students'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "<p>Students table exists.</p>";
    
    // Check columns in students table
    $query = "DESCRIBE students";
    $result = mysqli_query($conn, $query);
    
    echo "<h3>Columns in students table:</h3>";
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
    
    // Check sample data
    $query = "SELECT * FROM students LIMIT 5";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<h3>Sample data from students table:</h3>";
        echo "<table border='1'><tr>";
        
        // Get column names
        $fields = mysqli_fetch_fields($result);
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // Output data
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No data in students table or error fetching data.</p>";
    }
} else {
    echo "<p>Students table does not exist!</p>";
}

// Check if the database has been imported correctly
echo "<h3>All tables in database:</h3>";
$query = "SHOW TABLES";
$result = mysqli_query($conn, $query);

echo "<ul>";
while ($row = mysqli_fetch_row($result)) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";
?> 
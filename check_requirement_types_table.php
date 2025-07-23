<?php
// Database connection details
$db_host = 'localhost';
$db_user = 'root'; // Default XAMPP MySQL user
$db_pass = ''; // Default XAMPP MySQL password is empty
$db_name = 'shs_enrollment'; // Your database name

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected to database successfully.<br>";

// Check if table exists
$table_check_query = "SHOW TABLES LIKE 'requirement_types'";
$table_check_result = mysqli_query($conn, $table_check_query);

if (mysqli_num_rows($table_check_result) > 0) {
    echo "The requirement_types table exists.<br>";
    
    // Check table contents
    $content_query = "SELECT * FROM requirement_types";
    $content_result = mysqli_query($conn, $content_query);
    
    if (mysqli_num_rows($content_result) > 0) {
        echo "The table contains " . mysqli_num_rows($content_result) . " rows:<br>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Is Required</th><th>Is Active</th><th>Created At</th><th>Updated At</th></tr>";
        
        while ($row = mysqli_fetch_assoc($content_result)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['description'] . "</td>";
            echo "<td>" . $row['is_required'] . "</td>";
            echo "<td>" . $row['is_active'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "<td>" . $row['updated_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "The table is empty.";
    }
} else {
    echo "The requirement_types table does not exist.";
}

// Close connection
mysqli_close($conn);
?> 
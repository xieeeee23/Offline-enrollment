<?php
// Include necessary files
require_once 'includes/config.php';

// Check if the requirements table exists
$query = "SHOW TABLES LIKE 'requirements'";
$result = mysqli_query($conn, $query);

echo "<h2>Requirements Table Test</h2>";

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ Requirements table exists.</p>";
    
    // Check if there are any records in the table
    $query = "SELECT COUNT(*) as count FROM requirements";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        echo "<p style='color: green;'>✓ Requirements table has {$row['count']} records.</p>";
        
        // Display the first 10 records
        $query = "SELECT * FROM requirements LIMIT 10";
        $result = mysqli_query($conn, $query);
        
        echo "<h3>Sample Requirements:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Program</th><th>Required</th><th>Active</th></tr>";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['type']}</td>";
            echo "<td>{$row['program']}</td>";
            echo "<td>" . ($row['is_required'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ Requirements table exists but has no records.</p>";
        echo "<p>Please run <a href='create_requirements_table.php'>create_requirements_table.php</a> to add default requirements.</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Requirements table does not exist.</p>";
    echo "<p>Please run <a href='create_requirements_table.php'>create_requirements_table.php</a> to create the table and add default requirements.</p>";
}

// Check if the AJAX endpoint is working
echo "<h2>AJAX Endpoint Test</h2>";
echo "<p>Testing GET endpoint: <code>modules/registrar/process_requirement.php?action=get&id=1</code></p>";

// Close connection
mysqli_close($conn);
?> 
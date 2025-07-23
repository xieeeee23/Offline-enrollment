<?php
// Database connection
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get database connection
$conn = getConnection();

// Check if the 'room' column exists in the sections table
$check_room_column = $conn->query("SHOW COLUMNS FROM sections LIKE 'room'");

if ($check_room_column->num_rows == 0) {
    // The 'room' column doesn't exist, so add it
    $alter_table_sql = "ALTER TABLE sections ADD COLUMN room VARCHAR(50) AFTER max_students";
    
    if ($conn->query($alter_table_sql)) {
        echo "Success: 'room' column added to the sections table.<br>";
    } else {
        echo "Error adding 'room' column: " . $conn->error . "<br>";
    }
} else {
    echo "The 'room' column already exists in the sections table.<br>";
}

// Check if the 'capacity' column exists (in case it's named differently)
$check_capacity_column = $conn->query("SHOW COLUMNS FROM sections LIKE 'capacity'");
$check_max_students_column = $conn->query("SHOW COLUMNS FROM sections LIKE 'max_students'");

if ($check_capacity_column->num_rows > 0 && $check_max_students_column->num_rows == 0) {
    // Rename 'capacity' to 'max_students' for consistency
    $alter_table_sql = "ALTER TABLE sections CHANGE capacity max_students INT(11) DEFAULT 40";
    
    if ($conn->query($alter_table_sql)) {
        echo "Success: 'capacity' column renamed to 'max_students'.<br>";
    } else {
        echo "Error renaming column: " . $conn->error . "<br>";
    }
}

// Display the current structure of the sections table
$table_structure = $conn->query("DESCRIBE sections");
echo "<h2>Current Sections Table Structure</h2>";
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $table_structure->fetch_assoc()) {
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

// Display sample data
$sample_data = $conn->query("SELECT * FROM sections LIMIT 5");
echo "<h2>Sample Data</h2>";
if ($sample_data->num_rows > 0) {
    echo "<table border='1'><tr>";
    
    // Get column names
    $fields = $sample_data->fetch_fields();
    foreach ($fields as $field) {
        echo "<th>" . $field->name . "</th>";
    }
    echo "</tr>";
    
    // Reset result pointer
    $sample_data->data_seek(0);
    
    // Output data rows
    while ($row = $sample_data->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data found in the sections table.";
}

echo "<p>Table fix process completed.</p>";
?> 
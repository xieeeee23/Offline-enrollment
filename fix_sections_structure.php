<?php
// Database connection
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get database connection
$conn = getConnection();

// Check if the sections table exists
$check_table = $conn->query("SHOW TABLES LIKE 'sections'");
if ($check_table->num_rows == 0) {
    echo "Error: The sections table does not exist.<br>";
    exit;
}

echo "<h2>Fixing Sections Table Structure</h2>";

// Define the required columns and their properties
$required_columns = [
    'id' => ['type' => 'INT(11)', 'null' => 'NO', 'key' => 'PRI', 'default' => '', 'extra' => 'auto_increment'],
    'name' => ['type' => 'VARCHAR(50)', 'null' => 'NO', 'key' => '', 'default' => '', 'extra' => ''],
    'grade_level' => ['type' => "ENUM('Grade 11','Grade 12')", 'null' => 'NO', 'key' => '', 'default' => '', 'extra' => ''],
    'strand' => ['type' => 'VARCHAR(20)', 'null' => 'NO', 'key' => 'MUL', 'default' => '', 'extra' => ''],
    'max_students' => ['type' => 'INT(11)', 'null' => 'YES', 'key' => '', 'default' => '40', 'extra' => ''],
    'room' => ['type' => 'VARCHAR(50)', 'null' => 'YES', 'key' => '', 'default' => NULL, 'extra' => ''],
    'status' => ['type' => "ENUM('Active','Inactive')", 'null' => 'YES', 'key' => '', 'default' => 'Active', 'extra' => ''],
    'school_year' => ['type' => 'VARCHAR(20)', 'null' => 'NO', 'key' => '', 'default' => '', 'extra' => ''],
    'semester' => ['type' => "ENUM('First','Second')", 'null' => 'NO', 'key' => '', 'default' => '', 'extra' => ''],
    'created_at' => ['type' => 'TIMESTAMP', 'null' => 'NO', 'key' => '', 'default' => 'current_timestamp()', 'extra' => ''],
    'updated_at' => ['type' => 'TIMESTAMP', 'null' => 'NO', 'key' => '', 'default' => 'current_timestamp()', 'extra' => 'on update current_timestamp()']
];

// Get the current structure of the sections table
$current_structure = [];
$structure_query = $conn->query("DESCRIBE sections");
while ($row = $structure_query->fetch_assoc()) {
    $current_structure[$row['Field']] = [
        'type' => $row['Type'],
        'null' => $row['Null'],
        'key' => $row['Key'],
        'default' => $row['Default'],
        'extra' => $row['Extra']
    ];
}

// Check for missing columns and add them
foreach ($required_columns as $column_name => $properties) {
    if (!isset($current_structure[$column_name])) {
        // Column doesn't exist, add it
        $after_column = array_key_exists(array_key_last($current_structure), $required_columns) ? 
            "AFTER " . array_key_last($current_structure) : "FIRST";
        
        $default_value = $properties['default'] === NULL ? "NULL" : 
            ($properties['default'] === '' ? "" : "DEFAULT '" . $properties['default'] . "'");
        
        $sql = "ALTER TABLE sections ADD COLUMN $column_name {$properties['type']} " . 
               ($properties['null'] === 'NO' ? "NOT NULL " : "NULL ") . 
               $default_value . " " . $properties['extra'] . " " . $after_column;
        
        if ($conn->query($sql)) {
            echo "Added missing column '$column_name' to sections table.<br>";
        } else {
            echo "Error adding column '$column_name': " . $conn->error . "<br>";
        }
    }
}

// Check for renamed columns (capacity -> max_students)
if (isset($current_structure['capacity']) && !isset($current_structure['max_students'])) {
    $sql = "ALTER TABLE sections CHANGE capacity max_students INT(11) DEFAULT 40";
    if ($conn->query($sql)) {
        echo "Renamed column 'capacity' to 'max_students'.<br>";
        // Update the current structure
        $current_structure['max_students'] = $current_structure['capacity'];
        unset($current_structure['capacity']);
    } else {
        echo "Error renaming column 'capacity': " . $conn->error . "<br>";
    }
}

// Check for column type mismatches and fix them
foreach ($required_columns as $column_name => $properties) {
    if (isset($current_structure[$column_name])) {
        $current_type = strtolower($current_structure[$column_name]['type']);
        $required_type = strtolower($properties['type']);
        
        // Compare types (ignoring case and some formatting differences)
        $type_mismatch = false;
        
        // For ENUM types, just check if both are ENUMs
        if (strpos($current_type, 'enum') === 0 && strpos($required_type, 'enum') === 0) {
            // Both are ENUMs, consider them compatible
        } else if ($current_type !== $required_type) {
            $type_mismatch = true;
        }
        
        if ($type_mismatch) {
            $default_value = $properties['default'] === NULL ? "NULL" : 
                ($properties['default'] === '' ? "" : "DEFAULT '" . $properties['default'] . "'");
            
            $sql = "ALTER TABLE sections MODIFY COLUMN $column_name {$properties['type']} " . 
                   ($properties['null'] === 'NO' ? "NOT NULL " : "NULL ") . 
                   $default_value . " " . $properties['extra'];
            
            if ($conn->query($sql)) {
                echo "Modified column '$column_name' to correct type.<br>";
            } else {
                echo "Error modifying column '$column_name': " . $conn->error . "<br>";
            }
        }
    }
}

// Display the updated structure of the sections table
$table_structure = $conn->query("DESCRIBE sections");
echo "<h2>Updated Sections Table Structure</h2>";
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

echo "<p>Table structure fix process completed.</p>";
?> 
<?php
// Include database connection
require_once 'includes/config.php';

// Check sections table structure
echo "<h2>Sections Table Structure</h2>";
echo "<pre>";

$result = mysqli_query($conn, "DESCRIBE sections");
if ($result) {
    echo "Columns in sections table:\n\n";
    echo str_pad("Field", 20) . str_pad("Type", 25) . str_pad("Null", 6) . str_pad("Key", 6) . str_pad("Default", 10) . "Extra\n";
    echo str_repeat("-", 80) . "\n";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo str_pad($row['Field'], 20) . 
             str_pad($row['Type'], 25) . 
             str_pad($row['Null'], 6) . 
             str_pad($row['Key'], 6) . 
             str_pad($row['Default'] ?? 'NULL', 10) . 
             $row['Extra'] . "\n";
    }
} else {
    echo "Error checking sections table: " . mysqli_error($conn);
}

echo "</pre>";
?> 
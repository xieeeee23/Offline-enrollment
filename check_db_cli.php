<?php
// Include database configuration
require_once 'includes/config.php';

echo "Database Structure Check\n";
echo "=======================\n\n";

// Check connection
if (!$conn) {
    echo "Connection failed: " . mysqli_connect_error() . "\n";
    exit;
}

echo "Connected to database: " . DB_NAME . "\n\n";

// Check if students table exists
$query = "SHOW TABLES LIKE 'students'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo "Students table exists.\n\n";
    
    // Check columns in students table
    $query = "DESCRIBE students";
    $result = mysqli_query($conn, $query);
    
    echo "Columns in students table:\n";
    echo "-------------------------\n";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Field: " . str_pad($row['Field'], 20) . " | ";
        echo "Type: " . str_pad($row['Type'], 30) . " | ";
        echo "Default: " . $row['Default'] . "\n";
    }
    
    echo "\n";
    
    // Check sample data
    $query = "SELECT id, first_name, last_name, grade_level, section, status FROM students LIMIT 3";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "Sample data from students table:\n";
        echo "-----------------------------\n";
        
        while ($row = mysqli_fetch_assoc($result)) {
            echo "ID: " . str_pad($row['id'], 5) . " | ";
            echo "Name: " . str_pad($row['first_name'] . " " . $row['last_name'], 20) . " | ";
            echo "Grade: " . str_pad($row['grade_level'], 5) . " | ";
            echo "Section: " . str_pad($row['section'], 10) . " | ";
            echo "Status: " . (isset($row['status']) ? $row['status'] : "NULL") . "\n";
        }
    } else {
        echo "No data in students table or error fetching data.\n";
        if (!$result) {
            echo "Error: " . mysqli_error($conn) . "\n";
        }
    }
} else {
    echo "Students table does not exist!\n";
}

// Check if the database has been imported correctly
echo "\nAll tables in database:\n";
echo "---------------------\n";
$query = "SHOW TABLES";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_row($result)) {
    echo "- " . $row[0] . "\n";
}
?> 
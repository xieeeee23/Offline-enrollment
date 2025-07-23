<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "localenroll_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful\n\n";

// Array of tables to check
$tables = [
    'students',
    'senior_highschool_details',
    'shs_strands',
    'sections'
];

// Check if each table exists
foreach ($tables as $table) {
    $query = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        echo "$table: EXISTS\n";
        
        // Show table structure
        $structure_query = "DESCRIBE $table";
        $structure_result = $conn->query($structure_query);
        
        echo "Structure of $table table:\n";
        echo "-------------------------\n";
        
        while ($row = $structure_result->fetch_assoc()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL');
            if ($row['Key'] == 'PRI') {
                echo " | PRIMARY KEY";
            }
            echo "\n";
        }
        
        // Count records in the table
        $count_query = "SELECT COUNT(*) AS count FROM $table";
        $count_result = $conn->query($count_query);
        $count_row = $count_result->fetch_assoc();
        
        echo "Records in $table: " . $count_row['count'] . "\n\n";
    } else {
        echo "$table: DOES NOT EXIST\n\n";
    }
}

echo "Verification completed!";
?> 
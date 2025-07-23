<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'shs_enrollment';

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected successfully. Checking subjects table structure...\n";

// Check if subjects table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'subjects'");
if (mysqli_num_rows($table_check) === 0) {
    echo "The subjects table does not exist.\n";
    exit;
}

// Check the structure of the subjects table
$result = mysqli_query($conn, 'DESCRIBE subjects');
echo "\nSubjects Table Structure:\n";
echo "------------------------\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . " - Default: " . ($row['Default'] ? $row['Default'] : 'NULL') . "\n";
}

// Check if education_level column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM subjects LIKE 'education_level'");
if (mysqli_num_rows($result) > 0) {
    echo "\nThe 'education_level' column exists in the subjects table.\n";
} else {
    echo "\nThe 'education_level' column does NOT exist in the subjects table.\n";
    
    // Add the column if it doesn't exist
    echo "Adding 'education_level' column...\n";
    $alter_query = "ALTER TABLE subjects ADD COLUMN education_level VARCHAR(50) NOT NULL DEFAULT 'Senior High School' AFTER description";
    if (mysqli_query($conn, $alter_query)) {
        echo "Added 'education_level' column successfully.\n";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "\n";
    }
}

// Get all subjects with their details
$result = mysqli_query($conn, "SELECT * FROM subjects ORDER BY name");
echo "\nSubjects in the Database:\n";
echo "----------------------\n";
echo sprintf("%-5s %-30s %-15s %-20s %-15s\n", "ID", "Name", "Code", "Education Level", "Grade Level");
echo str_repeat("-", 90) . "\n";

while ($row = mysqli_fetch_assoc($result)) {
    echo sprintf("%-5s %-30s %-15s %-20s %-15s\n", 
        $row['id'], 
        substr($row['name'], 0, 28), 
        $row['code'], 
        $row['education_level'] ?? 'N/A',
        $row['grade_level']
    );
}

// Close connection
mysqli_close($conn);
?> 
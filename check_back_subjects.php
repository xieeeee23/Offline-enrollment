<?php
require_once 'includes/config.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Back Subjects Table Structure</h1>";

// Check if table exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'back_subjects'");
if (mysqli_num_rows($table_exists) == 0) {
    echo "<p style='color: red;'>The 'back_subjects' table does not exist in the database!</p>";
    
    // Check if there's a create table script
    echo "<h2>Looking for Create Table Script</h2>";
    $files_to_check = [
        'create_back_subjects_table.php',
        'create_back_subjects_table.sql',
        'database/create_back_subjects_table.php',
        'database/create_back_subjects_table.sql'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            echo "<p>Found file: $file</p>";
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                echo "<h3>Contents of $file:</h3>";
                echo "<pre>" . htmlspecialchars(file_get_contents($file)) . "</pre>";
            }
        }
    }
    
    // Check if there's a setup script
    if (file_exists('setup_back_subjects.php')) {
        echo "<p>Found setup file: setup_back_subjects.php</p>";
    }
    
    // Check if there's a README
    if (file_exists('README_BACK_SUBJECTS.md')) {
        echo "<h3>Contents of README_BACK_SUBJECTS.md:</h3>";
        echo "<pre>" . htmlspecialchars(file_get_contents('README_BACK_SUBJECTS.md')) . "</pre>";
    }
    
    exit;
}

$result = mysqli_query($conn, "DESCRIBE back_subjects");
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

echo "<h2>Sample Data</h2>";
$result = mysqli_query($conn, "SELECT * FROM back_subjects LIMIT 5");
if ($result) {
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1'><tr>";
        $first_row = mysqli_fetch_assoc($result);
        foreach ($first_row as $key => $value) {
            echo "<th>" . $key . "</th>";
        }
        echo "</tr>";
        
        // Display the first row
        echo "<tr>";
        foreach ($first_row as $value) {
            echo "<td>" . $value . "</td>";
        }
        echo "</tr>";
        
        // Display remaining rows
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . $value . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No data found in back_subjects table.";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}

// Check the create table statement
echo "<h2>Create Table Statement</h2>";
$result = mysqli_query($conn, "SHOW CREATE TABLE back_subjects");
if ($result) {
    $row = mysqli_fetch_array($result);
    echo "<pre>" . htmlspecialchars($row[1]) . "</pre>";
} else {
    echo "Error getting create table statement: " . mysqli_error($conn);
}

mysqli_close($conn);
?> 
<?php
include 'includes/config.php';

// Check if the sections table exists
$tables_result = mysqli_query($conn, "SHOW TABLES LIKE 'sections'");
if (mysqli_num_rows($tables_result) == 0) {
    echo "The 'sections' table does not exist in the database.\n";
    exit;
}

echo "CHECKING SECTIONS TABLE STRUCTURE:\n";
$result = mysqli_query($conn, 'SHOW CREATE TABLE sections');
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_row($result);
    echo $row[1] . "\n\n";
} else {
    echo "Error getting table structure: " . mysqli_error($conn) . "\n";
}

echo "COLUMNS IN SECTIONS TABLE:\n";
$result = mysqli_query($conn, 'DESCRIBE sections');
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error getting columns: " . mysqli_error($conn) . "\n";
}

// Check if education_level column exists
$column_result = mysqli_query($conn, "SHOW COLUMNS FROM sections LIKE 'education_level'");
if (mysqli_num_rows($column_result) > 0) {
    echo "\nThe 'education_level' column exists in the sections table.\n";
} else {
    echo "\nThe 'education_level' column does NOT exist in the sections table.\n";
}
?> 
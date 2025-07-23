<?php
// Include database connection
require_once 'includes/config.php';

// Update the enrollment_status column to include all required statuses
$query = "ALTER TABLE students MODIFY COLUMN enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated', '') DEFAULT 'pending'";
$result = mysqli_query($conn, $query);

if ($result) {
    echo "<p>Successfully updated enrollment_status column definition.</p>";
} else {
    echo "<p>Error updating column definition: " . mysqli_error($conn) . "</p>";
}

// Check the updated column definition
$query = "SHOW COLUMNS FROM students LIKE 'enrollment_status'";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $column = mysqli_fetch_assoc($result);
    echo "<h2>Updated Column Definition</h2>";
    echo "<pre>";
    print_r($column);
    echo "</pre>";
} else {
    echo "<p>Could not retrieve column definition: " . mysqli_error($conn) . "</p>";
}
?> 
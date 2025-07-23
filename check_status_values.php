<?php
// Include database connection
require_once 'includes/config.php';

// Query to get distinct enrollment status values
$query = "SELECT DISTINCT enrollment_status FROM students";
$result = mysqli_query($conn, $query);

echo "<h2>Distinct Enrollment Status Values</h2>";
echo "<ul>";
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>" . htmlspecialchars($row['enrollment_status'] ?? 'NULL') . "</li>";
    }
} else {
    echo "<li>No values found or error in query: " . mysqli_error($conn) . "</li>";
}
echo "</ul>";

// Check column definition
$query = "SHOW COLUMNS FROM students LIKE 'enrollment_status'";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $column = mysqli_fetch_assoc($result);
    echo "<h2>Column Definition</h2>";
    echo "<pre>";
    print_r($column);
    echo "</pre>";
} else {
    echo "<p>Could not retrieve column definition: " . mysqli_error($conn) . "</p>";
}
?> 
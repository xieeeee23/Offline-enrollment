<?php
// Include database connection
require_once 'includes/config.php';

echo "<h2>Updating Student Statuses</h2>";

// First, make sure we have the correct column definition
$query = "ALTER TABLE students MODIFY COLUMN enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated', '') DEFAULT 'pending'";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "<p>Error updating column definition: " . mysqli_error($conn) . "</p>";
} else {
    echo "<p>Successfully updated enrollment_status column definition.</p>";
}

// Update some students to have irregular status
$query = "UPDATE students SET enrollment_status = 'irregular' WHERE id % 5 = 1 LIMIT 5";
$result = mysqli_query($conn, $query);

if ($result) {
    $affected = mysqli_affected_rows($conn);
    echo "<p>Updated $affected students to 'irregular' status.</p>";
} else {
    echo "<p>Error updating to irregular status: " . mysqli_error($conn) . "</p>";
}

// Update some students to have graduated status
$query = "UPDATE students SET enrollment_status = 'graduated' WHERE id % 7 = 2 LIMIT 5";
$result = mysqli_query($conn, $query);

if ($result) {
    $affected = mysqli_affected_rows($conn);
    echo "<p>Updated $affected students to 'graduated' status.</p>";
} else {
    echo "<p>Error updating to graduated status: " . mysqli_error($conn) . "</p>";
}

// Check the distribution of statuses
$query = "SELECT enrollment_status, COUNT(*) as count FROM students GROUP BY enrollment_status";
$result = mysqli_query($conn, $query);

echo "<h2>Status Distribution After Updates</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Status</th><th>Count</th></tr>";

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $status = $row['enrollment_status'] ?? 'NULL';
        echo "<tr><td>" . htmlspecialchars($status) . "</td><td>" . $row['count'] . "</td></tr>";
    }
} else {
    echo "<tr><td colspan='2'>No data found or error in query: " . mysqli_error($conn) . "</td></tr>";
}

echo "</table>";

// Disable debug mode in students.php
echo "<h2>Next Steps</h2>";
echo "<p>Remember to disable debug mode in modules/registrar/students.php by setting \$debug_mode = false;</p>";
?> 
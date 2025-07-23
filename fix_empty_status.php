<?php
// Include database connection
require_once 'includes/config.php';

echo "<h2>Fixing Empty Enrollment Status Values</h2>";

// Update empty enrollment status values to 'enrolled'
$query = "UPDATE students SET enrollment_status = 'enrolled' WHERE enrollment_status = '' OR enrollment_status IS NULL";
$result = mysqli_query($conn, $query);

if ($result) {
    $affected = mysqli_affected_rows($conn);
    echo "<p>Updated $affected students with empty status to 'enrolled' status.</p>";
} else {
    echo "<p>Error updating empty status: " . mysqli_error($conn) . "</p>";
}

// Check the distribution of statuses after the update
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
?> 
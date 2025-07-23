<?php
// Include configuration
require_once 'includes/config.php';

// Check the structure of the sections table
$result = mysqli_query($conn, 'DESCRIBE sections');

echo "<h2>Sections Table Structure</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

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

// Check if settings table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
if (mysqli_num_rows($result) > 0) {
    echo "<h2>Settings Table Exists</h2>";
} else {
    echo "<h2>Settings Table Does Not Exist</h2>";
}
?> 
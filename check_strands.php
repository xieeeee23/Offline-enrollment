<?php
require_once 'includes/config.php';

// Get all strands from shs_strands table
$query = "SELECT strand_code, strand_name FROM shs_strands";
$result = mysqli_query($conn, $query);
$valid_strands = [];
while ($row = mysqli_fetch_assoc($result)) {
    $valid_strands[$row['strand_code']] = $row['strand_name'];
}

echo "<h3>Valid Strands in shs_strands table:</h3>";
echo "<ul>";
foreach ($valid_strands as $code => $name) {
    echo "<li>" . htmlspecialchars($code) . " - " . htmlspecialchars($name) . "</li>";
}
echo "</ul>";

// Get all strand values from students table
$query = "SELECT DISTINCT strand FROM students WHERE strand IS NOT NULL AND strand != ''";
$result = mysqli_query($conn, $query);
$student_strands = [];
while ($row = mysqli_fetch_assoc($result)) {
    $student_strands[] = $row['strand'];
}

echo "<h3>Strands used in students table:</h3>";
echo "<ul>";
foreach ($student_strands as $strand) {
    $valid = isset($valid_strands[$strand]) ? "Valid" : "INVALID";
    echo "<li>" . htmlspecialchars($strand) . " - " . $valid . "</li>";
}
echo "</ul>";

// Get all students with strands
$query = "SELECT id, first_name, last_name, strand FROM students WHERE strand IS NOT NULL AND strand != ''";
$result = mysqli_query($conn, $query);

echo "<h3>Students with strands:</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Strand</th><th>Valid?</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $valid = isset($valid_strands[$row['strand']]) ? "Yes" : "No";
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['strand']) . "</td>";
    echo "<td>" . $valid . "</td>";
    echo "</tr>";
}
echo "</table>";
?> 
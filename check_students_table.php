<?php
require_once 'includes/config.php';

// Check if the has_voucher and voucher_number columns exist in the students table
$check_voucher_columns = "SHOW COLUMNS FROM students LIKE 'has_voucher'";
$voucher_result = mysqli_query($conn, $check_voucher_columns);

$check_voucher_number = "SHOW COLUMNS FROM students LIKE 'voucher_number'";
$voucher_number_result = mysqli_query($conn, $check_voucher_number);

if (mysqli_num_rows($voucher_result) == 0) {
    // Add the has_voucher column if it doesn't exist
    $alter_query = "ALTER TABLE students ADD COLUMN has_voucher TINYINT(1) DEFAULT 0";
    if (mysqli_query($conn, $alter_query)) {
        echo "Added has_voucher column to students table<br>";
    } else {
        echo "Error adding has_voucher column: " . mysqli_error($conn) . "<br>";
    }
}

if (mysqli_num_rows($voucher_number_result) == 0) {
    // Add the voucher_number column if it doesn't exist
    $alter_query = "ALTER TABLE students ADD COLUMN voucher_number VARCHAR(50) DEFAULT NULL";
    if (mysqli_query($conn, $alter_query)) {
        echo "Added voucher_number column to students table<br>";
    } else {
        echo "Error adding voucher_number column: " . mysqli_error($conn) . "<br>";
    }
}

// Display the table structure
$result = mysqli_query($conn, "DESCRIBE students");
echo "<h3>Students Table Structure</h3>";
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
?> 
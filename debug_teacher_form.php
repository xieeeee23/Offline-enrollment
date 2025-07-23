<?php
include 'includes/config.php';

echo "CHECKING USERS TABLE STRUCTURE:\n";
$result = mysqli_query($conn, 'SHOW CREATE TABLE users');
$row = mysqli_fetch_row($result);
echo $row[1] . "\n\n";

echo "CHECKING TEACHERS TABLE STRUCTURE:\n";
$result = mysqli_query($conn, 'SHOW CREATE TABLE teachers');
$row = mysqli_fetch_row($result);
echo $row[1] . "\n\n";

echo "CHECKING USERS WITH ROLE 'teacher':\n";
$result = mysqli_query($conn, "SELECT * FROM users WHERE role = 'teacher'");
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . ", Username: " . $row['username'] . ", Name: " . $row['name'] . "\n";
    }
} else {
    echo "No users with role 'teacher' found. This could be the issue!\n";
    echo "The 'role' column in the users table might not include 'teacher' as an option.\n";
}

echo "\nCHECKING ROLE ENUM VALUES IN USERS TABLE:\n";
$result = mysqli_query($conn, "SHOW COLUMNS FROM users WHERE Field = 'role'");
$row = mysqli_fetch_assoc($result);
echo "Role column type: " . $row['Type'] . "\n";

// Check if we need to alter the users table to add 'teacher' role
if (strpos($row['Type'], 'teacher') === false) {
    echo "\nThe 'teacher' role is missing from the users table. This is likely causing the foreign key constraint error.\n";
    echo "To fix this, you need to modify the 'role' column in the users table to include 'teacher' as an option.\n";
    echo "Run the following SQL query:\n";
    echo "ALTER TABLE users MODIFY COLUMN role ENUM('admin','registrar','teacher') NOT NULL;\n";
}

// Check if there are any teacher records with invalid user_id values
echo "\nCHECKING FOR TEACHERS WITH INVALID USER_ID VALUES:\n";
$result = mysqli_query($conn, "SELECT t.id, t.user_id, t.first_name, t.last_name FROM teachers t LEFT JOIN users u ON t.user_id = u.id WHERE t.user_id IS NOT NULL AND u.id IS NULL");
if (mysqli_num_rows($result) > 0) {
    echo "Found teachers with invalid user_id values:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Teacher ID: " . $row['id'] . ", User ID: " . $row['user_id'] . ", Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
    }
} else {
    echo "No teachers with invalid user_id values found.\n";
}
?> 
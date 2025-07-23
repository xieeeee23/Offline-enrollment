<?php
include 'includes/config.php';

echo "Checking if teacher user with ID 3 exists...\n";

$query = "SELECT id, username, role FROM users WHERE id = 3";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "User found:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Role: " . $row['role'] . "\n";
} else {
    echo "No user found with ID 3\n";
}

echo "\nChecking all users:\n";
$query = "SELECT id, username, role FROM users";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . ", Username: " . $row['username'] . ", Role: " . $row['role'] . "\n";
    }
} else {
    echo "No users found\n";
}
?> 
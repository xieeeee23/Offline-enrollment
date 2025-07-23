<?php
include 'includes/config.php';

echo "CHECKING TEACHERS TABLE STRUCTURE:\n";
$result = mysqli_query($conn, 'SHOW CREATE TABLE teachers');
$row = mysqli_fetch_row($result);
echo $row[1] . "\n\n";

echo "EXISTING TEACHER RECORDS:\n";
$query = "SELECT t.*, u.username, u.role 
          FROM teachers t 
          LEFT JOIN users u ON t.user_id = u.id";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . 
             ", User ID: " . ($row['user_id'] ?? 'NULL') . 
             ", Username: " . ($row['username'] ?? 'NULL') . 
             ", Role: " . ($row['role'] ?? 'NULL') . 
             ", Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
    }
} else {
    echo "No teacher records found\n";
}

echo "\nTRYING TO ADD A TEACHER WITH USER_ID = 3:\n";
$user_id = 3;
$first_name = "Test";
$last_name = "Teacher";
$email = "test.teacher@example.com";
$department = "Senior High School";
$subject = "Test Subject";
$grade_level = "11";
$contact_number = "1234567890";
$qualification = "Test Qualification";
$status = "active";

// Check if contact_number column exists
$has_contact_column = true;
$check_contact_column = "SHOW COLUMNS FROM teachers LIKE 'contact_number'";
$contact_result = mysqli_query($conn, $check_contact_column);
if (mysqli_num_rows($contact_result) == 0) {
    $has_contact_column = false;
    echo "Contact number column doesn't exist, adding it...\n";
    $alter_query = "ALTER TABLE teachers ADD COLUMN contact_number VARCHAR(20) DEFAULT NULL";
    mysqli_query($conn, $alter_query);
}

// First check if this user_id is already linked to a teacher
$check_query = "SELECT id FROM teachers WHERE user_id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "i", $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    echo "User ID $user_id is already linked to a teacher record.\n";
} else {
    // Insert the teacher record
    if ($has_contact_column) {
        $query = "INSERT INTO teachers (user_id, first_name, last_name, email, department, subject, grade_level, contact_number, qualification, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isssssssss", $user_id, $first_name, $last_name, $email, $department, $subject, $grade_level, $contact_number, $qualification, $status);
    } else {
        $query = "INSERT INTO teachers (user_id, first_name, last_name, email, department, subject, grade_level, qualification, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issssssss", $user_id, $first_name, $last_name, $email, $department, $subject, $grade_level, $qualification, $status);
    }
    
    echo "Executing query: " . $query . "\n";
    
    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        $teacher_id = mysqli_insert_id($conn);
        echo "Teacher added successfully with ID: $teacher_id\n";
    } else {
        echo "Error adding teacher: " . mysqli_error($conn) . "\n";
    }
}
?> 
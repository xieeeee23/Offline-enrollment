<?php
include 'includes/config.php';

echo "DEBUGGING TEACHER INSERTION\n";
echo "==========================\n\n";

// Get available teacher users
echo "Available teacher users:\n";
$query = "SELECT u.id, u.name, u.username, u.email 
          FROM users u 
          LEFT JOIN teachers t ON u.id = t.user_id 
          WHERE u.role = 'teacher' AND t.id IS NULL
          ORDER BY u.name";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . ", Username: " . $row['username'] . ", Name: " . $row['name'] . "\n";
    }
} else {
    echo "No available teacher users found. All teacher users are already linked to teacher records.\n";
}

// Try to add a teacher with the available user_id
echo "\nTrying to add a teacher record...\n";

// Use the teacher user we just created (ID 3)
$user_id = 3;
$first_name = "John";
$last_name = "Doe";
$email = "john.doe@example.com";
$department = "Senior High School";
$subject = "Mathematics";
$grade_level = "Grade 11";
$contact_number = "1234567890";
$qualification = "Bachelor of Science in Mathematics";
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
echo "With user_id: " . $user_id . "\n";

// Execute the statement
if (mysqli_stmt_execute($stmt)) {
    $teacher_id = mysqli_insert_id($conn);
    echo "Teacher added successfully with ID: $teacher_id\n";
} else {
    echo "Error adding teacher: " . mysqli_error($conn) . "\n";
}

// List all teachers
echo "\nAll teachers in the database:\n";
$result = mysqli_query($conn, "SELECT t.id, t.user_id, t.first_name, t.last_name, t.department, t.subject, u.username 
                               FROM teachers t 
                               LEFT JOIN users u ON t.user_id = u.id");
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . ", User ID: " . $row['user_id'] . ", Name: " . $row['first_name'] . " " . $row['last_name'] . 
             ", Username: " . $row['username'] . "\n";
    }
} else {
    echo "No teachers found.\n";
}
?> 
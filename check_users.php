<?php
// Include database connection
require_once 'includes/config.php';

echo "<h1>User Database Check</h1>";

// Check if the users table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($table_check) == 0) {
    echo "<p style='color: red;'>Error: Users table does not exist!</p>";
    exit;
}

// Get table structure
echo "<h2>Users Table Structure</h2>";
$structure_query = "DESCRIBE users";
$structure_result = mysqli_query($conn, $structure_query);

if (!$structure_result) {
    echo "<p style='color: red;'>Error getting table structure: " . mysqli_error($conn) . "</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($structure_result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Count users
$count_query = "SELECT COUNT(*) as total FROM users";
$count_result = mysqli_query($conn, $count_query);
$count = mysqli_fetch_assoc($count_result)['total'];

echo "<h2>User Count: $count</h2>";

// Get all users
echo "<h2>All Users in Database</h2>";
$users_query = "SELECT * FROM users ORDER BY id ASC";
$users_result = mysqli_query($conn, $users_query);

if (!$users_result) {
    echo "<p style='color: red;'>Error getting users: " . mysqli_error($conn) . "</p>";
} else {
    if (mysqli_num_rows($users_result) == 0) {
        echo "<p>No users found in the database.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr>";
        
        while ($user = mysqli_fetch_assoc($users_result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

// Test user creation
echo "<h2>Test User Creation</h2>";

if (isset($_GET['create_test_user']) && $_GET['create_test_user'] == 1) {
    $test_username = "testuser_" . time();
    $test_password = password_hash("password123", PASSWORD_DEFAULT);
    $test_name = "Test User";
    $test_email = "test_" . time() . "@example.com";
    $test_role = "teacher";
    $test_status = "active";
    $test_photo = null;
    
    $query = "INSERT INTO users (username, password, name, email, role, status, photo, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sssssss", $test_username, $test_password, $test_name, $test_email, $test_role, $test_status, $test_photo);
    
    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);
        echo "<p style='color: green;'>Test user created successfully with ID: $user_id</p>";
        echo "<p>Username: $test_username</p>";
        echo "<p>Email: $test_email</p>";
    } else {
        echo "<p style='color: red;'>Error creating test user: " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "<p><a href='?create_test_user=1' style='padding: 5px 10px; background-color: #007bff; color: white; text-decoration: none; border-radius: 3px;'>Create Test User</a></p>";
}

// Check for database errors
echo "<h2>MySQL Status</h2>";
echo "<pre>";
print_r(mysqli_get_connection_stats($conn));
echo "</pre>";

// Display PHP info
echo "<h2>PHP Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>MySQL Client Version: " . mysqli_get_client_info() . "</p>";
echo "<p>MySQL Server Version: " . mysqli_get_server_info($conn) . "</p>";
?> 
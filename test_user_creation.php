<?php
// Include database connection
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h1>Test User Creation</h1>";

// Create a test user
if (isset($_POST['create_user'])) {
    $username = cleanInput($_POST['username']);
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $role = cleanInput($_POST['role']);
    $status = cleanInput($_POST['status']);
    $password = $_POST['password'];
    
    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } else if (valueExists('users', 'username', $username)) {
        $errors[] = 'Username already exists.';
    }
    
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    } else if (valueExists('users', 'email', $email)) {
        $errors[] = 'Email already exists.';
    }
    
    if (empty($role)) {
        $errors[] = 'Role is required.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }
    
    // If no errors, add user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $photo_path = null;
        
        $query = "INSERT INTO users (username, password, name, email, role, status, photo, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssss", $username, $hashed_password, $name, $email, $role, $status, $photo_path);
        
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            echo "<div style='padding: 10px; background-color: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px;'>";
            echo "<strong>Success!</strong> User created with ID: $user_id";
            echo "</div>";
            
            // Log the SQL query for debugging
            echo "<div style='padding: 10px; background-color: #f8f9fa; border-radius: 5px; margin-bottom: 20px;'>";
            echo "<strong>SQL Query:</strong> " . $query . "<br>";
            echo "<strong>Parameters:</strong> " . $username . ", [password], " . $name . ", " . $email . ", " . $role . ", " . $status . ", " . $photo_path;
            echo "</div>";
        } else {
            echo "<div style='padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin-bottom: 20px;'>";
            echo "<strong>Error!</strong> " . mysqli_error($conn);
            echo "</div>";
        }
    } else {
        echo "<div style='padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<strong>Error!</strong> " . implode('<br>', $errors);
        echo "</div>";
    }
}

// Show the form
?>
<form method="post" action="test_user_creation.php" style="max-width: 500px; margin: 0 auto;">
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px;">Username:</label>
        <input type="text" name="username" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px;">Full Name:</label>
        <input type="text" name="name" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px;">Email:</label>
        <input type="email" name="email" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px;">Role:</label>
        <select name="role" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
            <option value="">Select Role</option>
            <option value="admin">Admin</option>
            <option value="registrar">Registrar</option>
            <option value="teacher">Teacher</option>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px;">Status:</label>
        <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label style="display: block; margin-bottom: 5px;">Password:</label>
        <input type="password" name="password" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
    </div>
    
    <div>
        <button type="submit" name="create_user" value="1" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Create User</button>
    </div>
</form>

<hr style="margin: 30px 0;">

<h2>Verify Users in Database</h2>
<?php
// Get all users
$users_query = "SELECT * FROM users ORDER BY id DESC";
$users_result = mysqli_query($conn, $users_query);

if (!$users_result) {
    echo "<p style='color: red;'>Error getting users: " . mysqli_error($conn) . "</p>";
} else {
    if (mysqli_num_rows($users_result) == 0) {
        echo "<p>No users found in the database.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background-color: #f8f9fa;'><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr>";
        
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
?>

<div style="margin-top: 20px;">
    <a href="modules/admin/users.php" style="display: inline-block; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Go to Users Page</a>
</div> 
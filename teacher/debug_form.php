<?php
$title = 'Debug SHS Teacher Form';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// List all users with role 'teacher'
echo '<div class="container mt-4">';
echo '<div class="card">';
echo '<div class="card-header bg-primary text-white">';
echo '<h5 class="card-title mb-0">Available Teacher Users</h5>';
echo '</div>';
echo '<div class="card-body">';

$query = "SELECT id, username, name, email, role FROM users WHERE role = 'teacher'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Role</th></tr></thead>';
    echo '<tbody>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td>' . $row['username'] . '</td>';
        echo '<td>' . $row['name'] . '</td>';
        echo '<td>' . $row['email'] . '</td>';
        echo '<td>' . $row['role'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-warning">No users with teacher role found. Please create a teacher user first.</div>';
}

echo '</div></div>';

// Simple form to add a teacher
echo '<div class="card mt-4">';
echo '<div class="card-header bg-primary text-white">';
echo '<h5 class="card-title mb-0">Add Teacher (Debug Form)</h5>';
echo '</div>';
echo '<div class="card-body">';
?>

<form method="post" action="process_debug_form.php">
    <div class="mb-3">
        <label for="user_id" class="form-label">User Account</label>
        <select class="form-select" id="user_id" name="user_id">
            <option value="">None (No Login Access)</option>
            <?php
            $query = "SELECT u.id, u.name, u.username, u.email 
                      FROM users u 
                      LEFT JOIN teachers t ON u.id = t.user_id 
                      WHERE u.role = 'teacher' AND t.id IS NULL
                      ORDER BY u.name";
            $result = mysqli_query($conn, $query);
            
            if ($result) {
                while ($user = mysqli_fetch_assoc($result)) {
                    echo '<option value="' . $user['id'] . '" data-email="' . htmlspecialchars($user['email']) . '">';
                    echo htmlspecialchars($user['name'] . ' (' . $user['username'] . ')');
                    echo '</option>';
                }
            }
            ?>
        </select>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="John" required>
        </div>
        
        <div class="col-md-6">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="Doe" required>
        </div>
    </div>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="john.doe@example.com">
    </div>
    
    <div class="mb-3">
        <label for="department" class="form-label">Department</label>
        <input type="text" class="form-control" id="department" name="department" value="Senior High School" readonly>
    </div>
    
    <div class="mb-3">
        <label for="subject" class="form-label">Subject</label>
        <input type="text" class="form-control" id="subject" name="subject" value="Mathematics">
    </div>
    
    <div class="mb-3">
        <label for="grade_level" class="form-label">Grade Level</label>
        <select class="form-select" id="grade_level" name="grade_level">
            <option value="11" selected>Grade 11</option>
            <option value="12">Grade 12</option>
            <option value="11-12">Grade 11-12</option>
        </select>
    </div>
    
    <div class="mb-3">
        <label for="contact_number" class="form-label">Contact Number</label>
        <input type="text" class="form-control" id="contact_number" name="contact_number" value="1234567890">
    </div>
    
    <div class="mb-3">
        <label for="qualification" class="form-label">Qualification</label>
        <textarea class="form-control" id="qualification" name="qualification" rows="2">Bachelor of Science in Mathematics</textarea>
    </div>
    
    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status">
            <option value="active" selected>Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    
    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Add Teacher (Debug)
        </button>
    </div>
</form>

<?php
echo '</div></div>';
echo '</div>';

require_once $relative_path . 'includes/footer.php';
?> 
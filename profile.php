<?php
$title = 'My Profile';
require_once 'includes/header.php';

// Ensure default profile image exists
ensureDefaultProfileImage();

// Check if user is logged in
if (!checkAccess()) {
    $_SESSION['alert'] = showAlert('You must log in to access this page.', 'danger');
    redirect('login.php');
}

// Get user data
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get teacher data if user is a teacher
$teacher = null;
if ($_SESSION['role'] == 'teacher') {
    $teacher = getTeacherByUserId($user_id);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update user information
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Security question fields
    $security_question = isset($_POST['security_question']) ? cleanInput($_POST['security_question']) : '';
    $security_answer = isset($_POST['security_answer']) ? cleanInput($_POST['security_answer']) : '';
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    // Check if email already exists
    if (!empty($email) && valueExists('users', 'email', $email, $user_id)) {
        $errors[] = 'Email already exists.';
    }
    
    // Process password change if requested
    if (!empty($current_password)) {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (empty($new_password)) {
            $errors[] = 'New password is required.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
    }
    
    // Validate security question and answer if provided
    if (!empty($security_question) && empty($security_answer)) {
        $errors[] = 'Security answer is required if you select a security question.';
    }
    
    // Process profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        $file_type = $_FILES['profile_image']['type'];
        $file_size = $_FILES['profile_image']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Invalid file type. Only JPG, PNG, and GIF images are allowed.';
        } elseif ($file_size > $max_size) {
            $errors[] = 'File size exceeds the maximum limit of 2MB.';
        }
    }
    
    // Update user if no errors
    if (empty($errors)) {
        // Basic user info update
        $query = "UPDATE users SET name = ?, email = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $name, $email, $user_id);
        $update_user = mysqli_stmt_execute($stmt);
        
        // Update password if requested
        if (!empty($current_password) && !empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
            $update_password = mysqli_stmt_execute($stmt);
        }
        
        // Update security question if provided
        if (!empty($security_question) && !empty($security_answer)) {
            // Hash the security answer for security
            $hashed_answer = password_hash(strtolower($security_answer), PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET security_question = ?, security_answer = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssi", $security_question, $hashed_answer, $user_id);
            $update_security = mysqli_stmt_execute($stmt);
            
            if ($update_security) {
                logAction($user_id, 'UPDATE', 'Updated security question');
            }
        }
        
        // Upload profile image if provided
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/users/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Remove old profile images for this user
            $old_images = glob($upload_dir . 'user_*_' . $user_id . '.*');
            foreach ($old_images as $old_image) {
                if (file_exists($old_image)) {
                    unlink($old_image);
                }
            }
            
            // Generate unique filename
            $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . time() . '_' . $user_id . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Image uploaded successfully
                logAction($user_id, 'UPDATE', 'Updated profile image');
            } else {
                $_SESSION['alert'] = showAlert('Failed to upload profile image.', 'warning');
            }
        }
        
        // Update teacher information if applicable
        if ($_SESSION['role'] == 'teacher' && isset($_POST['contact_info'])) {
            $contact_info = cleanInput($_POST['contact_info']);
            
            if ($teacher) {
                // Update existing teacher record
                $query = "UPDATE teachers SET contact_info = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "si", $contact_info, $user_id);
                $update_teacher = mysqli_stmt_execute($stmt);
            }
        }
        
        // Log action
        logAction($user_id, 'UPDATE', 'Updated profile information');
        
        // Set success message
        $_SESSION['alert'] = showAlert('Profile updated successfully.', 'success');
        
        // Refresh user data
        $user = getUserById($user_id);
        if ($_SESSION['role'] == 'teacher') {
            $teacher = getTeacherByUserId($user_id);
        }
    } else {
        // Display errors
        $error_list = '<ul>';
        foreach ($errors as $error) {
            $error_list .= '<li>' . $error . '</li>';
        }
        $error_list .= '</ul>';
        $_SESSION['alert'] = showAlert('Please fix the following errors:' . $error_list, 'danger');
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">My Profile</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">User Information</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <?php
                    // Check if user has a profile image
                    $profile_image = 'assets/images/default-user.png';
                    $user_image_pattern = 'uploads/users/user_*_' . $user_id . '.{jpg,jpeg,png,gif}';
                    $user_images = glob($user_image_pattern, GLOB_BRACE);
                    
                    if (!empty($user_images)) {
                        // Use the first found image
                        $profile_image = $user_images[0];
                    }
                    ?>
                    <div class="profile-img-container">
                        <img src="<?php echo BASE_URL . $profile_image; ?>" alt="Profile" class="profile-img">
                        <label for="profile_image_upload" class="profile-img-upload" title="Upload new profile picture">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                </div>
                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                <p class="text-muted">
                    <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                </p>
                <p>
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                </p>
                <?php if ($teacher && !empty($teacher['contact_info'])): ?>
                <p>
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($teacher['contact_info']); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Edit Profile</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" id="profile-form">
                    <input type="file" class="d-none" id="profile_image_upload" name="profile_image" accept="image/*" onchange="document.getElementById('profile-form').submit();">
                    
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Image</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                        <div class="form-text">Upload a new profile image (JPG, PNG, or GIF, max 2MB)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <?php if ($_SESSION['role'] == 'teacher' && $teacher): ?>
                    <div class="mb-3">
                        <label for="contact_info" class="form-label">Contact Information</label>
                        <input type="text" class="form-control" id="contact_info" name="contact_info" value="<?php echo htmlspecialchars($teacher['contact_info'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <h5>Change Password</h5>
                    <p class="text-muted">Leave blank if you don't want to change your password.</p>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <hr>
                    
                    <h5>Security Question</h5>
                    <p class="text-muted">Set up a security question to help recover your account if you forget your password.</p>
                    
                    <?php
                    // Define security questions
                    $security_questions = [
                        'What was your childhood nickname?',
                        'What is the name of your first pet?',
                        'What was your first car?',
                        'What elementary school did you attend?',
                        'What is your mother\'s maiden name?',
                        'In what city were you born?',
                        'What is your favorite movie?',
                        'What is your favorite color?'
                    ];
                    ?>
                    
                    <div class="mb-3">
                        <label for="security_question" class="form-label">Security Question</label>
                        <select class="form-select" id="security_question" name="security_question">
                            <option value="">Select a security question</option>
                            <?php foreach ($security_questions as $question): ?>
                                <option value="<?php echo htmlspecialchars($question); ?>" <?php echo (isset($user['security_question']) && $user['security_question'] === $question) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($question); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="security_answer" class="form-label">Answer</label>
                        <input type="text" class="form-control" id="security_answer" name="security_answer" 
                               placeholder="<?php echo isset($user['security_answer']) ? '(Answer already set - enter new answer to change)' : 'Enter your answer'; ?>">
                        <div class="form-text">This will be used to verify your identity if you forget your password.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 

<script>
// Show preview of selected profile image
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        const preview = document.createElement('img');
        preview.className = 'profile-preview';
        
        // Remove any existing preview
        const existingPreview = document.querySelector('.profile-preview');
        if (existingPreview) {
            existingPreview.remove();
        }
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            document.querySelector('.form-text').after(preview);
        }
        
        reader.readAsDataURL(file);
    }
});
</script> 
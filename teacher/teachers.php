<?php
$title = 'SHS Teacher Management';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Helper function to check if a table exists
function tableExists($connection, $tableName) {
    $result = mysqli_query($connection, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// Check if user is logged in and has appropriate roles
if (!checkAccess(['admin', 'registrar', 'teacher'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    header('Location: ' . $relative_path . 'dashboard.php');
    exit;
}

// Check if teachers table exists, if not redirect to create it
$check_table_query = "SHOW TABLES LIKE 'teachers'";
$check_table_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_table_result) == 0) {
    // Table doesn't exist, create it now
    $create_table_query = "CREATE TABLE teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        department VARCHAR(100) DEFAULT NULL,
        subject VARCHAR(100) DEFAULT NULL,
        grade_level VARCHAR(50) DEFAULT NULL,
        qualification TEXT DEFAULT NULL,
        photo VARCHAR(255) DEFAULT NULL,
        contact_number VARCHAR(20) DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        $_SESSION['alert'] = showAlert('The teachers table has been created successfully.', 'success');
    } else {
        $_SESSION['alert'] = showAlert('Error creating teachers table: ' . mysqli_error($conn), 'danger');
        header('Location: ' . $relative_path . 'dashboard.php');
        exit;
    }
}

// Check if photo column exists in teachers table
$check_photo_column = "SHOW COLUMNS FROM teachers LIKE 'photo'";
$photo_result = mysqli_query($conn, $check_photo_column);
if (mysqli_num_rows($photo_result) == 0) {
    // Add the column if it doesn't exist
    $alter_query = "ALTER TABLE teachers ADD COLUMN photo VARCHAR(255) DEFAULT NULL";
    if (mysqli_query($conn, $alter_query)) {
        // Log the addition of the column
        logAction($_SESSION['user_id'], 'UPDATE', 'Added photo column to teachers table');
    } else {
        $_SESSION['alert'] = showAlert('Error adding photo column: ' . mysqli_error($conn), 'danger');
    }
}

// Create directory for teacher photos if it doesn't exist
$upload_dir = $relative_path . 'uploads/teachers';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Only admin and registrar can add/edit/delete teachers
$can_manage = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'registrar');

// Process delete teacher
if ($can_manage && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    
    // Check if teacher exists
    $query = "SELECT t.*, u.name FROM teachers t 
              LEFT JOIN users u ON t.user_id = u.id 
              WHERE t.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $teacher = mysqli_fetch_assoc($result);
        
        // Delete teacher photo if exists
        if (!empty($teacher['photo'])) {
            $photo_path = $relative_path . $teacher['photo'];
            if (file_exists($photo_path)) {
                unlink($photo_path);
            }
        }
        
        // Check for related records that might prevent deletion
        $check_related_records = false;
        $tables_to_check = [
            'class_schedules' => 'SELECT COUNT(*) as count FROM class_schedules WHERE teacher_id = ?'
            // Removed 'sections' check as 'adviser_id' column doesn't exist in the sections table
            // Removed 'grades' check as this table may not exist in the current database schema
        ];
        
        foreach ($tables_to_check as $table => $check_query) {
            if (tableExists($conn, $table)) {
                $check_stmt = mysqli_prepare($conn, $check_query);
                if ($check_stmt) {
                    mysqli_stmt_bind_param($check_stmt, "i", $delete_id);
                    mysqli_stmt_execute($check_stmt);
                    $check_result = mysqli_stmt_get_result($check_stmt);
                    $count_row = mysqli_fetch_assoc($check_result);
                    
                    if ($count_row['count'] > 0) {
                        $check_related_records = true;
                        break;
                    }
                }
            }
        }
        
        if ($check_related_records) {
            // Related records exist, offer to deactivate instead
            $_SESSION['alert'] = showAlert('Cannot delete teacher because there are related records in other tables. Consider deactivating the teacher instead.', 'warning');
            header('Location: ' . $relative_path . 'modules/teacher/teachers.php');
            exit;
        }
        
        // Delete teacher
        $query = "DELETE FROM teachers WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log action
            $log_desc = "Deleted teacher: {$teacher['first_name']} {$teacher['last_name']}";
            logAction($_SESSION['user_id'], 'DELETE', $log_desc);
            
            $_SESSION['alert'] = showAlert('Teacher deleted successfully.', 'success');
        } else {
            // Get the error message
            $error_message = mysqli_error($conn);
            
            // Check if it's a foreign key constraint error
            if (strpos($error_message, 'foreign key constraint fails') !== false) {
                $_SESSION['alert'] = showAlert('Cannot delete teacher because they are referenced in other parts of the system. Consider deactivating the teacher instead.', 'warning');
            } else {
                $_SESSION['alert'] = showAlert('Error deleting teacher: ' . $error_message, 'danger');
            }
        }
    } else {
        $_SESSION['alert'] = showAlert('Teacher not found.', 'danger');
    }
    
    // Redirect to teachers page
    header('Location: ' . $relative_path . 'modules/teacher/teachers.php');
    exit;
}

// Process toggle status
if ($can_manage && isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $teacher_id = (int) $_GET['toggle_status'];
    
    // Get current status
    $query = "SELECT status FROM teachers WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $teacher_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $teacher = mysqli_fetch_assoc($result);
        
        // Toggle status
        $new_status = ($teacher['status'] === 'active') ? 'inactive' : 'active';
        
        $query = "UPDATE teachers SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $new_status, $teacher_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log action
            $log_desc = "Changed teacher status to {$new_status} for teacher ID: {$teacher_id}";
            logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
            
            $_SESSION['alert'] = showAlert('Teacher status updated successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error updating teacher status: ' . mysqli_error($conn), 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('Teacher not found.', 'danger');
    }
    
    // Redirect to teachers page
    header('Location: ' . $relative_path . 'modules/teacher/teachers.php');
    exit;
}

// Process form submission for adding/editing teacher
if ($can_manage && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : null;
    $user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? (int) $_POST['user_id'] : null;
    $first_name = cleanInput($_POST['first_name']);
    $last_name = cleanInput($_POST['last_name']);
    $email = cleanInput($_POST['email']);
    $department = cleanInput($_POST['department']);
    $subject = cleanInput($_POST['subject']);
    $grade_level = cleanInput($_POST['grade_level']);
    $contact_number = cleanInput($_POST['contact_number']);
    $qualification = cleanInput($_POST['qualification']);
    $status = cleanInput($_POST['status']);
    
    // Initialize errors array
    $errors = [];
    
    // Handle photo upload
    $photo_path = null;
    if ($edit_id !== null) {
        // Get existing photo if editing
        $query = "SELECT photo FROM teachers WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $edit_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $photo_path = $row['photo'];
        }
    }
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $relative_path . 'uploads/teachers/';
        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($file_ext, $allowed_exts)) {
            $errors[] = 'Only JPG, JPEG, and PNG files are allowed';
        } else {
            $photo_filename = 'teacher_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_file = $upload_dir . $photo_filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                // Delete old photo if exists
                if (!empty($photo_path)) {
                    $old_photo_path = $relative_path . $photo_path;
                    if (file_exists($old_photo_path)) {
                        unlink($old_photo_path);
                    }
                }
                
                $photo_path = 'uploads/teachers/' . $photo_filename;
            } else {
                $errors[] = 'Failed to upload photo';
            }
        }
    }
    
    // Handle photo deletion
    if (isset($_POST['delete_photo']) && $_POST['delete_photo'] === '1') {
        if (!empty($photo_path)) {
            $old_photo_path = $relative_path . $photo_path;
            if (file_exists($old_photo_path)) {
                unlink($old_photo_path);
            }
            $photo_path = null;
        }
    }
    
    // Validate input
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    // Check if user_id exists in users table before proceeding
    if ($user_id !== null) {
        $user_check_query = "SELECT id FROM users WHERE id = ?";
        $user_check_stmt = mysqli_prepare($conn, $user_check_query);
        mysqli_stmt_bind_param($user_check_stmt, "i", $user_id);
        mysqli_stmt_execute($user_check_stmt);
        mysqli_stmt_store_result($user_check_stmt);
        
        if (mysqli_stmt_num_rows($user_check_stmt) === 0) {
            $errors[] = 'The selected user account does not exist. Please select a valid user or leave it empty.';
            $user_id = null; // Reset to null since it's invalid
        }
        mysqli_stmt_close($user_check_stmt);
    }
    
    // If no errors, proceed with insert/update
    if (empty($errors)) {
        if ($edit_id === null) {
            // Insert new teacher
            $query = "INSERT INTO teachers (user_id, first_name, last_name, email, department, subject, grade_level, qualification, status, contact_number, photo) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "issssssssss", $user_id, $first_name, $last_name, $email, $department, $subject, $grade_level, $qualification, $status, $contact_number, $photo_path);
            
            if (mysqli_stmt_execute($stmt)) {
                $new_teacher_id = mysqli_insert_id($conn);
                
                // Log action
                $log_desc = "Added new teacher: {$first_name} {$last_name}";
                logAction($_SESSION['user_id'], 'CREATE', $log_desc);
                
                $_SESSION['alert'] = showAlert('Teacher added successfully.', 'success');
                header('Location: ' . $relative_path . 'modules/teacher/teachers.php');
                exit;
            } else {
                $errors[] = 'Error adding teacher: ' . mysqli_error($conn);
            }
        } else {
            // Update existing teacher
            $query = "UPDATE teachers SET 
                      user_id = ?, 
                      first_name = ?, 
                      last_name = ?, 
                      email = ?, 
                      department = ?, 
                      subject = ?, 
                      grade_level = ?, 
                      qualification = ?, 
                      status = ?, 
                      contact_number = ?";
            
            // Only update photo if it's set
            if ($photo_path !== null) {
                $query .= ", photo = ?";
            }
            
            $query .= " WHERE id = ?";
            
            $stmt = mysqli_prepare($conn, $query);
            
            if ($photo_path !== null) {
                mysqli_stmt_bind_param($stmt, "issssssssssi", $user_id, $first_name, $last_name, $email, $department, $subject, $grade_level, $qualification, $status, $contact_number, $photo_path, $edit_id);
            } else {
                mysqli_stmt_bind_param($stmt, "isssssssssi", $user_id, $first_name, $last_name, $email, $department, $subject, $grade_level, $qualification, $status, $contact_number, $edit_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                // Log action
                $log_desc = "Updated teacher: {$first_name} {$last_name}";
                logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
                
                $_SESSION['alert'] = showAlert('Teacher updated successfully.', 'success');
                header('Location: ' . $relative_path . 'modules/teacher/teachers.php');
                exit;
            } else {
                $errors[] = 'Error updating teacher: ' . mysqli_error($conn);
            }
        }
    }
    
    // If there are errors, store them in session
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        
        if ($edit_id) {
            header('Location: ' . $relative_path . 'modules/teacher/teachers.php?edit=' . $edit_id);
            exit;
        } else {
            header('Location: ' . $relative_path . 'modules/teacher/teachers.php?action=add');
            exit;
        }
    }
}

// Get teacher to edit if edit parameter is set
$edit_teacher = null;
if ($can_manage && isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    
    $query = "SELECT * FROM teachers WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $edit_teacher = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['alert'] = showAlert('Teacher not found.', 'danger');
        redirect(BASE_URL . 'modules/teacher/teachers.php');
    }
}

// Get teacher users for dropdown
$teacher_users = [];
if ($can_manage) {
    $query = "SELECT u.id, u.name, u.username, u.email 
              FROM users u 
              LEFT JOIN teachers t ON u.id = t.user_id 
              WHERE u.role = 'teacher' AND t.id IS NULL
              ORDER BY u.name";
    
    // If editing, include the current user
    if ($edit_teacher && $edit_teacher['user_id']) {
        $query = "SELECT u.id, u.name, u.username, u.email 
                  FROM users u 
                  LEFT JOIN teachers t ON u.id = t.user_id AND t.id != ? 
                  WHERE u.role = 'teacher' AND (t.id IS NULL OR u.id = ?)
                  ORDER BY u.name";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    
    if ($edit_teacher && $edit_teacher['user_id']) {
        mysqli_stmt_bind_param($stmt, "ii", $edit_teacher['id'], $edit_teacher['user_id']);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $teacher_users[] = $row;
        }
    }
}

// Get filter parameters
$department_filter = 'Senior High School'; // Always filter for Senior High School
$status_filter = isset($_GET['status']) ? cleanInput($_GET['status']) : null;
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : null;

// Check if the department column exists in teachers table
$has_department_column = true;
$check_column_query = "SHOW COLUMNS FROM teachers LIKE 'department'";
$check_result = mysqli_query($conn, $check_column_query);
if (mysqli_num_rows($check_result) == 0) {
    $has_department_column = false;
    
    // Add the column if it doesn't exist
    $alter_query = "ALTER TABLE teachers ADD COLUMN department VARCHAR(100) DEFAULT NULL";
    mysqli_query($conn, $alter_query);
    
    // Add other missing columns if needed
    $columns_to_add = [
        'email' => 'VARCHAR(100) DEFAULT NULL',
        'subject' => 'VARCHAR(100) DEFAULT NULL',
        'qualification' => 'TEXT DEFAULT NULL',
        'status' => "ENUM('active', 'inactive') DEFAULT 'active'"
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        $check_column = "SHOW COLUMNS FROM teachers LIKE '$column'";
        $col_result = mysqli_query($conn, $check_column);
        if (mysqli_num_rows($col_result) == 0) {
            $alter_col_query = "ALTER TABLE teachers ADD COLUMN $column $definition";
            mysqli_query($conn, $alter_col_query);
        }
    }
    
    // Log the addition of the columns
    logAction($_SESSION['user_id'], 'UPDATE', 'Added missing columns to teachers table');
    $_SESSION['alert'] = showAlert('The teachers table has been updated with new fields. Please refresh the page.', 'info');
}

// Get unique departments for filter
$departments = [];
if ($has_department_column) {
    $dept_query = "SELECT DISTINCT department FROM teachers WHERE department IS NOT NULL AND department != '' ORDER BY department";
    $dept_result = mysqli_query($conn, $dept_query);
    if ($dept_result) {
        while ($row = mysqli_fetch_assoc($dept_result)) {
            $departments[] = $row['department'];
        }
    }
}

// Get all teachers with filters
$teachers = [];
$query = "SELECT t.*, u.name, u.username, u.email as user_email 
          FROM teachers t 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE 1=1";

// Add filters
if ($has_department_column) {
    $query .= " AND t.department = 'Senior High School'";
}

if (!empty($status_filter)) {
    // Check if status column exists
    $check_status_query = "SHOW COLUMNS FROM teachers LIKE 'status'";
    $check_status_result = mysqli_query($conn, $check_status_query);
    if (mysqli_num_rows($check_status_result) > 0) {
        $query .= " AND t.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
    }
}

if (!empty($search)) {
    $search_terms = [];
    $search_terms[] = "t.first_name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
    $search_terms[] = "t.last_name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
    
    // Only include fields in search if they exist
    if ($has_department_column) {
        $search_terms[] = "t.email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
        $search_terms[] = "t.department LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
        $search_terms[] = "t.subject LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
    }
    
    $query .= " AND (" . implode(" OR ", $search_terms) . ")";
}

$query .= " ORDER BY t.last_name, t.first_name";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $teachers[] = $row;
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <?php echo $can_manage ? 'Manage SHS Teachers' : 'SHS Teacher Directory'; ?>
        </h1>
    </div>
</div>

<?php if ($can_manage): ?>
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><?php echo $edit_teacher ? 'Edit SHS Teacher' : 'Add New SHS Teacher'; ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>modules/teacher/teachers.php" enctype="multipart/form-data">
                    <?php if ($edit_teacher): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_teacher['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="user_id" class="form-label">User Account (Optional)</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">None (No Login Access)</option>
                            <?php foreach ($teacher_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo ($edit_teacher && $edit_teacher['user_id'] == $user['id']) ? 'selected' : ''; ?> data-email="<?php echo htmlspecialchars($user['email']); ?>">
                                    <?php echo htmlspecialchars($user['name'] . ' (' . $user['username'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Link to a teacher user account or leave empty.</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $edit_teacher ? htmlspecialchars($edit_teacher['first_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $edit_teacher ? htmlspecialchars($edit_teacher['last_name']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <?php if ($has_department_column): ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $edit_teacher ? htmlspecialchars($edit_teacher['email'] ?? '') : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="department" value="Senior High School" readonly>
                        <small class="form-text text-muted">This page is for Senior High School teachers only.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="grade_level" class="form-label">Grade Level</label>
                        <select class="form-select" id="grade_level" name="grade_level">
                            <option value="">Select Grade Level</option>
                            <option value="11" <?php echo ($edit_teacher && $edit_teacher['grade_level'] == '11') ? 'selected' : ''; ?>>Grade 11</option>
                            <option value="12" <?php echo ($edit_teacher && $edit_teacher['grade_level'] == '12') ? 'selected' : ''; ?>>Grade 12</option>
                            <option value="11-12" <?php echo ($edit_teacher && $edit_teacher['grade_level'] == '11-12') ? 'selected' : ''; ?>>Grade 11-12</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject">
                            <option value="">Select a Subject</option>
                            <?php
                            // Check if education_level column exists in subjects table
                            $check_column = mysqli_query($conn, "SHOW COLUMNS FROM subjects LIKE 'education_level'");
                            
                            if (mysqli_num_rows($check_column) > 0) {
                                // Get subjects with education_level filter
                                // We'll load all subjects but filter them with JavaScript
                                $subjects_query = "SELECT name, grade_level FROM subjects WHERE education_level = 'Senior High School' AND status = 'active' ORDER BY grade_level, name";
                            } else {
                                // Fallback if education_level column doesn't exist
                                $subjects_query = "SELECT name, grade_level FROM subjects WHERE status = 'active' ORDER BY grade_level, name";
                            }
                            
                            $subjects_result = mysqli_query($conn, $subjects_query);
                            
                            if ($subjects_result && mysqli_num_rows($subjects_result) > 0) {
                                while ($subject_row = mysqli_fetch_assoc($subjects_result)) {
                                    $selected = ($edit_teacher && $edit_teacher['subject'] == $subject_row['name']) ? 'selected' : '';
                                    $grade_level = isset($subject_row['grade_level']) ? $subject_row['grade_level'] : '';
                                    echo '<option value="' . htmlspecialchars($subject_row['name']) . '" ' . $selected . ' data-grade="' . htmlspecialchars($grade_level) . '">' . 
                                         htmlspecialchars($subject_row['name']) . '</option>';
                                }
                            } else {
                                // Fallback to hardcoded subjects if database query fails
                                $default_subjects = [
                                    ['Oral Communication', '11'],
                                    ['Reading and Writing', '11'],
                                    ['Earth and Life Science', '11'],
                                    ['General Mathematics', '11'],
                                    ['Filipino sa Piling Larang', '11'],
                                    ['Personal Development', '11'],
                                    ['Physical Science', '12'],
                                    ['Statistics and Probability', '12'],
                                    ['Understanding Culture, Society and Politics', '12'],
                                    ['Physical Education and Health', '11-12']
                                ];
                                
                                foreach ($default_subjects as $subject) {
                                    $selected = ($edit_teacher && $edit_teacher['subject'] == $subject[0]) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($subject[0]) . '" ' . $selected . ' data-grade="' . htmlspecialchars($subject[1]) . '">' . 
                                         htmlspecialchars($subject[0]) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="email" value="">
                    <input type="hidden" name="department" value="">
                    <input type="hidden" name="subject" value="">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo $edit_teacher ? htmlspecialchars($edit_teacher['contact_number'] ?? '') : ''; ?>">
                    </div>
                    
                    <?php if ($has_department_column): ?>
                    <div class="mb-3">
                        <label for="qualification" class="form-label">Qualification</label>
                        <textarea class="form-control" id="qualification" name="qualification" rows="2"><?php echo $edit_teacher ? htmlspecialchars($edit_teacher['qualification'] ?? '') : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo ($edit_teacher && isset($edit_teacher['status']) && $edit_teacher['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_teacher && isset($edit_teacher['status']) && $edit_teacher['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="qualification" value="">
                    <input type="hidden" name="status" value="active">
                    <?php endif; ?>
                    
                    <div class="form-group mb-3">
                        <label for="photo">Teacher Photo</label>
                        <?php if ($edit_teacher && !empty($edit_teacher['photo'])): ?>
                            <div class="mb-2">
                                <img src="<?php echo $relative_path . htmlspecialchars($edit_teacher['photo']); ?>" 
                                     alt="Teacher Photo" class="img-thumbnail" style="max-height: 100px;">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="delete_photo" name="delete_photo" value="1">
                                    <label class="form-check-label" for="delete_photo">Delete current photo</label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="photo" name="photo">
                        <small class="form-text text-muted">Upload a photo (JPG, JPEG, PNG)</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $edit_teacher ? 'Update SHS Teacher' : 'Add SHS Teacher'; ?>
                        </button>
                        
                        <?php if ($edit_teacher): ?>
                            <a href="<?php echo BASE_URL; ?>modules/teacher/teachers.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
<?php else: ?>
<div class="row mb-4">
    <div class="col-12">
<?php endif; ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Filter SHS Teachers</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo BASE_URL; ?>modules/teacher/teachers.php" class="row g-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Name, Email, Subject...">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">SHS Teacher List</h5>
                <div>
                    <?php
                    // Build export URL with current filters
                    $export_url = BASE_URL . 'modules/reports/teacher_list.php?export=excel';
                    if (!empty($department_filter)) {
                        $export_url .= '&department=' . urlencode($department_filter);
                    }
                    if (!empty($status_filter)) {
                        $export_url .= '&status=' . urlencode($status_filter);
                    }
                    ?>
                    <a href="<?php echo $export_url; ?>" class="btn btn-success btn-sm" target="_blank">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <?php if ($has_department_column): ?>
                                <th>Department</th>
                                <th>Subject</th>
                                <?php endif; ?>
                                <th>Contact</th>
                                <?php if ($has_department_column): ?>
                                <th>Status</th>
                                <?php endif; ?>
                                <?php if ($can_manage): ?>
                                <th>User Account</th>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teachers)): ?>
                                <tr>
                                    <td colspan="<?php echo ($can_manage ? ($has_department_column ? 7 : 4) : ($has_department_column ? 5 : 2)); ?>" class="text-center">No teachers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <?php if (!empty($teacher['photo'])): ?>
                                                        <img src="<?php echo $relative_path . htmlspecialchars($teacher['photo']); ?>" 
                                                             alt="Teacher Photo" class="img-thumbnail" style="max-height: 50px; max-width: 50px;">
                                                    <?php else: ?>
                                                        <i class="fas fa-user text-secondary"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ms-3">
                                                    <strong><?php echo htmlspecialchars($teacher['last_name'] . ', ' . $teacher['first_name']); ?></strong>
                                                    <?php if ($has_department_column && !empty($teacher['email'])): ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($teacher['email']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <?php if ($has_department_column): ?>
                                        <td><?php echo htmlspecialchars($teacher['department'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['subject'] ?? 'N/A'); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($teacher['contact_number'] ?? 'N/A'); ?></td>
                                        <?php if ($has_department_column): ?>
                                        <td>
                                            <?php 
                                            $status_class = isset($teacher['status']) && $teacher['status'] === 'active' ? 'bg-success' : 'bg-danger';
                                            $status_text = isset($teacher['status']) ? ucfirst($teacher['status']) : 'Unknown';
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <?php endif; ?>
                                        <?php if ($can_manage): ?>
                                        <td>
                                            <?php if ($teacher['user_id']): ?>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($teacher['username']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo BASE_URL; ?>modules/teacher/teachers.php?edit=<?php echo $teacher['id']; ?>" class="btn btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" onclick="return confirmDelete('<?php echo BASE_URL; ?>modules/teacher/teachers.php?delete=<?php echo $teacher['id']; ?>', '<?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>')" class="btn btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>modules/teacher/schedule.php?teacher_id=<?php echo $teacher['id']; ?>" class="btn btn-info" title="View Schedule">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>modules/teacher/teachers.php?toggle_status=<?php echo $teacher['id']; ?>" class="btn <?php echo isset($teacher['status']) && $teacher['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo isset($teacher['status']) && $teacher['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas <?php echo isset($teacher['status']) && $teacher['status'] === 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted">
                Total SHS teachers: <?php echo count($teachers); ?>
            </div>
        </div>
    </div>
</div>

<style>
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
}
.avatar-initials {
    font-size: 16px;
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-populate email from selected user account
    const userIdSelect = document.getElementById('user_id');
    const emailInput = document.getElementById('email');
    
    if (userIdSelect && emailInput) {
        userIdSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.email) {
                emailInput.value = selectedOption.dataset.email;
            } else {
                emailInput.value = '';
            }
        });
    }
});

// Function to confirm teacher deletion
function confirmDelete(deleteUrl, teacherName) {
    if (confirm('Are you sure you want to delete ' + teacherName + '? This action cannot be undone.')) {
        window.location.href = deleteUrl;
    }
    return false;
}
</script>

<script>
$(document).ready(function() {
    // Auto-fill email from user selection
    $('#user_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var email = selectedOption.data('email') || '';
        $('#email').val(email);
    });
    
    // Initialize DataTables with checking if already initialized
    $('.data-table').each(function() {
        if (!$.fn.DataTable.isDataTable(this)) {
            $(this).DataTable({
                responsive: true
            });
        }
    });
    
    // Form validation
    (function() {
        'use strict';
        
        // Fetch all forms we want to apply validation to
        var forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
});
</script> 

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<script>
// Filter subjects based on selected grade level
document.addEventListener('DOMContentLoaded', function() {
    const gradeSelect = document.getElementById('grade_level');
    const subjectSelect = document.getElementById('subject');
    
    if (gradeSelect && subjectSelect) {
        // Store all original options
        const allSubjectOptions = Array.from(subjectSelect.options);
        
        // Function to filter subjects based on selected grade
        function filterSubjects() {
            const selectedGrade = gradeSelect.value;
            
            // Clear current options
            subjectSelect.innerHTML = '<option value="">Select a Subject</option>';
            
            // Filter and add options based on grade level
            allSubjectOptions.forEach(option => {
                const grade = option.getAttribute('data-grade');
                
                // Add option if it matches the selected grade or is for both grades
                // or if no grade is selected (show all)
                if (!selectedGrade || 
                    selectedGrade === grade || 
                    grade === '11-12' || 
                    (selectedGrade === 'Grade 11' && grade === '11') ||
                    (selectedGrade === 'Grade 12' && grade === '12')) {
                    subjectSelect.appendChild(option.cloneNode(true));
                }
            });
        }
        
        // Initial filtering
        filterSubjects();
        
        // Filter when grade level changes
        gradeSelect.addEventListener('change', filterSubjects);
    }
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
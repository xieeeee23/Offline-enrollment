<?php
$title = 'SHS Subject Management';
$page_header = 'SHS Subject Management';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    header("Location: " . BASE_URL . "dashboard.php");
    exit();
}

// Create subjects table if it doesn't exist
$check_table = "SHOW TABLES LIKE 'subjects'";
$table_exists = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_exists) == 0) {
    $create_table = "CREATE TABLE subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(20) NOT NULL,
        description TEXT,
        education_level VARCHAR(50) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table)) {
        // Add default subjects
        $default_subjects = [
            // Senior High School
            ['Oral Communication', 'SHS-OCOM', 'Oral communication skills', 'Senior High School', '11'],
            ['Reading and Writing', 'SHS-RW', 'Reading and writing skills', 'Senior High School', '11'],
            ['Earth and Life Science', 'SHS-ELS', 'Earth and life science', 'Senior High School', '11'],
            ['Physical Science', 'SHS-PS', 'Physical science', 'Senior High School', '11'],
            ['Statistics and Probability', 'SHS-STAT', 'Statistics and probability', 'Senior High School', '11'],
            ['General Mathematics', 'SHS-GMATH', 'General mathematics', 'Senior High School', '11'],
            ['Filipino sa Piling Larang', 'SHS-FPL', 'Filipino in selected fields', 'Senior High School', '11'],
            ['Personal Development', 'SHS-PD', 'Personal development', 'Senior High School', '11'],
            ['Understanding Culture, Society and Politics', 'SHS-UCSP', 'Culture, society and politics', 'Senior High School', '11'],
            ['Physical Education and Health', 'SHS-PE', 'Physical education and health', 'Senior High School', '11']
        ];
        
        foreach ($default_subjects as $subject) {
            $insert = "INSERT INTO subjects (name, code, description, education_level, grade_level) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert);
            mysqli_stmt_bind_param($stmt, 'sssss', $subject[0], $subject[1], $subject[2], $subject[3], $subject[4]);
            mysqli_stmt_execute($stmt);
        }
    } else {
        $_SESSION['alert'] = showAlert('Error creating subjects table: ' . mysqli_error($conn), 'danger');
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add or update subject
    if (isset($_POST['action']) && $_POST['action'] == 'save_subject') {
        $id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
        $name = cleanInput($_POST['name']);
        $code = cleanInput($_POST['code']);
        $description = cleanInput($_POST['description']);
        $education_level = cleanInput($_POST['education_level']);
        $grade_level = cleanInput($_POST['grade_level']);
        $status = cleanInput($_POST['status']);
        
        // Validate input
        if (empty($name) || empty($code) || empty($education_level) || empty($grade_level)) {
            $_SESSION['alert'] = showAlert('Please fill in all required fields.', 'danger');
        } else {
            try {
                if ($id > 0) {
                    // Update existing subject
                    $update = "UPDATE subjects SET 
                              name = ?, 
                              code = ?, 
                              description = ?, 
                              education_level = ?, 
                              grade_level = ?, 
                              status = ? 
                              WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update);
                    mysqli_stmt_bind_param($stmt, 'ssssssi', $name, $code, $description, $education_level, $grade_level, $status, $id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['alert'] = showAlert('Subject updated successfully!', 'success');
                        logAction($_SESSION['user_id'], 'UPDATE', "Updated subject: $name");
                    } else {
                        $_SESSION['alert'] = showAlert('Error updating subject: ' . mysqli_error($conn), 'danger');
                    }
                } else {
                    // Add new subject
                    $insert = "INSERT INTO subjects (name, code, description, education_level, grade_level, status) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert);
                    mysqli_stmt_bind_param($stmt, 'ssssss', $name, $code, $description, $education_level, $grade_level, $status);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $_SESSION['alert'] = showAlert('Subject added successfully!', 'success');
                        logAction($_SESSION['user_id'], 'CREATE', "Added new subject: $name");
                    } else {
                        $_SESSION['alert'] = showAlert('Error adding subject: ' . mysqli_error($conn), 'danger');
                    }
                }
            } catch (Exception $e) {
                $_SESSION['alert'] = showAlert('Error: ' . $e->getMessage(), 'danger');
            }
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . BASE_URL . "modules/academics/subjects.php");
        exit();
    }
    
    // Delete subject
    if (isset($_POST['action']) && $_POST['action'] == 'delete_subject') {
        $id = (int)$_POST['delete_id'];
        
        // Check if subject is being used in schedules
        $check_usage = "SELECT COUNT(*) as count FROM schedules WHERE subject = (SELECT name FROM subjects WHERE id = ?)";
        $stmt = mysqli_prepare($conn, $check_usage);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $usage = mysqli_fetch_assoc($result);
        
        if ($usage['count'] > 0) {
            $_SESSION['alert'] = showAlert('Cannot delete subject as it is being used in schedules.', 'danger');
        } else {
            $delete = "DELETE FROM subjects WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete);
            mysqli_stmt_bind_param($stmt, 'i', $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['alert'] = showAlert('Subject deleted successfully!', 'success');
                logAction($_SESSION['user_id'], 'DELETE', "Deleted subject ID: $id");
            } else {
                $_SESSION['alert'] = showAlert('Error deleting subject: ' . mysqli_error($conn), 'danger');
            }
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . BASE_URL . "modules/academics/subjects.php");
        exit();
    }
}

// Get education levels
$education_levels_query = "SELECT * FROM education_levels ORDER BY id";
$education_levels_result = mysqli_query($conn, $education_levels_query);
$education_levels = [];

while ($row = mysqli_fetch_assoc($education_levels_result)) {
    $education_levels[] = $row;
}

// Get subject to edit if ID is provided
$edit_subject = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM subjects WHERE id = ?";
    $stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($stmt, 'i', $edit_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_subject = mysqli_fetch_assoc($result);
}

// Get filter parameters
$filter_education_level = isset($_GET['filter_education_level']) ? cleanInput($_GET['filter_education_level']) : 'Senior High School';
$filter_grade_level = isset($_GET['filter_grade_level']) ? cleanInput($_GET['filter_grade_level']) : '';
$filter_status = isset($_GET['filter_status']) ? cleanInput($_GET['filter_status']) : '';
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Build query for subjects list
$query_params = [];
$where_clauses = ["1=1"];

if (!empty($filter_education_level)) {
    $where_clauses[] = "education_level = ?";
    $query_params[] = $filter_education_level;
}

if (!empty($filter_grade_level)) {
    $where_clauses[] = "grade_level = ?";
    $query_params[] = $filter_grade_level;
}

if (!empty($filter_status)) {
    $where_clauses[] = "status = ?";
    $query_params[] = $filter_status;
}

if (!empty($search)) {
    $where_clauses[] = "(name LIKE ? OR code LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $query_params[] = $search_param;
    $query_params[] = $search_param;
    $query_params[] = $search_param;
}

$subjects_sql = "SELECT * FROM subjects WHERE " . implode(' AND ', $where_clauses) . " ORDER BY grade_level, name";
$subjects_stmt = mysqli_prepare($conn, $subjects_sql);

if (!empty($query_params)) {
    $types = str_repeat('s', count($query_params));
    mysqli_stmt_bind_param($subjects_stmt, $types, ...$query_params);
}

mysqli_stmt_execute($subjects_stmt);
$subjects_result = mysqli_stmt_get_result($subjects_stmt);
$subjects = [];

while ($row = mysqli_fetch_assoc($subjects_result)) {
    $subjects[] = $row;
}

// Get distinct grade levels for filter
$grade_levels_query = "SELECT DISTINCT grade_level FROM subjects ORDER BY grade_level";
$grade_levels_result = mysqli_query($conn, $grade_levels_query);
$grade_levels = [];

while ($row = mysqli_fetch_assoc($grade_levels_result)) {
    $grade_levels[] = $row['grade_level'];
}
?>

<div class="container-fluid px-4">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])) echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    
    <div class="row">
        <!-- Subject Form -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><?php echo $edit_subject ? 'Edit Subject' : 'Add New Subject'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo BASE_URL; ?>modules/academics/subjects.php">
                        <input type="hidden" name="action" value="save_subject">
                        <?php if ($edit_subject): ?>
                            <input type="hidden" name="subject_id" value="<?php echo $edit_subject['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Subject Name*</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $edit_subject ? htmlspecialchars($edit_subject['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="code" class="form-label">Subject Code*</label>
                            <input type="text" class="form-control" id="code" name="code" value="<?php echo $edit_subject ? htmlspecialchars($edit_subject['code']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo $edit_subject ? htmlspecialchars($edit_subject['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="education_level" class="form-label">Education Level*</label>
                            <input type="text" class="form-control" id="education_level" name="education_level" value="Senior High School" readonly>
                            <small class="form-text text-muted">This page is for Senior High School subjects only.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="grade_level" class="form-label">Grade Level*</label>
                            <select class="form-select" id="grade_level" name="grade_level" required>
                                <option value="">Select Grade Level</option>
                                <option value="11" <?php echo ($edit_subject && $edit_subject['grade_level'] == '11') ? 'selected' : ''; ?>>Grade 11</option>
                                <option value="12" <?php echo ($edit_subject && $edit_subject['grade_level'] == '12') ? 'selected' : ''; ?>>Grade 12</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo ($edit_subject && $edit_subject['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($edit_subject && $edit_subject['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?php echo $edit_subject ? 'Update Subject' : 'Add Subject'; ?>
                            </button>
                            <?php if ($edit_subject): ?>
                            <a href="<?php echo BASE_URL; ?>modules/academics/subjects.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Subjects List -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Subjects List</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo BASE_URL; ?>modules/academics/subjects.php" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="filter_grade_level" class="form-label">Filter by Grade Level</label>
                                <select class="form-select" id="filter_grade_level" name="filter_grade_level">
                                    <option value="">All Grade Levels</option>
                                    <option value="11" <?php echo $filter_grade_level == '11' ? 'selected' : ''; ?>>Grade 11</option>
                                    <option value="12" <?php echo $filter_grade_level == '12' ? 'selected' : ''; ?>>Grade 12</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="filter_status" class="form-label">Filter by Status</label>
                                <select class="form-select" id="filter_status" name="filter_status">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $filter_status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search subject name, code, or description">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                                <a href="<?php echo BASE_URL; ?>modules/academics/subjects.php" class="btn btn-secondary">
                                    <i class="fas fa-sync-alt me-2"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Grade Level</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($subjects) > 0): ?>
                                    <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                        <td><?php echo 'Grade ' . htmlspecialchars($subject['grade_level']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $subject['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($subject['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>modules/academics/subjects.php?edit=<?php echo $subject['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $subject['id']; ?>">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                            
                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $subject['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $subject['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $subject['id']; ?>">Confirm Delete</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Are you sure you want to delete the subject: <strong><?php echo htmlspecialchars($subject['name']); ?></strong>?
                                                            <p class="text-danger mt-2">This action cannot be undone.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <form method="post" action="<?php echo BASE_URL; ?>modules/academics/subjects.php">
                                                                <input type="hidden" name="action" value="delete_subject">
                                                                <input type="hidden" name="delete_id" value="<?php echo $subject['id']; ?>">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No subjects found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
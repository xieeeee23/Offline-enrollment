<?php
$title = 'Manage Sections';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Get the current file name without path
$current_file = basename($_SERVER['PHP_SELF']);

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Check if sections table exists, create if not
$query = "SHOW TABLES LIKE 'sections'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Create the sections table - MODIFIED: Removed foreign key constraint to avoid errors
    $query = "CREATE TABLE sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        grade_level ENUM('Grade 11', 'Grade 12') NOT NULL,
        strand VARCHAR(20) NOT NULL,
        max_students INT DEFAULT 40,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        school_year VARCHAR(20) NOT NULL,
        semester ENUM('First', 'Second') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Error creating sections table: ' . mysqli_error($conn), 'danger');
    } else {
        $_SESSION['alert'] = showAlert('Sections table created successfully.', 'success');
    }
}

// Process form submission for adding/editing section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_section'])) {
    $edit_id = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : null;
    $name = cleanInput($_POST['name']);
    // Remove education_level as it doesn't exist in the table
    $grade_level = cleanInput($_POST['grade_level']);
    $strand = cleanInput($_POST['strand']);
    $max_students = (int) cleanInput($_POST['max_students']);
    $school_year = cleanInput($_POST['school_year']);
    $semester = cleanInput($_POST['semester']);
    $status = cleanInput($_POST['status']);
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Section name is required.';
    }
    
    if (empty($grade_level)) {
        $errors[] = 'Grade level is required.';
    }
    
    if (empty($strand)) {
        $errors[] = 'Strand is required.';
    }
    
    if (empty($school_year)) {
        $errors[] = 'School year is required.';
    }
    
    if (empty($semester)) {
        $errors[] = 'Semester is required.';
    }
    
    if ($max_students <= 0) {
        $errors[] = 'Maximum students must be greater than zero.';
    }
    
    // Check if section name already exists for the same grade level and strand
    if (!empty($name) && !empty($grade_level) && !empty($strand)) {
        $query = "SELECT id FROM sections WHERE name = ? AND grade_level = ? AND strand = ? AND school_year = ? AND semester = ?";
        if ($edit_id !== null) {
            $query .= " AND id != ?";
        }
        
        $stmt = mysqli_prepare($conn, $query);
        
        if ($edit_id !== null) {
            mysqli_stmt_bind_param($stmt, "sssssi", $name, $grade_level, $strand, $school_year, $semester, $edit_id);
        } else {
            mysqli_stmt_bind_param($stmt, "sssss", $name, $grade_level, $strand, $school_year, $semester);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $errors[] = 'A section with this name already exists for the selected grade level and strand.';
        }
    }
    
    // If no errors, add or update section
    if (empty($errors)) {
        if ($edit_id === null) {
            // Add new section
            $query = "INSERT INTO sections (name, grade_level, strand, max_students, school_year, semester, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssisss", $name, $grade_level, $strand, $max_students, $school_year, $semester, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['alert'] = showAlert('Section added successfully.', 'success');
                header("Location: " . BASE_URL . "modules/registrar/manage_sections.php");
                exit;
            } else {
                $_SESSION['alert'] = showAlert('Error adding section: ' . mysqli_error($conn), 'danger');
            }
        } else {
            // Update existing section
            $query = "UPDATE sections SET name = ?, grade_level = ?, strand = ?, max_students = ?, school_year = ?, semester = ?, status = ? 
                      WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssisssi", $name, $grade_level, $strand, $max_students, $school_year, $semester, $status, $edit_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['alert'] = showAlert('Section updated successfully.', 'success');
                header("Location: " . BASE_URL . "modules/registrar/manage_sections.php");
                exit;
            } else {
                $_SESSION['alert'] = showAlert('Error updating section: ' . mysqli_error($conn), 'danger');
            }
        }
    }
    
    if (!empty($errors)) {
        // Display errors
        $error_list = '<ul>';
        foreach ($errors as $error) {
            $error_list .= '<li>' . $error . '</li>';
        }
        $error_list .= '</ul>';
        $_SESSION['alert'] = showAlert('Please fix the following errors:' . $error_list, 'danger');
    }
}

// Process section deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int) $_GET['id'];
    
    // Check if section exists
    $query = "SELECT * FROM sections WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $section = mysqli_fetch_assoc($result);
        
        // Delete section
        $query = "DELETE FROM sections WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alert'] = showAlert('Section deleted successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error deleting section: ' . mysqli_error($conn), 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('Section not found.', 'danger');
    }
    
    header("Location: " . BASE_URL . "modules/registrar/manage_sections.php");
    exit;
}

// Get section to edit if edit parameter is set
$edit_section = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = (int) $_GET['id'];
    
    $query = "SELECT * FROM sections WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $edit_section = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['alert'] = showAlert('Section not found.', 'danger');
        header("Location: " . BASE_URL . "modules/registrar/manage_sections.php");
        exit;
    }
}

// Get filter parameters
$grade_level_filter = isset($_GET['grade_level']) ? cleanInput($_GET['grade_level']) : '';
$strand_filter = isset($_GET['strand']) ? cleanInput($_GET['strand']) : '';
$status_filter = isset($_GET['status']) ? cleanInput($_GET['status']) : '';

// Get all strands for dropdown
$query = "SELECT strand_code, strand_name FROM shs_strands ORDER BY strand_name";
$result = mysqli_query($conn, $query);
$strands = [];
while ($row = mysqli_fetch_assoc($result)) {
    $strands[] = $row;
}

// Get all school years for dropdown
$query = "SELECT school_year FROM school_years ORDER BY school_year DESC";
$result = mysqli_query($conn, $query);
$school_years = [];
while ($row = mysqli_fetch_assoc($result)) {
    $school_years[] = $row;
}

// Get sections with student counts
$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM students WHERE section = s.name AND grade_level = s.grade_level) as student_count
          FROM sections s";

// Add filters if set
$where_clauses = [];
$params = [];
$types = '';

if (!empty($grade_level_filter)) {
    $where_clauses[] = "s.grade_level = ?";
    $params[] = $grade_level_filter;
    $types .= 's';
}

if (!empty($strand_filter)) {
    $where_clauses[] = "s.strand = ?";
    $params[] = $strand_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

$query .= " ORDER BY s.grade_level, s.name";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sections = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sections[] = $row;
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Manage Sections</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><?php echo isset($edit_section) ? 'Edit Section' : 'Add New Section'; ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>modules/registrar/manage_sections.php">
                    <?php if (isset($edit_section)): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_section['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Section Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($edit_section['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="grade_level" class="form-label">Grade Level <span class="text-danger">*</span></label>
                        <select class="form-select" id="grade_level" name="grade_level" required>
                            <option value="">Select Grade Level</option>
                            <option value="Grade 11" <?php echo (isset($edit_section) && $edit_section['grade_level'] === 'Grade 11') ? 'selected' : ''; ?>>Grade 11</option>
                            <option value="Grade 12" <?php echo (isset($edit_section) && $edit_section['grade_level'] === 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="strand" class="form-label">Strand <span class="text-danger">*</span></label>
                        <select class="form-select" id="strand" name="strand" required>
                            <option value="">Select Strand</option>
                            <?php foreach ($strands as $strand): ?>
                                <option value="<?php echo $strand['strand_code']; ?>" <?php echo (isset($edit_section) && $edit_section['strand'] === $strand['strand_code']) ? 'selected' : ''; ?>>
                                    <?php echo $strand['strand_code'] . ' - ' . $strand['strand_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="max_students" class="form-label">Maximum Students</label>
                        <input type="number" class="form-control" id="max_students" name="max_students" value="<?php echo htmlspecialchars($edit_section['max_students'] ?? '40'); ?>" min="1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="school_year" class="form-label">School Year <span class="text-danger">*</span></label>
                        <select class="form-select" id="school_year" name="school_year" required>
                            <option value="">Select School Year</option>
                            <?php foreach ($school_years as $year): ?>
                                <option value="<?php echo $year['school_year']; ?>" <?php echo (isset($edit_section) && $edit_section['school_year'] === $year['school_year']) ? 'selected' : ''; ?>>
                                    <?php echo $year['school_year']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="First" <?php echo (isset($edit_section) && $edit_section['semester'] === 'First') ? 'selected' : ''; ?>>First</option>
                            <option value="Second" <?php echo (isset($edit_section) && $edit_section['semester'] === 'Second') ? 'selected' : ''; ?>>Second</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Active" <?php echo (isset($edit_section) && $edit_section['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo (isset($edit_section) && $edit_section['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <?php if (isset($edit_section)): ?>
                            <a href="<?php echo BASE_URL; ?>modules/registrar/manage_sections.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        <?php else: ?>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Reset
                            </button>
                        <?php endif; ?>
                        
                        <button type="submit" name="save_section" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> <?php echo isset($edit_section) ? 'Update' : 'Save'; ?> Section
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Section List</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo BASE_URL; ?>modules/registrar/manage_sections.php" class="row g-3 mb-3">
                    <div class="col-md-2">
                        <label for="grade_level_filter" class="form-label">Grade Level</label>
                        <select class="form-select" id="grade_level_filter" name="grade_level">
                            <option value="">All Grade Levels</option>
                            <option value="Grade 11" <?php echo ($grade_level_filter === 'Grade 11') ? 'selected' : ''; ?>>Grade 11</option>
                            <option value="Grade 12" <?php echo ($grade_level_filter === 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="strand_filter" class="form-label">Strand</label>
                        <select class="form-select" id="strand_filter" name="strand">
                            <option value="">All Strands</option>
                            <?php foreach ($strands as $strand): ?>
                                <option value="<?php echo $strand['strand_code']; ?>" <?php echo ($strand_filter === $strand['strand_code']) ? 'selected' : ''; ?>>
                                    <?php echo $strand['strand_code']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="school_year_filter" class="form-label">School Year</label>
                        <select class="form-select" id="school_year_filter" name="school_year">
                            <option value="">All Years</option>
                            <?php foreach ($school_years as $year): ?>
                                <option value="<?php echo $year['school_year']; ?>" <?php echo ($school_year_filter === $year['school_year']) ? 'selected' : ''; ?>>
                                    <?php echo $year['school_year']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="semester_filter" class="form-label">Semester</label>
                        <select class="form-select" id="semester_filter" name="semester">
                            <option value="">All Semesters</option>
                            <option value="First" <?php echo ($semester_filter === 'First') ? 'selected' : ''; ?>>First</option>
                            <option value="Second" <?php echo ($semester_filter === 'Second') ? 'selected' : ''; ?>>Second</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="status_filter" class="form-label">Status</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">All Status</option>
                            <option value="Active" <?php echo ($status_filter === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($status_filter === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="sectionsTable">
                        <thead>
                            <tr>
                                <th>Section Name</th>
                                <th>Grade Level</th>
                                <th>Strand</th>
                                <th>School Year</th>
                                <th>Semester</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php foreach ($sections as $section): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($section['name']); ?></td>
                                <td><?php echo htmlspecialchars($section['grade_level']); ?></td>
                                        <td><?php echo htmlspecialchars($section['strand']); ?></td>
                                        <td><?php echo htmlspecialchars($section['school_year']); ?></td>
                                        <td><?php echo htmlspecialchars($section['semester']); ?></td>
                                        <td>
                                    <?php 
                                    $capacity_percentage = ($section['student_count'] / $section['max_students']) * 100;
                                    $progress_class = 'bg-success';
                                    $capacity_warning = '';
                                    
                                    if ($capacity_percentage >= 100) {
                                        $progress_class = 'bg-danger';
                                        $capacity_warning = '<span class="badge bg-danger ms-2">Full</span>';
                                    } elseif ($capacity_percentage >= 90) {
                                        $progress_class = 'bg-warning';
                                        $capacity_warning = '<span class="badge bg-warning text-dark ms-2">Near Capacity</span>';
                                    }
                                    ?>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2"><?php echo $section['student_count']; ?> / <?php echo $section['max_students']; ?></div>
                                        <?php echo $capacity_warning; ?>
                                    </div>
                                    <div class="progress mt-1" style="height: 5px;">
                                        <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" 
                                             style="width: <?php echo min(100, $capacity_percentage); ?>%;" 
                                             aria-valuenow="<?php echo $section['student_count']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="<?php echo $section['max_students']; ?>"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $section['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo htmlspecialchars($section['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                    <button class="btn btn-sm btn-primary edit-section" 
                                            data-id="<?php echo $section['id']; ?>"
                                            onclick="window.location.href='<?php echo BASE_URL; ?>modules/registrar/manage_sections.php?action=edit&id=<?php echo $section['id']; ?>'">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-section" 
                                            data-id="<?php echo $section['id']; ?>"
                                            onclick="event.preventDefault(); Swal.fire({
                                                title: 'Delete Section',
                                                text: 'Are you sure you want to delete this section? This action cannot be undone.',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#d33',
                                                cancelButtonColor: '#3085d6',
                                                confirmButtonText: 'Yes, delete it!',
                                                cancelButtonText: 'Cancel'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    window.location.href='<?php echo BASE_URL; ?>modules/registrar/manage_sections.php?action=delete&id=<?php echo $section['id']; ?>';
                                                }
                                            });">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle edit button clicks
    document.querySelectorAll('.edit-section').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('data-id');
            window.location.href = `${BASE_URL}modules/registrar/manage_sections.php?action=edit&id=${sectionId}`;
        });
    });
    
    // Handle delete button clicks with SweetAlert2
    document.querySelectorAll('.delete-section').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Delete Section',
                text: 'Are you sure you want to delete this section? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `${BASE_URL}modules/registrar/manage_sections.php?action=delete&id=${sectionId}`;
                }
            });
        });
    });
    
    // Initialize DataTable for better table functionality
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#sectionsTable').DataTable({
            "responsive": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "order": [[1, 'asc'], [0, 'asc']] // Sort by grade level then section name
        });
    }
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
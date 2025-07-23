<?php
$page = 'course_management';
$title = 'Course Management';
require_once '../../includes/header.php';
checkAccess(['admin']);

$conn = getConnection();

// Create strands table if it doesn't exist
$create_strands_table = "
CREATE TABLE IF NOT EXISTS strands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    applicable_levels VARCHAR(100) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_strands_table);

// Create courses table if it doesn't exist
$create_courses_table = "
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    strand_id INT,
    grade_level VARCHAR(20) NOT NULL,
    units INT DEFAULT 1,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (code, grade_level),
    FOREIGN KEY (strand_id) REFERENCES strands(id) ON DELETE SET NULL
)";
$conn->query($create_courses_table);

// Insert default strands if none exist
$check_strands = $conn->query("SELECT COUNT(*) as count FROM strands");
$row = $check_strands->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO strands (code, name, description, applicable_levels) VALUES
        ('GEN', 'General Education', 'General education curriculum for all students', 'K,1,2,3,4,5,6,7,8,9,10'),
        ('STEM', 'Science, Technology, Engineering, and Mathematics', 'Focuses on science and mathematics', '11,12'),
        ('ABM', 'Accountancy, Business, and Management', 'Focuses on business and financial management', '11,12'),
        ('HUMSS', 'Humanities and Social Sciences', 'Focuses on literature, philosophy, and social sciences', '11,12'),
        ('GAS', 'General Academic Strand', 'General academic studies for senior high school', '11,12'),
        ('TVL', 'Technical-Vocational-Livelihood', 'Provides job-ready skills for various industries', '11,12')");
}

// Process form submission for strand
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_strand'])) {
    $strand_id = isset($_POST['strand_id']) ? (int)$_POST['strand_id'] : 0;
    $code = cleanInput($_POST['code']);
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $applicable_levels = isset($_POST['applicable_levels']) ? implode(',', $_POST['applicable_levels']) : '';
    $status = cleanInput($_POST['status']);
    
    $errors = [];
    if (empty($code)) $errors[] = "Strand code is required";
    if (empty($name)) $errors[] = "Strand name is required";
    if (empty($applicable_levels)) $errors[] = "At least one applicable grade level must be selected";
    
    if (empty($errors)) {
        if ($strand_id > 0) {
            // Update existing strand
            $stmt = $conn->prepare("UPDATE strands SET code = ?, name = ?, description = ?, applicable_levels = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $code, $name, $description, $applicable_levels, $status, $strand_id);
            if ($stmt->execute()) {
                logAction($_SESSION['user_id'], 'Updated strand', "Strand ID: $strand_id, Name: $name");
                showAlert("Strand updated successfully", "success");
            } else {
                showAlert("Error updating strand: " . $stmt->error, "danger");
            }
        } else {
            // Insert new strand
            $stmt = $conn->prepare("INSERT INTO strands (code, name, description, applicable_levels, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $code, $name, $description, $applicable_levels, $status);
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                logAction($_SESSION['user_id'], 'Added new strand', "Strand ID: $new_id, Name: $name");
                showAlert("Strand added successfully", "success");
            } else {
                showAlert("Error adding strand: " . $stmt->error, "danger");
            }
        }
    } else {
        foreach ($errors as $error) {
            showAlert($error, "danger");
        }
    }
}

// Process form submission for course
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_course'])) {
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $code = cleanInput($_POST['course_code']);
    $name = cleanInput($_POST['course_name']);
    $description = cleanInput($_POST['course_description']);
    $strand_id = (int)$_POST['strand_id'];
    $grade_level = cleanInput($_POST['grade_level']);
    $units = (int)$_POST['units'];
    $status = cleanInput($_POST['course_status']);
    
    $errors = [];
    if (empty($code)) $errors[] = "Course code is required";
    if (empty($name)) $errors[] = "Course name is required";
    if (empty($grade_level)) $errors[] = "Grade level is required";
    
    if (empty($errors)) {
        if ($course_id > 0) {
            // Update existing course
            $stmt = $conn->prepare("UPDATE courses SET code = ?, name = ?, description = ?, strand_id = ?, grade_level = ?, units = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssisisi", $code, $name, $description, $strand_id, $grade_level, $units, $status, $course_id);
            if ($stmt->execute()) {
                logAction($_SESSION['user_id'], 'Updated course', "Course ID: $course_id, Name: $name");
                showAlert("Course updated successfully", "success");
            } else {
                showAlert("Error updating course: " . $stmt->error, "danger");
            }
        } else {
            // Insert new course
            $stmt = $conn->prepare("INSERT INTO courses (code, name, description, strand_id, grade_level, units, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssisss", $code, $name, $description, $strand_id, $grade_level, $units, $status);
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                logAction($_SESSION['user_id'], 'Added new course', "Course ID: $new_id, Name: $name");
                showAlert("Course added successfully", "success");
            } else {
                showAlert("Error adding course: " . $stmt->error, "danger");
            }
        }
    } else {
        foreach ($errors as $error) {
            showAlert($error, "danger");
        }
    }
}

// Delete strand if requested
if (isset($_GET['delete_strand']) && is_numeric($_GET['delete_strand'])) {
    $strand_id = (int)$_GET['delete_strand'];
    
    // Check if strand is in use
    $check = $conn->prepare("SELECT COUNT(*) as count FROM courses WHERE strand_id = ?");
    $check->bind_param("i", $strand_id);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        showAlert("Cannot delete strand because it is associated with " . $row['count'] . " course(s)", "danger");
    } else {
        $stmt = $conn->prepare("DELETE FROM strands WHERE id = ?");
        $stmt->bind_param("i", $strand_id);
        if ($stmt->execute()) {
            logAction($_SESSION['user_id'], 'Deleted strand', "Strand ID: $strand_id");
            showAlert("Strand deleted successfully", "success");
        } else {
            showAlert("Error deleting strand: " . $stmt->error, "danger");
        }
    }
}

// Delete course if requested
if (isset($_GET['delete_course']) && is_numeric($_GET['delete_course'])) {
    $course_id = (int)$_GET['delete_course'];
    
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    if ($stmt->execute()) {
        logAction($_SESSION['user_id'], 'Deleted course', "Course ID: $course_id");
        showAlert("Course deleted successfully", "success");
    } else {
        showAlert("Error deleting course: " . $stmt->error, "danger");
    }
}

// Get all strands
$strands_result = $conn->query("SELECT * FROM strands ORDER BY code");
$strands = [];
if ($strands_result) {
    while ($row = $strands_result->fetch_assoc()) {
        $strands[] = $row;
    }
}

// Get all courses with strand names
$courses_result = $conn->query("SELECT c.*, s.name as strand_name FROM courses c 
                              LEFT JOIN strands s ON c.strand_id = s.id
                              ORDER BY c.grade_level, c.code");
$courses = [];
if ($courses_result) {
    while ($row = $courses_result->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Grade levels array for dropdown
$grade_levels = ['K' => 'Kindergarten'];
for($i = 1; $i <= 12; $i++) {
    $grade_levels[$i] = 'Grade ' . $i;
}

// Tab management
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'strands';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $title ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?= $title ?></li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $active_tab == 'strands' ? 'active' : '' ?>" 
                       href="?tab=strands" role="tab">Academic Strands</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $active_tab == 'courses' ? 'active' : '' ?>" 
                       href="?tab=courses" role="tab">Courses</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Strands Tab -->
                <div class="tab-pane fade <?= $active_tab == 'strands' ? 'show active' : '' ?>">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Academic Strands</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#strandModal">
                            <i class="fas fa-plus me-1"></i> Add New Strand
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="strandTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Applicable Levels</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($strands as $strand): ?>
                                <tr>
                                    <td><?= htmlspecialchars($strand['code']) ?></td>
                                    <td><?= htmlspecialchars($strand['name']) ?></td>
                                    <td><?= htmlspecialchars($strand['description']) ?></td>
                                    <td>
                                        <?php 
                                        $levels = explode(',', $strand['applicable_levels']);
                                        $display_levels = [];
                                        foreach ($levels as $level) {
                                            $display_levels[] = $level == 'K' ? 'Kindergarten' : 'Grade ' . $level;
                                        }
                                        echo implode(', ', $display_levels);
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $strand['status'] == 'Active' ? 'success' : 'danger' ?>">
                                            <?= $strand['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-strand-btn" 
                                                data-id="<?= $strand['id'] ?>"
                                                data-code="<?= htmlspecialchars($strand['code']) ?>"
                                                data-name="<?= htmlspecialchars($strand['name']) ?>"
                                                data-description="<?= htmlspecialchars($strand['description']) ?>"
                                                data-levels="<?= $strand['applicable_levels'] ?>"
                                                data-status="<?= $strand['status'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete_strand=<?= $strand['id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this strand?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($strands) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No strands found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Courses Tab -->
                <div class="tab-pane fade <?= $active_tab == 'courses' ? 'show active' : '' ?>">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Courses</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal">
                            <i class="fas fa-plus me-1"></i> Add New Course
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="courseTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Grade Level</th>
                                    <th>Strand</th>
                                    <th>Units</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?= htmlspecialchars($course['code']) ?></td>
                                    <td><?= htmlspecialchars($course['name']) ?></td>
                                    <td><?= $course['grade_level'] == 'K' ? 'Kindergarten' : 'Grade ' . $course['grade_level'] ?></td>
                                    <td><?= htmlspecialchars($course['strand_name'] ?? 'N/A') ?></td>
                                    <td><?= $course['units'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $course['status'] == 'Active' ? 'success' : 'danger' ?>">
                                            <?= $course['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-course-btn" 
                                                data-id="<?= $course['id'] ?>"
                                                data-code="<?= htmlspecialchars($course['code']) ?>"
                                                data-name="<?= htmlspecialchars($course['name']) ?>"
                                                data-description="<?= htmlspecialchars($course['description']) ?>"
                                                data-strand-id="<?= $course['strand_id'] ?>"
                                                data-grade-level="<?= $course['grade_level'] ?>"
                                                data-units="<?= $course['units'] ?>"
                                                data-status="<?= $course['status'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?tab=courses&delete_course=<?= $course['id'] ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this course?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($courses) == 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No courses found</td>
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

<!-- Strand Modal -->
<div class="modal fade" id="strandModal" tabindex="-1" aria-labelledby="strandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="strandModalLabel">Add New Strand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="?tab=strands">
                <div class="modal-body">
                    <input type="hidden" name="strand_id" id="strand_id" value="0">
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="code">Strand Code *</label>
                                <input type="text" class="form-control" id="code" name="code" required maxlength="10">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="name">Strand Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required maxlength="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Applicable Grade Levels *</label>
                        <div class="row">
                            <?php foreach ($grade_levels as $key => $level): ?>
                            <div class="col-md-3 mb-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="applicable_levels[]" 
                                           value="<?= $key ?>" id="level_<?= $key ?>">
                                    <label class="form-check-label" for="level_<?= $key ?>">
                                        <?= $level ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="save_strand" class="btn btn-primary">Save Strand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1" aria-labelledby="courseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="courseModalLabel">Add New Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="?tab=courses">
                <div class="modal-body">
                    <input type="hidden" name="course_id" id="course_id" value="0">
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="course_code">Course Code *</label>
                                <input type="text" class="form-control" id="course_code" name="course_code" required maxlength="10">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="course_name">Course Name *</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" required maxlength="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="course_description">Description</label>
                        <textarea class="form-control" id="course_description" name="course_description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="grade_level">Grade Level *</label>
                                <select class="form-select" id="grade_level" name="grade_level" required>
                                    <option value="">Select Grade Level</option>
                                    <?php foreach ($grade_levels as $key => $level): ?>
                                    <option value="<?= $key ?>"><?= $level ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="strand_id">Strand</label>
                                <select class="form-select" id="strand_id" name="strand_id">
                                    <option value="0">None</option>
                                    <?php foreach ($strands as $strand): ?>
                                    <option value="<?= $strand['id'] ?>"><?= htmlspecialchars($strand['name']) ?> (<?= $strand['code'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="units">Units</label>
                                <input type="number" class="form-control" id="units" name="units" min="1" max="10" value="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_status">Status</label>
                        <select class="form-select" id="course_status" name="course_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="save_course" class="btn btn-primary">Save Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables only if not already initialized
    if (!$.fn.DataTable.isDataTable('#strandTable')) {
        $('#strandTable').DataTable({
            responsive: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
    }
    
    if (!$.fn.DataTable.isDataTable('#courseTable')) {
        $('#courseTable').DataTable({
            responsive: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
        });
    }
    
    // Strand Edit Button Click
    $('.edit-strand-btn').on('click', function() {
        $('#edit_strand_id').val($(this).data('id'));
        $('#edit_strand_name').val($(this).data('name'));
        $('#edit_strand_code').val($(this).data('code'));
        $('#edit_strand_description').val($(this).data('description'));
        $('#edit_strand_status').val($(this).data('status'));
    });
    
    // Strand Delete Button Click
    $('.delete-strand-btn').on('click', function() {
        $('#delete_strand_id').val($(this).data('id'));
        $('#delete_strand_name_display').text($(this).data('name'));
    });
    
    // Course Edit Button Click
    $('.edit-course-btn').on('click', function() {
        $('#edit_course_id').val($(this).data('id'));
        $('#edit_course_name').val($(this).data('name'));
        $('#edit_course_code').val($(this).data('code'));
        $('#edit_course_description').val($(this).data('description'));
        $('#edit_course_units').val($(this).data('units'));
        $('#edit_course_strand').val($(this).data('strand'));
        $('#edit_course_year_level').val($(this).data('year-level'));
        $('#edit_course_semester').val($(this).data('semester'));
        $('#edit_course_status').val($(this).data('status'));
    });
    
    // Course Delete Button Click
    $('.delete-course-btn').on('click', function() {
        $('#delete_course_id').val($(this).data('id'));
        $('#delete_course_name_display').text($(this).data('name'));
        $('#delete_course_code_display').text($(this).data('code'));
    });
});
</script>

<?php
$conn->close();
require_once '../../includes/footer.php';
?> 
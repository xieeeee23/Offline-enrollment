<?php
$page = 'sections';
$title = 'Sections';
require_once '../../includes/header.php';
checkAccess(['admin', 'registrar']);

$conn = getConnection();

// Create sections table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    capacity INT NOT NULL DEFAULT 40,
    adviser_id INT,
    room VARCHAR(50),
    school_year VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (adviser_id) REFERENCES teachers(id) ON DELETE SET NULL
)";
$conn->query($create_table_sql);

// Check if description column exists and add it if missing
$check_description_column = $conn->query("SHOW COLUMNS FROM sections LIKE 'description'");
if ($check_description_column->num_rows == 0) {
    $conn->query("ALTER TABLE sections ADD COLUMN description TEXT AFTER room");
}

// Check if status column exists and add it if missing
$check_status_column = $conn->query("SHOW COLUMNS FROM sections LIKE 'status'");
if ($check_status_column->num_rows == 0) {
    $conn->query("ALTER TABLE sections ADD COLUMN status ENUM('Active', 'Inactive') DEFAULT 'Active' AFTER school_year");
}

// Check if teachers table exists
$check_teachers = $conn->query("SHOW TABLES LIKE 'teachers'");
if ($check_teachers->num_rows == 0) {
    // Create teachers table if it doesn't exist
    $create_teachers_sql = "
    CREATE TABLE IF NOT EXISTS teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50),
        gender ENUM('Male', 'Female', 'Other') NOT NULL,
        date_of_birth DATE,
        address TEXT,
        contact_number VARCHAR(20),
        email VARCHAR(100),
        specialty VARCHAR(100),
        qualification TEXT,
        hire_date DATE,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $conn->query($create_teachers_sql);
}

// Insert default sections if table is empty
$check_empty = $conn->query("SELECT COUNT(*) as count FROM sections");
$row = $check_empty->fetch_assoc();
if ($row['count'] == 0) {
    $current_year = date('Y');
    $school_year = $current_year . '-' . ($current_year + 1);
    
    $conn->query("INSERT INTO sections (name, grade_level, capacity, room, school_year) VALUES
        ('Sampaguita', 'K', 30, 'K-1', '$school_year'),
        ('Sampaguita', '1', 40, 'E-1A', '$school_year'),
        ('Rosal', '1', 40, 'E-1B', '$school_year'),
        ('Sampaguita', '7', 45, 'JHS-1A', '$school_year'),
        ('Rosal', '7', 45, 'JHS-1B', '$school_year'),
        ('Sampaguita', '11', 40, 'SHS-1A', '$school_year')");
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new section
    if (isset($_POST['add'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $grade_level = $conn->real_escape_string($_POST['grade_level']);
        $capacity = (int)$_POST['capacity'];
        $adviser_id = !empty($_POST['adviser_id']) ? (int)$_POST['adviser_id'] : null;
        $room = $conn->real_escape_string($_POST['room']);
        $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : "";
        $school_year = $conn->real_escape_string($_POST['school_year']);
        $status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : "Active";
        
        // Prepare section data for insertion
        $section_data = [
            'name' => $name,
            'grade_level' => $grade_level,
            'capacity' => $capacity,
            'adviser_id' => $adviser_id,
            'room' => $room,
            'school_year' => $school_year,
            'status' => $status
        ];
        
        // Add description if it exists in the database
        $descriptionExists = false;
        $result = $conn->query("SHOW COLUMNS FROM sections LIKE 'description'");
        if ($result && $result->num_rows > 0) {
            $section_data['description'] = $description;
        }
        
        // Use safeInsert to prevent duplicates
        $result = safeInsert('sections', $section_data, [
            'unique_fields' => ['name', 'grade_level', 'school_year'],
            'entity_name' => 'section',
            'log_action' => true
        ]);
        
        if ($result['success']) {
            showAlert('Section added successfully', 'success');
        } else {
            if (isset($result['duplicate']) && $result['duplicate']) {
                showAlert($result['message'], 'warning');
            } else {
                showAlert('Error adding section: ' . $result['message'], 'danger');
            }
        }
    }
    
    // Update section
    if (isset($_POST['update'])) {
        $id = (int)$_POST['edit_id'];
        $name = $conn->real_escape_string($_POST['edit_name']);
        $grade_level = $conn->real_escape_string($_POST['edit_grade_level']);
        $capacity = (int)$_POST['edit_capacity'];
        $adviser_id = !empty($_POST['edit_adviser_id']) ? (int)$_POST['edit_adviser_id'] : null;
        $room = $conn->real_escape_string($_POST['edit_room']);
        $description = isset($_POST['edit_description']) ? $conn->real_escape_string($_POST['edit_description']) : "";
        $school_year = $conn->real_escape_string($_POST['edit_school_year']);
        $status = isset($_POST['edit_status']) ? $conn->real_escape_string($_POST['edit_status']) : "Active";
        
        // Prepare section data for update
        $section_data = [
            'name' => $name,
            'grade_level' => $grade_level,
            'capacity' => $capacity,
            'adviser_id' => $adviser_id,
            'room' => $room,
            'school_year' => $school_year,
            'status' => $status
        ];
        
        // Add description if it exists in the database
        $descriptionExists = false;
        $result = $conn->query("SHOW COLUMNS FROM sections LIKE 'description'");
        if ($result && $result->num_rows > 0) {
            $section_data['description'] = $description;
        }
        
        // Use safeUpdate to prevent duplicates
        $result = safeUpdate('sections', $section_data, $id, [
            'unique_fields' => ['name', 'grade_level', 'school_year'],
            'entity_name' => 'section',
            'log_action' => true
        ]);
        
        if ($result['success']) {
            showAlert('Section updated successfully', 'success');
        } else {
            if (isset($result['duplicate']) && $result['duplicate']) {
                showAlert($result['message'], 'warning');
            } else {
                showAlert('Error updating section: ' . $result['message'], 'danger');
            }
        }
    }
    
    // Delete section
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete_id'];
        
        // Check if there are students assigned to this section
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE section = ? AND grade_level = (SELECT grade_level FROM sections WHERE id = ?)";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $id, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['count'] > 0) {
            showAlert('Cannot delete section as it has ' . $check_row['count'] . ' students assigned to it', 'danger');
        } else {
            $sql = "DELETE FROM sections WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                logAction($_SESSION['user_id'], 'Deleted section', 'Deleted section ID: ' . $id);
                showAlert('Section deleted successfully', 'success');
            } else {
                showAlert('Error deleting section: ' . $conn->error, 'danger');
            }
            $stmt->close();
        }
    }
}

// Get all sections with teacher names
$sql = "SELECT s.id, s.name, s.grade_level, s.capacity, s.adviser_id, s.room, s.school_year, CONCAT(t.first_name, ' ', t.last_name) as adviser_name 
        FROM sections s 
        LEFT JOIN teachers t ON s.adviser_id = t.id 
        ORDER BY s.school_year DESC, s.grade_level, s.name";
$result = $conn->query($sql);

// Create an array to store all section data
$sections = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Set default values for possibly missing columns
        $row['status'] = 'Active'; // Default status
        $row['description'] = ''; // Default empty description
        $sections[] = $row;
    }
}

// Get all active teachers for the dropdown
$teachers_sql = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM teachers WHERE status = 'Active' ORDER BY last_name, first_name";
$teachers_result = $conn->query($teachers_sql);
$teachers = [];
while ($teacher = $teachers_result->fetch_assoc()) {
    $teachers[] = $teacher;
}

// Get current school year for default value
$current_year = date('Y');
$current_month = date('n');
// If current month is after June, use current year, otherwise use previous year
$default_school_year = ($current_month >= 6) ? $current_year . '-' . ($current_year + 1) : ($current_year - 1) . '-' . $current_year;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $title ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?= $title ?></li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Manage Sections
            <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                <i class="fas fa-plus"></i> Add Section
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="sectionsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Grade Level</th>
                            <th>Capacity</th>
                            <th>Adviser</th>
                            <th>Room</th>
                            <th>School Year</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td>
                                <?php 
                                if ($row['grade_level'] == 'K') {
                                    echo 'Kindergarten';
                                } else {
                                    echo 'Grade ' . $row['grade_level'];
                                }
                                ?>
                            </td>
                            <td><?= $row['capacity'] ?></td>
                            <td><?= htmlspecialchars($row['adviser_name'] ?? 'Not Assigned') ?></td>
                            <td><?= htmlspecialchars($row['room'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['school_year']) ?></td>
                            <td>
                                <span class="badge bg-<?= (isset($row['status']) && $row['status'] == 'Active') ? 'success' : 'danger' ?>">
                                    <?= isset($row['status']) ? $row['status'] : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-grade-level="<?= htmlspecialchars($row['grade_level']) ?>"
                                        data-capacity="<?= $row['capacity'] ?>"
                                        data-adviser-id="<?= $row['adviser_id'] ?>"
                                        data-room="<?= htmlspecialchars($row['room'] ?? '') ?>"
                                        data-description="<?= htmlspecialchars(isset($row['description']) ? $row['description'] : '') ?>"
                                        data-school-year="<?= htmlspecialchars($row['school_year']) ?>"
                                        data-status="<?= htmlspecialchars(isset($row['status']) ? $row['status'] : 'Active') ?>"
                                        data-bs-toggle="modal" data-bs-target="#editSectionModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-btn"
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-grade-level="<?= htmlspecialchars($row['grade_level']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#deleteSectionModal">
                                    <i class="fas fa-trash"></i>
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

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSectionModalLabel">Add New Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Section Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="grade_level" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="grade_level" name="grade_level" required>
                                <option value="">Select Grade Level</option>
                                <option value="K">Kindergarten</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>"><?= 'Grade ' . $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" value="40">
                        </div>
                        <div class="col-md-6">
                            <label for="adviser_id" class="form-label">Adviser</label>
                            <select class="form-select" id="adviser_id" name="adviser_id">
                                <option value="">Select Adviser</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="room" class="form-label">Room</label>
                            <input type="text" class="form-control" id="room" name="room">
                        </div>
                        <div class="col-md-6">
                            <label for="school_year" class="form-label">School Year <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="school_year" name="school_year" required value="<?= $default_school_year ?>" placeholder="e.g., 2023-2024">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Add Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Section Modal -->
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSectionModalLabel">Edit Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_id" name="edit_id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label">Section Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_grade_level" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_grade_level" name="edit_grade_level" required>
                                <option value="">Select Grade Level</option>
                                <option value="K">Kindergarten</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>"><?= 'Grade ' . $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="edit_capacity" name="edit_capacity" min="1">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_adviser_id" class="form-label">Adviser</label>
                            <select class="form-select" id="edit_adviser_id" name="edit_adviser_id">
                                <option value="">Select Adviser</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_room" class="form-label">Room</label>
                            <input type="text" class="form-control" id="edit_room" name="edit_room">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_school_year" class="form-label">School Year <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_school_year" name="edit_school_year" required placeholder="e.g., 2023-2024">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="edit_status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-primary">Update Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Section Modal -->
<div class="modal fade" id="deleteSectionModal" tabindex="-1" aria-labelledby="deleteSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteSectionModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" id="delete_id" name="delete_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete the section: <span id="delete_section_name"></span> for <span id="delete_grade_level"></span>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable only if not already initialized
        if (!$.fn.DataTable.isDataTable('#sectionsTable')) {
            $('#sectionsTable').DataTable({
                order: [[5, 'desc'], [1, 'asc'], [0, 'asc']], // Sort by school year (desc), grade level, and section name
                responsive: true
            });
        }
        
        // Handle edit button clicks
        $('.edit-btn').click(function() {
            $('#edit_id').val($(this).data('id'));
            $('#edit_name').val($(this).data('name'));
            $('#edit_grade_level').val($(this).data('grade-level'));
            $('#edit_capacity').val($(this).data('capacity'));
            $('#edit_adviser_id').val($(this).data('adviser-id'));
            $('#edit_room').val($(this).data('room'));
            $('#edit_description').val($(this).data('description'));
            $('#edit_school_year').val($(this).data('school-year'));
            $('#edit_status').val($(this).data('status'));
        });
        
        // Handle delete button clicks
        $('.delete-btn').click(function() {
            $('#delete_id').val($(this).data('id'));
            $('#delete_section_name').text($(this).data('name'));
            
            let gradeLevel = $(this).data('grade-level');
            if (gradeLevel === 'K') {
                gradeLevel = 'Kindergarten';
            } else {
                gradeLevel = 'Grade ' + gradeLevel;
            }
            $('#delete_grade_level').text(gradeLevel);
        });
    });
</script>

<?php require_once '../../includes/footer.php'; ?> 
<?php
$page = 'education_levels';
$title = 'Education Levels';
require_once '../../includes/header.php';
checkAccess(['admin', 'registrar']);

$conn = getConnection();

// Create table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS education_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    grade_min INT,
    grade_max INT,
    age_min INT,
    age_max INT,
    display_order INT NOT NULL DEFAULT 0,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Insert default values if table is empty
$check_empty = $conn->query("SELECT COUNT(*) as count FROM education_levels");
$row = $check_empty->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO education_levels (level_name, description, grade_min, grade_max, age_min, age_max, display_order) VALUES
        ('Kindergarten', 'Early childhood education before elementary school', 0, 0, 5, 6, 1),
        ('Elementary', 'Primary education from grades 1 to 6', 1, 6, 6, 12, 2),
        ('Junior High School', 'Secondary education from grades 7 to 10', 7, 10, 12, 16, 3),
        ('Senior High School', 'Upper secondary education from grades 11 to 12', 11, 12, 16, 18, 4)");
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new education level
    if (isset($_POST['add'])) {
        $level_name = cleanInput($_POST['level_name']);
        $description = cleanInput($_POST['description']);
        $grade_min = (int)$_POST['grade_min'];
        $grade_max = (int)$_POST['grade_max'];
        $age_min = (int)$_POST['age_min'];
        $age_max = (int)$_POST['age_max'];
        $display_order = (int)$_POST['display_order'];
        $status = cleanInput($_POST['status']);
        
        // Validate input
        if (empty($level_name)) {
            showAlert('Level name is required', 'danger');
        } elseif ($grade_max < $grade_min) {
            showAlert('Maximum grade cannot be less than minimum grade', 'danger');
        } elseif ($age_max < $age_min) {
            showAlert('Maximum age cannot be less than minimum age', 'danger');
        } else {
            // Check if level name already exists
            if (!valueExists('education_levels', 'level_name', $level_name)) {
                $sql = "INSERT INTO education_levels (level_name, description, grade_min, grade_max, age_min, age_max, display_order, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiiiiss", $level_name, $description, $grade_min, $grade_max, $age_min, $age_max, $display_order, $status);
                
                if ($stmt->execute()) {
                    logAction($_SESSION['user_id'], 'Added new education level', 'Added education level: ' . $level_name);
                    showAlert('Education level added successfully', 'success');
                } else {
                    showAlert('Error adding education level: ' . $conn->error, 'danger');
                }
                $stmt->close();
            } else {
                showAlert('Education level name already exists', 'danger');
            }
        }
    }
    
    // Update education level
    if (isset($_POST['update'])) {
        $id = (int)$_POST['edit_id'];
        $level_name = cleanInput($_POST['edit_level_name']);
        $description = cleanInput($_POST['edit_description']);
        $grade_min = (int)$_POST['edit_grade_min'];
        $grade_max = (int)$_POST['edit_grade_max'];
        $age_min = (int)$_POST['edit_age_min'];
        $age_max = (int)$_POST['edit_age_max'];
        $display_order = (int)$_POST['edit_display_order'];
        $status = cleanInput($_POST['edit_status']);
        
        // Validate input
        if (empty($level_name)) {
            showAlert('Level name is required', 'danger');
        } elseif ($grade_max < $grade_min) {
            showAlert('Maximum grade cannot be less than minimum grade', 'danger');
        } elseif ($age_max < $age_min) {
            showAlert('Maximum age cannot be less than minimum age', 'danger');
        } else {
            // Check if level name already exists for other records
            if (!valueExists('education_levels', 'level_name', $level_name, $id)) {
                $sql = "UPDATE education_levels 
                        SET level_name = ?, description = ?, grade_min = ?, grade_max = ?, 
                            age_min = ?, age_max = ?, display_order = ?, status = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiiiiisi", $level_name, $description, $grade_min, $grade_max, $age_min, $age_max, $display_order, $status, $id);
                
                if ($stmt->execute()) {
                    logAction($_SESSION['user_id'], 'Updated education level', 'Updated education level ID: ' . $id);
                    showAlert('Education level updated successfully', 'success');
                } else {
                    showAlert('Error updating education level: ' . $conn->error, 'danger');
                }
                $stmt->close();
            } else {
                showAlert('Education level name already exists', 'danger');
            }
        }
    }
    
    // Delete education level
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete_id'];
        
        // Check if there are students using this education level
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE education_level_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['count'] > 0) {
            showAlert('Cannot delete education level as it is being used by ' . $check_row['count'] . ' students', 'danger');
        } else {
            $sql = "DELETE FROM education_levels WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                logAction($_SESSION['user_id'], 'Deleted education level', 'Deleted education level ID: ' . $id);
                showAlert('Education level deleted successfully', 'success');
            } else {
                showAlert('Error deleting education level: ' . $conn->error, 'danger');
            }
            $stmt->close();
        }
    }
}

// Get all education levels
$sql = "SELECT * FROM education_levels ORDER BY display_order";
$result = $conn->query($sql);
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
            Manage Education Levels
            <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addLevelModal">
                <i class="fas fa-plus"></i> Add Education Level
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="educationLevelsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Level Name</th>
                            <th>Description</th>
                            <th>Grade Range</th>
                            <th>Age Range</th>
                            <th>Display Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['level_name']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td>
                                <?php 
                                if ($row['grade_min'] == 0 && $row['grade_max'] == 0) {
                                    echo 'K';
                                } else {
                                    echo "Grade " . $row['grade_min'] . " - " . $row['grade_max'];
                                }
                                ?>
                            </td>
                            <td><?= $row['age_min'] ?> - <?= $row['age_max'] ?> years old</td>
                            <td><?= $row['display_order'] ?></td>
                            <td>
                                <span class="badge <?= ($row['status'] == 'Active') ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                    data-id="<?= $row['id'] ?>"
                                    data-level-name="<?= htmlspecialchars($row['level_name']) ?>"
                                    data-description="<?= htmlspecialchars($row['description']) ?>"
                                    data-grade-min="<?= $row['grade_min'] ?>"
                                    data-grade-max="<?= $row['grade_max'] ?>"
                                    data-age-min="<?= $row['age_min'] ?>"
                                    data-age-max="<?= $row['age_max'] ?>"
                                    data-display-order="<?= $row['display_order'] ?>"
                                    data-status="<?= $row['status'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                    data-id="<?= $row['id'] ?>"
                                    data-level-name="<?= htmlspecialchars($row['level_name']) ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Education Level Modal -->
<div class="modal fade" id="addLevelModal" tabindex="-1" aria-labelledby="addLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLevelModalLabel">Add Education Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="level_name" class="form-label">Level Name *</label>
                        <input type="text" class="form-control" id="level_name" name="level_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="grade_min" class="form-label">Minimum Grade</label>
                            <input type="number" class="form-control" id="grade_min" name="grade_min" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label for="grade_max" class="form-label">Maximum Grade</label>
                            <input type="number" class="form-control" id="grade_max" name="grade_max" min="0" value="0">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="age_min" class="form-label">Minimum Age</label>
                            <input type="number" class="form-control" id="age_min" name="age_min" min="3" value="5">
                        </div>
                        <div class="col-md-6">
                            <label for="age_max" class="form-label">Maximum Age</label>
                            <input type="number" class="form-control" id="age_max" name="age_max" min="3" value="6">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Education Level Modal -->
<div class="modal fade" id="editLevelModal" tabindex="-1" aria-labelledby="editLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLevelModalLabel">Edit Education Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="mb-3">
                        <label for="edit_level_name" class="form-label">Level Name *</label>
                        <input type="text" class="form-control" id="edit_level_name" name="edit_level_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="edit_description" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_grade_min" class="form-label">Minimum Grade</label>
                            <input type="number" class="form-control" id="edit_grade_min" name="edit_grade_min" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_grade_max" class="form-label">Maximum Grade</label>
                            <input type="number" class="form-control" id="edit_grade_max" name="edit_grade_max" min="0">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_age_min" class="form-label">Minimum Age</label>
                            <input type="number" class="form-control" id="edit_age_min" name="edit_age_min" min="3">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_age_max" class="form-label">Maximum Age</label>
                            <input type="number" class="form-control" id="edit_age_max" name="edit_age_max" min="3">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="edit_display_order" name="edit_display_order" min="1">
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="edit_status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Education Level Modal -->
<div class="modal fade" id="deleteLevelModal" tabindex="-1" aria-labelledby="deleteLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteLevelModalLabel">Delete Education Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" id="delete_id" name="delete_id">
                    <p>Are you sure you want to delete the education level <strong id="delete_level_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone and will affect any associated records.</p>
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
        // Initialize DataTable
        $('#educationLevelsTable').DataTable({
            responsive: true
        });
        
        // Set up edit buttons
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_level_name').value = this.dataset.levelName;
                document.getElementById('edit_description').value = this.dataset.description;
                document.getElementById('edit_grade_min').value = this.dataset.gradeMin;
                document.getElementById('edit_grade_max').value = this.dataset.gradeMax;
                document.getElementById('edit_age_min').value = this.dataset.ageMin;
                document.getElementById('edit_age_max').value = this.dataset.ageMax;
                document.getElementById('edit_display_order').value = this.dataset.displayOrder;
                document.getElementById('edit_status').value = this.dataset.status;
                
                var editModal = new bootstrap.Modal(document.getElementById('editLevelModal'));
                editModal.show();
            });
        });
        
        // Set up delete buttons
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('delete_id').value = this.dataset.id;
                document.getElementById('delete_level_name').textContent = this.dataset.levelName;
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteLevelModal'));
                deleteModal.show();
            });
        });
    });
</script>

<?php
$conn->close();
require_once '../../includes/footer.php';
?> 
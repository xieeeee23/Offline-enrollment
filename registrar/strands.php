<?php
$page = 'strands';
$title = 'Senior High School Strands';
require_once '../../includes/header.php';
checkAccess(['admin', 'registrar']);

// Add cache control headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$conn = getConnection();

// Create table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS shs_strands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    track_name VARCHAR(100) NOT NULL,
    strand_code VARCHAR(20) NOT NULL,
    strand_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Insert default values if table is empty
$check_empty = $conn->query("SELECT COUNT(*) as count FROM shs_strands");
$row = $check_empty->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO shs_strands (track_name, strand_code, strand_name, description) VALUES
        ('Academic', 'ABM', 'Accountancy, Business and Management', 'Focus on business-related fields and financial management'),
        ('Academic', 'STEM', 'Science, Technology, Engineering, and Mathematics', 'Focus on science and math-related fields'),
        ('Academic', 'HUMSS', 'Humanities and Social Sciences', 'Focus on literature, philosophy, social sciences'),
        ('Academic', 'GAS', 'General Academic Strand', 'General subjects for undecided students'),
        ('Technical-Vocational-Livelihood', 'TVL-HE', 'Home Economics', 'Skills related to household management'),
        ('Technical-Vocational-Livelihood', 'TVL-ICT', 'Information and Communications Technology', 'Computer and tech-related skills'),
        ('Technical-Vocational-Livelihood', 'TVL-IA', 'Industrial Arts', 'Skills related to manufacturing and production'),
        ('Sports', 'Sports', 'Sports Track', 'Focus on physical education and sports development'),
        ('Arts and Design', 'Arts', 'Arts and Design Track', 'Focus on visual and performing arts')");
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new strand
    if (isset($_POST['add'])) {
        $track_name = cleanInput($_POST['track_name']);
        $strand_code = cleanInput($_POST['strand_code']);
        $strand_name = cleanInput($_POST['strand_name']);
        $description = cleanInput($_POST['description']);
        $status = cleanInput($_POST['status']);
        
        // Validate input
        $errors = [];
        
        if (empty($track_name)) {
            $errors[] = "Track name is required";
        }
        
        if (empty($strand_code)) {
            $errors[] = "Strand code is required";
        } elseif (!preg_match('/^[A-Za-z0-9\-]+$/', $strand_code)) {
            $errors[] = "Strand code should only contain letters, numbers, and hyphens";
        }
        
        if (empty($strand_name)) {
            $errors[] = "Strand name is required";
        }
        
        if (empty($errors)) {
            // Prepare strand data for insertion
            $strand_data = [
                'track_name' => $track_name,
                'strand_code' => $strand_code,
                'strand_name' => $strand_name,
                'description' => $description,
                'status' => $status
            ];
            
            // Use safeInsert to prevent duplicates
            $result = safeInsert('shs_strands', $strand_data, [
                'unique_fields' => ['strand_code', 'strand_name'],
                'entity_name' => 'strand',
                'log_action' => true
            ]);
            
            if ($result['success']) {
                $_SESSION['alert'] = showAlert('Strand added successfully', 'success');
                echo "<script>window.location.href = 'strands.php?cache_bust=" . time() . "';</script>";
                exit;
            } else {
                if (isset($result['duplicate']) && $result['duplicate']) {
                    showAlert($result['message'], 'warning');
                } else {
                    showAlert('Error adding strand: ' . $result['message'], 'danger');
                }
            }
        } else {
            showAlert('Please fix the following errors: ' . implode(', ', $errors), 'danger');
        }
    }
    
    // Update strand
    if (isset($_POST['update'])) {
        $id = (int)$_POST['edit_id'];
        $track_name = cleanInput($_POST['edit_track_name']);
        $strand_code = cleanInput($_POST['edit_strand_code']);
        $strand_name = cleanInput($_POST['edit_strand_name']);
        $description = cleanInput($_POST['edit_description']);
        $status = cleanInput($_POST['edit_status']);
        
        // Validate input
        $errors = [];
        
        if (empty($track_name)) {
            $errors[] = "Track name is required";
        }
        
        if (empty($strand_code)) {
            $errors[] = "Strand code is required";
        } elseif (!preg_match('/^[A-Za-z0-9\-]+$/', $strand_code)) {
            $errors[] = "Strand code should only contain letters, numbers, and hyphens";
        }
        
        if (empty($strand_name)) {
            $errors[] = "Strand name is required";
        }
        
        if (empty($errors)) {
            // Prepare strand data for update
            $strand_data = [
                'track_name' => $track_name,
                'strand_code' => $strand_code,
                'strand_name' => $strand_name,
                'description' => $description,
                'status' => $status
            ];
            
            // Use safeUpdate to prevent duplicates
            $result = safeUpdate('shs_strands', $strand_data, $id, [
                'unique_fields' => ['strand_code', 'strand_name'],
                'entity_name' => 'strand',
                'log_action' => true
            ]);
            
            if ($result['success']) {
                $_SESSION['alert'] = showAlert('Strand updated successfully', 'success');
                echo "<script>window.location.href = 'strands.php?cache_bust=" . time() . "';</script>";
                exit;
            } else {
                if (isset($result['duplicate']) && $result['duplicate']) {
                    showAlert($result['message'], 'warning');
                } else {
                    showAlert('Error updating strand: ' . $result['message'], 'danger');
                }
            }
        } else {
            showAlert('Please fix the following errors: ' . implode(', ', $errors), 'danger');
        }
    }
    
    // Delete strand
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete_id'];
        
        // Check if there are students using this strand
        $check_sql = "SELECT COUNT(*) as count FROM senior_highschool_details WHERE strand = (SELECT strand_code FROM shs_strands WHERE id = ?)";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['count'] > 0) {
            showAlert('Cannot delete strand as it is being used by ' . $check_row['count'] . ' students', 'danger');
        } else {
            // Get strand info for logging
            $info_sql = "SELECT strand_code, strand_name FROM shs_strands WHERE id = ?";
            $info_stmt = $conn->prepare($info_sql);
            $info_stmt->bind_param("i", $id);
            $info_stmt->execute();
            $info_result = $info_stmt->get_result();
            $strand_info = $info_result->fetch_assoc();
            $info_stmt->close();
            
            $sql = "DELETE FROM shs_strands WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $strand_code = $strand_info && isset($strand_info['strand_code']) ? $strand_info['strand_code'] : 'Unknown';
                $strand_name = $strand_info && isset($strand_info['strand_name']) ? $strand_info['strand_name'] : 'Unknown';
                logAction($_SESSION['user_id'], 'Deleted SHS strand', 'Deleted strand: ' . $strand_code . ' - ' . $strand_name);
                $_SESSION['alert'] = showAlert('Strand deleted successfully', 'success');
                echo "<script>window.location.href = 'strands.php?cache_bust=" . time() . "';</script>";
                exit;
            } else {
                showAlert('Error deleting strand: ' . $conn->error, 'danger');
            }
            $stmt->close();
        }
    }
    
    // Clean up any invalid strands
    $cleanup_sql = "DELETE FROM shs_strands WHERE 
                   strand_code REGEXP '^[0-9]+$' OR 
                   strand_name REGEXP '^[0-9]+$' OR 
                   LENGTH(strand_code) < 2 OR 
                   LENGTH(strand_name) < 3";
    $conn->query($cleanup_sql);
}

// Force refresh of data
$cache_bust = isset($_GET['cache_bust']) ? $_GET['cache_bust'] : '';

// Get all strands with a SQL comment to prevent caching
$sql = "SELECT * FROM shs_strands ORDER BY track_name, strand_code /* cache_bust: " . time() . " */";
$result = $conn->query($sql);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $title ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?= $title ?></li>
    </ol>
    
    <?php 
    if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    }
    ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Manage SHS Strands
            <div class="float-end">
                <button type="button" class="btn btn-secondary me-2" id="refreshButton">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStrandModal">
                <i class="fas fa-plus"></i> Add Strand
            </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="strandsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Track</th>
                            <th>Strand Code</th>
                            <th>Strand Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['track_name']) ?></td>
                            <td><?= htmlspecialchars($row['strand_code']) ?></td>
                            <td><?= htmlspecialchars($row['strand_name']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td>
                                <span class="badge <?= ($row['status'] == 'Active') ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                    data-id="<?= $row['id'] ?>"
                                    data-track-name="<?= htmlspecialchars($row['track_name']) ?>"
                                    data-strand-code="<?= htmlspecialchars($row['strand_code']) ?>"
                                    data-strand-name="<?= htmlspecialchars($row['strand_name']) ?>"
                                    data-description="<?= htmlspecialchars($row['description']) ?>"
                                    data-status="<?= $row['status'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                    data-id="<?= $row['id'] ?>"
                                    data-strand-code="<?= htmlspecialchars($row['strand_code']) ?>"
                                    data-strand-name="<?= htmlspecialchars($row['strand_name']) ?>">
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

<!-- Add Strand Modal -->
<div class="modal fade" id="addStrandModal" tabindex="-1" aria-labelledby="addStrandModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStrandModalLabel">Add SHS Strand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="addStrandForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="track_name" class="form-label">Track Name *</label>
                        <select class="form-select" id="track_name" name="track_name" required>
                            <option value="">Select Track</option>
                            <option value="Academic">Academic</option>
                            <option value="Technical-Vocational-Livelihood">Technical-Vocational-Livelihood</option>
                            <option value="Sports">Sports</option>
                            <option value="Arts and Design">Arts and Design</option>
                        </select>
                        <div class="invalid-feedback">Please select a track</div>
                    </div>
                    <div class="mb-3">
                        <label for="strand_code" class="form-label">Strand Code *</label>
                        <input type="text" class="form-control" id="strand_code" name="strand_code" required 
                               pattern="[A-Za-z0-9\-]+" title="Only letters, numbers, and hyphens are allowed">
                        <div class="invalid-feedback">Please enter a valid strand code (letters, numbers, and hyphens only)</div>
                    </div>
                    <div class="mb-3">
                        <label for="strand_name" class="form-label">Strand Name *</label>
                        <input type="text" class="form-control" id="strand_name" name="strand_name" required>
                        <div class="invalid-feedback">Please enter a strand name</div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
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

<!-- Edit Strand Modal -->
<div class="modal fade" id="editStrandModal" tabindex="-1" aria-labelledby="editStrandModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStrandModalLabel">Edit SHS Strand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="editStrandForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="mb-3">
                        <label for="edit_track_name" class="form-label">Track Name *</label>
                        <select class="form-select" id="edit_track_name" name="edit_track_name" required>
                            <option value="">Select Track</option>
                            <option value="Academic">Academic</option>
                            <option value="Technical-Vocational-Livelihood">Technical-Vocational-Livelihood</option>
                            <option value="Sports">Sports</option>
                            <option value="Arts and Design">Arts and Design</option>
                        </select>
                        <div class="invalid-feedback">Please select a track</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_strand_code" class="form-label">Strand Code *</label>
                        <input type="text" class="form-control" id="edit_strand_code" name="edit_strand_code" required
                               pattern="[A-Za-z0-9\-]+" title="Only letters, numbers, and hyphens are allowed">
                        <div class="invalid-feedback">Please enter a valid strand code (letters, numbers, and hyphens only)</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_strand_name" class="form-label">Strand Name *</label>
                        <input type="text" class="form-control" id="edit_strand_name" name="edit_strand_name" required>
                        <div class="invalid-feedback">Please enter a strand name</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="edit_description" rows="3"></textarea>
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

<!-- Delete Strand Modal -->
<div class="modal fade" id="deleteStrandModal" tabindex="-1" aria-labelledby="deleteStrandModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteStrandModalLabel">Delete SHS Strand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="deleteStrandForm">
                <div class="modal-body">
                    <input type="hidden" id="delete_id" name="delete_id">
                    <p>Are you sure you want to delete the strand <strong id="delete_strand_code"></strong> - <strong id="delete_strand_name"></strong>?</p>
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
        // Destroy existing DataTable instance if it exists
        if ($.fn.DataTable.isDataTable('#strandsTable')) {
            $('#strandsTable').DataTable().destroy();
        }
        
        // Initialize DataTable with refresh handling
        let table = $('#strandsTable').DataTable({
            responsive: true,
            order: [[0, 'asc'], [1, 'asc']] // Sort by Track then Strand Code
        });
        
        // Add refresh button functionality
        document.getElementById('refreshButton').addEventListener('click', function() {
            window.location.href = 'strands.php?cache_bust=' + new Date().getTime();
        });
        
        // Client-side form validation for Add Strand form
        const addStrandForm = document.getElementById('addStrandForm');
        if (addStrandForm) {
            addStrandForm.addEventListener('submit', function(event) {
                if (!addStrandForm.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                addStrandForm.classList.add('was-validated');
            });
        }
        
        // Client-side form validation for Edit Strand form
        const editStrandForm = document.getElementById('editStrandForm');
        if (editStrandForm) {
            editStrandForm.addEventListener('submit', function(event) {
                if (!editStrandForm.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                editStrandForm.classList.add('was-validated');
            });
        }
        
        // Set up edit buttons
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const trackName = this.getAttribute('data-track-name');
                const strandCode = this.getAttribute('data-strand-code');
                const strandName = this.getAttribute('data-strand-name');
                const description = this.getAttribute('data-description');
                const status = this.getAttribute('data-status');
                
                document.getElementById('edit_id').value = id;
                document.getElementById('edit_track_name').value = trackName;
                document.getElementById('edit_strand_code').value = strandCode;
                document.getElementById('edit_strand_name').value = strandName;
                document.getElementById('edit_description').value = description;
                document.getElementById('edit_status').value = status;
                
                // Show the edit modal
                const editModal = new bootstrap.Modal(document.getElementById('editStrandModal'));
                editModal.show();
            });
        });
        
        // Set up delete buttons
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const strandCode = this.getAttribute('data-strand-code');
                const strandName = this.getAttribute('data-strand-name');
                
                document.getElementById('delete_id').value = id;
                document.getElementById('delete_strand_code').textContent = strandCode;
                document.getElementById('delete_strand_name').textContent = strandName;
                
                // Show the delete modal
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteStrandModal'));
                deleteModal.show();
            });
        });
    });
</script>

<?php
$conn->close();
require_once '../../includes/footer.php';
?> 
<?php
$title = 'Grading Periods';
$page_header = 'Grading Periods Management';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
    exit();
}

// Create the grading_periods table if it doesn't exist
$check_table = "SHOW TABLES LIKE 'grading_periods'";
$table_exists = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_exists) == 0) {
    $create_table = "CREATE TABLE grading_periods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        is_current BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);
    
    // Insert default grading periods
    $insert_periods = "INSERT INTO grading_periods (name, start_date, end_date, school_year, is_current) VALUES
        ('First Quarter', '2023-06-05', '2023-08-11', '2023-2024', TRUE),
        ('Second Quarter', '2023-08-14', '2023-10-20', '2023-2024', FALSE),
        ('Third Quarter', '2023-10-23', '2023-12-22', '2023-2024', FALSE),
        ('Fourth Quarter', '2024-01-08', '2024-03-22', '2023-2024', FALSE)";
    mysqli_query($conn, $insert_periods);
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Add new grading period
        if ($action === 'add') {
            $name = cleanInput($_POST['name']);
            $start_date = cleanInput($_POST['start_date']);
            $end_date = cleanInput($_POST['end_date']);
            $school_year = cleanInput($_POST['school_year']);
            $is_current = isset($_POST['is_current']) ? 1 : 0;
            
            // Validate dates
            if (strtotime($end_date) < strtotime($start_date)) {
                $_SESSION['alert'] = showAlert('End date must be after start date.', 'danger');
            } else {
                // If setting this period as current, update all others to not current
                if ($is_current) {
                    $update_others = "UPDATE grading_periods SET is_current = 0";
                    mysqli_query($conn, $update_others);
                }
                
                // Insert new period
                $insert_query = "INSERT INTO grading_periods (name, start_date, end_date, school_year, is_current) 
                                VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, 'ssssi', $name, $start_date, $end_date, $school_year, $is_current);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['alert'] = showAlert('Grading period added successfully!', 'success');
                    logAction($_SESSION['user_id'], 'CREATE', "Added grading period: $name ($school_year)");
                } else {
                    $_SESSION['alert'] = showAlert('Error adding grading period: ' . mysqli_error($conn), 'danger');
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        
        // Edit grading period
        elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $name = cleanInput($_POST['name']);
            $start_date = cleanInput($_POST['start_date']);
            $end_date = cleanInput($_POST['end_date']);
            $school_year = cleanInput($_POST['school_year']);
            $is_current = isset($_POST['is_current']) ? 1 : 0;
            
            // Validate dates
            if (strtotime($end_date) < strtotime($start_date)) {
                $_SESSION['alert'] = showAlert('End date must be after start date.', 'danger');
            } else {
                // If setting this period as current, update all others to not current
                if ($is_current) {
                    $update_others = "UPDATE grading_periods SET is_current = 0";
                    mysqli_query($conn, $update_others);
                }
                
                // Update period
                $update_query = "UPDATE grading_periods SET name = ?, start_date = ?, end_date = ?, 
                               school_year = ?, is_current = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, 'ssssii', $name, $start_date, $end_date, $school_year, $is_current, $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['alert'] = showAlert('Grading period updated successfully!', 'success');
                    logAction($_SESSION['user_id'], 'UPDATE', "Updated grading period: $name ($school_year)");
                } else {
                    $_SESSION['alert'] = showAlert('Error updating grading period: ' . mysqli_error($conn), 'danger');
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        
        // Delete grading period
        elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            // Check if grades exist for this period
            $check_grades = "SELECT COUNT(*) as count FROM grades WHERE grading_period_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_grades);
            mysqli_stmt_bind_param($check_stmt, 'i', $id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $grades_count = mysqli_fetch_assoc($check_result)['count'];
            
            if ($grades_count > 0) {
                $_SESSION['alert'] = showAlert('Cannot delete this grading period because there are ' . $grades_count . ' grades associated with it.', 'danger');
            } else {
                // Delete the period
                $delete_query = "DELETE FROM grading_periods WHERE id = ?";
                $stmt = mysqli_prepare($conn, $delete_query);
                mysqli_stmt_bind_param($stmt, 'i', $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['alert'] = showAlert('Grading period deleted successfully!', 'success');
                    logAction($_SESSION['user_id'], 'DELETE', "Deleted grading period ID: $id");
                } else {
                    $_SESSION['alert'] = showAlert('Error deleting grading period: ' . mysqli_error($conn), 'danger');
                }
                
                mysqli_stmt_close($stmt);
            }
        }
        
        // Set current grading period
        elseif ($action === 'set_current') {
            $id = (int)$_POST['id'];
            
            // Update all periods to not current
            $update_all = "UPDATE grading_periods SET is_current = 0";
            mysqli_query($conn, $update_all);
            
            // Set the selected period as current
            $update_query = "UPDATE grading_periods SET is_current = 1 WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, 'i', $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['alert'] = showAlert('Current grading period updated successfully!', 'success');
                logAction($_SESSION['user_id'], 'UPDATE', "Set current grading period ID: $id");
            } else {
                $_SESSION['alert'] = showAlert('Error updating current grading period: ' . mysqli_error($conn), 'danger');
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get all grading periods
$periods_query = "SELECT * FROM grading_periods ORDER BY school_year DESC, start_date ASC";
$periods_result = mysqli_query($conn, $periods_query);
$grading_periods = [];

while ($row = mysqli_fetch_assoc($periods_result)) {
    $grading_periods[] = $row;
}

// Get unique school years for filtering
$school_years = [];
foreach ($grading_periods as $period) {
    if (!in_array($period['school_year'], $school_years)) {
        $school_years[] = $period['school_year'];
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <?php if (isset($_SESSION['alert'])) echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-alt me-2"></i> Grading Periods</h5>
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
                    <i class="fas fa-plus me-1"></i> Add New Period
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Name</th>
                                <th width="15%">School Year</th>
                                <th width="15%">Start Date</th>
                                <th width="15%">End Date</th>
                                <th width="10%">Status</th>
                                <th width="20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($grading_periods) > 0): ?>
                                <?php foreach ($grading_periods as $index => $period): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($period['name']); ?></td>
                                    <td><?php echo htmlspecialchars($period['school_year']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($period['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($period['end_date'])); ?></td>
                                    <td>
                                        <?php if ($period['is_current']): ?>
                                            <span class="badge bg-success">Current</span>
                                        <?php else: ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Set this as the current grading period?');">
                                                <input type="hidden" name="action" value="set_current">
                                                <input type="hidden" name="id" value="<?php echo $period['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Set Current</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary edit-period" 
                                                data-id="<?php echo $period['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($period['name']); ?>"
                                                data-start="<?php echo $period['start_date']; ?>"
                                                data-end="<?php echo $period['end_date']; ?>"
                                                data-year="<?php echo htmlspecialchars($period['school_year']); ?>"
                                                data-current="<?php echo $period['is_current']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#editPeriodModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="post" class="d-inline delete-form" onsubmit="return confirm('Are you sure you want to delete this grading period? This cannot be undone.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $period['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No grading periods found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Period Modal -->
<div class="modal fade" id="addPeriodModal" tabindex="-1" aria-labelledby="addPeriodModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addPeriodModalLabel">Add New Grading Period</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Period Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="school_year" class="form-label">School Year</label>
                        <input type="text" class="form-control" id="school_year" name="school_year" placeholder="e.g. 2023-2024" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_current" name="is_current">
                        <label class="form-check-label" for="is_current">Set as Current Grading Period</label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Period Modal -->
<div class="modal fade" id="editPeriodModal" tabindex="-1" aria-labelledby="editPeriodModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editPeriodModalLabel">Edit Grading Period</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Period Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_school_year" class="form-label">School Year</label>
                        <input type="text" class="form-control" id="edit_school_year" name="school_year" placeholder="e.g. 2023-2024" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_is_current" name="is_current">
                        <label class="form-check-label" for="edit_is_current">Set as Current Grading Period</label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Period</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit period button handler
    const editButtons = document.querySelectorAll('.edit-period');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const start = this.getAttribute('data-start');
            const end = this.getAttribute('data-end');
            const year = this.getAttribute('data-year');
            const isCurrent = this.getAttribute('data-current') === '1';
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_start_date').value = start;
            document.getElementById('edit_end_date').value = end;
            document.getElementById('edit_school_year').value = year;
            document.getElementById('edit_is_current').checked = isCurrent;
        });
    });
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
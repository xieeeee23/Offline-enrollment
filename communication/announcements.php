<?php
$title = 'Announcements';
$page_header = 'Announcements';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Create the table if it doesn't exist
$check_table = "SHOW TABLES LIKE 'announcements'";
$table_exists = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_exists) == 0) {
    $create_table = "CREATE TABLE announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        user_id INT,
        target_audience ENUM('all', 'students', 'teachers', 'parents', 'admin', 'registrar') DEFAULT 'all',
        is_pinned BOOLEAN DEFAULT FALSE,
        start_date DATE DEFAULT CURRENT_DATE,
        end_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    mysqli_query($conn, $create_table);
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user has permission to create announcements
    if (checkAccess(['admin', 'registrar', 'teacher'])) {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            
            // Create new announcement
            if ($action === 'create') {
                $title = cleanInput($_POST['title']);
                $content = cleanInput($_POST['content']);
                $target_audience = cleanInput($_POST['target_audience']);
                $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
                $start_date = cleanInput($_POST['start_date']);
                $end_date = !empty($_POST['end_date']) ? cleanInput($_POST['end_date']) : NULL;
                
                $query = "INSERT INTO announcements (title, content, user_id, target_audience, is_pinned, start_date, end_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'ssissss', $title, $content, $_SESSION['user_id'], $target_audience, $is_pinned, $start_date, $end_date);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['alert'] = showAlert('Announcement created successfully.', 'success');
                    logAction($_SESSION['user_id'], 'CREATE', 'Created new announcement: ' . $title);
                } else {
                    $_SESSION['alert'] = showAlert('Error creating announcement.', 'danger');
                }
                
                // Redirect to prevent form resubmission
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
            
            // Update existing announcement
            else if ($action === 'update' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                $title = cleanInput($_POST['title']);
                $content = cleanInput($_POST['content']);
                $target_audience = cleanInput($_POST['target_audience']);
                $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
                $start_date = cleanInput($_POST['start_date']);
                $end_date = !empty($_POST['end_date']) ? cleanInput($_POST['end_date']) : NULL;
                
                $query = "UPDATE announcements SET title = ?, content = ?, target_audience = ?, 
                          is_pinned = ?, start_date = ?, end_date = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'ssssssi', $title, $content, $target_audience, $is_pinned, $start_date, $end_date, $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['alert'] = showAlert('Announcement updated successfully.', 'success');
                    logAction($_SESSION['user_id'], 'UPDATE', 'Updated announcement ID: ' . $id);
                } else {
                    $_SESSION['alert'] = showAlert('Error updating announcement.', 'danger');
                }
                
                // Redirect to prevent form resubmission
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
            
            // Delete announcement
            else if ($action === 'delete' && isset($_POST['id'])) {
                $id = (int)$_POST['id'];
                
                // Get the announcement title for logging
                $get_title = "SELECT title FROM announcements WHERE id = ?";
                $title_stmt = mysqli_prepare($conn, $get_title);
                mysqli_stmt_bind_param($title_stmt, 'i', $id);
                mysqli_stmt_execute($title_stmt);
                mysqli_stmt_bind_result($title_stmt, $title);
                mysqli_stmt_fetch($title_stmt);
                mysqli_stmt_close($title_stmt);
                
                $query = "DELETE FROM announcements WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, 'i', $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['alert'] = showAlert('Announcement deleted successfully.', 'success');
                    logAction($_SESSION['user_id'], 'DELETE', 'Deleted announcement: ' . $title);
                } else {
                    $_SESSION['alert'] = showAlert('Error deleting announcement.', 'danger');
                }
                
                // Redirect to prevent form resubmission
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    } else {
        $_SESSION['alert'] = showAlert('You do not have permission to perform this action.', 'danger');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch announcements based on user role
$query = "SELECT a.*, u.name as creator_name FROM announcements a 
          LEFT JOIN users u ON a.user_id = u.id WHERE 1=1";

// Filter announcements based on user role
if ($_SESSION['role'] !== 'admin') {
    $query .= " AND (a.target_audience = 'all' OR a.target_audience = '" . $_SESSION['role'] . "s')";
}

// Sort by pinned status and then by creation date
$query .= " ORDER BY a.is_pinned DESC, a.created_at DESC";

$result = mysqli_query($conn, $query);
$announcements = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $announcements[] = $row;
    }
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <?php if (isset($_SESSION['alert'])) echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    </div>
</div>

<?php if (checkAccess(['admin', 'registrar', 'teacher'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-plus-circle me-2"></i> Create New Announcement</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label for="target_audience" class="form-label">Target Audience</label>
                            <select class="form-select" id="target_audience" name="target_audience">
                                <option value="all">Everyone</option>
                                <option value="students">Students</option>
                                <option value="teachers">Teachers</option>
                                <option value="parents">Parents</option>
                                <?php if (checkAccess(['admin'])): ?>
                                <option value="admin">Administrators</option>
                                <option value="registrar">Registrars</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date (Optional)</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="is_pinned" name="is_pinned">
                                <label class="form-check-label" for="is_pinned">
                                    Pin this announcement
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Post Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-bullhorn me-2"></i> All Announcements</h5>
            </div>
            <div class="card-body">
                <?php if (empty($announcements)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No announcements available at this time.
                </div>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                    <div class="card mb-3 announcement-card <?php echo $announcement['is_pinned'] ? 'border-primary' : ''; ?>">
                        <div class="card-header d-flex justify-content-between align-items-center <?php echo $announcement['is_pinned'] ? 'bg-light text-primary' : ''; ?>">
                            <h5 class="card-title mb-0">
                                <?php if ($announcement['is_pinned']): ?>
                                <i class="fas fa-thumbtack me-2"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($announcement['title']); ?>
                            </h5>
                            <div>
                                <?php if ($announcement['target_audience'] != 'all'): ?>
                                <span class="badge bg-info me-2"><?php echo ucfirst($announcement['target_audience']); ?></span>
                                <?php endif; ?>
                                <span class="badge bg-secondary">
                                    Posted: <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i> Posted by: <?php echo htmlspecialchars($announcement['creator_name']); ?>
                                </small>
                                
                                <?php if (checkAccess(['admin']) || ($announcement['user_id'] == $_SESSION['user_id'])): ?>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-announcement" 
                                            data-id="<?php echo $announcement['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($announcement['title']); ?>"
                                            data-content="<?php echo htmlspecialchars($announcement['content']); ?>"
                                            data-target="<?php echo $announcement['target_audience']; ?>"
                                            data-pinned="<?php echo $announcement['is_pinned']; ?>"
                                            data-start="<?php echo $announcement['start_date']; ?>"
                                            data-end="<?php echo $announcement['end_date']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-announcement"
                                            data-id="<?php echo $announcement['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($announcement['title']); ?>">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editAnnouncementForm" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editAnnouncementModalLabel">Edit Announcement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_target_audience" class="form-label">Target Audience</label>
                            <select class="form-select" id="edit_target_audience" name="target_audience">
                                <option value="all">Everyone</option>
                                <option value="students">Students</option>
                                <option value="teachers">Teachers</option>
                                <option value="parents">Parents</option>
                                <?php if (checkAccess(['admin'])): ?>
                                <option value="admin">Administrators</option>
                                <option value="registrar">Registrars</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_content" class="form-label">Content</label>
                        <textarea class="form-control" id="edit_content" name="content" rows="5" required></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="edit_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_end_date" class="form-label">End Date (Optional)</label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="edit_is_pinned" name="is_pinned">
                                <label class="form-check-label" for="edit_is_pinned">
                                    Pin this announcement
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAnnouncementModal" tabindex="-1" aria-labelledby="deleteAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteAnnouncementForm" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAnnouncementModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the announcement: <strong id="delete_title"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit announcement handling
    const editButtons = document.querySelectorAll('.edit-announcement');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const content = this.getAttribute('data-content');
            const target = this.getAttribute('data-target');
            const pinned = this.getAttribute('data-pinned') === '1';
            const startDate = this.getAttribute('data-start');
            const endDate = this.getAttribute('data-end');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_content').value = content;
            document.getElementById('edit_target_audience').value = target;
            document.getElementById('edit_is_pinned').checked = pinned;
            document.getElementById('edit_start_date').value = startDate;
            document.getElementById('edit_end_date').value = endDate || '';
            
            const editModal = new bootstrap.Modal(document.getElementById('editAnnouncementModal'));
            editModal.show();
        });
    });
    
    // Delete announcement handling
    const deleteButtons = document.querySelectorAll('.delete-announcement');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_title').textContent = title;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteAnnouncementModal'));
            deleteModal.show();
        });
    });
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
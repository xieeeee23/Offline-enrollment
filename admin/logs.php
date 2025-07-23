<?php
$title = 'Activity Logs';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin role
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Process clear logs if requested
if (isset($_GET['clear']) && $_GET['clear'] === 'all') {
    // Confirm with a POST request for security
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_clear'])) {
        $query = "DELETE FROM logs";
        if (mysqli_query($conn, $query)) {
            // Log this action (will be the only log left)
            logAction($_SESSION['user_id'], 'DELETE', 'Cleared all logs');
            
            $_SESSION['alert'] = showAlert('All logs have been cleared successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error clearing logs: ' . mysqli_error($conn), 'danger');
        }
        
        // Redirect to logs page
        redirect('modules/admin/logs.php');
    }
}

// Set up pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Get filter parameters
$user_filter = isset($_GET['user']) ? (int) $_GET['user'] : null;
$action_filter = isset($_GET['action']) ? cleanInput($_GET['action']) : null;
$date_filter = isset($_GET['date']) ? cleanInput($_GET['date']) : null;

// Build query based on filters
$where_clauses = [];
$params = [];
$types = "";

if ($user_filter) {
    $where_clauses[] = "l.user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

if ($action_filter) {
    $where_clauses[] = "l.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if ($date_filter) {
    $where_clauses[] = "DATE(l.timestamp) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

// Construct the WHERE clause
$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM logs l $where_sql";
$total_records = 0;

if (empty($params)) {
    $count_result = mysqli_query($conn, $count_query);
} else {
    $stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
}

if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    $total_records = $row['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Get logs with pagination and filters
$logs = [];
$query = "SELECT l.*, u.name, u.username, u.role 
          FROM logs l 
          LEFT JOIN users u ON l.user_id = u.id 
          $where_sql
          ORDER BY l.timestamp DESC 
          LIMIT ?, ?";

// Add pagination parameters
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
}

// Get unique action types for filter dropdown
$actions = [];
$action_query = "SELECT DISTINCT action FROM logs ORDER BY action";
$action_result = mysqli_query($conn, $action_query);
if ($action_result) {
    while ($row = mysqli_fetch_assoc($action_result)) {
        $actions[] = $row['action'];
    }
}

// Get users for filter dropdown
$users = [];
$user_query = "SELECT id, name, username FROM users ORDER BY name";
$user_result = mysqli_query($conn, $user_query);
if ($user_result) {
    while ($row = mysqli_fetch_assoc($user_result)) {
        $users[] = $row;
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Activity Logs</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Filter Logs</h5>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                    <i class="fas fa-trash"></i> Clear All Logs
                </button>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="user" class="form-label">User</label>
                        <select class="form-select" id="user" name="user">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo ($user_filter == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name'] . ' (' . $user['username'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="action" class="form-label">Action</label>
                        <select class="form-select" id="action" name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo $action; ?>" <?php echo ($action_filter == $action) ? 'selected' : ''; ?>>
                                    <?php echo $action; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="<?php echo $relative_path; ?>modules/admin/logs.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Log Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No logs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo formatDate($log['timestamp'], 'M d, Y h:i:s A'); ?></td>
                                        <td>
                                            <?php if ($log['name']): ?>
                                                <?php echo htmlspecialchars($log['name']); ?>
                                                <span class="badge bg-info"><?php echo ucfirst($log['role'] ?? ''); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = 'bg-secondary';
                                            switch ($log['action']) {
                                                case 'LOGIN':
                                                    $badge_class = 'bg-success';
                                                    break;
                                                case 'LOGOUT':
                                                    $badge_class = 'bg-warning text-dark';
                                                    break;
                                                case 'CREATE':
                                                    $badge_class = 'bg-primary';
                                                    break;
                                                case 'UPDATE':
                                                    $badge_class = 'bg-info text-dark';
                                                    break;
                                                case 'DELETE':
                                                    $badge_class = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['description']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo $user_filter ? '&user=' . $user_filter : ''; ?><?php echo $action_filter ? '&action=' . $action_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>">
                                        First
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $user_filter ? '&user=' . $user_filter : ''; ?><?php echo $action_filter ? '&action=' . $action_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $user_filter ? '&user=' . $user_filter : ''; ?><?php echo $action_filter ? '&action=' . $action_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $user_filter ? '&user=' . $user_filter : ''; ?><?php echo $action_filter ? '&action=' . $action_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>">
                                        Next
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $user_filter ? '&user=' . $user_filter : ''; ?><?php echo $action_filter ? '&action=' . $action_filter : ''; ?><?php echo $date_filter ? '&date=' . $date_filter : ''; ?>">
                                        Last
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted">
                Total records: <?php echo $total_records; ?>
            </div>
        </div>
    </div>
</div>

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1" aria-labelledby="clearLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="clearLogsModalLabel">Clear All Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear all logs? This action cannot be undone!</p>
                <p><strong>Warning:</strong> This will permanently delete all activity logs from the system.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="<?php echo BASE_URL; ?>modules/admin/logs.php?clear=all">
                    <input type="hidden" name="confirm_clear" value="1">
                    <button type="submit" class="btn btn-danger">Clear All Logs</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
<?php
// This file provides direct access to the database.php file
// without URL duplication issues

// Define the relative path
$relative_path = '../../';

// Include necessary files
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';
require_once $relative_path . 'includes/Database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin role
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    header("Location: {$relative_path}dashboard.php");
    exit;
}

// Include the header
require_once $relative_path . 'includes/header.php';

// Get database statistics
$conn = getConnection();
    $tables = [];
    $result = mysqli_query($conn, 'SHOW TABLES');
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
$tables_count = count($tables);

// Calculate total size and rows
$total_size = 0;
$total_rows = 0;
$db_stats = [];

foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLE STATUS LIKE '$table'");
    $row = mysqli_fetch_assoc($result);
    
    $size = ($row['Data_length'] + $row['Index_length']);
    $rows = $row['Rows'];
    
    $total_size += $size;
    $total_rows += $rows;
    
    $db_stats[] = [
        'name' => $table,
        'rows' => $rows,
        'size' => $size,
        'engine' => $row['Engine']
    ];
}

// Sort by size (largest first)
usort($db_stats, function($a, $b) {
    return $b['size'] - $a['size'];
});

// Get backup files
$backup_dir = $relative_path . 'backups';
$backups = [];
if (file_exists($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backup_dir . '/' . $file),
                'date' => date('Y-m-d H:i:s', filemtime($backup_dir . '/' . $file))
            ];
        }
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

// Get the largest tables
$largest_tables = array_slice($db_stats, 0, 5);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Database Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Database Management</li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])): ?>
        <?php echo $_SESSION['alert']; ?>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

<div class="row">
        <!-- Database Statistics -->
        <div class="col-xl-6">
        <div class="card mb-4">
            <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i> Database Statistics
            </div>
            <div class="card-body">
                <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="me-3">
                                            <div class="text-white-75 small">Total Tables</div>
                                            <div class="text-lg fw-bold"><?php echo $tables_count; ?></div>
                                        </div>
                                        <i class="fas fa-table fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="me-3">
                                            <div class="text-white-75 small">Database Size</div>
                                            <div class="text-lg fw-bold"><?php echo formatBytes($total_size); ?></div>
                                        </div>
                                        <i class="fas fa-database fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="me-3">
                                            <div class="text-white-75 small">Total Records</div>
                                            <div class="text-lg fw-bold"><?php echo number_format($total_rows); ?></div>
                                        </div>
                                        <i class="fas fa-list fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="me-3">
                                            <div class="text-white-75 small">Backups Available</div>
                                            <div class="text-lg fw-bold"><?php echo count($backups); ?></div>
                                        </div>
                                        <i class="fas fa-save fa-2x text-white-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Database Actions -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-download me-1"></i> Database Backup
                </div>
                <div class="card-body">
                    <p>Create a complete backup of your database. This will export all tables, data, and structure.</p>
                    <form method="post" action="<?php echo $relative_path; ?>db_manage.php">
                        <div class="d-grid gap-2">
                            <button type="submit" name="backup_db" class="btn btn-primary">
                                <i class="fas fa-download me-1"></i> Create Database Backup
                            </button>
                            <a href="export_sql.php" class="btn btn-outline-primary">
                                <i class="fas fa-cog me-1"></i> Advanced Export Options
                            </a>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <h5>Database Import</h5>
                    <p>Import a database SQL file. This will add or update records in your database.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i> 
                        <strong>Warning:</strong> Importing a database may overwrite existing data. A backup will be created automatically before import.
                    </div>
                    
                    <form method="post" action="<?php echo $relative_path; ?>db_manage.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="sql_file" class="form-label">SQL File</label>
                            <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql" required>
                            <div class="form-text">Select a valid SQL file to import. Maximum file size: <?php echo ini_get('upload_max_filesize'); ?>.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="import_db" class="btn btn-success">
                                <i class="fas fa-upload me-1"></i> Import Database
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Backups -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i> Recent Backups
        </div>
        <div class="card-body">
            <?php if (count($backups) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($backup['name']); ?></td>
                            <td><?php echo formatBytes($backup['size']); ?></td>
                            <td><?php echo htmlspecialchars($backup['date']); ?></td>
                            <td>
                                <a href="<?php echo $relative_path; ?>backups/<?php echo urlencode($backup['name']); ?>" class="btn btn-sm btn-primary" download>
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                                <a href="<?php echo $relative_path; ?>db_manage.php?restore=<?php echo urlencode($backup['name']); ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to restore this backup? Current data may be overwritten.');">
                                    <i class="fas fa-undo me-1"></i> Restore
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-1"></i> No backup files found.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

require_once $relative_path . 'includes/footer.php';
?> 
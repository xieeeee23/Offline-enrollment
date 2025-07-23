<?php
$title = 'Export Database SQL';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin role
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}

// Get all tables in the database
$tables = [];
$result = mysqli_query($conn, 'SHOW TABLES');
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    // Set time limit to allow for large database exports
    set_time_limit(600);
    ini_set('memory_limit', '512M');
    
    $selected_tables = isset($_POST['tables']) ? $_POST['tables'] : [];
    $export_structure = isset($_POST['export_structure']) ? true : false;
    $export_data = isset($_POST['export_data']) ? true : false;
    $add_drop_table = isset($_POST['add_drop_table']) ? true : false;
    $include_comments = isset($_POST['include_comments']) ? true : false;
    
    if (empty($selected_tables)) {
        $_SESSION['alert'] = showAlert('Please select at least one table to export.', 'warning');
    } elseif (!$export_structure && !$export_data) {
        $_SESSION['alert'] = showAlert('Please select at least one export option (structure or data).', 'warning');
    } else {
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for SQL file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="database_export_' . date('Y-m-d_H-i-s') . '.sql"');
        
        // Output SQL header with timestamp and database info
        if ($include_comments) {
        echo "-- Database Export\n";
        echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "-- Server: " . mysqli_get_server_info($conn) . "\n";
            echo "-- Database: " . DB_NAME . "\n\n";
        }
        
        echo "SET FOREIGN_KEY_CHECKS=0;\n";
        echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        echo "SET time_zone = \"+00:00\";\n\n";
        
        // Export each selected table
        foreach ($selected_tables as $table) {
            // Sanitize table name to prevent SQL injection
            $table = mysqli_real_escape_string($conn, $table);
            
            if ($include_comments) {
                echo "-- --------------------------------------------------------\n";
                echo "-- Table structure for table `$table`\n";
                echo "-- --------------------------------------------------------\n\n";
            }
            
            // Export table structure
            if ($export_structure) {
            $result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
            $row = mysqli_fetch_row($result);
            
                if ($add_drop_table) {
            echo "DROP TABLE IF EXISTS `$table`;\n";
                }
                
            echo $row[1] . ";\n\n";
            }
            
            // Export table data
            if ($export_data) {
            // Get table data
            $result = mysqli_query($conn, "SELECT * FROM `$table`");
            $num_fields = mysqli_num_fields($result);
            $num_rows = mysqli_num_rows($result);
            
            if ($num_rows > 0) {
                    if ($include_comments) {
                        echo "-- --------------------------------------------------------\n";
                echo "-- Data for table `$table`\n";
                        echo "-- --------------------------------------------------------\n\n";
                    }
                
                // Get column names
                $fields = [];
                for ($i = 0; $i < $num_fields; $i++) {
                    $field_info = mysqli_fetch_field_direct($result, $i);
                    $fields[] = "`" . $field_info->name . "`";
                }
                
                    // Get column types for proper escaping
                    $field_types = [];
                    $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");
                    while ($column = mysqli_fetch_assoc($columns_result)) {
                        $type = strtolower($column['Type']);
                        $is_numeric = (
                            strpos($type, 'int') !== false || 
                            strpos($type, 'decimal') !== false || 
                            strpos($type, 'float') !== false || 
                            strpos($type, 'double') !== false
                        );
                        $field_types[$column['Field']] = $is_numeric;
                    }
                    
                    // Process rows in batches to save memory
                    $batch_size = 100;
                $row_count = 0;
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        if ($row_count % $batch_size == 0) {
                            if ($row_count > 0) {
                                echo ";\n";
                            }
                            echo "INSERT INTO `$table` (" . implode(', ', $fields) . ") VALUES\n";
                        } else {
                            echo ",\n";
                        }
                        
                        echo "(";
                        $values = [];
                        foreach ($row as $field => $value) {
                            if (is_null($value)) {
                                $values[] = "NULL";
                            } elseif ($field_types[$field]) {
                                // Numeric value - no quotes needed
                                $values[] = $value;
                            } else {
                                // String value - escape and quote
                                $values[] = "'" . mysqli_real_escape_string($conn, $value) . "'";
                            }
                        }
                        echo implode(", ", $values);
                        echo ")";
                        
                        $row_count++;
                        
                        if ($row_count % $batch_size == 0 || $row_count == $num_rows) {
                            echo ";";
                        }
                    }
                    
                    if ($row_count > 0 && $row_count % $batch_size != 0) {
                        echo ";\n";
                    }
                    
                    echo "\n";
                }
            }
        }
        
        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Log action
        logAction($_SESSION['user_id'], 'EXPORT', 'Exported ' . count($selected_tables) . ' tables to SQL');
        exit;
    }
}

// Get table sizes and row counts
$table_info = [];
foreach ($tables as $table) {
    // Get table row count
    $row_count_result = mysqli_query($conn, "SELECT COUNT(*) FROM `$table`");
    $row_count = mysqli_fetch_row($row_count_result)[0];
    
    // Get table size
    $size_result = mysqli_query($conn, "SHOW TABLE STATUS LIKE '$table'");
    $size_row = mysqli_fetch_assoc($size_result);
    $size_kb = round(($size_row['Data_length'] + $size_row['Index_length']) / 1024, 2);
    
    $table_info[$table] = [
        'rows' => $row_count,
        'size' => $size_kb,
        'engine' => $size_row['Engine'],
        'collation' => $size_row['Collation'],
        'created' => $size_row['Create_time'],
        'updated' => $size_row['Update_time']
    ];
}

// Sort tables by name
ksort($table_info);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Export Database SQL</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="database.php">Database Management</a></li>
        <li class="breadcrumb-item active">Export SQL</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-download me-1"></i> Select Tables to Export
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                Export Options
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="export_structure" id="export_structure" checked>
                                    <label class="form-check-label" for="export_structure">
                                        Export table structure
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="export_data" id="export_data" checked>
                                    <label class="form-check-label" for="export_data">
                                        Export table data
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="add_drop_table" id="add_drop_table" checked>
                                    <label class="form-check-label" for="add_drop_table">
                                        Add DROP TABLE statements
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="include_comments" id="include_comments" checked>
                                    <label class="form-check-label" for="include_comments">
                                        Include comments in SQL
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                Table Selection
                            </div>
                            <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <button type="button" class="btn btn-sm btn-secondary" id="select-all">Select All</button>
                                <button type="button" class="btn btn-sm btn-secondary" id="deselect-all">Deselect All</button>
                                </div>
                                
                                <div class="input-group mb-3">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="table-search" placeholder="Search tables...">
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i> Select tables to export. Use the search box to filter tables by name.
                                </div>
                            </div>
                        </div>
                    </div>
                            </div>
                            
                            <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tables-table">
                                    <thead>
                                        <tr>
                                            <th width="50px">Select</th>
                                            <th>Table Name</th>
                                            <th>Rows</th>
                                            <th>Size</th>
                                <th>Engine</th>
                                <th>Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            <?php foreach ($table_info as $table => $info): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input table-checkbox" type="checkbox" name="tables[]" value="<?php echo htmlspecialchars($table); ?>" id="table-<?php echo htmlspecialchars($table); ?>">
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($table); ?></td>
                                <td><?php echo number_format($info['rows']); ?></td>
                                <td><?php echo number_format($info['size'], 2); ?> KB</td>
                                <td><?php echo htmlspecialchars($info['engine']); ?></td>
                                <td><?php echo $info['updated'] ? date('Y-m-d H:i:s', strtotime($info['updated'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                        </div>
                        
                <div class="d-flex justify-content-between mt-3">
                    <a href="database.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Database Management
                    </a>
                            <button type="submit" name="export" class="btn btn-primary">
                                <i class="fas fa-download me-1"></i> Export Selected Tables
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
    <div class="card mb-4">
                <div class="card-header bg-info text-white">
            <i class="fas fa-info-circle me-1"></i> Export Information
                </div>
                <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>About Database Export</h5>
                    <p>This tool allows you to export database tables as SQL statements. The exported file can be used for:</p>
                    <ul>
                        <li>Creating database backups</li>
                        <li>Migrating data to another server</li>
                        <li>Setting up a development environment</li>
                    </ul>
                    <p><strong>Note:</strong> Depending on the size of your database, the export process may take some time.</p>
                </div>
                <div class="col-md-6">
                    <h5>Export Options Explained</h5>
                    <ul>
                        <li><strong>Export table structure:</strong> Includes CREATE TABLE statements</li>
                        <li><strong>Export table data:</strong> Includes INSERT statements with table data</li>
                        <li><strong>Add DROP TABLE statements:</strong> Adds DROP TABLE IF EXISTS before CREATE TABLE</li>
                        <li><strong>Include comments:</strong> Adds descriptive comments to the SQL file</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all tables
    document.getElementById('select-all').addEventListener('click', function() {
        document.querySelectorAll('.table-checkbox').forEach(function(checkbox) {
            checkbox.checked = true;
        });
    });
    
    // Deselect all tables
    document.getElementById('deselect-all').addEventListener('click', function() {
        document.querySelectorAll('.table-checkbox').forEach(function(checkbox) {
            checkbox.checked = false;
        });
    });
    
    // Table search functionality
    document.getElementById('table-search').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#tables-table tbody tr');
        
        tableRows.forEach(function(row) {
            const tableName = row.cells[1].textContent.toLowerCase();
            if (tableName.includes(searchText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const checkedTables = document.querySelectorAll('.table-checkbox:checked');
        const exportStructure = document.getElementById('export_structure').checked;
        const exportData = document.getElementById('export_data').checked;
        
        if (checkedTables.length === 0) {
            e.preventDefault();
            alert('Please select at least one table to export.');
        } else if (!exportStructure && !exportData) {
            e.preventDefault();
            alert('Please select at least one export option (structure or data).');
        }
    });
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
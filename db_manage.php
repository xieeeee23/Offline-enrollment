<?php
// Direct access file for database management that avoids URL duplication issues
// This file should be accessed directly from the browser

// Define the relative path
$relative_path = './';

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['backup_db'])) {
        // Set time limit to allow for large database exports
        set_time_limit(600);
        ini_set('memory_limit', '512M');
        
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Get database credentials
        $db_host = DB_HOST;
        $db_user = DB_USER;
        $db_pass = DB_PASS;
        $db_name = DB_NAME;
        
        // Create backup directory if it doesn't exist
        $backup_dir = $relative_path . 'backups';
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Generate filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$timestamp}.sql";
        $filepath = $backup_dir . '/' . $filename;
        
        // Try to use mysqldump if available (most reliable method)
        $output = null;
        $return_var = null;
        
        // Create backup using mysqldump if available
        if (function_exists('exec')) {
            $command = "mysqldump --host=$db_host --user=$db_user " . 
                      ($db_pass ? "--password='$db_pass' " : "") . 
                      "--add-drop-table --skip-lock-tables --complete-insert " .
                      "--extended-insert --single-transaction --quick " .
                      "--routines --triggers --events $db_name > \"$filepath\"";
            
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                // Success - offer download
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                
                // Log action
                logAction($_SESSION['user_id'], 'BACKUP', 'Created database backup: ' . $filename);
                exit;
            }
        }
        
        // If mysqldump fails or is not available, use PHP to export
        $tables = [];
        $result = mysqli_query($conn, 'SHOW TABLES');
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
        
        // Open file for writing
        $file_handle = fopen($filepath, 'w');
        
        // Export header
        fwrite($file_handle, "-- LocalEnroll Pro Database Backup\n");
        fwrite($file_handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($file_handle, "-- Server version: " . mysqli_get_server_info($conn) . "\n");
        fwrite($file_handle, "-- PHP Version: " . phpversion() . "\n");
        fwrite($file_handle, "-- Database: " . $db_name . "\n\n");
        fwrite($file_handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($file_handle, "SET time_zone = \"+00:00\";\n");
        fwrite($file_handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
        
        // Export structure and data for each table
        foreach ($tables as $table) {
            // Get table structure
            $result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
            $row = mysqli_fetch_row($result);
            fwrite($file_handle, "-- Table structure for table `$table`\n");
            fwrite($file_handle, "DROP TABLE IF EXISTS `$table`;\n");
            fwrite($file_handle, $row[1] . ";\n\n");
            
            // Get table data
            $result = mysqli_query($conn, "SELECT * FROM `$table`");
            $num_fields = mysqli_num_fields($result);
            $num_rows = mysqli_num_rows($result);
            
            if ($num_rows > 0) {
                fwrite($file_handle, "-- Dumping data for table `$table`\n");
                
                $field_type = [];
                $i = 0;
                while ($i < $num_fields) {
                    $meta = mysqli_fetch_field($result);
                    $field_type[$i] = $meta->type;
                    $i++;
                }
                
                // Insert statements
                $insert_head = "INSERT INTO `$table` VALUES ";
                $insert_count = 0;
                
                while ($row = mysqli_fetch_row($result)) {
                    if ($insert_count == 0) {
                        fwrite($file_handle, $insert_head . "(");
                    } else {
                        fwrite($file_handle, ",\n(");
                    }
                    
                    for ($i = 0; $i < $num_fields; $i++) {
                        if (is_null($row[$i])) {
                            fwrite($file_handle, "NULL");
                        } else {
                            // Handle different field types
                            switch ($field_type[$i]) {
                                case 'int':
                                case 'tinyint':
                                case 'smallint':
                                case 'mediumint':
                                case 'bigint':
                                case 'decimal':
                                case 'float':
                                case 'double':
                                    fwrite($file_handle, $row[$i]);
                                    break;
                                default:
                                    fwrite($file_handle, "'" . mysqli_real_escape_string($conn, $row[$i]) . "'");
                            }
                        }
                        
                        if ($i < ($num_fields - 1)) {
                            fwrite($file_handle, ",");
                        }
                    }
                    
                    if ($insert_count == 100) {
                        fwrite($file_handle, ");\n");
                        $insert_count = 0;
                    } else {
                        fwrite($file_handle, ")");
                        $insert_count++;
                    }
                }
                
                if ($insert_count > 0) {
                    fwrite($file_handle, ";\n");
                }
            }
            
            fwrite($file_handle, "\n\n");
        }
        
        // Re-enable foreign key checks
        fwrite($file_handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        
        fclose($file_handle);
        
        // Offer download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        
        // Log action
        logAction($_SESSION['user_id'], 'BACKUP', 'Created database backup: ' . $filename);
        exit;
    } elseif (isset($_POST['import_db']) && isset($_FILES['sql_file'])) {
        // Handle database import
        // Set time limit for large imports
        set_time_limit(600);
        ini_set('memory_limit', '512M');
        
        $file = $_FILES['sql_file'];
        
        // Check for errors
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Create backup before import
            $backup_dir = $relative_path . 'backups';
            if (!file_exists($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $backup_filename = "pre_import_backup_{$timestamp}.sql";
            $backup_filepath = $backup_dir . '/' . $backup_filename;
            
            // Try to create backup using mysqldump
            $db_host = DB_HOST;
            $db_user = DB_USER;
            $db_pass = DB_PASS;
            $db_name = DB_NAME;
            
            $backup_created = false;
            
            if (function_exists('exec')) {
                $command = "mysqldump --host=$db_host --user=$db_user " . 
                          ($db_pass ? "--password='$db_pass' " : "") . 
                          "--add-drop-table --skip-lock-tables --complete-insert " .
                          "--extended-insert --single-transaction --quick " .
                          "$db_name > \"$backup_filepath\"";
                
                exec($command, $output, $return_var);
                
                if ($return_var === 0) {
                    $backup_created = true;
                }
            }
            
            // If mysqldump fails, log a warning but continue with import
            if (!$backup_created) {
                $_SESSION['alert'] = showAlert('Warning: Could not create backup before import. Proceeding with import anyway.', 'warning');
            }
            
            // Process the uploaded SQL file
            $temp_file = $file['tmp_name'];
            $file_content = file_get_contents($temp_file);
            
            if ($file_content) {
                // Check if the file is too large (over 10MB)
                if (strlen($file_content) > 10 * 1024 * 1024) {
                    // For large files, use a different approach
                    $success = importLargeFile($temp_file, $conn);
                    
                    if ($success) {
                        $_SESSION['alert'] = showAlert('Large database file imported successfully.', 'success');
                        logAction($_SESSION['user_id'], 'IMPORT', 'Imported large database from SQL file');
                    } else {
                        $_SESSION['alert'] = showAlert('Error importing large database file.', 'danger');
                    }
                    
                    // Redirect to avoid resubmission
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }
                
                // For smaller files, process in PHP
                // Split file into individual queries
                $queries = [];
                $current_query = '';
                $lines = explode("\n", $file_content);
                
                foreach ($lines as $line) {
                    // Skip comments and empty lines for processing (but keep them in the query)
                    if (substr(trim($line), 0, 2) == '--' || trim($line) == '' || substr(trim($line), 0, 1) == '#') {
                        $current_query .= $line . "\n";
                        continue;
                    }
                    
                    $current_query .= $line . "\n";
                    
                    // If line ends with ;, it's the end of a query
                    if (substr(trim($line), -1) == ';') {
                        $queries[] = $current_query;
                        $current_query = '';
                    }
                }
                
                // Add any remaining query
                if (!empty(trim($current_query))) {
                    $queries[] = $current_query;
                }
                
                // Begin transaction
                mysqli_autocommit($conn, false);
                
                // Disable foreign key checks before import
                mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
                
                $success = true;
                $error_messages = [];
                $warning_count = 0;
                $executed_count = 0;
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        // Skip queries that might be problematic
                        if (stripos($query, 'FOREIGN_KEY_CHECKS') !== false) {
                            continue;
                        }
                        
                        // Skip comments-only queries
                        if (substr(trim($query), 0, 2) == '--' || substr(trim($query), 0, 1) == '#') {
                            continue;
                        }
                        
                        // Modify CREATE TABLE queries to use IF NOT EXISTS
                        if (stripos($query, 'CREATE TABLE') !== false && stripos($query, 'IF NOT EXISTS') === false) {
                            $query = str_ireplace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $query);
                        }
                        
                        // Handle DROP TABLE queries
                        if (stripos($query, 'DROP TABLE') !== false && stripos($query, 'IF EXISTS') === false) {
                            $query = str_ireplace('DROP TABLE', 'DROP TABLE IF EXISTS', $query);
                        }
                        
                        // Execute query with error handling
                        try {
                            $result = mysqli_query($conn, $query);
                            if (!$result) {
                                // Store the error but continue with other queries
                                $error_message = mysqli_error($conn);
                                $error_messages[] = $error_message . " in query: " . substr($query, 0, 100) . "...";
                                
                                // Only count as warning for non-critical errors
                                if (strpos($error_message, 'Duplicate entry') !== false || 
                                    strpos($error_message, 'already exists') !== false ||
                                    strpos($error_message, 'Data too long') !== false ||
                                    strpos($error_message, 'foreign key constraint') !== false) {
                                    $warning_count++;
                                } else {
                                    // Don't fail on all errors, just log them
                                    $warning_count++;
                                }
                            } else {
                                $executed_count++;
                            }
                        } catch (Exception $e) {
                            // Log the exception and continue
                            $error_messages[] = "Exception: " . $e->getMessage() . " in query: " . substr($query, 0, 100) . "...";
                            $warning_count++;
                        }
                    }
                }
                
                // Re-enable foreign key checks
                mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
                
                // Commit transaction
                mysqli_commit($conn);
                
                if (count($error_messages) > 0) {
                    $_SESSION['alert'] = showAlert('Database imported with ' . $warning_count . ' warnings. ' . $executed_count . ' queries executed successfully.', 'warning');
                    
                    // Log detailed errors for admin review
                    $error_log = $backup_dir . '/import_errors_' . $timestamp . '.log';
                    file_put_contents($error_log, implode("\n\n", $error_messages));
                } else {
                    $_SESSION['alert'] = showAlert('Database imported successfully. ' . $executed_count . ' queries executed.', 'success');
                }
                
                // Log action
                logAction($_SESSION['user_id'], 'IMPORT', 'Imported database from SQL file');
            } else {
                $_SESSION['alert'] = showAlert('Error reading SQL file.', 'danger');
            }
        } else {
            $_SESSION['alert'] = showAlert('Error uploading file: ' . $file['error'], 'danger');
        }
        
        // Redirect to avoid resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif (isset($_GET['restore']) && !empty($_GET['restore'])) {
        // Process database restore
        $backup_file = $_GET['restore'];
        $backup_path = $relative_path . 'backups/' . $backup_file;
        
        // Validate that the file exists and is a SQL file
        if (file_exists($backup_path) && pathinfo($backup_path, PATHINFO_EXTENSION) === 'sql') {
            // Create a new backup before restoring
            $timestamp = date('Y-m-d_H-i-s');
            $pre_restore_backup = "pre_restore_backup_{$timestamp}.sql";
            $pre_restore_path = $relative_path . 'backups/' . $pre_restore_backup;
            
            // Try to create backup using mysqldump
            $db_host = DB_HOST;
            $db_user = DB_USER;
            $db_pass = DB_PASS;
            $db_name = DB_NAME;
            
            $backup_created = false;
            
            if (function_exists('exec')) {
                $command = "mysqldump --host=$db_host --user=$db_user " . 
                          ($db_pass ? "--password='$db_pass' " : "") . 
                          "--add-drop-table --skip-lock-tables --complete-insert " .
                          "--extended-insert --single-transaction --quick " .
                          "$db_name > \"$pre_restore_path\"";
                
                exec($command, $output, $return_var);
                
                if ($return_var === 0) {
                    $backup_created = true;
                }
            }
            
            // Read the backup file
            $sql_content = file_get_contents($backup_path);
            
            if ($sql_content) {
                // Set time limit for large restores
                set_time_limit(600);
                ini_set('memory_limit', '512M');
                
                // Modify SQL content to add IF NOT EXISTS to CREATE TABLE statements
                $sql_content = preg_replace('/CREATE TABLE\s+(`[^`]+`|[^\s]+)/i', 'CREATE TABLE IF NOT EXISTS $1', $sql_content);
                // Add IF EXISTS to DROP TABLE statements
                $sql_content = preg_replace('/DROP TABLE\s+(?!IF EXISTS)(`[^`]+`|[^\s]+)/i', 'DROP TABLE IF EXISTS $1', $sql_content);
                
                // Split file into individual queries
                $queries = [];
                $current_query = '';
                $lines = explode("\n", $sql_content);
                
                foreach ($lines as $line) {
                    // Skip comments and empty lines for processing
                    if (substr(trim($line), 0, 2) == '--' || trim($line) == '' || substr(trim($line), 0, 1) == '#') {
                        $current_query .= $line . "\n";
                        continue;
                    }
                    
                    $current_query .= $line . "\n";
                    
                    // If line ends with ;, it's the end of a query
                    if (substr(trim($line), -1) == ';') {
                        $queries[] = $current_query;
                        $current_query = '';
                    }
                }
                
                // Add any remaining query
                if (!empty(trim($current_query))) {
                    $queries[] = $current_query;
                }
                
                // Begin transaction
                mysqli_autocommit($conn, false);
                
                // Disable foreign key checks before restore
                mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
                
                $success = true;
                $error_messages = [];
                $warning_count = 0;
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        // Skip queries that might be problematic
                        if (stripos($query, 'FOREIGN_KEY_CHECKS') !== false) {
                            continue;
                        }
                        
                        // Skip comments-only queries
                        if (substr(trim($query), 0, 2) == '--' || substr(trim($query), 0, 1) == '#') {
                            continue;
                        }
                        
                        // Modify CREATE TABLE queries to use IF NOT EXISTS
                        if (stripos($query, 'CREATE TABLE') !== false && stripos($query, 'IF NOT EXISTS') === false) {
                            $query = str_ireplace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $query);
                        }
                        
                        // Handle DROP TABLE queries
                        if (stripos($query, 'DROP TABLE') !== false && stripos($query, 'IF EXISTS') === false) {
                            $query = str_ireplace('DROP TABLE', 'DROP TABLE IF EXISTS', $query);
                        }
                        
                        // Execute query with error handling
                        try {
                            $result = mysqli_query($conn, $query);
                            if (!$result) {
                                $error_message = mysqli_error($conn);
                                $error_messages[] = $error_message . " in query: " . substr($query, 0, 100) . "...";
                                
                                // Only count as warning for non-critical errors
                                if (strpos($error_message, 'Duplicate entry') !== false || 
                                    strpos($error_message, 'already exists') !== false ||
                                    strpos($error_message, 'Data too long') !== false) {
                                    $warning_count++;
                                } else {
                                    $success = false;
                                }
                            }
                        } catch (Exception $e) {
                            // Log the exception but continue
                            $error_messages[] = "Exception: " . $e->getMessage() . " in query: " . substr($query, 0, 100) . "...";
                            $warning_count++;
                        }
                    }
                }
                
                // Re-enable foreign key checks
                mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
                
                // Commit or rollback transaction
                if ($success) {
                    mysqli_commit($conn);
                    
                    if (count($error_messages) > 0) {
                        $_SESSION['alert'] = showAlert('Database restored with ' . $warning_count . ' warnings.', 'warning');
                        
                        // Log detailed errors
                        $error_log = $backup_dir . '/restore_errors_' . $timestamp . '.log';
                        file_put_contents($error_log, implode("\n\n", $error_messages));
                    } else {
                        $_SESSION['alert'] = showAlert('Database restored successfully.', 'success');
                    }
                    
                    // Log action
                    logAction($_SESSION['user_id'], 'RESTORE', 'Restored database from backup: ' . $backup_file);
                } else {
                    mysqli_rollback($conn);
                    $_SESSION['alert'] = showAlert('Error restoring database. Transaction rolled back.', 'danger');
                    
                    // Log detailed errors
                    $error_log = $backup_dir . '/restore_errors_' . $timestamp . '.log';
                    file_put_contents($error_log, implode("\n\n", $error_messages));
                }
            } else {
                $_SESSION['alert'] = showAlert('Error reading backup file.', 'danger');
            }
        } else {
            $_SESSION['alert'] = showAlert('Invalid backup file.', 'danger');
        }
        
        // Redirect to avoid resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Function to import large SQL files
function importLargeFile($file_path, $conn) {
    // Get database credentials
    $db_host = DB_HOST;
    $db_user = DB_USER;
    $db_pass = DB_PASS;
    $db_name = DB_NAME;
    
    // Disable foreign key checks first
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
    
    // Try to preprocess the file to add IF NOT EXISTS to CREATE TABLE statements
    $temp_file = null;
    if (function_exists('exec') && is_readable($file_path)) {
        $temp_file = tempnam(sys_get_temp_dir(), 'sql_import_');
        if ($temp_file) {
            // Read and process the file
            $content = file_get_contents($file_path);
            if ($content) {
                // Add IF NOT EXISTS to CREATE TABLE statements
                $content = preg_replace('/CREATE TABLE\s+(`[^`]+`|[^\s]+)/i', 'CREATE TABLE IF NOT EXISTS $1', $content);
                // Add IF EXISTS to DROP TABLE statements
                $content = preg_replace('/DROP TABLE\s+(?!IF EXISTS)(`[^`]+`|[^\s]+)/i', 'DROP TABLE IF EXISTS $1', $content);
                file_put_contents($temp_file, $content);
                
                // Use the processed file
                $file_path = $temp_file;
            }
        }
    }
    
    // Try to use mysql command line tool for large files
    if (function_exists('exec')) {
        // Create a temporary error log file
        $error_log_file = tempnam(sys_get_temp_dir(), 'mysql_error_');
        
        // Redirect stderr to the error log file
        $command = "mysql --host=$db_host --user=$db_user " . 
                  ($db_pass ? "--password='$db_pass' " : "") . 
                  "$db_name < \"$file_path\" 2> \"$error_log_file\"";
        
        exec($command, $output, $return_var);
        
        // Check for errors
        $success = ($return_var === 0);
        if (!$success && file_exists($error_log_file)) {
            $error_content = file_get_contents($error_log_file);
            if (!empty($error_content)) {
                // Log the error but don't fail if it's just about tables already existing
                if (strpos($error_content, 'already exists') !== false || 
                    strpos($error_content, 'Duplicate entry') !== false) {
                    $success = true;
                    error_log("Non-critical MySQL errors during import: " . $error_content);
                } else {
                    error_log("MySQL import errors: " . $error_content);
                }
            }
        }
        
        // Clean up temp files
        if ($temp_file && file_exists($temp_file)) {
            unlink($temp_file);
        }
        if (file_exists($error_log_file)) {
            unlink($error_log_file);
        }
        
        // Re-enable foreign key checks
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
        
        return $success;
    }
    
    // If exec is not available, try to process the file in chunks
    $handle = fopen($file_path, 'r');
    if ($handle) {
        $query = '';
        $success = true;
        
        while (($line = fgets($handle)) !== false) {
            // Skip comments and empty lines
            if (substr(trim($line), 0, 2) == '--' || trim($line) == '' || substr(trim($line), 0, 1) == '#') {
                continue;
            }
            
            $query .= $line;
            
            // If line ends with ;, it's the end of a query
            if (substr(trim($line), -1) == ';') {
                // Modify CREATE TABLE queries to use IF NOT EXISTS
                if (stripos($query, 'CREATE TABLE') !== false && stripos($query, 'IF NOT EXISTS') === false) {
                    $query = str_ireplace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $query);
                }
                
                // Handle DROP TABLE queries
                if (stripos($query, 'DROP TABLE') !== false && stripos($query, 'IF EXISTS') === false) {
                    $query = str_ireplace('DROP TABLE', 'DROP TABLE IF EXISTS', $query);
                }
                
                // Execute the query
                if (!mysqli_query($conn, $query)) {
                    // Log error but continue
                    error_log("Error importing SQL: " . mysqli_error($conn) . " in query: " . substr($query, 0, 100));
                }
                $query = '';
            }
        }
        
        fclose($handle);
        
        // Clean up temp file if created
        if ($temp_file && file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        // Re-enable foreign key checks
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
        
        return $success;
    }
    
    // Clean up temp file if created
    if ($temp_file && file_exists($temp_file)) {
        unlink($temp_file);
    }
    
    return false;
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
        <!-- Database Actions -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-download me-1"></i> Database Backup
                </div>
                <div class="card-body">
                    <p>Create a complete backup of your database. This will export all tables, data, and structure.</p>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="d-grid gap-2">
                            <button type="submit" name="backup_db" class="btn btn-primary">
                                <i class="fas fa-download me-1"></i> Create Database Backup
                            </button>
                            <a href="modules/admin/export_sql.php" class="btn btn-outline-primary">
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
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
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
                                <a href="modules/admin/database.php?restore=<?php echo urlencode($backup['name']); ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to restore this backup? Current data may be overwritten.');">
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
<?php
/**
 * Automatic Database Backup Cron Script
 * 
 * This script handles automatic database backups based on the configured schedule.
 * It should be run via a cron job at the appropriate frequency (e.g., daily).
 */

// Set script as CLI application
define('CLI_SCRIPT', true);

// Include configuration
$relative_path = __DIR__ . '/';
require_once $relative_path . 'includes/config.php';

// Connect to database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    echo "Error: Unable to connect to database.\n";
    exit(1);
}

// Load backup settings
$backup_settings = [];
$settings_query = "SELECT * FROM system_settings WHERE setting_key = 'backup_settings'";
$settings_result = mysqli_query($conn, $settings_query);
if ($settings_result && mysqli_num_rows($settings_result) > 0) {
    $settings_row = mysqli_fetch_assoc($settings_result);
    $backup_settings = json_decode($settings_row['setting_value'], true);
} else {
    echo "Error: Backup settings not found.\n";
    exit(1);
}

// Check if automatic backups are enabled
if (!isset($backup_settings['enabled']) || !$backup_settings['enabled']) {
    echo "Automatic backups are disabled. Exiting.\n";
    exit(0);
}

// Check if it's time to run a backup based on frequency
$should_run = false;
$last_backup = isset($backup_settings['last_backup']) ? $backup_settings['last_backup'] : 0;
$current_time = time();

switch ($backup_settings['frequency']) {
    case 'daily':
        // Run if last backup was more than 24 hours ago
        $should_run = ($current_time - $last_backup) >= 86400;
        break;
    case 'weekly':
        // Run if last backup was more than 7 days ago
        $should_run = ($current_time - $last_backup) >= 604800;
        break;
    case 'monthly':
        // Run if last backup was more than 30 days ago
        $should_run = ($current_time - $last_backup) >= 2592000;
        break;
    default:
        // Default to daily
        $should_run = ($current_time - $last_backup) >= 86400;
}

if (!$should_run) {
    echo "No backup needed at this time. Last backup was " . date('Y-m-d H:i:s', $last_backup) . "\n";
    exit(0);
}

// Create backups directory if it doesn't exist
$backup_dir = $relative_path . 'backups';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Helper function to create a database backup
function createDatabaseBackup($conn, $backup_dir) {
    // Generate filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "auto_backup_{$timestamp}.sql";
    $backup_file = $backup_dir . '/' . $filename;
    
    // Get database credentials
    $db_host = DB_HOST;
    $db_user = DB_USER;
    $db_pass = DB_PASS;
    $db_name = DB_NAME;
    
    // Try to use mysqldump if available (much faster and more reliable for large databases)
    if (function_exists('exec')) {
        $command = "mysqldump --host=$db_host --user=$db_user " . ($db_pass ? "--password=$db_pass " : "") . "$db_name > \"$backup_file\"";
        exec($command, $output, $return_var);
        
        if ($return_var === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
            return $filename;
        }
    }
    
    // If exec fails or is not available, use PHP to export
    $output = fopen($backup_file, 'w');
    if ($output === false) {
        return false;
    }
    
    // Write header
    fwrite($output, "-- LocalEnroll Pro Automatic Database Backup\n");
    fwrite($output, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($output, "-- Database: " . $db_name . "\n\n");
    fwrite($output, "SET FOREIGN_KEY_CHECKS=0;\n\n");
    
    // Get all tables
    $tables = [];
    $result = mysqli_query($conn, 'SHOW TABLES');
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }
    
    // Export each table
    foreach ($tables as $table) {
        // Table structure
        $result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $row = mysqli_fetch_row($result);
        fwrite($output, "-- Table structure for table `$table`\n");
        fwrite($output, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($output, $row[1] . ";\n\n");
        
        // Table data
        $result = mysqli_query($conn, "SELECT * FROM `$table`");
        $num_fields = mysqli_num_fields($result);
        $num_rows = mysqli_num_rows($result);
        
        if ($num_rows > 0) {
            fwrite($output, "-- Dumping data for table `$table`\n");
            
            // Get column names
            $fields = [];
            for ($i = 0; $i < $num_fields; $i++) {
                $field_info = mysqli_fetch_field_direct($result, $i);
                $fields[] = "`" . $field_info->name . "`";
            }
            
            // Insert statements
            $insert_header = "INSERT INTO `$table` (" . implode(', ', $fields) . ") VALUES\n";
            fwrite($output, $insert_header);
            
            $counter = 0;
            while ($row = mysqli_fetch_row($result)) {
                if ($counter % 100 == 0 && $counter > 0) {
                    fwrite($output, ";\n" . $insert_header);
                } else if ($counter > 0) {
                    fwrite($output, ",\n");
                }
                
                fwrite($output, "(");
                for ($i = 0; $i < $num_fields; $i++) {
                    if ($row[$i] === null) {
                        fwrite($output, "NULL");
                    } else {
                        fwrite($output, "'" . mysqli_real_escape_string($conn, $row[$i]) . "'");
                    }
                    
                    if ($i < ($num_fields - 1)) {
                        fwrite($output, ", ");
                    }
                }
                fwrite($output, ")");
                $counter++;
            }
            
            if ($counter > 0) {
                fwrite($output, ";\n");
            }
            fwrite($output, "\n");
        }
    }
    
    fwrite($output, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($output);
    
    return $filename;
}

// Create backup
echo "Creating automatic database backup...\n";
$backup_file = createDatabaseBackup($conn, $backup_dir);

if ($backup_file) {
    echo "Backup created successfully: $backup_file\n";
    
    // Update last backup timestamp
    $backup_settings['last_backup'] = time();
    $settings_json = json_encode($backup_settings);
    
    $update_query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = 'backup_settings'";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "s", $settings_json);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "Backup settings updated.\n";
    } else {
        echo "Error updating backup settings: " . mysqli_error($conn) . "\n";
    }
    
    // Log action
    $log_query = "INSERT INTO activity_log (user_id, action_type, action_description, action_timestamp) 
                  VALUES (0, 'AUTO_BACKUP', 'Automatic database backup created: $backup_file', NOW())";
    mysqli_query($conn, $log_query);
    
    // Clean up old backups based on retention policy
    $retention_days = isset($backup_settings['retention']) ? (int)$backup_settings['retention'] : 30;
    $retention_seconds = $retention_days * 86400;
    $cutoff_time = time() - $retention_seconds;
    
    echo "Cleaning up old backups (retention: $retention_days days)...\n";
    $deleted_count = 0;
    
    if (is_dir($backup_dir)) {
        $files = scandir($backup_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql' && strpos($file, 'auto_backup_') === 0) {
                $file_path = $backup_dir . '/' . $file;
                $file_time = filemtime($file_path);
                
                if ($file_time < $cutoff_time) {
                    if (unlink($file_path)) {
                        echo "Deleted old backup: $file\n";
                        $deleted_count++;
                    }
                }
            }
        }
    }
    
    echo "Cleanup complete. $deleted_count old backups deleted.\n";
} else {
    echo "Error creating backup.\n";
    exit(1);
}

echo "Automatic backup process completed successfully.\n";
exit(0);
?> 
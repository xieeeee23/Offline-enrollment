<?php
/**
 * Database Maintenance Script
 * 
 * This script performs regular database maintenance tasks:
 * - Optimize tables
 * - Check for data integrity issues
 * - Clean up old logs
 * - Backup database structure
 * - Generate maintenance report
 */

// Include database connection
require_once 'includes/config.php';

// Set execution time limit for maintenance tasks
set_time_limit(300); // 5 minutes

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Maintenance</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .progress { height: 25px; }
    </style>
</head>
<body>
<div class='container mt-4'>
    <h1 class='mb-4'>Database Maintenance Report</h1>
    <p class='text-muted'>Generated on: " . date('F d, Y H:i:s') . "</p>";

// Function to log maintenance activity
function logMaintenanceActivity($action, $description, $status) {
    try {
        $db = getDB();
        $db->insert('logs', [
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            'action' => 'MAINTENANCE_' . strtoupper($action),
            'description' => $description,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("Failed to log maintenance activity: " . $e->getMessage());
    }
}

// Function to execute maintenance task with progress
function executeMaintenanceTask($task_name, $callback) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'>";
    echo "<h5 class='mb-0'>$task_name</h5>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    try {
        $start_time = microtime(true);
        $result = $callback();
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        if ($result !== false) {
            echo "<p class='success'>✓ Completed successfully in {$duration}s</p>";
            logMaintenanceActivity($task_name, "Completed successfully in {$duration}s", 'success');
        } else {
            echo "<p class='error'>✗ Failed to complete</p>";
            logMaintenanceActivity($task_name, "Failed to complete", 'error');
        }
        
        return $result;
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        logMaintenanceActivity($task_name, "Error: " . $e->getMessage(), 'error');
        return false;
    }
    
    echo "</div></div>";
}

// 1. Database Health Check
echo "<h2>1. Database Health Check</h2>";
$health_check = executeMaintenanceTask("Database Health Check", function() {
    $db = getDB();
    $result = $db->query("SELECT 1");
    return $result !== false;
});

if ($health_check) {
    echo "<div class='alert alert-success'>Database connection is healthy</div>";
} else {
    echo "<div class='alert alert-danger'>Database connection failed</div>";
    exit;
}

// 2. Get Database Statistics
echo "<h2>2. Database Statistics</h2>";
$stats = executeMaintenanceTask("Database Statistics", function() {
    return getDatabaseStats();
});

if ($stats) {
    echo "<div class='row'>";
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<h5>Database Size</h5>";
    echo "<h3 class='text-primary'>" . ($stats['size'] ?? 'Unknown') . " MB</h3>";
    echo "</div></div></div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<h5>Connection Status</h5>";
    echo "<h3 class='text-" . ($stats['connection_status'] === 'healthy' ? 'success' : 'danger') . "'>" . ucfirst($stats['connection_status']) . "</h3>";
    echo "</div></div></div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<h5>Total Tables</h5>";
    echo "<h3 class='text-info'>" . count($stats['tables']) . "</h3>";
    echo "</div></div></div>";
    echo "</div>";
}

// 3. Table Optimization
echo "<h2>3. Table Optimization</h2>";
$optimization_results = executeMaintenanceTask("Table Optimization", function() {
    return optimizeDatabase();
});

if ($optimization_results) {
    echo "<div class='table-responsive'>";
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Table</th><th>Status</th></tr></thead>";
    echo "<tbody>";
    foreach ($optimization_results as $table => $status) {
        $status_class = $status === 'success' ? 'success' : 'danger';
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td><span class='text-$status_class'>" . ucfirst($status) . "</span></td>";
        echo "</tr>";
    }
    echo "</tbody></table></div>";
}

// 4. Data Integrity Check
echo "<h2>4. Data Integrity Check</h2>";
$integrity_issues = executeMaintenanceTask("Data Integrity Check", function() {
    $db = getDB();
    $issues = [];
    
    // Check for orphaned records
    $orphaned_requirements = $db->fetchValue(
        "SELECT COUNT(*) FROM student_requirements sr 
         LEFT JOIN students s ON sr.student_id = s.id 
         WHERE s.id IS NULL"
    );
    if ($orphaned_requirements > 0) {
        $issues[] = "$orphaned_requirements orphaned requirement records";
    }
    
    // Check for invalid enrollment statuses
    $invalid_statuses = $db->fetchValue(
        "SELECT COUNT(*) FROM students 
         WHERE enrollment_status NOT IN ('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated')"
    );
    if ($invalid_statuses > 0) {
        $issues[] = "$invalid_statuses students with invalid enrollment status";
    }
    
    // Check for duplicate LRNs
    $duplicate_lrns = $db->fetchValue(
        "SELECT COUNT(*) FROM (
            SELECT lrn, COUNT(*) as count 
            FROM students 
            GROUP BY lrn 
            HAVING count > 1
        ) as duplicates"
    );
    if ($duplicate_lrns > 0) {
        $issues[] = "$duplicate_lrns duplicate LRN values";
    }
    
    // Check for empty required fields
    $empty_lrns = $db->fetchValue(
        "SELECT COUNT(*) FROM students WHERE lrn = '' OR lrn IS NULL"
    );
    if ($empty_lrns > 0) {
        $issues[] = "$empty_lrns students with empty LRN";
    }
    
    return $issues;
});

if ($integrity_issues) {
    if (empty($integrity_issues)) {
        echo "<div class='alert alert-success'>No data integrity issues found</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h5>Data Integrity Issues Found:</h5>";
        echo "<ul>";
        foreach ($integrity_issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

// 5. Clean Up Old Logs
echo "<h2>5. Log Cleanup</h2>";
$log_cleanup = executeMaintenanceTask("Log Cleanup", function() {
    $db = getDB();
    
    // Delete logs older than 90 days
    $deleted_count = $db->delete('logs', 'timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)');
    
    return $deleted_count;
});

if ($log_cleanup !== false) {
    echo "<div class='alert alert-info'>Cleaned up $log_cleanup old log records</div>";
}

// 6. Backup Database Structure
echo "<h2>6. Database Structure Backup</h2>";
$backup_result = executeMaintenanceTask("Database Structure Backup", function() {
    return backupDatabaseStructure();
});

if ($backup_result) {
    echo "<div class='alert alert-success'>Database structure backed up to: $backup_result</div>";
} else {
    echo "<div class='alert alert-warning'>Failed to create database backup</div>";
}

// 7. Performance Analysis
echo "<h2>7. Performance Analysis</h2>";
$performance_analysis = executeMaintenanceTask("Performance Analysis", function() {
    $db = getDB();
    
    $analysis = [];
    
    // Check for slow queries (if slow query log is enabled)
    $slow_queries = $db->fetchValue("SHOW VARIABLES LIKE 'slow_query_log'");
    if ($slow_queries) {
        $analysis[] = "Slow query log is enabled";
    }
    
    // Check table sizes
    $large_tables = $db->fetchAll(
        "SELECT table_name, 
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
         FROM information_schema.tables 
         WHERE table_schema = ? 
         AND (data_length + index_length) > 10485760
         ORDER BY (data_length + index_length) DESC",
        [DB_NAME]
    );
    
    if ($large_tables) {
        $analysis[] = "Large tables found:";
        foreach ($large_tables as $table) {
            $analysis[] = "- {$table['table_name']}: {$table['size_mb']} MB";
        }
    }
    
    // Check for missing indexes
    $tables_without_indexes = $db->fetchAll(
        "SELECT table_name 
         FROM information_schema.tables t
         LEFT JOIN information_schema.statistics s 
            ON t.table_name = s.table_name 
            AND t.table_schema = s.table_schema
         WHERE t.table_schema = ? 
         AND s.index_name IS NULL
         GROUP BY t.table_name",
        [DB_NAME]
    );
    
    if ($tables_without_indexes) {
        $analysis[] = "Tables without indexes:";
        foreach ($tables_without_indexes as $table) {
            $analysis[] = "- {$table['table_name']}";
        }
    }
    
    return $analysis;
});

if ($performance_analysis) {
    if (empty($performance_analysis)) {
        echo "<div class='alert alert-success'>No performance issues detected</div>";
    } else {
        echo "<div class='alert alert-info'>";
        echo "<h5>Performance Analysis Results:</h5>";
        echo "<ul>";
        foreach ($performance_analysis as $item) {
            echo "<li>$item</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

// 8. Security Check
echo "<h2>8. Security Check</h2>";
$security_check = executeMaintenanceTask("Security Check", function() {
    $db = getDB();
    
    $security_issues = [];
    
    // Check for users with default passwords
    $default_password_users = $db->fetchValue(
        "SELECT COUNT(*) FROM users WHERE password = 'password' OR password = '123456'"
    );
    if ($default_password_users > 0) {
        $security_issues[] = "$default_password_users users with default passwords";
    }
    
    // Check for inactive users
    $inactive_users = $db->fetchValue(
        "SELECT COUNT(*) FROM users WHERE status = 'inactive'"
    );
    if ($inactive_users > 0) {
        $security_issues[] = "$inactive_users inactive user accounts";
    }
    
    // Check for users without last login
    $no_login_users = $db->fetchValue(
        "SELECT COUNT(*) FROM users WHERE last_login IS NULL"
    );
    if ($no_login_users > 0) {
        $security_issues[] = "$no_login_users users who have never logged in";
    }
    
    return $security_issues;
});

if ($security_check) {
    if (empty($security_check)) {
        echo "<div class='alert alert-success'>No security issues detected</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h5>Security Issues Found:</h5>";
        echo "<ul>";
        foreach ($security_check as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

// 9. Maintenance Summary
echo "<h2>9. Maintenance Summary</h2>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5>Maintenance Completed Successfully</h5>";
echo "<p>The database maintenance has been completed. Here are the key points:</p>";
echo "<ul>";
echo "<li>Database health: " . ($health_check ? 'Good' : 'Poor') . "</li>";
echo "<li>Table optimization: " . ($optimization_results ? 'Completed' : 'Failed') . "</li>";
echo "<li>Data integrity: " . (empty($integrity_issues) ? 'Good' : 'Issues found') . "</li>";
echo "<li>Log cleanup: " . ($log_cleanup !== false ? 'Completed' : 'Failed') . "</li>";
echo "<li>Backup creation: " . ($backup_result ? 'Successful' : 'Failed') . "</li>";
echo "</ul>";

// Recommendations
echo "<h6>Recommendations:</h6>";
echo "<ul>";
echo "<li>Run this maintenance script weekly for optimal performance</li>";
echo "<li>Monitor database size and consider archiving old data if needed</li>";
echo "<li>Review security issues and address them promptly</li>";
echo "<li>Consider implementing automated backups</li>";
echo "<li>Monitor query performance and add indexes as needed</li>";
echo "</ul>";

echo "</div></div>";

// Log completion
logMaintenanceActivity('COMPLETE', 'Database maintenance completed successfully', 'success');

echo "<div class='mt-4'>";
echo "<a href='dashboard.php' class='btn btn-primary'>Return to Dashboard</a>";
echo "<a href='fix_database_management.php' class='btn btn-secondary ms-2'>Run Database Fix</a>";
echo "</div>";

echo "</div>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?> 
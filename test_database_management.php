<?php
/**
 * Database Management Test Script
 * 
 * This script tests the database management improvements to ensure they work correctly.
 */

// Include database connection
require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Management Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
<div class='container mt-4'>
    <h1 class='mb-4'>Database Management Test Results</h1>
    <p class='text-muted'>Test run on: " . date('F d, Y H:i:s') . "</p>";

// Function to run test and report results
function runTest($test_name, $callback) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header'>";
    echo "<h5 class='mb-0'>$test_name</h5>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    try {
        $start_time = microtime(true);
        $result = $callback();
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 3);
        
        if ($result === true || $result !== false) {
            echo "<p class='success'>✓ PASSED ({$duration}s)</p>";
            return true;
        } else {
            echo "<p class='error'>✗ FAILED</p>";
            return false;
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        return false;
    }
    
    echo "</div></div>";
}

$test_results = [];

// Test 1: Database Connection
echo "<h2>1. Database Connection Tests</h2>";

$test_results['connection'] = runTest("Basic Database Connection", function() {
    $db = getDB();
    $result = $db->query("SELECT 1 as test");
    return $result && $result->num_rows > 0;
});

$test_results['connection_pool'] = runTest("Connection Pooling", function() {
    $db1 = getDB();
    $db2 = getDB();
    return $db1 === $db2; // Should be same instance
});

$test_results['health_check'] = runTest("Database Health Check", function() {
    return checkDatabaseHealth();
});

// Test 2: Database Class Methods
echo "<h2>2. Database Class Method Tests</h2>";

$test_results['fetch_one'] = runTest("fetchOne Method", function() {
    $db = getDB();
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM students");
    return $result && isset($result['count']);
});

$test_results['fetch_all'] = runTest("fetchAll Method", function() {
    $db = getDB();
    $result = $db->fetchAll("SELECT id, first_name FROM students LIMIT 5");
    return is_array($result);
});

$test_results['fetch_value'] = runTest("fetchValue Method", function() {
    $db = getDB();
    $result = $db->fetchValue("SELECT COUNT(*) FROM students");
    return is_numeric($result);
});

$test_results['prepared_statements'] = runTest("Prepared Statements", function() {
    $db = getDB();
    $result = $db->fetchOne("SELECT id FROM students WHERE id = ?", [1]);
    return $result !== false;
});

// Test 3: Table Structure Tests
echo "<h2>3. Table Structure Tests</h2>";

$test_results['table_exists'] = runTest("Table Existence Check", function() {
    $db = getDB();
    return $db->tableExists('students') && 
           $db->tableExists('users') && 
           $db->tableExists('student_requirements');
});

$test_results['column_exists'] = runTest("Column Existence Check", function() {
    $db = getDB();
    return $db->columnExists('students', 'enrollment_status') &&
           $db->columnExists('students', 'lrn') &&
           $db->columnExists('students', 'has_voucher');
});

$test_results['table_structure'] = runTest("Table Structure Retrieval", function() {
    $db = getDB();
    $structure = $db->getTableStructure('students');
    return is_array($structure) && count($structure) > 0;
});

// Test 4: Data Integrity Tests
echo "<h2>4. Data Integrity Tests</h2>";

$test_results['enrollment_status'] = runTest("Enrollment Status Validation", function() {
    $db = getDB();
    $invalid_count = $db->fetchValue(
        "SELECT COUNT(*) FROM students 
         WHERE enrollment_status NOT IN ('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated')"
    );
    return $invalid_count == 0;
});

$test_results['lrn_uniqueness'] = runTest("LRN Uniqueness", function() {
    $db = getDB();
    $duplicate_count = $db->fetchValue(
        "SELECT COUNT(*) FROM (
            SELECT lrn, COUNT(*) as count 
            FROM students 
            GROUP BY lrn 
            HAVING count > 1
        ) as duplicates"
    );
    return $duplicate_count == 0;
});

$test_results['foreign_keys'] = runTest("Foreign Key Constraints", function() {
    $db = getDB();
    $orphaned_count = $db->fetchValue(
        "SELECT COUNT(*) FROM student_requirements sr 
         LEFT JOIN students s ON sr.student_id = s.id 
         WHERE s.id IS NULL"
    );
    return $orphaned_count == 0;
});

// Test 5: Performance Tests
echo "<h2>5. Performance Tests</h2>";

$test_results['indexes'] = runTest("Performance Indexes", function() {
    $db = getDB();
    $indexes = $db->fetchAll(
        "SELECT INDEX_NAME FROM information_schema.statistics 
         WHERE table_schema = ? AND table_name = 'students'",
        [DB_NAME]
    );
    return count($indexes) >= 5; // Should have at least 5 indexes
});

$test_results['query_performance'] = runTest("Query Performance", function() {
    $db = getDB();
    $start_time = microtime(true);
    
    // Run a complex query
    $result = $db->fetchAll(
        "SELECT s.*, sr.birth_certificate 
         FROM students s 
         LEFT JOIN student_requirements sr ON s.id = sr.student_id 
         WHERE s.enrollment_status = ? 
         ORDER BY s.last_name, s.first_name 
         LIMIT 100",
        ['enrolled']
    );
    
    $end_time = microtime(true);
    $duration = $end_time - $start_time;
    
    return $duration < 1.0; // Should complete in less than 1 second
});

// Test 6: Transaction Tests
echo "<h2>6. Transaction Tests</h2>";

$test_results['transactions'] = runTest("Transaction Support", function() {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // Test insert
        $test_data = [
            'first_name' => 'Test',
            'last_name' => 'Student',
            'lrn' => 'TEST' . time(),
            'dob' => '2000-01-01',
            'gender' => 'Male',
            'contact_number' => '1234567890',
            'grade_level' => 'Grade 11',
            'enrollment_status' => 'pending'
        ];
        
        $id = $db->insert('students', $test_data);
        
        if (!$id) {
            throw new Exception("Insert failed");
        }
        
        // Test update
        $affected = $db->update('students', 
            ['enrollment_status' => 'enrolled'], 
            'id = ?', 
            [$id]
        );
        
        if ($affected != 1) {
            throw new Exception("Update failed");
        }
        
        // Test delete
        $deleted = $db->delete('students', 'id = ?', [$id]);
        
        if ($deleted != 1) {
            throw new Exception("Delete failed");
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
});

// Test 7: Error Handling Tests
echo "<h2>7. Error Handling Tests</h2>";

$test_results['error_handling'] = runTest("Error Handling", function() {
    $db = getDB();
    
    try {
        // This should fail
        $db->query("SELECT * FROM non_existent_table");
        return false; // Should not reach here
    } catch (Exception $e) {
        return true; // Expected to throw exception
    }
});

$test_results['sql_injection_prevention'] = runTest("SQL Injection Prevention", function() {
    $db = getDB();
    
    $malicious_input = "'; DROP TABLE students; --";
    
    try {
        $result = $db->fetchOne(
            "SELECT * FROM students WHERE first_name = ?", 
            [$malicious_input]
        );
        return true; // Should not cause any damage
    } catch (Exception $e) {
        return false;
    }
});

// Test 8: Utility Function Tests
echo "<h2>8. Utility Function Tests</h2>";

$test_results['database_stats'] = runTest("Database Statistics", function() {
    $stats = getDatabaseStats();
    return is_array($stats) && 
           isset($stats['size']) && 
           isset($stats['connection_status']) &&
           $stats['connection_status'] === 'healthy';
});

$test_results['backup_function'] = runTest("Backup Function", function() {
    $backup_file = backupDatabaseStructure();
    return $backup_file && file_exists($backup_file);
});

// Test 9: Security Tests
echo "<h2>9. Security Tests</h2>";

$test_results['password_security'] = runTest("Password Security Check", function() {
    $db = getDB();
    $weak_passwords = $db->fetchValue(
        "SELECT COUNT(*) FROM users WHERE password IN ('password', '123456', 'admin')"
    );
    return $weak_passwords == 0;
});

$test_results['user_activity_logging'] = runTest("User Activity Logging", function() {
    $db = getDB();
    
    // Test logging
    $log_id = $db->insert('logs', [
        'user_id' => 1,
        'action' => 'TEST',
        'description' => 'Test log entry',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    return $log_id > 0;
});

// Test Summary
echo "<h2>Test Summary</h2>";

$total_tests = count($test_results);
$passed_tests = count(array_filter($test_results));
$failed_tests = $total_tests - $passed_tests;

echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-body text-center'>";
echo "<h5>Total Tests</h5>";
echo "<h3 class='text-info'>$total_tests</h3>";
echo "</div></div></div>";

echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-body text-center'>";
echo "<h5>Passed</h5>";
echo "<h3 class='text-success'>$passed_tests</h3>";
echo "</div></div></div>";

echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-body text-center'>";
echo "<h5>Failed</h5>";
echo "<h3 class='text-danger'>$failed_tests</h3>";
echo "</div></div></div>";
echo "</div>";

$success_rate = round(($passed_tests / $total_tests) * 100, 1);

echo "<div class='mt-3'>";
echo "<div class='progress'>";
echo "<div class='progress-bar bg-success' style='width: {$success_rate}%'>";
echo "$success_rate%";
echo "</div>";
echo "</div>";
echo "</div>";

if ($failed_tests > 0) {
    echo "<div class='alert alert-warning mt-3'>";
    echo "<h5>Failed Tests:</h5>";
    echo "<ul>";
    foreach ($test_results as $test => $result) {
        if (!$result) {
            echo "<li>" . ucwords(str_replace('_', ' ', $test)) . "</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
}

if ($passed_tests == $total_tests) {
    echo "<div class='alert alert-success mt-3'>";
    echo "<h5>🎉 All Tests Passed!</h5>";
    echo "<p>The database management improvements are working correctly.</p>";
    echo "</div>";
}

// Recommendations
echo "<h2>Recommendations</h2>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5>Next Steps:</h5>";
echo "<ul>";
echo "<li>Run <code>fix_database_management.php</code> to apply any missing fixes</li>";
echo "<li>Schedule <code>database_maintenance.php</code> to run weekly</li>";
echo "<li>Monitor database performance regularly</li>";
echo "<li>Review security reports monthly</li>";
echo "<li>Keep database backups current</li>";
echo "</ul>";
echo "</div></div>";

echo "<div class='mt-4'>";
echo "<a href='dashboard.php' class='btn btn-primary'>Return to Dashboard</a>";
echo "<a href='fix_database_management.php' class='btn btn-secondary ms-2'>Run Database Fix</a>";
echo "<a href='database_maintenance.php' class='btn btn-info ms-2'>Run Maintenance</a>";
echo "</div>";

echo "</div>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?> 
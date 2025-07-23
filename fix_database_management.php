<?php
/**
 * Comprehensive Database Management Fix Script
 * 
 * This script fixes various database management issues found in the SHS Enrollment System:
 * 1. Table structure inconsistencies
 * 2. Missing foreign key constraints
 * 3. Data type mismatches
 * 4. Missing indexes
 * 5. Data integrity issues
 * 6. Enum value inconsistencies
 */

// Include database connection
require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Management Fix</title>
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
    <h1 class='mb-4'>Database Management Fix Report</h1>";

// Function to execute SQL and return result
function executeSQL($conn, $sql, $description) {
    echo "<div class='mb-3'>";
    echo "<h5>$description</h5>";
    echo "<pre>$sql</pre>";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $affected = mysqli_affected_rows($conn);
        echo "<p class='success'>✓ Success: $affected rows affected</p>";
        return true;
    } else {
        echo "<p class='error'>✗ Error: " . mysqli_error($conn) . "</p>";
        return false;
    }
    echo "</div>";
}

// Function to check if table exists
function tableExists($conn, $table_name) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
    return mysqli_num_rows($result) > 0;
}

// Function to check if column exists
function columnExists($conn, $table_name, $column_name) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table_name LIKE '$column_name'");
    return mysqli_num_rows($result) > 0;
}

// Function to get column definition
function getColumnDefinition($conn, $table_name, $column_name) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table_name LIKE '$column_name'");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

echo "<h2>1. Database Connection Test</h2>";
if ($conn) {
    echo "<p class='success'>✓ Database connection successful</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
} else {
    echo "<p class='error'>✗ Database connection failed: " . mysqli_connect_error() . "</p>";
    exit;
}

echo "<h2>2. Fix Students Table Structure</h2>";

// Fix enrollment_status enum values
$status_fix_sql = "ALTER TABLE students MODIFY COLUMN enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated') DEFAULT 'pending'";
executeSQL($conn, $status_fix_sql, "Updating enrollment_status enum values");

// Add missing columns if they don't exist
$missing_columns = [
    'has_voucher' => "ALTER TABLE students ADD COLUMN has_voucher TINYINT(1) NOT NULL DEFAULT 0 AFTER enrollment_status",
    'voucher_number' => "ALTER TABLE students ADD COLUMN voucher_number VARCHAR(50) DEFAULT NULL AFTER has_voucher",
    'strand' => "ALTER TABLE students ADD COLUMN strand VARCHAR(50) DEFAULT NULL AFTER section",
    'guardian_name' => "ALTER TABLE students ADD COLUMN guardian_name VARCHAR(100) DEFAULT NULL AFTER strand",
    'guardian_contact' => "ALTER TABLE students ADD COLUMN guardian_contact VARCHAR(20) DEFAULT NULL AFTER guardian_name",
    'student_type' => "ALTER TABLE students ADD COLUMN student_type ENUM('new', 'old') DEFAULT 'new' AFTER guardian_contact"
];

foreach ($missing_columns as $column => $sql) {
    if (!columnExists($conn, 'students', $column)) {
        executeSQL($conn, $sql, "Adding missing column: $column");
    } else {
        echo "<p class='info'>ℹ Column '$column' already exists</p>";
    }
}

// Fix data type issues
$data_type_fixes = [
    "ALTER TABLE students MODIFY COLUMN contact_number VARCHAR(20) NOT NULL",
    "ALTER TABLE students MODIFY COLUMN email VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE students MODIFY COLUMN photo VARCHAR(255) DEFAULT NULL"
];

foreach ($data_type_fixes as $sql) {
    executeSQL($conn, $sql, "Fixing data types");
}

echo "<h2>3. Fix Student Requirements Table</h2>";

// Clean up corrupted column names in student_requirements table
$cleanup_columns = [
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `2x2_____icture`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `2x2_____icture_file`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `8iyyi`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `8iyyi_file`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `_ood__oral__ertificate`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `_ood__oral__ertificate_file`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `_arent__uardian___`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `_arent__uardian____file`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `_eport__ard____orm_138`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `_eport__ard____orm_138_file`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `iuyui`",
    "ALTER TABLE student_requirements DROP COLUMN IF EXISTS `iuyui_file`"
];

foreach ($cleanup_columns as $sql) {
    executeSQL($conn, $sql, "Cleaning up corrupted column names");
}

// Add proper requirement columns if they don't exist
$requirement_columns = [
    '2x2_id_picture' => "ALTER TABLE student_requirements ADD COLUMN 2x2_id_picture TINYINT(1) DEFAULT 0",
    '2x2_id_picture_file' => "ALTER TABLE student_requirements ADD COLUMN 2x2_id_picture_file VARCHAR(255) DEFAULT NULL",
    'good_moral_certificate' => "ALTER TABLE student_requirements ADD COLUMN good_moral_certificate TINYINT(1) DEFAULT 0",
    'good_moral_certificate_file' => "ALTER TABLE student_requirements ADD COLUMN good_moral_certificate_file VARCHAR(255) DEFAULT NULL",
    'parent_guardian_id' => "ALTER TABLE student_requirements ADD COLUMN parent_guardian_id TINYINT(1) DEFAULT 0",
    'parent_guardian_id_file' => "ALTER TABLE student_requirements ADD COLUMN parent_guardian_id_file VARCHAR(255) DEFAULT NULL",
    'report_card___form_138' => "ALTER TABLE student_requirements ADD COLUMN report_card___form_138 TINYINT(1) DEFAULT 0",
    'report_card___form_138_file' => "ALTER TABLE student_requirements ADD COLUMN report_card___form_138_file VARCHAR(255) DEFAULT NULL"
];

foreach ($requirement_columns as $column => $sql) {
    if (!columnExists($conn, 'student_requirements', $column)) {
        executeSQL($conn, $sql, "Adding requirement column: $column");
    } else {
        echo "<p class='info'>ℹ Requirement column '$column' already exists</p>";
    }
}

echo "<h2>4. Fix Foreign Key Constraints</h2>";

// Add foreign key constraints
$foreign_keys = [
    "ALTER TABLE students ADD CONSTRAINT fk_students_enrolled_by FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL",
    "ALTER TABLE student_requirements ADD CONSTRAINT fk_student_requirements_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE",
    "ALTER TABLE enrollment_history ADD CONSTRAINT fk_enrollment_history_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE",
    "ALTER TABLE enrollment_history ADD CONSTRAINT fk_enrollment_history_enrolled_by FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL"
];

foreach ($foreign_keys as $sql) {
    executeSQL($conn, $sql, "Adding foreign key constraint");
}

echo "<h2>5. Fix Data Integrity Issues</h2>";

// Fix empty enrollment status values
$fix_empty_status = "UPDATE students SET enrollment_status = 'pending' WHERE enrollment_status = '' OR enrollment_status IS NULL";
executeSQL($conn, $fix_empty_status, "Fixing empty enrollment status values");

// Fix duplicate LRN values
$fix_duplicate_lrn = "
UPDATE students s1 
JOIN (
    SELECT lrn, MIN(id) as min_id 
    FROM students 
    GROUP BY lrn 
    HAVING COUNT(*) > 1
) s2 ON s1.lrn = s2.lrn AND s1.id > s2.min_id 
SET s1.lrn = CONCAT(s1.lrn, '_', s1.id)
";
executeSQL($conn, $fix_duplicate_lrn, "Fixing duplicate LRN values");

// Fix invalid email addresses
$fix_invalid_emails = "UPDATE students SET email = NULL WHERE email = '' OR email NOT LIKE '%@%'";
executeSQL($conn, $fix_invalid_emails, "Fixing invalid email addresses");

echo "<h2>6. Add Missing Indexes</h2>";

// Add performance indexes
$indexes = [
    "CREATE INDEX idx_students_lrn ON students(lrn)",
    "CREATE INDEX idx_students_enrollment_status ON students(enrollment_status)",
    "CREATE INDEX idx_students_grade_level ON students(grade_level)",
    "CREATE INDEX idx_students_strand ON students(strand)",
    "CREATE INDEX idx_students_section ON students(section)",
    "CREATE INDEX idx_student_requirements_student_id ON student_requirements(student_id)",
    "CREATE INDEX idx_enrollment_history_student_id ON enrollment_history(student_id)",
    "CREATE INDEX idx_enrollment_history_school_year ON enrollment_history(school_year)"
];

foreach ($indexes as $sql) {
    executeSQL($conn, $sql, "Adding performance index");
}

echo "<h2>7. Create Missing Tables</h2>";

// Create back_subjects table if it doesn't exist
if (!tableExists($conn, 'back_subjects')) {
    $create_back_subjects = "
    CREATE TABLE back_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_code VARCHAR(20) NOT NULL,
        subject_name VARCHAR(100) NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        remarks TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )";
    executeSQL($conn, $create_back_subjects, "Creating back_subjects table");
} else {
    echo "<p class='info'>ℹ back_subjects table already exists</p>";
}

// Create requirement_types table if it doesn't exist
if (!tableExists($conn, 'requirement_types')) {
    $create_requirement_types = "
    CREATE TABLE requirement_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        is_required TINYINT(1) DEFAULT 1,
        file_required TINYINT(1) DEFAULT 0,
        max_file_size INT DEFAULT 5242880,
        allowed_extensions VARCHAR(255) DEFAULT 'jpg,jpeg,png,pdf',
        display_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    executeSQL($conn, $create_requirement_types, "Creating requirement_types table");
} else {
    echo "<p class='info'>ℹ requirement_types table already exists</p>";
}

echo "<h2>8. Insert Default Data</h2>";

// Insert default requirement types
$default_requirements = [
    "INSERT IGNORE INTO requirement_types (name, description, is_required, file_required, display_order) VALUES 
    ('Birth Certificate', 'Official birth certificate from PSA', 1, 1, 1)",
    "INSERT IGNORE INTO requirement_types (name, description, is_required, file_required, display_order) VALUES 
    ('Report Card (Form 138)', 'Latest report card from previous school', 1, 1, 2)",
    "INSERT IGNORE INTO requirement_types (name, description, is_required, file_required, display_order) VALUES 
    ('Good Moral Certificate', 'Certificate of good moral character', 1, 1, 3)",
    "INSERT IGNORE INTO requirement_types (name, description, is_required, file_required, display_order) VALUES 
    ('Medical Certificate', 'Medical clearance certificate', 1, 1, 4)",
    "INSERT IGNORE INTO requirement_types (name, description, is_required, file_required, display_order) VALUES 
    ('2x2 ID Picture', 'Recent 2x2 ID picture', 1, 1, 5)",
    "INSERT IGNORE INTO requirement_types (name, description, is_required, file_required, display_order) VALUES 
    ('Enrollment Form', 'Completed enrollment form', 1, 1, 6)",
    "INSERT IGNORE INTO requirement_types (name, description, is_required, file_required, display_order) VALUES 
    ('Parent/Guardian ID', 'Valid ID of parent or guardian', 1, 1, 7)"
];

foreach ($default_requirements as $sql) {
    executeSQL($conn, $sql, "Inserting default requirement types");
}

echo "<h2>9. Database Statistics</h2>";

// Get database statistics
$stats_queries = [
    "SELECT COUNT(*) as total_students FROM students" => "Total Students",
    "SELECT COUNT(*) as enrolled_students FROM students WHERE enrollment_status = 'enrolled'" => "Enrolled Students",
    "SELECT COUNT(*) as pending_students FROM students WHERE enrollment_status = 'pending'" => "Pending Students",
    "SELECT COUNT(*) as total_users FROM users" => "Total Users",
    "SELECT COUNT(*) as total_teachers FROM teachers" => "Total Teachers",
    "SELECT COUNT(*) as total_sections FROM sections" => "Total Sections"
];

echo "<div class='row'>";
foreach ($stats_queries as $sql => $label) {
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<div class='col-md-4 mb-3'>";
        echo "<div class='card'>";
        echo "<div class='card-body text-center'>";
        echo "<h5 class='card-title'>$label</h5>";
        echo "<h2 class='text-primary'>" . $row[array_keys($row)[0]] . "</h2>";
        echo "</div></div></div>";
    }
}
echo "</div>";

echo "<h2>10. Verification Tests</h2>";

// Run verification tests
$verification_tests = [
    "SELECT COUNT(*) as count FROM students WHERE enrollment_status NOT IN ('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated')" => "Invalid enrollment status values",
    "SELECT COUNT(*) as count FROM students WHERE lrn = '' OR lrn IS NULL" => "Empty LRN values",
    "SELECT COUNT(*) as count FROM students WHERE contact_number = '' OR contact_number IS NULL" => "Empty contact numbers",
    "SELECT COUNT(*) as count FROM student_requirements WHERE student_id NOT IN (SELECT id FROM students)" => "Orphaned requirement records"
];

foreach ($verification_tests as $sql => $description) {
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row[array_keys($row)[0]];
        if ($count == 0) {
            echo "<p class='success'>✓ $description: No issues found</p>";
        } else {
            echo "<p class='warning'>⚠ $description: $count issues found</p>";
        }
    }
}

echo "<h2>11. Recommendations</h2>";
echo "<div class='alert alert-info'>";
echo "<h5>Database Management Best Practices:</h5>";
echo "<ul>";
echo "<li>Regularly backup your database</li>";
echo "<li>Monitor database performance and add indexes as needed</li>";
echo "<li>Implement proper data validation in application code</li>";
echo "<li>Use transactions for critical operations</li>";
echo "<li>Regularly check for data integrity issues</li>";
echo "<li>Keep database schema documentation updated</li>";
echo "</ul>";
echo "</div>";

echo "<div class='alert alert-success'>";
echo "<h5>Database Fix Complete!</h5>";
echo "<p>The database management issues have been addressed. The system should now be more stable and perform better.</p>";
echo "<p><a href='dashboard.php' class='btn btn-primary'>Return to Dashboard</a></p>";
echo "</div>";

echo "</div>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?> 
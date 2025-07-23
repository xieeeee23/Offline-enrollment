<?php
// Calculate the relative path to the includes directory
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

/**
 * Verify and ensure proper database connectivity between education levels, grade levels, and sections
 */

// Check if user is logged in
if (!isLoggedIn()) {
    die("Authentication required");
}

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return mysqli_num_rows($result) > 0;
}

// Function to check if a column exists in a table
function columnExists($conn, $tableName, $columnName) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return mysqli_num_rows($result) > 0;
}

// Function to check foreign key constraints
function foreignKeyExists($conn, $tableName, $columnName, $referencedTable, $referencedColumn) {
    $query = "SELECT * FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '$tableName'
              AND COLUMN_NAME = '$columnName'
              AND REFERENCED_TABLE_NAME = '$referencedTable'
              AND REFERENCED_COLUMN_NAME = '$referencedColumn'";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Start output buffering
ob_start();

echo "<h2>Database Connection Verification</h2>";

// Check database connection
if (!$conn) {
    echo "<p style='color: red;'>Database connection failed: " . mysqli_connect_error() . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>Database connection successful</p>";
}

// Check if required tables exist
$requiredTables = ['education_levels', 'sections', 'students'];
$missingTables = [];

foreach ($requiredTables as $table) {
    if (!tableExists($conn, $table)) {
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "<p style='color: red;'>Missing tables: " . implode(", ", $missingTables) . "</p>";
} else {
    echo "<p style='color: green;'>All required tables exist</p>";
}

// Check education_level_id column in students table
if (columnExists($conn, 'students', 'education_level_id')) {
    echo "<p style='color: green;'>education_level_id column exists in students table</p>";
    
    // Check foreign key constraint
    if (foreignKeyExists($conn, 'students', 'education_level_id', 'education_levels', 'id')) {
        echo "<p style='color: green;'>Foreign key constraint exists between students.education_level_id and education_levels.id</p>";
    } else {
        echo "<p style='color: orange;'>Foreign key constraint missing between students.education_level_id and education_levels.id</p>";
        
        // Add foreign key constraint if missing
        $query = "ALTER TABLE students ADD CONSTRAINT fk_student_education_level 
                  FOREIGN KEY (education_level_id) REFERENCES education_levels(id) ON DELETE SET NULL";
        if (mysqli_query($conn, $query)) {
            echo "<p style='color: green;'>Added foreign key constraint successfully</p>";
        } else {
            echo "<p style='color: red;'>Failed to add foreign key constraint: " . mysqli_error($conn) . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>education_level_id column missing in students table</p>";
}

// Check grade_level column in students table
if (columnExists($conn, 'students', 'grade_level')) {
    echo "<p style='color: green;'>grade_level column exists in students table</p>";
} else {
    echo "<p style='color: red;'>grade_level column missing in students table</p>";
}

// Check section column in students table
if (columnExists($conn, 'students', 'section')) {
    echo "<p style='color: green;'>section column exists in students table</p>";
} else {
    echo "<p style='color: red;'>section column missing in students table</p>";
}

// Verify that sections table has grade_level column
if (columnExists($conn, 'sections', 'grade_level')) {
    echo "<p style='color: green;'>grade_level column exists in sections table</p>";
} else {
    echo "<p style='color: red;'>grade_level column missing in sections table</p>";
}

// Verify education_levels table structure
$educationLevelColumns = ['id', 'level_name', 'grade_min', 'grade_max', 'display_order', 'status'];
$missingColumns = [];

foreach ($educationLevelColumns as $column) {
    if (!columnExists($conn, 'education_levels', $column)) {
        $missingColumns[] = $column;
    }
}

if (!empty($missingColumns)) {
    echo "<p style='color: red;'>Missing columns in education_levels table: " . implode(", ", $missingColumns) . "</p>";
} else {
    echo "<p style='color: green;'>All required columns exist in education_levels table</p>";
}

// Count records in each table
$tables = ['education_levels', 'sections', 'students'];
foreach ($tables as $table) {
    $query = "SELECT COUNT(*) as count FROM $table";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>$table table has " . $row['count'] . " records</p>";
    } else {
        echo "<p style='color: red;'>Failed to count records in $table table: " . mysqli_error($conn) . "</p>";
    }
}

// Output the buffer
$output = ob_get_clean();

// Return JSON response if requested via AJAX
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['html' => $output]);
    exit;
}

// Otherwise display HTML
echo $output;
?> 
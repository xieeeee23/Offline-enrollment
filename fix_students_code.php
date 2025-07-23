<?php
// This script will fix the students.php file by replacing the problematic code with a properly formatted version

// First, read the entire file
$file_path = 'modules/registrar/students.php';
$content = file_get_contents($file_path);

if ($content === false) {
    die("Error: Could not read the file $file_path");
}

// Create a backup just in case
file_put_contents($file_path . '.backup2', $content);
echo "Created backup at {$file_path}.backup2\n";

// The properly formatted code
$fixed_code = '<?php
$title = \'Senior High School Enrollment System\';
$relative_path = \'../../\';
require_once $relative_path . \'includes/header.php\';

// Check if user is logged in and has admin or registrar role
if (!checkAccess([\'admin\', \'registrar\'])) {
    $_SESSION[\'alert\'] = showAlert(\'You do not have permission to access this page.\', \'danger\');
    redirect(\'dashboard.php\');
}

// Check if students table exists, create if not
$query = "SHOW TABLES LIKE \'students\'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Create the students table
    $query = "CREATE TABLE students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn VARCHAR(20) NOT NULL UNIQUE,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        dob DATE NOT NULL,
        gender ENUM(\'Male\', \'Female\', \'Other\') NOT NULL,
        address TEXT,
        contact_number VARCHAR(20),
        guardian_name VARCHAR(100),
        guardian_contact VARCHAR(20),
        grade_level VARCHAR(20) NOT NULL,
        section VARCHAR(20) NOT NULL,
        enrollment_status ENUM(\'enrolled\', \'pending\', \'withdrawn\') DEFAULT \'pending\',
        photo VARCHAR(255) DEFAULT NULL,
        enrolled_by INT,
        date_enrolled DATE,
        education_level_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (education_level_id) REFERENCES education_levels(id) ON DELETE SET NULL
    )";
    
    if (!mysqli_query($conn, $query)) {
        $_SESSION[\'alert\'] = showAlert(\'Error creating students table: \' . mysqli_error($conn), \'danger\');
    }
    
    // Create directory for student photos if it doesn\'t exist
    $upload_dir = $relative_path . \'uploads/students\';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
} else {
    // Check if photo column exists in students table
    $query = "SHOW COLUMNS FROM students LIKE \'photo\'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
        // Add photo column to students table
        $query = "ALTER TABLE students ADD COLUMN photo VARCHAR(255) DEFAULT NULL";
        if (!mysqli_query($conn, $query)) {
            $_SESSION[\'alert\'] = showAlert(\'Error adding photo column to students table: \' . mysqli_error($conn), \'danger\');
        } else {
            $_SESSION[\'alert\'] = showAlert(\'Photo column added to students table.\', \'success\');
        }
    }
    
    // Check if address column exists in students table
    $query = "SHOW COLUMNS FROM students LIKE \'address\'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
        // Add address column to students table
        $query = "ALTER TABLE students ADD COLUMN address TEXT AFTER gender";
        if (!mysqli_query($conn, $query)) {
            $_SESSION[\'alert\'] = showAlert(\'Error adding address column to students table: \' . mysqli_error($conn), \'danger\');
        } else {
            $_SESSION[\'alert\'] = showAlert(\'Address column added to students table.\', \'success\');
        }
    }
    
    // Check if education_level_id column exists in students table
    $query = "SHOW COLUMNS FROM students LIKE \'education_level_id\'";
    $result = mysqli_query($conn, $query);
    $education_level_column_exists = mysqli_num_rows($result) > 0;
    
    if ($education_level_column_exists) {
        // Remove education_level_id column from students table if it exists
        $query = "ALTER TABLE students DROP FOREIGN KEY students_ibfk_2";
        mysqli_query($conn, $query);
        
        $query = "ALTER TABLE students DROP COLUMN education_level_id";
        mysqli_query($conn, $query);
    }
}

// Create directory for student photos if it doesn\'t exist
$upload_dir = $relative_path . \'uploads/students\';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if gender column exists in students table
$query = "SHOW COLUMNS FROM students LIKE \'gender\'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Add gender column to students table
    $query = "ALTER TABLE students ADD COLUMN gender ENUM(\'Male\', \'Female\', \'Other\') NOT NULL DEFAULT \'Male\' AFTER last_name";
    if (!mysqli_query($conn, $query)) {
        $_SESSION[\'alert\'] = showAlert(\'Error adding gender column to students table: \' . mysqli_error($conn), \'danger\');
    } else {
        $_SESSION[\'alert\'] = showAlert(\'Gender column added to students table.\', \'success\');
    }
}

// Process delete student
if (isset($_GET[\'action\']) && $_GET[\'action\'] === \'delete\' && isset($_GET[\'id\'])) {
    $delete_id = (int) $_GET[\'id\'];
    
    // Check if student exists
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $student = mysqli_fetch_assoc($result);
        
        // Delete student photo if exists
        if (!empty($student[\'photo\'])) {
            $full_path = $relative_path . $student[\'photo\'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
        
        // Delete student
        $query = "DELETE FROM students WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log action
            $log_desc = "Deleted student: {$student[\'first_name\']} {$student[\'last_name\']} (LRN: {$student[\'lrn\']})";
            logAction($_SESSION[\'user_id\'], \'DELETE\', $log_desc);
            
            $_SESSION[\'alert\'] = showAlert(\'Student deleted successfully.\', \'success\');
        } else {
            $_SESSION[\'alert\'] = showAlert(\'Error deleting student: \' . mysqli_error($conn), \'danger\');
        }
    } else {
        $_SESSION[\'alert\'] = showAlert(\'Student not found.\', \'danger\');
    }
    
    // Redirect to students page
    redirect(\'modules/registrar/students.php\');
}';

// Replace the beginning of the file with the fixed code
$new_content = $fixed_code . substr($content, strpos($content, "// Process form submission for adding/editing student"));

// Write the fixed content back to the file
if (file_put_contents($file_path, $new_content) !== false) {
    echo "Success: Fixed the students.php file.\n";
} else {
    echo "Error: Could not write to the file.\n";
}

// Check the syntax of the fixed file
$output = [];
$return_var = 0;
exec("php -l $file_path", $output, $return_var);

if ($return_var === 0) {
    echo "Syntax check passed. The file has been fixed successfully.\n";
} else {
    echo "Syntax check failed. Please check the file manually.\n";
    echo implode("\n", $output) . "\n";
}
?> 
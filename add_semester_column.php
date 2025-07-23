<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'shs_enrollment';

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected successfully. Checking school_years table...\n";

// Check if school_years table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'school_years'");
if (mysqli_num_rows($table_check) === 0) {
    echo "Creating school_years table...\n";
    
    // Create school_years table if it doesn't exist
    $create_table_query = "CREATE TABLE school_years (
        id INT AUTO_INCREMENT PRIMARY KEY,
        year_start INT NOT NULL,
        year_end INT NOT NULL,
        school_year VARCHAR(20) NOT NULL UNIQUE,
        semester ENUM('First', 'Second', 'Summer') DEFAULT 'First',
        is_current TINYINT(1) DEFAULT 0,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "School years table created successfully with semester column.\n";
        
        // Add current school year
        $current_year = (int)date('Y');
        $year_end = $current_year + 1;
        $school_year = $current_year . '-' . $year_end;
        
        $insert_query = "INSERT INTO school_years (year_start, year_end, school_year, semester, is_current, status) 
                         VALUES (?, ?, ?, 'First', 1, 'Active')";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "iis", $current_year, $year_end, $school_year);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Added current school year: $school_year\n";
        } else {
            echo "Error adding current school year: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Error creating school_years table: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "School years table exists. Checking for semester column...\n";
    
    // Check if semester column exists
    $check_semester_column = "SHOW COLUMNS FROM school_years LIKE 'semester'";
    $semester_column_result = mysqli_query($conn, $check_semester_column);
    
    if (mysqli_num_rows($semester_column_result) === 0) {
        echo "Adding semester column to school_years table...\n";
        
        // Add semester column to school_years table
        $add_semester_query = "ALTER TABLE school_years ADD COLUMN semester ENUM('First', 'Second', 'Summer') DEFAULT 'First' AFTER school_year";
        
        if (mysqli_query($conn, $add_semester_query)) {
            echo "Semester column added successfully.\n";
        } else {
            echo "Error adding semester column: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Semester column already exists in school_years table.\n";
    }
}

// Close connection
mysqli_close($conn);
echo "Done!\n";
?> 
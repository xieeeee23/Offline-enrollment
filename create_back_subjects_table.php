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

echo "Connected successfully. Creating back_subjects table...\n";

// Check if back_subjects table exists
$query = "SHOW TABLES LIKE 'back_subjects'";
$result = mysqli_query($conn, $query);
$table_exists = mysqli_num_rows($result) > 0;

if (!$table_exists) {
    // Create the back_subjects table
    $query = "CREATE TABLE back_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_code VARCHAR(20) NOT NULL,
        subject_name VARCHAR(100) NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        semester VARCHAR(20) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    if (mysqli_query($conn, $query)) {
        // Create indexes for better performance
        mysqli_query($conn, "CREATE INDEX idx_back_subjects_student ON back_subjects(student_id)");
        mysqli_query($conn, "CREATE INDEX idx_back_subjects_subject ON back_subjects(subject_code)");
        mysqli_query($conn, "CREATE INDEX idx_back_subjects_status ON back_subjects(status)");
        mysqli_query($conn, "CREATE INDEX idx_back_subjects_school_year ON back_subjects(school_year)");
        mysqli_query($conn, "CREATE INDEX idx_back_subjects_semester ON back_subjects(semester)");
        
        echo "Back subjects table created successfully with indexes.\n";
    } else {
        echo "Error creating back subjects table: " . mysqli_error($conn) . "\n";
    }
} else {
    // Check if indexes exist and create them if they don't
    $indexes_to_check = [
        'idx_back_subjects_student' => 'CREATE INDEX idx_back_subjects_student ON back_subjects(student_id)',
        'idx_back_subjects_subject' => 'CREATE INDEX idx_back_subjects_subject ON back_subjects(subject_code)',
        'idx_back_subjects_status' => 'CREATE INDEX idx_back_subjects_status ON back_subjects(status)',
        'idx_back_subjects_school_year' => 'CREATE INDEX idx_back_subjects_school_year ON back_subjects(school_year)',
        'idx_back_subjects_semester' => 'CREATE INDEX idx_back_subjects_semester ON back_subjects(semester)'
    ];
    
    foreach ($indexes_to_check as $index_name => $create_query) {
        $index_check = mysqli_query($conn, "SHOW INDEX FROM back_subjects WHERE Key_name = '$index_name'");
        if (mysqli_num_rows($index_check) === 0) {
            mysqli_query($conn, $create_query);
            echo "Created index: $index_name\n";
        }
    }
    
    echo "Back subjects table already exists. Indexes checked and created if needed.\n";
}

// Close connection
mysqli_close($conn);
echo "Done!\n";
?> 
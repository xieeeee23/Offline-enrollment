<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "localenroll_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful\n";

// First, rename the old sections table to sections_old
$query = "RENAME TABLE sections TO sections_old";
if ($conn->query($query) === TRUE) {
    echo "Old sections table renamed to sections_old\n";
} else {
    echo "Error renaming sections table: " . $conn->error . "\n";
    exit;
}

// Create new sections table with the correct structure
$query = "CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    grade_level ENUM('Grade 11', 'Grade 12') NOT NULL,
    strand VARCHAR(20) NOT NULL,
    max_students INT DEFAULT 40,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    school_year VARCHAR(20) NOT NULL,
    semester ENUM('First', 'Second') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (strand) REFERENCES shs_strands(strand_code)
)";

if ($conn->query($query) === TRUE) {
    echo "New sections table created successfully\n";
    
    // Insert default sections
    $current_year = date('Y');
    $school_year = $current_year . '-' . ($current_year + 1);
    
    $insert_query = "INSERT INTO sections (name, grade_level, strand, max_students, status, school_year, semester) VALUES 
        ('ABM-11A', 'Grade 11', 'ABM', 40, 'Active', '$school_year', 'First'),
        ('STEM-11A', 'Grade 11', 'STEM', 40, 'Active', '$school_year', 'First'),
        ('HUMSS-11A', 'Grade 11', 'HUMSS', 40, 'Active', '$school_year', 'First'),
        ('ABM-12A', 'Grade 12', 'ABM', 40, 'Active', '$school_year', 'First'),
        ('STEM-12A', 'Grade 12', 'STEM', 40, 'Active', '$school_year', 'First'),
        ('HUMSS-12A', 'Grade 12', 'HUMSS', 40, 'Active', '$school_year', 'First')";
    
    if ($conn->query($insert_query) === TRUE) {
        echo "Default sections inserted successfully\n";
    } else {
        echo "Error inserting default sections: " . $conn->error . "\n";
    }
} else {
    echo "Error creating new sections table: " . $conn->error . "\n";
}

echo "Update completed!";
?> 
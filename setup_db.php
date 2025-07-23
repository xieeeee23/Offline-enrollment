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

// Create senior_highschool_details table if not exists
$query = "CREATE TABLE IF NOT EXISTS senior_highschool_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    track VARCHAR(100) NOT NULL,
    strand VARCHAR(20) NOT NULL,
    semester ENUM('First', 'Second') NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    previous_school VARCHAR(255),
    previous_track VARCHAR(100),
    previous_strand VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";

if ($conn->query($query) === TRUE) {
    echo "Table senior_highschool_details created successfully\n";
} else {
    echo "Error creating senior_highschool_details table: " . $conn->error . "\n";
}

// Create shs_strands table if not exists
$query = "CREATE TABLE IF NOT EXISTS shs_strands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    track_name VARCHAR(100) NOT NULL,
    strand_code VARCHAR(20) NOT NULL UNIQUE,
    strand_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($query) === TRUE) {
    echo "Table shs_strands created successfully\n";
    
    // Check if strands table is empty and insert default strands
    $check_query = "SELECT COUNT(*) as count FROM shs_strands";
    $check_result = $conn->query($check_query);
    $row = $check_result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $insert_query = "INSERT INTO shs_strands (track_name, strand_code, strand_name, description, status) VALUES
            ('Academic', 'ABM', 'Accountancy, Business and Management', 'Focus on business-related fields and financial management', 'Active'),
            ('Academic', 'STEM', 'Science, Technology, Engineering, and Mathematics', 'Focus on science and math-related fields', 'Active'),
            ('Academic', 'HUMSS', 'Humanities and Social Sciences', 'Focus on literature, philosophy, social sciences', 'Active'),
            ('Academic', 'GAS', 'General Academic Strand', 'General subjects for undecided students', 'Active'),
            ('Technical-Vocational-Livelihood', 'TVL-HE', 'Home Economics', 'Skills related to household management', 'Active'),
            ('Technical-Vocational-Livelihood', 'TVL-ICT', 'Information and Communications Technology', 'Computer and tech-related skills', 'Active'),
            ('Technical-Vocational-Livelihood', 'TVL-IA', 'Industrial Arts', 'Skills related to manufacturing and production', 'Active'),
            ('Sports', 'Sports', 'Sports Track', 'Focus on physical education and sports development', 'Active'),
            ('Arts and Design', 'Arts', 'Arts and Design Track', 'Focus on visual and performing arts', 'Active')";
        
        if ($conn->query($insert_query) === TRUE) {
            echo "Default strands inserted successfully\n";
        } else {
            echo "Error inserting default strands: " . $conn->error . "\n";
        }
    }
} else {
    echo "Error creating shs_strands table: " . $conn->error . "\n";
}

// Create sections table if not exists
$query = "CREATE TABLE IF NOT EXISTS sections (
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
    echo "Table sections created successfully\n";
    
    // Check if sections table is empty and insert default sections
    $check_query = "SELECT COUNT(*) as count FROM sections";
    $check_result = $conn->query($check_query);
    $row = $check_result->fetch_assoc();
    
    if ($row['count'] == 0) {
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
    }
} else {
    echo "Error creating sections table: " . $conn->error . "\n";
}

// Create upload directory for student photos if it doesn't exist
$upload_dir = './uploads/students';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "Student photos upload directory created\n";
    } else {
        echo "Failed to create student photos upload directory\n";
    }
}

echo "Setup completed!";
?> 
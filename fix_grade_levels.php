<?php
require_once 'includes/config.php';

// Check if grade_levels table exists
$check_query = "SHOW TABLES LIKE 'grade_levels'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Table doesn't exist, create it
    $create_table_query = "CREATE TABLE grade_levels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        description TEXT,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "Grade levels table created successfully.<br>";
        
        // Insert default grade levels
        $insert_query = "INSERT INTO grade_levels (name, description) VALUES 
            ('Grade 11', 'Senior High School - First Year'),
            ('Grade 12', 'Senior High School - Second Year')";
        
        if (mysqli_query($conn, $insert_query)) {
            echo "Default grade levels added successfully.";
        } else {
            echo "Error adding default grade levels: " . mysqli_error($conn);
        }
    } else {
        echo "Error creating grade levels table: " . mysqli_error($conn);
    }
} else {
    echo "Grade levels table already exists.";
}

// Check if strands table exists
$check_query = "SHOW TABLES LIKE 'strands'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Table doesn't exist, create it
    $create_table_query = "CREATE TABLE strands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        echo "<br>Strands table created successfully.<br>";
        
        // Copy data from shs_strands if it exists
        $check_shs_strands = "SHOW TABLES LIKE 'shs_strands'";
        $result = mysqli_query($conn, $check_shs_strands);
        
        if (mysqli_num_rows($result) > 0) {
            $copy_query = "INSERT INTO strands (code, name, description, status)
                          SELECT strand_code, strand_name, description, status
                          FROM shs_strands";
            
            if (mysqli_query($conn, $copy_query)) {
                echo "Data copied from shs_strands to strands table.";
            } else {
                echo "Error copying data: " . mysqli_error($conn);
            }
        } else {
            // Insert some default strands
            $insert_query = "INSERT INTO strands (code, name, description) VALUES 
                ('STEM', 'Science, Technology, Engineering, and Mathematics', 'For students interested in science and technology fields'),
                ('HUMSS', 'Humanities and Social Sciences', 'For students interested in social sciences and humanities'),
                ('ABM', 'Accountancy, Business, and Management', 'For students interested in business and finance'),
                ('GAS', 'General Academic Strand', 'General academic preparation for college'),
                ('TVL', 'Technical-Vocational-Livelihood', 'For students interested in technical and vocational careers')";
            
            if (mysqli_query($conn, $insert_query)) {
                echo "Default strands added successfully.";
            } else {
                echo "Error adding default strands: " . mysqli_error($conn);
            }
        }
    } else {
        echo "<br>Error creating strands table: " . mysqli_error($conn);
    }
} else {
    echo "<br>Strands table already exists.";
}
?> 
<?php
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';

// Check if sections already exist
$check_query = "SELECT COUNT(*) as count FROM sections WHERE name IN ('PENDING-11', 'PENDING-12')";
$check_result = mysqli_query($conn, $check_query);
$row = mysqli_fetch_assoc($check_result);

if ($row['count'] < 2) {
    // Create pending sections for Grade 11 and Grade 12
    $query = "INSERT INTO sections (name, grade_level, strand, status, created_at) 
              VALUES ('PENDING-11', 'Grade 11', '', 'Active', NOW()), 
                     ('PENDING-12', 'Grade 12', '', 'Active', NOW())";
    
    if (mysqli_query($conn, $query)) {
        echo "Pending sections created successfully.";
    } else {
        echo "Error creating pending sections: " . mysqli_error($conn);
    }
} else {
    echo "Pending sections already exist.";
}
?> 
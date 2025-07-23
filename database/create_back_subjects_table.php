<?php
// Include database connection
$relative_path = '../';
require_once $relative_path . 'includes/db_connect.php';

// SQL to create the back_subjects table
$sql = "
CREATE TABLE IF NOT EXISTS `back_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` enum('First','Second') NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_completed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

// Execute the query
if (mysqli_query($conn, $sql)) {
    echo "Table 'back_subjects' created successfully or already exists.<br>";
    
    // Add foreign key constraint
    $add_constraint_sql = "
    ALTER TABLE `back_subjects`
    ADD CONSTRAINT `back_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
    ";
    
    if (mysqli_query($conn, $add_constraint_sql)) {
        echo "Foreign key constraint added successfully.<br>";
    } else {
        echo "Error adding foreign key constraint: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

// Close connection
mysqli_close($conn);

echo "<p>You can now go back to the <a href='../index.php'>homepage</a>.</p>";
?> 
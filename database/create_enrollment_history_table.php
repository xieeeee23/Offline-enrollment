<?php
// Include database connection
$relative_path = '../';
require_once $relative_path . 'includes/db_connect.php';

// SQL to create the enrollment_history table
$sql = "
CREATE TABLE IF NOT EXISTS `enrollment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` enum('First','Second') NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `section` varchar(50) NOT NULL,
  `strand` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `date_enrolled` date NOT NULL,
  `enrolled_by` int(11) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `enrolled_by` (`enrolled_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

// Execute the query
if (mysqli_query($conn, $sql)) {
    echo "Table 'enrollment_history' created successfully or already exists.<br>";
    
    // Add foreign key constraints
    $add_constraints_sql = "
    ALTER TABLE `enrollment_history`
    ADD CONSTRAINT `enrollment_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `enrollment_history_ibfk_2` FOREIGN KEY (`enrolled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
    ";
    
    if (mysqli_query($conn, $add_constraints_sql)) {
        echo "Foreign key constraints added successfully.<br>";
    } else {
        echo "Error adding foreign key constraints: " . mysqli_error($conn) . "<br>";
        echo "This is normal if the constraints already exist.<br>";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

// Close connection
mysqli_close($conn);

echo "<p>You can now go back to the <a href='../index.php'>homepage</a>.</p>";
?> 
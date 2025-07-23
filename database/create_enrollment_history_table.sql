-- SQL Script to create the enrollment_history table

-- Check if table exists and create it if it doesn't
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
  KEY `enrolled_by` (`enrolled_by`),
  CONSTRAINT `enrollment_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `enrollment_history_ibfk_2` FOREIGN KEY (`enrolled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 
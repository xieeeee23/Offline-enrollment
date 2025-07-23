-- SQL Script to create the back_subjects table

-- Check if table exists and create it if it doesn't
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

-- Add foreign key constraints if the referenced tables exist
ALTER TABLE `back_subjects`
  ADD CONSTRAINT `back_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Note: You may need to add a constraint for subject_id if you have a subjects table
-- ALTER TABLE `back_subjects`
--   ADD CONSTRAINT `back_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE; 
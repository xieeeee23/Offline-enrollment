-- LocalEnroll Pro Database Backup
-- Generated: 2025-07-21 17:29:25
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
-- Database: shs_enrollment

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS=0;

-- Table structure for table `back_subjects`
DROP TABLE IF EXISTS `back_subjects`;
CREATE TABLE `back_subjects` (
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
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `back_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for table `education_levels`
DROP TABLE IF EXISTS `education_levels`;
CREATE TABLE `education_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `grade_min` int(11) DEFAULT NULL,
  `grade_max` int(11) DEFAULT NULL,
  `age_min` int(11) DEFAULT NULL,
  `age_max` int(11) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `level_name` (`level_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `education_levels`
INSERT INTO `education_levels` VALUES ('1','Kindergarten','Early childhood education before elementary school','0','0','5','6','1','Active','2025-07-03 23:43:13','2025-07-03 23:43:13'),
('2','Elementary','Primary education from grades 1 to 6','1','6','6','12','2','Active','2025-07-03 23:43:13','2025-07-03 23:43:13'),
('3','Junior High School','Secondary education from grades 7 to 10','7','10','12','16','3','Active','2025-07-03 23:43:13','2025-07-03 23:43:13'),
('4','Senior High School','Upper secondary education from grades 11 to 12','11','12','16','18','4','Active','2025-07-03 23:43:13','2025-07-03 23:43:13');


-- Table structure for table `enrollment_history`
DROP TABLE IF EXISTS `enrollment_history`;
CREATE TABLE `enrollment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `strand` varchar(50) DEFAULT NULL,
  `section` varchar(50) NOT NULL,
  `enrollment_status` varchar(20) NOT NULL,
  `date_enrolled` date NOT NULL,
  `enrolled_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `enrolled_by` (`enrolled_by`),
  CONSTRAINT `enrollment_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollment_history_ibfk_2` FOREIGN KEY (`enrolled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for table `logs`
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=376 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `logs`
INSERT INTO `logs` VALUES ('1','1','LOGIN','Admin logged in','2025-07-03 18:37:05'),
('2','2','ENROLL','Registered student Juan Dela Cruz','2025-07-03 18:37:05'),
('3','2','ENROLL','Registered student Maria Santos','2025-07-03 18:37:05'),
('4','2','ENROLL','Registered student Pedro Reyes','2025-07-03 18:37:05'),
('5','1','CREATE','Enrolled new student: Leo John Bendijo (LRN: 2025967453) with photo','2025-07-03 22:09:39'),
('6','1','CREATE','Added SHS details for student: Leo John Bendijo, Track: Academic, Strand: GAS','2025-07-03 22:09:39'),
('7','1','CREATE','Enrolled new student: Leo John Bendijo (LRN: 2025746756) with photo','2025-07-03 22:09:49'),
('8','1','CREATE','Enrolled new student: Leo John Bendijo (LRN: 2025679980) with photo','2025-07-03 22:27:57'),
('9','1','CREATE','Added SHS details for student: Leo John Bendijo, Track: Academic, Strand: ','2025-07-03 22:27:57'),
('10','1','DELETE','Deleted photo for student ID: 6','2025-07-03 23:48:13'),
('11','1','DELETE','Deleted photo for student ID: 5','2025-07-03 23:48:18'),
('12','1','UPDATE','Added contact_number column to teachers table','2025-07-03 23:50:41'),
('13','1','DELETE','Deleted photo for student ID: 4','2025-07-04 00:09:01'),
('14','1','CREATE','Created requirements table and added default requirements','2025-07-04 02:08:16'),
('15','1','UPDATE','Changed teacher status to inactive for teacher ID: 13','2025-07-04 09:21:08'),
('16','1','UPDATE','Changed teacher status to active for teacher ID: 13','2025-07-04 09:21:16'),
('17','1','CREATE','Auto-generated school year: 2029-2030','2025-07-04 09:23:09'),
('18','1','CREATE','Auto-generated school year: 2030-2031','2025-07-04 09:23:09'),
('19','1','CREATE','Added new student: Glycel Hilaos (LRN: 677867864545)','2025-07-04 10:48:44'),
('20','1','UPDATE','Updated student: Glycel Hilaos (LRN: 677867864545)','2025-07-04 10:49:19'),
('21','1','UPDATE','Added photo column to teachers table','2025-07-04 11:29:54'),
('22','1','UPDATE','Added photo column to users table','2025-07-04 11:30:47'),
('23','1','UPDATE','Updated user: registrar (Role: registrar) and changed password','2025-07-04 11:39:45'),
('24','1','UPDATE','Updated user: registrar (Role: registrar)','2025-07-04 13:05:12'),
('25','1','UPDATE','Updated user: registrar (Role: registrar)','2025-07-04 13:18:58'),
('26','1','UPDATE','Updated user: registrar (Role: registrar)','2025-07-04 13:22:19'),
('27','1','UPDATE','Updated user: registrar (Role: registrar)','2025-07-04 13:22:29'),
('28','1','LOGOUT','User logged out','2025-07-04 15:17:25'),
('29','1','LOGIN','User logged in','2025-07-04 15:17:36'),
('30','1','CREATE','Added new teacher: Glycel Hilaos','2025-07-04 16:37:04'),
('31','1','CREATE','Added new teacher: Glycel Hilaos','2025-07-04 16:37:11'),
('32','1','UPDATE','Updated teacher: Glycel Hilaos','2025-07-04 16:59:22'),
('33','1','CREATE','Added new requirement: sd','2025-07-04 17:43:04'),
('34','1','DELETE','Deleted requirement ID: 8','2025-07-04 17:52:43'),
('35','1','CREATE','Added new subject: SHS Registrar','2025-07-04 17:53:51'),
('36','1','CREATE','Added new subject: SHS Registrar','2025-07-04 17:58:48'),
('37','1','CREATE','Added new schedule: Earth and Life Science for 11 Section A','2025-07-04 19:42:30'),
('38','1','LOGIN','User logged in','2025-07-04 20:32:15'),
('39','1','LOGIN','User logged in','2025-07-04 23:52:30'),
('40','1','LOGIN','User logged in','2025-07-05 14:45:40'),
('43','1','LOGIN','User logged in','2025-07-05 23:33:52'),
('44','1','LOGIN','User logged in','2025-07-06 07:15:24'),
('45','1','LOGIN','User logged in','2025-07-06 12:32:25'),
('46','1','CREATE','Added new student: mark Lopez (LRN: 453524234123)','2025-07-06 12:34:25'),
('47','1','UPDATE','Updated student: Glycel Hilaos (LRN: 677867864545)','2025-07-06 12:40:57'),
('48','1','UPDATE','Updated SHS details for student: Glycel Hilaos, Track: Academic, Strand: ABM','2025-07-06 13:19:17'),
('49','1','UPDATE','Updated student: Glycel Hilaos (LRN: 677867864545)','2025-07-06 13:19:17'),
('51','1','CREATE','Added new subject: Bronny','2025-07-06 13:25:41'),
('52','1','UPDATE','Updated subject: Statistics and Probability','2025-07-06 13:25:53'),
('53','1','Added new SHS strand','Added strand: 9u88686 - 675765765','2025-07-06 13:26:20'),
('54','1','CREATE','Added new requirement: 8iyyi','2025-07-06 13:27:25'),
('57','1','Added new SHS strand','Added strand: 909097 - aeru','2025-07-06 13:31:02'),
('58','1','Added new SHS strand','Added strand: 909097 - aeru','2025-07-06 13:31:15'),
('59','1','Added new SHS strand','Added strand: 909097 - aeru','2025-07-06 13:36:44'),
('60','1','Added new SHS strand','Added strand: dfgdttr - aereer','2025-07-06 13:37:12'),
('61','1','Added new SHS strand','Added strand: w1123 - baho','2025-07-06 13:37:43'),
('62','1','Deleted SHS strand','Deleted strand: dfgdttr - aereer','2025-07-06 13:38:07'),
('63','1','Deleted SHS strand','Deleted strand: w1123 - baho','2025-07-06 13:38:17'),
('64','1','Deleted SHS strand','Deleted strand:  - ','2025-07-06 13:38:20'),
('65','1','Deleted SHS strand','Deleted strand: Unknown - Unknown','2025-07-06 15:51:27'),
('71','1','LOGIN','User logged in','2025-07-06 16:31:09'),
('79','1','DELETE','Deleted requirement ID: 9','2025-07-06 17:05:52'),
('80','1','UPDATE','Requirement ID: 7 deactivated','2025-07-06 17:05:59'),
('81','1','UPDATE','Requirement ID: 7 activated','2025-07-06 17:06:03'),
('89','1','UPDATE','Updated SHS details for student: mark Lopez, Track: Academic, Strand: ABM','2025-07-06 17:46:28'),
('90','1','UPDATE','Updated student: mark Lopez (LRN: 453524234123)','2025-07-06 17:46:28'),
('91','1','UPDATE','Updated SHS details for student: arman sartu, Track: Academic, Strand: ABM','2025-07-06 17:51:30'),
('92','1','UPDATE','Updated student: arman sartu (LRN: 202596777888)','2025-07-06 17:51:30'),
('93','1','DELETE','Deleted student: Leo John Bendijo (LRN: 2025746756)','2025-07-06 18:10:57'),
('94','1','DELETE','Deleted student: arman sartu (LRN: 202596777888)','2025-07-06 18:14:48'),
('95','1','DELETE','Deleted student: Leo John Bendijo (LRN: 2025679980)','2025-07-06 18:16:16'),
('97','1','DELETE','Deleted teacher: Glycel Hilaos','2025-07-06 18:28:57'),
('98','1','DELETE','Deleted teacher: Glycel Hilaos','2025-07-06 18:29:18'),
('99','1','DELETE','Deleted teacher: John Doe','2025-07-06 18:29:47'),
('100','1','DELETE','Deleted teacher: Second Teacher','2025-07-06 18:36:43'),
('101','1','CREATE','Added new teacher: Leo John Bendijo','2025-07-06 18:37:08'),
('102','1','CREATE','Added new schedule: Statistics and Probability for 11 Section A','2025-07-06 18:37:44'),
('103','1','INSERT','Added new strand with ID: 16','2025-07-06 18:38:28'),
('104','1','Deleted SHS strand','Deleted strand: 2erer4 - bahowe','2025-07-06 18:38:35'),
('105','1','UPDATE','Modified users table to remove student and parent roles','2025-07-06 19:17:00'),
('106','1','LOGIN','User logged in','2025-07-06 21:32:23'),
('107','1','UPDATE','Updated SHS details for student: Juan Dela Cruz, Track: Arts and Design, Strand: Arts','2025-07-06 21:36:56'),
('108','1','UPDATE','Updated student: Juan Dela Cruz (LRN: 123456789012)','2025-07-06 21:36:56'),
('109','1','UPDATE','Updated SHS details for student: Pedro Reyes, Track: Academic, Strand: ABM','2025-07-06 21:37:25'),
('110','1','UPDATE','Updated student: Pedro Reyes (LRN: 345678901234)','2025-07-06 21:37:25'),
('111','1','UPDATE','Updated SHS details for student: Maria Santos, Track: Academic, Strand: ABM','2025-07-06 21:38:09'),
('112','1','UPDATE','Updated student: Maria Santos (LRN: 234567890123)','2025-07-06 21:38:09'),
('113','1','INSERT','Added new student with ID: 9','2025-07-06 22:15:22'),
('114','1','INSERT','Added new senior high school details with ID: 8','2025-07-06 22:15:22'),
('115','1','CREATE','Added SHS details for student: Sarah arman, Track: Academic, Strand: ABM','2025-07-06 22:15:22'),
('116','1','UPDATE','Requirement ID: 2 deactivated','2025-07-06 22:20:32'),
('117','1','UPDATE','Requirement ID: 2 activated','2025-07-06 22:20:36'),
('124','1','LOGIN','User logged in','2025-07-07 16:44:14'),
('125','1','LOGIN','User logged in','2025-07-18 14:07:22'),
('126','1','UPDATE','Updated section with ID: 1','2025-07-18 15:18:07'),
('127','1','LOGIN','User logged in','2025-07-18 16:09:22'),
('128','1','UPDATE','Updated SHS details for student: Sarah arman, Track: Academic, Strand: ABM','2025-07-18 17:10:43'),
('129','1','UPDATE','Updated student: Sarah arman (LRN: 123454455343)','2025-07-18 17:10:43'),
('130','1','INSERT','Added new student with ID: 10','2025-07-18 17:11:39'),
('131','1','INSERT','Added new senior high school details with ID: 9','2025-07-18 17:11:39'),
('132','1','CREATE','Added SHS details for student: Ansdk Bendijo, Track: ARTS AND DESIGN, Strand: ARTS','2025-07-18 17:11:39');
INSERT INTO `logs` VALUES ('133','1','UPDATE','Updated SHS details for student: Saraherw arman, Track: Academic, Strand: ABM','2025-07-18 17:12:31'),
('134','1','UPDATE','Updated student: Saraherw arman (LRN: 123454455343)','2025-07-18 17:12:31'),
('135','1','CREATE','Created requirement_types table and added default types','2025-07-18 17:14:03'),
('136','1','UPDATE','Updated SHS details for student: Saraherw arman, Track: Academic, Strand: ABM','2025-07-18 17:14:35'),
('137','1','UPDATE','Updated student: Saraherw arman (LRN: 123454455343)','2025-07-18 17:14:35'),
('138','1','UPDATE','Updated SHS details for student: Saraherw arman, Track: Academic, Strand: ABM','2025-07-18 17:37:17'),
('139','1','UPDATE','Updated student: Saraherw arman (LRN: 123454455343)','2025-07-18 17:37:17'),
('140','1','UPDATE','Updated SHS details for student: Juan Dela Cruz, Track: Arts and Design, Strand: Arts','2025-07-18 17:59:08'),
('141','1','UPDATE','Updated student: Juan Dela Cruz (LRN: 123456789012)','2025-07-18 17:59:08'),
('142','1','DELETE','Deleted student: Ansdk Bendijo (LRN: 123456643443)','2025-07-18 18:34:41'),
('143','1','LOGIN','User logged in','2025-07-19 15:02:44'),
('144','1','LOGIN','User logged in','2025-07-20 16:49:27'),
('145','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('146','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('147','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('148','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:02:38'),
('149','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:02:38'),
('150','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('151','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('152','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('153','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('154','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:02:38'),
('155','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:02:38'),
('156','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('157','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('158','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('159','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('160','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:02:38'),
('161','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:02:38'),
('162','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('163','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('164','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('165','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('166','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:02:38'),
('167','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:02:38'),
('168','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('169','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('170','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('171','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('172','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:02:38'),
('173','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:02:38'),
('174','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('175','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('176','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('177','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('178','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:02:38'),
('179','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:02:38'),
('180','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:02:38'),
('181','1','UPDATE','Updated SHS details for student: Maria Santos, Track: Academic, Strand: ABM','2025-07-20 17:03:00'),
('182','1','UPDATE','Updated student: Maria Santos (LRN: 234567890123)','2025-07-20 17:03:00'),
('183','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('184','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('185','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('186','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:03:00'),
('187','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:03:00'),
('188','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('189','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('190','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('191','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('192','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:03:00'),
('193','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:03:00'),
('194','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('195','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('196','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('197','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('198','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:03:00'),
('199','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:03:00'),
('200','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('201','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('202','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('203','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('204','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:03:00'),
('205','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:03:00'),
('206','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('207','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('208','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('209','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('210','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:03:00'),
('211','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:03:00'),
('212','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('213','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('214','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('215','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('216','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:03:00'),
('217','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:03:00'),
('218','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:03:00'),
('219','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('220','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('221','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('222','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:09'),
('223','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:09'),
('224','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('225','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('226','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('227','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('228','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:09'),
('229','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:09'),
('230','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('231','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('232','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('233','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09');
INSERT INTO `logs` VALUES ('234','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:09'),
('235','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:09'),
('236','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('237','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('238','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('239','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('240','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:09'),
('241','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:09'),
('242','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('243','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('244','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('245','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('246','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:09'),
('247','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:09'),
('248','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('249','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('250','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('251','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('252','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:09'),
('253','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:09'),
('254','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:09'),
('255','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('256','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('257','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('258','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:13'),
('259','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:13'),
('260','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('261','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('262','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('263','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('264','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:13'),
('265','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:13'),
('266','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('267','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('268','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('269','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('270','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:13'),
('271','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:13'),
('272','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('273','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('274','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('275','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('276','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:13'),
('277','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:13'),
('278','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('279','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('280','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('281','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('282','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:13'),
('283','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:13'),
('284','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('285','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('286','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('287','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('288','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 17:37:13'),
('289','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 17:37:13'),
('290','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 17:37:13'),
('291','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('292','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('293','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('294','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:09:21'),
('295','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:09:21'),
('296','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('297','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('298','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('299','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('300','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:09:21'),
('301','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:09:21'),
('302','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('303','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('304','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('305','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('306','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:09:21'),
('307','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:09:21'),
('308','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('309','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('310','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('311','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('312','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:09:21'),
('313','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:09:21'),
('314','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('315','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('316','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('317','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('318','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:09:21'),
('319','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:09:21'),
('320','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('321','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('322','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('323','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('324','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:09:21'),
('325','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:09:21'),
('326','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:09:21'),
('327','1','ERROR','Database error occurred: Undefined variable $DB_NAME','2025-07-20 18:18:47'),
('328','1','ERROR','Database error occurred: Undefined variable $DB_NAME','2025-07-20 18:19:11'),
('329','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('330','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('331','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('332','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:22:16'),
('333','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:22:16'),
('334','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16');
INSERT INTO `logs` VALUES ('335','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('336','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('337','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('338','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:22:16'),
('339','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:22:16'),
('340','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('341','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('342','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('343','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('344','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:22:16'),
('345','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:22:16'),
('346','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('347','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('348','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('349','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('350','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:22:16'),
('351','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:22:16'),
('352','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('353','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('354','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('355','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('356','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:22:16'),
('357','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:22:16'),
('358','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('359','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('360','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('361','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16'),
('362','1','ERROR','Database error occurred: Undefined array key \"first_name\"','2025-07-20 18:22:16'),
('363','1','ERROR','Database error occurred: Undefined array key \"last_name\"','2025-07-20 18:22:16'),
('364','1','ERROR','Database error occurred: Undefined array key \"id\"','2025-07-20 18:22:16');


-- Table structure for table `requirement_types`
DROP TABLE IF EXISTS `requirement_types`;
CREATE TABLE `requirement_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `requirement_types`
INSERT INTO `requirement_types` VALUES ('1','2x2 ID Picture','2x2 ID Picture','1','1','2025-07-18 17:14:03','2025-07-18 17:14:03'),
('2','8iyyi','8iyyi','1','1','2025-07-18 17:14:03','2025-07-18 17:14:03'),
('3','Birth Certificate','Birth Certificate','1','1','2025-07-18 17:14:03','2025-07-18 17:14:03'),
('4','Enrollment Form','Enrollment Form','1','1','2025-07-18 17:14:03','2025-07-18 17:14:03'),
('5','Good Moral Certificate','Good Moral Certificate','1','1','2025-07-18 17:14:03','2025-07-18 17:14:03'),
('6','Medical Certificate','Medical Certificate','1','1','2025-07-18 17:14:03','2025-07-18 17:14:03'),
('7','Parent/Guardian ID','Parent/Guardian ID','1','1','2025-07-18 17:14:03','2025-07-18 17:14:03'),
('8','Report Card / Form 138','Report Card / Form 138','1','1','2025-07-18 17:14:03','2025-07-18 17:14:03');


-- Table structure for table `requirements`
DROP TABLE IF EXISTS `requirements`;
CREATE TABLE `requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `program` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `requirements`
INSERT INTO `requirements` VALUES ('1','Birth Certificate','document','all','0','1','1','2025-07-04 02:08:14','2025-07-07 06:21:38'),
('2','Report Card / Form 138','document','all','Report Card / Form 138','1','1','2025-07-04 02:08:15','2025-07-06 22:20:36'),
('3','Good Moral Certificate','document','all','Good Moral Certificate','1','1','2025-07-04 02:08:15','2025-07-04 02:08:15'),
('4','Medical Certificate','document','all','Medical Certificate','1','1','2025-07-04 02:08:15','2025-07-04 02:08:15'),
('5','2x2 ID Picture','document','all','2x2 ID Picture','1','1','2025-07-04 02:08:15','2025-07-04 02:08:15'),
('6','Enrollment Form','document','all','Enrollment Form','1','1','2025-07-04 02:08:15','2025-07-04 02:08:15'),
('7','Parent/Guardian ID','document','all','Parent/Guardian ID','1','1','2025-07-04 02:08:16','2025-07-06 17:06:03'),
('12','8iyyi','payment','undergraduate','iuoioioi','1','1','2025-07-07 00:30:33','2025-07-07 00:30:33');


-- Table structure for table `requirements_mapping`
DROP TABLE IF EXISTS `requirements_mapping`;
CREATE TABLE `requirements_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requirement_id` int(11) NOT NULL,
  `column_name` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `requirement_id` (`requirement_id`),
  UNIQUE KEY `column_name` (`column_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `requirements_mapping`
INSERT INTO `requirements_mapping` VALUES ('2','1','birth_certificate','2025-07-07 00:29:08'),
('3','2','report_card___form_138','2025-07-07 00:29:08'),
('4','3','good_moral_certificate','2025-07-07 00:29:08'),
('5','4','medical_certificate','2025-07-07 00:29:08'),
('6','5','2x2_id_picture','2025-07-07 00:29:09'),
('7','6','enrollment_form','2025-07-07 00:29:09'),
('8','7','parent_guardian_id','2025-07-07 00:29:09'),
('9','12','8iyyi','2025-07-07 00:30:34');


-- Table structure for table `schedule`
DROP TABLE IF EXISTS `schedule`;
CREATE TABLE `schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_level` varchar(20) NOT NULL,
  `section` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day` varchar(20) NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `room` varchar(50) NOT NULL,
  `strand` varchar(50) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `schedule`
INSERT INTO `schedule` VALUES ('2','11','Section A','Statistics and Probability','17','Tuesday','08:23:00','17:34:00','234','','2025-07-06 18:37:44','2025-07-06 18:37:44');


-- Table structure for table `schedules`
DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) DEFAULT NULL,
  `grade_level` varchar(20) NOT NULL,
  `section` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Table structure for table `school_years`
DROP TABLE IF EXISTS `school_years`;
CREATE TABLE `school_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year_start` int(11) NOT NULL,
  `year_end` int(11) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `semester` enum('First','Second','Summer') DEFAULT 'First',
  `is_current` tinyint(1) DEFAULT 0,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_year` (`school_year`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `school_years`
INSERT INTO `school_years` VALUES ('1','2025','2026','2025-2026','First','1','Active','2025-07-04 09:21:21','2025-07-21 17:29:25'),
('2','2026','2027','2026-2027','First','0','Active','2025-07-04 09:21:37','2025-07-04 09:21:37'),
('3','2027','2028','2027-2028','First','0','Active','2025-07-04 09:21:40','2025-07-04 09:21:40'),
('4','2028','2029','2028-2029','First','0','Active','2025-07-04 09:21:41','2025-07-04 09:21:41'),
('5','2029','2030','2029-2030','First','0','Active','2025-07-04 09:23:09','2025-07-04 09:23:09'),
('6','2030','2031','2030-2031','First','0','Active','2025-07-04 09:23:09','2025-07-04 09:23:09');


-- Table structure for table `sections`
DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `grade_level` enum('Grade 11','Grade 12') NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 40,
  `adviser_id` int(11) DEFAULT NULL,
  `strand` varchar(20) NOT NULL,
  `max_students` int(11) DEFAULT 40,
  `room` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `school_year` varchar(20) NOT NULL,
  `semester` enum('First','Second') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `strand` (`strand`),
  CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`strand`) REFERENCES `shs_strands` (`strand_code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `sections`
INSERT INTO `sections` VALUES ('1','ABM-11A','Grade 11','40',NULL,'ABM','40','3232','','Active','2030-2031','Second','2025-07-03 18:37:05','2025-07-18 15:18:07'),
('2','STEM-11A','Grade 11','40',NULL,'STEM','40',NULL,NULL,'Active','2023-2024','First','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('3','HUMSS-11A','Grade 11','40',NULL,'HUMSS','40',NULL,NULL,'Active','2023-2024','First','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('4','ABM-12A','Grade 12','40',NULL,'ABM','40',NULL,NULL,'Active','2023-2024','First','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('5','STEM-12A','Grade 12','40',NULL,'STEM','40',NULL,NULL,'Active','2023-2024','First','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('6','HUMSS-12A','Grade 12','40',NULL,'HUMSS','40',NULL,NULL,'Active','2023-2024','First','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('7','SHS Registrar','Grade 11','40',NULL,'Arts','40',NULL,NULL,'Active','2029-2030','First','2025-07-04 16:30:30','2025-07-04 16:30:30'),
('8','aert','Grade 11','40',NULL,'HUMSS','40',NULL,NULL,'Active','2028-2029','Second','2025-07-06 13:25:10','2025-07-06 13:25:10');


-- Table structure for table `senior_highschool_details`
DROP TABLE IF EXISTS `senior_highschool_details`;
CREATE TABLE `senior_highschool_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `track` varchar(100) NOT NULL,
  `strand` varchar(20) NOT NULL,
  `semester` enum('First','Second') NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `previous_track` varchar(100) DEFAULT NULL,
  `previous_strand` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `senior_highschool_details_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `senior_highschool_details`
INSERT INTO `senior_highschool_details` VALUES ('1','1','Arts and Design','Arts','First','2025-2026','Manila High School','','','2025-07-03 18:37:05','2025-07-18 17:59:08'),
('2','2','Academic','ABM','Second','2025-2026','Makati High School','','','2025-07-03 18:37:05','2025-07-20 17:03:00'),
('3','3','Academic','ABM','First','2023-2024','Quezon City High School','','','2025-07-03 18:37:05','2025-07-06 21:37:25'),
('6','7','Academic','ABM','First','2025-2026','jgjhbjbjbjhbhj','ffdd','dfgfdfds','2025-07-06 13:19:17','2025-07-06 13:19:17'),
('7','8','Academic','ABM','First','2025-2026','','','','2025-07-06 17:46:28','2025-07-06 17:46:28'),
('8','9','Academic','ABM','First','2025-2026','','','','2025-07-06 22:15:22','2025-07-06 22:15:22');


-- Table structure for table `shs_schedule_list`
DROP TABLE IF EXISTS `shs_schedule_list`;
CREATE TABLE `shs_schedule_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `section` varchar(50) NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `shs_schedule_list_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `shs_schedule_list`
INSERT INTO `shs_schedule_list` VALUES ('1','17','8786 - SHS Registrar','ABM-11A','11','Saturday','08:03:00','15:34:00','223','2025-07-21 04:43:39','2025-07-21 04:43:39');


-- Table structure for table `shs_strands`
DROP TABLE IF EXISTS `shs_strands`;
CREATE TABLE `shs_strands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `track_name` varchar(100) NOT NULL,
  `strand_code` varchar(20) NOT NULL,
  `strand_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `strand_code` (`strand_code`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `shs_strands`
INSERT INTO `shs_strands` VALUES ('1','Academic','ABM','Accountancy, Business and Management','Focus on business-related fields and financial management','Active','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('2','Academic','STEM','Science, Technology, Engineering, and Mathematics','Focus on science and math-related fields','Active','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('3','Academic','HUMSS','Humanities and Social Sciences','Focus on literature, philosophy, social sciences','Active','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('4','Academic','GAS','General Academic Strand','General subjects for undecided students','Active','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('5','Technical-Vocational-Livelihood','TVL-HE','Home Economics','Skills related to household management','Active','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('6','Technical-Vocational-Livelihood','TVL-ICT','Information and Communications Technology','Computer and tech-related skills','Active','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('7','Technical-Vocational-Livelihood','TVL-IA','Industrial Arts','Skills related to manufacturing and production','Active','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('8','Sports','Sports','Sports Track','Focus on physical education and sports development','Active','2025-07-03 18:37:05','2025-07-03 18:37:05'),
('9','Arts and Design','Arts','Arts and Design Track','Focus on visual and performing arts','Active','2025-07-03 18:37:05','2025-07-03 18:37:05');


-- Table structure for table `student_requirements`
DROP TABLE IF EXISTS `student_requirements`;
CREATE TABLE `student_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `birth_certificate` tinyint(1) DEFAULT 0,
  `report_card` tinyint(1) DEFAULT 0,
  `good_moral` tinyint(1) DEFAULT 0,
  `medical_certificate` tinyint(1) DEFAULT 0,
  `id_picture` tinyint(1) DEFAULT 0,
  `enrollment_form` tinyint(1) DEFAULT 0,
  `parent_id` tinyint(1) DEFAULT 0,
  `birth_certificate_file` varchar(255) DEFAULT NULL,
  `report_card_file` varchar(255) DEFAULT NULL,
  `good_moral_file` varchar(255) DEFAULT NULL,
  `medical_certificate_file` varchar(255) DEFAULT NULL,
  `id_picture_file` varchar(255) DEFAULT NULL,
  `enrollment_form_file` varchar(255) DEFAULT NULL,
  `parent_id_file` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `report_card___form_138` tinyint(1) DEFAULT 0,
  `report_card___form_138_file` varchar(255) DEFAULT NULL,
  `good_moral_certificate` tinyint(1) DEFAULT 0,
  `good_moral_certificate_file` varchar(255) DEFAULT NULL,
  `2x2_id_picture` tinyint(1) DEFAULT 0,
  `2x2_id_picture_file` varchar(255) DEFAULT NULL,
  `parent_guardian_id` tinyint(1) DEFAULT 0,
  `parent_guardian_id_file` varchar(255) DEFAULT NULL,
  `8iyyi` tinyint(1) DEFAULT 0,
  `8iyyi_file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `student_requirements_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `student_requirements`
INSERT INTO `student_requirements` VALUES ('4','1','1','1','0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','2025-07-06 16:53:19','2025-07-06 18:19:08','0',NULL,'0',NULL,'0',NULL,'0',NULL,'0',NULL),
('5','3','1','1','0','0','0','0','0','uploads/requirements/3/birth_certificate_1751792174.json','uploads/requirements/3/report_card_1751792174.png',NULL,NULL,NULL,NULL,NULL,'','2025-07-06 16:56:14','2025-07-06 16:56:14','0',NULL,'0',NULL,'0',NULL,'0',NULL,'0',NULL),
('10','2','0','0','0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-07 00:28:40','2025-07-07 00:28:40','0',NULL,'0',NULL,'0',NULL,'0',NULL,'0',NULL),
('11','7','0','0','0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-07 00:28:40','2025-07-07 00:28:40','0',NULL,'0',NULL,'0',NULL,'0',NULL,'0',NULL),
('12','8','0','0','0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-07 00:28:41','2025-07-07 00:28:41','0',NULL,'0',NULL,'0',NULL,'0',NULL,'0',NULL),
('13','9','0','0','0','0','0','0','0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-07 00:28:41','2025-07-07 00:28:41','0',NULL,'0',NULL,'0',NULL,'0',NULL,'0',NULL);


-- Table structure for table `students`
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lrn` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `grade_level` enum('Grade 11','Grade 12') NOT NULL,
  `section` varchar(20) DEFAULT NULL,
  `enrollment_status` enum('enrolled','pending','withdrawn','irregular','graduated') DEFAULT 'pending',
  `photo` varchar(255) DEFAULT NULL,
  `enrolled_by` int(11) DEFAULT NULL,
  `date_enrolled` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `strand` varchar(50) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `has_voucher` tinyint(1) DEFAULT 0,
  `voucher_number` varchar(50) DEFAULT NULL,
  `student_type` enum('new','old') DEFAULT 'new',
  PRIMARY KEY (`id`),
  UNIQUE KEY `lrn` (`lrn`),
  KEY `enrolled_by` (`enrolled_by`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`enrolled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `students`
INSERT INTO `students` VALUES ('1','123456789012','Juan','Santos','Dela Cruz','2007-05-15','Male','Catholic','Quezon City','09123456789','juan@example.com','Pedro Dela Cruz','Engineer','Maria Dela Cruz','Teacher','Grade 11',NULL,'withdrawn',NULL,'2','2025-07-03','2025-07-03 18:37:05','2025-07-18 17:59:08','Arts','','','0','','new'),
('2','234567890123','Maria','Reyes','Santos','2007-07-22','Female','Catholic','Makati City','09234567890','maria@example.com','Juan Santos','Businessman','Ana Santos','Accountant','Grade 11','ABM-11A','enrolled',NULL,'2','2025-07-03','2025-07-03 18:37:05','2025-07-20 17:03:00','ABM','','','0','','new'),
('3','345678901234','Pedro','Garcia','Reyes','2006-03-10','Male','Christian','Manila City','09345678901','pedro@example.com','Jose Reyes','Doctor','Ana Reyes','Nurse','Grade 12','ABM-12A','enrolled',NULL,'2','2025-07-03','2025-07-03 18:37:05','2025-07-06 21:37:25','ABM','','','0',NULL,'new'),
('7','677867864545','Glycel','Saga','Hilaos','1999-09-05','Male','catholic','San Francisco Dist Pagadian City Zamboanga Del Sur Philippines','09632441878','leojohnpro4@gmail.com','fdgfdg','xcvcx','cbcb','ghjjg','Grade 11','ABM-11A','enrolled','uploads/students/student_1751597359_1232.jpg','1','2025-07-04','2025-07-04 10:48:44','2025-07-06 13:19:17','ABM','erer','090765436554','0',NULL,'new'),
('8','453524234123','mark','Drag','Lopez','2000-09-21','Male','catholic','San Francisco Dist Pagadian City Zamboanga Del Sur Philippines','09464923087','bmaxleo12@gmail.com','fdgfdg','wqewqe','rytrytr','asdasd','Grade 11','ABM-11A','enrolled','uploads/students/student_1751776465_3081.jpg','1','2025-07-06','2025-07-06 12:34:25','2025-07-06 17:46:28','ABM','fgfsdfsd','083853554','0',NULL,'new'),
('9','123454455343','Saraherw','arnil','arman','2001-12-23','Male','Catholic','San Francisco Dist Pagadian City Zamboanga Del Sur Philippines','09632441878','leojohnpro4@gmail.com','Pedro Dela Cruz','Engineer','rytrytr','ghjjg','Grade 12','','pending',NULL,'1','2025-07-06','2025-07-06 22:15:22','2025-07-18 17:14:35','ABM','fgfsdfsd','090765436554','0','','old');


-- Table structure for table `subjects`
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `education_level` varchar(50) NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `subjects`
INSERT INTO `subjects` VALUES ('1','Early Literacy','KIN-EL','Basic reading and writing skills','Kindergarten','K','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('2','Mathematics and Numbers','KIN-MN','Basic number concepts and counting','Kindergarten','K','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('3','Creative Arts','KIN-CA','Art, music and creative expression','Kindergarten','K','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('4','Physical Development','KIN-PD','Motor skills and physical activities','Kindergarten','K','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('5','Social Studies','KIN-SS','Basic social skills and community awareness','Kindergarten','K','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('6','Science Exploration','KIN-SE','Basic science concepts and exploration','Kindergarten','K','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('7','English','ELE-ENG','English language and literature','Elementary','1','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('8','Mathematics','ELE-MATH','Basic mathematics','Elementary','1','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('9','Science','ELE-SCI','Basic science concepts','Elementary','1','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('10','Filipino','ELE-FIL','Filipino language','Elementary','1','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('11','Araling Panlipunan','ELE-AP','Social studies','Elementary','1','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('12','MAPEH','ELE-MAPEH','Music, Arts, Physical Education, and Health','Elementary','1','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('13','Edukasyon sa Pagpapakatao','ELE-ESP','Values education','Elementary','1','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('14','English','JHS-ENG','English language and literature','Junior High School','7','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('15','Mathematics','JHS-MATH','Mathematics','Junior High School','7','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('16','Science','JHS-SCI','Science','Junior High School','7','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('17','Filipino','JHS-FIL','Filipino language','Junior High School','7','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('18','Araling Panlipunan','JHS-AP','Social studies','Junior High School','7','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('19','MAPEH','JHS-MAPEH','Music, Arts, Physical Education, and Health','Junior High School','7','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('20','Edukasyon sa Pagpapakatao','JHS-ESP','Values education','Junior High School','7','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('21','Technology and Livelihood Education','JHS-TLE','Technology and livelihood education','Junior High School','7','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('22','Oral Communication','SHS-OCOM','Oral communication skills','Senior High School','11','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('23','Reading and Writing','SHS-RW','Reading and writing skills','Senior High School','11','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('24','Earth and Life Science','SHS-ELS','Earth and life science','Senior High School','11','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('25','Physical Science','SHS-PS','Physical science','Senior High School','11','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('26','Statistics and Probability','SHS-STAT','Statistics and probability','Senior High School','11','inactive','2025-07-03 23:46:43','2025-07-06 13:25:53'),
('27','General Mathematics','SHS-GMATH','General mathematics','Senior High School','11','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('28','Filipino sa Piling Larang','SHS-FPL','Filipino in selected fields','Senior High School','11','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('29','Personal Development','SHS-PD','Personal development','Senior High School','11','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('30','Understanding Culture, Society and Politics','SHS-UCSP','Culture, society and politics','Senior High School','11','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('31','Physical Education and Health','SHS-PE','Physical education and health','Senior High School','11','active','2025-07-03 23:46:43','2025-07-03 23:46:43'),
('32','SHS Registrar','8786','hjk','Senior High School','11','active','2025-07-04 17:53:51','2025-07-04 17:53:51'),
('33','SHS Registrar','8786','hjk','Senior High School','11','active','2025-07-04 17:58:48','2025-07-04 17:58:48'),
('34','Bronny','8786ki','lioopop','Senior High School','12','active','2025-07-06 13:25:41','2025-07-06 13:25:41');


-- Table structure for table `system_settings`
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `system_settings`
INSERT INTO `system_settings` VALUES ('1','backup_settings','{\"enabled\":false,\"frequency\":\"daily\",\"retention\":30,\"last_backup\":null}','2025-07-20 18:59:25','2025-07-20 18:59:25');


-- Table structure for table `teachers`
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `qualification` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contact_number` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `teachers`
INSERT INTO `teachers` VALUES ('17','3','Leo John','Bendijo','leojohnpro4@gmail.com','Senior High School','Statistics and Probability','','art','active','2025-07-06 18:37:08','2025-07-06 18:37:08','09632441878','uploads/teachers/teacher_1751798228_4072.jpeg');


-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','registrar','teacher') NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(255) DEFAULT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES ('1','admin','$2y$10$JcCUYKYGM6Z3QRzwfzMfveN2WHNS9.Pk4NHSJEElM4buBltO5Y4He','admin','System Administrator','admin@shsenrollment.com','active','2025-07-03 18:37:05',NULL,NULL,NULL),
('2','registrar','$2y$10$7TPNG7gPaKQAJ5wbXqRmieG4V88YacZSabQz5vGumVwDp08wtTcVW','registrar','SHS Registrar','registrar@shsenrollment.com','active','2025-07-03 18:37:05','uploads/users/user_1751606549_9249.jpeg',NULL,NULL),
('3','teacher1','$2y$10$ddSfckEQpfWEX5l6vJab7eBB/nvOJZbj.Cq5c0xEbihHMA9Uj80oO','teacher','Teacher User','teacher@example.com','active','2025-07-04 08:49:18',NULL,NULL,NULL),
('4','teacher2','$2y$10$zvFsoCwgZEknqaMsCZAhBehIzg20cduzYmwZAFewhAjfKvLcAYrR.','teacher','Second Teacher','teacher2@example.com','active','2025-07-04 08:59:19',NULL,NULL,NULL);


SET FOREIGN_KEY_CHECKS=1;

-- Database setup for SHS Enrollment System

-- Create database
CREATE DATABASE IF NOT EXISTS shs_enrollment;
USE shs_enrollment;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'registrar') NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students Table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lrn VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    religion VARCHAR(50) DEFAULT NULL,
    address TEXT,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    father_name VARCHAR(100) DEFAULT NULL,
    father_occupation VARCHAR(100) DEFAULT NULL,
    mother_name VARCHAR(100) DEFAULT NULL,
    mother_occupation VARCHAR(100) DEFAULT NULL,
    grade_level ENUM('Grade 11', 'Grade 12') NOT NULL,
    section VARCHAR(20),
    enrollment_status ENUM('enrolled', 'pending', 'withdrawn') DEFAULT 'pending',
    photo VARCHAR(255) DEFAULT NULL,
    enrolled_by INT,
    date_enrolled DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Senior High School Details Table
CREATE TABLE IF NOT EXISTS senior_highschool_details (
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
);

-- SHS Strands Table
CREATE TABLE IF NOT EXISTS shs_strands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    track_name VARCHAR(100) NOT NULL,
    strand_code VARCHAR(20) NOT NULL UNIQUE,
    strand_name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sections Table
CREATE TABLE IF NOT EXISTS sections (
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
);

-- Logs Table
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default SHS Strands
INSERT INTO shs_strands (track_name, strand_code, strand_name, description, status)
VALUES
    ('Academic', 'ABM', 'Accountancy, Business and Management', 'Focus on business-related fields and financial management', 'Active'),
    ('Academic', 'STEM', 'Science, Technology, Engineering, and Mathematics', 'Focus on science and math-related fields', 'Active'),
    ('Academic', 'HUMSS', 'Humanities and Social Sciences', 'Focus on literature, philosophy, social sciences', 'Active'),
    ('Academic', 'GAS', 'General Academic Strand', 'General subjects for undecided students', 'Active'),
    ('Technical-Vocational-Livelihood', 'TVL-HE', 'Home Economics', 'Skills related to household management', 'Active'),
    ('Technical-Vocational-Livelihood', 'TVL-ICT', 'Information and Communications Technology', 'Computer and tech-related skills', 'Active'),
    ('Technical-Vocational-Livelihood', 'TVL-IA', 'Industrial Arts', 'Skills related to manufacturing and production', 'Active'),
    ('Sports', 'Sports', 'Sports Track', 'Focus on physical education and sports development', 'Active'),
    ('Arts and Design', 'Arts', 'Arts and Design Track', 'Focus on visual and performing arts', 'Active');

-- Insert some sample sections
INSERT INTO sections (name, grade_level, strand, max_students, status, school_year, semester)
VALUES 
    ('ABM-11A', 'Grade 11', 'ABM', 40, 'Active', '2023-2024', 'First'),
    ('STEM-11A', 'Grade 11', 'STEM', 40, 'Active', '2023-2024', 'First'),
    ('HUMSS-11A', 'Grade 11', 'HUMSS', 40, 'Active', '2023-2024', 'First'),
    ('ABM-12A', 'Grade 12', 'ABM', 40, 'Active', '2023-2024', 'First'),
    ('STEM-12A', 'Grade 12', 'STEM', 40, 'Active', '2023-2024', 'First'),
    ('HUMSS-12A', 'Grade 12', 'HUMSS', 40, 'Active', '2023-2024', 'First');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role, name, email, status)
VALUES ('admin', '$2y$10$F9jXttSPAULe8HFh7HQjDO2p9iVl2Ij2JRr92VhFGqHCC7e0W8/2C', 'admin', 'System Administrator', 'admin@shsenrollment.com', 'active');

-- Insert sample users
INSERT INTO users (username, password, role, name, email, status)
VALUES 
('registrar', '$2y$10$F9jXttSPAULe8HFh7HQjDO2p9iVl2Ij2JRr92VhFGqHCC7e0W8/2C', 'registrar', 'SHS Registrar', 'registrar@shsenrollment.com', 'active');

-- Insert sample students
INSERT INTO students (lrn, first_name, middle_name, last_name, dob, gender, religion, address, contact_number, email, father_name, father_occupation, mother_name, mother_occupation, grade_level, section, enrollment_status, enrolled_by, date_enrolled)
VALUES
('123456789012', 'Juan', 'Santos', 'Dela Cruz', '2007-05-15', 'Male', 'Catholic', 'Quezon City', '09123456789', 'juan@example.com', 'Pedro Dela Cruz', 'Engineer', 'Maria Dela Cruz', 'Teacher', 'Grade 11', 'ABM-11A', 'enrolled', 2, CURDATE()),
('234567890123', 'Maria', 'Reyes', 'Santos', '2007-07-22', 'Female', 'Catholic', 'Makati City', '09234567890', 'maria@example.com', 'Juan Santos', 'Businessman', 'Ana Santos', 'Accountant', 'Grade 11', 'STEM-11A', 'enrolled', 2, CURDATE()),
('345678901234', 'Pedro', 'Garcia', 'Reyes', '2006-03-10', 'Male', 'Christian', 'Manila City', '09345678901', 'pedro@example.com', 'Jose Reyes', 'Doctor', 'Ana Reyes', 'Nurse', 'Grade 12', 'HUMSS-12A', 'enrolled', 2, CURDATE());

-- Insert sample senior highschool details
INSERT INTO senior_highschool_details (student_id, track, strand, semester, school_year, previous_school)
VALUES
(1, 'Academic', 'ABM', 'First', '2023-2024', 'Manila High School'),
(2, 'Academic', 'STEM', 'First', '2023-2024', 'Makati High School'),
(3, 'Academic', 'HUMSS', 'First', '2023-2024', 'Quezon City High School');

-- Insert sample logs
INSERT INTO logs (user_id, action, description)
VALUES
(1, 'LOGIN', 'Admin logged in'),
(2, 'ENROLL', 'Registered student Juan Dela Cruz'),
(2, 'ENROLL', 'Registered student Maria Santos'),
(2, 'ENROLL', 'Registered student Pedro Reyes'); 
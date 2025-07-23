-- Grading System Tables
-- This file contains the tables needed for a simple grading system

-- Grading Periods Table
CREATE TABLE IF NOT EXISTS grading_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    units DECIMAL(3,1) DEFAULT 1.0,
    grade_level VARCHAR(20),
    strand_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (strand_id) REFERENCES strands(id) ON DELETE SET NULL
);

-- Class Records Table
CREATE TABLE IF NOT EXISTS class_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    section VARCHAR(50) NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    grading_period_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (grading_period_id) REFERENCES grading_periods(id) ON DELETE CASCADE
);

-- Student Grades Table
CREATE TABLE IF NOT EXISTS student_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_record_id INT NOT NULL,
    written_work DECIMAL(5,2) DEFAULT 0,
    performance_task DECIMAL(5,2) DEFAULT 0,
    quarterly_assessment DECIMAL(5,2) DEFAULT 0,
    final_grade DECIMAL(5,2) DEFAULT 0,
    letter_grade VARCHAR(5),
    remarks VARCHAR(100),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_record_id) REFERENCES class_records(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Grade Components Table
CREATE TABLE IF NOT EXISTS grade_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    weight DECIMAL(5,2) NOT NULL,
    category ENUM('written_work', 'performance_task', 'quarterly_assessment') NOT NULL,
    class_record_id INT NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_record_id) REFERENCES class_records(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Component Scores Table
CREATE TABLE IF NOT EXISTS component_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    grade_component_id INT NOT NULL,
    score DECIMAL(5,2) DEFAULT 0,
    max_score DECIMAL(5,2) NOT NULL,
    percentage DECIMAL(5,2) DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_component_id) REFERENCES grade_components(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Grading System Settings Table
CREATE TABLE IF NOT EXISTS grading_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    written_work_weight DECIMAL(5,2) DEFAULT 30.00,
    performance_task_weight DECIMAL(5,2) DEFAULT 50.00,
    quarterly_assessment_weight DECIMAL(5,2) DEFAULT 20.00,
    passing_grade DECIMAL(5,2) DEFAULT 75.00,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default grading settings
INSERT INTO grading_settings (written_work_weight, performance_task_weight, quarterly_assessment_weight, passing_grade, created_by)
VALUES (30.00, 50.00, 20.00, 75.00, 1);

-- Create indexes for better performance
CREATE INDEX idx_student_grades_student ON student_grades(student_id);
CREATE INDEX idx_student_grades_class_record ON student_grades(class_record_id);
CREATE INDEX idx_class_records_teacher ON class_records(teacher_id);
CREATE INDEX idx_class_records_subject ON class_records(subject_id);
CREATE INDEX idx_component_scores_student ON component_scores(student_id);
CREATE INDEX idx_component_scores_component ON component_scores(grade_component_id); 
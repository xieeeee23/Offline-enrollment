-- Back Subjects Table
-- This table tracks students with back subjects (failed subjects that need to be retaken)

CREATE TABLE IF NOT EXISTS back_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    grade DECIMAL(5,2) DEFAULT NULL,
    status ENUM('pending', 'retaking', 'passed', 'failed') NOT NULL DEFAULT 'pending',
    remarks TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add irregular_status column to students table
ALTER TABLE students ADD COLUMN irregular_status BOOLEAN DEFAULT FALSE AFTER enrollment_status;

-- Index for faster lookups
CREATE INDEX idx_back_subjects_student ON back_subjects(student_id);
CREATE INDEX idx_back_subjects_subject ON back_subjects(subject_code); 
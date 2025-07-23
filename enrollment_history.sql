-- Enrollment History Table
-- This table tracks the enrollment history of students across different school years, semesters, and grade levels

CREATE TABLE IF NOT EXISTS enrollment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    school_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    section VARCHAR(50) NOT NULL,
    strand VARCHAR(50) DEFAULT NULL,
    enrollment_date DATE NOT NULL,
    enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'graduated', 'transferred') NOT NULL DEFAULT 'enrolled',
    remarks TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Index for faster lookups
CREATE INDEX idx_enrollment_history_student ON enrollment_history(student_id);
CREATE INDEX idx_enrollment_history_school_year ON enrollment_history(school_year);
CREATE INDEX idx_enrollment_history_semester ON enrollment_history(semester); 
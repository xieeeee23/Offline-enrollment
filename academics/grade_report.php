<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'teacher', 'registrar', 'parent'])) {
    echo '<div class="alert alert-danger">You do not have permission to access this resource.</div>';
    exit();
}

// Set up the page
$title = 'Grade Report';
$relative_path = '../../';
$extra_css = '
<style>
    body {
        font-family: "Poppins", Arial, sans-serif;
        margin: 20px;
    }
    .report-header {
        text-align: center;
        margin-bottom: 20px;
    }
    .school-name {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .report-title {
        font-size: 18px;
        margin-bottom: 15px;
    }
    .report-info {
        margin-bottom: 20px;
    }
    .report-info table {
        width: 100%;
        border-collapse: collapse;
    }
    .report-info td {
        padding: 5px;
    }
    .grades-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .grades-table th, .grades-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
    }
    .grades-table th {
        background-color: #f8f9fa;
    }
    .student-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    .signature-line {
        margin-top: 50px;
        border-top: 1px solid #000;
        width: 200px;
        text-align: center;
        padding-top: 5px;
    }
    .print-btn {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 999;
    }
    @media print {
        .print-btn {
            display: none;
        }
        body {
            margin: 0;
            padding: 15mm;
        }
    }
</style>
';

// Get report parameters
$report_type = isset($_GET['report_type']) ? cleanInput($_GET['report_type']) : 'class';
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$grade_level = isset($_GET['report_grade_level']) ? cleanInput($_GET['report_grade_level']) : '';
$section = isset($_GET['report_section']) ? cleanInput($_GET['report_section']) : '';
$period_id = isset($_GET['report_period']) ? cleanInput($_GET['report_period']) : '';
$format = isset($_GET['format']) ? cleanInput($_GET['format']) : 'html';

// Check if this is an Excel export
if ($format === 'excel') {
    // Set headers for Excel file download
    $filename = 'grade_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Set UTF-8 BOM for Excel to recognize UTF-8 encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write report title
    fputcsv($output, [SYSTEM_NAME]);
    fputcsv($output, ['GRADE REPORT']);
    fputcsv($output, ['Date Generated: ' . date('F d, Y')]);
    fputcsv($output, []);
    
    if ($report_type === 'student') {
        // Get student information
        $student_query = "SELECT s.*, CONCAT(t.first_name, ' ', t.last_name) as adviser_name 
                        FROM students s
                        LEFT JOIN teachers t ON s.section = t.section
                        WHERE s.id = ?";
        $stmt = mysqli_prepare($conn, $student_query);
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $student_result = mysqli_stmt_get_result($stmt);
        
        if ($student = mysqli_fetch_assoc($student_result)) {
            // Student information
            fputcsv($output, ['STUDENT INFORMATION']);
            fputcsv($output, ['Name:', $student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']]);
            fputcsv($output, ['LRN:', $student['lrn']]);
            fputcsv($output, ['Grade & Section:', $student['grade_level'] . ' - ' . $student['section']]);
            fputcsv($output, ['Adviser:', $student['adviser_name'] ?? 'Not Assigned']);
            fputcsv($output, []);
            
            // Get grading period information
            $period_query = "SELECT * FROM grading_periods WHERE id = ?";
            $stmt = mysqli_prepare($conn, $period_query);
            mysqli_stmt_bind_param($stmt, "i", $period_id);
            mysqli_stmt_execute($stmt);
            $period_result = mysqli_stmt_get_result($stmt);
            
            if ($period = mysqli_fetch_assoc($period_result)) {
                fputcsv($output, ['Grading Period:', $period['name'] . ' (' . $period['school_year'] . ')']);
                fputcsv($output, []);
                
                // Get grades for this student
                $grades_query = "SELECT g.*, s.name as subject_name, 
                                CONCAT(t.first_name, ' ', t.last_name) as teacher_name
                                FROM grades g
                                LEFT JOIN subjects s ON g.subject_id = s.id
                                LEFT JOIN teachers t ON g.teacher_id = t.id
                                WHERE g.student_id = ? AND g.grading_period_id = ?
                                ORDER BY s.name";
                $stmt = mysqli_prepare($conn, $grades_query);
                mysqli_stmt_bind_param($stmt, "ii", $student_id, $period_id);
                mysqli_stmt_execute($stmt);
                $grades_result = mysqli_stmt_get_result($stmt);
                
                // Write grades table header
                fputcsv($output, ['Subject', 'Teacher', 'Grade', 'Letter Grade', 'Remarks']);
                
                // Calculate average
                $total_grade = 0;
                $grade_count = 0;
                
                // Write grades data
                while ($grade = mysqli_fetch_assoc($grades_result)) {
                    $letter_grade = getLetterGrade($grade['grade']);
                    $remarks = !empty($grade['remarks']) ? $grade['remarks'] : getRemarks($grade['grade']);
                    
                    fputcsv($output, [
                        $grade['subject_name'] ?? $grade['subject_id'],
                        $grade['teacher_name'] ?? 'Not Assigned',
                        $grade['grade'],
                        $letter_grade,
                        $remarks
                    ]);
                    
                    $total_grade += $grade['grade'];
                    $grade_count++;
                }
                
                // Calculate and write average
                if ($grade_count > 0) {
                    $average = round($total_grade / $grade_count, 2);
                    fputcsv($output, []);
                    fputcsv($output, ['Average:', '', $average, getLetterGrade($average), getRemarks($average)]);
                }
            }
        }
    } else {
        // Class report
        // Get class information
        fputcsv($output, ['CLASS INFORMATION']);
        fputcsv($output, ['Grade Level:', $grade_level]);
        fputcsv($output, ['Section:', $section]);
        fputcsv($output, []);
        
        // Get grading period information
        if ($period_id === 'all') {
            fputcsv($output, ['Grading Period:', 'All Periods']);
        } else {
            $period_query = "SELECT * FROM grading_periods WHERE id = ?";
            $stmt = mysqli_prepare($conn, $period_query);
            mysqli_stmt_bind_param($stmt, "i", $period_id);
            mysqli_stmt_execute($stmt);
            $period_result = mysqli_stmt_get_result($stmt);
            
            if ($period = mysqli_fetch_assoc($period_result)) {
                fputcsv($output, ['Grading Period:', $period['name'] . ' (' . $period['school_year'] . ')']);
            }
        }
        fputcsv($output, []);
        
        // Get students in this class
        $students_query = "SELECT * FROM students 
                          WHERE grade_level = ? AND section = ?
                          ORDER BY last_name, first_name";
        $stmt = mysqli_prepare($conn, $students_query);
        mysqli_stmt_bind_param($stmt, "ss", $grade_level, $section);
        mysqli_stmt_execute($stmt);
        $students_result = mysqli_stmt_get_result($stmt);
        
        // Get subjects for this grade level
        $subjects_query = "SELECT * FROM subjects 
                          WHERE grade_level = ? OR grade_level = 'All'
                          ORDER BY name";
        $stmt = mysqli_prepare($conn, $subjects_query);
        mysqli_stmt_bind_param($stmt, "s", $grade_level);
        mysqli_stmt_execute($stmt);
        $subjects_result = mysqli_stmt_get_result($stmt);
        
        $subjects = [];
        while ($subject = mysqli_fetch_assoc($subjects_result)) {
            $subjects[] = $subject;
        }
        
        // Write header row with student name and subjects
        $header_row = ['Student Name', 'LRN'];
        foreach ($subjects as $subject) {
            $header_row[] = $subject['name'];
        }
        $header_row[] = 'Average';
        fputcsv($output, $header_row);
        
        // Write data rows
        while ($student = mysqli_fetch_assoc($students_result)) {
            $row = [
                $student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name'],
                $student['lrn']
            ];
            
            $total_grade = 0;
            $grade_count = 0;
            
            foreach ($subjects as $subject) {
                // Get grade for this student and subject
                if ($period_id === 'all') {
                    // Get average across all periods
                    $grade_query = "SELECT AVG(grade) as avg_grade FROM grades 
                                   WHERE student_id = ? AND subject_id = ?";
                    $stmt = mysqli_prepare($conn, $grade_query);
                    mysqli_stmt_bind_param($stmt, "is", $student['id'], $subject['id']);
                } else {
                    // Get grade for specific period
                    $grade_query = "SELECT grade FROM grades 
                                   WHERE student_id = ? AND subject_id = ? AND grading_period_id = ?";
                    $stmt = mysqli_prepare($conn, $grade_query);
                    mysqli_stmt_bind_param($stmt, "isi", $student['id'], $subject['id'], $period_id);
                }
                
                mysqli_stmt_execute($stmt);
                $grade_result = mysqli_stmt_get_result($stmt);
                $grade_row = mysqli_fetch_assoc($grade_result);
                
                $grade = $grade_row ? ($period_id === 'all' ? round($grade_row['avg_grade'], 2) : $grade_row['grade']) : '';
                $row[] = $grade;
                
                if ($grade !== '') {
                    $total_grade += $grade;
                    $grade_count++;
                }
            }
            
            // Calculate average
            $average = $grade_count > 0 ? round($total_grade / $grade_count, 2) : '';
            $row[] = $average;
            
            fputcsv($output, $row);
        }
    }
    
    // Close output stream and exit
    fclose($output);
    exit;
}

// Get school information
// Check if settings table exists, create if not
$check_table_query = "SHOW TABLES LIKE 'settings'";
$check_table_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_table_result) == 0) {
    // Create settings table
    $create_table_query = "CREATE TABLE settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        // Insert default settings
        $default_settings = [
            ['school_name', 'THE KRISLIZZ INTERNATIONAL ACADEMY INC.'],
            ['school_address', 'Isabela, Philippines'],
            ['school_contact', '(123) 456-7890'],
            ['school_email', 'info@krislizz-academy.edu.ph'],
            ['school_year', '2023-2024']
        ];
        
        $insert_query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        
        foreach ($default_settings as $setting) {
            mysqli_stmt_bind_param($insert_stmt, 'ss', $setting[0], $setting[1]);
            mysqli_stmt_execute($insert_stmt);
        }
    } else {
        echo '<div class="alert alert-danger">Error creating settings table: ' . mysqli_error($conn) . '</div>';
    }
}

$school_query = "SELECT * FROM settings WHERE setting_key IN ('school_name', 'school_address', 'school_contact', 'school_email')";
$school_result = mysqli_query($conn, $school_query);
$school_info = [];

while ($row = mysqli_fetch_assoc($school_result)) {
    $school_info[$row['setting_key']] = $row['setting_value'];
}

// Get school year
$school_year_query = "SELECT setting_value FROM settings WHERE setting_key = 'school_year'";
$school_year_result = mysqli_query($conn, $school_year_query);
$school_year_row = mysqli_fetch_assoc($school_year_result);
$school_year = $school_year_row ? $school_year_row['setting_value'] : '2023-2024';

// Check if grading_periods table exists, create if not
$check_table_query = "SHOW TABLES LIKE 'grading_periods'";
$check_table_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_table_result) == 0) {
    // Create grading_periods table
    $create_table_query = "CREATE TABLE grading_periods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        school_year VARCHAR(20) NOT NULL,
        is_current TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        // Insert default grading periods
        $current_year = date('Y');
        $default_periods = [
            ['First Quarter', "$current_year-06-01", "$current_year-08-31", '2023-2024', 1],
            ['Second Quarter', "$current_year-09-01", "$current_year-11-30", '2023-2024', 0],
            ['Third Quarter', "$current_year-12-01", ($current_year+1)."-02-28", '2023-2024', 0],
            ['Fourth Quarter', ($current_year+1)."-03-01", ($current_year+1)."-05-31", '2023-2024', 0]
        ];
        
        $insert_query = "INSERT INTO grading_periods (name, start_date, end_date, school_year, is_current) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        
        foreach ($default_periods as $period) {
            mysqli_stmt_bind_param($insert_stmt, 'ssssi', $period[0], $period[1], $period[2], $period[3], $period[4]);
            mysqli_stmt_execute($insert_stmt);
        }
    } else {
        echo '<div class="alert alert-danger">Error creating grading_periods table: ' . mysqli_error($conn) . '</div>';
    }
}

// Check if users table exists, create if not
$check_table_query = "SHOW TABLES LIKE 'users'";
$check_table_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_table_result) == 0) {
    // Create users table
    $create_table_query = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        role ENUM('admin', 'teacher', 'registrar', 'student', 'parent') NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        // Insert default admin user with password 'admin123'
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_query = "INSERT INTO users (username, password, name, email, role) VALUES ('admin', ?, 'System Administrator', 'admin@example.com', 'admin')";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, 's', $admin_password);
        mysqli_stmt_execute($insert_stmt);
    } else {
        echo '<div class="alert alert-danger">Error creating users table: ' . mysqli_error($conn) . '</div>';
    }
}

// Check if teachers table exists, create if not
$check_table_query = "SHOW TABLES LIKE 'teachers'";
$check_table_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_table_result) == 0) {
    // Create teachers table
    $create_table_query = "CREATE TABLE teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50),
        gender ENUM('Male', 'Female', 'Other') NOT NULL,
        date_of_birth DATE,
        address TEXT,
        contact_number VARCHAR(20),
        email VARCHAR(100),
        specialty VARCHAR(100),
        qualification TEXT,
        hire_date DATE,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        echo '<div class="alert alert-danger">Error creating teachers table: ' . mysqli_error($conn) . '</div>';
    }
}

// Check if students table exists, create if not
$check_table_query = "SHOW TABLES LIKE 'students'";
$check_table_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_table_result) == 0) {
    // Create students table
    $create_table_query = "CREATE TABLE students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn VARCHAR(20) NOT NULL UNIQUE,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50),
        dob DATE NOT NULL,
        gender ENUM('Male', 'Female', 'Other') NOT NULL,
        address TEXT,
        contact_number VARCHAR(20),
        guardian_name VARCHAR(100),
        guardian_contact VARCHAR(20),
        grade_level VARCHAR(20) NOT NULL,
        section VARCHAR(20) NOT NULL,
        enrollment_status ENUM('enrolled', 'pending', 'withdrawn') DEFAULT 'pending',
        photo VARCHAR(255) DEFAULT NULL,
        enrolled_by INT,
        date_enrolled DATE,
        education_level_id INT DEFAULT NULL,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        echo '<div class="alert alert-danger">Error creating students table: ' . mysqli_error($conn) . '</div>';
    }
}

// Check if grades table exists, create if not
$check_table_query = "SHOW TABLES LIKE 'grades'";
$check_table_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_table_result) == 0) {
    // Create grades table
    $create_table_query = "CREATE TABLE grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id VARCHAR(100) NOT NULL,
        teacher_id INT NOT NULL,
        grading_period_id INT NOT NULL,
        grade DECIMAL(5,2) NOT NULL,
        remarks TEXT,
        date_recorded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
        FOREIGN KEY (grading_period_id) REFERENCES grading_periods(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        echo '<div class="alert alert-danger">Error creating grades table: ' . mysqli_error($conn) . '</div>';
    }
}

// Check if schedules table exists, create if not
$check_table_query = "SHOW TABLES LIKE 'schedules'";
$check_table_result = mysqli_query($conn, $check_table_query);
if (mysqli_num_rows($check_table_result) == 0) {
    // Create schedules table
    $create_table_query = "CREATE TABLE schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        subject VARCHAR(100) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        section VARCHAR(50) NOT NULL,
        day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        room VARCHAR(50),
        school_year VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        echo '<div class="alert alert-danger">Error creating schedules table: ' . mysqli_error($conn) . '</div>';
    }
}

// Get grading period information
$periods = [];
$period_name = '';

if ($period_id === 'all') {
    // All periods
    $periods_query = "SELECT * FROM grading_periods ORDER BY start_date";
    $periods_result = mysqli_query($conn, $periods_query);
    
    while ($row = mysqli_fetch_assoc($periods_result)) {
        $periods[] = $row;
    }
    
    $period_name = 'All Grading Periods';
} else {
    // Single period
    $period_query = "SELECT * FROM grading_periods WHERE id = ?";
    $period_stmt = mysqli_prepare($conn, $period_query);
    mysqli_stmt_bind_param($period_stmt, 's', $period_id);
    mysqli_stmt_execute($period_stmt);
    $period_result = mysqli_stmt_get_result($period_stmt);
    $period = mysqli_fetch_assoc($period_result);
    
    if ($period) {
        $periods[] = $period;
        $period_name = $period['name'] . ' (' . $period['school_year'] . ')';
    }
}

// Function to calculate letter grade
function getLetterGrade($grade) {
    if ($grade >= 90) return 'A';
    if ($grade >= 80) return 'B';
    if ($grade >= 70) return 'C';
    if ($grade >= 60) return 'D';
    return 'F';
}

// Function to get the remarks based on grade
function getRemarks($grade) {
    if ($grade >= 90) return 'Outstanding';
    if ($grade >= 80) return 'Very Satisfactory';
    if ($grade >= 70) return 'Satisfactory';
    if ($grade >= 60) return 'Fairly Satisfactory';
    return 'Needs Improvement';
}

// Handle student report
if ($report_type === 'student' && $student_id > 0) {
    // Get student information
    $student_query = "SELECT * FROM students WHERE id = ?";
    $student_stmt = mysqli_prepare($conn, $student_query);
    mysqli_stmt_bind_param($student_stmt, 'i', $student_id);
    mysqli_stmt_execute($student_stmt);
    $student_result = mysqli_stmt_get_result($student_stmt);
    $student = mysqli_fetch_assoc($student_result);
    
    if (!$student) {
        echo '<div class="alert alert-danger">Student not found.</div>';
        exit();
    }
    
    // Get all subjects
    $subjects_query = "SELECT DISTINCT subject_id FROM grades WHERE student_id = ? ORDER BY subject_id";
    $subjects_stmt = mysqli_prepare($conn, $subjects_query);
    mysqli_stmt_bind_param($subjects_stmt, 'i', $student_id);
    mysqli_stmt_execute($subjects_stmt);
    $subjects_result = mysqli_stmt_get_result($subjects_stmt);
    $subjects = [];
    
    while ($row = mysqli_fetch_assoc($subjects_result)) {
        $subjects[] = $row['subject_id'];
    }
    
    // Get grades for each subject and period
    $grades_data = [];
    $overall_averages = [];
    
    foreach ($periods as $period) {
        $period_id = $period['id'];
        $grades_query = "SELECT g.*, t.first_name, t.last_name 
                        FROM grades g
                        LEFT JOIN teachers t ON g.teacher_id = t.id
                        WHERE g.student_id = ? AND g.grading_period_id = ?";
        $grades_stmt = mysqli_prepare($conn, $grades_query);
        mysqli_stmt_bind_param($grades_stmt, 'ii', $student_id, $period_id);
        mysqli_stmt_execute($grades_stmt);
        $grades_result = mysqli_stmt_get_result($grades_stmt);
        
        $period_grades = [];
        $sum = 0;
        $count = 0;
        
        while ($row = mysqli_fetch_assoc($grades_result)) {
            $period_grades[$row['subject_id']] = [
                'grade' => $row['grade'],
                'remarks' => $row['remarks'],
                'teacher' => $row['first_name'] . ' ' . $row['last_name']
            ];
            
            $sum += $row['grade'];
            $count++;
        }
        
        $grades_data[$period_id] = $period_grades;
        
        // Calculate average for this period
        $overall_averages[$period_id] = $count > 0 ? round($sum / $count, 2) : 0;
    }
    
    // Start generating the report
    require_once $relative_path . 'includes/report_header.php';
    ?>

    <button class="btn btn-primary print-btn" onclick="window.print()">
        <i class="fas fa-print me-2"></i> Print Report
    </button>

    <div class="report-header">
        <div class="school-name"><?php echo htmlspecialchars($school_info['school_name'] ?? 'THE KRISLIZZ INTERNATIONAL ACADEMY INC.'); ?></div>
        <div>School Year: <?php echo htmlspecialchars($school_year); ?></div>
        <div class="report-title mt-3">STUDENT REPORT CARD</div>
    </div>

    <div class="report-info">
        <table>
            <tr>
                <td width="15%"><strong>Student Name:</strong></td>
                <td width="35%"><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . (isset($student['middle_name']) ? ' ' . $student['middle_name'] : '')); ?></td>
                <td width="15%"><strong>LRN:</strong></td>
                <td width="35%"><?php echo htmlspecialchars($student['lrn']); ?></td>
            </tr>
            <tr>
                <td><strong>Grade & Section:</strong></td>
                <td><?php echo htmlspecialchars('Grade ' . $student['grade_level'] . ' - ' . $student['section']); ?></td>
                <td><strong>School Year:</strong></td>
                <td><?php echo htmlspecialchars($school_year); ?></td>
            </tr>
        </table>
    </div>

    <div class="grades-container">
        <table class="grades-table">
            <thead>
                <tr>
                    <th rowspan="2">Subject</th>
                    <?php foreach ($periods as $period): ?>
                    <th colspan="2"><?php echo htmlspecialchars($period['name']); ?></th>
                    <?php endforeach; ?>
                    <?php if (count($periods) > 1): ?>
                    <th rowspan="2">Final Grade</th>
                    <th rowspan="2">Remarks</th>
                    <?php endif; ?>
                </tr>
                <tr>
                    <?php foreach ($periods as $period): ?>
                    <th>Grade</th>
                    <th>Letter</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                <tr>
                    <td><?php echo htmlspecialchars($subject); ?></td>
                    <?php 
                    $subject_sum = 0;
                    $subject_count = 0;
                    foreach ($periods as $period): 
                        $period_id = $period['id'];
                        $grade = isset($grades_data[$period_id][$subject]) ? $grades_data[$period_id][$subject]['grade'] : '-';
                        
                        if (is_numeric($grade)) {
                            $subject_sum += $grade;
                            $subject_count++;
                        }
                    ?>
                    <td><?php echo is_numeric($grade) ? number_format($grade, 2) : $grade; ?></td>
                    <td><?php echo is_numeric($grade) ? getLetterGrade($grade) : '-'; ?></td>
                    <?php endforeach; ?>
                    
                    <?php if (count($periods) > 1): 
                        $final_grade = $subject_count > 0 ? round($subject_sum / $subject_count, 2) : '-';
                    ?>
                    <td><?php echo is_numeric($final_grade) ? number_format($final_grade, 2) : $final_grade; ?></td>
                    <td><?php echo is_numeric($final_grade) ? getRemarks($final_grade) : '-'; ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                
                <tr>
                    <td><strong>Overall Average</strong></td>
                    <?php 
                    $final_average_sum = 0;
                    $final_average_count = 0;
                    foreach ($periods as $period): 
                        $period_id = $period['id'];
                        $avg = $overall_averages[$period_id];
                        
                        if ($avg > 0) {
                            $final_average_sum += $avg;
                            $final_average_count++;
                        }
                    ?>
                    <td><strong><?php echo $avg > 0 ? number_format($avg, 2) : '-'; ?></strong></td>
                    <td><strong><?php echo $avg > 0 ? getLetterGrade($avg) : '-'; ?></strong></td>
                    <?php endforeach; ?>
                    
                    <?php if (count($periods) > 1): 
                        $final_overall = $final_average_count > 0 ? round($final_average_sum / $final_average_count, 2) : '-';
                    ?>
                    <td><strong><?php echo is_numeric($final_overall) ? number_format($final_overall, 2) : $final_overall; ?></strong></td>
                    <td><strong><?php echo is_numeric($final_overall) ? getRemarks($final_overall) : '-'; ?></strong></td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="signatures mt-4">
        <div class="row">
            <div class="col-md-4 text-center">
                <div class="signature-line">Class Adviser</div>
            </div>
            <div class="col-md-4 text-center">
                <div class="signature-line">School Registrar</div>
            </div>
            <div class="col-md-4 text-center">
                <div class="signature-line">School Principal</div>
            </div>
        </div>
    </div>

    <?php
    require_once $relative_path . 'includes/report_footer.php';
}
// Handle class report
else if ($report_type === 'class') {
    // Build query based on filters
    $query_params = [];
    $where_clauses = ["1=1"];
    
    if (!empty($grade_level)) {
        $where_clauses[] = "s.grade_level = ?";
        $query_params[] = $grade_level;
    }
    
    if (!empty($section)) {
        $where_clauses[] = "s.section = ?";
        $query_params[] = $section;
    }
    
    // Get students
    $students_sql = "SELECT s.* FROM students s WHERE " . implode(' AND ', $where_clauses) . " ORDER BY s.grade_level, s.section, s.last_name, s.first_name";
    
    $students_stmt = mysqli_prepare($conn, $students_sql);
    
    if (!empty($query_params)) {
        $types = str_repeat('s', count($query_params));
        mysqli_stmt_bind_param($students_stmt, $types, ...$query_params);
    }
    
    mysqli_stmt_execute($students_stmt);
    $students_result = mysqli_stmt_get_result($students_stmt);
    $students = [];
    
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[$row['id']] = $row;
    }
    
    // Get subjects
    $subjects_query = "SELECT DISTINCT subject FROM schedules ORDER BY subject";
    $subjects_result = mysqli_query($conn, $subjects_query);
    $subjects = [];
    
    while ($row = mysqli_fetch_assoc($subjects_result)) {
        $subjects[] = $row['subject'];
    }
    
    // Get grades for each student and subject
    $grades_data = [];
    
    foreach ($students as $student) {
        $student_id = $student['id'];
        
        if ($period_id === 'all') {
            // Get averages across all periods
            $grades_query = "SELECT subject_id, AVG(grade) as average_grade
                            FROM grades 
                            WHERE student_id = ?
                            GROUP BY subject_id";
            $grades_stmt = mysqli_prepare($conn, $grades_query);
            mysqli_stmt_bind_param($grades_stmt, 'i', $student_id);
        } else {
            // Get grades for specific period
            $grades_query = "SELECT subject_id, grade
                            FROM grades 
                            WHERE student_id = ? AND grading_period_id = ?";
            $grades_stmt = mysqli_prepare($conn, $grades_query);
            mysqli_stmt_bind_param($grades_stmt, 'ii', $student_id, $period_id);
        }
        
        mysqli_stmt_execute($grades_stmt);
        $grades_result = mysqli_stmt_get_result($grades_stmt);
        
        $student_grades = [];
        while ($row = mysqli_fetch_assoc($grades_result)) {
            $student_grades[$row['subject_id']] = $period_id === 'all' ? $row['average_grade'] : $row['grade'];
        }
        
        $grades_data[$student_id] = $student_grades;
    }
    
    // Start generating the report
    require_once $relative_path . 'includes/report_header.php';
    ?>

    <button class="btn btn-primary print-btn" onclick="window.print()">
        <i class="fas fa-print me-2"></i> Print Report
    </button>

    <div class="report-header">
        <div class="school-name"><?php echo htmlspecialchars($school_info['school_name'] ?? 'THE KRISLIZZ INTERNATIONAL ACADEMY INC.'); ?></div>
        <div>School Year: <?php echo htmlspecialchars($school_year); ?></div>
        <div class="report-title mt-3">CLASS GRADE REPORT</div>
        <div>
            <?php if (!empty($grade_level) && !empty($section)): ?>
            Grade <?php echo htmlspecialchars($grade_level); ?> - <?php echo htmlspecialchars($section); ?>
            <?php elseif (!empty($grade_level)): ?>
            Grade <?php echo htmlspecialchars($grade_level); ?> (All Sections)
            <?php else: ?>
            All Grades and Sections
            <?php endif; ?>
        </div>
        <div><?php echo htmlspecialchars($period_name); ?></div>
    </div>

    <?php if (empty($students)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> No students found for the selected criteria.
    </div>
    <?php else: ?>

    <div class="grades-container mt-4">
        <table class="grades-table">
            <thead>
                <tr>
                    <th rowspan="2">#</th>
                    <th rowspan="2">Student Name</th>
                    <?php if (!empty($grade_level) && empty($section)): ?>
                    <th rowspan="2">Section</th>
                    <?php endif; ?>
                    <?php if (empty($grade_level)): ?>
                    <th rowspan="2">Grade-Section</th>
                    <?php endif; ?>
                    <?php foreach ($subjects as $subject): ?>
                    <th colspan="2"><?php echo htmlspecialchars($subject); ?></th>
                    <?php endforeach; ?>
                    <th colspan="2">Overall</th>
                </tr>
                <tr>
                    <?php foreach ($subjects as $subject): ?>
                    <th>Grade</th>
                    <th>Letter</th>
                    <?php endforeach; ?>
                    <th>Avg</th>
                    <th>Letter</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                foreach ($students as $student_id => $student): 
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . (isset($student['middle_name']) ? ' ' . $student['middle_name'] : '')); ?></td>
                    
                    <?php if (!empty($grade_level) && empty($section)): ?>
                    <td><?php echo htmlspecialchars($student['section']); ?></td>
                    <?php endif; ?>
                    
                    <?php if (empty($grade_level)): ?>
                    <td><?php echo htmlspecialchars($student['grade_level'] . '-' . $student['section']); ?></td>
                    <?php endif; ?>
                    
                    <?php 
                    $sum = 0;
                    $count = 0;
                    foreach ($subjects as $subject): 
                        $grade = isset($grades_data[$student_id][$subject]) ? $grades_data[$student_id][$subject] : '-';
                        
                        if (is_numeric($grade)) {
                            $sum += $grade;
                            $count++;
                        }
                    ?>
                    <td><?php echo is_numeric($grade) ? number_format($grade, 2) : $grade; ?></td>
                    <td><?php echo is_numeric($grade) ? getLetterGrade($grade) : '-'; ?></td>
                    <?php endforeach; ?>
                    
                    <?php 
                        $avg = $count > 0 ? round($sum / $count, 2) : '-';
                    ?>
                    <td><strong><?php echo is_numeric($avg) ? number_format($avg, 2) : $avg; ?></strong></td>
                    <td><strong><?php echo is_numeric($avg) ? getLetterGrade($avg) : '-'; ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="signatures mt-4">
        <div class="row">
            <div class="col-md-4 text-center">
                <div class="signature-line">Prepared by</div>
            </div>
            <div class="col-md-4 text-center">
                <div class="signature-line">Verified by</div>
            </div>
            <div class="col-md-4 text-center">
                <div class="signature-line">School Principal</div>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <?php
    require_once $relative_path . 'includes/report_footer.php';
}
else {
    echo '<div class="alert alert-danger">Invalid report parameters.</div>';
}
?> 
<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'teacher', 'registrar'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You do not have permission to access this resource.']);
    exit();
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="grade_report.xls"');
header('Cache-Control: max-age=0');

// Get parameters
$report_type = isset($_GET['report_type']) ? cleanInput($_GET['report_type']) : 'class';
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$grade_level = isset($_GET['report_grade_level']) ? cleanInput($_GET['report_grade_level']) : '';
$section = isset($_GET['report_section']) ? cleanInput($_GET['report_section']) : '';
$period_id = isset($_GET['report_period']) ? cleanInput($_GET['report_period']) : '';

// Get school information
$school_query = "SELECT * FROM settings WHERE setting_key IN ('school_name', 'school_address')";
$school_result = mysqli_query($conn, $school_query);
$school_info = [];

while ($row = mysqli_fetch_assoc($school_result)) {
    $school_info[$row['setting_key']] = $row['setting_value'];
}

// Get school year
$school_year_query = "SELECT setting_value FROM settings WHERE setting_key = 'school_year'";
$school_year_result = mysqli_query($conn, $school_year_query);
$school_year_row = mysqli_fetch_assoc($school_year_result);
$school_year = $school_year_row ? $school_year_row['setting_value'] : '';

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

// Start creating the Excel content
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
echo "<style>
    table { border-collapse: collapse; }
    th, td { border: 1px solid #000; padding: 5px; text-align: center; }
    th { background-color: #f0f0f0; }
</style>";
echo "</head>";
echo "<body>";

// School header
echo "<h1>" . htmlspecialchars($school_info['school_name'] ?? 'THE KRISLIZZ INTERNATIONAL ACADEMY INC.') . "</h1>";
echo "<h2>Grade Report - " . htmlspecialchars($period_name) . "</h2>";
echo "<p>School Year: " . htmlspecialchars($school_year) . "</p>";

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
        echo "<p>Student not found.</p>";
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
    
    // Student information
    echo "<h3>Student Report Card</h3>";
    echo "<table border='0' cellpadding='5' style='border: none;'>";
    echo "<tr><td style='text-align: left; border: none;'><b>Student Name:</b></td><td style='text-align: left; border: none;'>" . htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']) . "</td>";
    echo "<td style='text-align: left; border: none;'><b>LRN:</b></td><td style='text-align: left; border: none;'>" . htmlspecialchars($student['lrn']) . "</td></tr>";
    echo "<tr><td style='text-align: left; border: none;'><b>Grade & Section:</b></td><td style='text-align: left; border: none;'>Grade " . htmlspecialchars($student['grade_level'] . ' - ' . $student['section']) . "</td>";
    echo "<td style='text-align: left; border: none;'><b>School Year:</b></td><td style='text-align: left; border: none;'>" . htmlspecialchars($school_year) . "</td></tr>";
    echo "</table>";
    echo "<br>";
    
    // Grades table
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>";
    echo "<th rowspan='2'>Subject</th>";
    foreach ($periods as $period) {
        echo "<th colspan='2'>" . htmlspecialchars($period['name']) . "</th>";
    }
    if (count($periods) > 1) {
        echo "<th rowspan='2'>Final Grade</th>";
        echo "<th rowspan='2'>Remarks</th>";
    }
    echo "</tr>";
    
    echo "<tr>";
    foreach ($periods as $period) {
        echo "<th>Grade</th>";
        echo "<th>Letter</th>";
    }
    echo "</tr>";
    
    foreach ($subjects as $subject) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($subject) . "</td>";
        
        $subject_sum = 0;
        $subject_count = 0;
        foreach ($periods as $period) {
            $period_id = $period['id'];
            $grade = isset($grades_data[$period_id][$subject]) ? $grades_data[$period_id][$subject]['grade'] : '-';
            
            if (is_numeric($grade)) {
                $subject_sum += $grade;
                $subject_count++;
            }
            
            echo "<td>" . (is_numeric($grade) ? number_format($grade, 2) : $grade) . "</td>";
            echo "<td>" . (is_numeric($grade) ? getLetterGrade($grade) : '-') . "</td>";
        }
        
        if (count($periods) > 1) {
            $final_grade = $subject_count > 0 ? round($subject_sum / $subject_count, 2) : '-';
            echo "<td>" . (is_numeric($final_grade) ? number_format($final_grade, 2) : $final_grade) . "</td>";
            echo "<td>" . (is_numeric($final_grade) ? getRemarks($final_grade) : '-') . "</td>";
        }
        
        echo "</tr>";
    }
    
    // Overall average
    echo "<tr>";
    echo "<td><b>Overall Average</b></td>";
    
    $final_average_sum = 0;
    $final_average_count = 0;
    foreach ($periods as $period) {
        $period_id = $period['id'];
        $avg = $overall_averages[$period_id];
        
        if ($avg > 0) {
            $final_average_sum += $avg;
            $final_average_count++;
        }
        
        echo "<td><b>" . ($avg > 0 ? number_format($avg, 2) : '-') . "</b></td>";
        echo "<td><b>" . ($avg > 0 ? getLetterGrade($avg) : '-') . "</b></td>";
    }
    
    if (count($periods) > 1) {
        $final_overall = $final_average_count > 0 ? round($final_average_sum / $final_average_count, 2) : '-';
        echo "<td><b>" . (is_numeric($final_overall) ? number_format($final_overall, 2) : $final_overall) . "</b></td>";
        echo "<td><b>" . (is_numeric($final_overall) ? getRemarks($final_overall) : '-') . "</b></td>";
    }
    
    echo "</tr>";
    echo "</table>";
    
    // Signature lines
    echo "<br><br>";
    echo "<table border='0' cellpadding='5' style='border: none;'>";
    echo "<tr>";
    echo "<td style='text-align: center; border: none;'>___________________________<br>Class Adviser</td>";
    echo "<td style='text-align: center; border: none;'>___________________________<br>School Registrar</td>";
    echo "<td style='text-align: center; border: none;'>___________________________<br>School Principal</td>";
    echo "</tr>";
    echo "</table>";
}
// Handle class report
else if ($report_type === 'class') {
    // Build query based on filters
    $query_params = [];
    $where_clauses = ["s.status = 'Active'"];
    
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
    
    // Class information
    echo "<h3>Class Grade Report</h3>";
    
    if (!empty($grade_level) && !empty($section)) {
        echo "<p>Grade " . htmlspecialchars($grade_level) . " - " . htmlspecialchars($section) . "</p>";
    } elseif (!empty($grade_level)) {
        echo "<p>Grade " . htmlspecialchars($grade_level) . " (All Sections)</p>";
    } else {
        echo "<p>All Grades and Sections</p>";
    }
    echo "<p>" . htmlspecialchars($period_name) . "</p>";
    echo "<br>";
    
    if (empty($students)) {
        echo "<p>No students found for the selected criteria.</p>";
    } else {
        // Grades table
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        echo "<th rowspan='2'>#</th>";
        echo "<th rowspan='2'>Student Name</th>";
        
        if (!empty($grade_level) && empty($section)) {
            echo "<th rowspan='2'>Section</th>";
        }
        
        if (empty($grade_level)) {
            echo "<th rowspan='2'>Grade-Section</th>";
        }
        
        foreach ($subjects as $subject) {
            echo "<th colspan='2'>" . htmlspecialchars($subject) . "</th>";
        }
        
        echo "<th colspan='2'>Overall</th>";
        echo "</tr>";
        
        echo "<tr>";
        foreach ($subjects as $subject) {
            echo "<th>Grade</th>";
            echo "<th>Letter</th>";
        }
        echo "<th>Avg</th>";
        echo "<th>Letter</th>";
        echo "</tr>";
        
        $counter = 1;
        foreach ($students as $student_id => $student) {
            echo "<tr>";
            echo "<td>" . $counter++ . "</td>";
            echo "<td>" . htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']) . "</td>";
            
            if (!empty($grade_level) && empty($section)) {
                echo "<td>" . htmlspecialchars($student['section']) . "</td>";
            }
            
            if (empty($grade_level)) {
                echo "<td>" . htmlspecialchars($student['grade_level'] . '-' . $student['section']) . "</td>";
            }
            
            $sum = 0;
            $count = 0;
            foreach ($subjects as $subject) {
                $grade = isset($grades_data[$student_id][$subject]) ? $grades_data[$student_id][$subject] : '-';
                
                if (is_numeric($grade)) {
                    $sum += $grade;
                    $count++;
                }
                
                echo "<td>" . (is_numeric($grade) ? number_format($grade, 2) : $grade) . "</td>";
                echo "<td>" . (is_numeric($grade) ? getLetterGrade($grade) : '-') . "</td>";
            }
            
            $avg = $count > 0 ? round($sum / $count, 2) : '-';
            echo "<td><b>" . (is_numeric($avg) ? number_format($avg, 2) : $avg) . "</b></td>";
            echo "<td><b>" . (is_numeric($avg) ? getLetterGrade($avg) : '-') . "</b></td>";
            
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Signature lines
        echo "<br><br>";
        echo "<table border='0' cellpadding='5' style='border: none;'>";
        echo "<tr>";
        echo "<td style='text-align: center; border: none;'>___________________________<br>Prepared by</td>";
        echo "<td style='text-align: center; border: none;'>___________________________<br>Verified by</td>";
        echo "<td style='text-align: center; border: none;'>___________________________<br>School Principal</td>";
        echo "</tr>";
        echo "</table>";
    }
}
else {
    echo "<p>Invalid report parameters.</p>";
}

echo "</body>";
echo "</html>";
?>

 
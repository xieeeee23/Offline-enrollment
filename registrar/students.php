<?php
$title = 'Senior High School Enrollment System';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Debug statement for filter_has_voucher
// echo "<pre>filter_has_voucher: " . var_export(isset($_GET['has_voucher']) ? $_GET['has_voucher'] : 'not set', true) . "</pre>";

// Enable debugging - uncomment to see the SQL query
$debug_mode = false;

// Function to check if back_subjects table exists and create it if it doesn't
function checkAndCreateBackSubjectsTable($conn) {
    $query = "SELECT COUNT(*) as count FROM information_schema.tables 
              WHERE table_schema = DATABASE() 
              AND table_name = 'back_subjects'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] == 0) {
        // Table doesn't exist, create it
        $sql = "
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
        ";
        
        if (mysqli_query($conn, $sql)) {
            // Try to add foreign key constraint
            $add_constraint_sql = "
            ALTER TABLE `back_subjects`
            ADD CONSTRAINT `back_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
            ";
            
            mysqli_query($conn, $add_constraint_sql);
            return "Back subjects table created successfully.";
        } else {
            return "Error creating back subjects table: " . mysqli_error($conn);
        }
    }
    return null;
}

// Check and create back_subjects table if needed
$back_subjects_message = checkAndCreateBackSubjectsTable($conn);
if ($back_subjects_message) {
    $_SESSION['alert'] = showAlert($back_subjects_message, strpos($back_subjects_message, "Error") === false ? 'success' : 'danger');
}

// Debug statement - comment out after debugging
// echo "<pre>Filter strand: " . $filter_strand . "</pre>";

// Custom CSS for status badges
$extra_css = <<<CSS
<style>
    .status-badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        letter-spacing: 0.5px;
        text-transform: capitalize;
    }
    
    .status-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .status-badge.enrolled {
        background-color: #28a745 !important;
        border: none;
    }
    
    .status-badge.pending {
        background-color: #ffc107 !important;
        color: #212529 !important;
        border: none;
    }
    
    .status-badge.withdrawn {
        background-color: #dc3545 !important;
        border: none;
    }
    
    .status-badge.irregular {
        background-color: #fd7e14 !important;
        border: none;
    }
    
    .status-badge.graduated {
        background-color: #17a2b8 !important;
        border: none;
    }
    
    .status-badge.unknown {
        background-color: #6c757d !important;
        border: none;
    }
</style>
CSS;

echo $extra_css;

// Function to check and update enrollment status column
function checkAndUpdateEnrollmentStatusColumn($conn) {
    $query = "SHOW COLUMNS FROM students LIKE 'enrollment_status'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $column_info = mysqli_fetch_assoc($result);
        $type = $column_info['Type'];
        
        // Check if the enum includes 'irregular' and 'graduated'
        if (strpos($type, 'irregular') === false || strpos($type, 'graduated') === false) {
            // Update the column to include all required statuses
            $query = "ALTER TABLE students MODIFY COLUMN enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated') DEFAULT 'pending'";
            if (mysqli_query($conn, $query)) {
                return "Enrollment status column updated to include all required statuses.";
            } else {
                return "Error updating enrollment status column: " . mysqli_error($conn);
            }
        }
    }
    return null;
}

// Check and update enrollment status column
$status_update_message = checkAndUpdateEnrollmentStatusColumn($conn);
if ($status_update_message) {
    $_SESSION['alert'] = showAlert($status_update_message, $status_update_message === "Error updating enrollment status column: " . mysqli_error($conn) ? 'danger' : 'success');
}

// Get search parameters - make sure these are defined before export processing
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_grade = isset($_GET['grade']) ? $_GET['grade'] : '';
$filter_strand = isset($_GET['strand']) ? $_GET['strand'] : '';
$filter_section = isset($_GET['section']) ? $_GET['section'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$filter_school_year = isset($_GET['school_year']) ? $_GET['school_year'] : '';
$filter_semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$filter_student_type = isset($_GET['student_type']) ? $_GET['student_type'] : '';
$filter_has_voucher = isset($_GET['has_voucher']) && $_GET['has_voucher'] !== '' ? $_GET['has_voucher'] : '';

// Handle numeric grade values (convert 11 to 'Grade 11')
if ($filter_grade == '11' || $filter_grade == '12') {
    $filter_grade = 'Grade ' . $filter_grade;
}

// Check if any filter is applied
$is_filtered = !empty($search) || !empty($filter_grade) || !empty($filter_strand) || 
               !empty($filter_section) || !empty($filter_status) || !empty($filter_gender) ||
               !empty($filter_school_year) || !empty($filter_semester) || !empty($filter_student_type) ||
               $filter_has_voucher !== '';

// Process export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Build query with current filters
    $query = "SELECT * FROM students WHERE 1=1";
    $params = array();
    
    if (!empty($search)) {
        $query .= " AND (first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR lrn LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($filter_grade)) {
        $query .= " AND grade_level = ?";
        $params[] = $filter_grade;
    }
    
    if (!empty($filter_strand)) {
        $query .= " AND strand = ?";
        $params[] = $filter_strand;
    }
    
    if (!empty($filter_section)) {
        $query .= " AND section = ?";
        $params[] = $filter_section;
    }
    
    if (!empty($filter_status)) {
        $query .= " AND enrollment_status = ?";
        $params[] = $filter_status;
        
        // Debug output for status filtering
        if ($debug_mode) {
            echo "<div class='alert alert-info'>";
            echo "<strong>Status Filter Debug:</strong> Filtering for status: " . htmlspecialchars($filter_status);
            echo "</div>";
        }
    }
    
    if (!empty($filter_student_type)) {
        $query .= " AND student_type = ?";
        $params[] = $filter_student_type;
    }
    
    if ($filter_has_voucher !== '') {
        if ($filter_has_voucher === '1') {
            // Only students with has_voucher explicitly set to 1
            $query .= " AND has_voucher = 1";
        } else if ($filter_has_voucher === '0') {
            // Students with has_voucher set to 0, NULL, or any other value that's not 1
            $query .= " AND (has_voucher = 0 OR has_voucher IS NULL)";
        }
    }
    
    $query .= " ORDER BY last_name, first_name";
    
    // Prepare and execute the query
    $stmt = mysqli_prepare($conn, $query);
    
    if (!empty($params)) {
        $types = str_repeat("s", count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Handle PDF or Word export requests - redirect to Excel export
    if ($export_type === 'pdf' || $export_type === 'word') {
        $_SESSION['alert'] = showAlert('Only Excel export is available.', 'info');
        $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['export' => 'excel']));
        redirect($redirect_url);
        exit;
    }
    
    // Consolidated Report (Comprehensive Excel Report instead of PDF)
    elseif ($export_type === 'consolidated') {
        // Set headers for Excel file download
        $filename = 'comprehensive_student_report_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Set UTF-8 BOM for Excel to recognize UTF-8 encoding
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Filter information
        $filter_text = '';
        if (!empty($filter_status)) {
            $filter_text .= 'Status: ' . ucfirst($filter_status);
        }
        if (!empty($filter_grade)) {
            $filter_text .= ($filter_text ? ' | ' : '') . 'Grade: ' . $filter_grade;
        }
        if (!empty($filter_strand)) {
            $filter_text .= ($filter_text ? ' | ' : '') . 'Strand: ' . $filter_strand;
        }
        if (!empty($filter_section)) {
            $filter_text .= ($filter_text ? ' | ' : '') . 'Section: ' . $filter_section;
        }
        if (!empty($filter_student_type)) {
            $filter_text .= ($filter_text ? ' | ' : '') . 'Student Type: ' . ucfirst($filter_student_type);
        }
        if (!empty($filter_has_voucher)) {
            $filter_text .= ($filter_text ? ' | ' : '') . 'Voucher: ' . ($filter_has_voucher == '1' ? 'Yes' : 'No');
        }
        if (!empty($search)) {
            $filter_text .= ($filter_text ? ' | ' : '') . 'Search: ' . $search;
        }
        
        // Get student statistics
        $total_students = mysqli_num_rows($result);
        
        // Reset result pointer
        mysqli_data_seek($result, 0);
        
        // Count by status
        $enrolled_count = 0;
        $pending_count = 0;
        $withdrawn_count = 0;
        
        // Count by grade level
        $grade11_count = 0;
        $grade12_count = 0;
        
        // Count by gender
        $male_count = 0;
        $female_count = 0;
        $other_count = 0;
        
        // Strand counts
        $strands = array();
        
        // Section counts
        $sections = array();
        
        while ($student = mysqli_fetch_assoc($result)) {
            // Count by status
            if ($student['enrollment_status'] == 'enrolled') $enrolled_count++;
            elseif ($student['enrollment_status'] == 'pending') $pending_count++;
            elseif ($student['enrollment_status'] == 'withdrawn') $withdrawn_count++;
            
            // Count by grade level
            if ($student['grade_level'] == 'Grade 11') $grade11_count++;
            elseif ($student['grade_level'] == 'Grade 12') $grade12_count++;
            
            // Count by gender
            if ($student['gender'] == 'Male') $male_count++;
            elseif ($student['gender'] == 'Female') $female_count++;
            else $other_count++;
            
            // Count by strand
            if (!empty($student['strand'])) {
                if (isset($strands[$student['strand']])) {
                    $strands[$student['strand']]++;
                } else {
                    $strands[$student['strand']] = 1;
                }
            }
            
            // Count by section
            if (!empty($student['section'])) {
                if (isset($sections[$student['section']])) {
                    $sections[$student['section']]++;
                } else {
                    $sections[$student['section']] = 1;
                }
            }
        }
        
        // Write report title and metadata
        fputcsv($output, ['THE KRISLIZZ INTERNATIONAL ACADEMY INC.']);
        fputcsv($output, ['COMPREHENSIVE STUDENT REPORT']);
        fputcsv($output, ['Date Generated: ' . date('F d, Y')]);
        if (!empty($filter_text)) {
            fputcsv($output, ['Filters: ' . $filter_text]);
        }
        fputcsv($output, []); // Empty line
        
        // Write report summary section
        fputcsv($output, ['REPORT SUMMARY']);
        fputcsv($output, ['Total Students:', $total_students]);
        fputcsv($output, []); // Empty line
        
        // Enrollment Status
        fputcsv($output, ['ENROLLMENT STATUS']);
        fputcsv($output, ['Enrolled Students:', $enrolled_count, round(($enrolled_count / $total_students) * 100, 1) . '%']);
        fputcsv($output, ['Pending Students:', $pending_count, round(($pending_count / $total_students) * 100, 1) . '%']);
        fputcsv($output, ['Withdrawn Students:', $withdrawn_count, round(($withdrawn_count / $total_students) * 100, 1) . '%']);
        fputcsv($output, []); // Empty line
        
        // Grade Level Distribution
        fputcsv($output, ['GRADE LEVEL DISTRIBUTION']);
        fputcsv($output, ['Grade 11 Students:', $grade11_count, round(($grade11_count / $total_students) * 100, 1) . '%']);
        fputcsv($output, ['Grade 12 Students:', $grade12_count, round(($grade12_count / $total_students) * 100, 1) . '%']);
        fputcsv($output, []); // Empty line
        
        // Gender Distribution
        fputcsv($output, ['GENDER DISTRIBUTION']);
        fputcsv($output, ['Male Students:', $male_count, round(($male_count / $total_students) * 100, 1) . '%']);
        fputcsv($output, ['Female Students:', $female_count, round(($female_count / $total_students) * 100, 1) . '%']);
        if ($other_count > 0) {
            fputcsv($output, ['Other Gender Students:', $other_count, round(($other_count / $total_students) * 100, 1) . '%']);
        }
        fputcsv($output, []); // Empty line
        
        // Strand Distribution
        if (count($strands) > 0) {
            fputcsv($output, ['STRAND DISTRIBUTION']);
            arsort($strands); // Sort by count (highest first)
            foreach ($strands as $strand => $count) {
                fputcsv($output, [$strand . ':', $count, round(($count / $total_students) * 100, 1) . '%']);
            }
            fputcsv($output, []); // Empty line
        }
        
        // Section Distribution
        if (count($sections) > 0) {
            fputcsv($output, ['SECTION DISTRIBUTION']);
            arsort($sections); // Sort by count (highest first)
            foreach ($sections as $section => $count) {
                fputcsv($output, [$section . ':', $count, round(($count / $total_students) * 100, 1) . '%']);
            }
            fputcsv($output, []); // Empty line
        }
        
        // Reset result pointer
        mysqli_data_seek($result, 0);
        
        // Detailed Student List
        fputcsv($output, ['DETAILED STUDENT LISTING']);
        fputcsv($output, ['LRN', 'Last Name', 'First Name', 'Middle Name', 'Grade Level', 'Strand', 'Section', 'Gender', 'Status']);
        
        while ($student = mysqli_fetch_assoc($result)) {
            // Format LRN to 12 digits
            $formatted_lrn = $student['lrn'];
            if (strlen($formatted_lrn) < 12) {
                $formatted_lrn = str_pad($formatted_lrn, 12, '0', STR_PAD_LEFT);
            }
            
            // Determine section display based on status
            $section_display = ($student['enrollment_status'] === 'pending') ? '' : $student['section'];
            
            fputcsv($output, [
                $formatted_lrn,
                $student['last_name'],
                $student['first_name'],
                $student['middle_name'] ?? '',
                'Grade ' . $student['grade_level'],
                $student['strand'] ?? '',
                $section_display,
                $student['gender'],
                ucfirst($student['enrollment_status'])
            ]);
        }
        
        fputcsv($output, []); // Empty line
        
        // Reset result pointer
        mysqli_data_seek($result, 0);
        
        // Student Contact Information
        fputcsv($output, ['STUDENT CONTACT INFORMATION']);
        fputcsv($output, ['LRN', 'Last Name', 'First Name', 'Middle Name', 'Contact Number', 'Email', 'Guardian Name']);
        
        while ($student = mysqli_fetch_assoc($result)) {
            // Format LRN to 12 digits
            $formatted_lrn = $student['lrn'];
            if (strlen($formatted_lrn) < 12) {
                $formatted_lrn = str_pad($formatted_lrn, 12, '0', STR_PAD_LEFT);
            }
            
            fputcsv($output, [
                $formatted_lrn,
                $student['last_name'],
                $student['first_name'],
                $student['middle_name'] ?? '',
                $student['contact_number'] ?? 'N/A',
                $student['email'] ?? 'N/A',
                $student['guardian_name'] ?? 'N/A'
            ]);
        }
        
        // Close output stream
        fclose($output);
        exit;
    }
}

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Define LRN format validation function
function validateLRN($lrn) {
    // Check if LRN is exactly 12 digits
    if (!preg_match('/^\d{12}$/', $lrn)) {
        return false;
    }
    return true;
}

// Check if we need to alter the table to add new columns
$columns_to_check = [
    'middle_name' => 'VARCHAR(50)',
    'religion' => 'VARCHAR(50)',
    'email' => 'VARCHAR(100)',
    'father_name' => 'VARCHAR(100)',
    'father_occupation' => 'VARCHAR(100)',
    'mother_name' => 'VARCHAR(100)',
    'mother_occupation' => 'VARCHAR(100)',
    'strand' => 'VARCHAR(50)',
    'guardian_name' => 'VARCHAR(100)',
    'guardian_contact' => 'VARCHAR(20)',
    'has_voucher' => 'TINYINT(1) DEFAULT 0',
    'voucher_number' => 'VARCHAR(50) DEFAULT NULL',
    'student_type' => 'ENUM("new", "old") DEFAULT "new"'
];

foreach ($columns_to_check as $column => $type) {
    $query = "SHOW COLUMNS FROM students LIKE '$column'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
        // Add column to students table
        $query = "ALTER TABLE students ADD COLUMN $column $type";
        if (!mysqli_query($conn, $query)) {
            $_SESSION['alert'] = showAlert("Error adding $column column: " . mysqli_error($conn), 'danger');
        }
    }
}

// Check if students table exists, create if not
$query = "SHOW TABLES LIKE 'students'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Create the students table
    $query = "CREATE TABLE students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn VARCHAR(20) NOT NULL UNIQUE,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50),
        last_name VARCHAR(50) NOT NULL,
        dob DATE NOT NULL,
        gender ENUM('Male', 'Female', 'Other') NOT NULL,
        religion VARCHAR(50),
        address TEXT,
        contact_number VARCHAR(20),
        email VARCHAR(100),
        father_name VARCHAR(100),
        father_occupation VARCHAR(100),
        mother_name VARCHAR(100),
        mother_occupation VARCHAR(100),
        guardian_name VARCHAR(100),
        guardian_contact VARCHAR(20),
        grade_level VARCHAR(20) NOT NULL,
        strand VARCHAR(50),
        section VARCHAR(20) NOT NULL,
        enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated') DEFAULT 'pending',
        photo VARCHAR(255) DEFAULT NULL,
        enrolled_by INT,
        date_enrolled DATE,
        education_level_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (education_level_id) REFERENCES education_levels(id) ON DELETE SET NULL
    )";
    
    if (!mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Error creating students table: ' . mysqli_error($conn), 'danger');
    }
    
    // Create directory for student photos if it doesn't exist
    $upload_dir = $relative_path . 'uploads/students';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
} else {
    // Check if enrollment_status column has the correct enum values
    $query = "SHOW COLUMNS FROM students LIKE 'enrollment_status'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $column_info = mysqli_fetch_assoc($result);
        $type = $column_info['Type'];
        
        // Check if the enum includes 'irregular' and 'graduated'
        if (strpos($type, 'irregular') === false || strpos($type, 'graduated') === false) {
            // Update the column to include all required statuses
            $query = "ALTER TABLE students MODIFY COLUMN enrollment_status ENUM('enrolled', 'pending', 'withdrawn', 'irregular', 'graduated') DEFAULT 'pending'";
            if (mysqli_query($conn, $query)) {
                $_SESSION['alert'] = showAlert('Enrollment status column updated to include all required statuses.', 'success');
            } else {
                $_SESSION['alert'] = showAlert('Error updating enrollment status column: ' . mysqli_error($conn), 'danger');
            }
        }
    }
    
    // Check if photo column exists in students table
    $query = "SHOW COLUMNS FROM students LIKE 'photo'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
        // Add photo column to students table
        $query = "ALTER TABLE students ADD COLUMN photo VARCHAR(255) DEFAULT NULL";
        if (!mysqli_query($conn, $query)) {
            $_SESSION['alert'] = showAlert('Error adding photo column to students table: ' . mysqli_error($conn), 'danger');
        } else {
            $_SESSION['alert'] = showAlert('Photo column added to students table.', 'success');
        }
    }
    
    // Check if address column exists in students table
    $query = "SHOW COLUMNS FROM students LIKE 'address'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
        // Add address column to students table
        $query = "ALTER TABLE students ADD COLUMN address TEXT AFTER gender";
        if (!mysqli_query($conn, $query)) {
            $_SESSION['alert'] = showAlert('Error adding address column to students table: ' . mysqli_error($conn), 'danger');
        } else {
            $_SESSION['alert'] = showAlert('Address column added to students table.', 'success');
        }
    }
    
    // Check if education_level_id column exists in students table
    $query = "SHOW COLUMNS FROM students LIKE 'education_level_id'";
    $result = mysqli_query($conn, $query);
    $education_level_column_exists = mysqli_num_rows($result) > 0;
    
    if ($education_level_column_exists) {
        // Remove education_level_id column from students table if it exists
        $query = "ALTER TABLE students DROP FOREIGN KEY students_ibfk_2";
        mysqli_query($conn, $query);
        
        $query = "ALTER TABLE students DROP COLUMN education_level_id";
        mysqli_query($conn, $query);
    }
}

// Create directory for student photos if it doesn't exist
$upload_dir = $relative_path . 'uploads/students';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if gender column exists in students table
$query = "SHOW COLUMNS FROM students LIKE 'gender'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Add gender column to students table
    $query = "ALTER TABLE students ADD COLUMN gender ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male' AFTER last_name";
    if (!mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Error adding gender column to students table: ' . mysqli_error($conn), 'danger');
    } else {
        $_SESSION['alert'] = showAlert('Gender column added to students table.', 'success');
    }
}

// Process delete student
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int) $_GET['id'];
    
    // Check if student exists
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $student = mysqli_fetch_assoc($result);
        
        // Start transaction to ensure data integrity
        mysqli_begin_transaction($conn);
        
        try {
            // Delete related records first
            
            // 1. Delete student requirements
            $query = "DELETE FROM student_requirements WHERE student_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $delete_id);
            mysqli_stmt_execute($stmt);
            
            // 2. Delete from senior_highschool_details if exists
            // First check if the table exists
            $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'senior_highschool_details'");
            if (mysqli_num_rows($table_check) > 0) {
                $query = "DELETE FROM senior_highschool_details WHERE student_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $delete_id);
                mysqli_stmt_execute($stmt);
            }
            
            // Delete student photo if exists
            if (!empty($student['photo'])) {
                $full_path = $relative_path . $student['photo'];
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
            }
            
            // Delete requirement files if they exist
            $req_dir = $relative_path . 'uploads/requirements/' . $delete_id;
            if (is_dir($req_dir)) {
                $files = glob($req_dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                // Try to remove the directory
                @rmdir($req_dir);
            }
            
            // Finally delete the student
            $query = "DELETE FROM students WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $delete_id);
            
            if (mysqli_stmt_execute($stmt)) {
                // Commit the transaction
                mysqli_commit($conn);
                
                // Log action
                $log_desc = "Deleted student: {$student['first_name']} {$student['last_name']} (LRN: {$student['lrn']})";
                logAction($_SESSION['user_id'], 'DELETE', $log_desc);
                
                $_SESSION['alert'] = showAlert('Student deleted successfully.', 'success');
            } else {
                // Rollback the transaction if student deletion fails
                mysqli_rollback($conn);
                $_SESSION['alert'] = showAlert('Error deleting student: ' . mysqli_error($conn), 'danger');
            }
        } catch (Exception $e) {
            // Rollback the transaction if any error occurs
            mysqli_rollback($conn);
            $_SESSION['alert'] = showAlert('Error deleting student: ' . $e->getMessage(), 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('Student not found.', 'danger');
    }
    
    // Redirect to students page
    redirect('modules/registrar/students.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_student'])) {
    // Validation and data processing would go here
    // ...

    // Redirect after processing
    redirect('modules/registrar/students.php');
}

// Main page content starts here
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Students</h1>
        <div>
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-download fa-sm text-white-50 me-1"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </a></li>
                    <li><a class="dropdown-item" href="<?php echo $_SERVER['PHP_SELF']; ?>?<?php echo http_build_query(array_merge($_GET, ['export' => 'consolidated'])); ?>">
                        <i class="fas fa-file-alt me-1"></i> Comprehensive Report
                    </a></li>
                </ul>
            </div>
            <button type="button" class="btn btn-sm btn-primary me-2" id="printButton">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <a href="<?php echo $relative_path; ?>modules/registrar/add_student.php" class="btn btn-sm btn-success">
                <i class="fas fa-plus fa-sm text-white-50 me-1"></i> Add New Student
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>

    <!-- Search and Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primar text-white">Search and Filter</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="row">
                <div class="col-md-3 mb-3">
                    <label for="search">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Name, LRN..." value="<?php echo htmlspecialchars($search); ?>">
                    <small class="form-text text-muted">LRN must be 12 digits (e.g., 123456789012)</small>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="grade">Grade Level</label>
                    <select class="form-control" id="grade" name="grade">
                        <option value="">All Grades</option>
                        <option value="Grade 11" <?php echo $filter_grade === 'Grade 11' ? 'selected' : ''; ?>>Grade 11</option>
                        <option value="Grade 12" <?php echo $filter_grade === 'Grade 12' ? 'selected' : ''; ?>>Grade 12</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="strand">Strand</label>
                    <select class="form-control" id="strand" name="strand">
                        <option value="">All Strands</option>
                        <?php
                        // Get all strands from the shs_strands table
                        $query = "SELECT strand_code, strand_name FROM shs_strands WHERE status = 'Active' ORDER BY strand_name";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $selected = $filter_strand === $row['strand_code'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['strand_code']) . "' $selected>" . 
                                 htmlspecialchars($row['strand_code'] . ' - ' . $row['strand_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="section">Section</label>
                    <select class="form-control" id="section" name="section">
                        <option value="">All Sections</option>
                        <?php
                        // Get all sections
                        $query = "SELECT DISTINCT section FROM students WHERE section IS NOT NULL AND section != '' ORDER BY section";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $selected = $filter_section === $row['section'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['section']) . "' $selected>" . htmlspecialchars($row['section']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="enrolled" <?php echo $filter_status === 'enrolled' ? 'selected' : ''; ?>>Enrolled</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="withdrawn" <?php echo $filter_status === 'withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                        <option value="irregular" <?php echo $filter_status === 'irregular' ? 'selected' : ''; ?>>Irregular</option>
                        <option value="graduated" <?php echo $filter_status === 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="gender">Gender</label>
                    <select class="form-control" id="gender" name="gender">
                        <option value="">All Genders</option>
                        <option value="Male" <?php echo $filter_gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $filter_gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $filter_gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="school_year">School Year</label>
                    <select class="form-control" id="school_year" name="school_year">
                        <option value="">All School Years</option>
                        <?php
                        // Get all school years
                        $query = "SELECT DISTINCT school_year FROM school_years ORDER BY school_year DESC";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $selected = $filter_school_year === $row['school_year'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['school_year']) . "' $selected>" . htmlspecialchars($row['school_year']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="semester">Semester</label>
                    <select class="form-control" id="semester" name="semester">
                        <option value="">All Semesters</option>
                        <option value="First" <?php echo $filter_semester === 'First' ? 'selected' : ''; ?>>First Semester</option>
                        <option value="Second" <?php echo $filter_semester === 'Second' ? 'selected' : ''; ?>>Second Semester</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="student_type">Student Type</label>
                    <select class="form-control" id="student_type" name="student_type">
                        <option value="">All Students</option>
                        <option value="new" <?php echo $filter_student_type === 'new' ? 'selected' : ''; ?>>New Students</option>
                        <option value="old" <?php echo $filter_student_type === 'old' ? 'selected' : ''; ?>>Old Students</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="has_voucher">Voucher</label>
                    <select class="form-control" id="has_voucher" name="has_voucher">
                        <option value="">All Students</option>
                        <option value="1" <?php echo $filter_has_voucher === '1' ? 'selected' : ''; ?>>With Voucher</option>
                        <option value="0" <?php echo $filter_has_voucher === '0' ? 'selected' : ''; ?>>Without Voucher</option>
                    </select>
                </div>
                <div class="col-md-1 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2">Filter</button>
                </div>
            </form>
            <div class="row">
                <div class="col-12 text-right">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Reset Filters</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary text-white">Students List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if (!$is_filtered): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Please apply filters to view student data.
                </div>
                <?php else: ?>
                <?php
                // Build the query for filtered students
                $query = "SELECT s.*, 
                         ss.strand_code, ss.strand_name,
                         CONCAT(ss.strand_code, ' - ', ss.strand_name) as strand_full_name,
                         (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'back_subjects') as back_subjects_table_exists,
                         IFNULL((SELECT COUNT(*) FROM back_subjects bs WHERE bs.student_id = s.id AND bs.status = 'pending'), 0) as back_subjects_count,
                         shd.semester, shd.school_year
                         FROM students s
                         LEFT JOIN shs_strands ss ON s.strand = ss.strand_code
                         LEFT JOIN senior_highschool_details shd ON s.id = shd.student_id
                         WHERE 1=1";
                
                $params = array();
                
                if (!empty($search)) {
                    $query .= " AND (s.first_name LIKE ? OR s.middle_name LIKE ? OR s.last_name LIKE ? OR s.lrn LIKE ?)";
                    $search_param = "%$search%";
                    $params[] = $search_param;
                    $params[] = $search_param;
                    $params[] = $search_param;
                    $params[] = $search_param;
                }
                
                if (!empty($filter_grade)) {
                    $query .= " AND s.grade_level = ?";
                    $params[] = $filter_grade;
                }
                
                if (!empty($filter_strand)) {
                    $query .= " AND s.strand = ?";
                    $params[] = $filter_strand;
                }
                
                if (!empty($filter_section)) {
                    $query .= " AND s.section = ?";
                    $params[] = $filter_section;
                }
                
                if (!empty($filter_status)) {
                    $query .= " AND s.enrollment_status = ?";
                    $params[] = $filter_status;
                    
                    // Debug output for status filtering
                    if ($debug_mode) {
                        echo "<div class='alert alert-info'>";
                        echo "<strong>Status Filter Debug:</strong> Filtering for status: " . htmlspecialchars($filter_status);
                        echo "</div>";
                    }
                }
                
                if (!empty($filter_gender)) {
                    $query .= " AND s.gender = ?";
                    $params[] = $filter_gender;
                }
                
                if (!empty($filter_school_year)) {
                    $query .= " AND shd.school_year = ?";
                    $params[] = $filter_school_year;
                }
                
                if (!empty($filter_semester)) {
                    $query .= " AND shd.semester = ?";
                    $params[] = $filter_semester;
                }
                
                    if (!empty($filter_student_type)) {
                        $query .= " AND s.student_type = ?";
                        $params[] = $filter_student_type;
                    }
                
                    if ($filter_has_voucher !== '') {
                        if ($filter_has_voucher === '1') {
                            // Only students with has_voucher explicitly set to 1
                            $query .= " AND s.has_voucher = 1";
                        } else if ($filter_has_voucher === '0') {
                            // Students with has_voucher set to 0, NULL, or any other value that's not 1
                            $query .= " AND (s.has_voucher = 0 OR s.has_voucher IS NULL)";
                        }
                    }
                
                if (isset($_GET['irregular']) && $_GET['irregular'] !== '') {
                    $irregular_status = (int) $_GET['irregular'];
                    // Check if irregular_students table exists and join with it instead of using irregular_status column
                    $query = "SELECT COUNT(*) as count FROM information_schema.tables 
                             WHERE table_schema = DATABASE() 
                             AND table_name = 'irregular_students'";
                    $check_result = mysqli_query($conn, $query);
                    $row = mysqli_fetch_assoc($check_result);
                    
                    if ($row['count'] > 0) {
                        if ($irregular_status == 1) {
                            $query .= " AND EXISTS (SELECT 1 FROM irregular_students ir WHERE ir.student_id = s.id)";
                        } else {
                            $query .= " AND NOT EXISTS (SELECT 1 FROM irregular_students ir WHERE ir.student_id = s.id)";
                        }
                    }
                }
                
                $query .= " ORDER BY s.last_name, s.first_name";
                
                // Debug output for the query
                if ($debug_mode) {
                    echo "<div class='alert alert-info'>";
                    echo "<strong>Debug:</strong> SQL Query: " . htmlspecialchars($query);
                    echo "<br><strong>Parameters:</strong> ";
                    echo "<pre>" . print_r($params, true) . "</pre>";
                    echo "<br><strong>Filter has_voucher:</strong> " . htmlspecialchars($filter_has_voucher);
                    
                    // Add additional debug information for voucher values
                    echo "<br><strong>Voucher filter condition:</strong> ";
                    if ($filter_has_voucher === '1') {
                        echo "has_voucher = 1";
                    } else if ($filter_has_voucher === '0') {
                        echo "(has_voucher = 0 OR has_voucher IS NULL)";
                    } else {
                        echo "No voucher filter applied";
                    }
                    echo "</div>";
                }
                
                // Prepare and execute the query
                $stmt = mysqli_prepare($conn, $query);
                
                if (!empty($params)) {
                    $types = str_repeat("s", count($params));
                    mysqli_stmt_bind_param($stmt, $types, ...$params);
                }
                
                // Store the result for later use in the delete modals
                $result = null;
                if ($stmt) {
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                }
                ?>
                <table class="table table-bordered table-striped table-hover" id="studentsTable">
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Name</th>
                            <th>Grade Level</th>
                            <th>Strand</th>
                            <th>Section</th>
                            <th>Gender</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Voucher</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php 
                            // Debug: Print voucher values
                            if ($debug_mode) {
                                echo "<div class='alert alert-info'>";
                                echo "<strong>Debug:</strong> Voucher values in result set:<br>";
                                mysqli_data_seek($result, 0);
                                $voucher_values = [];
                                $total_students = 0;
                                $with_voucher = 0;
                                $without_voucher = 0;
                                $null_voucher = 0;
                                
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $total_students++;
                                    if ($row['has_voucher'] == 1) {
                                        $with_voucher++;
                                    } else if ($row['has_voucher'] == 0) {
                                        $without_voucher++;
                                    } else {
                                        $null_voucher++;
                                    }
                                    
                                    $voucher_values[] = [
                                        'id' => $row['id'],
                                        'name' => $row['first_name'] . ' ' . $row['last_name'],
                                        'has_voucher' => $row['has_voucher'],
                                        'has_voucher_type' => gettype($row['has_voucher'])
                                    ];
                                }
                                
                                echo "<p>Total students: $total_students</p>";
                                echo "<p>With voucher (has_voucher=1): $with_voucher</p>";
                                echo "<p>Without voucher (has_voucher=0): $without_voucher</p>";
                                echo "<p>NULL voucher value: $null_voucher</p>";
                                echo "<pre>" . print_r($voucher_values, true) . "</pre>";
                                echo "</div>";
                                
                                // Reset result pointer
                                mysqli_data_seek($result, 0);
                            }
                            
                            while ($student = mysqli_fetch_assoc($result)): 
                                $status_class = '';
                                // Handle empty enrollment status as 'enrolled'
                                $enrollment_status = !empty($student['enrollment_status']) ? strtolower($student['enrollment_status']) : 'enrolled';
                                
                                // Normalize status for CSS class
                                switch($enrollment_status) {
                                    case 'enrolled':
                                        $status_class = 'bg-success text-white';
                                        $display_status = 'Enrolled';
                                        break;
                                    case 'pending':
                                    case 'pending requirements':
                                    case 'incomplete requirements':
                                        $status_class = 'bg-warning text-dark';
                                        $enrollment_status = 'pending';
                                        $display_status = 'Pending';
                                        break;
                                    case 'withdrawn':
                                        $status_class = 'bg-danger text-white';
                                        $display_status = 'Withdrawn';
                                        break;
                                    case 'irregular':
                                        $status_class = 'bg-info text-white';
                                        $display_status = 'Irregular';
                                        break;
                                    case 'graduated':
                                        $status_class = 'bg-primary text-white';
                                        $display_status = 'Graduated';
                                        break;
                                    default:
                                        // This should rarely happen now
                                        $status_class = 'bg-secondary text-white';
                                        $enrollment_status = 'enrolled';
                                        $display_status = 'Enrolled';
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']);
                                    if (!empty($student['middle_name'])) {
                                        echo ' ' . htmlspecialchars(substr($student['middle_name'], 0, 1) . '.');
                                    }
                                    if (isset($student['irregular_status']) && $student['irregular_status']) {
                                        echo ' <span class="badge bg-warning text-dark">Irregular</span>';
                                    }
                                    if (isset($student['back_subjects_table_exists']) && $student['back_subjects_table_exists'] > 0) {
                                        // Only show back subjects if the table exists
                                        if (isset($student['back_subjects_count']) && $student['back_subjects_count'] > 0) {
                                            echo ' <span class="badge bg-danger" title="Has ' . $student['back_subjects_count'] . ' pending back subject(s)">Back Subject</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['grade_level']); ?></td>
                                <td><?php echo htmlspecialchars($student['strand_full_name'] ?? $student['strand'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    // Only display section if status is not pending
                                    if ($enrollment_status !== 'pending') {
                                        echo htmlspecialchars($student['section']); 
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($display_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $student_type = isset($student['student_type']) ? $student['student_type'] : 'new';
                                    $type_badge_class = $student_type === 'new' ? 'bg-info' : 'bg-primary';
                                    ?>
                                    <span class="badge <?php echo $type_badge_class; ?>">
                                        <?php echo ucfirst(htmlspecialchars($student_type)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    // Strict check for voucher status - convert to integer for consistent comparison
                                    $has_voucher = false;
                                    if (isset($student['has_voucher'])) {
                                        // Convert to integer and check if it's 1
                                        $has_voucher = ((int)$student['has_voucher'] === 1);
                                    }
                                    
                                    if ($has_voucher): 
                                    ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i> Yes
                                        </span>
                                        <?php if (!empty($student['voucher_number'])): ?>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars($student['voucher_number']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-student" data-id="<?php echo $student['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No students found matching the filter criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modals Container - Moved outside the table to prevent duplicate issues -->
<div id="deleteModalsContainer">
    <?php
    // Create modals for all students in the result set
    if (isset($result) && $result) {
        // Reset the result set to create modals separately
        mysqli_data_seek($result, 0);
        while ($student = mysqli_fetch_assoc($result)) {
    ?>
    <div class="modal fade" id="deleteModal<?php echo $student['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $student['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel<?php echo $student['id']; ?>">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete student: <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>?</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. All related data will also be deleted, including:
                        <ul class="mb-0 mt-2">
                            <li>Student requirements and documents</li>
                            <li>Senior high school details</li>
                            <li>Uploaded photos and files</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&id=<?php echo $student['id']; ?>" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete Student
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php 
        }
    }
    ?>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 

<script src="<?php echo $relative_path; ?>assets/js/students-print.js"></script>
<script>
// Function to validate and format LRN numbers
function formatLRN(lrnValue) {
    // Remove any non-numeric characters
    lrnValue = lrnValue.replace(/\D/g, '');
    
    // Limit to 12 digits
    if (lrnValue.length > 12) {
        lrnValue = lrnValue.substring(0, 12);
    }
    
    return lrnValue;
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle search input
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            // Only format if the input appears to be an LRN (all digits)
            if (/^\d+$/.test(this.value)) {
                this.value = formatLRN(this.value);
            }
        });
    }
    
    // Initialize delete student buttons
    document.querySelectorAll('.delete-student').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('data-id');
            const modalId = `deleteModal${studentId}`;
            const modal = document.getElementById(modalId);
            
            // Remove any existing backdrops first
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });
            
            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
            
            if (modal) {
                // Close any open modals first
                document.querySelectorAll('.modal.show').forEach(openModal => {
                    const bsOpenModal = bootstrap.Modal.getInstance(openModal);
                    if (bsOpenModal) bsOpenModal.hide();
                });
                
                // Show the delete modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            } else {
                console.error(`Modal with ID ${modalId} not found. Redirecting to delete URL.`);
                // Fallback: redirect to delete URL directly
                window.location.href = `${window.location.pathname}?action=delete&id=${studentId}`;
            }
        });
    });
});

// Function to fix modal backdrop issue
function fixModalBackdropIssue() {
    // Remove any orphaned modal backdrops
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        if (!document.querySelector('.modal.show')) {
            backdrop.remove();
        }
    });
    
    // Add event listener to clean up backdrop when modal is hidden
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
        });
    });
}

// Call the fix function when document is ready
document.addEventListener('DOMContentLoaded', fixModalBackdropIssue);

// Initialize print functionality
document.addEventListener('DOMContentLoaded', function() {
    // Create print modal when DOM is loaded
    createPrintModal();
    
    // Add event listener to print button
    document.getElementById('printButton').addEventListener('click', function() {
        openPrintModal();
    });
    
    // Initialize DataTable for better table functionality
    if ($.fn.DataTable && document.getElementById('studentsTable')) {
        $('#studentsTable').DataTable({
            "paging": true,
            "ordering": true,
            "info": true,
            "responsive": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "language": {
                "search": "Quick Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)"
            }
        });
    }
    
    // Handle search input
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            // Only format if the input appears to be an LRN (all digits)
            if (/^\d+$/.test(this.value)) {
                this.value = formatLRN(this.value);
            }
        });
    }
    
    // Initialize delete student buttons
    document.querySelectorAll('.delete-student').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('data-id');
            const modalId = `deleteModal${studentId}`;
            const modal = document.getElementById(modalId);
            
            // Remove any existing backdrops first
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });
            
            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
            
            if (modal) {
                // Close any open modals first
                document.querySelectorAll('.modal.show').forEach(openModal => {
                    const bsOpenModal = bootstrap.Modal.getInstance(openModal);
                    if (bsOpenModal) bsOpenModal.hide();
                });
                
                // Show the delete modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            } else {
                console.error(`Modal with ID ${modalId} not found. Redirecting to delete URL.`);
                // Fallback: redirect to delete URL directly
                window.location.href = `${window.location.pathname}?action=delete&id=${studentId}`;
            }
        });
    });
});

// Function to fix modal backdrop issue
function fixModalBackdropIssue() {
    // Remove any orphaned modal backdrops
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        if (!document.querySelector('.modal.show')) {
            backdrop.remove();
        }
    });
    
    // Add event listener to clean up backdrop when modal is hidden
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.remove();
            });
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
        });
    });
}

// Call the fix function when document is ready
document.addEventListener('DOMContentLoaded', fixModalBackdropIssue);

// Create print modal function
function createPrintModal() {
    // Check if modal already exists
    if (document.getElementById('printModal')) return;
    
    // Create modal HTML
    const modalHTML = `
    <div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printModalLabel">Print Options</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="m-0">Gender Filter</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="printMale" checked>
                                        <label class="form-check-label" for="printMale">
                                            Male Students
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="printFemale" checked>
                                        <label class="form-check-label" for="printFemale">
                                            Female Students
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="printOther" checked>
                                        <label class="form-check-label" for="printOther">
                                            Other Gender Students
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="m-0">Additional Filters</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group mb-2">
                                        <label for="printGradeLevel">Grade Level</label>
                                        <select class="form-control" id="printGradeLevel">
                                            <option value="">All Grade Levels</option>
                                            <option value="Grade 11">Grade 11</option>
                                            <option value="Grade 12">Grade 12</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="printStrand">Strand</label>
                                        <select class="form-control" id="printStrand">
                                            <option value="">All Strands</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="printSection">Section</label>
                                        <select class="form-control" id="printSection">
                                            <option value="">All Sections</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="m-0">Academic Term</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group mb-2">
                                        <label for="printSchoolYear">School Year</label>
                                        <select class="form-control" id="printSchoolYear">
                                            <option value="">All School Years</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="printSemester">Semester</label>
                                        <select class="form-control" id="printSemester">
                                            <option value="">All Semesters</option>
                                            <option value="First">First Semester</option>
                                            <option value="Second">Second Semester</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="m-0">Print Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="printShowSummary" checked>
                                        <label class="form-check-label" for="printShowSummary">
                                            Include Summary Statistics
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="printLandscape" checked>
                                        <label class="form-check-label" for="printLandscape">
                                            Landscape Orientation
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label for="printTitle">Custom Report Title</label>
                                        <input type="text" class="form-control" id="printTitle" placeholder="Student Enrollment List">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="generatePrintBtn">
                        <i class="fas fa-print me-1"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Add modal to the document
    const modalDiv = document.createElement('div');
    modalDiv.innerHTML = modalHTML;
    document.body.appendChild(modalDiv);
    
    // Add event listener for the print button in the modal
    document.getElementById('generatePrintBtn').addEventListener('click', function() {
        generatePrintReport();
    });
    
    // Populate the modal dropdowns
    populatePrintModalDropdowns();
}

// Open print modal function
function openPrintModal() {
    const printModal = new bootstrap.Modal(document.getElementById('printModal'));
    printModal.show();
}

// Populate print modal dropdowns function
function populatePrintModalDropdowns() {
    // Get main page dropdowns
    const strandDropdown = document.getElementById('strand');
    const sectionDropdown = document.getElementById('section');
    const schoolYearDropdown = document.getElementById('school_year');
    
    // Get print modal dropdowns
    const printStrandDropdown = document.getElementById('printStrand');
    const printSectionDropdown = document.getElementById('printSection');
    const printSchoolYearDropdown = document.getElementById('printSchoolYear');
    
    // Copy strand options
    if (strandDropdown && printStrandDropdown) {
        // Clear existing options except first
        printStrandDropdown.innerHTML = '<option value="">All Strands</option>';
        
        Array.from(strandDropdown.options).forEach(option => {
            if (option.value !== '') {
                const newOption = document.createElement('option');
                newOption.value = option.value;
                newOption.text = option.text;
                printStrandDropdown.appendChild(newOption);
            }
        });
    }
    
    // Copy section options
    if (sectionDropdown && printSectionDropdown) {
        // Clear existing options except first
        printSectionDropdown.innerHTML = '<option value="">All Sections</option>';
        
        Array.from(sectionDropdown.options).forEach(option => {
            if (option.value !== '') {
                const newOption = document.createElement('option');
                newOption.value = option.value;
                newOption.text = option.text;
                printSectionDropdown.appendChild(newOption);
            }
        });
    }
    
    // Copy school year options
    if (schoolYearDropdown && printSchoolYearDropdown) {
        // Clear existing options except first
        printSchoolYearDropdown.innerHTML = '<option value="">All School Years</option>';
        
        Array.from(schoolYearDropdown.options).forEach(option => {
            if (option.value !== '') {
                const newOption = document.createElement('option');
                newOption.value = option.value;
                newOption.text = option.text;
                printSchoolYearDropdown.appendChild(newOption);
            }
        });
    }
    
    // Set default values from current filters
    document.getElementById('printGradeLevel').value = document.getElementById('grade')?.value || '';
    document.getElementById('printStrand').value = document.getElementById('strand')?.value || '';
    document.getElementById('printSection').value = document.getElementById('section')?.value || '';
    document.getElementById('printSchoolYear').value = document.getElementById('school_year')?.value || '';
    document.getElementById('printSemester').value = document.getElementById('semester')?.value || '';
}

// Generate print report function
function generatePrintReport() {
    // Get filter values from modal
    const printOptions = {
        includeMale: document.getElementById('printMale').checked,
        includeFemale: document.getElementById('printFemale').checked,
        includeOther: document.getElementById('printOther').checked,
        gradeLevel: document.getElementById('printGradeLevel').value,
        strand: document.getElementById('printStrand').value,
        section: document.getElementById('printSection').value,
        schoolYear: document.getElementById('printSchoolYear').value,
        semester: document.getElementById('printSemester').value,
        showSummary: document.getElementById('printShowSummary').checked,
        landscape: document.getElementById('printLandscape').checked,
        customTitle: document.getElementById('printTitle').value
    };
    
    // Close modal
    const modalElement = document.getElementById('printModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
        modal.hide();
    }
    
    // Get display values for filters
    const gradeLevelText = printOptions.gradeLevel || 'All Grades';
    const strandText = printOptions.strand ? 
        document.querySelector(`#strand option[value="${printOptions.strand}"]`)?.textContent || printOptions.strand : 
        'All Strands';
    const sectionText = printOptions.section || 'All Sections';
    const statusText = document.getElementById('status')?.value ? 
        document.querySelector('#status option:checked')?.textContent : 
        'All Statuses';
    const schoolYearText = printOptions.schoolYear || 'All School Years';
    const semesterText = printOptions.semester ? 
        (printOptions.semester === 'First' ? 'First Semester' : 'Second Semester') : 
        'All Semesters';
    
    // Get students data from table
    const table = document.getElementById('studentsTable');
    if (!table) {
        alert('No student table found. Please apply filters first to view students.');
        return;
    }
    
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    if (rows.length === 0) {
        alert('No students to print. Please apply filters to view student data first.');
        return;
    }
    
    // Separate students by gender
    const maleStudents = [];
    const femaleStudents = [];
    const otherStudents = [];
    
    // Filter rows based on selected criteria
    rows.forEach(row => {
        const columns = row.querySelectorAll('td');
        if (columns.length < 7) return; // Skip rows with insufficient columns
        
        const gender = columns[5].textContent.trim();
        const grade = columns[2].textContent.trim();
        const strand = columns[3].textContent.trim();
        const section = columns[4].textContent.trim();
        
        // Check if row meets all filter criteria
        if (printOptions.gradeLevel && grade !== printOptions.gradeLevel) return;
        if (printOptions.strand && !strand.includes(printOptions.strand)) return;
        if (printOptions.section && section !== printOptions.section) return;
        
        const student = {
            lrn: columns[0].textContent.trim().replace(/[^\d]/g, ''), // Remove any non-digit characters
            name: columns[1].textContent.trim(),
            grade: grade,
            strand: strand,
            section: section,
            status: columns[6].textContent.trim()
        };
        
        if (gender === 'Male' && printOptions.includeMale) {
            maleStudents.push(student);
        } else if (gender === 'Female' && printOptions.includeFemale) {
            femaleStudents.push(student);
        } else if (gender !== 'Male' && gender !== 'Female' && printOptions.includeOther) {
            otherStudents.push(student);
        }
    });
    
    // Check if we have any students to print
    const totalStudents = maleStudents.length + femaleStudents.length + otherStudents.length;
    if (totalStudents === 0) {
        alert('No students match your selected criteria. Please adjust your filters and try again.');
        return;
    }
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    // Create HTML content for the print window
    let printContent = `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Student List Report - THE KRISLIZZ INTERNATIONAL ACADEMY INC.</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                padding: 20px;
                margin: 0;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 15px;
                position: relative;
                border-bottom: 3px double #4e73df;
            }
            .header::after {
                content: '';
                position: absolute;
                bottom: -2px;
                left: 0;
                width: 100%;
                height: 5px;
                background: linear-gradient(to right, #003366, #4e73df, #003366);
                border-radius: 2px;
            }
            .school-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }
            .logo-container {
                width: 100px;
                height: 100px;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .logo {
                max-width: 100%;
                max-height: 100px;
                object-fit: contain;
            }
            .title-container {
                flex: 1;
                text-align: center;
            }
            .school-name {
                font-size: 24pt;
                font-weight: bold;
                margin-bottom: 5px;
                color: #003366;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .system-name {
                font-size: 18pt;
                margin-bottom: 5px;
                color: #4e73df;
            }
            .report-title {
                font-size: 18pt;
                margin: 10px 0 5px;
                color: #4e73df;
                font-weight: bold;
                text-transform: uppercase;
            }
            .report-subtitle {
                font-size: 12pt;
                color: #666;
                margin: 5px 0;
            }
            .filter-info {
                background-color: #f8f9fa;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 20px;
                font-size: 12px;
                border-left: 4px solid #4e73df;
            }
            .student-container {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                justify-content: space-between;
                margin-bottom: 30px;
            }
            .gender-section {
                flex: 1;
                min-width: 45%;
            }
            .section-title {
                background-color: #4e73df;
                color: white;
                padding: 8px 15px;
                border-radius: 5px;
                margin-bottom: 10px;
                font-size: 16px;
                font-weight: bold;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .male-title {
                background-color: #2980b9;
            }
            .female-title {
                background-color: #9b59b6;
            }
            .other-title {
                background-color: #1abc9c;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                font-size: 12px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #4e73df;
                color: white;
                font-weight: bold;
                text-transform: uppercase;
                position: sticky;
                top: 0;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            tr:hover {
                background-color: #e9ecef;
            }
            .summary {
                margin-top: 20px;
                font-weight: bold;
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                border-left: 4px solid #4e73df;
            }
            .print-date {
                text-align: right;
                font-size: 12px;
                color: #666;
                margin-top: 30px;
            }
            .status-badge {
                padding: 3px 8px;
                border-radius: 10px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
                display: inline-block;
                text-align: center;
                min-width: 80px;
            }
            .enrolled {
                background-color: #28a745;
                color: white;
            }
            .pending {
                background-color: #ffc107;
                color: #212529;
            }
            .withdrawn {
                background-color: #dc3545;
                color: white;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                color: #777;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            .watermark {
                position: fixed;
                opacity: 0.05;
                z-index: -1;
                transform: rotate(-45deg);
                font-size: 150px;
                width: 100%;
                text-align: center;
                top: 50%;
                color: #4e73df;
                font-weight: bold;
            }
            .signature-section {
                margin-top: 50px;
                display: flex;
                justify-content: space-between;
                page-break-inside: avoid;
            }
            .signature-box {
                width: 30%;
                text-align: center;
            }
            .signature-line {
                border-top: 1px solid #333;
                margin-top: 40px;
                padding-top: 5px;
                text-align: center;
                font-weight: bold;
            }
            .signature-title {
                text-align: center;
                font-size: 9pt;
                color: #666;
            }
            @media print {
                body {
                    padding: 0;
                    margin: 0.5in;
                }
                .student-container {
                    page-break-inside: avoid;
                }
                .filter-info {
                    border: 1px solid #ddd;
                }
                table { 
                    page-break-inside: auto;
                }
                tr { 
                    page-break-inside: avoid; 
                    page-break-after: auto;
                }
                thead { 
                    display: table-header-group;
                }
                tfoot { 
                    display: table-footer-group;
                }
                @page {
                    size: ${printOptions.landscape ? 'landscape' : 'portrait'};
                }
            }
        </style>
    </head>
    <body>
        <div class="watermark">KLIA</div>
        <div class="header">
            <div class="school-header">
                <div class="logo-container">
                    <img src="${window.location.protocol}//${window.location.host}/Offline enrollment/assets/images/logo.jpg" class="logo" alt="School Logo">
                </div>
                <div class="title-container">
                    <div class="school-name">THE KRISLIZZ INTERNATIONAL ACADEMY INC.</div>
                    <div class="system-name">Enrollment Management System</div>
                    <div class="report-title">${printOptions.customTitle || 'Student Enrollment List'}</div>
                    <div class="report-subtitle">${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                </div>
                <div class="logo-container">
                </div>
            </div>
        </div>
        
        <div class="filter-info">
            <strong>Filters:</strong> 
            Grade Level: ${gradeLevelText} | 
            Strand: ${strandText} | 
            Section: ${sectionText} | 
            School Year: ${schoolYearText} | 
            Semester: ${semesterText} | 
            Status: ${statusText}
        </div>
        
        <div class="student-container">`;
    
    // Add Male Students Section if included
    if (printOptions.includeMale) {
        printContent += `
            <div class="gender-section">
                <div class="section-title male-title">
                    <span>Male Students</span>
                    <span>${maleStudents.length}</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Name</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        if (maleStudents.length > 0) {
            maleStudents.forEach(student => {
                printContent += `
                            <tr>
                                <td>${student.lrn}</td>
                                <td>${student.name}</td>
                                <td>${student.grade}</td>
                                <td>${student.section}</td>
                                <td><span class="status-badge ${student.status.toLowerCase()}">${student.status}</span></td>
                            </tr>`;
            });
        } else {
            printContent += `
                            <tr>
                                <td colspan="5" style="text-align: center;">No male students found</td>
                            </tr>`;
        }
        
        printContent += `
                    </tbody>
                </table>
            </div>`;
    }
    
    // Add Female Students Section if included
    if (printOptions.includeFemale) {
        printContent += `
            <div class="gender-section">
                <div class="section-title female-title">
                    <span>Female Students</span>
                    <span>${femaleStudents.length}</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Name</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        if (femaleStudents.length > 0) {
            femaleStudents.forEach(student => {
                printContent += `
                            <tr>
                                <td>${student.lrn}</td>
                                <td>${student.name}</td>
                                <td>${student.grade}</td>
                                <td>${student.section}</td>
                                <td><span class="status-badge ${student.status.toLowerCase()}">${student.status}</span></td>
                            </tr>`;
            });
        } else {
            printContent += `
                            <tr>
                                <td colspan="5" style="text-align: center;">No female students found</td>
                            </tr>`;
        }
        
        printContent += `
                    </tbody>
                </table>
            </div>`;
    }
    
    // Add Other Gender Students Section if included
    if (printOptions.includeOther && otherStudents.length > 0) {
        printContent += `
            <div class="gender-section" style="width: 100%;">
                <div class="section-title other-title">
                    <span>Other Students</span>
                    <span>${otherStudents.length}</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Name</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        otherStudents.forEach(student => {
            printContent += `
                            <tr>
                                <td>${student.lrn}</td>
                                <td>${student.name}</td>
                                <td>${student.grade}</td>
                                <td>${student.section}</td>
                                <td><span class="status-badge ${student.status.toLowerCase()}">${student.status}</span></td>
                            </tr>`;
        });
        
        printContent += `
                    </tbody>
                </table>
            </div>`;
    }
    
    // Add Summary Section if enabled
    if (printOptions.showSummary) {
        printContent += `
        </div>
        
        <div class="summary">
            <p>Total Students: ${totalStudents}</p>`;
            
        // Add gender breakdown only for included genders
        let summaryItems = [];
        if (printOptions.includeMale) summaryItems.push(`Male: ${maleStudents.length}`);
        if (printOptions.includeFemale) summaryItems.push(`Female: ${femaleStudents.length}`);
        if (printOptions.includeOther && otherStudents.length > 0) summaryItems.push(`Other: ${otherStudents.length}`);
        
        printContent += `
            <p>${summaryItems.join(' | ')}</p>
        </div>`;
    } else {
        printContent += `
        </div>`;
    }
    
    printContent += `
        <div class="print-date">
            Printed on: ${new Date().toLocaleString()}
        </div>

        <div class="footer">
            Senior High School Enrollment System | Student Management Module
        </div>
    </body>
    </html>
    `;
    
    // Write HTML to the new window
    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Wait for content to load then print
    printWindow.onload = function() {
        setTimeout(function() {
            printWindow.print();
        }, 500);
    };
}
</script> 
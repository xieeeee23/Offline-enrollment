<?php
// Make sure we have the correct relative path
$script_path = $_SERVER['SCRIPT_NAME'];
if (strpos($script_path, '/modules/registrar/requirements.php') !== false || 
    strpos($script_path, '\\modules\\registrar\\requirements.php') !== false) {
    $relative_path = '../../';
} else {
    $relative_path = './';
}

// Check if this is an AJAX refresh request
$is_ajax_refresh = isset($_GET['ajax_refresh']) && $_GET['ajax_refresh'] === 'true';

// If AJAX refresh, we only need to include minimal files
if ($is_ajax_refresh) {
    require_once $relative_path . 'includes/config.php';
    require_once $relative_path . 'includes/functions.php';
    
    // Check if user is logged in and has admin or registrar role
    if (!checkAccess(['admin', 'registrar'])) {
        http_response_code(403);
        echo 'Access denied';
        exit;
    }
} else {
$title = 'Student Requirements';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';
}

// Define page header
$page_header = 'Student Requirements Management';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    header('Location: ' . $relative_path . 'dashboard.php');
    exit;
}

// Define requirement types
$requirement_types = [
    'birth_certificate' => 'Birth Certificate',
    'report_card' => 'Report Card / Form 138',
    'good_moral' => 'Good Moral Certificate',
    'medical_certificate' => 'Medical Certificate',
    'id_picture' => '2x2 ID Picture',
    'enrollment_form' => 'Enrollment Form',
    'parent_id' => 'Parent/Guardian ID'
];

// Get all available requirements from the database
$db_requirements = [];
$query = "SELECT * FROM requirements WHERE is_active = 1 ORDER BY name";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Use the name as key (sanitized for column name) and the name as value
        $key = str_replace(' ', '_', $row['name']);
        $key = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
        $key = strtolower($key);
        $db_requirements[$key] = $row['name'];
    }
}

// Merge default requirements with database requirements
// If there are requirements in the database, use those instead
if (!empty($db_requirements)) {
    $requirement_types = $db_requirements;
}

// Debug the final requirements list
error_log('Final requirements list: ' . json_encode($requirement_types));

// Process requirement status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_requirements'])) {
    $student_id = (int) $_POST['student_id'];
    $requirements = isset($_POST['requirements']) ? $_POST['requirements'] : [];
    $remarks = isset($_POST['remarks']) ? cleanInput($_POST['remarks']) : '';
    
    // Begin transaction
    mysqli_autocommit($conn, false);
    $success = true;
    
    // Check if requirements record exists
    $query = "SELECT * FROM student_requirements WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing_record = mysqli_fetch_assoc($result);
    
    // Create upload directory if it doesn't exist
    $upload_dir = $relative_path . 'uploads/requirements/' . $student_id;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Process file uploads
    $file_fields = [];
    foreach ($requirement_types as $key => $label) {
        $file_key = $key . '_file';
        
        // Initialize with existing value or null
        $file_fields[$file_key] = ($existing_record && isset($existing_record[$file_key])) ? 
            $existing_record[$file_key] : null;
        
        // Check if a file was uploaded
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES[$file_key]['tmp_name'];
            $file_name = $_FILES[$file_key]['name'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            
            // Generate a unique filename
            $new_filename = $key . '_' . time() . '.' . $file_ext;
            $file_path = 'uploads/requirements/' . $student_id . '/' . $new_filename;
            $full_path = $relative_path . $file_path;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $full_path)) {
                $file_fields[$file_key] = $file_path;
                
                // If file upload successful, mark requirement as completed
                $requirements[$key] = 1;
            } else {
                $_SESSION['alert'] = showAlert('Error uploading file: ' . $file_name, 'danger');
                $success = false;
            }
        }
    }
    
    // Debug file fields
    error_log("File fields for student $student_id: " . json_encode($file_fields));
    
    if (mysqli_num_rows($result) > 0) {
        // Update existing requirements
        $query = "UPDATE student_requirements SET ";
        $params = [];
        $types = "";
        
        foreach ($requirement_types as $key => $label) {
            $status = isset($requirements[$key]) ? 1 : 0;
            $query .= "$key = ?, ";
            $params[] = $status;
            $types .= "i";
        }
        
        // Add file fields to update query
        foreach ($file_fields as $key => $value) {
            if ($value !== null) { // Only include fields with valid data
                $query .= "$key = ?, ";
                $params[] = $value;
                $types .= "s";
            }
        }
        
        $query .= "remarks = ?, updated_at = NOW() WHERE student_id = ?";
        $params[] = $remarks;
        $params[] = $student_id;
        $types .= "si";
        
        try {
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                throw new Exception('Failed to prepare update statement: ' . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to execute update statement: ' . mysqli_stmt_error($stmt));
            }
            
            $success = true;
        } catch (Exception $e) {
            $success = false;
            $_SESSION['alert'] = showAlert('Error updating requirements: ' . $e->getMessage(), 'danger');
        }
    } else {
        // Insert new requirements
        $query = "INSERT INTO student_requirements (student_id, ";
        $query .= implode(", ", array_keys($requirement_types));
        
        // Add file fields to column names
        foreach ($file_fields as $key => $value) {
            if ($value !== null) { // Only include file fields that have data
                $query .= ", $key";
            }
        }
        
        $query .= ", remarks, created_at, updated_at) VALUES (?, ";
        
        $params = [$student_id];
        $types = "i";
        
        foreach ($requirement_types as $key => $label) {
            $status = isset($requirements[$key]) ? 1 : 0;
            $query .= "?, ";
            $params[] = $status;
            $types .= "i";
        }
        
        // Add file field values
        foreach ($file_fields as $key => $value) {
            if ($value !== null) { // Only include file fields that have data
                $query .= "?, ";
                $params[] = $value;
                $types .= "s";
            }
        }
        
        $query .= "?, NOW(), NOW())";
        $params[] = $remarks;
        $types .= "s";
        
        try {
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to execute statement: ' . mysqli_stmt_error($stmt));
            }
            
            $success = true;
        } catch (Exception $e) {
            $success = false;
            $_SESSION['alert'] = showAlert('Error inserting requirements: ' . $e->getMessage(), 'danger');
        }
    }
    
    // Commit or rollback transaction
    if ($success) {
        mysqli_commit($conn);
        $_SESSION['alert'] = showAlert('Requirements updated successfully.', 'success');
        
        // Log action
        logAction($_SESSION['user_id'], 'UPDATE', 'Updated requirements for student ID: ' . $student_id);
    } else {
        mysqli_rollback($conn);
        if (!isset($_SESSION['alert'])) {
            $_SESSION['alert'] = showAlert('Error updating requirements: ' . mysqli_error($conn), 'danger');
        }
    }
    
    // Redirect to prevent form resubmission - use header directly instead of redirect function
    header('Location: ' . $relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
    exit;
}

// Process file download request
if (isset($_GET['download']) && isset($_GET['student_id']) && isset($_GET['file_type'])) {
    $student_id = (int) $_GET['student_id'];
    $file_type = cleanInput($_GET['file_type']);
    
    // Get the file path from the database
    $query = "SELECT {$file_type}_file FROM student_requirements WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $file_path = $row[$file_type . '_file'];
        
        if (!empty($file_path)) {
            $full_path = $relative_path . $file_path;
            
            if (file_exists($full_path)) {
                // Get file info
                $file_info = pathinfo($full_path);
                $file_name = $file_info['basename'];
                $file_ext = $file_info['extension'];
                
                // Set appropriate content type based on extension
                switch (strtolower($file_ext)) {
                    case 'pdf':
                        $content_type = 'application/pdf';
                        break;
                    case 'jpg':
                    case 'jpeg':
                        $content_type = 'image/jpeg';
                        break;
                    case 'png':
                        $content_type = 'image/png';
                        break;
                    case 'doc':
                        $content_type = 'application/msword';
                        break;
                    case 'docx':
                        $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                        break;
                    default:
                        $content_type = 'application/octet-stream';
                }
                
                // Clean output buffer
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Set headers for download
                header('Content-Type: ' . $content_type);
                header('Content-Disposition: attachment; filename="' . $file_name . '"');
                header('Content-Length: ' . filesize($full_path));
                
                // Output file
                readfile($full_path);
                exit;
            } else {
                $_SESSION['alert'] = showAlert('File not found.', 'danger');
            }
        } else {
            $_SESSION['alert'] = showAlert('No file available for this requirement.', 'warning');
        }
    } else {
        $_SESSION['alert'] = showAlert('Student requirements not found.', 'danger');
    }
    
    header('Location: ' . $relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
    exit;
}

// Check if student_requirements table exists, create if not
$query = "SHOW TABLES LIKE 'student_requirements'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Create the student_requirements table with dynamic columns
    $query = "CREATE TABLE student_requirements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Error creating requirements table: ' . mysqli_error($conn), 'danger');
        error_log('Error creating student_requirements table: ' . mysqli_error($conn));
    } else {
        error_log('Created student_requirements table successfully');
        
        // Add columns for each requirement type
        foreach ($requirement_types as $key => $label) {
            $alter_query = "ALTER TABLE student_requirements 
                           ADD COLUMN $key TINYINT(1) DEFAULT 0,
                           ADD COLUMN {$key}_file VARCHAR(255) DEFAULT NULL";
            
            if (!mysqli_query($conn, $alter_query)) {
                error_log('Error adding columns for requirement ' . $key . ': ' . mysqli_error($conn));
            } else {
                error_log('Added columns for requirement ' . $key . ' successfully');
            }
        }
    }
    
    // Create directory for document uploads if it doesn't exist
    $upload_dir = $relative_path . 'uploads/requirements';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
} else {
    // Check if all requirement columns exist in the student_requirements table
    foreach ($requirement_types as $key => $label) {
        // Check if the status column exists
        $check_column_query = "SHOW COLUMNS FROM student_requirements LIKE '$key'";
        $column_result = mysqli_query($conn, $check_column_query);
        
        if (mysqli_num_rows($column_result) == 0) {
            // Column doesn't exist, add it
            $alter_query = "ALTER TABLE student_requirements ADD COLUMN $key TINYINT(1) DEFAULT 0";
            if (mysqli_query($conn, $alter_query)) {
                error_log("Added missing column '$key' to student_requirements table");
            } else {
                error_log("Failed to add column '$key': " . mysqli_error($conn));
            }
        }
        
        // Check if the file column exists
        $file_column = $key . '_file';
        $check_file_column_query = "SHOW COLUMNS FROM student_requirements LIKE '$file_column'";
        $file_column_result = mysqli_query($conn, $check_file_column_query);
        
        if (mysqli_num_rows($file_column_result) == 0) {
            // File column doesn't exist, add it
            $alter_query = "ALTER TABLE student_requirements ADD COLUMN $file_column VARCHAR(255) DEFAULT NULL";
            if (mysqli_query($conn, $alter_query)) {
                error_log("Added missing file column '$file_column' to student_requirements table");
            } else {
                error_log("Failed to add file column '$file_column': " . mysqli_error($conn));
            }
        }
    }
}

// Check if requirements table exists, create if not
$query = "SHOW TABLES LIKE 'requirements'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Create the requirements table
    $query = "CREATE TABLE requirements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        program VARCHAR(50) NOT NULL,
        description TEXT,
        is_required TINYINT(1) DEFAULT 1,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Error creating requirements table: ' . mysqli_error($conn), 'danger');
    } else {
        // Insert default requirements based on requirement_types
        foreach ($requirement_types as $key => $label) {
            $query = "INSERT INTO requirements (name, type, program, description, is_required, is_active) 
                      VALUES (?, 'document', 'all', ?, 1, 1)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $label, $label);
            mysqli_stmt_execute($stmt);
        }
        
        $_SESSION['alert'] = showAlert('Requirements table created and populated with default requirements.', 'success');
        
        // Log action
        if (isset($_SESSION['user_id'])) {
            logAction($_SESSION['user_id'], 'CREATE', 'Created requirements table and added default requirements');
        }
    }
}

// Check if requirement_types table exists, create if not
$query = "SHOW TABLES LIKE 'requirement_types'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Create the requirement_types table
    $query = "CREATE TABLE requirement_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        is_required TINYINT(1) DEFAULT 1,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Error creating requirement_types table: ' . mysqli_error($conn), 'danger');
        error_log('Error creating requirement_types table: ' . mysqli_error($conn));
    } else {
        // Insert default requirement types
        foreach ($requirement_types as $key => $label) {
            $query = "INSERT INTO requirement_types (name, description, is_required, is_active) 
                      VALUES (?, ?, 1, 1)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $label, $label);
            mysqli_stmt_execute($stmt);
        }
        
        $_SESSION['alert'] = showAlert('Requirement types table created and populated with default types.', 'success');
        error_log('Requirement types table created and populated with default types');
        
        // Log action
        if (isset($_SESSION['user_id'])) {
            logAction($_SESSION['user_id'], 'CREATE', 'Created requirement_types table and added default types');
        }
    }
}

// Create templates directory and CSV template if it doesn't exist
$templates_dir = $relative_path . 'modules/registrar/templates';
if (!file_exists($templates_dir)) {
    if (!mkdir($templates_dir, 0777, true)) {
        $_SESSION['alert'] = showAlert('Error creating templates directory. Please check permissions.', 'warning');
    }
}

$csv_template_file = $templates_dir . '/requirements_template.csv';
if (!file_exists($csv_template_file)) {
    $csv_content = "Name,Type,RequiredFor,Description,IsRequired\n";
    $csv_content .= "Birth Certificate,document,all,Official birth certificate,Yes\n";
    $csv_content .= "Form 137,document,undergraduate,Official high school transcript,Yes\n";
    $csv_content .= "Good Moral Character,document,all,Certificate of good moral character,Yes\n";
    $csv_content .= "Medical Certificate,document,all,Recent medical certificate,Yes\n";
    $csv_content .= "Graduation Certificate,document,graduate,College graduation certificate,Yes\n";
    
    if (file_put_contents($csv_template_file, $csv_content) === false) {
        $_SESSION['alert'] = showAlert('Error creating CSV template file. Please check permissions.', 'warning');
    }
}

// Get student list
$students = [];
$query = "SELECT s.id, s.lrn, s.first_name, s.last_name, s.grade_level, s.section, s.strand, s.enrollment_status,
          CASE 
            WHEN r.id IS NULL THEN 0
            WHEN (r.birth_certificate + r.report_card + r.good_moral + r.medical_certificate + r.id_picture + r.enrollment_form + r.parent_id) = " . count($requirement_types) . " THEN 1
            ELSE 0
          END AS requirements_complete
          FROM students s
          LEFT JOIN student_requirements r ON s.id = r.student_id
          ORDER BY s.last_name, s.first_name";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

// Get student requirements if student_id is provided
$student_requirements = null;
$selected_student = null;
if (isset($_GET['student_id']) && is_numeric($_GET['student_id'])) {
    $student_id = (int) $_GET['student_id'];
    
    // Get student details
    $query = "SELECT id, lrn, first_name, last_name, grade_level, section, strand, enrollment_status 
              FROM students 
              WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $selected_student = mysqli_fetch_assoc($result);
        
        // Get student requirements
        $query = "SELECT * FROM student_requirements WHERE student_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $student_requirements = mysqli_fetch_assoc($result);
        }
    }
}

// Process export to PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // PDF export functionality has been removed
    $_SESSION['alert'] = showAlert('PDF export functionality has been removed from the system.', 'info');
        header('Location: ' . $relative_path . 'modules/registrar/requirements.php');
    exit;
}

// Process export to Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Clean output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Generate filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "student_requirements_{$timestamp}.csv";
    
    // Set headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create output handle
    $output = fopen('php://output', 'w');
    
    // Write CSV header
    $header = ['LRN', 'Last Name', 'First Name', 'Grade Level', 'Section', 'Status'];
    foreach ($requirement_types as $label) {
        $header[] = $label;
    }
    $header[] = 'Remarks';
    
    fputcsv($output, $header);
    
    // Get all students with their requirements
    $query = "SELECT s.id, s.lrn, s.first_name, s.last_name, s.grade_level, s.section, 
              s.enrollment_status, r.*
              FROM students s
              LEFT JOIN student_requirements r ON s.id = r.student_id
              ORDER BY s.grade_level, s.section, s.last_name, s.first_name";
    $result = mysqli_query($conn, $query);
    
    // Write data rows
    while ($row = mysqli_fetch_assoc($result)) {
        $data = [
            $row['lrn'],
            $row['last_name'],
            $row['first_name'],
            $row['grade_level'],
            $row['section'],
            ucfirst($row['enrollment_status'])
        ];
        
        foreach ($requirement_types as $key => $label) {
            $data[] = isset($row[$key]) && $row[$key] ? 'Yes' : 'No';
        }
        
        $data[] = $row['remarks'] ?? '';
        
        fputcsv($output, $data);
    }
    
    fclose($output);
    exit;
}

// Process export to Word
if (isset($_GET['export']) && $_GET['export'] === 'word') {
    // Clean output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Generate filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "student_requirements_{$timestamp}.doc";
    
    // Set headers for download
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Start building the Word document
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<title>Student Requirements Report</title>';
    echo '<!--[if gte mso 9]>';
    echo '<xml>';
    echo '<w:WordDocument>';
    echo '<w:View>Print</w:View>';
    echo '<w:Zoom>100</w:Zoom>';
    echo '<w:DoNotOptimizeForBrowser/>';
    echo '</w:WordDocument>';
    echo '</xml>';
    echo '<![endif]-->';
    echo '<style>';
    echo 'table {border-collapse: collapse; width: 100%;}';
    echo 'th, td {border: 1px solid #ddd; padding: 8px;}';
    echo 'th {background-color: #f2f2f2;}';
    echo '.yes {color: green;}';
    echo '.no {color: red;}';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<h1>Student Requirements Report</h1>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>LRN</th>';
    echo '<th>Name</th>';
    echo '<th>Grade & Section</th>';
    echo '<th>Status</th>';
    
    foreach ($requirement_types as $key => $label) {
        echo '<th>' . htmlspecialchars($label) . '</th>';
    }
    
    echo '<th>Remarks</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    
    // Get all students with their requirements
    $query = "SELECT s.id, s.lrn, s.first_name, s.last_name, s.grade_level, s.section, 
              s.enrollment_status, r.*
              FROM students s
              LEFT JOIN student_requirements r ON s.id = r.student_id
              ORDER BY s.grade_level, s.section, s.last_name, s.first_name";
    $result = mysqli_query($conn, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['lrn']) . '</td>';
        echo '<td>' . htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['grade_level'] . ' - ' . $row['section']) . '</td>';
        echo '<td>' . htmlspecialchars(ucfirst($row['enrollment_status'])) . '</td>';
        
        foreach ($requirement_types as $key => $label) {
            $status = isset($row[$key]) && $row[$key] ? 'Yes' : 'No';
            $class = isset($row[$key]) && $row[$key] ? 'yes' : 'no';
            echo '<td class="' . $class . '">' . $status . '</td>';
        }
        
        echo '<td>' . htmlspecialchars($row['remarks'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</body></html>';
    exit;
}

// Add bulk upload processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_upload']) && isset($_POST['student_id'])) {
    $student_id = (int) $_POST['student_id'];
    $requirement_type = isset($_POST['requirement_type']) ? cleanInput($_POST['requirement_type']) : '';
    
    if (empty($requirement_type) || !array_key_exists($requirement_type, $requirement_types)) {
        $_SESSION['alert'] = showAlert('Invalid requirement type.', 'danger');
        header('Location: ' . $relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
        exit;
    }
    
    // Check if files were uploaded
    if (isset($_FILES['bulk_files']) && !empty($_FILES['bulk_files']['name'][0])) {
        // Create upload directory if it doesn't exist
        $upload_dir = $relative_path . 'uploads/requirements/' . $student_id;
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Begin transaction
        mysqli_autocommit($conn, false);
        $success = true;
        
        // Get existing requirements
        $query = "SELECT * FROM student_requirements WHERE student_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $existing_record = mysqli_fetch_assoc($result);
        
        // Process each uploaded file
        $file_count = count($_FILES['bulk_files']['name']);
        $uploaded_files = [];
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['bulk_files']['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['bulk_files']['tmp_name'][$i];
                $file_name = $_FILES['bulk_files']['name'][$i];
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                
                // Generate a unique filename
                $new_filename = $requirement_type . '_' . time() . '_' . $i . '.' . $file_ext;
                $file_path = 'uploads/requirements/' . $student_id . '/' . $new_filename;
                $full_path = $relative_path . $file_path;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $full_path)) {
                    $uploaded_files[] = $file_path;
                } else {
                    $_SESSION['alert'] = showAlert('Error uploading file: ' . $file_name, 'danger');
                    $success = false;
                    break;
                }
            }
        }
        
        // If files were uploaded successfully, update the database
        if ($success && !empty($uploaded_files)) {
            $file_column = $requirement_type . '_file';
            $file_path = implode('|', $uploaded_files);
            
            if ($existing_record) {
                // Update existing record
                $query = "UPDATE student_requirements SET $requirement_type = 1, $file_column = ? WHERE student_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "si", $file_path, $student_id);
            } else {
                // Insert new record
                $query = "INSERT INTO student_requirements (student_id, $requirement_type, $file_column, created_at, updated_at) 
                          VALUES (?, 1, ?, NOW(), NOW())";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "is", $student_id, $file_path);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                $success = false;
            }
        }
        
        // Commit or rollback transaction
        if ($success) {
            mysqli_commit($conn);
            $_SESSION['alert'] = showAlert('Files uploaded successfully.', 'success');
            
            // Log action
            logAction($_SESSION['user_id'], 'UPLOAD', 'Uploaded ' . count($uploaded_files) . ' files for requirement: ' . $requirement_types[$requirement_type] . ' (Student ID: ' . $student_id . ')');
        } else {
            mysqli_rollback($conn);
            $_SESSION['alert'] = showAlert('Error uploading files: ' . mysqli_error($conn), 'danger');
        }
        
        header('Location: ' . $relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
        exit;
    } else {
        $_SESSION['alert'] = showAlert('No files selected for upload.', 'warning');
        header('Location: ' . $relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
        exit;
    }
}
?>

<?php if (isset($_SESSION['alert'])) {
    echo $_SESSION['alert'];
    unset($_SESSION['alert']);
} ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_header; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Student Requirements</li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])) { echo $_SESSION['alert']; unset($_SESSION['alert']); } ?>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-tasks me-1"></i> Manage Student Requirements
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRequirementTypeModal">
                    <i class="fas fa-plus me-1"></i> Add Requirement Type
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRequirementModal">
                        <i class="fas fa-plus-circle me-1"></i> Add New Requirement
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#uploadRequirementsModal">
                        <i class="fas fa-upload me-1"></i> Batch Upload
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <button type="button" class="btn btn-outline-success" id="refreshTable">
                        <i class="fas fa-sync-alt me-1"></i> Refresh Table
                    </button>
                    <button type="button" class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#helpModal">
                        <i class="fas fa-question-circle me-1"></i> Help
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="requirementsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Requirement</th>
                            <th>Type</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get requirements list from database with debugging
                        error_log("Fetching requirements from database");
                        $query = "SELECT * FROM requirements ORDER BY id";
                        $result = mysqli_query($conn, $query);
                        
                        if (!$result) {
                            error_log("Error fetching requirements: " . mysqli_error($conn));
                        }
                        
                        // Log the number of requirements found
                        $num_rows = mysqli_num_rows($result);
                        error_log("Found $num_rows requirements in the database");
                        
                        if ($result && $num_rows > 0) {
                            $count = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                $status_class = $row['is_active'] ? 'bg-success' : 'bg-secondary';
                                $status_text = $row['is_active'] ? 'Active' : 'Inactive';
                                $status_icon = $row['is_active'] ? 'fa-check-circle' : 'fa-times-circle';
                                
                                echo '<tr>
                                    <td>' . $count . '</td>
                                    <td>' . htmlspecialchars($row['name']) . '</td>
                                    <td>' . htmlspecialchars(ucfirst($row['type'])) . '</td>
                                    <td>' . htmlspecialchars(ucfirst($row['program'])) . '</td>
                                    <td>
                                        <span class="badge ' . $status_class . '">
                                            <i class="fas ' . $status_icon . ' me-1"></i> ' . $status_text . '
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary edit-requirement-btn" data-id="' . $row['id'] . '" onclick="editRequirement(' . $row['id'] . ')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteRequirement(' . $row['id'] . ')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm ' . ($row['is_active'] ? 'btn-warning' : 'btn-success') . '" 
                                            onclick="toggleRequirementStatus(' . $row['id'] . ', ' . $row['is_active'] . ')">
                                            <i class="fas ' . ($row['is_active'] ? 'fa-toggle-off' : 'fa-toggle-on') . '"></i>
                                        </button>
                                    </td>
                                </tr>';
                                $count++;
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">No requirements found. Add your first requirement using the form above.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
<?php
// If this is an AJAX refresh request, stop here
if ($is_ajax_refresh) {
    exit;
}
?>
    
    <?php if ($selected_student): ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">
                        Student Requirements: <?php echo htmlspecialchars($selected_student['last_name'] . ', ' . $selected_student['first_name']); ?>
                    </h6>
                    <div>
                        <span class="badge bg-info">
                            Grade: <?php echo htmlspecialchars($selected_student['grade_level'] . ' - ' . $selected_student['section']); ?>
                        </span>
                        <span class="badge <?php echo $selected_student['enrollment_status'] === 'enrolled' ? 'bg-success' : 'bg-warning'; ?> ms-2">
                            <?php echo ucfirst(htmlspecialchars($selected_student['enrollment_status'])); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data">
                        <input type="hidden" name="student_id" value="<?php echo $selected_student['id']; ?>">
                        <input type="hidden" name="update_requirements" value="1">
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Requirement</th>
                                        <th>Status</th>
                                        <th>Upload File</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requirement_types as $key => $label): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($label); ?></td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input custom-switch" type="checkbox" 
                                                    id="req_<?php echo $key; ?>" 
                                                    name="requirements[<?php echo $key; ?>]" 
                                                    <?php echo (isset($student_requirements[$key]) && $student_requirements[$key]) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="req_<?php echo $key; ?>">
                                                    <?php if (isset($student_requirements[$key]) && $student_requirements[$key]): ?>
                                                        <span class="text-success">Submitted</span>
                                                    <?php else: ?>
                                                        <span class="text-danger">Missing</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="file" class="form-control form-control-sm" name="<?php echo $key; ?>_file">
                                        </td>
                                        <td>
                                            <?php if (isset($student_requirements[$key.'_file']) && $student_requirements[$key.'_file']): ?>
                                                <a href="<?php echo $relative_path; ?>modules/registrar/requirements.php?download=1&student_id=<?php echo $selected_student['id']; ?>&file_type=<?php echo $key; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="fas fa-file-upload"></i> No file</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3"><?php echo isset($student_requirements['remarks']) ? htmlspecialchars($student_requirements['remarks']) : ''; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4 text-end align-self-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Requirements
                                </button>
                                <a href="<?php echo $relative_path; ?>modules/registrar/requirements.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
    <!-- Full-width Student List -->
    <div class="col-md-12">
            <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-white">Student List</h6>
                <div id="studentCounter" class="small text-muted">
                    Please apply filters to view student data
                </div>
                </div>
                <div class="card-body">
                <!-- Search and filter options -->
                <div class="mb-4">
                    <div class="row">
                        <!-- Search box with enter button -->
                        <div class="col-md-4 mb-2">
                            <div class="input-group">
                                <input type="text" id="studentSearch" class="form-control" placeholder="Search students by name...">
                                <button id="searchButton" class="btn btn-primary" type="button">
                                    <i class="fas fa-search"></i> Search
                                </button>
                        </div>
                        </div>
                        
                        <!-- Filter dropdowns -->
                        <div class="col-md-2 mb-2">
                            <select id="gradeFilter" class="form-select">
                                    <option value="">All Grades</option>
                                <option value="11">Grade 11</option>
                                <option value="12">Grade 12</option>
                                </select>
                            </div>
                        
                        <div class="col-md-2 mb-2">
                            <select id="sectionFilter" class="form-select">
                                    <option value="">All Sections</option>
                                    <?php
                                    $sections = array();
                                    foreach ($students as $student) {
                                    if ($student['enrollment_status'] === 'pending') continue;
                                        if (!in_array($student['section'], $sections) && !empty($student['section'])) {
                                            $sections[] = $student['section'];
                                        }
                                    }
                                    sort($sections);
                                    foreach ($sections as $section) {
                                        echo '<option value="' . htmlspecialchars($section) . '">' . htmlspecialchars($section) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        
                        <div class="col-md-2 mb-2">
                            <select id="strandFilter" class="form-select">
                                    <option value="">All Strands</option>
                                    <?php
                                    $strands = array();
                                    foreach ($students as $student) {
                                    if ($student['enrollment_status'] === 'pending') continue;
                                        if (isset($student['strand']) && !empty($student['strand']) && !in_array($student['strand'], $strands)) {
                                            $strands[] = $student['strand'];
                                        }
                                    }
                                    sort($strands);
                                    foreach ($strands as $strand) {
                                        echo '<option value="' . htmlspecialchars($strand) . '">' . htmlspecialchars($strand) . '</option>';
                                    }
                                    ?>
                                </select>
                        </div>
                        
                        <div class="col-md-2 mb-2">
                            <select id="statusFilter" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="enrolled">Enrolled</option>
                                <option value="irregular">Irregular</option>
                                </select>
                            </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-3 mb-2">
                            <select id="requirementsFilter" class="form-select">
                                    <option value="">All Requirements</option>
                                <option value="1">Complete</option>
                                <option value="0">Incomplete</option>
                                </select>
                        </div>
                        
                        <div class="col-md-9 d-flex justify-content-end">
                            <button id="resetFilters" class="btn btn-secondary">
                                <i class="fas fa-undo me-1"></i> Reset Filters
                            </button>
                        </div>
                        </div>
                    </div>
                
                <!-- Student List - Initially hidden -->
                <div class="table-responsive" id="studentList" style="display: none;">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Student Name</th>
                                <th>Grade & Section</th>
                                <th>Strand</th>
                                <th>Requirements</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): 
                                if ($student['enrollment_status'] === 'pending') continue;
                            ?>
                            <tr class="<?php echo ($student['requirements_complete'] == 1) ? 'table-success' : 'table-danger'; ?>"
                                data-grade="<?php echo htmlspecialchars($student['grade_level']); ?>"
                               data-section="<?php echo htmlspecialchars($student['section']); ?>"
                               data-strand="<?php echo htmlspecialchars($student['strand'] ?? ''); ?>"
                               data-status="<?php echo htmlspecialchars(strtolower($student['enrollment_status'])); ?>"
                                data-requirements="<?php echo $student['requirements_complete']; ?>"
                                style="display: none;">
                                <td class="fw-bold"><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['grade_level'] . ' - ' . $student['section']); ?></td>
                                <td><?php echo isset($student['strand']) && !empty($student['strand']) ? htmlspecialchars($student['strand']) : 'N/A'; ?></td>
                                <td>
                                        <?php if ($student['requirements_complete'] == 1): ?>
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Complete</span>
                                        <?php else: ?>
                                            <span class="text-danger"><i class="fas fa-times-circle"></i> Incomplete</span>
                                        <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php 
                                        echo $student['enrollment_status'] === 'enrolled' ? 'bg-success' : 'bg-info'; 
                                    ?> rounded-pill">
                                    <?php echo ucfirst(htmlspecialchars($student['enrollment_status'])); ?>
                                </span>
                                </td>
                                <td>
                                    <a href="<?php echo $relative_path; ?>modules/registrar/requirements.php?student_id=<?php echo $student['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                            </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                
                <!-- Empty state message -->
                <div id="emptyState" class="text-center py-5">
                    <img src="<?php echo $relative_path; ?>assets/img/undraw_file_searching.svg" alt="No students" style="max-width: 200px; margin-bottom: 20px;">
                    <h5>No students found</h5>
                    <p class="text-muted">Try adjusting your search or filters</p>
                </div>
            </div>
        </div>
                </div>
                    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filters = {
        search: document.getElementById('studentSearch'),
        searchBtn: document.getElementById('searchButton'),
        grade: document.getElementById('gradeFilter'),
        section: document.getElementById('sectionFilter'),
        strand: document.getElementById('strandFilter'),
        status: document.getElementById('statusFilter'),
        requirements: document.getElementById('requirementsFilter'),
        reset: document.getElementById('resetFilters'),
        counter: document.getElementById('studentCounter'),
        list: document.getElementById('studentList'),
        emptyState: document.getElementById('emptyState'),
        tableRows: document.querySelectorAll('#studentList tbody tr')
    };

    const totalStudents = filters.tableRows.length;
    let anyFilterActive = false;

    function updateList() {
        const searchTerm = filters.search.value.toLowerCase();
        const gradeValue = filters.grade.value;
        const sectionValue = filters.section.value;
        const strandValue = filters.strand.value;
        const statusValue = filters.status.value.toLowerCase();
        const reqValue = filters.requirements.value;

        anyFilterActive = searchTerm || gradeValue || sectionValue || strandValue || statusValue || reqValue;

        let visibleCount = 0;

        filters.tableRows.forEach(row => {
            const name = row.querySelector('td:first-child').textContent.toLowerCase();
            const grade = row.dataset.grade;
            const matchesSearch = !searchTerm || name.includes(searchTerm);
            const matchesGrade = !gradeValue || grade === gradeValue;
            const matchesSection = !sectionValue || row.dataset.section === sectionValue;
            const matchesStrand = !strandValue || row.dataset.strand === strandValue;
            const matchesStatus = !statusValue || row.dataset.status === statusValue;
            const matchesReq = !reqValue || row.dataset.requirements === reqValue;

            if (matchesSearch && matchesGrade && matchesSection && matchesStrand && matchesStatus && matchesReq) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (anyFilterActive) {
            filters.list.style.display = visibleCount > 0 ? 'block' : 'none';
            filters.emptyState.style.display = visibleCount > 0 ? 'none' : 'block';
            filters.counter.textContent = `Showing ${visibleCount} of ${totalStudents} students`;
        } else {
            filters.list.style.display = 'none';
            filters.emptyState.style.display = 'none';
            filters.counter.textContent = 'Please apply filters to view student data';
        }
    }

    // Event listeners
    [filters.grade, filters.section, filters.strand, filters.status, filters.requirements].forEach(filter => {
        filter.addEventListener('change', updateList);
    });

    filters.searchBtn.addEventListener('click', updateList);
    filters.search.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') updateList();
    });

    filters.reset.addEventListener('click', function() {
        filters.search.value = '';
        filters.grade.value = '';
        filters.section.value = '';
        filters.strand.value = '';
        filters.status.value = '';
        filters.requirements.value = '';
        anyFilterActive = false;
        updateList();
    });

    updateList();
});
</script>
    <?php endif; ?>
</div>

<!-- Add Requirement Modal -->
<div class="modal fade" id="addRequirementModal" tabindex="-1" aria-labelledby="addRequirementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white" id="addRequirementModalLabel">Add New Requirement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addRequirementForm" method="post" action="<?php echo $relative_path; ?>modules/registrar/process_requirement.php">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="requirementName" class="form-label">Requirement Name</label>
                        <input type="text" class="form-control" id="requirementName" name="requirement_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="requirementType" class="form-label">Requirement Type</label>
                        <select class="form-select" id="requirementType" name="requirement_type" required>
                            <option value="" selected disabled>Select Type</option>
                            <option value="document">Document</option>
                            <option value="payment">Payment</option>
                            <option value="form">Form</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="requiredFor" class="form-label">Required For</label>
                        <select class="form-select" id="requiredFor" name="required_for" required>
                            <option value="" selected disabled>Select Program</option>
                            <option value="all">All Programs</option>
                            <option value="undergraduate">Undergraduate</option>
                            <option value="graduate">Graduate</option>
                            <option value="masters">Masters</option>
                            <option value="phd">PhD</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="requirementDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="requirementDescription" name="requirement_description" rows="3"></textarea>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="isRequired" name="is_required" checked>
                        <label class="form-check-label" for="isRequired">Required for Enrollment</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addRequirementForm" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Add Requirement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Requirement Modal -->
<div class="modal fade" id="editRequirementModal" tabindex="-1" aria-labelledby="editRequirementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white" id="editRequirementModalLabel">Edit Requirement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading spinner -->
                <div id="editModalLoader" class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading requirement data...</p>
                </div>
                
                <div id="editModalContent" style="display: none;">
                    <form id="editRequirementForm" method="post" action="<?php echo $relative_path; ?>modules/registrar/process_requirement.php">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="requirement_id" id="editRequirementId">
                        <div class="mb-3">
                            <label for="editRequirementName" class="form-label">Requirement Name</label>
                            <input type="text" class="form-control" id="editRequirementName" name="requirement_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRequirementType" class="form-label">Requirement Type</label>
                            <select class="form-select" id="editRequirementType" name="requirement_type" required>
                                <option value="document">Document</option>
                                <option value="payment">Payment</option>
                                <option value="form">Form</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editRequiredFor" class="form-label">Required For</label>
                            <select class="form-select" id="editRequiredFor" name="required_for" required>
                                <option value="all">All Programs</option>
                                <option value="undergraduate">Undergraduate</option>
                                <option value="graduate">Graduate</option>
                                <option value="masters">Masters</option>
                                <option value="phd">PhD</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editRequirementDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editRequirementDescription" name="requirement_description" rows="3"></textarea>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="editIsRequired" name="is_required">
                            <label class="form-check-label" for="editIsRequired">Required for Enrollment</label>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editRequirementForm" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Update Requirement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Requirements Modal -->
<div class="modal fade" id="uploadRequirementsModal" tabindex="-1" aria-labelledby="uploadRequirementsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white" id="uploadRequirementsModalLabel">Batch Upload Requirements</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="uploadRequirementsForm" method="post" action="<?php echo $relative_path; ?>modules/registrar/process_requirement.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="batch_upload">
                    <div class="mb-3">
                        <label for="requirementsFile" class="form-label">Upload CSV File</label>
                        <input type="file" class="form-control" id="requirementsFile" name="requirements_file" accept=".csv" required>
                        <div class="form-text">File must be in CSV format with headers: Name, Type, RequiredFor, Description, IsRequired</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="overwriteExisting" name="overwrite_existing">
                            <label class="form-check-label" for="overwriteExisting">Overwrite Existing Requirements</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="uploadRequirementsForm" class="btn btn-primary">
                    <i class="fas fa-upload me-1"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white" id="helpModalLabel">Requirements Management Help</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="helpAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                How to Add Requirements
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <p>To add a new requirement:</p>
                                <ol>
                                    <li>Click the "Add Requirement" button</li>
                                    <li>Fill in all required fields</li>
                                    <li>Click "Add Requirement" to save</li>
                                </ol>
                                <p>Each requirement needs a name, type, and which programs it applies to.</p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Batch Uploading Requirements
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <p>To upload multiple requirements at once:</p>
                                <ol>
                                    <li>Prepare a CSV file with the required columns</li>
                                    <li>Click "Batch Upload" from the options menu</li>
                                    <li>Select your CSV file</li>
                                    <li>Choose whether to overwrite existing requirements</li>
                                    <li>Click "Upload"</li>
                                </ol>
                                <p><strong>CSV Format:</strong> Name, Type, RequiredFor, Description, IsRequired</p>
                                <p>Download a <a href="templates/requirements_template.csv">sample template</a></p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Managing Requirements
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <p>You can manage existing requirements with the following actions:</p>
                                <ul>
                                    <li><strong>Edit:</strong> Update an existing requirement's details</li>
                                    <li><strong>Delete:</strong> Remove a requirement (cannot be undone)</li>
                                    <li><strong>Status Toggle:</strong> Quickly enable/disable a requirement</li>
                                </ul>
                                <p>Requirements are automatically applied to students based on their program.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Requirement Type Modal -->
<div class="modal fade" id="addRequirementTypeModal" tabindex="-1" aria-labelledby="addRequirementTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white" id="addRequirementTypeModalLabel">Add Requirement Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addRequirementTypeForm" method="post" action="<?php echo $relative_path; ?>modules/registrar/process_requirement.php">
                    <input type="hidden" name="action" value="add_type">
                    <div class="mb-3">
                        <label for="requirementTypeName" class="form-label">Type Name</label>
                        <input type="text" class="form-control" id="requirementTypeName" name="requirement_type_name" required>
                        <div class="form-text">Enter a name for the new requirement type (e.g., "Medical", "Financial", etc.)</div>
                    </div>
                    <div class="mb-3">
                        <label for="requirementTypeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="requirementTypeDescription" name="requirement_type_description" rows="3"></textarea>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="typeIsRequired" name="type_is_required" checked>
                        <label class="form-check-label" for="typeIsRequired">Required by Default</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addRequirementTypeForm" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Add Type
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add a hidden test link for debugging -->
<div style="display: none;">
    <a href="<?php echo $relative_path; ?>modules/registrar/process_requirement.php?action=get&id=1" target="_blank" id="testAjaxLink">Test AJAX</a>
</div>

<!-- JavaScript to enhance the functionality -->
<script>
    // Initialize DataTables for better table management
    $(document).ready(function() {
        try {
            // Check if the table is already initialized as a DataTable
            if (!$.fn.DataTable.isDataTable('#requirementsTable')) {
                $('#requirementsTable').DataTable({
                    "order": [[0, "asc"]],
                    "pageLength": 25,
                    "language": {
                        "search": "Search requirements:",
                        "lengthMenu": "Show _MENU_ requirements per page",
                        "info": "Showing _START_ to _END_ of _TOTAL_ requirements",
                        "emptyTable": "No requirements found"
                    },
                    "columnDefs": [
                        { "orderable": false, "targets": 5 }
                    ]
                });
            }
            
            // Update form action with correct paths
            $('#addRequirementForm').attr('action', '<?php echo $relative_path; ?>modules/registrar/process_requirement.php');
            $('#editRequirementForm').attr('action', '<?php echo $relative_path; ?>modules/registrar/process_requirement.php');
            $('#uploadRequirementsForm').attr('action', '<?php echo $relative_path; ?>modules/registrar/process_requirement.php');
            
            // Force initial filtering to ensure counter is correct
            filterStudents();
            
            // Student search and filter functionality
            $("#studentSearch").on("keyup", function() {
                // Auto-search as you type
                filterStudents();
            });
            
            // Add event listeners for all filter dropdowns to filter in real-time
            $("#gradeFilter, #sectionFilter, #strandFilter, #statusFilter, #requirementsFilter").on("change", function() {
                filterStudents();
            });
            
            // Apply filters when button is clicked
            $("#applyFilters").on("click", function() {
                filterStudents();
            });
            
            // Reset all filters when reset button is clicked
            $("#resetFilters").on("click", function() {
                $("#studentSearch").val("");
                $("#gradeFilter, #sectionFilter, #strandFilter, #statusFilter, #requirementsFilter").val("");
                filterStudents();
            });
            
            // Update counter on page load
            updateStudentCounter(<?php echo count($students); ?>);
            
            // Add click event handler for edit buttons - fixed implementation
            $(document).on('click', '.edit-requirement-btn', function() {
                const requirementId = $(this).data('id');
                console.log('Edit button clicked for requirement ID:', requirementId);
                editRequirement(requirementId);
            });
        } catch (e) {
            console.error("DataTable initialization error:", e);
        }
    });
    
    // Function to populate edit modal with requirement data
    function editRequirement(id) {
        // Debug the call
        console.log('Edit requirement called for ID:', id);
        
        // Show the modal first to prevent flickering
        $('#editRequirementModal').modal('show');
        
        // Show loading indicator
        $('#editModalLoader').show();
        $('#editModalContent').hide();
        
        // Fetch requirement data via AJAX
        $.ajax({
            url: '<?php echo $relative_path; ?>modules/registrar/process_requirement.php',
            type: 'GET',
            data: { 
                action: 'get', 
                id: id 
            },
            dataType: 'json',
            success: function(response) {
                console.log('Response received:', response);
                
                if (response && response.success === true) {
                    var requirement = response.data;
                    console.log('Requirement data:', requirement);
                    
                    // Populate the form fields
                    $('#editRequirementId').val(requirement.id);
                    $('#editRequirementName').val(requirement.name);
                    $('#editRequirementType').val(requirement.type);
                    $('#editRequiredFor').val(requirement.program);
                    $('#editRequirementDescription').val(requirement.description);
                    $('#editIsRequired').prop('checked', requirement.is_required == 1);
                    
                    console.log('Form populated with data');
                    
                    // Show the modal content
                    $('#editModalLoader').hide();
                    $('#editModalContent').show();
                } else {
                    $('#editModalLoader').hide();
                    showErrorAlert('Error: ' + (response && response.message ? response.message : 'Unknown error'));
                    $('#editRequirementModal').modal('hide');
                }
            },
            error: function(xhr, status, error) {
                $('#editModalLoader').hide();
                console.error('AJAX Error:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                showErrorAlert('Error fetching requirement data. Please try again.');
                $('#editRequirementModal').modal('hide');
            }
        });
    }

    // Function to confirm requirement deletion
    function deleteRequirement(id) {
        if (confirm('Are you sure you want to delete this requirement? This action cannot be undone.')) {
            // Show loading indicator
            document.body.classList.add('loading');
            
            // Create a timestamp to prevent caching
            const timestamp = new Date().getTime();
            window.location.href = '<?php echo $relative_path; ?>modules/registrar/process_requirement.php?action=delete&id=' + id + '&t=' + timestamp;
        }
        return false;
    }

    // Function to toggle requirement status
    function toggleRequirementStatus(id, status) {
        window.location.href = '<?php echo $relative_path; ?>modules/registrar/process_requirement.php?action=toggle_status&id=' + id + '&status=' + (status ? '0' : '1');
    }

    // Print table function
    function printTable() {
        // Create a printable version with proper styling
        var printContents = document.createElement('div');
        printContents.innerHTML = '<h2 class="text-center mb-4">Student Requirements Report</h2>';
        
        // Clone the requirements table
        var requirementsTable = document.querySelector('#requirementsTable').cloneNode(true);
        
        // Apply print-friendly styling
        printContents.appendChild(requirementsTable);
        
        // Store the current body content
        var originalContents = document.body.innerHTML;
        
        // Replace body content with print content
        document.body.innerHTML = `
            <div class="container mt-4">
                <style>
                    @media print {
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        h2 { text-align: center; margin-bottom: 20px; }
                        .no-print { display: none; }
                    }
                </style>
                ${printContents.innerHTML}
                <div class="no-print mt-4 text-center">
                    <button onclick="window.print()" class="btn btn-primary">Print</button>
                    <button onclick="window.location.reload()" class="btn btn-secondary ml-2">Back</button>
                </div>
            </div>
        `;
        
        // Add event listener to restore original content after printing
        window.onafterprint = function() {
            document.body.innerHTML = originalContents;
            // Re-initialize any scripts that were lost
            if (typeof $ !== 'undefined') {
                try {
                    // Check if the table is already initialized as a DataTable
                    if (!$.fn.DataTable.isDataTable('#requirementsTable')) {
                        $('#requirementsTable').DataTable({
                            "order": [[0, "asc"]],
                            "pageLength": 25,
                            "language": {
                                "search": "Search requirements:",
                                "lengthMenu": "Show _MENU_ requirements per page",
                                "info": "Showing _START_ to _END_ of _TOTAL_ requirements",
                                "emptyTable": "No requirements found"
                            },
                            "columnDefs": [
                                { "orderable": false, "targets": 5 }
                            ]
                        });
                    }
                } catch (e) {
                    console.error("DataTable re-initialization error:", e);
                }
            }
        };
    }
    
    // Show error alert
    function showErrorAlert(message) {
        var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
        
        // Add the alert at the top of the page
        $('.container-fluid').prepend(alertHtml);
        
        // Scroll to the top of the page
        window.scrollTo(0, 0);
    }

        // For testing, add a click handler to the test link
    $(document).ready(function() {
        $('#testAjaxLink').on('click', function(e) {
            e.preventDefault();
            console.log('Test link clicked, testing AJAX endpoint');
            $.ajax({
                url: $(this).attr('href'),
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Test AJAX response:', response);
                    alert('AJAX test successful. Check console for details.');
                },
                error: function(xhr, status, error) {
                    console.error('Test AJAX Error:', xhr.responseText);
                    console.error('Status:', status);
                    console.error('Error:', error);
                    alert('AJAX test failed. Check console for details.');
                }
            });
        });
    });

    // Function to filter students based on selected criteria
    function filterStudents() {
        var searchText = $("#studentSearch").val().toLowerCase();
        var gradeFilter = $("#gradeFilter").val().toLowerCase();
        var sectionFilter = $("#sectionFilter").val().toLowerCase();
        var strandFilter = $("#strandFilter").val().toLowerCase();
        var statusFilter = $("#statusFilter").val().toLowerCase();
        var requirementsFilter = $("#requirementsFilter").val().toLowerCase();
        
        console.log("Applying filters:", {
            search: searchText,
            grade: gradeFilter,
            section: sectionFilter,
            strand: strandFilter,
            status: statusFilter,
            requirements: requirementsFilter
        });
        
        var totalStudents = <?php echo count($students); ?>;
        var visibleStudents = 0;
        
        // Update visual feedback for active filters
        updateFilterVisualFeedback();
        
        // Loop through all students
        $("#studentList .list-group-item").each(function() {
            var $student = $(this);
            var studentName = $student.find('.fw-bold').text().toLowerCase();
            var studentGrade = $student.data('grade') ? $student.data('grade').toString().toLowerCase() : '';
            var studentSection = $student.data('section') ? $student.data('section').toString().toLowerCase() : '';
            var studentStrand = $student.data('strand') ? $student.data('strand').toString().toLowerCase() : '';
            var studentStatus = $student.data('status') ? $student.data('status').toString().toLowerCase() : '';
            var requirementsStatus = $student.data('requirements');
            
            // Debug log for first student
            if (visibleStudents === 0) {
                console.log("First student data:", {
                    name: studentName,
                    grade: studentGrade,
                    section: studentSection,
                    strand: studentStrand,
                    status: studentStatus,
                    requirements: requirementsStatus
                });
            }
            
            // Match criteria
            var matchesSearch = !searchText || studentName.includes(searchText);
            var matchesGrade = !gradeFilter || studentGrade === gradeFilter || studentGrade.includes(gradeFilter);
            var matchesSection = !sectionFilter || studentSection === sectionFilter;
            var matchesStrand = !strandFilter || studentStrand === strandFilter;
            var matchesStatus = !statusFilter || studentStatus === statusFilter;
            
            var matchesRequirements = true;
                if (requirementsFilter === 'complete') {
                    matchesRequirements = (requirementsStatus == 1);
                } else if (requirementsFilter === 'incomplete') {
                    matchesRequirements = (requirementsStatus == 0);
            }
            
            // Show or hide based on all filters
            if (matchesSearch && matchesGrade && matchesSection && matchesStrand && matchesStatus && matchesRequirements) {
                $student.show();
                visibleStudents++;
            } else {
                $student.hide();
            }
        });
        
        // Update the counter
        updateStudentCounter(visibleStudents, totalStudents);
        
        // Show a message if no students match the filters
        if (visibleStudents === 0) {
            if ($("#noMatchingStudents").length === 0) {
                $("#studentList").append('<div id="noMatchingStudents" class="alert alert-info mt-3">No students match the selected filters. Try adjusting your filters.</div>');
            }
        } else {
            $("#noMatchingStudents").remove();
        }
    }
    
    // Function to update visual feedback for active filters
    function updateFilterVisualFeedback() {
        // Check if any filters are active
        var hasActiveFilters = false;
        
        // Check each filter and update visual feedback
        $("#gradeFilter, #sectionFilter, #strandFilter, #statusFilter, #requirementsFilter").each(function() {
            var $filter = $(this);
            if ($filter.val()) {
                $filter.addClass('filter-active');
                hasActiveFilters = true;
            } else {
                $filter.removeClass('filter-active');
            }
        });
        
        // Check search field
        if ($("#studentSearch").val()) {
            $("#studentSearch").addClass('filter-active');
            hasActiveFilters = true;
        } else {
            $("#studentSearch").removeClass('filter-active');
        }
        
        // Update reset button state
        if (hasActiveFilters) {
            $("#resetFilters").addClass('filter-active');
        } else {
            $("#resetFilters").removeClass('filter-active');
        }
    }

    // Function to filter students by search text as user types
    function autoFilterBySearchText(searchText) {
        // Apply all current filters but with the updated search text
        filterStudents();
    }
    
    // Function to update the student counter
    function updateStudentCounter(visible, total) {
        total = total || <?php echo count($students); ?>;
        $("#studentCounter").text("Showing " + visible + " of " + total + " students");
    }

    // Add form submission handler for edit form
    $('#editRequirementForm').on('submit', function(e) {
        console.log('Edit form submitted');
        // The form will be submitted normally, this is just for logging
    });
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?>

<style>
    /* Enhanced visual styling for requirements page */
    /* Main container styling */
    .card {
        border: none;
        border-radius: 0.75rem;
        box-shadow: 0 0.125rem 0.375rem rgba(0, 0, 0, 0.08);
        transition: all 0.3s;
        overflow: hidden;
    }
    
    .card:hover {
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.12);
    }
    
    .card-header {
        background: linear-gradient(135deg, #4e73df, #3f51b5);
        border-bottom: none;
        padding: 1rem 1.25rem;
    }
    
    .card-title {
        font-weight: 600;
        letter-spacing: 0.02rem;
    }
    
    /* Table styling */
    .table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table thead th {
        background-color: #f8f9fc;
        color: #5a5c69;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05rem;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 0.75rem;
    }
    
    .table tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.03);
    }
    
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    /* Status indicators */
    .badge {
        padding: 0.4em 0.65em;
        font-weight: 600;
        border-radius: 0.25rem;
    }
    
    .badge.bg-success {
        background-color: #1cc88a !important;
    }
    
    .bg-info {
        background-color: #36b9cc !important;
    }
    
    /* Icons */
    .fa-check-circle {
        color: #1cc88a;
    }
    
    .fa-times-circle {
        color: #e74a3b;
    }
    
    .fa-info-circle {
        color: #4e73df;
    }
    
    /* Buttons */
    .btn {
        border-radius: 0.35rem;
        padding: 0.375rem 0.75rem;
        font-weight: 500;
        letter-spacing: 0.02rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.08);
        transition: all 0.2s;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.12);
    }
    
    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    
    .btn-primary:hover {
        background-color: #4262c5;
        border-color: #3d5bbf;
    }
    
    .btn-outline-secondary {
        color: #6c757d;
        border-color: #6c757d;
    }
    
    .btn-outline-secondary:hover {
        color: #fff;
        background-color: #6c757d;
        border-color: #6c757d;
    }
    
    /* Student list styling */
    .list-group-item {
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
    }
    
    .list-group-item:hover {
        background-color: rgba(78, 115, 223, 0.05);
        border-left-color: #4e73df;
    }
    
    .list-group-item.requirements-complete {
        border-left-color: #1cc88a;
        background-color: rgba(28, 200, 138, 0.05);
    }
    
    .list-group-item.requirements-complete:hover {
        background-color: rgba(28, 200, 138, 0.1);
    }
    
    .list-group-item .text-success {
        color: #1cc88a !important;
        font-weight: 600;
    }
    
    .list-group-item .text-danger {
        color: #e74a3b !important;
        font-weight: 600;
    }
    
    /* Active filter styling */
    .filter-active {
        border-color: #4e73df;
        background-color: rgba(78, 115, 223, 0.1);
    }
    
    /* Student counter styling */
    #studentCounter {
        background-color: #f8f9fc;
        border-radius: 0.25rem;
        padding: 0.25rem;
        margin-top: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    /* Filter reset button animation */
    #resetFilters.filter-active {
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(78, 115, 223, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(78, 115, 223, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(78, 115, 223, 0);
        }
    }
</style>

<script>
    // Initialize DataTable for requirements table
    $(document).ready(function() {
        try {
            if ($.fn.DataTable.isDataTable('#requirementsTable')) {
                $('#requirementsTable').DataTable().destroy();
            }
            
            if ($('#requirementsTable').length) {
                $('#requirementsTable').DataTable({
                    "order": [[0, "asc"]],
                    "pageLength": 25,
                    "language": {
                        "search": "Search requirements:",
                        "lengthMenu": "Show _MENU_ requirements per page",
                        "info": "Showing _START_ to _END_ of _TOTAL_ requirements",
                        "emptyTable": "No requirements found"
                    },
                    "columnDefs": [
                        { "orderable": false, "targets": 5 }
                    ]
                });
            }
            
            // Initialize student list
            initializeStudentList();
            
            // Add event handlers for real-time filtering
            $('#studentSearch').on('keyup', function() {
                filterStudents();
            });
            
            // Add refresh button functionality
            $('#refreshTable').on('click', function() {
                // Show loading spinner on the button
                const $button = $(this);
                const originalHtml = $button.html();
                $button.html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
                $button.prop('disabled', true);
                
                // Refresh the requirements table without reloading the page
                refreshRequirementsTable(function() {
                    // Restore button state after refresh
                    $button.html(originalHtml);
                    $button.prop('disabled', false);
                });
            });
            
            // Enhance form submission with better error handling
            $('#addRequirementForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                let isValid = true;
                const requiredFields = ['requirementName', 'requirementType', 'requiredFor'];
                
                requiredFields.forEach(field => {
                    const $field = $('#' + field);
                    if (!$field.val()) {
                        isValid = false;
                        $field.addClass('is-invalid');
                    } else {
                        $field.removeClass('is-invalid');
                    }
                });
                
                if (!isValid) {
                    // Show validation error
                    const alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        'Please fill in all required fields.' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>';
                
                    // Add the alert at the top of the modal
                    $('#addRequirementModal .modal-body').prepend(alertHtml);
                    return;
                }
                
                // Submit the form with AJAX
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    beforeSend: function() {
                        // Show loading state
                        $('#addRequirementModal button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> Adding...');
                        $('#addRequirementModal button[type="submit"]').prop('disabled', true);
                    },
                    success: function(response) {
                        // Close the modal
                        $('#addRequirementModal').modal('hide');
                        
                        // Reset the form
                        $('#addRequirementForm')[0].reset();
                
                        // Show success message
                        const alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            'Requirement added successfully!' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>';
                        
                        // Add the alert at the top of the page
                        $('.container-fluid').prepend(alertHtml);
                        
                        // Refresh the requirements table without reloading the page
                        refreshRequirementsTable();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error submitting form:', error);
                        console.error('Response:', xhr.responseText);
            
                        // Show error message
                        const alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            'Error adding requirement. Please try again.' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>';
                        
                        // Add the alert at the top of the modal
                        $('#addRequirementModal .modal-body').prepend(alertHtml);
                    },
                    complete: function() {
                        // Reset button state
                        $('#addRequirementModal button[type="submit"]').html('<i class="fas fa-plus-circle me-1"></i> Add Requirement');
                        $('#addRequirementModal button[type="submit"]').prop('disabled', false);
                    }
                });
            });
        } catch (e) {
            console.error("DataTable initialization error:", e);
        }
    });
    
    // Function to initialize student list
    function initializeStudentList() {
        // Add hover effect to student list items
        $("#studentList .list-group-item").hover(
            function() {
                $(this).addClass("bg-light");
            },
            function() {
                $(this).removeClass("bg-light");
            }
        );
        
        // Highlight complete/incomplete requirements
        $("#studentList .list-group-item").each(function() {
            var $student = $(this);
            var requirementsStatus = $student.data('requirements');
            
            if (requirementsStatus == 1) {
                $student.addClass('requirements-complete');
                // Add a subtle green left border
                $student.css('border-left', '4px solid #1cc88a');
            } else {
                $student.addClass('requirements-incomplete');
                // Add a subtle red left border
                $student.css('border-left', '4px solid #e74a3b');
            }
        });
        
        // Log the first student's data for debugging
        if ($("#studentList .list-group-item").length > 0) {
            var $firstStudent = $("#studentList .list-group-item").first();
            console.log("First student data attributes:", {
                name: $firstStudent.find('.fw-bold').text(),
                grade: $firstStudent.data('grade'),
                section: $firstStudent.data('section'),
                strand: $firstStudent.data('strand'),
                status: $firstStudent.data('status'),
                requirements: $firstStudent.data('requirements')
            });
        }
        
        // Populate filter options based on actual data
        populateFilterOptions();
    }
    
    // Function to populate filter options based on actual data
    function populateFilterOptions() {
        // Get unique grade levels from student data
        var gradeLevels = [];
        var sections = [];
        var strands = [];
        var statuses = [];
        
        $("#studentList .list-group-item").each(function() {
            var $student = $(this);
            
            // Collect grade levels
            var grade = $student.data('grade');
            if (grade && gradeLevels.indexOf(grade) === -1) {
                gradeLevels.push(grade);
            }
            
            // Collect sections
            var section = $student.data('section');
            if (section && sections.indexOf(section) === -1) {
                sections.push(section);
            }
            
            // Collect strands
            var strand = $student.data('strand');
            if (strand && strands.indexOf(strand) === -1) {
                strands.push(strand);
            }
            
            // Collect statuses
            var status = $student.data('status');
            if (status && statuses.indexOf(status) === -1) {
                statuses.push(status);
            }
        });
        
        // Sort collected values
        gradeLevels.sort();
        sections.sort();
        strands.sort();
        statuses.sort();
        
        console.log("Filter options:", {
            grades: gradeLevels,
            sections: sections,
            strands: strands,
            statuses: statuses
        });
        
        // Update grade filter dropdown
        var $gradeFilter = $("#gradeFilter");
        $gradeFilter.find('option:not(:first)').remove(); // Keep "All Grades" option
        
        // Add options based on actual data
        $.each(gradeLevels, function(index, grade) {
            var displayText = grade.charAt(0).toUpperCase() + grade.slice(1); // Capitalize first letter
            $gradeFilter.append($('<option></option>').val(grade).text(displayText));
        });
    }

    // Function to debug student data attributes
    function debugStudentData() {
        console.log('Debugging student data attributes:');
        var studentData = [];
        
        $("#studentList .list-group-item").each(function(index) {
            var $student = $(this);
            studentData.push({
                name: $student.find('.fw-bold').text(),
                grade: $student.data('grade'),
                section: $student.data('section'),
                strand: $student.data('strand'),
                status: $student.data('status'),
                requirements: $student.data('requirements'),
                visible: $student.is(':visible')
            });
        });
        
        console.table(studentData);
        return studentData;
    }
    
    // Function to refresh the requirements table without reloading the page
    function refreshRequirementsTable(callback) {
        console.log('Refreshing requirements table...');
        
        // Show loading indicator
        const loadingHtml = '<div id="tableLoadingIndicator" class="text-center my-3"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading requirements...</p></div>';
        $('#requirementsTable').closest('.table-responsive').append(loadingHtml);
        
        // Fetch updated requirements data via AJAX
        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            data: { 
                ajax_refresh: true,
                timestamp: Date.now() // Prevent caching
            },
            success: function(response) {
                // Extract the requirements table HTML from the response
                const parser = new DOMParser();
                const htmlDoc = parser.parseFromString(response, 'text/html');
                const newTableHtml = $(htmlDoc).find('#requirementsTable').html();
                
                // Update the table content
                if (newTableHtml) {
                    // Destroy existing DataTable if it exists
                    if ($.fn.DataTable.isDataTable('#requirementsTable')) {
                        $('#requirementsTable').DataTable().destroy();
                    }
                    
                    // Replace table HTML
                    $('#requirementsTable').html(newTableHtml);
                    
                    // Reinitialize DataTable
                    $('#requirementsTable').DataTable({
                        "order": [[0, "asc"]],
                        "pageLength": 25,
                        "language": {
                            "search": "Search requirements:",
                            "lengthMenu": "Show _MENU_ requirements per page",
                            "info": "Showing _START_ to _END_ of _TOTAL_ requirements",
                            "emptyTable": "No requirements found"
                        },
                        "columnDefs": [
                            { "orderable": false, "targets": 5 }
                        ]
                    });
                    
                    console.log('Requirements table refreshed successfully');
                } else {
                    console.error('Could not find requirements table in response');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error refreshing requirements table:', error);
                
                // Show error message
                const alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    'Error refreshing requirements table. Please try again.' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>';
                
                // Add the alert above the table
                $('#requirementsTable').closest('.card').prepend(alertHtml);
            },
            complete: function() {
                // Remove loading indicator
                $('#tableLoadingIndicator').remove();
                
                // Call callback function if provided
                if (typeof callback === 'function') {
                    callback();
                }
            }
        });
    }
</script>

<!-- Add a hidden test link for debugging -->
<div style="position: fixed; bottom: 10px; right: 10px; z-index: 9999;">
    <a href="<?php echo $relative_path; ?>modules/registrar/process_requirement.php?action=get&id=1" target="_blank" id="testAjaxLink" class="btn btn-sm btn-info">Test AJAX</a>
</div>

<script>
// For testing, add a click handler to the test link
$(document).ready(function() {
    // Check for success message and refresh the page if needed
    const urlParams = new URLSearchParams(window.location.search);
    const refreshParam = urlParams.get('refresh');
    
    if (refreshParam === '1') {
        console.log('Page loaded with refresh parameter, refreshing requirements table');
        // Remove the refresh parameter from the URL without reloading the page
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
        
        // Force reload the requirements table
        if ($.fn.DataTable.isDataTable('#requirementsTable')) {
            $('#requirementsTable').DataTable().ajax.reload();
        } else {
            location.reload();
        }
    }

    $('#testAjaxLink').on('click', function(e) {
        e.preventDefault();
        console.log('Test link clicked, testing AJAX endpoint');
        $.ajax({
            url: $(this).attr('href'),
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Test AJAX response:', response);
                alert('AJAX test successful. Check console for details.');
            },
            error: function(xhr, status, error) {
                console.error('Test AJAX Error:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                alert('AJAX test failed. Check console for details.');
            }
        });
    });
    
    // Ensure edit buttons work properly
    $(document).on('click', '.edit-requirement-btn', function() {
        var id = $(this).data('id');
        console.log('Edit button clicked for ID:', id);
        editRequirement(id);
    });
    
    // Add refresh button to force reload the requirements table
    $('<button id="refreshRequirementsTable" class="btn btn-sm btn-info ms-2">Refresh Table</button>')
        .insertAfter('#testAjaxLink')
        .on('click', function() {
            location.reload();
    });
    
});
</script>

<style>
    /* Loading indicator styles */
    body.loading {
        position: relative;
        overflow: hidden;
    }
    
    body.loading:before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.7);
        z-index: 9999;
    }
    
    body.loading:after {
        content: '';
        position: fixed;
        top: 50%;
        left: 50%;
        width: 50px;
        height: 50px;
        margin-top: -25px;
        margin-left: -25px;
        border-radius: 50%;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        animation: spin 1s linear infinite;
        z-index: 10000;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
<?php
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
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
        $_SESSION['alert'] = showAlert('Error updating requirements: ' . mysqli_error($conn), 'danger');
    }
    
    // Redirect to prevent form resubmission
    redirect($relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
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
    
    redirect($relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
}

// Check if student_requirements table exists, create if not
$query = "SHOW TABLES LIKE 'student_requirements'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Create the student_requirements table
    $query = "CREATE TABLE student_requirements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        birth_certificate TINYINT(1) DEFAULT 0,
        report_card TINYINT(1) DEFAULT 0,
        good_moral TINYINT(1) DEFAULT 0,
        medical_certificate TINYINT(1) DEFAULT 0,
        id_picture TINYINT(1) DEFAULT 0,
        enrollment_form TINYINT(1) DEFAULT 0,
        parent_id TINYINT(1) DEFAULT 0,
        birth_certificate_file VARCHAR(255) DEFAULT NULL,
        report_card_file VARCHAR(255) DEFAULT NULL,
        good_moral_file VARCHAR(255) DEFAULT NULL,
        medical_certificate_file VARCHAR(255) DEFAULT NULL,
        id_picture_file VARCHAR(255) DEFAULT NULL,
        enrollment_form_file VARCHAR(255) DEFAULT NULL,
        parent_id_file VARCHAR(255) DEFAULT NULL,
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Error creating requirements table: ' . mysqli_error($conn), 'danger');
    }
    
    // Create directory for document uploads if it doesn't exist
    $upload_dir = $relative_path . 'uploads/requirements';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
} else {
    // Check if all file columns exist in the student_requirements table
    $file_columns = [
        'birth_certificate_file', 'report_card_file', 'good_moral_file', 
        'medical_certificate_file', 'id_picture_file', 'enrollment_form_file', 'parent_id_file'
    ];
    
    foreach ($file_columns as $column) {
        $check_column_query = "SHOW COLUMNS FROM student_requirements LIKE '$column'";
        $column_result = mysqli_query($conn, $check_column_query);
        
        if (mysqli_num_rows($column_result) == 0) {
            // Column doesn't exist, add it
            $alter_query = "ALTER TABLE student_requirements ADD COLUMN $column VARCHAR(255) DEFAULT NULL";
            if (mysqli_query($conn, $alter_query)) {
                $_SESSION['alert'] = showAlert("Added missing column '$column' to student_requirements table.", 'success');
                logAction($_SESSION['user_id'] ?? 0, 'UPDATE', "Added missing column '$column' to student_requirements table");
            } else {
                $_SESSION['alert'] = showAlert("Failed to add column '$column': " . mysqli_error($conn), 'danger');
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
$query = "SELECT id, lrn, first_name, last_name, grade_level, section, enrollment_status 
          FROM students 
          ORDER BY last_name, first_name";
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
    $query = "SELECT id, lrn, first_name, last_name, grade_level, section, enrollment_status 
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
    // Check if TCPDF is installed
    if (!function_exists('is_tcpdf_installed') || !is_tcpdf_installed()) {
        $_SESSION['alert'] = showAlert('TCPDF is not installed. Please install it first.', 'danger');
        redirect($relative_path . 'modules/registrar/requirements.php');
    }
    
    // Include TCPDF wrapper
    require_once $relative_path . 'includes/tcpdf/tcpdf_wrapper.php';
    
    // Create new PDF document
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    
    // Set document information
    $pdf->SetCreator('LocalEnroll Pro');
    $pdf->SetAuthor('LocalEnroll Pro');
    $pdf->SetTitle('Student Requirements Report');
    $pdf->SetSubject('Student Requirements Report');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'Student Requirements Report', 'Generated on: ' . date('Y-m-d H:i:s'));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(['helvetica', '', 10]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont('courier');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(true, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Get all students with their requirements
    $query = "SELECT s.id, s.lrn, s.first_name, s.last_name, s.grade_level, s.section, 
              s.enrollment_status, r.*
              FROM students s
              LEFT JOIN student_requirements r ON s.id = r.student_id
              ORDER BY s.grade_level, s.section, s.last_name, s.first_name";
    $result = mysqli_query($conn, $query);
    
    // Build the HTML table
    $html = '<h1>Student Requirements Report</h1>';
    $html .= '<table border="1" cellpadding="5">
        <thead>
            <tr style="background-color: #f5f5f5; font-weight: bold;">
                <th>LRN</th>
                <th>Name</th>
                <th>Grade & Section</th>
                <th>Status</th>';
    
    foreach ($requirement_types as $key => $label) {
        $html .= '<th>' . $label . '</th>';
    }
    
    $html .= '
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>';
    
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '<tr>
            <td>' . htmlspecialchars($row['lrn']) . '</td>
            <td>' . htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) . '</td>
            <td>' . htmlspecialchars($row['grade_level'] . ' - ' . $row['section']) . '</td>
            <td>' . htmlspecialchars(ucfirst($row['enrollment_status'])) . '</td>';
        
        foreach ($requirement_types as $key => $label) {
            $status = isset($row[$key]) && $row[$key] ? '✓' : '✗';
            $color = isset($row[$key]) && $row[$key] ? 'green' : 'red';
            $html .= '<td style="text-align: center; color: ' . $color . ';">' . $status . '</td>';
        }
        
        $html .= '<td>' . htmlspecialchars($row['remarks'] ?? '') . '</td>
            </tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output('student_requirements_report.pdf', 'I');
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
        redirect($relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
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
        
        redirect($relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
    } else {
        $_SESSION['alert'] = showAlert('No files selected for upload.', 'warning');
        redirect($relative_path . 'modules/registrar/requirements.php?student_id=' . $student_id);
    }
}
?>

<?php if (isset($_SESSION['alert'])) {
    echo $_SESSION['alert'];
    unset($_SESSION['alert']);
} ?>

<div class="container-fluid fade-in py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white card-title">Requirements Management</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-white"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Options:</div>
                            <a class="dropdown-item" href="<?php echo $relative_path; ?>modules/registrar/requirements.php?export=excel">
                                <i class="fas fa-file-excel fa-sm fa-fw mr-2 text-gray-400"></i> Export to Excel
                            </a>
                            <a class="dropdown-item" href="<?php echo $relative_path; ?>modules/registrar/requirements.php?export=pdf">
                                <i class="fas fa-file-pdf fa-sm fa-fw mr-2 text-gray-400"></i> Export to PDF
                            </a>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="printTable()">
                                <i class="fas fa-print fa-sm fa-fw mr-2 text-gray-400"></i> Print List
                            </a>
                        </div>
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
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#helpModal">
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
                                // Get requirements list from database - example query, adjust as needed
                                $query = "SELECT * FROM requirements ORDER BY id";
                                $result = mysqli_query($conn, $query);
                                
                                if ($result && mysqli_num_rows($result) > 0) {
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
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editRequirement(' . $row['id'] . ')">
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
        </div>
    </div>
    
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
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Student List</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($students as $student): ?>
                            <a href="<?php echo $relative_path; ?>modules/registrar/requirements.php?student_id=<?php echo $student['id']; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></div>
                                    <small class="text-muted">
                                        Grade: <?php echo htmlspecialchars($student['grade_level'] . ' - ' . $student['section']); ?>
                                    </small>
                                </div>
                                <span class="badge <?php echo $student['enrollment_status'] === 'enrolled' ? 'bg-success' : 'bg-warning'; ?> rounded-pill">
                                    <?php echo ucfirst(htmlspecialchars($student['enrollment_status'])); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Student Requirements Status</h6>
                </div>
                <div class="card-body">
                    <div class="text-center py-5">
                        <img src="<?php echo $relative_path; ?>assets/img/undraw_file_manager.svg" alt="Select Student" style="max-width: 200px; margin-bottom: 20px;">
                        <h5>Select a student from the list</h5>
                        <p class="text-muted">Click on a student from the list to view and manage their requirements.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                <form id="addRequirementForm" method="post" action="process_requirement.php">
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
                <form id="uploadRequirementsForm" method="post" action="process_requirement.php" enctype="multipart/form-data">
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

<!-- JavaScript to enhance the functionality -->
<script>
    // Initialize DataTables for better table management
    $(document).ready(function() {
        try {
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
            
            // Update form action with correct paths
            $('#addRequirementForm').attr('action', '<?php echo $relative_path; ?>modules/registrar/process_requirement.php');
            $('#editRequirementForm').attr('action', '<?php echo $relative_path; ?>modules/registrar/process_requirement.php');
            $('#uploadRequirementsForm').attr('action', '<?php echo $relative_path; ?>modules/registrar/process_requirement.php');
        } catch (e) {
            console.error("DataTable initialization error:", e);
        }
    });

    // Function to populate edit modal with requirement data
    function editRequirement(id) {
        // Debug the call
        console.log('Edit requirement called for ID:', id);
        
        // Fetch requirement data via AJAX
        $.ajax({
            url: '<?php echo $relative_path; ?>modules/registrar/ajax_requirements.php',
            type: 'GET',
            data: { action: 'get_requirement', id: id },
            dataType: 'json',
            beforeSend: function() {
                // Clear any previous error messages
                console.log('Fetching requirement data for ID: ' + id);
                // Show loading indicator
                $('#editModalLoader').show();
                $('#editModalContent').hide();
            },
            success: function(response) {
                console.log('Response received:', response);
                
                if (response && response.status === 'success') {
                    var requirement = response.data;
                    
                    // Populate the form fields
                    $('#editRequirementId').val(requirement.id);
                    $('#editRequirementName').val(requirement.name);
                    $('#editRequirementType').val(requirement.type);
                    $('#editRequiredFor').val(requirement.program);
                    $('#editRequirementDescription').val(requirement.description);
                    $('#editIsRequired').prop('checked', requirement.is_required == 1);
                    
                    // Show the modal content
                    $('#editModalLoader').hide();
                    $('#editModalContent').show();
                    
                    // Show the modal
                    $('#editRequirementModal').modal('show');
                } else {
                    $('#editModalLoader').hide();
                    showErrorAlert('Error: ' + (response ? response.message : 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                $('#editModalLoader').hide();
                console.error('AJAX Error:', xhr.responseText);
                console.error('Status:', status);
                console.error('Error:', error);
                showErrorAlert('Error fetching requirement data. Please try again.');
            }
        });
    }

    // Function to confirm requirement deletion
    function deleteRequirement(id) {
        if (confirm('Are you sure you want to delete this requirement? This action cannot be undone.')) {
            window.location.href = '<?php echo $relative_path; ?>modules/registrar/process_requirement.php?action=delete&id=' + id;
        }
    }

    // Function to toggle requirement status
    function toggleRequirementStatus(id, status) {
        window.location.href = '<?php echo $relative_path; ?>modules/registrar/process_requirement.php?action=toggle_status&id=' + id + '&status=' + (status ? '0' : '1');
    }

    // Print table function
    function printTable() {
        window.print();
    }
    
    // Show error alert
    function showErrorAlert(message) {
        var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
        
        // Add the alert at the top of the page
        $('.container-fluid.fade-in').prepend(alertHtml);
        
        // Scroll to the top of the page
        window.scrollTo(0, 0);
    }
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
        border-left: none;
        border-right: none;
        padding: 0.75rem 1.25rem;
        transition: all 0.2s;
    }
    
    .list-group-item.active {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    
    .list-group-item:hover:not(.active) {
        background-color: #f8f9fc;
    }
    
    /* Form elements */
    .form-control, .form-select {
        border-radius: 0.35rem;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d3e2;
        transition: border-color 0.2s;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
    }
    
    .form-label {
        font-weight: 500;
        color: #5a5c69;
    }
    
    /* Form switches */
    .form-switch .form-check-input {
        width: 2.5em;
        height: 1.25em;
    }
    
    .form-check-input:checked {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    
    /* Search bar */
    .dataTables_filter input {
        border-radius: 0.35rem;
        border: 1px solid #d1d3e2;
        padding: 0.5rem 0.75rem;
        width: 250px !important;
    }
    
    /* Pagination */
    .pagination .page-item.active .page-link {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    
    .pagination .page-link {
        color: #4e73df;
    }
    
    /* Modal enhancements */
    .modal-content {
        border: none;
        border-radius: 0.75rem;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #4e73df, #3f51b5);
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        border-bottom: none;
    }
    
    .modal-footer {
        border-top: 1px solid #eaecf4;
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }
    
    /* Requirements progress display */
    .requirements-progress {
        height: 0.5rem;
        border-radius: 0.25rem;
        margin-top: 0.5rem;
        background-color: #eaecf4;
    }
    
    .requirements-progress-bar {
        height: 100%;
        border-radius: 0.25rem;
        background-color: #4e73df;
    }
    
    /* Improved spacing */
    .mb-4 {
        margin-bottom: 1.5rem !important;
    }
    
    .py-2 {
        padding-top: 0.5rem !important;
        padding-bottom: 0.5rem !important;
    }
    
    /* Stop modal flickering */
    .modal.fade.show {
        display: block !important;
        transform: none !important;
        transition: none !important;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1050;
        overflow-x: hidden;
        overflow-y: auto;
        pointer-events: auto;
    }
    
    .modal-dialog {
        margin: 1.75rem auto;
        max-width: 800px;
        pointer-events: all;
        position: relative;
        transform: none !important;
        transition: none !important;
    }
    
    .modal-content {
        background-color: #fff;
        border: 0;
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        pointer-events: auto;
    }
    
    .modal-backdrop {
        pointer-events: none !important;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
    }
    
    /* Disable transitions on hover */
    .modal:hover, 
    .modal:hover .modal-dialog,
    .modal:hover .modal-content,
    .modal-dialog:hover,
    .modal-content:hover {
        transition: none !important;
        transform: none !important;
    }
    
    /* Bootstrap switch style improvements */
    .custom-switch {
        width: 3em !important;
        height: 1.5em !important;
        margin: 0 !important;
        cursor: pointer;
    }
</style> 
<?php
$page = 'view_student';
$title = 'View Student Details';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has access to this page
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have access to this page.', 'danger');
    redirect($relative_path . 'index.php');
    exit;
}

// Check if student ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['alert'] = showAlert('Invalid student ID.', 'danger');
    redirect($relative_path . 'modules/registrar/students.php');
    exit;
}

$student_id = (int) $_GET['id'];

// Get student information
$query = "SELECT * FROM students WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = showAlert('Student not found.', 'danger');
    redirect($relative_path . 'modules/registrar/students.php');
    exit;
}

$student = mysqli_fetch_assoc($result);

// Get SHS details if grade level is 11 or 12
$shs_details = null;
if (in_array($student['grade_level'], ['Grade 11', 'Grade 12'])) {
    $shs_query = "SELECT * FROM senior_highschool_details WHERE student_id = ?";
    $shs_stmt = mysqli_prepare($conn, $shs_query);
    mysqli_stmt_bind_param($shs_stmt, "i", $student_id);
    mysqli_stmt_execute($shs_stmt);
    $shs_result = mysqli_stmt_get_result($shs_stmt);
    
    if (mysqli_num_rows($shs_result) > 0) {
        $shs_details = mysqli_fetch_assoc($shs_result);
    }
}

// Get student requirements
$req_query = "SELECT * FROM student_requirements WHERE student_id = ?";
$req_stmt = mysqli_prepare($conn, $req_query);
mysqli_stmt_bind_param($req_stmt, "i", $student_id);
mysqli_stmt_execute($req_stmt);
$req_result = mysqli_stmt_get_result($req_stmt);
$requirements = mysqli_fetch_assoc($req_result);

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

// Calculate requirements completion
$completed = 0;
$total = count($requirement_types);

if ($requirements) {
    foreach ($requirement_types as $key => $label) {
        if (isset($requirements[$key]) && $requirements[$key] == 1) {
            $completed++;
        }
    }
}
$percentage = $total > 0 ? ($completed / $total) * 100 : 0;

// Check if print mode is requested
$print_mode = isset($_GET['print']) && $_GET['print'] == 'true';

// If print mode, use a simplified layout
if ($print_mode) {
    // Include only necessary CSS
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Student Information - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            @page {
                size: letter portrait;
                margin: 0.3cm;
            }
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                margin: 0;
                padding: 5px;
                font-size: 9pt;
                color: #000;
                line-height: 1.2;
                background-color: #fff;
            }
            .header {
                text-align: center;
                margin-bottom: 8px;
                border-bottom: 2px solid #3a5998;
                padding-bottom: 5px;
                background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
                border-radius: 5px 5px 0 0;
                padding-top: 5px;
            }
            .school-logo {
                max-width: 50px;
                margin-bottom: 2px;
                display: inline-block;
                border-radius: 50%;
                box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            }
            .school-name {
                font-size: 12pt;
                font-weight: bold;
                margin-bottom: 0;
                color: #3a5998;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .document-title {
                font-size: 11pt;
                margin-bottom: 0;
                color: #555;
                font-weight: 600;
            }
            .student-info {
                margin-bottom: 8px;
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                background-color: #f8f9fa;
                padding: 5px;
                border-radius: 5px;
                border-left: 3px solid #3a5998;
            }
            .student-info-text {
                flex: 1;
            }
            .student-photo {
                max-width: 70px;
                border: 1px solid #ddd;
                padding: 2px;
                margin-left: 10px;
                background-color: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .section {
                margin-bottom: 8px;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                overflow: hidden;
            }
            .section-title {
                font-weight: bold;
                padding: 3px 8px;
                margin-bottom: 3px;
                background-color: #3a5998;
                color: white;
                font-size: 9pt;
                border-radius: 3px 3px 0 0;
            }
            .section-content {
                padding: 3px 5px;
            }
            .row {
                display: flex;
                margin-bottom: 3px;
                flex-wrap: wrap;
            }
            .col {
                flex: 1;
                min-width: 180px;
                margin-bottom: 0;
                padding-right: 5px;
            }
            .label {
                font-weight: bold;
                margin-right: 3px;
                color: #555;
            }
            .value {
                color: #000;
            }
            .status {
                padding: 1px 5px;
                border-radius: 3px;
                display: inline-block;
                font-weight: bold;
                font-size: 8pt;
                text-transform: uppercase;
            }
            .status-enrolled {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .status-pending {
                background-color: #fff3cd;
                color: #856404;
                border: 1px solid #ffeeba;
            }
            .status-withdrawn {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .compact-table {
                display: flex;
                flex-wrap: wrap;
                background-color: #f8f9fa;
                border-radius: 3px;
                padding: 3px;
            }
            .compact-table-item {
                width: 50%;
                padding: 2px 5px;
                font-size: 8pt;
                display: flex;
                align-items: center;
            }
            .compact-table-item i {
                margin-right: 3px;
                font-size: 10px;
            }
            .submitted {
                color: #28a745;
            }
            .not-submitted {
                color: #dc3545;
            }
            .footer {
                margin-top: 8px;
                text-align: center;
                font-size: 7pt;
                color: #666;
                border-top: 1px solid #dee2e6;
                padding-top: 3px;
                background-color: #f8f9fa;
                border-radius: 0 0 5px 5px;
                padding: 3px;
            }
            .signature-area {
                display: flex;
                justify-content: space-between;
                margin-top: 10px;
                margin-bottom: 8px;
            }
            .signature-box {
                text-align: center;
                width: 32%;
            }
            .signature-line {
                border-top: 1px solid #000;
                width: 100%;
                margin-top: 20px;
                margin-bottom: 2px;
            }
            .signature-title {
                font-weight: bold;
                font-size: 8pt;
            }
                .no-print {
                display: block;
            }
            h2 {
                margin-top: 0;
                margin-bottom: 3px;
                font-size: 11pt;
                color: #333;
            }
            p {
                margin: 0 0 2px 0;
            }
            .student-lrn {
                font-size: 9pt;
                color: #6c757d;
                font-weight: normal;
            }
            .info-box {
                display: inline-block;
                padding: 1px 5px;
                background-color: #e9ecef;
                border-radius: 3px;
                margin-right: 5px;
                border: 1px solid #dee2e6;
                }
            @media print {
                html, body {
                    width: 100%;
                    height: 100%;
                    margin: 0;
                    padding: 0;
                }
                body {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .no-print {
                    display: none !important;
                }
                .section-title {
                    background-color: #3a5998 !important;
                    color: white !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .header {
                    background: linear-gradient(to bottom, #f8f9fa, #e9ecef) !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .student-info {
                    background-color: #f8f9fa !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .compact-table {
                    background-color: #f8f9fa !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .footer {
                    background-color: #f8f9fa !important;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .status-enrolled, .status-pending, .status-withdrawn {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="<?php echo $relative_path; ?>assets/images/logo.jpg" alt="School Logo" class="school-logo">
            <div class="school-name">THE KRISLIZZ INTERNATIONAL ACADEMY INC.</div>
            <div class="document-title">STUDENT INFORMATION SHEET</div>
        </div>
        
        <div class="student-info">
            <div class="student-info-text">
            <h2><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . (!empty($student['middle_name']) ? ' ' . $student['middle_name'] : '')); ?></h2>
            <div class="row">
                <div class="col">
                        <span class="label">LRN:</span> 
                        <span class="student-lrn"><?php echo htmlspecialchars($student['lrn']); ?></span>
                </div>
                <div class="col">
                    <span class="label">Status:</span> 
                    <span class="status status-<?php echo strtolower($student['enrollment_status']); ?>">
                        <?php echo ucfirst($student['enrollment_status']); ?>
                    </span>
                </div>
            </div>
            </div>
            <?php if (!empty($student['photo'])): ?>
                <img src="<?php echo $relative_path . $student['photo']; ?>" alt="Student Photo" class="student-photo">
            <?php endif; ?>
        </div>
        
        <div class="section">
            <div class="section-title">Academic Information</div>
            <div class="section-content">
            <div class="row">
                <div class="col">
                        <span class="label">Grade Level:</span> 
                        <span class="info-box"><?php echo htmlspecialchars($student['grade_level']); ?></span>
                </div>
                <div class="col">
                        <span class="label">Section:</span> 
                        <span class="info-box"><?php echo htmlspecialchars($student['section']); ?></span>
            </div>
            <?php if ($shs_details): ?>
                <div class="col">
                        <span class="label">Track:</span> 
                        <span class="info-box"><?php echo htmlspecialchars($shs_details['track']); ?></span>
                </div>
                <div class="col">
                        <span class="label">Strand:</span> 
                        <span class="info-box"><?php echo htmlspecialchars($shs_details['strand']); ?></span>
                </div>
                    <?php endif; ?>
            </div>
                <?php if ($shs_details): ?>
            <div class="row">
                <div class="col">
                        <span class="label">Semester:</span> 
                        <span class="value"><?php echo htmlspecialchars($shs_details['semester'] ?? 'Not specified'); ?></span>
                </div>
                <div class="col">
                        <span class="label">School Year:</span> 
                        <span class="value"><?php echo htmlspecialchars($shs_details['school_year'] ?? 'Not specified'); ?></span>
                </div>
                    <div class="col">
                        <span class="label">Date Enrolled:</span> 
                        <span class="value"><?php echo isset($student['date_enrolled']) ? date('m/d/Y', strtotime($student['date_enrolled'])) : 'Not recorded'; ?></span>
            </div>
                </div>
                <?php else: ?>
            <div class="row">
                <div class="col">
                    <span class="label">Date Enrolled:</span> 
                        <span class="value"><?php echo isset($student['date_enrolled']) ? date('m/d/Y', strtotime($student['date_enrolled'])) : 'Not recorded'; ?></span>
                </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Personal Information</div>
            <div class="section-content">
            <div class="row">
                <div class="col">
                        <span class="label">Gender:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['gender']); ?></span>
                </div>
                <div class="col">
                        <span class="label">Date of Birth:</span> 
                        <span class="value"><?php echo date('m/d/Y', strtotime($student['dob'])); ?></span>
                </div>
                <div class="col">
                        <span class="label">Religion:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['religion'] ?? 'Not provided'); ?></span>
                </div>
                <div class="col">
                        <span class="label">Contact:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['contact_number']); ?></span>
                </div>
            </div>
            <div class="row">
                    <div class="col" style="min-width: 98%;">
                        <span class="label">Address:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['address']); ?></span>
                </div>
            </div>
            <div class="row">
                    <div class="col" style="min-width: 98%;">
                        <span class="label">Email:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Parent/Guardian Information</div>
            <div class="section-content">
            <div class="row">
                <div class="col">
                        <span class="label">Father's Name:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['father_name'] ?? 'Not provided'); ?></span>
                </div>
                <div class="col">
                        <span class="label">Father's Occupation:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['father_occupation'] ?? 'Not provided'); ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col">
                        <span class="label">Mother's Name:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['mother_name'] ?? 'Not provided'); ?></span>
                </div>
                <div class="col">
                        <span class="label">Mother's Occupation:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['mother_occupation'] ?? 'Not provided'); ?></span>
                </div>
            </div>
            <div class="row">
                <div class="col">
                        <span class="label">Guardian's Name:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['guardian_name'] ?? 'Not provided'); ?></span>
                </div>
                <div class="col">
                        <span class="label">Guardian's Contact:</span> 
                        <span class="value"><?php echo htmlspecialchars($student['guardian_contact'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <?php
            // First get the student's requirements status
            $req_query = "SELECT * FROM student_requirements WHERE student_id = ?";
            $req_stmt = mysqli_prepare($conn, $req_query);
            mysqli_stmt_bind_param($req_stmt, "i", $student_id);
            mysqli_stmt_execute($req_stmt);
            $req_result = mysqli_stmt_get_result($req_stmt);
            $requirements = mysqli_fetch_assoc($req_result);
            
            // Get active requirements (from requirements table first, fallback to student_requirements columns)
            $active_req_query = "SELECT * FROM requirements WHERE is_active = 1";
            $active_req_result = mysqli_query($conn, $active_req_query);
            $requirement_types = [];
            
            if ($active_req_result && mysqli_num_rows($active_req_result) > 0) {
                while ($row = mysqli_fetch_assoc($active_req_result)) {
                    $column_name = str_replace(' ', '_', $row['name']);
                    $column_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $column_name);
                    $column_name = strtolower($column_name);
                    $requirement_types[$column_name] = $row['name'];
                }
            } else {
                // Fallback to student_requirements columns
                $types_query = "SHOW COLUMNS FROM student_requirements WHERE Field NOT IN ('id', 'student_id', 'remarks', 'created_at', 'updated_at') AND Field NOT LIKE '%_file'";
                $types_result = mysqli_query($conn, $types_query);
                
                while ($column = mysqli_fetch_assoc($types_result)) {
                    if (substr($column['Field'], -5) !== '_file') {
                        $display_name = str_replace('_', ' ', $column['Field']);
                        $display_name = ucwords($display_name);
                        $requirement_types[$column['Field']] = $display_name;
                    }
                }
            }
            
            // Calculate completed requirements - THIS IS THE FIXED PART
            $completed = 0;
            $total = count($requirement_types);
            
            if ($requirements) {
                foreach ($requirement_types as $key => $label) {
                    if (isset($requirements[$key]) && $requirements[$key] == 1) {
                        $completed++;
                    }
                }
            }
            ?>
            
            <div class="section-title">Requirements Status (<?php echo $completed; ?> of <?php echo $total; ?> submitted)</div>
            <div class="section-content">
                <div class="compact-table">
                <?php foreach ($requirement_types as $key => $label): ?>
                    <div class="compact-table-item">
                        <?php if ($requirements && isset($requirements[$key]) && $requirements[$key] == 1): ?>
                            <i class="fas fa-check-circle submitted"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle not-submitted"></i>
                        <?php endif; ?>
                        <span class="label"><?php echo htmlspecialchars($label); ?>:</span>
                        <?php if ($requirements && isset($requirements[$key]) && $requirements[$key] == 1): ?>
                            <span class="submitted">Submitted</span>
                        <?php else: ?>
                            <span class="not-submitted">Not Submitted</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            
            <?php if ($requirements && !empty($requirements['remarks'])): ?>
                <div style="margin-top: 3px; font-size: 8pt;">
                    <span class="label">Remarks:</span> 
                    <span class="value"><?php echo htmlspecialchars($requirements['remarks']); ?></span>
            </div>
            <?php endif; ?>
            </div>
        </div>
        
        <div class="signature-area">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Student Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Parent/Guardian Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-title">Registrar Signature</div>
            </div>
        </div>
        
        <div class="footer">
            <p>This document is for official school use only. Not valid without school seal.</p>
            <p>Printed on: <?php echo date('m/d/Y h:i A'); ?> by <?php echo $_SESSION['user']['name'] ?? $_SESSION['username'] ?? 'System User'; ?></p>
        </div>
        
        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="printDocument();" style="padding: 10px 20px; background-color: #3a5998; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                <i class="fas fa-print"></i> Print Document
            </button>
            <a href="?id=<?php echo $student_id; ?>" style="display: inline-block; margin-left: 10px; padding: 10px 20px; background-color: #6c757d; color: white; border-radius: 5px; text-decoration: none; font-size: 14px;">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
        </div>
        
        <script>
            // Function to print document
            function printDocument() {
                window.print();
            }
            
            // Auto-print when page loads
            document.addEventListener('DOMContentLoaded', function() {
                // Short delay to ensure everything is loaded
                setTimeout(function() {
                    window.print();
                }, 1000);
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mt-4">Student Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>modules/registrar/students.php">Students</a></li>
                    <li class="breadcrumb-item active">View Student</li>
    </ol>
            </nav>
        </div>
    </div>
    
    <?php if (isset($_SESSION['alert'])): ?>
        <?php echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Student Information</h5>
                    <div>
                        <a href="?id=<?php echo $student_id; ?>&print=true" class="btn btn-light btn-sm" target="_blank">
                            <i class="fas fa-print me-1"></i> Print
                        </a>
                        <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-light btn-sm ms-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                        <?php if (checkAccess(['admin', 'registrar'])): ?>
                        <a href="<?php echo $relative_path; ?>modules/registrar/edit_student.php?id=<?php echo $student_id; ?>" class="btn btn-warning btn-sm ms-2">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="<?php echo $relative_path; ?>modules/registrar/requirements.php?student_id=<?php echo $student_id; ?>" class="btn btn-info btn-sm ms-2">
                            <i class="fas fa-clipboard-list me-1"></i> Requirements
                        </a>
                        <a href="<?php echo $relative_path; ?>modules/registrar/view_enrollment_history.php?id=<?php echo $student_id; ?>" class="btn btn-success btn-sm ms-2">
                            <i class="fas fa-history me-1"></i> Enrollment History
                        </a>
                        <a href="<?php echo $relative_path; ?>modules/registrar/back_subjects.php?id=<?php echo $student_id; ?>" class="btn btn-danger btn-sm ms-2">
                            <i class="fas fa-exclamation-triangle me-1"></i> Back Subjects
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="mb-3">
                            <?php if (!empty($student['photo'])): ?>
                                    <img src="<?php echo $relative_path . $student['photo']; ?>" alt="Student Photo" class="img-thumbnail" style="max-height: 200px;">
                            <?php else: ?>
                                    <div class="border rounded p-3 bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="fas fa-user fa-5x text-secondary"></i>
                                    </div>
                            <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <?php
                                $status_class = 'bg-secondary';
                                switch ($student['enrollment_status']) {
                                    case 'enrolled':
                                        $status_class = 'bg-success';
                                        break;
                                    case 'pending':
                                        $status_class = 'bg-warning text-dark';
                                        break;
                                    case 'withdrawn':
                                        $status_class = 'bg-danger';
                                        break;
                                }
                                ?>
                                <div class="badge <?php echo $status_class; ?> p-2 fs-6">
                                    <?php echo ucfirst($student['enrollment_status']); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <h3 class="mb-4">
                                <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . (!empty($student['middle_name']) ? ' ' . $student['middle_name'] : '')); ?>
                                <small class="text-muted d-block mt-1">LRN: <?php echo htmlspecialchars($student['lrn']); ?></small>
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Grade Level:</strong></p>
                                        <p><?php echo htmlspecialchars($student['grade_level']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Section:</strong></p>
                                        <p><?php echo htmlspecialchars($student['section']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Gender:</strong></p>
                                        <p><?php echo htmlspecialchars($student['gender']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Date of Birth:</strong></p>
                                        <p><?php echo date('F d, Y', strtotime($student['dob'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Religion:</strong></p>
                                        <p><?php echo htmlspecialchars($student['religion'] ?? 'Not provided'); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Address:</strong></p>
                                        <p><?php echo htmlspecialchars($student['address']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Contact Number:</strong></p>
                                        <p><?php echo htmlspecialchars($student['contact_number']); ?></p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Email:</strong></p>
                                        <p><?php echo htmlspecialchars($student['email'] ?? 'Not provided'); ?></p>
                                    </div>
                                </div>
                            </div>
                                </div>
                            </div>
                            
                    <?php if ($shs_details): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5 class="mb-0">Senior High School Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                <div class="col-md-6">
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Track:</strong></p>
                                                <p class="text-primary"><?php echo htmlspecialchars($shs_details['track']); ?></p>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Strand:</strong></p>
                                                <p><span class="badge bg-primary"><?php echo htmlspecialchars($shs_details['strand']); ?></span></p>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Semester:</strong></p>
                                                <p><?php echo htmlspecialchars($shs_details['semester'] ?? 'Not specified'); ?> Semester</p>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>School Year:</strong></p>
                                                <p><?php echo htmlspecialchars($shs_details['school_year'] ?? 'Not specified'); ?></p>
                                            </div>
                                </div>
                                        
                                <div class="col-md-6">
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Previous School:</strong></p>
                                                <p><?php echo htmlspecialchars($shs_details['previous_school'] ?? 'Not specified'); ?></p>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Previous Track:</strong></p>
                                                <p><?php echo htmlspecialchars($shs_details['previous_track'] ?? 'Not applicable'); ?></p>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Previous Strand:</strong></p>
                                                <p><?php echo htmlspecialchars($shs_details['previous_strand'] ?? 'Not applicable'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Parent/Guardian Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Father's Name:</strong></p>
                                                <p><?php echo htmlspecialchars($student['father_name'] ?? 'Not provided'); ?></p>
                        </div>
                        
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Father's Occupation:</strong></p>
                                                <p><?php echo htmlspecialchars($student['father_occupation'] ?? 'Not provided'); ?></p>
                                            </div>
                        </div>
                        
                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Mother's Name:</strong></p>
                                                <p><?php echo htmlspecialchars($student['mother_name'] ?? 'Not provided'); ?></p>
                        </div>
                        
                                            <div class="mb-3">
                                                <p class="mb-1"><strong>Mother's Occupation:</strong></p>
                                                <p><?php echo htmlspecialchars($student['mother_occupation'] ?? 'Not provided'); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Students
                        </a>
                        <div>
                            <a href="<?php echo $relative_path; ?>modules/registrar/edit_student.php?id=<?php echo $student_id; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Edit Student
                            </a>
                            <a href="?id=<?php echo $student_id; ?>&print=true" class="btn btn-success ms-2" target="_blank">
                                <i class="fas fa-print me-1"></i> Print Student Profile
                            </a>
                            <button type="button" class="btn btn-info ms-2" onclick="exportStudentData()">
                                <i class="fas fa-file-export me-1"></i> Export Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Requirements Summary Section -->
    <div class="row mt-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Requirements Summary</h5>
                    <div>
                        <?php if (checkAccess(['admin'])): ?>
                        <?php endif; ?>
                    <a href="<?php echo $relative_path; ?>modules/registrar/requirements.php?student_id=<?php echo $student_id; ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-clipboard-check me-1"></i> Manage Requirements
                    </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    // Get student requirements
                    $req_query = "SELECT * FROM student_requirements WHERE student_id = ?";
                    $req_stmt = mysqli_prepare($conn, $req_query);
                    mysqli_stmt_bind_param($req_stmt, "i", $student_id);
                    mysqli_stmt_execute($req_stmt);
                    $req_result = mysqli_stmt_get_result($req_stmt);
                    $requirements = mysqli_fetch_assoc($req_result);
                    
                    // Get active requirements from the requirements table
                    $active_req_query = "SELECT * FROM requirements WHERE is_active = 1";
                    $active_req_result = mysqli_query($conn, $active_req_query);
                    $active_requirements = [];
                    
                    if ($active_req_result && mysqli_num_rows($active_req_result) > 0) {
                        while ($row = mysqli_fetch_assoc($active_req_result)) {
                            // Create column name from requirement name
                            $column_name = str_replace(' ', '_', $row['name']);
                            $column_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $column_name);
                            $column_name = strtolower($column_name);
                            
                            $active_requirements[$column_name] = $row['name'];
                        }
                    }
                    
                    // If no active requirements found in the requirements table, fall back to columns in student_requirements
                    if (empty($active_requirements)) {
                        // Get all requirement types from the database
                        $types_query = "SHOW COLUMNS FROM student_requirements WHERE Field NOT IN ('id', 'student_id', 'remarks', 'created_at', 'updated_at') AND Field NOT LIKE '%_file'";
                        $types_result = mysqli_query($conn, $types_query);
                        
                        while ($column = mysqli_fetch_assoc($types_result)) {
                            // Skip columns that end with _file using substr check for PHP 7.x compatibility
                            if (substr($column['Field'], -5) !== '_file') {
                                // Format the field name for display
                                $display_name = str_replace('_', ' ', $column['Field']);
                                $display_name = ucwords($display_name);
                                $active_requirements[$column['Field']] = $display_name;
                            }
                        }
                    }
                    
                    if ($requirements) {
                        $completed = 0;
                        $total = count($active_requirements);
                        
                        foreach ($active_requirements as $key => $label) {
                            if (isset($requirements[$key]) && $requirements[$key] == 1) {
                                $completed++;
                            }
                        }
                        
                        $percentage = $total > 0 ? ($completed / $total) * 100 : 0;
                        ?>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <h6>Completion Status: <?php echo $completed; ?> of <?php echo $total; ?> requirements submitted</h6>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar <?php echo $percentage == 100 ? 'bg-success' : 'bg-info'; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;" 
                                        aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo round($percentage); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($active_requirements as $key => $label): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <?php if (isset($requirements[$key]) && $requirements[$key] == 1): ?>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger me-2"></i>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($label); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (!empty($requirements['remarks'])): ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <strong>Remarks:</strong> <?php echo htmlspecialchars($requirements['remarks']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php } else { ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> No requirements information available for this student.
                            <a href="<?php echo $relative_path; ?>modules/registrar/requirements.php?student_id=<?php echo $student_id; ?>" class="alert-link">
                                Click here to add requirements
                            </a>.
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Debug section - only visible to admin users with debug parameter
    if (checkAccess(['admin']) && isset($_GET['debug']) && $_GET['debug'] == '1'):
    ?>
    <div class="row mt-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">Debug Information</h5>
                </div>
                <div class="card-body">
                    <h6>Active Requirements from Requirements Table:</h6>
                    <pre><?php print_r($active_requirements); ?></pre>
                    
                    <h6>Student Requirements Data:</h6>
                    <pre><?php print_r($requirements); ?></pre>
                    
                    <h6>Student Requirements Table Structure:</h6>
                    <?php
                    $structure_query = "SHOW COLUMNS FROM student_requirements";
                    $structure_result = mysqli_query($conn, $structure_query);
                    echo '<table class="table table-sm table-bordered">';
                    echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
                    echo '<tbody>';
                    while ($column = mysqli_fetch_assoc($structure_result)) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
                        echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
                        echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
                        echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
                        echo '<td>' . htmlspecialchars($column['Default'] ?? 'NULL') . '</td>';
                        echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    ?>
                    
                    <div class="mt-3">
                        <a href="<?php echo $_SERVER['REQUEST_URI']; ?>&refresh=<?php echo time(); ?>" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-1"></i> Refresh Data
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
/**
 * Function to export student data as CSV
 */
function exportStudentData() {
    // Get student data from the page
    const studentName = "<?php echo addslashes($student['first_name'] . ' ' . $student['last_name']); ?>";
    const studentData = {
        id: <?php echo $student_id; ?>,
        lrn: "<?php echo addslashes($student['lrn']); ?>",
        firstName: "<?php echo addslashes($student['first_name']); ?>",
        lastName: "<?php echo addslashes($student['last_name']); ?>",
        middleName: "<?php echo addslashes($student['middle_name'] ?? ''); ?>",
        gradeLevel: "<?php echo addslashes($student['grade_level']); ?>",
        section: "<?php echo addslashes($student['section']); ?>",
        gender: "<?php echo addslashes($student['gender']); ?>",
        dateOfBirth: "<?php echo addslashes($student['dob']); ?>",
        address: "<?php echo addslashes($student['address']); ?>",
        contactNumber: "<?php echo addslashes($student['contact_number']); ?>",
        email: "<?php echo addslashes($student['email'] ?? ''); ?>",
        religion: "<?php echo addslashes($student['religion'] ?? ''); ?>",
        enrollmentStatus: "<?php echo addslashes($student['enrollment_status']); ?>",
        dateEnrolled: "<?php echo addslashes($student['date_enrolled'] ?? ''); ?>",
        fatherName: "<?php echo addslashes($student['father_name'] ?? ''); ?>",
        fatherOccupation: "<?php echo addslashes($student['father_occupation'] ?? ''); ?>",
        motherName: "<?php echo addslashes($student['mother_name'] ?? ''); ?>",
        motherOccupation: "<?php echo addslashes($student['mother_occupation'] ?? ''); ?>",
        guardianName: "<?php echo addslashes($student['guardian_name'] ?? ''); ?>",
        guardianContact: "<?php echo addslashes($student['guardian_contact'] ?? ''); ?>"
    };

    <?php if ($shs_details): ?>
    // Add SHS details
    studentData.track = "<?php echo addslashes($shs_details['track']); ?>";
    studentData.strand = "<?php echo addslashes($shs_details['strand']); ?>";
    studentData.semester = "<?php echo addslashes($shs_details['semester'] ?? ''); ?>";
    studentData.schoolYear = "<?php echo addslashes($shs_details['school_year'] ?? ''); ?>";
    <?php endif; ?>

    // Convert to JSON string
    const dataStr = JSON.stringify(studentData, null, 2);
    
    // Create export options dialog
    const modalHtml = `
        <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">Export Student Data</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Choose export format for <strong>${studentName}'s</strong> data:</p>
                        <div class="d-grid gap-3">
                            <button class="btn btn-primary" onclick="downloadStudentData('json')">
                                <i class="fas fa-file-code me-2"></i> Export as JSON
                            </button>
                            <button class="btn btn-success" onclick="downloadStudentData('csv')">
                                <i class="fas fa-file-csv me-2"></i> Export as CSV
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to document if it doesn't exist
    if (!document.getElementById('exportModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    // Show modal
    const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
    exportModal.show();
}

/**
 * Download student data in the specified format
 */
function downloadStudentData(format) {
    const studentName = "<?php echo addslashes($student['first_name'] . '_' . $student['last_name']); ?>";
    const studentData = {
        id: <?php echo $student_id; ?>,
        lrn: "<?php echo addslashes($student['lrn']); ?>",
        firstName: "<?php echo addslashes($student['first_name']); ?>",
        lastName: "<?php echo addslashes($student['last_name']); ?>",
        middleName: "<?php echo addslashes($student['middle_name'] ?? ''); ?>",
        gradeLevel: "<?php echo addslashes($student['grade_level']); ?>",
        section: "<?php echo addslashes($student['section']); ?>",
        gender: "<?php echo addslashes($student['gender']); ?>",
        dateOfBirth: "<?php echo addslashes($student['dob']); ?>",
        address: "<?php echo addslashes($student['address']); ?>",
        contactNumber: "<?php echo addslashes($student['contact_number']); ?>",
        email: "<?php echo addslashes($student['email'] ?? ''); ?>",
        religion: "<?php echo addslashes($student['religion'] ?? ''); ?>",
        enrollmentStatus: "<?php echo addslashes($student['enrollment_status']); ?>",
        dateEnrolled: "<?php echo addslashes($student['date_enrolled'] ?? ''); ?>",
        fatherName: "<?php echo addslashes($student['father_name'] ?? ''); ?>",
        fatherOccupation: "<?php echo addslashes($student['father_occupation'] ?? ''); ?>",
        motherName: "<?php echo addslashes($student['mother_name'] ?? ''); ?>",
        motherOccupation: "<?php echo addslashes($student['mother_occupation'] ?? ''); ?>",
        guardianName: "<?php echo addslashes($student['guardian_name'] ?? ''); ?>",
        guardianContact: "<?php echo addslashes($student['guardian_contact'] ?? ''); ?>"
    };

    <?php if ($shs_details): ?>
    // Add SHS details
    studentData.track = "<?php echo addslashes($shs_details['track']); ?>";
    studentData.strand = "<?php echo addslashes($shs_details['strand']); ?>";
    studentData.semester = "<?php echo addslashes($shs_details['semester'] ?? ''); ?>";
    studentData.schoolYear = "<?php echo addslashes($shs_details['school_year'] ?? ''); ?>";
    <?php endif; ?>
    
    let content, filename, contentType;
    
    if (format === 'json') {
        content = JSON.stringify(studentData, null, 2);
        filename = `student_${studentName}_${Date.now()}.json`;
        contentType = 'application/json';
    } else if (format === 'csv') {
        // Convert object to CSV
        const headers = Object.keys(studentData).join(',');
        const values = Object.values(studentData).map(value => {
            if (typeof value === 'string') {
                // Escape quotes and wrap in quotes
                return `"${value.replace(/"/g, '""')}"`;
            }
            return value;
        }).join(',');
        
        content = headers + '\n' + values;
        filename = `student_${studentName}_${Date.now()}.csv`;
        contentType = 'text/csv';
    }
    
    // Create download link
    const blob = new Blob([content], { type: contentType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    
    // Clean up
    setTimeout(() => URL.revokeObjectURL(url), 100);
    
    // Hide modal
    bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
}
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
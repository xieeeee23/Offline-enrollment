<?php
$title = 'Student List';
$page_header = 'Student List';
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';
require_once $relative_path . 'includes/report_header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'registrar', 'teacher'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit();
}

// Get filter parameters
$filter_gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$filter_grade = isset($_GET['grade']) ? $_GET['grade'] : '';
$filter_strand = isset($_GET['strand']) ? $_GET['strand'] : '';
$filter_section = isset($_GET['section']) ? $_GET['section'] : '';
$filter_school_year = isset($_GET['school_year']) ? $_GET['school_year'] : '';
$filter_semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_student_type = isset($_GET['student_type']) ? $_GET['student_type'] : '';
$filter_has_voucher = isset($_GET['has_voucher']) ? $_GET['has_voucher'] : '';

// Get print options
$show_summary = isset($_GET['show_summary']) && $_GET['show_summary'] === '1';
$landscape = isset($_GET['landscape']) && $_GET['landscape'] === '1';
$custom_title = isset($_GET['title']) ? $_GET['title'] : 'Student List';

// Process gender filter (comma-separated list)
$gender_array = [];
if (!empty($filter_gender)) {
    $gender_array = explode(',', $filter_gender);
}

// Build query with filters
$query = "SELECT s.*, 
          CONCAT(s.last_name, ', ', s.first_name, ' ', IF(s.middle_name IS NULL OR s.middle_name = '', '', CONCAT(LEFT(s.middle_name, 1), '.'))) AS full_name,
          ss.strand_name
          FROM students s
          LEFT JOIN shs_strands ss ON s.strand = ss.strand_code
          WHERE 1=1";
$params = array();
$types = "";

// Apply gender filter
if (!empty($gender_array)) {
    $placeholders = implode(',', array_fill(0, count($gender_array), '?'));
    $query .= " AND s.gender IN ($placeholders)";
    foreach ($gender_array as $gender) {
        $params[] = $gender;
        $types .= "s";
    }
}

// Apply grade filter
if (!empty($filter_grade)) {
    $query .= " AND s.grade_level = ?";
    $params[] = $filter_grade;
    $types .= "s";
}

// Apply strand filter
if (!empty($filter_strand)) {
    $query .= " AND s.strand = ?";
    $params[] = $filter_strand;
    $types .= "s";
}

// Apply section filter
if (!empty($filter_section)) {
    $query .= " AND s.section = ?";
    $params[] = $filter_section;
    $types .= "s";
}

// Apply school year filter
if (!empty($filter_school_year)) {
    $query .= " AND s.school_year = ?";
    $params[] = $filter_school_year;
    $types .= "s";
}

// Apply semester filter
if (!empty($filter_semester)) {
    $query .= " AND s.semester = ?";
    $params[] = $filter_semester;
    $types .= "s";
}

// Apply status filter
if (!empty($filter_status)) {
    $query .= " AND s.enrollment_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Apply student type filter
if (!empty($filter_student_type)) {
    $query .= " AND s.student_type = ?";
    $params[] = $filter_student_type;
    $types .= "s";
}

// Apply voucher filter
if ($filter_has_voucher !== '') {
    if ($filter_has_voucher === '1') {
        $query .= " AND s.has_voucher = 1";
    } else if ($filter_has_voucher === '0') {
        $query .= " AND (s.has_voucher = 0 OR s.has_voucher IS NULL)";
    }
}

// Order by last name, first name
$query .= " ORDER BY s.last_name, s.first_name";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    // Handle preparation error
    echo '<div class="alert alert-danger">Error preparing statement: ' . mysqli_error($conn) . '</div>';
    require_once $relative_path . 'includes/report_footer.php';
    exit();
}

if (!empty($params)) {
    try {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error binding parameters: ' . $e->getMessage() . '</div>';
        require_once $relative_path . 'includes/report_footer.php';
        exit();
    }
}

try {
    $execute_result = mysqli_stmt_execute($stmt);
    if (!$execute_result) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error executing query: ' . $e->getMessage() . '</div>';
    require_once $relative_path . 'includes/report_footer.php';
    exit();
}

// Get student statistics
$total_students = mysqli_num_rows($result);

// Count by gender
$male_count = 0;
$female_count = 0;
$other_count = 0;

// Count by grade level
$grade_counts = [];

// Count by strand
$strand_counts = [];

// Count by section
$section_counts = [];

// Count by status
$status_counts = [];

// Count by student type
$student_type_counts = [];

// Count by voucher status
$voucher_counts = [
    'with_voucher' => 0,
    'without_voucher' => 0
];

// Process students for statistics
$students = [];
while ($student = mysqli_fetch_assoc($result)) {
    $students[] = $student;
    
    // Count by gender
    if ($student['gender'] === 'Male') $male_count++;
    elseif ($student['gender'] === 'Female') $female_count++;
    else $other_count++;
    
    // Count by grade level
    $grade_level = $student['grade_level'];
    if (!empty($grade_level)) {
        if (isset($grade_counts[$grade_level])) {
            $grade_counts[$grade_level]++;
        } else {
            $grade_counts[$grade_level] = 1;
        }
    }
    
    // Count by strand
    $strand = $student['strand_name'] ?? $student['strand'];
    if (!empty($strand)) {
        if (isset($strand_counts[$strand])) {
            $strand_counts[$strand]++;
        } else {
            $strand_counts[$strand] = 1;
        }
    }
    
    // Count by section
    $section = $student['section'];
    if (!empty($section)) {
        if (isset($section_counts[$section])) {
            $section_counts[$section]++;
        } else {
            $section_counts[$section] = 1;
        }
    }
    
    // Count by status
    $status = $student['enrollment_status'];
    if (!empty($status)) {
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        } else {
            $status_counts[$status] = 1;
        }
    }
    
    // Count by student type
    $student_type = $student['student_type'] ?? 'new';
    if (!empty($student_type)) {
        if (isset($student_type_counts[$student_type])) {
            $student_type_counts[$student_type]++;
        } else {
            $student_type_counts[$student_type] = 1;
        }
    }
    
    // Count by voucher status
    if (isset($student['has_voucher']) && $student['has_voucher'] == 1) {
        $voucher_counts['with_voucher']++;
    } else {
        $voucher_counts['without_voucher']++;
    }
}

// Reset result pointer
mysqli_data_seek($result, 0);

// Separate students by gender
$male_students = [];
$female_students = [];

foreach ($students as $student) {
    if ($student['gender'] === 'Male') {
        $male_students[] = $student;
    } elseif ($student['gender'] === 'Female') {
        $female_students[] = $student;
    }
}

// Get filter descriptions for display
$filter_descriptions = [];

if (!empty($filter_grade)) {
    $filter_descriptions[] = "Grade Level: $filter_grade";
}

if (!empty($filter_strand)) {
    // Get strand name
    $strand_query = "SELECT strand_name FROM shs_strands WHERE strand_code = ?";
    $strand_stmt = mysqli_prepare($conn, $strand_query);
    mysqli_stmt_bind_param($strand_stmt, "s", $filter_strand);
    mysqli_stmt_execute($strand_stmt);
    $strand_result = mysqli_stmt_get_result($strand_stmt);
    if ($strand_row = mysqli_fetch_assoc($strand_result)) {
        $filter_descriptions[] = "Strand: {$filter_strand} - {$strand_row['strand_name']}";
    } else {
        $filter_descriptions[] = "Strand: $filter_strand";
    }
}

if (!empty($filter_section)) {
    $filter_descriptions[] = "Section: $filter_section";
}

if (!empty($filter_school_year)) {
    $filter_descriptions[] = "School Year: $filter_school_year";
}

if (!empty($filter_semester)) {
    $filter_descriptions[] = "Semester: $filter_semester";
}

if (!empty($filter_status)) {
    $filter_descriptions[] = "Status: " . ucfirst($filter_status);
}

if (!empty($filter_student_type)) {
    $filter_descriptions[] = "Student Type: " . ucfirst($filter_student_type);
}

if ($filter_has_voucher !== '') {
    $filter_descriptions[] = "Voucher: " . ($filter_has_voucher == '1' ? 'With Voucher' : 'Without Voucher');
}

if (!empty($gender_array)) {
    $filter_descriptions[] = "Gender: " . implode(', ', $gender_array);
}

?>

<!-- Main Content Area -->
<div class="container-fluid px-0">
    <!-- Header info already in report_header.php -->
    
    <?php if (!empty($filter_descriptions)): ?>
    <div class="text-center mb-2">
        <p class="filter-desc mb-0"><?php echo implode(' | ', $filter_descriptions); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Horizontal rule -->
    <hr class="divider my-2">
    
    <?php if ($show_summary && $total_students > 0): ?>
    <!-- Summary Statistics Section -->
    <div class="summary-section mb-3">
        <h5 class="fw-bold mb-2">Summary Statistics</h5>
        <div class="row g-0">
            <div class="col-md-3">
                <p class="mb-1 fw-bold">Total Students: <?php echo $total_students; ?></p>
                <ul class="ps-3 mb-2">
                    <li>Male: <?php echo $male_count; ?></li>
                    <li>Female: <?php echo $female_count; ?></li>
                    <?php if ($other_count > 0): ?>
                    <li>Other: <?php echo $other_count; ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <?php if (!empty($grade_counts)): ?>
            <div class="col-md-3">
                <p class="mb-1 fw-bold">By Grade Level:</p>
                <ul class="ps-3 mb-2">
                    <?php foreach ($grade_counts as $grade => $count): ?>
                    <li>Grade <?php echo htmlspecialchars($grade); ?>: <?php echo $count; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($strand_counts)): ?>
            <div class="col-md-3">
                <p class="mb-1 fw-bold">By Strand:</p>
                <ul class="ps-3 mb-2">
                    <?php foreach ($strand_counts as $strand => $count): ?>
                    <li><?php echo htmlspecialchars($strand); ?>: <?php echo $count; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($status_counts)): ?>
            <div class="col-md-3">
                <p class="mb-1 fw-bold">By Status:</p>
                <ul class="ps-3 mb-2">
                    <?php foreach ($status_counts as $status => $count): ?>
                    <li><?php echo ucfirst(htmlspecialchars($status)); ?>: <?php echo $count; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($student_type_counts)): ?>
            <div class="col-md-3">
                <p class="mb-1 fw-bold">By Student Type:</p>
                <ul class="ps-3 mb-2">
                    <?php foreach ($student_type_counts as $type => $count): ?>
                    <li><?php echo ucfirst(htmlspecialchars($type)); ?>: <?php echo $count; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($voucher_counts['with_voucher'] > 0 || $voucher_counts['without_voucher'] > 0): ?>
            <div class="col-md-3">
                <p class="mb-1 fw-bold">By Voucher Status:</p>
                <ul class="ps-3 mb-2">
                    <li>With Voucher: <?php echo $voucher_counts['with_voucher']; ?></li>
                    <li>Without Voucher: <?php echo $voucher_counts['without_voucher']; ?></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Student Lists Section -->
    <div class="row g-0">
        <!-- Male Students Column -->
        <div class="col-md-6 pe-md-1">
            <div class="male-section mb-3">
                <div class="list-header bg-primary text-white py-1 px-2">
                    <h5 class="mb-0">Male Students (<?php echo $male_count; ?>)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered m-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 30px;">#</th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Grade</th>
                                <th>Section</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($male_students) > 0): ?>
                                <?php $counter = 1; ?>
                                <?php foreach ($male_students as $student): ?>
                                <tr>
                                    <td class="text-center"><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($student['student_id'] ?? $student['lrn']); ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td>Grade <?php echo htmlspecialchars($student['grade_level']); ?></td>
                                    <td><?php echo htmlspecialchars($student['section']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No male students found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Female Students Column -->
        <div class="col-md-6 ps-md-1">
            <div class="female-section mb-3">
                <div class="list-header bg-danger text-white py-1 px-2">
                    <h5 class="mb-0">Female Students (<?php echo $female_count; ?>)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered m-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 30px;">#</th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Grade</th>
                                <th>Section</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($female_students) > 0): ?>
                                <?php $counter = 1; ?>
                                <?php foreach ($female_students as $student): ?>
                                <tr>
                                    <td class="text-center"><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($student['student_id'] ?? $student['lrn']); ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td>Grade <?php echo htmlspecialchars($student['grade_level']); ?></td>
                                    <td><?php echo htmlspecialchars($student['section']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No female students found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Horizontal rule before signatures -->
    <hr class="divider my-4">
    
    <!-- Signature Section -->
    <div class="signature-section mt-5 mb-3">
        <div class="row g-0 text-center">
            <div class="col-md-4">
                <p>Prepared by</p>
                <div class="signature-line"></div>
                <p class="signatory-title">Registrar</p>
            </div>
            <div class="col-md-4">
                <p>Verified by</p>
                <div class="signature-line"></div>
                <p class="signatory-title">Admin Officer</p>
            </div>
            <div class="col-md-4">
                <p>Approved by</p>
                <div class="signature-line"></div>
                <p class="signatory-title">School Principal</p>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer-section mt-4 text-center">
        <p class="small mb-1">© <?php echo date('Y'); ?>, THE KRISLIZZ INTERNATIONAL ACADEMY INC. All Rights Reserved.</p>
        <p class="small mb-0">This is an official document of the school. Unauthorized reproduction is strictly prohibited.</p>
    </div>
</div>

<style>
/* Base styles */
body {
    font-family: 'Arial', sans-serif;
    font-size: 11pt;
}

.divider {
    border-top: 1px solid #000;
    margin: 10px 0;
}

.fw-bold {
    font-weight: bold;
}

.list-header {
    padding: 3px 5px;
    font-weight: bold;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10pt;
}

.table th, .table td {
    border: 1px solid #333;
    padding: 3px 5px;
}

.table thead th {
    background-color: #f0f0f0;
    font-weight: bold;
    text-align: left;
}

.signature-line {
    border-top: 1px solid #333;
    width: 70%;
    margin: 40px auto 3px;
}

.signatory-title {
    margin-top: 3px;
    font-weight: normal;
}

.filter-desc {
    font-size: 9pt;
    color: #666;
    font-style: italic;
}

/* Print specific styles */
@media print {
    body {
        font-size: 10pt;
    }
    
    .container-fluid {
        width: 100%;
        padding: 0;
        margin: 0;
    }
    
    .bg-primary {
        background-color: #eaf2ff !important;
        color: #000 !important;
    }
    
    .bg-danger {
        background-color: #ffebee !important;
        color: #000 !important;
    }
    
    .table {
        font-size: 9pt;
    }
    
    .table th, .table td {
        padding: 2px 3px;
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
    }
    
    /* Column definitions for print */
    .col-md-3 {
        width: 25%;
        float: left;
    }
    
    .col-md-4 {
        width: 33.33%;
        float: left;
    }
    
    .col-md-6 {
        width: 50%;
        float: left;
    }
    
    .col-md-12 {
        width: 100%;
    }
    
    /* Fix padding */
    .pe-md-1 {
        padding-right: 0.15rem !important;
    }
    
    .ps-md-1 {
        padding-left: 0.15rem !important;
    }
    
    .male-section, .female-section {
        break-inside: avoid;
    }
    
    .signature-section {
        break-before: auto;
        break-after: avoid;
        margin-top: 1rem;
    }
    
    .report-title {
        margin: 5px 0 3px;
        font-size: 14pt;
    }
    
    .report-date {
        font-size: 8pt;
        color: #666;
    }
    
    /* Remove unnecessary margins */
    ul {
        margin-top: 0;
        margin-bottom: 0.3rem;
        padding-left: 16px;
    }
    
    h5 {
        margin-top: 0;
        margin-bottom: 3px;
        font-size: 10pt;
    }
    
    p {
        margin-top: 0;
        margin-bottom: 2px;
    }
    
    .mb-0 {
        margin-bottom: 0 !important;
    }
    
    .mb-1 {
        margin-bottom: 0.15rem !important;
    }
    
    .mb-2 {
        margin-bottom: 0.3rem !important;
    }
    
    .mb-3 {
        margin-bottom: 0.5rem !important;
    }
    
    .mt-4 {
        margin-top: 1rem !important;
    }
    
    .mt-5 {
        margin-top: 1.5rem !important;
    }
    
    .py-1 {
        padding-top: 0.15rem !important;
        padding-bottom: 0.15rem !important;
    }
    
    .px-2 {
        padding-left: 0.3rem !important;
        padding-right: 0.3rem !important;
    }
    
    .text-center {
        text-align: center !important;
    }
    
    .small {
        font-size: 7pt;
    }
    
    /* Set proper margins for long bond paper */
    @page {
        <?php if ($landscape): ?>
        size: 14in 8.5in;
        margin: 0.3in;
        <?php else: ?>
        size: 8.5in 14in;
        margin: 0.5in 0.5in;
        <?php endif; ?>
    }
    
    /* Compact the signature section */
    .signature-line {
        margin: 30px auto 3px;
    }
    
    /* Maximize table space */
    .summary-section {
        margin-bottom: 0.3rem;
    }
    
    .summary-section h5 {
        margin-bottom: 0.15rem;
    }
    
    /* Fix header spacing */
    .report-header {
        margin-bottom: 0.5rem !important;
    }
    
    /* Allow for more rows in tables */
    .table-sm th,
    .table-sm td {
        padding: 1px 2px !important;
        font-size: 8.5pt !important;
    }
    
    /* Reduce divider margins */
    .divider {
        margin: 5px 0;
    }
    
    .my-2 {
        margin-top: 0.3rem !important;
        margin-bottom: 0.3rem !important;
    }
    
    .my-4 {
        margin-top: 0.75rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    /* Ensure max table rows visible on long paper */
    .table-responsive {
        max-height: none !important;
        overflow: visible !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Automatically print when page loads
    setTimeout(function() {
        window.print();
    }, 1000);
});
</script>

<?php require_once $relative_path . 'includes/report_footer.php'; ?> 
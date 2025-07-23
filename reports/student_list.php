<?php
$title = 'Student List Report';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Get filter parameters
$grade_filter = isset($_GET['grade']) ? cleanInput($_GET['grade']) : null;
$section_filter = isset($_GET['section']) ? cleanInput($_GET['section']) : null;
$status_filter = isset($_GET['status']) ? cleanInput($_GET['status']) : 'enrolled'; // Default to enrolled students

// Check if this is an Excel export request
$export_excel = isset($_GET['export']) && $_GET['export'] === 'excel';

// Get all students with filters
$students = [];
$query = "SELECT s.*, u.name as enrolled_by_name 
          FROM students s 
          LEFT JOIN users u ON s.enrolled_by = u.id 
          WHERE 1=1";

// Add filters
if (!empty($grade_filter)) {
    $query .= " AND s.grade_level = '" . mysqli_real_escape_string($conn, $grade_filter) . "'";
}

if (!empty($section_filter)) {
    $query .= " AND s.section = '" . mysqli_real_escape_string($conn, $section_filter) . "'";
}

if (!empty($status_filter)) {
    $query .= " AND s.enrollment_status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

$query .= " ORDER BY s.grade_level, s.section, s.last_name, s.first_name";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

// Get unique grade levels for filter
$grades = [];
$grade_query = "SELECT DISTINCT grade_level FROM students ORDER BY grade_level";
$grade_result = mysqli_query($conn, $grade_query);
if ($grade_result) {
    while ($row = mysqli_fetch_assoc($grade_result)) {
        $grades[] = $row['grade_level'];
    }
}

// Get unique sections for filter
$sections = [];
$section_query = "SELECT DISTINCT section FROM students ORDER BY section";
$section_result = mysqli_query($conn, $section_query);
if ($section_result) {
    while ($row = mysqli_fetch_assoc($section_result)) {
        $sections[] = $row['section'];
    }
}

// Process export to Excel
if ($export_excel) {
    try {
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Generate filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "student_list_{$timestamp}.csv";
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create output handle
        $output = fopen('php://output', 'w');
        if (!$output) {
            throw new Exception('Unable to create output stream');
        }
        
        // Add UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write report title and metadata
        fputcsv($output, [SYSTEM_NAME ?? 'School Management System']);
        fputcsv($output, ['Student List Report']);
        fputcsv($output, ['Date Generated: ' . date('F d, Y')]);
        
        // Filter information
        $filter_text = 'Status: ' . ucfirst($status_filter);
        if (!empty($grade_filter)) {
            $filter_text .= ' | Grade: ' . $grade_filter;
        }
        if (!empty($section_filter)) {
            $filter_text .= ' | Section: ' . $section_filter;
        }
        fputcsv($output, ['Filters: ' . $filter_text]);
        fputcsv($output, []); // Empty line
        
        // Write CSV header
        $header = ['LRN', 'Last Name', 'First Name', 'Middle Name', 'Gender', 'Date of Birth', 
                'Grade Level', 'Section', 'Status', 'Guardian Name', 'Contact Number'];
        
        fputcsv($output, $header);
        
        // Write data rows directly from the already fetched students array
        foreach ($students as $student) {
            $data = [
                $student['lrn'] ?? 'N/A',
                $student['last_name'] ?? 'N/A',
                $student['first_name'] ?? 'N/A',
                $student['middle_name'] ?? '',
                $student['gender'] ?? 'N/A',
                $student['date_of_birth'] ?? 'N/A',
                $student['grade_level'] ?? 'N/A',
                $student['section'] ?? 'N/A',
                ucfirst($student['enrollment_status'] ?? 'Unknown'),
                $student['guardian_name'] ?? 'N/A',
                $student['contact_number'] ?? 'N/A'
            ];
            
            fputcsv($output, $data);
        }
        
        fclose($output);
        exit;
    } catch (Exception $e) {
        // If there was an error, cancel the output buffering and show error
        if (ob_get_level()) {
            ob_end_clean();
        }
        $_SESSION['alert'] = showAlert('Error exporting to Excel: ' . $e->getMessage(), 'danger');
        redirect($_SERVER['PHP_SELF']);
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Student List Report</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Student List Report</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2 btn-print" data-print-target="#student-list-table">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-light btn-export-excel" data-table-id="student-list-table" data-filename="student_list_<?php echo date('Y-m-d'); ?>">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="get" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="grade_level" class="form-label">Grade Level</label>
                            <select class="form-select" id="grade_level" name="grade_level">
                                <option value="">All Grades</option>
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?php echo $grade; ?>" <?php echo (isset($_GET['grade_level']) && $_GET['grade_level'] === $grade) ? 'selected' : ''; ?>>
                                        <?php echo $grade; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="section" class="form-label">Section</label>
                            <select class="form-select" id="section" name="section">
                                <option value="">All Sections</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo $section; ?>" <?php echo (isset($_GET['section']) && $_GET['section'] === $section) ? 'selected' : ''; ?>>
                                        <?php echo $section; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="enrolled" <?php echo (isset($_GET['status']) && $_GET['status'] === 'enrolled') ? 'selected' : ''; ?>>Enrolled</option>
                                <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="withdrawn" <?php echo (isset($_GET['status']) && $_GET['status'] === 'withdrawn') ? 'selected' : ''; ?>>Withdrawn</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <a href="<?php echo $relative_path; ?>modules/reports/student_list.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="student-list-table" class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>LRN</th>
                                <th>Name</th>
                                <th>Grade & Section</th>
                                <th>Date of Birth</th>
                                <th>Status</th>
                                <th>Date Enrolled</th>
                                <th>Enrolled By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No students found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                        <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['grade_level'] . ' ' . $student['section']); ?></td>
                                        <td><?php echo formatDate($student['dob']); ?></td>
                                        <td>
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
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($student['enrollment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($student['date_enrolled']); ?></td>
                                        <td><?php echo htmlspecialchars($student['enrolled_by_name'] ?? 'Unknown'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted">
                Total students: <?php echo count($students); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
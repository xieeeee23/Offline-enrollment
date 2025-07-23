<?php
$title = 'Teacher List Report';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Get filter parameters
$department_filter = isset($_GET['department']) ? cleanInput($_GET['department']) : null;
$status_filter = isset($_GET['status']) ? cleanInput($_GET['status']) : 'active'; // Default to active teachers

// Check if this is an Excel export request
$export_excel = isset($_GET['export']) && $_GET['export'] === 'excel';

// Get all teachers with filters
$teachers = [];
$query = "SELECT t.*, t.email as teacher_email, u.name as user_name, u.email as user_email, u.role 
          FROM teachers t 
          LEFT JOIN users u ON t.user_id = u.id 
          WHERE 1=1";

// Add filters
if (!empty($department_filter)) {
    $query .= " AND t.department = '" . mysqli_real_escape_string($conn, $department_filter) . "'";
}

if (!empty($status_filter)) {
    $query .= " AND t.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

$query .= " ORDER BY t.last_name, t.first_name";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $teachers[] = $row;
    }
}

// Get unique departments for filter
$departments = [];
$dept_query = "SELECT DISTINCT department FROM teachers ORDER BY department";
$dept_result = mysqli_query($conn, $dept_query);
if ($dept_result) {
    while ($row = mysqli_fetch_assoc($dept_result)) {
        if (!empty($row['department'])) {
            $departments[] = $row['department'];
        }
    }
}

// Export to Excel if requested
if ($export_excel) {
    try {
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for Excel file download
        $filename = 'teacher_list_report_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        if (!$output) {
            throw new Exception('Unable to create output stream');
        }
        
        // Set UTF-8 BOM for Excel to recognize UTF-8 encoding
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write report title and metadata
        fputcsv($output, ['THE KRISLIZZ INTERNATIONAL ACADEMY INC.']);
        fputcsv($output, ['TEACHER LIST REPORT']);
        fputcsv($output, ['Date Generated: ' . date('F d, Y')]);
    
    // Filter information
    $filter_text = 'Status: ' . ucfirst($status_filter);
    if (!empty($department_filter)) {
        $filter_text .= ' | Department: ' . $department_filter;
    }
        fputcsv($output, ['Filters: ' . $filter_text]);
        fputcsv($output, []); // Empty line
        
        // Table header - include more fields for comprehensive data
        fputcsv($output, [
            'Last Name', 
            'First Name', 
            'Department', 
            'Subject',
            'Grade Level', 
            'Contact Number', 
            'Email', 
            'Qualification',
            'Status',
            'User Account'
        ]);
    
    // Table data
    foreach ($teachers as $teacher) {
            // Determine which email to use - prefer teacher's email, fall back to user email
            $email = !empty($teacher['teacher_email']) ? $teacher['teacher_email'] : ($teacher['user_email'] ?? 'N/A');
            
            fputcsv($output, [
                $teacher['last_name'] ?? 'N/A',
                $teacher['first_name'] ?? 'N/A',
                $teacher['department'] ?? 'Not Assigned',
                $teacher['subject'] ?? 'N/A',
                $teacher['grade_level'] ?? 'N/A',
                $teacher['contact_number'] ?? 'N/A',
                $email,
                $teacher['qualification'] ?? 'N/A',
                ucfirst($teacher['status'] ?? 'Unknown'),
                !empty($teacher['user_name']) ? $teacher['user_name'] . ' (' . ucfirst($teacher['role']) . ')' : 'No Account'
            ]);
        }
        
        // Close output stream
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
        <h1 class="mb-4">Teacher List Report</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Filter Teachers</h5>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-4">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>" <?php echo ($department_filter == $dept) ? 'selected' : ''; ?>>
                                    <?php echo $dept; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="">All Status</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="d-grid gap-2 w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Teacher List</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2 btn-print" data-print-target="#teacher-list-table">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-sm btn-light btn-export-excel" data-table-id="teacher-list-table" data-filename="teacher_list_<?php echo date('Y-m-d'); ?>">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="teacher-list-table" class="table table-striped table-hover data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Contact Number</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>User Account</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teachers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No teachers found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($teacher['last_name'] . ', ' . $teacher['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['department'] ?? 'Not Assigned'); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['contact_number'] ?? 'N/A'); ?></td>
                                        <td><?php 
                                        $email = !empty($teacher['teacher_email']) ? $teacher['teacher_email'] : ($teacher['user_email'] ?? 'N/A');
                                        echo htmlspecialchars($email);
                                        ?></td>
                                        <td>
                                            <?php
                                            $status_class = $teacher['status'] === 'active' ? 'bg-success' : 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($teacher['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($teacher['user_id'])): ?>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($teacher['user_name'] . ' (' . ucfirst($teacher['role']) . ')'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No Account</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted">
                Total teachers: <?php echo count($teachers); ?>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
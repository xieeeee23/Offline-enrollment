<?php
$title = 'Senior High School Enrollment System';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// This is a backup version of the students.php file
// Created to ensure no binary corruption issues

// Custom CSS for status badges
$extra_css = <<<CSS
<style>
    .status-badge {
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        font-weight: 500;
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
</style>
CSS;

echo $extra_css;

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Get search parameters - make sure these are defined before export processing
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_grade = isset($_GET['grade']) ? $_GET['grade'] : '';
$filter_strand = isset($_GET['strand']) ? $_GET['strand'] : '';
$filter_section = isset($_GET['section']) ? $_GET['section'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

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
    
    // Export to Excel only (removed PDF and Word export options)
    if ($export_type === 'excel') {
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
        
        // Write CSV header
        $header = ['LRN', 'Last Name', 'First Name', 'Middle Name', 'Gender', 'Grade Level', 'Strand', 
                   'Section', 'Status', 'Date of Birth', 'Contact Number', 'Email', 'Address'];
        
        fputcsv($output, $header);
        
        // Write data rows
        while ($row = mysqli_fetch_assoc($result)) {
            // Format LRN to 12 digits
            $formatted_lrn = $row['lrn'];
            if (strlen($formatted_lrn) < 12) {
                $formatted_lrn = str_pad($formatted_lrn, 12, '0', STR_PAD_LEFT);
            }
            
            $data = [
                $formatted_lrn,
                $row['last_name'],
                $row['first_name'],
                $row['middle_name'],
                $row['gender'],
                $row['grade_level'],
                $row['strand'],
                $row['section'],
                ucfirst($row['enrollment_status']),
                $row['dob'],
                $row['contact_number'],
                $row['email'],
                $row['address']
            ];
            
            fputcsv($output, $data);
        }
        
        fclose($output);
        exit;
    } else {
        // If any other export type is requested, redirect back to the report page
        $_SESSION['alert'] = showAlert('Only Excel export is available.', 'info');
        redirect($_SERVER['PHP_SELF']);
    }
}

// Process delete request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $student_id = (int) $_GET['id'];
    
    // Get student details first for logging
    $query = "SELECT first_name, last_name, lrn FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($result);
    
    if ($student) {
        // Delete the student
        $query = "DELETE FROM students WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log the action
            $log_desc = "Deleted student: {$student['first_name']} {$student['last_name']} (LRN: {$student['lrn']})";
            logAction($_SESSION['user_id'], 'DELETE', $log_desc);
            
            $_SESSION['alert'] = showAlert('Student deleted successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error deleting student: ' . mysqli_error($conn), 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('Student not found.', 'danger');
    }
    
    // Redirect to students page
    redirect('modules/registrar/students.php');
}

// Get all grade levels
$grade_levels = [];
$query = "SELECT DISTINCT grade_level FROM students ORDER BY grade_level";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $grade_levels[] = $row['grade_level'];
}

// Get all strands
$strands = [];
$query = "SELECT DISTINCT strand FROM students WHERE strand IS NOT NULL AND strand != '' ORDER BY strand";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $strands[] = $row['strand'];
}

// Get all sections
$sections = [];
$query = "SELECT DISTINCT section FROM students ORDER BY section";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $sections[] = $row['section'];
}

// Get student count for pagination
$count_query = "SELECT COUNT(*) as total FROM students WHERE 1=1";
$count_params = array();

if (!empty($search)) {
    $count_query .= " AND (first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR lrn LIKE ?)";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
}

if (!empty($filter_grade)) {
    $count_query .= " AND grade_level = ?";
    $count_params[] = $filter_grade;
}

if (!empty($filter_strand)) {
    $count_query .= " AND strand = ?";
    $count_params[] = $filter_strand;
}

if (!empty($filter_section)) {
    $count_query .= " AND section = ?";
    $count_params[] = $filter_section;
}

if (!empty($filter_status)) {
    $count_query .= " AND enrollment_status = ?";
    $count_params[] = $filter_status;
}

$count_stmt = mysqli_prepare($conn, $count_query);

if (!empty($count_params)) {
    $types = str_repeat("s", count($count_params));
    mysqli_stmt_bind_param($count_stmt, $types, ...$count_params);
}

mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];

// Pagination settings
$records_per_page = 10;
$total_pages = ceil($total_records / $records_per_page);
$page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($page - 1) * $records_per_page;

// Get students with filters and pagination
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
}

$query .= " ORDER BY last_name, first_name LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;

$stmt = mysqli_prepare($conn, $query);

// Bind parameters
if (!empty($params)) {
    $types = str_repeat("s", count($params) - 2) . "ii"; // The last two parameters are integers
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$students = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Students Management</h1>
        <div>
            <div class="btn-group mr-2">
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-file-excel mr-1"></i> Excel
                </a>
            </div>
            <a href="<?php echo $relative_path; ?>modules/registrar/add_student.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add New Student
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Search and Filter</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="mb-0">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <input type="text" class="form-control" placeholder="Search by name or LRN..." 
                              name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-control" name="grade">
                            <option value="">All Grades</option>
                            <?php foreach ($grade_levels as $grade): ?>
                            <option value="<?php echo htmlspecialchars($grade); ?>" 
                                   <?php echo ($filter_grade === $grade) ? 'selected' : ''; ?>>
                                Grade <?php echo htmlspecialchars($grade); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-control" name="strand">
                            <option value="">All Strands</option>
                            <?php foreach ($strands as $strand): ?>
                            <option value="<?php echo htmlspecialchars($strand); ?>" 
                                   <?php echo ($filter_strand === $strand) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($strand); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-control" name="section">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $section): ?>
                            <option value="<?php echo htmlspecialchars($section); ?>" 
                                   <?php echo ($filter_section === $section) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($section); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-control" name="status">
                            <option value="">All Status</option>
                            <option value="enrolled" <?php echo ($filter_status === 'enrolled') ? 'selected' : ''; ?>>Enrolled</option>
                            <option value="pending" <?php echo ($filter_status === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="withdrawn" <?php echo ($filter_status === 'withdrawn') ? 'selected' : ''; ?>>Withdrawn</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
            <span class="badge badge-pill badge-info"><?php echo $total_records; ?> students found</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Name</th>
                            <th>Grade & Section</th>
                            <th>Status</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No students found</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']);
                                    if (!empty($student['middle_name'])) {
                                        echo ' ' . htmlspecialchars(mb_substr($student['middle_name'], 0, 1) . '.');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo 'Grade ' . htmlspecialchars($student['grade_level']) . ' - ' . htmlspecialchars($student['section']);
                                    if (!empty($student['strand'])) {
                                        echo ' (' . htmlspecialchars($student['strand']) . ')';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch ($student['enrollment_status']) {
                                        case 'enrolled':
                                            $status_class = 'enrolled';
                                            break;
                                        case 'pending':
                                            $status_class = 'pending';
                                            break;
                                        case 'withdrawn':
                                            $status_class = 'withdrawn';
                                            break;
                                    }
                                    ?>
                                    <span class="badge status-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($student['enrollment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($student['contact_number']); ?></td>
                                <td>
                                    <a href="<?php echo $relative_path; ?>modules/registrar/view_student.php?id=<?php echo $student['id']; ?>" 
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo $relative_path; ?>modules/registrar/edit_student.php?id=<?php echo $student['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&id=<?php echo $student['id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this student?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?<?php 
                                echo http_build_query(array_merge($_GET, ['page' => $page - 1])); 
                            ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?<?php 
                                echo http_build_query(array_merge($_GET, ['page' => $i])); 
                            ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $_SERVER['PHP_SELF']; ?>?<?php 
                                echo http_build_query(array_merge($_GET, ['page' => $page + 1])); 
                            ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?>

<script>
// JavaScript for handling status badges
document.addEventListener('DOMContentLoaded', function() {
    // Enhance badge display with animation
    const badges = document.querySelectorAll('.status-badge');
    badges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.cursor = 'pointer';
        });
    });
});

// Function to validate LRN format (12 digits)
function validateLRN(lrn) {
    return /^\d{12}$/.test(lrn);
}
</script> 
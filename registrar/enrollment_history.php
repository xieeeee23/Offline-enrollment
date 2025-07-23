<?php
$title = 'Student Enrollment History';
$page_header = 'Student Enrollment History';
$relative_path = '../../';

// Add required JavaScript files
$extra_js = <<<JS
<script src="{$relative_path}assets/js/export-table.js"></script>
JS;

require_once $relative_path . 'includes/header.php';

// Add custom CSS for search highlighting
echo '<style>
    .search-highlight {
        background-color: #fff3cd;
        padding: 2px;
        border-radius: 3px;
        font-weight: bold;
    }
    .search-container {
        position: relative;
        margin-bottom: 1rem;
    }
    .search-container .input-group {
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border-radius: 4px;
    }
    .search-container .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    .search-icon {
        color: #4e73df;
    }
</style>';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Get search parameter
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Get filter parameters
$student_filter = isset($_GET['student']) ? (int)$_GET['student'] : null;
$school_year_filter = isset($_GET['school_year']) ? $_GET['school_year'] : '';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : '';
$strand_filter = isset($_GET['strand']) ? $_GET['strand'] : '';

// Initialize history records array
$history_records = [];

// Query to get enrollment history based on filters
$query = "SELECT 
            sh.id, 
            sh.student_id, 
            sh.school_year, 
            sh.semester,
            s.grade_level,
            sh.strand,
            s.section,
            s.enrollment_status as status,
            s.date_enrolled,
            s.first_name,
            s.last_name,
            s.middle_name,
            s.lrn,
            u.name as enrolled_by_name
          FROM senior_highschool_details sh
          LEFT JOIN students s ON sh.student_id = s.id
          LEFT JOIN users u ON s.enrolled_by = u.id
          WHERE 1=1";

// Add search condition
if (!empty($search_query)) {
    $search_param = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $query .= " AND (s.first_name LIKE '$search_param' OR s.last_name LIKE '$search_param' OR s.lrn LIKE '$search_param' OR CONCAT(s.first_name, ' ', s.last_name) LIKE '$search_param' OR CONCAT(s.last_name, ', ', s.first_name) LIKE '$search_param')";
}

// Add filters to query
if ($student_filter) {
    $query .= " AND sh.student_id = " . $student_filter;
}
if ($school_year_filter) {
    $query .= " AND sh.school_year = '" . mysqli_real_escape_string($conn, $school_year_filter) . "'";
}
if ($semester_filter) {
    $query .= " AND sh.semester = '" . mysqli_real_escape_string($conn, $semester_filter) . "'";
}
if ($grade_filter) {
    $query .= " AND s.grade_level = 'Grade " . mysqli_real_escape_string($conn, $grade_filter) . "'";
}
if ($strand_filter) {
    $query .= " AND sh.strand = '" . mysqli_real_escape_string($conn, $strand_filter) . "'";
}

$query .= " ORDER BY sh.school_year DESC, sh.semester DESC, s.last_name ASC, s.first_name ASC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $history_records[] = $row;
    }
}

// Get all students for dropdown
$students = [];
$student_query = "SELECT id, first_name, last_name, lrn FROM students ORDER BY last_name, first_name";
$student_result = mysqli_query($conn, $student_query);
if ($student_result) {
    while ($row = mysqli_fetch_assoc($student_result)) {
        $students[] = $row;
    }
}

// Get all school years for dropdown
$school_years = [];
$school_year_query = "SELECT DISTINCT school_year FROM school_years ORDER BY school_year DESC";
$school_year_result = mysqli_query($conn, $school_year_query);
if ($school_year_result) {
    while ($row = mysqli_fetch_assoc($school_year_result)) {
        $school_years[] = $row['school_year'];
    }
}

// Get all strands for dropdown
$strands = [];
$strand_query = "SELECT strand_code, strand_name FROM shs_strands ORDER BY strand_name";
$strand_result = mysqli_query($conn, $strand_query);
if ($strand_result) {
    while ($row = mysqli_fetch_assoc($strand_result)) {
        $strands[$row['strand_code']] = $row['strand_name'];
    }
}
?>

<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary text-white">Filter Enrollment History</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-12 mb-3 search-container">
                    <div class="input-group">
                        <span class="input-group-text search-icon">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by student name or LRN..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button class="btn btn-primary" type="submit">
                            Search
                        </button>
                    </div>
                    <?php if (!empty($search_query)): ?>
                    <div class="mt-2">
                        <span class="badge bg-info text-white">
                            <i class="fas fa-filter"></i> Search results for: "<?php echo htmlspecialchars($search_query); ?>"
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label for="student" class="form-label">Student</label>
                    <select class="form-select" id="student" name="student">
                        <option value="">All Students</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo $student_filter == $student['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' (' . $student['lrn'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="school_year" class="form-label">School Year</label>
                    <select class="form-select" id="school_year" name="school_year">
                        <option value="">All School Years</option>
                        <?php foreach ($school_years as $school_year): ?>
                            <option value="<?php echo $school_year; ?>" <?php echo $school_year_filter == $school_year ? 'selected' : ''; ?>>
                                <?php echo $school_year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="semester" class="form-label">Semester</label>
                    <select class="form-select" id="semester" name="semester">
                        <option value="">All Semesters</option>
                        <option value="First" <?php echo $semester_filter == 'First' ? 'selected' : ''; ?>>First</option>
                        <option value="Second" <?php echo $semester_filter == 'Second' ? 'selected' : ''; ?>>Second</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="grade" class="form-label">Grade Level</label>
                    <select class="form-select" id="grade" name="grade">
                        <option value="">All Grades</option>
                        <option value="11" <?php echo $grade_filter == '11' ? 'selected' : ''; ?>>Grade 11</option>
                        <option value="12" <?php echo $grade_filter == '12' ? 'selected' : ''; ?>>Grade 12</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="strand" class="form-label">Strand</label>
                    <select class="form-select" id="strand" name="strand">
                        <option value="">All Strands</option>
                        <?php foreach ($strands as $code => $name): ?>
                            <option value="<?php echo $code; ?>" <?php echo $strand_filter == $code ? 'selected' : ''; ?>>
                                <?php echo $code . ' - ' . $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <div class="d-grid gap-2 w-100">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
            <div class="row mt-3">
                <div class="col-12 text-end">
                    <a href="enrollment_history.php?search=&student=&school_year=&semester=&grade=&strand=" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Reset Filters
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollment History Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary text-white">Enrollment History Records</h6>
            <div>
                <button type="button" class="btn btn-sm btn-primary btn-export-excel" data-table-id="historyTable" data-filename="enrollment_history_<?php echo date('Y-m-d'); ?>">
                    <i class="fas fa-file-excel me-1"></i> Export to Excel
                </button>
                <button type="button" class="btn btn-sm btn-danger" id="printButton">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="historyTable">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>LRN</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Grade Level</th>
                            <th>Strand</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th>Date Enrolled</th>
                            <th>Enrolled By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($history_records) > 0): ?>
                            <?php foreach ($history_records as $record): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            echo htmlspecialchars($record['last_name'] . ', ' . $record['first_name']);
                                            if (!empty($record['middle_name'])) {
                                                echo ' ' . substr($record['middle_name'], 0, 1) . '.';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['lrn']); ?></td>
                                    <td><?php echo htmlspecialchars($record['school_year']); ?></td>
                                    <td><?php echo htmlspecialchars($record['semester']); ?></td>
                                    <td><?php echo htmlspecialchars('Grade ' . $record['grade_level']); ?></td>
                                    <td>
                                        <?php 
                                            $strand_code = $record['strand'];
                                            echo htmlspecialchars($strand_code);
                                            if (isset($strands[$strand_code])) {
                                                echo ' - ' . htmlspecialchars($strands[$strand_code]);
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['section']); ?></td>
                                    <td>
                                        <?php 
                                            $status = $record['status'];
                                            $status_class = '';
                                            switch(strtolower($status)) {
                                                case 'enrolled':
                                                    $status_class = 'badge bg-success';
                                                    break;
                                                case 'pending':
                                                case 'incomplete':
                                                    $status_class = 'badge bg-warning text-dark';
                                                    break;
                                                case 'withdrawn':
                                                    $status_class = 'badge bg-danger';
                                                    break;
                                                default:
                                                    $status_class = 'badge bg-secondary';
                                            }
                                        ?>
                                        <span class="<?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($record['date_enrolled']) {
                                                echo date('M d, Y', strtotime($record['date_enrolled']));
                                            } else {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['enrolled_by_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="<?php echo $relative_path; ?>modules/registrar/view_student.php?id=<?php echo $record['student_id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center">No enrollment history records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
$(document).ready(function() {
    // Initialize DataTable with standard functionality
    const historyTable = $('#historyTable').DataTable({
        "pageLength": 25,
        "order": [[2, "desc"], [3, "desc"]],
        "language": {
            "emptyTable": "No enrollment history records found.",
            "zeroRecords": "No matching records found.",
            "info": "Showing _START_ to _END_ of _TOTAL_ records",
            "infoEmpty": "Showing 0 to 0 of 0 records",
            "infoFiltered": "(filtered from _MAX_ total records)"
        },
        // Disable DataTable's built-in filtering to use our server-side filtering
        "searching": false
    });

    // Initialize select2 for better dropdown experience
    $('#student').select2({
        placeholder: "Select a student",
        allowClear: true,
        theme: "bootstrap-5"
    });
    
    // Add event listener for search input
    $('#search').on('keyup', function(e) {
        // If Enter key is pressed, submit the form
        if (e.key === 'Enter') {
            $(this).closest('form').submit();
        }
    });
    
    // Highlight search term if present
    const searchTerm = <?php echo json_encode($search_query); ?>;
    if (searchTerm) {
        highlightSearchTerm(searchTerm);
    }
    
    // Add event listener for print button
    $('#printButton').on('click', function(e) {
        e.preventDefault();
        
        // Use the standard printTable function from export-table.js if available
        if (typeof window.printTable === 'function') {
            // Get the report title
            const title = 'Student Enrollment History';
            window.printTable('historyTable', title);
        } else {
            // Fallback to custom print function
            customPrintTable();
        }
    });
});

// Function to highlight search terms in the table
function highlightSearchTerm(term) {
    if (!term) return;
    
    // Convert to lowercase for case-insensitive matching
    const searchTermLower = term.toLowerCase();
    
    // Get all table cells
    const cells = document.querySelectorAll('#historyTable tbody td');
    
    // Loop through each cell
    cells.forEach(cell => {
        // Skip cells with complex content (like those with badges)
        if (cell.querySelector('.badge')) return;
        
        const content = cell.textContent;
        const contentLower = content.toLowerCase();
        
        // Check if the cell contains the search term
        if (contentLower.includes(searchTermLower)) {
            // Create a highlighted version of the content
            const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            const highlightedContent = content.replace(regex, '<span class="search-highlight">$1</span>');
            cell.innerHTML = highlightedContent;
        }
    });
}

// Custom print function as fallback
function customPrintTable() {
    // Create a new window
    const printWindow = window.open('', '_blank', 'height=600,width=800');
    if (!printWindow) {
        alert("Please allow pop-ups to use the print function.");
        return;
    }
    
    // Get the table HTML
    const table = document.getElementById('historyTable');
    if (!table) {
        alert("Table not found.");
        return;
    }
    
    // Clone the table to modify it for printing
    const printTable = table.cloneNode(true);
    
    // Remove the Actions column
    const headerRow = printTable.querySelector('thead tr');
    const actionColumnIndex = [...headerRow.cells].findIndex(cell => cell.textContent.trim() === 'Actions');
    
    if (actionColumnIndex !== -1) {
        // Remove the header cell
        headerRow.deleteCell(actionColumnIndex);
        
        // Remove the corresponding data cells
        const rows = printTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.cells.length > actionColumnIndex) {
                row.deleteCell(actionColumnIndex);
            }
        });
    }
    
    // Get search value directly from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const searchValue = urlParams.get('search') || '';
    
    // Get other filter values
    const studentValue = document.querySelector('#student option:selected')?.text || 'All Students';
    const schoolYearValue = document.querySelector('#school_year')?.value || 'All School Years';
    const semesterValue = document.querySelector('#semester')?.value || 'All Semesters';
    const gradeValue = document.querySelector('#grade')?.value ? 'Grade ' + document.querySelector('#grade').value : 'All Grades';
    const strandValue = document.querySelector('#strand option:selected')?.text || 'All Strands';
    
    // Generate print content
    const printContent = `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Enrollment History</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .school-name {
                    font-size: 18px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .report-title {
                    font-size: 16px;
                    margin-bottom: 5px;
                }
                .filters {
                    font-size: 12px;
                    margin-bottom: 20px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 4px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    font-size: 12px;
                }
                th {
                    background-color: #f2f2f2;
                    text-align: left;
                    font-weight: bold;
                }
                .print-footer {
                    text-align: center;
                    font-size: 12px;
                    margin-top: 20px;
                    padding-top: 10px;
                    border-top: 1px solid #ddd;
                }
                .badge {
                    padding: 3px 8px;
                    border-radius: 4px;
                    font-size: 11px;
                    font-weight: bold;
                    display: inline-block;
                }
                .bg-success, .badge-success {
                    background-color: #28a745 !important;
                    color: white;
                }
                .bg-warning, .badge-warning {
                    background-color: #ffc107 !important;
                    color: #212529;
                }
                .bg-danger, .badge-danger {
                    background-color: #dc3545 !important;
                    color: white;
                }
                .bg-secondary, .badge-secondary {
                    background-color: #6c757d !important;
                    color: white;
                }
                .search-highlight {
                    background-color: #fff3cd;
                    padding: 2px;
                    border-radius: 3px;
                    font-weight: bold;
                }
                @media print {
                    .no-print {
                        display: none;
                    }
                    body {
                        padding: 0;
                        margin: 0;
                    }
                    .filters {
                        background-color: transparent !important;
                        border: 1px solid #ddd;
                    }
                    th {
                        background-color: #e9ecef !important;
                        color: black !important;
                    }
                    @page {
                        size: landscape;
                        margin: 1cm;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="school-name">THE KRISLIZZ INTERNATIONAL ACADEMY INC.</div>
                <div class="report-title">Student Enrollment History</div>
                <div class="date">${new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</div>
            </div>
            
            <div class="filters">
                <strong>Filters:</strong> 
                ${searchValue ? 'Search: "' + searchValue + '" | ' : ''}
                Student: ${studentValue} | 
                School Year: ${schoolYearValue} | 
                Semester: ${semesterValue} | 
                Grade Level: ${gradeValue} | 
                Strand: ${strandValue}
            </div>
            
            <div class="table-responsive">
                ${printTable.outerHTML}
            </div>
            
            <div class="print-footer">
                <div>Printed on: ${new Date().toLocaleString()}</div>
                <div>By: ${<?php echo json_encode($_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? $_SESSION['username'] ?? 'System User'); ?>}</div>
            </div>
            
            <div class="no-print mt-3">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
                <button class="btn btn-secondary ms-2" onclick="window.close()">Close</button>
            </div>
        </body>
        </html>
    `;
    
    // Write to the window and print
    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Wait for content to load before printing
    printWindow.onload = function() {
        setTimeout(function() {
            try {
                printWindow.focus();
                printWindow.print();
            } catch (error) {
                console.error("Error during printing:", error);
                alert("There was an error during printing. Please try again.");
            }
        }, 1000); // Increased timeout to ensure content is fully loaded
    };
}
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
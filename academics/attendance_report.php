<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'teacher', 'registrar'])) {
    echo '<div class="alert alert-danger">You do not have permission to access this resource.</div>';
    exit();
}

// Get report parameters
$month = isset($_GET['report_month']) ? (int)$_GET['report_month'] : date('n');
$year = isset($_GET['report_year']) ? (int)$_GET['report_year'] : date('Y');
$grade_section = isset($_GET['report_grade_section']) ? cleanInput($_GET['report_grade_section']) : '';

// Generate date range for the month
$start_date = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
$end_date = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

// Build query based on filters
$query_params = [];
$where_clauses = ["a.date BETWEEN ? AND ?"];
$query_params[] = $start_date;
$query_params[] = $end_date;

if (!empty($grade_section)) {
    $grade_section_parts = explode('-', $grade_section);
    if (count($grade_section_parts) === 2) {
        $grade_level = $grade_section_parts[0];
        $section = $grade_section_parts[1];
        $where_clauses[] = "s.grade_level = ?";
        $query_params[] = $grade_level;
        $where_clauses[] = "s.section = ?";
        $query_params[] = $section;
    }
}

// Build the SQL query
$sql = "SELECT 
            s.id, 
            s.lrn, 
            s.first_name, 
            s.middle_name, 
            s.last_name, 
            s.grade_level, 
            s.section,
            a.date,
            a.status,
            a.remarks,
            u.name as recorded_by_name
        FROM 
            attendance a
        JOIN 
            students s ON a.student_id = s.id
        LEFT JOIN 
            users u ON a.recorded_by = u.id
        WHERE 
            " . implode(' AND ', $where_clauses) . "
        ORDER BY 
            s.grade_level, s.section, s.last_name, s.first_name, a.date";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);
$types = str_repeat('s', count($query_params));
mysqli_stmt_bind_param($stmt, $types, ...$query_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Group results by student and date
$students = [];
$dates = [];
$attendance_data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $student_id = $row['id'];
    $date = $row['date'];
    
    // Track unique students
    if (!isset($students[$student_id])) {
        $students[$student_id] = [
            'id' => $student_id,
            'lrn' => $row['lrn'],
            'name' => $row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name'],
            'grade_level' => $row['grade_level'],
            'section' => $row['section']
        ];
    }
    
    // Track unique dates
    if (!in_array($date, $dates)) {
        $dates[] = $date;
    }
    
    // Store attendance data
    $attendance_data[$student_id][$date] = [
        'status' => $row['status'],
        'remarks' => $row['remarks'],
        'recorded_by' => $row['recorded_by_name']
    ];
}

// Sort dates chronologically
sort($dates);

// Calculate attendance statistics
$attendance_stats = [
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'excused' => 0,
    'total_entries' => 0
];

foreach ($attendance_data as $student_attendances) {
    foreach ($student_attendances as $attendance) {
        $attendance_stats[$attendance['status']]++;
        $attendance_stats['total_entries']++;
    }
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'present':
            return 'bg-success';
        case 'absent':
            return 'bg-danger';
        case 'late':
            return 'bg-warning';
        case 'excused':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// Output the report
?>

<div class="report-header mb-4">
    <h4><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?> Attendance Report</h4>
    <?php if (!empty($grade_section)): ?>
    <p class="text-muted">Grade & Section: <?php echo htmlspecialchars($grade_section); ?></p>
    <?php else: ?>
    <p class="text-muted">All Grades & Sections</p>
    <?php endif; ?>
</div>

<?php if (empty($students)): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i> No attendance records found for the selected criteria.
</div>
<?php else: ?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="attendance-summary d-flex justify-content-center">
            <div class="mx-2 px-3 py-2 bg-success bg-opacity-10 border border-success rounded text-center">
                <h5 class="mb-0"><?php echo $attendance_stats['present']; ?></h5>
                <small>Present</small>
            </div>
            <div class="mx-2 px-3 py-2 bg-danger bg-opacity-10 border border-danger rounded text-center">
                <h5 class="mb-0"><?php echo $attendance_stats['absent']; ?></h5>
                <small>Absent</small>
            </div>
            <div class="mx-2 px-3 py-2 bg-warning bg-opacity-10 border border-warning rounded text-center">
                <h5 class="mb-0"><?php echo $attendance_stats['late']; ?></h5>
                <small>Late</small>
            </div>
            <div class="mx-2 px-3 py-2 bg-info bg-opacity-10 border border-info rounded text-center">
                <h5 class="mb-0"><?php echo $attendance_stats['excused']; ?></h5>
                <small>Excused</small>
            </div>
            <div class="mx-2 px-3 py-2 bg-secondary bg-opacity-10 border border-secondary rounded text-center">
                <h5 class="mb-0"><?php echo $attendance_stats['total_entries']; ?></h5>
                <small>Total Entries</small>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th rowspan="2" class="align-middle">Student</th>
                <th rowspan="2" class="align-middle">LRN</th>
                <th rowspan="2" class="align-middle">Grade-Section</th>
                <?php foreach ($dates as $date): ?>
                <th class="text-center"><?php echo date('d', strtotime($date)); ?></th>
                <?php endforeach; ?>
                <th colspan="4" class="text-center">Summary</th>
            </tr>
            <tr>
                <?php foreach ($dates as $date): ?>
                <th class="text-center small"><?php echo date('D', strtotime($date)); ?></th>
                <?php endforeach; ?>
                <th class="text-center bg-success bg-opacity-25">P</th>
                <th class="text-center bg-danger bg-opacity-25">A</th>
                <th class="text-center bg-warning bg-opacity-25">L</th>
                <th class="text-center bg-info bg-opacity-25">E</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['name']); ?></td>
                <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                <td><?php echo htmlspecialchars($student['grade_level'] . '-' . $student['section']); ?></td>
                
                <?php 
                $student_stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
                foreach ($dates as $date): 
                    $attendance = isset($attendance_data[$student['id']][$date]) ? $attendance_data[$student['id']][$date] : null;
                    if ($attendance) {
                        $student_stats[$attendance['status']]++;
                    }
                ?>
                <td class="text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo $attendance ? ucfirst($attendance['status']) . ($attendance['remarks'] ? ': ' . $attendance['remarks'] : '') : 'No record'; ?>">
                    <?php if ($attendance): ?>
                    <span class="badge rounded-pill <?php echo getStatusBadgeClass($attendance['status']); ?>">
                        <?php echo strtoupper($attendance['status'][0]); ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
                
                <!-- Summary cells -->
                <td class="text-center fw-bold"><?php echo $student_stats['present']; ?></td>
                <td class="text-center fw-bold"><?php echo $student_stats['absent']; ?></td>
                <td class="text-center fw-bold"><?php echo $student_stats['late']; ?></td>
                <td class="text-center fw-bold"><?php echo $student_stats['excused']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mt-4 text-end">
    <button type="button" class="btn btn-primary" id="printReport">
        <i class="fas fa-print me-2"></i> Print Report
    </button>
    <button type="button" class="btn btn-success" id="exportExcel">
        <i class="fas fa-file-excel me-2"></i> Export to Excel
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Print report functionality
    document.getElementById('printReport').addEventListener('click', function() {
        window.print();
    });
    
    // Export to Excel functionality (basic implementation)
    document.getElementById('exportExcel').addEventListener('click', function() {
        // This would normally use a library like SheetJS or call a server endpoint
        alert('Export to Excel functionality will be implemented.');
    });
});
</script>

<?php endif; ?> 
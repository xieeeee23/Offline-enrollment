<?php
$title = 'Sections List Report';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Get filter parameters (same as manage_sections.php)
$grade_level_filter = isset($_GET['grade_level']) ? cleanInput($_GET['grade_level']) : '';
$strand_filter = isset($_GET['strand']) ? cleanInput($_GET['strand']) : '';
$status_filter = isset($_GET['status']) ? cleanInput($_GET['status']) : '';

// Get all strands for dropdown
$query = "SELECT strand_code, strand_name FROM shs_strands ORDER BY strand_name";
$result = mysqli_query($conn, $query);
$strands = [];
while ($row = mysqli_fetch_assoc($result)) {
    $strands[] = $row;
}

// Get sections with student counts (same query as manage_sections.php)
$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM students WHERE section = s.name AND grade_level = s.grade_level) as student_count
          FROM sections s";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($grade_level_filter)) {
    $where_clauses[] = "s.grade_level = ?";
    $params[] = $grade_level_filter;
    $types .= 's';
}

if (!empty($strand_filter)) {
    $where_clauses[] = "s.strand = ?";
    $params[] = $strand_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

$query .= " ORDER BY s.grade_level, s.name";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sections = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sections[] = $row;
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Sections List Report</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Sections List</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light me-2" id="printButton">
                        <i class="fas fa-print me-1"></i> Print
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
                                <option value="Grade 11" <?= ($grade_level_filter === 'Grade 11') ? 'selected' : '' ?>>Grade 11</option>
                                <option value="Grade 12" <?= ($grade_level_filter === 'Grade 12') ? 'selected' : '' ?>>Grade 12</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="strand" class="form-label">Strand</label>
                            <select class="form-select" id="strand" name="strand">
                                <option value="">All Strands</option>
                                <?php foreach ($strands as $strand): ?>
                                    <option value="<?= $strand['strand_code'] ?>" <?= ($strand_filter === $strand['strand_code']) ? 'selected' : '' ?>>
                                        <?= $strand['strand_code'] ?> - <?= $strand['strand_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="Active" <?= ($status_filter === 'Active') ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= ($status_filter === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <a href="sections_list.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Printable area -->
                <div id="printableArea">
                    <h4 class="text-center mb-3 d-none d-print-block"><?= SYSTEM_NAME ?> - Sections List</h4>
                    <p class="text-muted text-center d-none d-print-block">
                        Generated on: <?= date('F j, Y') ?><br>
                        Filters: 
                        <?= !empty($grade_level_filter) ? "Grade: $grade_level_filter " : '' ?>
                        <?= !empty($strand_filter) ? "| Strand: $strand_filter " : '' ?>
                        <?= !empty($status_filter) ? "| Status: $status_filter" : '' ?>
                    </p>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Section Name</th>
                                    <th>Grade Level</th>
                                    <th>Strand</th>
                                    <th>Students</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sections)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No sections found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sections as $section): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($section['name']) ?></td>
                                            <td><?= htmlspecialchars($section['grade_level']) ?></td>
                                            <td><?= htmlspecialchars($section['strand']) ?></td>
                                            <td><?= $section['student_count'] ?></td>
                                            <td>
                                                <?= $section['student_count'] ?> / <?= $section['max_students'] ?>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <?php
                                                    $percentage = ($section['student_count'] / $section['max_students']) * 100;
                                                    $progress_class = ($percentage >= 100) ? 'bg-danger' : (($percentage >= 90) ? 'bg-warning' : 'bg-success');
                                                    ?>
                                                    <div class="progress-bar <?= $progress_class ?>" 
                                                         style="width: <?= min(100, $percentage) ?>%"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= ($section['status'] === 'Active') ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= htmlspecialchars($section['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted">
                Total sections: <?= count($sections) ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('printButton').addEventListener('click', function() {
    // Clone the printable area
    const printContent = document.getElementById('printableArea').cloneNode(true);
    
    // Create a new window
    const printWindow = window.open('', '_blank');
    
    // Write the print content
    printWindow.document.write(`
        <html>
            <head>
                <title>Sections List Report</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .progress { background-color: #e9ecef; }
                    .badge { padding: 5px; border-radius: 3px; }
                </style>
            </head>
            <body>
                ${printContent.innerHTML}
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() { window.close(); }, 100);
                    };
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?>
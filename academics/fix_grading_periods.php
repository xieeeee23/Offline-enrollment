<?php
$title = 'Fix Grading Periods';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit();
}

// Check if period_number column exists in grading_periods table
$check_column_query = "SHOW COLUMNS FROM grading_periods LIKE 'period_number'";
$column_exists = mysqli_query($conn, $check_column_query);
$column_exists = mysqli_num_rows($column_exists) > 0;

// If column doesn't exist, add it
if (!$column_exists) {
    $add_column_query = "ALTER TABLE grading_periods ADD COLUMN period_number INT NOT NULL DEFAULT 0 AFTER name";
    $add_column_result = mysqli_query($conn, $add_column_query);
    
    if ($add_column_result) {
        // Update existing records with sequential period numbers
        $get_periods_query = "SELECT id FROM grading_periods ORDER BY id";
        $periods_result = mysqli_query($conn, $get_periods_query);
        
        $period_number = 1;
        while ($period = mysqli_fetch_assoc($periods_result)) {
            $update_query = "UPDATE grading_periods SET period_number = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ii", $period_number, $period['id']);
            mysqli_stmt_execute($stmt);
            $period_number++;
        }
        
        $_SESSION['alert'] = showAlert('period_number column added to grading_periods table and existing records updated.', 'success');
    } else {
        $_SESSION['alert'] = showAlert('Error adding period_number column: ' . mysqli_error($conn), 'danger');
    }
} else {
    $_SESSION['alert'] = showAlert('period_number column already exists in grading_periods table.', 'info');
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>modules/academics/grading.php">Grading System</a></li>
        <li class="breadcrumb-item active"><?php echo $title; ?></li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])) echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-wrench me-2"></i> Grading Periods Table Fix</h5>
        </div>
        <div class="card-body">
            <h5>Current Table Structure:</h5>
            <pre class="bg-light p-3">
<?php
// Show the table structure
$show_table_query = "DESCRIBE grading_periods";
$table_structure = mysqli_query($conn, $show_table_query);

if ($table_structure) {
    echo "Table: grading_periods\n\n";
    echo "Column Name\t\tType\t\tNull\tKey\tDefault\tExtra\n";
    echo "--------------------------------------------------------------\n";
    
    while ($column = mysqli_fetch_assoc($table_structure)) {
        echo $column['Field'] . "\t\t" . 
             $column['Type'] . "\t\t" . 
             $column['Null'] . "\t" . 
             $column['Key'] . "\t" . 
             $column['Default'] . "\t" . 
             $column['Extra'] . "\n";
    }
} else {
    echo "Error retrieving table structure: " . mysqli_error($conn);
}
?>
            </pre>
            
            <h5 class="mt-4">Grading Periods Data:</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Period Number</th>
                            <?php if ($column_exists): ?>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>School Year</th>
                            <th>Is Current</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $periods_query = "SELECT * FROM grading_periods ORDER BY " . ($column_exists ? "period_number" : "id");
                        $periods_result = mysqli_query($conn, $periods_query);
                        
                        if ($periods_result && mysqli_num_rows($periods_result) > 0) {
                            while ($period = mysqli_fetch_assoc($periods_result)) {
                                echo "<tr>";
                                echo "<td>" . $period['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($period['name']) . "</td>";
                                echo "<td>" . (isset($period['period_number']) ? $period['period_number'] : 'N/A') . "</td>";
                                
                                if ($column_exists) {
                                    echo "<td>" . (isset($period['start_date']) ? $period['start_date'] : 'N/A') . "</td>";
                                    echo "<td>" . (isset($period['end_date']) ? $period['end_date'] : 'N/A') . "</td>";
                                    echo "<td>" . (isset($period['school_year']) ? $period['school_year'] : 'N/A') . "</td>";
                                    echo "<td>" . (isset($period['is_current']) ? ($period['is_current'] ? 'Yes' : 'No') : 'N/A') . "</td>";
                                }
                                
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='" . ($column_exists ? "7" : "3") . "'>No grading periods found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                <a href="<?php echo $relative_path; ?>modules/academics/grading.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Grading System
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
<?php
$title = 'Auto Generate School Years';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}

// Number of future years to generate
$future_years = 5;
$generated_count = 0;
$log_messages = [];

// Process auto generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_years'])) {
    $future_years = isset($_POST['future_years']) ? (int)$_POST['future_years'] : 5;
    
    // Get current year
    $current_year = (int)date('Y');
    
    // Check if school_years table exists
    $query = "SHOW TABLES LIKE 'school_years'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        // Create school_years table if it doesn't exist
        $create_table_query = "CREATE TABLE school_years (
            id INT AUTO_INCREMENT PRIMARY KEY,
            year_start INT NOT NULL,
            year_end INT NOT NULL,
            school_year VARCHAR(20) NOT NULL UNIQUE,
            is_current TINYINT(1) DEFAULT 0,
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (mysqli_query($conn, $create_table_query)) {
            $log_messages[] = "Created school_years table successfully.";
        } else {
            $log_messages[] = "Error creating school_years table: " . mysqli_error($conn);
            $_SESSION['alert'] = showAlert('Error creating school_years table: ' . mysqli_error($conn), 'danger');
        }
    }
    
    // Get existing school years
    $existing_years = [];
    $query = "SELECT school_year FROM school_years";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $existing_years[] = $row['school_year'];
        }
    }
    
    // Generate school years
    for ($i = 0; $i <= $future_years; $i++) {
        $year_start = $current_year + $i;
        $year_end = $year_start + 1;
        $school_year = $year_start . '-' . $year_end;
        
        // Check if this school year already exists
        if (!in_array($school_year, $existing_years)) {
            $query = "INSERT INTO school_years (year_start, year_end, school_year, is_current, status) 
                      VALUES (?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $query);
            $is_current = ($year_start == $current_year) ? 1 : 0;
            $status = 'Active';
            
            mysqli_stmt_bind_param($stmt, "iisss", $year_start, $year_end, $school_year, $is_current, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $generated_count++;
                $log_messages[] = "Generated school year: {$school_year}";
                
                // Log action
                $log_desc = "Auto-generated school year: {$school_year}";
                logAction($_SESSION['user_id'], 'CREATE', $log_desc);
            } else {
                $log_messages[] = "Error generating school year {$school_year}: " . mysqli_error($conn);
            }
        } else {
            $log_messages[] = "School year {$school_year} already exists.";
        }
    }
    
    // Set the current year
    $current_school_year = $current_year . '-' . ($current_year + 1);
    $query = "UPDATE school_years SET is_current = 0";
    mysqli_query($conn, $query);
    
    $query = "UPDATE school_years SET is_current = 1 WHERE school_year = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $current_school_year);
    mysqli_stmt_execute($stmt);
    
    if ($generated_count > 0) {
        $_SESSION['alert'] = showAlert("Successfully generated {$generated_count} new school years.", 'success');
    } else {
        $_SESSION['alert'] = showAlert("No new school years were generated. All required years already exist.", 'info');
    }
}

// Get existing school years for display
$school_years = [];
$query = "SELECT * FROM school_years ORDER BY year_start DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $school_years[] = $row;
    }
}
?>

<div class="container-fluid">
    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>
    
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Auto Generate School Years</h1>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Generation Parameters</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="mb-3">
                            <label for="future_years" class="form-label">Number of Future Years to Generate</label>
                            <input type="number" class="form-control" id="future_years" name="future_years" value="5" min="1" max="20" required>
                            <small class="form-text text-muted">This will generate school years from the current year up to the specified number of years in the future.</small>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="generate_years" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-1"></i> Generate School Years
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">How It Works</h5>
                </div>
                <div class="card-body">
                    <p>The auto-generation process works as follows:</p>
                    <ol>
                        <li>Creates a school_years table if it doesn't exist</li>
                        <li>Generates school years starting from the current year</li>
                        <li>Adds the specified number of future years</li>
                        <li>Sets the current school year based on the current date</li>
                        <li>Skips years that already exist in the database</li>
                    </ol>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i> This tool ensures that your system always has school years available for selection in forms and reports.
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($log_messages)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Generation Log</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p><strong>Summary:</strong> <?php echo $generated_count; ?> new school years generated.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($log_messages as $index => $message): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $message; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Existing School Years</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>School Year</th>
                                    <th>Status</th>
                                    <th>Current</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($school_years)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No school years found. Click "Generate School Years" to create them.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($school_years as $year): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($year['school_year']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $year['status'] == 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo htmlspecialchars($year['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($year['is_current']): ?>
                                                <span class="badge bg-primary">Current</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($year['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
<?php
// Calculate the relative path to the includes directory
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect($relative_path . 'login.php');
}

// Check if user has access to this module
if (!hasAccess('registrar')) {
    $_SESSION['alert'] = showAlert('You do not have access to this module.', 'danger');
    redirect($relative_path);
}

// Get data from sections table
$sections = [];
$query = "SELECT * FROM sections ORDER BY name LIMIT 100";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sections[] = $row;
    }
}

// Get education levels
$education_levels = [];
$query = "SELECT * FROM education_levels WHERE status = 'Active' ORDER BY display_order";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $education_levels[$row['id']] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Sections Table Test</h1>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Sections Database Structure</h5>
                    <a href="<?php echo $relative_path; ?>modules/registrar/test_dropdowns.php" class="btn btn-sm btn-light">
                        <i class="fas fa-vial me-1"></i> Test Dropdowns
                    </a>
                </div>
                <div class="card-body">
                    <h5>Database Connection</h5>
                    <?php if ($conn): ?>
                        <div class="alert alert-success mb-4">
                            <i class="fas fa-check-circle me-2"></i> Database connection is working properly.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i> Database connection failed!
                        </div>
                    <?php endif; ?>
                    
                    <h5>Table Structure</h5>
                    <?php
                    // Check if sections table exists
                    $query = "SHOW TABLES LIKE 'sections'";
                    $result = mysqli_query($conn, $query);
                    if (mysqli_num_rows($result) > 0): ?>
                        <div class="alert alert-success mb-4">
                            <i class="fas fa-check-circle me-2"></i> Sections table exists.
                        </div>
                        
                        <?php
                        // Get table structure
                        $query = "DESCRIBE sections";
                        $result = mysqli_query($conn, $query);
                        if ($result && mysqli_num_rows($result) > 0): ?>
                            <h6>Sections Table Structure:</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Field</th>
                                            <th>Type</th>
                                            <th>Null</th>
                                            <th>Key</th>
                                            <th>Default</th>
                                            <th>Extra</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['Field']); ?></td>
                                                <td><?php echo htmlspecialchars($row['Type']); ?></td>
                                                <td><?php echo htmlspecialchars($row['Null']); ?></td>
                                                <td><?php echo htmlspecialchars($row['Key']); ?></td>
                                                <td><?php echo htmlspecialchars($row['Default'] ?? 'NULL'); ?></td>
                                                <td><?php echo htmlspecialchars($row['Extra']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-4">
                                <i class="fas fa-exclamation-triangle me-2"></i> Could not retrieve sections table structure.
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="alert alert-danger mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i> Sections table does not exist!
                        </div>
                    <?php endif; ?>
                    
                    <h5>Education Levels</h5>
                    <?php if (!empty($education_levels)): ?>
                        <div class="alert alert-success mb-4">
                            <i class="fas fa-check-circle me-2"></i> Found <?php echo count($education_levels); ?> education levels.
                        </div>
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Level Name</th>
                                        <th>Min Grade</th>
                                        <th>Max Grade</th>
                                        <th>Display Order</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($education_levels as $level): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($level['id']); ?></td>
                                            <td><?php echo htmlspecialchars($level['level_name']); ?></td>
                                            <td><?php echo htmlspecialchars($level['min_grade']); ?></td>
                                            <td><?php echo htmlspecialchars($level['max_grade']); ?></td>
                                            <td><?php echo htmlspecialchars($level['display_order']); ?></td>
                                            <td><?php echo htmlspecialchars($level['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i> No education levels found.
                        </div>
                    <?php endif; ?>
                    
                    <h5>Sections Data</h5>
                    <?php if (!empty($sections)): ?>
                        <div class="alert alert-success mb-4">
                            <i class="fas fa-check-circle me-2"></i> Found <?php echo count($sections); ?> sections.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Grade Level</th>
                                        <th>Room</th>
                                        <th>School Year</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sections as $section): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($section['id']); ?></td>
                                            <td><?php echo htmlspecialchars($section['name']); ?></td>
                                            <td><?php echo htmlspecialchars($section['grade_level']); ?></td>
                                            <td><?php echo htmlspecialchars($section['room'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($section['school_year']); ?></td>
                                            <td><?php echo htmlspecialchars($section['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> No sections found. You may need to add sections first.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
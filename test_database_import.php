<?php
// Test file for database import functionality
$relative_path = './';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin role
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Test Database Import</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Test Database Import</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-1"></i> Test Import Form
                </div>
                <div class="card-body">
                    <p>Use this form to test the database import functionality.</p>
                    
                    <form method="post" action="modules/admin/database.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="sql_file" class="form-label">SQL File</label>
                            <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql" required>
                            <div class="form-text">Select a valid SQL file to import.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="import_db" class="btn btn-success">
                                <i class="fas fa-upload me-1"></i> Test Import Database
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i> 
                        <strong>Note:</strong> This is a test form that submits directly to the database.php script. Use this to verify the import functionality is working correctly.
                    </div>
                    
                    <div class="mt-3">
                        <a href="database.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i> Go to Database Management
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 
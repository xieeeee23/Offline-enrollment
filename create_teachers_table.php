<?php
// Set correct relative path
$title = 'Create Teachers Table';
$relative_path = './';
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Create teachers table function
function create_teachers_table($conn) {
    $success = true;
    $messages = [];
    
    // Create teachers table if not exists
    $query = "CREATE TABLE IF NOT EXISTS teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        department VARCHAR(100) DEFAULT NULL,
        subject VARCHAR(100) DEFAULT NULL,
        grade_level VARCHAR(50) DEFAULT NULL,
        qualification TEXT DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if (!mysqli_query($conn, $query)) {
        $success = false;
        $messages[] = [
            'type' => 'danger',
            'text' => 'Error creating teachers table: ' . mysqli_error($conn)
        ];
    } else {
        $messages[] = [
            'type' => 'success',
            'text' => 'Teachers table created successfully.'
        ];
    }
    
    return [
        'success' => $success,
        'messages' => $messages
    ];
}

// Handle creation request
if (isset($_POST['create_table']) || isset($_GET['auto_create'])) {
    $result = create_teachers_table($conn);
    
    foreach ($result['messages'] as $message) {
        $_SESSION['alert'] = showAlert($message['text'], $message['type']);
    }
    
    if (isset($_GET['auto_create'])) {
        redirect('modules/teacher/teachers.php');
    } else {
        redirect('create_teachers_table.php');
    }
}

// Include the header manually
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - SHS Enrollment System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h1 class="mb-4">Create Teachers Table</h1>
                
                <?php if (isset($_SESSION['alert'])): ?>
                    <?php echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Teachers Table Setup</h5>
                    </div>
                    <div class="card-body">
                        <p>Click the button below to create the teachers table in the database.</p>
                        <form method="post" action="">
                            <button type="submit" name="create_table" class="btn btn-primary">Create Teachers Table</button>
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Requirement Types Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3>Create Requirement Types Table</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Include necessary files
                        require_once 'includes/config.php';
                        require_once 'includes/functions.php';

                        // Check if user is logged in and has admin role
                        if (!isset($_SESSION['user_id'])) {
                            echo '<div class="alert alert-danger">You must be logged in to access this page.</div>';
                            echo '<a href="login.php" class="btn btn-primary">Go to Login Page</a>';
                            exit;
                        }

                        // SQL to create requirement_types table
                        $sql = "CREATE TABLE IF NOT EXISTS `requirement_types` (
                            `id` INT AUTO_INCREMENT PRIMARY KEY,
                            `name` VARCHAR(255) NOT NULL,
                            `description` TEXT,
                            `is_required` TINYINT(1) DEFAULT 1,
                            `is_active` TINYINT(1) DEFAULT 1,
                            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

                        // Execute query
                        if (mysqli_query($conn, $sql)) {
                            echo '<div class="alert alert-success">Requirement types table created successfully.</div>';
                            
                            // Check if requirement types already exist
                            $check_sql = "SELECT COUNT(*) as count FROM `requirement_types`";
                            $result = mysqli_query($conn, $check_sql);
                            $row = mysqli_fetch_assoc($result);
                            
                            if ($row['count'] > 0) {
                                echo '<div class="alert alert-info">Requirement types already exist in the table. No new types added.</div>';
                            } else {
                                // Insert default requirement types
                                $default_types = [
                                    ['Document', 'Official documents required for enrollment', 1],
                                    ['Payment', 'Payment receipts and financial requirements', 1],
                                    ['Form', 'Forms that need to be filled out', 1],
                                    ['Other', 'Other miscellaneous requirements', 1]
                                ];
                                
                                $insert_sql = "INSERT INTO `requirement_types` (`name`, `description`, `is_required`) VALUES (?, ?, ?)";
                                $stmt = mysqli_prepare($conn, $insert_sql);
                                
                                if ($stmt) {
                                    mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $is_required);
                                    
                                    $success_count = 0;
                                    foreach ($default_types as $type) {
                                        $name = $type[0];
                                        $description = $type[1];
                                        $is_required = $type[2];
                                        
                                        if (mysqli_stmt_execute($stmt)) {
                                            $success_count++;
                                        }
                                    }
                                    
                                    echo '<div class="alert alert-success">' . $success_count . ' default requirement types added successfully.</div>';
                                    mysqli_stmt_close($stmt);
                                } else {
                                    echo '<div class="alert alert-danger">Error preparing statement: ' . mysqli_error($conn) . '</div>';
                                }
                            }
                        } else {
                            echo '<div class="alert alert-danger">Error creating requirement types table: ' . mysqli_error($conn) . '</div>';
                        }

                        // Close connection
                        mysqli_close($conn);
                        ?>
                        <div class="mt-3">
                            <a href="modules/registrar/requirements.php" class="btn btn-primary">Go to Requirements Page</a>
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
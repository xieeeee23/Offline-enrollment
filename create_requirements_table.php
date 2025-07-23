<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Requirements Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3>Create Requirements Table</h3>
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

                        // SQL to create requirements table
                        $sql = "CREATE TABLE IF NOT EXISTS `requirements` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `type` enum('document','payment','form','other') NOT NULL DEFAULT 'document',
                            `program` varchar(50) NOT NULL DEFAULT 'all',
                            `description` text DEFAULT NULL,
                            `is_required` tinyint(1) NOT NULL DEFAULT 1,
                            `is_active` tinyint(1) NOT NULL DEFAULT 1,
                            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

                        // Execute query
                        if (mysqli_query($conn, $sql)) {
                            echo '<div class="alert alert-success">Requirements table created successfully.</div>';
                            
                            // Check if requirements already exist
                            $check_sql = "SELECT COUNT(*) as count FROM `requirements`";
                            $result = mysqli_query($conn, $check_sql);
                            $row = mysqli_fetch_assoc($result);
                            
                            if ($row['count'] > 0) {
                                echo '<div class="alert alert-info">Requirements already exist in the table. No new requirements added.</div>';
                            } else {
                                // Insert default requirements
                                $default_requirements = [
                                    ['Birth Certificate', 'document', 'all', 'Original or certified true copy of birth certificate', 1],
                                    ['Report Card / Form 138', 'document', 'all', 'Previous school year report card', 1],
                                    ['Good Moral Certificate', 'document', 'all', 'Certificate of good moral character from previous school', 1],
                                    ['Medical Certificate', 'document', 'all', 'Recent medical certificate from licensed physician', 1],
                                    ['2x2 ID Picture', 'document', 'all', 'Recent 2x2 ID picture with white background', 1],
                                    ['Enrollment Form', 'form', 'all', 'Completed and signed enrollment form', 1],
                                    ['Parent/Guardian ID', 'document', 'all', 'Valid ID of parent or guardian', 1]
                                ];
                                
                                $insert_sql = "INSERT INTO `requirements` (`name`, `type`, `program`, `description`, `is_required`) VALUES (?, ?, ?, ?, ?)";
                                $stmt = mysqli_prepare($conn, $insert_sql);
                                
                                if ($stmt) {
                                    mysqli_stmt_bind_param($stmt, "ssssi", $name, $type, $program, $description, $is_required);
                                    
                                    $success_count = 0;
                                    foreach ($default_requirements as $req) {
                                        $name = $req[0];
                                        $type = $req[1];
                                        $program = $req[2];
                                        $description = $req[3];
                                        $is_required = $req[4];
                                        
                                        if (mysqli_stmt_execute($stmt)) {
                                            $success_count++;
                                        }
                                    }
                                    
                                    echo '<div class="alert alert-success">' . $success_count . ' default requirements added successfully.</div>';
                                    mysqli_stmt_close($stmt);
                                } else {
                                    echo '<div class="alert alert-danger">Error preparing statement: ' . mysqli_error($conn) . '</div>';
                                }
                            }
                        } else {
                            echo '<div class="alert alert-danger">Error creating requirements table: ' . mysqli_error($conn) . '</div>';
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
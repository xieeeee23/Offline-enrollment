<?php
// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirectToPage('../../dashboard.php');
    exit;
}

// Get requirement details
if (isset($_GET['action']) && $_GET['action'] === 'get') {
    // No longer requiring X-Requested-With header for easier testing
    /*
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
    */
    
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid requirement ID']);
        exit;
    }
    
    $id = (int) $_GET['id'];
    
    $query = "SELECT * FROM requirements WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Requirement not found']);
    }
    exit;
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Add new requirement
    if ($action === 'add') {
        // Debug incoming data
        error_log('Adding new requirement - POST data: ' . json_encode($_POST));
        
        $name = cleanInput($_POST['requirement_name']);
        $type = cleanInput($_POST['requirement_type']);
        $program = cleanInput($_POST['required_for']);
        $description = cleanInput($_POST['requirement_description'] ?? '');
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        
        // Debug cleaned data
        error_log('Cleaned requirement data: ' . json_encode([
            'name' => $name,
            'type' => $type,
            'program' => $program,
            'description' => $description,
            'is_required' => $is_required
        ]));
        
        if (empty($name) || empty($type) || empty($program)) {
            error_log('Validation error: Missing required fields');
            $_SESSION['alert'] = showAlert('Name, type, and program are required fields.', 'danger');
            redirectToPage('requirements.php');
            exit;
        }
        
        // Check if requirement_types table exists, create if not
        $table_check_query = "SHOW TABLES LIKE 'requirement_types'";
        $table_check_result = mysqli_query($conn, $table_check_query);
        
        if (mysqli_num_rows($table_check_result) == 0) {
            error_log('requirement_types table does not exist, creating it now');
            // Create table
            $create_table_query = "CREATE TABLE requirement_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                is_required TINYINT(1) DEFAULT 1,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            if (!mysqli_query($conn, $create_table_query)) {
                error_log('Failed to create requirement_types table: ' . mysqli_error($conn));
                $_SESSION['alert'] = showAlert('Error creating requirement_types table: ' . mysqli_error($conn), 'danger');
                redirectToPage('requirements.php');
                exit;
            } else {
                error_log('Successfully created requirement_types table');
            }
        }
        
        $query = "INSERT INTO requirements (name, type, program, description, is_required, is_active) 
                  VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            error_log('Failed to prepare statement: ' . mysqli_error($conn));
            $_SESSION['alert'] = showAlert('Error preparing statement: ' . mysqli_error($conn), 'danger');
            redirectToPage('requirements.php');
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $type, $program, $description, $is_required);
        
        if (mysqli_stmt_execute($stmt)) {
            // Get the ID of the newly inserted requirement
            $new_id = mysqli_insert_id($conn);
            error_log('Successfully added requirement with ID: ' . $new_id);
            
            // Update the student_requirements table structure
            updateStudentRequirementsTable($name);
            
            // Log the action with the new ID for debugging
            logAction($_SESSION['user_id'], 'CREATE', 'Added new requirement: ' . $name . ' (ID: ' . $new_id . ')');
            
            // Set success message
            $_SESSION['alert'] = showAlert('Requirement added successfully. ID: ' . $new_id, 'success');
        } else {
            // Log the error for debugging
            error_log('Error adding requirement: ' . mysqli_error($conn));
            $_SESSION['alert'] = showAlert('Error adding requirement: ' . mysqli_error($conn), 'danger');
        }
        
        // Debug info
        error_log('Redirecting to requirements.php after adding requirement: ' . $name);
        
        redirectToPage('requirements.php');
        exit;
    }
    
    // Add new requirement type
    if ($action === 'add_type') {
        $type_name = cleanInput($_POST['requirement_type_name']);
        $description = cleanInput($_POST['requirement_type_description'] ?? '');
        $is_required = isset($_POST['type_is_required']) ? 1 : 0;
        
        if (empty($type_name)) {
            $_SESSION['alert'] = showAlert('Type name is required.', 'danger');
            redirectToPage('requirements.php');
            exit;
        }
        
        // Check if type already exists
        $check_query = "SELECT id FROM requirement_types WHERE name = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "s", $type_name);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $_SESSION['alert'] = showAlert('A requirement type with this name already exists.', 'warning');
                redirectToPage('requirements.php');
                exit;
            }
        }
        
        // Check if requirement_types table exists, create if not
        $table_check_query = "SHOW TABLES LIKE 'requirement_types'";
        $table_check_result = mysqli_query($conn, $table_check_query);
        
        if (mysqli_num_rows($table_check_result) == 0) {
            // Create table
            $create_table_query = "CREATE TABLE requirement_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                is_required TINYINT(1) DEFAULT 1,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            if (!mysqli_query($conn, $create_table_query)) {
                $_SESSION['alert'] = showAlert('Error creating requirement_types table: ' . mysqli_error($conn), 'danger');
                redirectToPage('requirements.php');
                exit;
            }
        }
        
        // Insert the new type
        $query = "INSERT INTO requirement_types (name, description, is_required, is_active) 
                  VALUES (?, ?, ?, 1)";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssi", $type_name, $description, $is_required);
            
            if (mysqli_stmt_execute($stmt)) {
                logAction($_SESSION['user_id'], 'CREATE', 'Added new requirement type: ' . $type_name);
                $_SESSION['alert'] = showAlert('Requirement type added successfully.', 'success');
            } else {
                $_SESSION['alert'] = showAlert('Error adding requirement type: ' . mysqli_error($conn), 'danger');
            }
        } else {
            $_SESSION['alert'] = showAlert('Error preparing statement: ' . mysqli_error($conn), 'danger');
        }
        
        redirectToPage('requirements.php');
        exit;
    }
    
    // Edit requirement
    if ($action === 'edit') {
        if (!isset($_POST['requirement_id']) || !is_numeric($_POST['requirement_id'])) {
            // Check if this is an AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => 'Invalid requirement ID']);
                exit;
            } else {
                $_SESSION['alert'] = showAlert('Invalid requirement ID', 'danger');
                redirectToPage('requirements.php');
                exit;
            }
        }
        
        $id = (int) $_POST['requirement_id'];
        $name = cleanInput($_POST['requirement_name']);
        $type = cleanInput($_POST['requirement_type']);
        $program = cleanInput($_POST['required_for']);
        $description = cleanInput($_POST['requirement_description'] ?? '');
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        
        if (empty($name) || empty($type) || empty($program)) {
            // Check if this is an AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => 'Name, type, and program are required fields.']);
                exit;
            } else {
                $_SESSION['alert'] = showAlert('Name, type, and program are required fields.', 'danger');
                redirectToPage('requirements.php');
                exit;
            }
        }
        
        $query = "UPDATE requirements SET name = ?, type = ?, program = ?, description = ?, is_required = ? 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssii", $name, $type, $program, $description, $is_required, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            logAction($_SESSION['user_id'], 'UPDATE', 'Updated requirement ID: ' . $id);
            
            // Check if this is an AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['success' => true, 'message' => 'Requirement updated successfully.']);
                exit;
            } else {
                $_SESSION['alert'] = showAlert('Requirement updated successfully.', 'success');
            }
        } else {
            // Check if this is an AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => 'Error updating requirement: ' . mysqli_error($conn)]);
                exit;
            } else {
                $_SESSION['alert'] = showAlert('Error updating requirement: ' . mysqli_error($conn), 'danger');
            }
        }
        
        // Only redirect if not an AJAX request
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            redirectToPage('requirements.php');
            exit;
        }
    }
    
    // Batch upload requirements
    if ($action === 'batch_upload') {
        if (!isset($_FILES['requirements_file']) || $_FILES['requirements_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['alert'] = showAlert('No file uploaded or error in upload.', 'danger');
            redirectToPage('requirements.php');
            exit;
        }
        
        $file = $_FILES['requirements_file']['tmp_name'];
        $overwrite = isset($_POST['overwrite_existing']) ? true : false;
        
        // Read CSV file
        $handle = fopen($file, 'r');
        if (!$handle) {
            $_SESSION['alert'] = showAlert('Error opening file.', 'danger');
            redirectToPage('requirements.php');
            exit;
        }
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $success_count = 0;
        $error_count = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 4) {
                $error_count++;
                continue;
            }
            
            $name = cleanInput($data[0]);
            $type = cleanInput($data[1]);
            $program = cleanInput($data[2]);
            $description = cleanInput($data[3]);
            $is_required = isset($data[4]) && strtolower($data[4]) === 'yes' ? 1 : 0;
            
            if (empty($name) || empty($type) || empty($program)) {
                $error_count++;
                continue;
            }
            
            // Check if requirement already exists
            $check_query = "SELECT id FROM requirements WHERE name = ? AND type = ? AND program = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "sss", $name, $type, $program);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                if ($overwrite) {
                    $row = mysqli_fetch_assoc($check_result);
                    $id = $row['id'];
                    
                    $update_query = "UPDATE requirements SET description = ?, is_required = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "sii", $description, $is_required, $id);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    // Skip if not overwriting
                    continue;
                }
            } else {
                $insert_query = "INSERT INTO requirements (name, type, program, description, is_required, is_active) 
                                VALUES (?, ?, ?, ?, ?, 1)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "ssssi", $name, $type, $program, $description, $is_required);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        fclose($handle);
        
        logAction($_SESSION['user_id'], 'IMPORT', 'Batch imported requirements. Success: ' . $success_count . ', Errors: ' . $error_count);
        $_SESSION['alert'] = showAlert('Batch upload completed. ' . $success_count . ' requirements added/updated successfully. ' . $error_count . ' errors.', 'success');
        redirectToPage('requirements.php');
        exit;
    }
}

// Process GET actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Delete requirement
    if ($action === 'delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = (int) $_GET['id'];
        
        // Get the requirement name before deleting it
        $get_req_query = "SELECT name FROM requirements WHERE id = ?";
        $get_req_stmt = mysqli_prepare($conn, $get_req_query);
        mysqli_stmt_bind_param($get_req_stmt, "i", $id);
        mysqli_stmt_execute($get_req_stmt);
        $get_req_result = mysqli_stmt_get_result($get_req_stmt);
        $req_data = mysqli_fetch_assoc($get_req_result);
        
        if ($req_data) {
            // Create column name from requirement name
            $requirement_name = $req_data['name'];
            $column_name = str_replace(' ', '_', $requirement_name);
            $column_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $column_name);
            $column_name = strtolower($column_name);
            $file_column = $column_name . '_file';
            
            // Start a transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Delete the requirement from the requirements table
                $query = "DELETE FROM requirements WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Failed to delete requirement: " . mysqli_error($conn));
                }
                
                // Check if we need to update the student_requirements table
                // First check if the column exists
                $column_check_query = "SHOW COLUMNS FROM student_requirements LIKE '$column_name'";
                $column_check_result = mysqli_query($conn, $column_check_query);
                
                if (mysqli_num_rows($column_check_result) > 0) {
                    // Column exists, set all values to 0 (not submitted)
                    $update_query = "UPDATE student_requirements SET $column_name = 0 WHERE $column_name = 1";
                    if (!mysqli_query($conn, $update_query)) {
                        throw new Exception("Failed to update student requirements: " . mysqli_error($conn));
                    }
                    
                    // Check if file column exists
                    $file_column_check_query = "SHOW COLUMNS FROM student_requirements LIKE '$file_column'";
                    $file_column_check_result = mysqli_query($conn, $file_column_check_query);
                    
                    if (mysqli_num_rows($file_column_check_result) > 0) {
                        // Get all files that need to be deleted
                        $files_query = "SELECT student_id, $file_column FROM student_requirements WHERE $file_column IS NOT NULL AND $file_column != ''";
                        $files_result = mysqli_query($conn, $files_query);
                        
                        while ($file_row = mysqli_fetch_assoc($files_result)) {
                            $file_path = '../../uploads/requirements/' . $file_row['student_id'] . '/' . $file_row[$file_column];
                            if (file_exists($file_path)) {
                                unlink($file_path);
                            }
                        }
                        
                        // Clear all file references
                        $update_file_query = "UPDATE student_requirements SET $file_column = NULL";
                        if (!mysqli_query($conn, $update_file_query)) {
                            throw new Exception("Failed to clear file references: " . mysqli_error($conn));
                        }
                    }
                }
                
                // Commit the transaction
                mysqli_commit($conn);
                
                logAction($_SESSION['user_id'], 'DELETE', 'Deleted requirement ID: ' . $id . ' (' . $requirement_name . ')');
                $_SESSION['alert'] = showAlert('Requirement deleted successfully.', 'success');
            } catch (Exception $e) {
                // Rollback the transaction
                mysqli_rollback($conn);
                
                error_log("Error in requirement deletion: " . $e->getMessage());
                $_SESSION['alert'] = showAlert('Error: ' . $e->getMessage(), 'danger');
            }
        } else {
            $_SESSION['alert'] = showAlert('Requirement not found.', 'warning');
        }
        
        redirectToPage('requirements.php');
        exit;
    }
    
    // Toggle requirement status
    if ($action === 'toggle_status' && isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['status'])) {
        $id = (int) $_GET['id'];
        $status = (int) $_GET['status'];
        
        $query = "UPDATE requirements SET is_active = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $status, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $status_text = $status ? 'activated' : 'deactivated';
            logAction($_SESSION['user_id'], 'UPDATE', 'Requirement ID: ' . $id . ' ' . $status_text);
            $_SESSION['alert'] = showAlert('Requirement ' . $status_text . ' successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error updating requirement status: ' . mysqli_error($conn), 'danger');
        }
        
        redirectToPage('requirements.php');
        exit;
    }
}

// Default redirect if no action is taken
redirectToPage('requirements.php');
exit;

// Helper function for redirects
function redirectToPage($page) {
    // Determine the correct relative path
    $script_path = $_SERVER['SCRIPT_NAME'];
    $relative_path = '';
    
    // Debug the current script path
    error_log('Current script path: ' . $script_path);
    
    // If we're in a subdirectory like /modules/registrar/
    if (strpos($script_path, '/modules/registrar/') !== false || 
        strpos($script_path, '\\modules\\registrar\\') !== false) {
        $relative_path = '../../';
    }
    
    // Debug the determined relative path
    error_log('Determined relative path: ' . $relative_path);
    
    // Add relative path if the page doesn't start with http or /
    if (strpos($page, 'http') !== 0 && strpos($page, '/') !== 0) {
        // If it's a relative path like 'requirements.php', add the proper directory path
        if (strpos($page, '../') === false && strpos($page, './') === false) {
            if ($page === 'requirements.php') {
                $page = $relative_path . 'modules/registrar/' . $page;
                error_log('Redirecting to requirements.php with path: ' . $page);
            } else if ($page === 'dashboard.php') {
                $page = $relative_path . $page;
                error_log('Redirecting to dashboard.php with path: ' . $page);
            } else {
                $page = $relative_path . 'modules/registrar/' . $page;
                error_log('Redirecting to other page with path: ' . $page);
            }
        }
    }
    
    // Check if we need to preserve student_id parameter
    if (isset($_POST['student_id']) || isset($_GET['student_id'])) {
        $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : $_GET['student_id'];
        // Check if the page already contains a query string
        if (strpos($page, '?') !== false) {
            $page .= '&student_id=' . $student_id;
        } else {
            $page .= '?student_id=' . $student_id;
        }
    }
    
    // Add refresh parameter for requirements.php to force table refresh
    if (strpos($page, 'requirements.php') !== false) {
        if (strpos($page, '?') !== false) {
            $page .= '&refresh=' . time(); // Add timestamp to prevent caching
        } else {
            $page .= '?refresh=' . time();
        }
    }
    
    // Debug the final redirect URL
    error_log('Final redirect URL: ' . $page);
    
    header('Location: ' . $page);
    exit;
}

// Function to update the student_requirements table structure when a new requirement is added
function updateStudentRequirementsTable($requirement_name) {
    global $conn;
    
    // Create a valid column name from the requirement name - FIX: Ensure proper conversion
    // First replace spaces with underscores, then remove any non-alphanumeric characters
    $column_name = str_replace(' ', '_', $requirement_name);
    $column_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $column_name);
    $column_name = strtolower($column_name);
    
    // Debug the column name creation
    error_log('Requirement name: ' . $requirement_name);
    error_log('Generated column name: ' . $column_name);
    
    // Check if the student_requirements table exists
    $table_check_query = "SHOW TABLES LIKE 'student_requirements'";
    $table_check_result = mysqli_query($conn, $table_check_query);
    
    if (mysqli_num_rows($table_check_result) == 0) {
        error_log('student_requirements table does not exist');
        return false;
    }
    
    // Check if the column already exists
    $column_check_query = "SHOW COLUMNS FROM student_requirements LIKE '$column_name'";
    $column_check_result = mysqli_query($conn, $column_check_query);
    
    if (mysqli_num_rows($column_check_result) == 0) {
        // Add the status column
        $alter_query = "ALTER TABLE student_requirements ADD COLUMN $column_name TINYINT(1) DEFAULT 0";
        if (!mysqli_query($conn, $alter_query)) {
            error_log('Error adding column ' . $column_name . ': ' . mysqli_error($conn));
            return false;
        } else {
            error_log('Added column ' . $column_name . ' successfully');
        }
    }
    
    // Check if the file column exists
    $file_column = $column_name . '_file';
    $file_column_check_query = "SHOW COLUMNS FROM student_requirements LIKE '$file_column'";
    $file_column_check_result = mysqli_query($conn, $file_column_check_query);
    
    if (mysqli_num_rows($file_column_check_result) == 0) {
        // Add the file column
        $alter_query = "ALTER TABLE student_requirements ADD COLUMN $file_column VARCHAR(255) DEFAULT NULL";
        if (!mysqli_query($conn, $alter_query)) {
            error_log('Error adding file column ' . $file_column . ': ' . mysqli_error($conn));
            return false;
        } else {
            error_log('Added file column ' . $file_column . ' successfully');
        }
    }
    
    return true;
}
?> 
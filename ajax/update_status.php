<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!checkAccess()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if required parameters are provided
if (!isset($_GET['type']) || !isset($_GET['id']) || !isset($_GET['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Get parameters
$type = cleanInput($_GET['type']);
$id = (int)$_GET['id'];
$status = cleanInput($_GET['status']);

// Validate status
if ($status !== 'Active' && $status !== 'Inactive') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Update status based on type
$success = false;
$message = '';

switch ($type) {
    case 'student':
        // Check if user has permission to update student status
        if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'registrar') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You do not have permission to update student status']);
            exit;
        }
        
        // Update student status
        $query = "UPDATE students SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        $success = mysqli_stmt_execute($stmt);
        
        if ($success) {
            // Log the action
            logAction($_SESSION['user_id'], 'UPDATE', "Updated student ID {$id} status to {$status}");
            $message = "Student status updated to {$status}";
        } else {
            $message = "Failed to update student status: " . mysqli_error($conn);
        }
        break;
        
    case 'teacher':
        // Check if user has permission to update teacher status
        if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'registrar') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You do not have permission to update teacher status']);
            exit;
        }
        
        // Update teacher status
        $query = "UPDATE teachers SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        $success = mysqli_stmt_execute($stmt);
        
        if ($success) {
            // Log the action
            logAction($_SESSION['user_id'], 'UPDATE', "Updated teacher ID {$id} status to {$status}");
            $message = "Teacher status updated to {$status}";
        } else {
            $message = "Failed to update teacher status: " . mysqli_error($conn);
        }
        break;
        
    case 'user':
        // Check if user has permission to update user status
        if ($_SESSION['role'] !== 'admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You do not have permission to update user status']);
            exit;
        }
        
        // Update user status
        $query = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        $dbStatus = strtolower($status);
        mysqli_stmt_bind_param($stmt, "si", $dbStatus, $id);
        $success = mysqli_stmt_execute($stmt);
        
        if ($success) {
            // Log the action
            logAction($_SESSION['user_id'], 'UPDATE', "Updated user ID {$id} status to {$status}");
            $message = "User status updated to {$status}";
        } else {
            $message = "Failed to update user status: " . mysqli_error($conn);
        }
        break;
        
    default:
        $message = "Invalid type specified";
        break;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $message,
    'type' => $type,
    'id' => $id,
    'status' => $status
]);
exit; 
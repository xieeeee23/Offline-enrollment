<?php
/**
 * Refresh User Settings
 * 
 * This script refreshes user settings in the session
 * by fetching the latest settings from the database.
 * It returns a JSON response indicating success or failure.
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

try {
    // Get user ID
    $user_id = $_SESSION['user_id'];
    
    // Get latest settings from database
    $user_settings = getUserSettings($user_id);
    
    // Update session settings
    $_SESSION['user_settings'] = $user_settings;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Settings refreshed successfully',
        'settings' => $user_settings
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error refreshing settings: ' . $e->getMessage()
    ]);
}
?> 
<?php
/**
 * Update Settings AJAX Handler
 * 
 * This script handles AJAX requests to update user settings.
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

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $settings = [];
    
    // Process settings from POST data
    if (isset($_POST['sidebar_expanded'])) {
        $settings['sidebar_expanded'] = (int)$_POST['sidebar_expanded'];
    }
    
    if (isset($_POST['theme_preference'])) {
        $settings['theme_preference'] = $_POST['theme_preference'];
    }
    
    if (isset($_POST['table_compact'])) {
        $settings['table_compact'] = (int)$_POST['table_compact'];
    }
    
    if (isset($_POST['table_hover'])) {
        $settings['table_hover'] = (int)$_POST['table_hover'];
    }
    
    if (isset($_POST['font_size'])) {
        $settings['font_size'] = $_POST['font_size'];
    }
    
    if (isset($_POST['high_contrast'])) {
        $settings['high_contrast'] = (int)$_POST['high_contrast'];
    }
    
    if (isset($_POST['color_blind_mode'])) {
        $settings['color_blind_mode'] = (int)$_POST['color_blind_mode'];
    }
    
    if (isset($_POST['enable_animations'])) {
        $settings['enable_animations'] = (int)$_POST['enable_animations'];
    }
    
    if (isset($_POST['animation_speed'])) {
        $settings['animation_speed'] = $_POST['animation_speed'];
    }
    
    if (isset($_POST['card_style'])) {
        $settings['card_style'] = $_POST['card_style'];
    }
    
    if (isset($_POST['motion_reduce'])) {
        $settings['motion_reduce'] = $_POST['motion_reduce'];
    }
    
    if (isset($_POST['focus_visible'])) {
        $settings['focus_visible'] = (int)$_POST['focus_visible'];
    }
    
    // Update user settings
    if (!empty($settings)) {
        if (updateUserSettings($user_id, $settings)) {
            // Update session settings
            $_SESSION['user_settings'] = getUserSettings($user_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating settings'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No settings to update'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 
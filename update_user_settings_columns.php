<?php
/**
 * Update User Settings Columns
 * 
 * This script adds any missing user settings columns to the users table.
 */

require_once 'includes/config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admin privileges required.");
}

// Define the columns we need
$required_columns = [
    'theme_preference' => "VARCHAR(10) DEFAULT 'system'",
    'sidebar_expanded' => "TINYINT(1) DEFAULT 1",
    'table_compact' => "TINYINT(1) DEFAULT 0",
    'table_hover' => "TINYINT(1) DEFAULT 1",
    'font_size' => "VARCHAR(10) DEFAULT 'normal'",
    'high_contrast' => "TINYINT(1) DEFAULT 0",
    'color_blind_mode' => "TINYINT(1) DEFAULT 0",
    'enable_animations' => "TINYINT(1) DEFAULT 1",
    'animation_speed' => "VARCHAR(10) DEFAULT 'normal'",
    'card_style' => "VARCHAR(20) DEFAULT 'default'",
    'motion_reduce' => "VARCHAR(10) DEFAULT 'none'",
    'focus_visible' => "TINYINT(1) DEFAULT 1"
];

// Check existing columns
$result = mysqli_query($conn, "SHOW COLUMNS FROM users");
$existing_columns = [];

while ($row = mysqli_fetch_assoc($result)) {
    $existing_columns[] = $row['Field'];
}

// Add missing columns
$added_columns = [];
$errors = [];

foreach ($required_columns as $column => $definition) {
    if (!in_array($column, $existing_columns)) {
        $query = "ALTER TABLE users ADD COLUMN $column $definition";
        
        if (mysqli_query($conn, $query)) {
            $added_columns[] = $column;
        } else {
            $errors[] = "Error adding column '$column': " . mysqli_error($conn);
        }
    }
}

// Output results
echo "<h1>User Settings Columns Update</h1>";

if (!empty($added_columns)) {
    echo "<div style='color: green; margin-bottom: 10px;'>";
    echo "<strong>Added columns:</strong><br>";
    echo implode("<br>", $added_columns);
    echo "</div>";
} else {
    echo "<div style='color: blue; margin-bottom: 10px;'>";
    echo "<strong>All required columns already exist.</strong>";
    echo "</div>";
}

if (!empty($errors)) {
    echo "<div style='color: red; margin-bottom: 10px;'>";
    echo "<strong>Errors:</strong><br>";
    echo implode("<br>", $errors);
    echo "</div>";
}

// Add a link to go back to the admin page
echo "<p><a href='modules/admin/database.php'>Return to Database Management</a></p>";
?> 
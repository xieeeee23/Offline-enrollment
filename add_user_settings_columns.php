<?php
// Include database connection
require_once 'includes/config.php';

echo "<h1>Adding User Settings Columns</h1>";

// Check if columns exist first
$columns_to_add = [
    'theme_preference' => "VARCHAR(10) DEFAULT 'system'",
    'sidebar_expanded' => "TINYINT(1) DEFAULT 1",
    'table_compact' => "TINYINT(1) DEFAULT 0",
    'font_size' => "VARCHAR(10) DEFAULT 'normal'",
    'high_contrast' => "TINYINT(1) DEFAULT 0",
    'color_blind_mode' => "TINYINT(1) DEFAULT 0",
    'enable_animations' => "TINYINT(1) DEFAULT 1",
    'animation_speed' => "VARCHAR(10) DEFAULT 'normal'",
    'card_style' => "VARCHAR(10) DEFAULT 'default'",
    'motion_reduce' => "VARCHAR(10) DEFAULT 'none'",
    'focus_visible' => "TINYINT(1) DEFAULT 1",
    'table_hover' => "TINYINT(1) DEFAULT 1"
];

$columns_added = 0;
$errors = 0;

echo "<h2>Checking existing columns...</h2>";
echo "<ul>";

foreach ($columns_to_add as $column => $definition) {
    // Check if column exists
    $check_query = "SHOW COLUMNS FROM users LIKE '$column'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        echo "<li>Column <strong>$column</strong> already exists.</li>";
    } else {
        // Add column
        $alter_query = "ALTER TABLE users ADD COLUMN $column $definition";
        if (mysqli_query($conn, $alter_query)) {
            echo "<li>Added column <strong>$column</strong> successfully.</li>";
            $columns_added++;
        } else {
            echo "<li style='color: red;'>Error adding column <strong>$column</strong>: " . mysqli_error($conn) . "</li>";
            $errors++;
        }
    }
}

echo "</ul>";

if ($columns_added > 0) {
    echo "<div style='padding: 10px; background-color: #d4edda; color: #155724; border-radius: 5px;'>";
    echo "<strong>Success!</strong> Added $columns_added new columns to the users table.";
    echo "</div>";
} elseif ($errors === 0) {
    echo "<div style='padding: 10px; background-color: #d1ecf1; color: #0c5460; border-radius: 5px;'>";
    echo "<strong>Info:</strong> All required columns already exist. No changes were made.";
    echo "</div>";
} else {
    echo "<div style='padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 5px;'>";
    echo "<strong>Error!</strong> Failed to add some columns. Please check the error messages above.";
    echo "</div>";
}

echo "<p><a href='settings.php' style='display: inline-block; padding: 8px 16px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Go to Settings Page</a></p>";
?> 
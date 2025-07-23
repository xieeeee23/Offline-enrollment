<?php
require_once 'includes/config.php';

// Get all tables
$tables_query = "SHOW TABLES";
$tables_result = mysqli_query($conn, $tables_query);

echo "<h2>Database Tables</h2>";
echo "<ul>";
while ($table = mysqli_fetch_row($tables_result)) {
    $table_name = $table[0];
    echo "<li><strong>{$table_name}</strong>";
    
    // Get columns for this table
    $columns_query = "SHOW COLUMNS FROM {$table_name}";
    $columns_result = mysqli_query($conn, $columns_query);
    
    echo "<ul>";
    while ($column = mysqli_fetch_assoc($columns_result)) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
    
    echo "</li>";
}
echo "</ul>";
?> 
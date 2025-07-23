<?php
// Test script to verify column name generation logic

// Sample requirement names
$requirements = [
    'Birth Certificate',
    'Form 137',
    'Good Moral Certificate',
    '2x2 ID Picture',
    'Parent/Guardian ID',
    'Medical Certificate',
    'Report Card / Form 138'
];

echo "<h1>Column Name Generation Test</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Requirement Name</th><th>Old Method</th><th>New Method</th></tr>";

foreach ($requirements as $name) {
    // Old method
    $old_column = strtolower(preg_replace('/[^a-z0-9_]/', '_', str_replace(' ', '_', $name)));
    
    // New method
    $new_column = str_replace(' ', '_', $name);
    $new_column = preg_replace('/[^a-zA-Z0-9_]/', '_', $new_column);
    $new_column = strtolower($new_column);
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($name) . "</td>";
    echo "<td>" . htmlspecialchars($old_column) . "</td>";
    echo "<td>" . htmlspecialchars($new_column) . "</td>";
    echo "</tr>";
}

echo "</table>";
?> 
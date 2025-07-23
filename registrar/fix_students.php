<?php
// Fix for unclosed brace in students.php
$file = 'students.php';
$content = file_get_contents($file);

// Check if the file exists and was read correctly
if ($content === false) {
    echo "Error: Could not read the file $file";
    exit;
}

// Add a closing brace after line 430 (the missing one)
$lines = explode("\n", $content);
$fixed_content = '';
$line_count = count($lines);

for ($i = 0; $i < $line_count; $i++) {
    $fixed_content .= $lines[$i] . "\n";
    
    // After line 430, add the missing closing brace
    if ($i === 429) {
        $fixed_content .= "                }\n";
    }
}

// Write the fixed content back to the file
if (file_put_contents($file, $fixed_content) !== false) {
    echo "Success: Fixed the unclosed brace issue in $file";
} else {
    echo "Error: Could not write to the file $file";
}
?> 
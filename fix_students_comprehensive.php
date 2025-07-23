<?php
// Fix the students.php file by adding missing closing braces

// Read the entire file
$file_path = 'modules/registrar/students.php';
$content = file_get_contents($file_path);

if ($content === false) {
    die("Error: Could not read the file $file_path");
}

// Count opening and closing braces to find imbalance
$open_braces = substr_count($content, '{');
$close_braces = substr_count($content, '}');
$missing_braces = $open_braces - $close_braces;

echo "Opening braces: $open_braces\n";
echo "Closing braces: $close_braces\n";
echo "Missing braces: $missing_braces\n";

// Add missing closing braces at the end of the file
if ($missing_braces > 0) {
    $append = str_repeat("\n} // Added missing closing brace", $missing_braces);
    if (file_put_contents($file_path, $append, FILE_APPEND) !== false) {
        echo "Success: Added $missing_braces closing brace(s) to the end of the file.\n";
    } else {
        echo "Error: Could not write to the file.\n";
    }
} else {
    echo "No missing braces detected.\n";
}
?> 
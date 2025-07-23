<?php
// Fix the students.php file by removing the extra closing braces

$file_path = 'modules/registrar/students.php';
$content = file_get_contents($file_path);

if ($content === false) {
    die("Error: Could not read the file $file_path");
}

// Remove the extra closing braces we added at the end
$content = preg_replace('/\n\} \/\/ Fix for unclosed brace.*$/s', '', $content);

// Write the fixed content back to the file
if (file_put_contents($file_path, $content) !== false) {
    echo "Success: Removed extra closing braces from the file.\n";
} else {
    echo "Error: Could not write to the file.\n";
}

// Now add just one closing brace with a comment to fix the issue
$append = "\n} // Fixed unclosed brace\n";
if (file_put_contents($file_path, $append, FILE_APPEND) !== false) {
    echo "Success: Added one closing brace to the end of the file.\n";
} else {
    echo "Error: Could not append to the file.\n";
}
?> 
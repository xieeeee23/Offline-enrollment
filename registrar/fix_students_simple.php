<?php
// Simple fix for unclosed brace in students.php
$file = 'students.php';

// Append a closing brace at the end of the file
if (file_put_contents($file, "\n} // Fix for unclosed brace\n", FILE_APPEND) !== false) {
    echo "Success: Added closing brace to the end of $file";
} else {
    echo "Error: Could not write to the file $file";
}
?> 
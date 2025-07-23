<?php
// Append a closing brace to the students.php file
$file_path = 'modules/registrar/students.php';
file_put_contents($file_path, "\n} // Fix for unclosed brace\n", FILE_APPEND);
echo "Added closing brace to students.php";
?> 
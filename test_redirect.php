<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "Testing URL generation for redirects<br>";
echo "BASE_URL defined as: " . BASE_URL . "<br>";

// Test 1: Simple redirect
echo "<br>Test 1: redirect('modules/registrar/students.php')<br>";
$test1 = BASE_URL . 'modules/registrar/students.php';
echo "Would redirect to: " . $test1 . "<br>";

// Test 2: Redirect with query params
echo "<br>Test 2: redirect('modules/registrar/students.php?action=edit&id=1')<br>";
$test2 = BASE_URL . 'modules/registrar/students.php?action=edit&id=1';
echo "Would redirect to: " . $test2 . "<br>";

// Test 3: Redirect with relative_path prepended
echo "<br>Test 3: redirect(\$relative_path . 'modules/registrar/students.php')<br>";
$relative_path = '../../';
$test3 = BASE_URL . $relative_path . 'modules/registrar/students.php';
echo "Would redirect to: " . $test3 . " (WRONG - double path)<br>";
?>

<p>Conclusion: The correct way to redirect is to use <code>redirect('modules/registrar/students.php')</code> without the <code>$relative_path</code> variable.</p> 
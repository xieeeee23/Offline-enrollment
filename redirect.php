<?php
// Redirect to the correct location
$correct_path = str_replace('/offline%20enrollment/offline%20enrollment/', '/offline%20enrollment/', $_SERVER['REQUEST_URI']);
header("Location: $correct_path");
exit;
?>
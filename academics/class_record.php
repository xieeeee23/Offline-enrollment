<?php
 = 'Class Record';
 = 'Class Record Management';
 = '../../';
require_once \ . 'includes/header.php';
// Check if user has necessary permissions
if (!checkAccess(['admin', 'teacher', 'registrar'])) {

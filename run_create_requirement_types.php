<?php
// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to run this script.";
    exit;
}

// Read the SQL file
$sql_file = file_get_contents('create_requirement_types_table.sql');

// Execute each SQL statement
$statements = explode(';', $sql_file);
$success = true;

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if (!mysqli_query($conn, $statement)) {
            echo "Error executing statement: " . mysqli_error($conn) . "<br>";
            $success = false;
        }
    }
}

if ($success) {
    echo "Requirement types table created successfully!";
    // Log the action
    if (function_exists('logAction')) {
        logAction($_SESSION['user_id'], 'CREATE', 'Created requirement_types table');
    }
} else {
    echo "There were errors creating the requirement_types table.";
}

// Close connection
mysqli_close($conn);
?> 
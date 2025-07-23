<?php
require_once 'includes/config.php';

// SQL to add voucher fields
$sql = "ALTER TABLE students 
        ADD COLUMN IF NOT EXISTS has_voucher TINYINT(1) NOT NULL DEFAULT 0 AFTER enrollment_status, 
        ADD COLUMN IF NOT EXISTS voucher_number VARCHAR(50) DEFAULT NULL AFTER has_voucher";

// Execute the query
if (mysqli_query($conn, $sql)) {
    echo "Voucher fields added successfully.";
} else {
    echo "Error adding voucher fields: " . mysqli_error($conn);
}
?> 
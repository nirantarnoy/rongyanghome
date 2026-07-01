<?php
require 'config.php';

// Check if column exists first
$check_sql = "SHOW COLUMNS FROM payroll_commissions LIKE 'commission_type'";
$check_res = mysqli_query($conn, $check_sql);
if (mysqli_num_rows($check_res) == 0) {
    $sql = "ALTER TABLE payroll_commissions ADD COLUMN commission_type VARCHAR(50) NOT NULL DEFAULT 'monthly' AFTER status";
    if (mysqli_query($conn, $sql)) {
        echo "Successfully added commission_type column to payroll_commissions table.\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Column commission_type already exists in payroll_commissions table.\n";
}
?>

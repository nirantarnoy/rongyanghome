<?php
require 'config.php';

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$tables = ['quotations', 'sales_orders', 'invoices'];
$columns = ['header_logo', 'signature1', 'signature2', 'signature3', 'qr_code_image', 'items'];

echo "<h2>Fixing Database Schema for Base64 Images</h2>";
echo "<ul>";

foreach ($tables as $table) {
    echo "<li>Checking table: <strong>$table</strong><ul>";
    
    // Check if table exists
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check_table) == 0) {
        echo "<li style='color: orange;'>Table $table does not exist. Skipping.</li>";
        echo "</ul></li>";
        continue;
    }

    foreach ($columns as $column) {
        // Check if column exists
        $check_column = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
        if (mysqli_num_rows($check_column) > 0) {
            $sql = "ALTER TABLE `$table` MODIFY `$column` LONGTEXT";
            if (mysqli_query($conn, $sql)) {
                echo "<li style='color: green;'>Updated $column to LONGTEXT in $table.</li>";
            } else {
                echo "<li style='color: red;'>Error updating $column in $table: " . mysqli_error($conn) . "</li>";
            }
        }
    }
    echo "</ul></li>";
}

echo "</ul>";
echo "<p>Fix Completed.</p>";
?>

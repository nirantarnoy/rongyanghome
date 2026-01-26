<?php
require '../config.php';

// Add notes column to quotations table
$sql = "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS notes TEXT AFTER grand_total";

if (mysqli_query($conn, $sql)) {
    echo "Column 'notes' added successfully to quotations table\n";
} else {
    // Check if column already exists
    $check_sql = "SHOW COLUMNS FROM quotations LIKE 'notes'";
    $result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($result) > 0) {
        echo "Column 'notes' already exists in quotations table\n";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
?>

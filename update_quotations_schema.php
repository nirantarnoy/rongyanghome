<?php
include 'config.php';

$sql = "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS conditions TEXT AFTER notes";

if (mysqli_query($conn, $sql)) {
    echo "Successfully updated quotations table schema.\n";
} else {
    echo "Error updating quotations table schema: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>

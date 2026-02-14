<?php
require 'config.php';

$sql = "ALTER TABLE receipts ADD COLUMN qr_code_image LONGTEXT AFTER conditions";

if (mysqli_query($conn, $sql)) {
    echo "Successfully added qr_code_image column to receipts table.\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
?>

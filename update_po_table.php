<?php
include 'config.php';
$sql = "ALTER TABLE purchase_orders ADD COLUMN qr_code_image LONGTEXT AFTER signature2";
if (mysqli_query($conn, $sql)) {
    echo "Column qr_code_image added successfully\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}
?>

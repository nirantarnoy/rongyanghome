<?php
require 'config.php';
$sql = "ALTER TABLE company ADD COLUMN logo TEXT AFTER email";
if (mysqli_query($conn, $sql)) {
    echo "Column 'logo' added successfully";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>

<?php
require 'config.php';
$sql = "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS issuer_company_id INT AFTER company_id";
if (mysqli_query($conn, $sql)) {
    echo "Column issuer_company_id added successfully or already exists.";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>

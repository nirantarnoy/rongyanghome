<?php
require_once __DIR__ . '/../config.php';

$sql = "ALTER TABLE goods_receipts ADD COLUMN is_stocked TINYINT(1) DEFAULT 0 AFTER grand_total";

if (mysqli_query($conn, $sql)) {
    echo "Successfully added 'is_stocked' column to 'goods_receipts' table.";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>

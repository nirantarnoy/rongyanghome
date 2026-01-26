<?php
require 'config.php';
$queries = [
    "ALTER TABLE stock_requisition_items ADD COLUMN warehouse_id INT AFTER product_id",
    "ALTER TABLE stock_requisitions DROP COLUMN warehouse_id"
];

foreach ($queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "Success: $query\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>

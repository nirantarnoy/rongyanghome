<?php
require 'config.php';
$queries = [
    "ALTER TABLE stock_production_orders 
        ADD COLUMN order_date DATE AFTER order_no,
        ADD COLUMN due_date DATE AFTER order_date,
        ADD COLUMN customer_name VARCHAR(255) AFTER due_date,
        ADD COLUMN project_name VARCHAR(255) AFTER customer_name,
        ADD COLUMN sku VARCHAR(100) AFTER product_id,
        ADD COLUMN unit VARCHAR(50) AFTER qty,
        ADD COLUMN dimensions VARCHAR(255) AFTER unit,
        ADD COLUMN instructions TEXT AFTER dimensions,
        ADD COLUMN qc_standards TEXT AFTER instructions,
        ADD COLUMN ordered_by VARCHAR(255) AFTER qc_standards,
        ADD COLUMN foreman VARCHAR(255) AFTER ordered_by",
    "CREATE TABLE IF NOT EXISTS stock_production_bom (
        id INT AUTO_INCREMENT PRIMARY KEY,
        production_order_id INT NOT NULL,
        product_id INT NOT NULL,
        qty DECIMAL(10,2) NOT NULL,
        INDEX (production_order_id),
        INDEX (product_id)
    )"
];

foreach ($queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "Success: " . substr($query, 0, 50) . "...\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>

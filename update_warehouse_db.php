<?php
require 'config.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS stock_warehouses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        location TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (company_id)
    )",
    "ALTER TABLE stock_transactions ADD COLUMN warehouse_id INT AFTER product_id",
    "ALTER TABLE stock_requisitions ADD COLUMN warehouse_id INT AFTER company_id",
    "CREATE TABLE IF NOT EXISTS stock_inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        product_id INT NOT NULL,
        warehouse_id INT NOT NULL,
        quantity INT DEFAULT 0,
        UNIQUE KEY (product_id, warehouse_id),
        INDEX (company_id)
    )"
];

foreach ($queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "Success: $query\n";
    } else {
        echo "Error: " . mysqli_error($conn) . " for query: $query\n";
    }
}
?>

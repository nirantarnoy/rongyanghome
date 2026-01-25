<?php
require 'config.php';

$sql = "
ALTER TABLE stock_production_orders 
ADD COLUMN order_date DATE AFTER order_no,
ADD COLUMN due_date DATE AFTER order_date,
ADD COLUMN customer_name VARCHAR(255) AFTER due_date,
ADD COLUMN project_name VARCHAR(255) AFTER customer_name,
ADD COLUMN sku VARCHAR(100) AFTER project_name,
ADD COLUMN unit VARCHAR(50) AFTER qty,
ADD COLUMN dimensions VARCHAR(255) AFTER unit,
ADD COLUMN instructions TEXT AFTER dimensions,
ADD COLUMN qc_standards TEXT AFTER instructions,
ADD COLUMN ordered_by VARCHAR(255) AFTER qc_standards,
ADD COLUMN foreman VARCHAR(255) AFTER ordered_by;

CREATE TABLE IF NOT EXISTS stock_production_bom (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT NOT NULL,
    product_id INT NOT NULL,
    qty DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (production_order_id) REFERENCES stock_production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES stock_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (mysqli_multi_query($conn, $sql)) {
    do {
        if ($result = mysqli_store_result($conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
    echo "Table stock_production_orders updated and stock_production_bom created successfully";
} else {
    echo "Error updating database: " . mysqli_error($conn);
}
?>

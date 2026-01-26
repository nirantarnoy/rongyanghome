<?php
require 'config.php';

// Create material_requisitions table
$sql = "CREATE TABLE IF NOT EXISTS material_requisitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    requisition_no VARCHAR(50) NOT NULL,
    production_order_id INT NOT NULL,
    requisition_date DATE NOT NULL,
    requested_by VARCHAR(255),
    department VARCHAR(255),
    purpose TEXT,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    approved_by VARCHAR(255),
    approved_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (production_order_id) REFERENCES stock_production_orders(id) ON DELETE CASCADE,
    INDEX idx_company (company_id),
    INDEX idx_production (production_order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "Table material_requisitions created successfully\n";
} else {
    echo "Error creating material_requisitions table: " . mysqli_error($conn) . "\n";
}

// Create material_requisition_items table
$sql2 = "CREATE TABLE IF NOT EXISTS material_requisition_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    product_id INT NOT NULL,
    qty_requested DECIMAL(15,4) NOT NULL,
    qty_approved DECIMAL(15,4) DEFAULT 0,
    qty_issued DECIMAL(15,4) DEFAULT 0,
    warehouse_id INT,
    unit VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requisition_id) REFERENCES material_requisitions(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES stock_products(id),
    INDEX idx_requisition (requisition_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql2)) {
    echo "Table material_requisition_items created successfully\n";
} else {
    echo "Error creating material_requisition_items table: " . mysqli_error($conn) . "\n";
}

echo "Database setup completed!\n";
?>

<?php
require 'config.php';

// Drop existing tables if needed (comment out if you want to keep data)
// mysqli_query($conn, "DROP TABLE IF EXISTS material_requisition_items");
// mysqli_query($conn, "DROP TABLE IF EXISTS material_requisitions");

// Create material_requisitions table
$sql = "CREATE TABLE IF NOT EXISTS material_requisitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    requisition_no VARCHAR(50) NOT NULL UNIQUE,
    production_order_id INT,
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
    INDEX idx_company (company_id),
    INDEX idx_production (production_order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    if (mysqli_query($conn, $sql)) {
        echo "✓ Table material_requisitions ready\n";
    }
} catch (Exception $e) {
    echo "Note: material_requisitions table may already exist\n";
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
    INDEX idx_requisition (requisition_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    if (mysqli_query($conn, $sql2)) {
        echo "✓ Table material_requisition_items ready\n";
    }
} catch (Exception $e) {
    echo "Note: material_requisition_items table may already exist\n";
}

echo "\n✓ Database setup completed!\n";
mysqli_close($conn);
?>

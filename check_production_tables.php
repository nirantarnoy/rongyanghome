<?php
require 'config.php';

echo "=== ตรวจสอบและสร้างตารางที่จำเป็น ===\n\n";

// Check and create stock_production_orders table
$check = mysqli_query($conn, "SHOW TABLES LIKE 'stock_production_orders'");
if (mysqli_num_rows($check) == 0) {
    echo "❌ ตาราง stock_production_orders ไม่มี - กำลังสร้าง...\n";
    $sql = "CREATE TABLE stock_production_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        order_no VARCHAR(50) NOT NULL,
        order_date DATE NOT NULL,
        due_date DATE,
        customer_name VARCHAR(255),
        project_name VARCHAR(255),
        product_id INT,
        sku VARCHAR(100),
        qty DECIMAL(15,4) NOT NULL,
        unit VARCHAR(50),
        dimensions VARCHAR(255),
        instructions TEXT,
        qc_standards TEXT,
        ordered_by VARCHAR(255),
        foreman VARCHAR(255),
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_company (company_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $sql)) {
        echo "✅ สร้างตาราง stock_production_orders สำเร็จ\n";
    } else {
        echo "❌ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✅ ตาราง stock_production_orders มีอยู่แล้ว\n";
}

// Check and create stock_production_bom table
$check2 = mysqli_query($conn, "SHOW TABLES LIKE 'stock_production_bom'");
if (mysqli_num_rows($check2) == 0) {
    echo "❌ ตาราง stock_production_bom ไม่มี - กำลังสร้าง...\n";
    $sql2 = "CREATE TABLE stock_production_bom (
        id INT AUTO_INCREMENT PRIMARY KEY,
        production_order_id INT NOT NULL,
        product_id INT NOT NULL,
        qty DECIMAL(15,4) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_production_order (production_order_id),
        INDEX idx_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $sql2)) {
        echo "✅ สร้างตาราง stock_production_bom สำเร็จ\n";
    } else {
        echo "❌ Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✅ ตาราง stock_production_bom มีอยู่แล้ว\n";
}

// Check material_requisitions
$check3 = mysqli_query($conn, "SHOW TABLES LIKE 'material_requisitions'");
if (mysqli_num_rows($check3) > 0) {
    echo "✅ ตาราง material_requisitions มีอยู่แล้ว\n";
} else {
    echo "❌ ตาราง material_requisitions ไม่มี\n";
}

// Check material_requisition_items
$check4 = mysqli_query($conn, "SHOW TABLES LIKE 'material_requisition_items'");
if (mysqli_num_rows($check4) > 0) {
    echo "✅ ตาราง material_requisition_items มีอยู่แล้ว\n";
} else {
    echo "❌ ตาราง material_requisition_items ไม่มี\n";
}

echo "\n=== เสร็จสิ้น ===\n";
mysqli_close($conn);
?>

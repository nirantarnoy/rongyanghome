<?php
require 'config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== เริ่มต้นการอัปเดตโครงสร้างฐานข้อมูล (Production By-products) ===\n\n";

$sql = "CREATE TABLE IF NOT EXISTS stock_production_byproducts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT NOT NULL,
    name VARCHAR(255),
    qty DECIMAL(15,4),
    unit VARCHAR(50),
    price DECIMAL(15,4),
    total DECIMAL(15,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_prod_id (production_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql)) {
    echo "✅ สร้างตาราง stock_production_byproducts เรียบร้อยแล้ว\n";
} else {
    echo "❌ เกิดข้อผิดพลาดในการสร้างตาราง: " . mysqli_error($conn) . "\n";
}

echo "\nตรวจสอบตารางอื่นที่เกี่ยวข้อง...\n";

// ตัวอย่างการตรวจสอบตารางหลักเผื่อกรณีต้องการ migrate เพิ่ม
$check_orders = mysqli_query($conn, "SHOW TABLES LIKE 'stock_production_orders'");
if (mysqli_num_rows($check_orders) > 0) {
    echo "✅ ตาราง stock_production_orders พร้อมใช้งาน\n";
} else {
    echo "⚠️ คำเตือน: ไม่พบตาราง stock_production_orders กรุณารันสคริปต์สร้างตารางหลักก่อน\n";
}

echo "\n=== เสร็จสิ้นกระบวนการ ===\n";
mysqli_close($conn);
?>

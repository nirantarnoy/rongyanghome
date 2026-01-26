<?php
require '../config.php';

// Create templates table for quotation
$sql = "CREATE TABLE IF NOT EXISTS quotation_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    template_type ENUM('payment_terms', 'notes') NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    template_content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_type (company_id, template_type),
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $sql)) {
    echo "✅ Table 'quotation_templates' created successfully\n";
    
    // Insert default templates
    $company_id = 1; // Default company
    
    $defaults = [
        ['payment_terms', 'โอนเงินล่วงหน้า 100%', 'โอนเงินล่วงหน้า 100% ก่อนการผลิต'],
        ['payment_terms', 'มัดจำ 50%', 'มัดจำ 50% ก่อนการผลิต ชำระส่วนที่เหลือก่อนส่งมอบ'],
        ['payment_terms', 'เครดิต 7 วัน', 'ชำระภายใน 7 วันหลังรับสินค้า'],
        ['payment_terms', 'เครดิต 30 วัน', 'ชำระภายใน 30 วันหลังรับสินค้า'],
        ['notes', 'การรับประกัน', 'รับประกันสินค้า 1 ปี นับจากวันที่ส่งมอบ'],
        ['notes', 'การจัดส่ง', 'ค่าจัดส่งฟรีในเขตกรุงเทพและปริมณฑล'],
        ['notes', 'ราคาพิเศษ', 'ราคานี้เป็นราคาพิเศษสำหรับลูกค้าประจำ'],
    ];
    
    foreach ($defaults as $default) {
        $insert_sql = "INSERT INTO quotation_templates (company_id, template_type, template_name, template_content) 
                      VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "isss", $company_id, $default[0], $default[1], $default[2]);
        mysqli_stmt_execute($stmt);
    }
    
    echo "✅ Default templates inserted\n";
} else {
    echo "❌ Error creating table: " . mysqli_error($conn) . "\n";
}

// Add new columns to quotations table
$alter_sqls = [
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS delivery_time VARCHAR(255) DEFAULT NULL AFTER customer_tax_id",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS payment_terms TEXT DEFAULT NULL AFTER delivery_time",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS qr_code_image TEXT DEFAULT NULL AFTER signature3"
];

foreach ($alter_sqls as $alter_sql) {
    if (mysqli_query($conn, $alter_sql)) {
        echo "✅ Column added successfully\n";
    } else {
        // Ignore if column already exists
        if (strpos(mysqli_error($conn), 'Duplicate column') === false) {
            echo "⚠️ " . mysqli_error($conn) . "\n";
        }
    }
}

mysqli_close($conn);
?>

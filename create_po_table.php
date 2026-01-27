<?php
include 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    company_id INT(11) NOT NULL,
    issuer_company_id INT(11),
    year INT(4) NOT NULL,
    doc_number VARCHAR(50) NOT NULL,
    doc_date DATE NOT NULL,
    vendor_code VARCHAR(50),
    vendor_name VARCHAR(255),
    vendor_address TEXT,
    vendor_phone VARCHAR(50),
    vendor_email VARCHAR(100),
    vendor_tax_id VARCHAR(50),
    payment_terms VARCHAR(255),
    items LONGTEXT,
    vat_enabled TINYINT(1) DEFAULT 1,
    vat_type ENUM('include', 'exclude') DEFAULT 'exclude',
    subtotal DECIMAL(15, 2) DEFAULT 0.00,
    total_discount DECIMAL(15, 2) DEFAULT 0.00,
    vat_amount DECIMAL(15, 2) DEFAULT 0.00,
    grand_total DECIMAL(15, 2) DEFAULT 0.00,
    notes TEXT,
    conditions TEXT,
    signature1 LONGTEXT,
    signature2 LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (company_id),
    INDEX (doc_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "Table purchase_orders created successfully\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>

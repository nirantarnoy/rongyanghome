<?php
require '../config.php';

// Create quotations table
$sql = "CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    doc_number VARCHAR(50) NOT NULL,
    doc_date DATE NOT NULL,
    customer_name VARCHAR(255),
    customer_address TEXT,
    customer_phone VARCHAR(50),
    customer_tax_id VARCHAR(50),
    items JSON,
    vat_enabled TINYINT(1) DEFAULT 1,
    vat_type ENUM('exclude', 'include') DEFAULT 'exclude',
    subtotal DECIMAL(15,2) DEFAULT 0,
    vat_amount DECIMAL(15,2) DEFAULT 0,
    grand_total DECIMAL(15,2) DEFAULT 0,
    signature1 TEXT,
    signature2 TEXT,
    signature3 TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "Table quotations created successfully\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}
?>

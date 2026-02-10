<?php
/**
 * Unified Migration Script - 2026-02-10
 * 
 * This script updates the database schema for:
 * 1. Receipts: Adds payment_terms and conditions
 * 2. Quotations: Adds delivery_time, payment_terms, conditions, qr_code_image, and issuer_company_id
 * 3. Templates: Updates quotation_templates to support 'conditions' type
 */

require_once 'config.php';

echo "<pre>";
echo "Starting migration...\n";

// 1. Update quotation_templates table and enum
echo "\n--- Updating quotation_templates ---\n";
$create_template_table = "CREATE TABLE IF NOT EXISTS quotation_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    template_type ENUM('payment_terms', 'notes', 'conditions') NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    template_content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_type (company_id, template_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $create_template_table)) {
    echo "✅ Table 'quotation_templates' is ready.\n";
    
    // Update enum just in case the table existed with old enum
    $update_enum = "ALTER TABLE quotation_templates MODIFY COLUMN template_type ENUM('payment_terms', 'notes', 'conditions') NOT NULL";
    if (mysqli_query($conn, $update_enum)) {
        echo "✅ Updated template_type enum to include 'conditions'.\n";
    } else {
        echo "❌ Error updating enum: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "❌ Error creating/checking template table: " . mysqli_error($conn) . "\n";
}

// 2. Update quotations table
echo "\n--- Updating quotations table ---\n";
$quotation_queries = [
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS issuer_company_id INT AFTER company_id",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS delivery_time VARCHAR(255) DEFAULT NULL AFTER customer_tax_id",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS payment_terms TEXT DEFAULT NULL AFTER delivery_time",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS conditions TEXT AFTER notes",
    "ALTER TABLE quotations ADD COLUMN IF NOT EXISTS qr_code_image TEXT DEFAULT NULL AFTER signature3"
];

foreach ($quotation_queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ Executed: $sql\n";
    } else {
        echo "❌ Error: " . mysqli_error($conn) . "\n";
    }
}

// 3. Update receipts table
echo "\n--- Updating receipts table ---\n";
$receipt_queries = [
    "ALTER TABLE receipts ADD COLUMN IF NOT EXISTS payment_terms TEXT AFTER customer_tax_id",
    "ALTER TABLE receipts ADD COLUMN IF NOT EXISTS total_discount DECIMAL(15,2) DEFAULT 0 AFTER subtotal",
    "ALTER TABLE receipts ADD COLUMN IF NOT EXISTS conditions TEXT AFTER notes"
];

foreach ($receipt_queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ Executed: $sql\n";
    } else {
        echo "❌ Error: " . mysqli_error($conn) . "\n";
    }
}

// 4. Update goods_receipts table (for consistency)
echo "\n--- Updating goods_receipts table ---\n";
$gr_queries = [
    "ALTER TABLE goods_receipts ADD COLUMN IF NOT EXISTS payment_terms TEXT AFTER vendor_tax_id",
    "ALTER TABLE goods_receipts ADD COLUMN IF NOT EXISTS conditions TEXT AFTER notes"
];

foreach ($gr_queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ Executed: $sql\n";
    } else {
        echo "❌ Error: " . mysqli_error($conn) . "\n";
    }
}

echo "\nMigration finished successfully!\n";
echo "</pre>";

mysqli_close($conn);
?>

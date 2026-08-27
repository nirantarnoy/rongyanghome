<?php
/**
 * Migration script for 2026-02-08
 * This script updates the database schema for:
 * 1. Invoices & Tax Invoices
 * 2. Project Transactions (Income/Expense linkage)
 * 3. Stock Requisitions
 */

require 'config.php';

// Ensure we are connected
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$queries = [
    // 1. Invoices Table (Handles Invoice and Tax Invoice)
    "CREATE TABLE IF NOT EXISTS `invoices` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `company_id` int(11) NOT NULL,
        `issuer_company_id` int(11) DEFAULT NULL,
        `year` int(11) DEFAULT NULL,
        `doc_number` varchar(50) NOT NULL,
        `doc_date` date DEFAULT NULL,
        `customer_code` varchar(50) DEFAULT NULL,
        `customer_name` varchar(255) DEFAULT NULL,
        `customer_address` text DEFAULT NULL,
        `customer_phone` varchar(50) DEFAULT NULL,
        `customer_email` varchar(100) DEFAULT NULL,
        `customer_tax_id` varchar(20) DEFAULT NULL,
        `payment_terms` varchar(255) DEFAULT NULL,
        `items` longtext DEFAULT NULL,
        `vat_enabled` tinyint(1) DEFAULT 0,
        `vat_type` enum('include','exclude') DEFAULT 'exclude',
        `subtotal` decimal(15,2) DEFAULT 0.00,
        `total_discount` decimal(15,2) DEFAULT 0.00,
        `vat_amount` decimal(15,2) DEFAULT 0.00,
        `grand_total` decimal(15,2) DEFAULT 0.00,
        `notes` text DEFAULT NULL,
        `conditions` text DEFAULT NULL,
        `signature1` varchar(255) DEFAULT NULL,
        `signature2` varchar(255) DEFAULT NULL,
        `qr_code_image` text DEFAULT NULL,
        `header_name` varchar(255) DEFAULT NULL,
        `header_address` text DEFAULT NULL,
        `header_phone` varchar(100) DEFAULT NULL,
        `header_tax_id` varchar(20) DEFAULT NULL,
        `header_logo` text DEFAULT NULL,
        `type` enum('invoice','tax_invoice') DEFAULT 'invoice',
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `company_id` (`company_id`),
        KEY `doc_number` (`doc_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // 2. Project Transactions Table
    "CREATE TABLE IF NOT EXISTS `transactions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `project_id` int(11) NOT NULL,
        `category_id` int(11) NOT NULL,
        `transaction_date` date NOT NULL,
        `amount` decimal(15,2) NOT NULL,
        `note` text DEFAULT NULL,
        `module_type` int(11) NOT NULL COMMENT '1=Project Module, 2=Company Module',
        `company_id` int(11) NOT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `project_id` (`project_id`),
        KEY `company_id` (`company_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // 3. Stock Requisitions Table (User confirmed structure)
    "CREATE TABLE IF NOT EXISTS `stock_requisitions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `company_id` int(11) NOT NULL,
        `req_no` varchar(50) NOT NULL,
        `po_no` varchar(100) DEFAULT NULL,
        `so_no` varchar(100) DEFAULT NULL,
        `customer_name` varchar(255) DEFAULT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `shipping_address` text DEFAULT NULL,
        `shipping_method` varchar(255) DEFAULT NULL,
        `requisition_date` date DEFAULT NULL,
        `remark` text DEFAULT NULL,
        `requester_name` varchar(255) DEFAULT NULL,
        `status` enum('pending','approved','rejected') DEFAULT 'pending',
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `company_id` (`company_id`),
        KEY `req_no` (`req_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // 4. Stock Requisition Items Table
    "CREATE TABLE IF NOT EXISTS `stock_requisition_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `requisition_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `warehouse_id` int(11) DEFAULT NULL,
        `qty` decimal(15,2) NOT NULL,
        `price` decimal(15,2) DEFAULT 0.00,
        PRIMARY KEY (`id`),
        KEY `requisition_id` (`requisition_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

echo "<h2>Database Migration - 2026-02-08</h2>";
echo "<ul>";

foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "<li style='color: green;'>Success: Table structure updated.</li>";
    } else {
        echo "<li style='color: red;'>Error: " . mysqli_error($conn) . "</li>";
    }
}

echo "</ul>";
echo "<p>Migration Completed.</p>";
?>

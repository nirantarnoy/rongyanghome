<?php
require 'config.php';

$tables = ['purchase_orders', 'sales_orders', 'receipts', 'goods_receipts'];
$columns = [
    'header_name' => 'VARCHAR(255)',
    'header_address' => 'TEXT',
    'header_phone' => 'VARCHAR(50)',
    'header_tax_id' => 'VARCHAR(50)',
    'header_logo' => 'LONGTEXT',
    'issuer_company_id' => 'INT'
];

foreach ($tables as $table) {
    echo "Checking table: $table\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    $existing_columns = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $existing_columns[] = $row['Field'];
    }
    
    foreach ($columns as $col => $type) {
        if (!in_array($col, $existing_columns)) {
            echo "  Adding column: $col\n";
            mysqli_query($conn, "ALTER TABLE $table ADD COLUMN $col $type");
        }
    }
}

// Also check if items column can store images (it's usually JSON, so it should be fine if it's TEXT or LONGTEXT)
// Let's check if 'items' is LONGTEXT to be safe for base64 images
foreach (array_merge(['quotations'], $tables) as $table) {
    mysqli_query($conn, "ALTER TABLE $table MODIFY COLUMN items LONGTEXT");
}

echo "Done.\n";
?>

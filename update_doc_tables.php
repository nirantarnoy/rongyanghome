<?php
require 'config.php';
$tables = ['purchase_orders', 'quotations', 'sales_orders', 'receipts', 'goods_receipts'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "Updating table: $table\n";
        $cols = [
            'header_name' => "VARCHAR(255)",
            'header_address' => "TEXT",
            'header_phone' => "VARCHAR(100)",
            'header_tax_id' => "VARCHAR(50)",
            'header_logo' => "LONGTEXT"
        ];
        foreach ($cols as $col => $type) {
            $check = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$col'");
            if (mysqli_num_rows($check) == 0) {
                $sql = "ALTER TABLE $table ADD COLUMN $col $type";
                if (mysqli_query($conn, $sql)) {
                    echo "  - Added $col\n";
                } else {
                    echo "  - Error adding $col: " . mysqli_error($conn) . "\n";
                }
            } else {
                echo "  - $col already exists\n";
            }
        }
    }
}
?>

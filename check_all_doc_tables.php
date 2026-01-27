<?php
require 'config.php';
$tables = ['purchase_orders', 'quotations', 'sales_orders', 'receipts', 'goods_receipts'];
foreach ($tables as $table) {
    echo "Table: $table\n";
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        $res = mysqli_query($conn, "DESCRIBE $table");
        while ($row = mysqli_fetch_assoc($res)) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "  (Not found)\n";
    }
    echo "\n";
}
?>

<?php
require __DIR__ . '/../config.php';

$updates = [
    'invoices' => ['signer_name1', 'signer_name2'],
    'quotations' => ['signer_name1', 'signer_name2', 'signer_name3'],
    'receipts' => ['signer_name1', 'signer_name2'],
    'purchase_orders' => ['signer_name1', 'signer_name2'],
    'sales_orders' => ['signer_name1', 'signer_name2'],
    'goods_receipts' => ['signer_name1', 'signer_name2']
];

foreach ($updates as $table => $cols) {
    echo "Checking table: $table\n";
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($check_table) > 0) {
        foreach ($cols as $col) {
            $check_col = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$col'");
            if (mysqli_num_rows($check_col) == 0) {
                $sql = "ALTER TABLE `$table` ADD COLUMN `$col` VARCHAR(255) DEFAULT NULL";
                if (mysqli_query($conn, $sql)) {
                    echo " - Added column $col\n";
                } else {
                    echo " - Error adding column $col: " . mysqli_error($conn) . "\n";
                }
            } else {
                echo " - Column $col already exists\n";
            }
        }
    } else {
        echo " - Table $table not found\n";
    }
}
echo "Migration completed.\n";
?>

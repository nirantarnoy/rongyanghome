<?php
require 'config.php';
require 'file_helper.php';

$tables = [
    'quotations' => ['signature1', 'signature2', 'signature3', 'qr_code_image', 'header_logo', 'items'],
    'sales_orders' => ['signature1', 'signature2', 'qr_code_image', 'header_logo', 'items'],
    'invoices' => ['signature1', 'signature2', 'qr_code_image', 'header_logo', 'items'],
    'receipts' => ['signature1', 'signature2', 'header_logo', 'items'],
    'goods_receipts' => ['signature1', 'signature2', 'qr_code_image', 'header_logo', 'items'],
    'purchase_orders' => ['signature1', 'signature2', 'qr_code_image', 'header_logo', 'items']
];

foreach ($tables as $table => $columns) {
    echo "Processing table: $table\n";
    $sql = "SELECT id, " . implode(', ', $columns) . " FROM $table";
    $res = mysqli_query($conn, $sql);
    
    while ($row = mysqli_fetch_assoc($res)) {
        $updates = [];
        foreach ($columns as $column) {
            $value = $row[$column];
            if (empty($value)) continue;

            if ($column === 'items') {
                $processed = processItemsImages($value, "uploads/items");
                if ($processed !== $value) {
                    $updates[] = "$column = '" . mysqli_real_escape_string($conn, $processed) . "'";
                }
            } else {
                $subDir = 'uploads';
                if (strpos($column, 'signature') !== false) $subDir = 'uploads/signatures';
                elseif (strpos($column, 'qr') !== false) $subDir = 'uploads/qrcodes';
                elseif (strpos($column, 'logo') !== false) $subDir = 'uploads/logos';
                
                $processed = saveBase64Image($value, $subDir);
                if ($processed !== $value) {
                    $updates[] = "$column = '" . mysqli_real_escape_string($conn, $processed) . "'";
                }
            }
        }
        
        if (!empty($updates)) {
            $updateSql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE id = " . $row['id'];
            if (mysqli_query($conn, $updateSql)) {
                echo "  Updated row ID: " . $row['id'] . "\n";
            } else {
                echo "  Error updating row ID: " . $row['id'] . ": " . mysqli_error($conn) . "\n";
            }
        }
    }
}
echo "Migration finished.\n";
?>

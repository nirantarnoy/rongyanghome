<?php
require 'config.php';
$table = 'stock_requisition_items';
$result = mysqli_query($conn, "DESCRIBE $table");
$out = "--- $table ---\n";
while ($row = mysqli_fetch_assoc($result)) {
    $out .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
}
file_put_contents('req_items_schema.txt', $out);
?>

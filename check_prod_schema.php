<?php
require 'config.php';
$table = 'stock_production_orders';
$result = mysqli_query($conn, "DESCRIBE $table");
$output = "--- $table ---\n";
while ($row = mysqli_fetch_assoc($result)) {
    $output .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
}
file_put_contents('prod_schema.txt', $output);
?>

<?php
require 'config.php';

$output = "";
function get_schema($conn, $table) {
    $out = "--- $table ---\n";
    $result = mysqli_query($conn, "DESCRIBE $table");
    while ($row = mysqli_fetch_assoc($result)) {
        $out .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
    }
    $out .= "\n";
    return $out;
}

$output .= get_schema($conn, 'stock_products');
$output .= get_schema($conn, 'stock_transactions');
$output .= get_schema($conn, 'stock_requisitions');

file_put_contents('schema_output.txt', $output);
?>

<?php
require 'config.php';
$tables = ['stock_production_orders', 'stock_production_bom'];
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $res = mysqli_query($conn, "DESCRIBE $t");
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            echo " - {$r['Field']} ({$r['Type']})\n";
        }
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
}
?>

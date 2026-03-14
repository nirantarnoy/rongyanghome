<?php
require 'config.php';
$res = $conn->query('DESCRIBE stock_production_orders');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}

<?php
require 'config.php';
$res = mysqli_query($conn, "SELECT * FROM stock_transactions WHERE qty < 0 OR (type != 'in' AND type != 'out')");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
$res2 = mysqli_query($conn, "SELECT type, SUM(qty) as total_qty FROM stock_transactions GROUP BY type");
while($row = mysqli_fetch_assoc($res2)) {
    print_r($row);
}

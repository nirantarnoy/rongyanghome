<?php
require 'config.php';
$result = mysqli_query($conn, "DESCRIBE purchase_orders");
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}
echo "PURCHASE ORDERS:\n";
echo json_encode($rows, JSON_PRETTY_PRINT);

$result = mysqli_query($conn, "DESCRIBE quotations");
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}
echo "\nQUOTATIONS:\n";
echo json_encode($rows, JSON_PRETTY_PRINT);
?>

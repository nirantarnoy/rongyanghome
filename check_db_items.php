<?php
require 'config.php';
$table = 'stock_requisition_items';
$result = mysqli_query($conn, "DESCRIBE $table");
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row;
}
echo json_encode($columns);
?>

<?php
require 'config.php';
$table = 'action_logs';
$result = mysqli_query($conn, "DESCRIBE $table");
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row;
}
echo json_encode($columns);
$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM action_logs");
$row = mysqli_fetch_assoc($result);
echo "\nCount: " . $row['c'] . "\n";
?>

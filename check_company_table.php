<?php
require 'config.php';
$result = mysqli_query($conn, "DESCRIBE company");
$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}
echo json_encode($rows, JSON_PRETTY_PRINT);
?>

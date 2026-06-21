<?php
require 'config.php';
$result = mysqli_query($conn, "DESCRIBE users");
$columns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row;
}
echo json_encode($columns);
?>

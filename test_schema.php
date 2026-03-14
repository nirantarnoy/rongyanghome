<?php
require 'config.php';
$res = mysqli_query($conn, "DESCRIBE invoices");
if (!$res) {
    file_put_contents('schema_out.txt', "ERROR: " . mysqli_error($conn));
    exit;
}
$cols = [];
while($r = mysqli_fetch_assoc($res)) {
    $cols[] = $r['Field'];
}
file_put_contents('schema_out.txt', "Columns: " . implode(", ", $cols));
echo "DONE";
?>

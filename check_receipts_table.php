<?php
include 'config.php';
$table = 'receipts';
$res = mysqli_query($conn, "DESCRIBE $table");
echo "Table: $table\n";
while($row = mysqli_fetch_assoc($res)) {
    echo "{$row['Field']} - {$row['Type']}\n";
}
?>

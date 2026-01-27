<?php
include 'config.php';
function describe($conn, $table) {
    echo "Table: $table\n";
    $result = mysqli_query($conn, "DESCRIBE $table");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']} - {$row['Extra']}\n";
    }
    echo "\n";
}
describe($conn, 'quotations');
describe($conn, 'receipts');
?>

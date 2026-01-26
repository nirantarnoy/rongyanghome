<?php
require 'config.php';
$tables = ['material_requisitions', 'material_requisition_items'];
foreach ($tables as $table) {
    echo "Table: $table\n";
    $res = mysqli_query($conn, "DESCRIBE $table");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "  {$row['Field']} - {$row['Type']}\n";
        }
    } else {
        echo "  Error: " . mysqli_error($conn) . "\n";
    }
    echo "\n";
}
?>

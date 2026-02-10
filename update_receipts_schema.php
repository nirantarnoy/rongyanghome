<?php
include 'config.php';

$queries = [
    "ALTER TABLE receipts ADD COLUMN IF NOT EXISTS payment_terms TEXT AFTER customer_tax_id",
    "ALTER TABLE receipts ADD COLUMN IF NOT EXISTS conditions TEXT AFTER notes"
];

foreach ($queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Successfully executed: $sql\n";
    } else {
        echo "Error executing $sql: " . mysqli_error($conn) . "\n";
    }
}
?>

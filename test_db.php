<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Connecting with timeout...\n";
$conn = mysqli_init();
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
if (!@mysqli_real_connect($conn, '127.0.0.1', 'root', '', 'rongyang_db')) {
    echo "Connection failed: " . mysqli_connect_error() . "\n";
    exit;
}
echo "Connected successfully!\n";
$res = mysqli_query($conn, 'SHOW COLUMNS FROM invoices');
if (!$res) {
    echo mysqli_error($conn);
    exit;
}
while($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}
?>

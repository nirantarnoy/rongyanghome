<?php
$conn = mysqli_connect('127.0.0.1', 'root', '', 'rongyang_db');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$res = mysqli_query($conn, 'SHOW COLUMNS FROM invoices');
if (!$res) {
    echo mysqli_error($conn);
    exit;
}
while($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}
?>

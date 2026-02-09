<?php
require 'config.php';
$table = $_GET['table'] ?? 'quotations';
$sql = "SELECT id, doc_number, qr_code_image, LENGTH(qr_code_image) as qr_len FROM $table ORDER BY id DESC LIMIT 5";
$res = mysqli_query($conn, $sql);
echo "<h2>Last 5 entries in $table</h2>";
echo "<table border='1'><tr><th>ID</th><th>Number</th><th>QR Length</th><th>QR Preview</th></tr>";
while($row = mysqli_fetch_assoc($res)) {
    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['doc_number']}</td>
            <td>{$row['qr_len']}</td>
            <td>" . ($row['qr_code_image'] ? "<img src='{$row['qr_code_image']}' style='max-width:100px;'>" : "Empty") . "</td>
          </tr>";
}
echo "</table>";
?>

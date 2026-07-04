<?php
require 'config.php';
$res = mysqli_query($conn, 'SELECT * FROM action_logs ORDER BY id DESC LIMIT 20');
$out = "";
while($r = mysqli_fetch_assoc($res)) {
    $out .= json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}
file_put_contents('logs.txt', $out);
echo "Done";

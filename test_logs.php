<?php
require 'config.php';
$res = mysqli_query($conn, 'SELECT * FROM action_logs ORDER BY id DESC LIMIT 10');
while($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}

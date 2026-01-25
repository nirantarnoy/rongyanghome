<?php
require 'config.php';
$res = mysqli_query($conn, "SHOW TABLES LIKE 'action_log'");
if(mysqli_num_rows($res) > 0) {
    echo "Table action_log exists:\n";
    $res2 = mysqli_query($conn, "DESCRIBE action_log");
    while($row = mysqli_fetch_assoc($res2)) {
        echo $row['Field'] . ' (' . $row['Type'] . ")\n";
    }
} else {
    echo "Table action_log not found\n";
}

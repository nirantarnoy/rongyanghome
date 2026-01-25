<?php
require 'config.php';
$res = mysqli_query($conn, "SHOW TABLES LIKE 'user'");
if(mysqli_num_rows($res) > 0) {
    echo "Table user exists:\n";
    $res2 = mysqli_query($conn, "DESCRIBE user");
    while($row = mysqli_fetch_assoc($res2)) {
        echo $row['Field'] . ' (' . $row['Type'] . ")\n";
    }
} else {
    echo "Table user not found\n";
}

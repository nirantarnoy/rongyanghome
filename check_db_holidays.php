<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect using localhost just like the real app config does
$conn = mysqli_connect("localhost", "root", "", "rongyang_db");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

echo "<pre>";
echo "=== DESCRIBE payroll_holidays ===\n";
$res = mysqli_query($conn, "DESCRIBE payroll_holidays");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

echo "\n=== SHOW INDEX FROM payroll_holidays ===\n";
$res2 = mysqli_query($conn, "SHOW INDEX FROM payroll_holidays");
if ($res2) {
    while ($row = mysqli_fetch_assoc($res2)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

echo "\n=== SHOW CREATE TABLE payroll_holidays ===\n";
$res3 = mysqli_query($conn, "SHOW CREATE TABLE payroll_holidays");
if ($res3) {
    $row = mysqli_fetch_assoc($res3);
    print_r($row);
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

echo "\n=== SELECT * FROM payroll_holidays ===\n";
$res4 = mysqli_query($conn, "SELECT * FROM payroll_holidays");
if ($res4) {
    while ($row = mysqli_fetch_assoc($res4)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}
echo "</pre>";

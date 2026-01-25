<?php
require 'config.php';
$sql = "CREATE TABLE IF NOT EXISTS stock_action_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    activity TEXT NOT NULL,
    action_type ENUM('create', 'update', 'delete', 'view') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $sql)) {
    echo "Table stock_action_logs created successfully\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}

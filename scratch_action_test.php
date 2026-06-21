<?php
require 'config.php';
$company_id = 1;
$date_from = '2026-01-19';
$date_to = '2026-02-19';

$params = [$company_id];
$types = "i";
$where_clause = "WHERE l.company_id = ?";

if (!empty($date_from)) {
    $where_clause .= " AND DATE(l.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $where_clause .= " AND DATE(l.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$log_sql = "SELECT l.*, u.full_name, u.username 
           FROM action_logs l 
           LEFT JOIN users u ON l.user_id = u.id 
           $where_clause 
           ORDER BY l.created_at DESC 
           LIMIT 500";

$log_stmt = mysqli_prepare($conn, $log_sql);
if ($log_stmt) {
    // Attempt bind param
    try {
        mysqli_stmt_bind_param($log_stmt, $types, ...$params);
        mysqli_stmt_execute($log_stmt);
        $log_res = mysqli_stmt_get_result($log_stmt);
        if ($log_res === false) {
            echo "Error getting result: " . mysqli_error($conn) . "\n";
        } else {
            echo "Success! Rows: " . mysqli_num_rows($log_res) . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Prepare failed: " . mysqli_error($conn) . "\n";
}
?>

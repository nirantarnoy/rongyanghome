<?php
// Simple fix for add_production - create this as a separate file to test
require '../auth_check.php';
require '../config.php';

header('Content-Type: application/json');

$company_id = $_SESSION['company_id'];
$order_no = $_POST['order_no'] ?? '';
$order_date = $_POST['order_date'] ?? date('Y-m-d');
$due_date = $_POST['due_date'] ?? null;
$customer_name = $_POST['customer_name'] ?? '';
$project_name = $_POST['project_name'] ?? '';
$product_id = $_POST['product_id'] ?? 0;
$sku = $_POST['sku'] ?? '';
$qty = $_POST['qty'] ?? 0;
$unit = $_POST['unit'] ?? '';
$dimensions = $_POST['dimensions'] ?? '';
$instructions = $_POST['instructions'] ?? '';
$qc_standards = $_POST['qc_standards'] ?? '';
$ordered_by = $_POST['ordered_by'] ?? '';
$foreman = $_POST['foreman'] ?? '';
$status = 'pending';
$bom = $_POST['bom'] ?? [];

mysqli_begin_transaction($conn);
try {
    // Insert production order
    $sql = "INSERT INTO stock_production_orders (company_id, order_no, order_date, due_date, customer_name, project_name, product_id, sku, qty, unit, dimensions, instructions, qc_standards, ordered_by, foreman, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssssisssssssss", $company_id, $order_no, $order_date, $due_date, $customer_name, $project_name, $product_id, $sku, $qty, $unit, $dimensions, $instructions, $qc_standards, $ordered_by, $foreman, $status);
    mysqli_stmt_execute($stmt);
    $production_order_id = mysqli_insert_id($conn);

    // Insert BOM items
    foreach ($bom as $item) {
        $sql_bom = "INSERT INTO stock_production_bom (production_order_id, product_id, qty) VALUES (?, ?, ?)";
        $stmt_bom = mysqli_prepare($conn, $sql_bom);
        mysqli_stmt_bind_param($stmt_bom, "iid", $production_order_id, $item['product_id'], $item['qty']);
        mysqli_stmt_execute($stmt_bom);
    }

    mysqli_commit($conn);
    
    $message = 'สร้างใบสั่งผลิตเรียบร้อยแล้ว';
    echo json_encode(['status' => 'success', 'message' => $message, 'production_id' => $production_order_id]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>

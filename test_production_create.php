<?php
// Test production order creation
require 'config.php';

// Simulate POST data
$_POST = [
    'order_no' => 'TEST-PO-001',
    'order_date' => date('Y-m-d'),
    'due_date' => date('Y-m-d', strtotime('+7 days')),
    'customer_name' => 'ทดสอบลูกค้า',
    'project_name' => 'โครงการทดสอบ',
    'product_id' => 1,
    'sku' => 'TEST-SKU',
    'qty' => 10,
    'unit' => 'ชิ้น',
    'dimensions' => '10x20',
    'instructions' => 'คำแนะนำทดสอบ',
    'qc_standards' => 'มาตรฐานทดสอบ',
    'ordered_by' => 'ผู้ทดสอบ',
    'foreman' => 'หัวหน้าทดสอบ',
    'bom' => [
        ['product_id' => 2, 'qty' => 5],
        ['product_id' => 3, 'qty' => 3]
    ]
];

$_SESSION['company_id'] = 1;
$_SESSION['user_login'] = 'admin';

$company_id = 1;
$order_no = $_POST['order_no'];
$order_date = $_POST['order_date'];
$due_date = $_POST['due_date'];
$customer_name = $_POST['customer_name'];
$project_name = $_POST['project_name'];
$product_id = $_POST['product_id'];
$sku = $_POST['sku'];
$qty = $_POST['qty'];
$unit = $_POST['unit'];
$dimensions = $_POST['dimensions'];
$instructions = $_POST['instructions'];
$qc_standards = $_POST['qc_standards'];
$ordered_by = $_POST['ordered_by'];
$foreman = $_POST['foreman'];
$status = 'pending';
$bom = $_POST['bom'];

echo "=== ทดสอบการสร้างใบสั่งผลิต ===\n\n";

mysqli_begin_transaction($conn);
try {
    // Insert production order
    $sql = "INSERT INTO stock_production_orders (company_id, order_no, order_date, due_date, customer_name, project_name, product_id, sku, qty, unit, dimensions, instructions, qc_standards, ordered_by, foreman, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "isssssisdsssssss", $company_id, $order_no, $order_date, $due_date, $customer_name, $project_name, $product_id, $sku, $qty, $unit, $dimensions, $instructions, $qc_standards, $ordered_by, $foreman, $status);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $production_order_id = mysqli_insert_id($conn);
    echo "✅ สร้างใบสั่งผลิต ID: $production_order_id\n";

    // Insert BOM items
    foreach ($bom as $item) {
        $sql_bom = "INSERT INTO stock_production_bom (production_order_id, product_id, qty) VALUES (?, ?, ?)";
        $stmt_bom = mysqli_prepare($conn, $sql_bom);
        mysqli_stmt_bind_param($stmt_bom, "iid", $production_order_id, $item['product_id'], $item['qty']);
        
        if (!mysqli_stmt_execute($stmt_bom)) {
            throw new Exception("BOM insert failed: " . mysqli_stmt_error($stmt_bom));
        }
    }
    echo "✅ เพิ่ม BOM " . count($bom) . " รายการ\n";

    // Auto-create material requisition
    if (!empty($bom)) {
        $requisition_no = 'MR-' . date('Ymd') . '-' . str_pad($production_order_id, 4, '0', STR_PAD_LEFT);
        $purpose = "เบิกวัสดุสำหรับใบสั่งผลิต: $order_no";
        
        $sql_req = "INSERT INTO material_requisitions (company_id, requisition_no, production_order_id, requisition_date, requested_by, department, purpose, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt_req = mysqli_prepare($conn, $sql_req);
        $department = 'ฝ่ายผลิต';
        mysqli_stmt_bind_param($stmt_req, "isisss", $company_id, $requisition_no, $production_order_id, $order_date, $ordered_by, $department, $purpose);
        
        if (!mysqli_stmt_execute($stmt_req)) {
            throw new Exception("Requisition insert failed: " . mysqli_stmt_error($stmt_req));
        }
        
        $requisition_id = mysqli_insert_id($conn);
        echo "✅ สร้างใบเบิกจ่าย: $requisition_no (ID: $requisition_id)\n";

        // Insert requisition items
        foreach ($bom as $item) {
            $unit_sql = "SELECT unit FROM stock_products WHERE id = ?";
            $unit_stmt = mysqli_prepare($conn, $unit_sql);
            mysqli_stmt_bind_param($unit_stmt, "i", $item['product_id']);
            mysqli_stmt_execute($unit_stmt);
            $result = mysqli_stmt_get_result($unit_stmt);
            $product_unit = $result ? mysqli_fetch_assoc($result)['unit'] ?? '' : '';
            
            $sql_req_item = "INSERT INTO material_requisition_items (requisition_id, product_id, qty_requested, unit) 
                            VALUES (?, ?, ?, ?)";
            $stmt_req_item = mysqli_prepare($conn, $sql_req_item);
            mysqli_stmt_bind_param($stmt_req_item, "iids", $requisition_id, $item['product_id'], $item['qty'], $product_unit);
            
            if (!mysqli_stmt_execute($stmt_req_item)) {
                throw new Exception("Requisition item insert failed: " . mysqli_stmt_error($stmt_req_item));
            }
        }
        echo "✅ เพิ่มรายการเบิกจ่าย " . count($bom) . " รายการ\n";
    }

    mysqli_commit($conn);
    echo "\n✅ สำเร็จ! ทุกอย่างทำงานได้ปกติ\n";
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "\n❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
}

mysqli_close($conn);
?>

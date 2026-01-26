<?php
require '../auth_check.php';
include '../config.php';

$action = $_REQUEST['action'] ?? '';
$company_id = $_SESSION['company_id'] ?? 0;

if ($action == 'save') {
    $id = $_POST['id'] ?? null;
    $doc_number = mysqli_real_escape_string($conn, $_POST['doc_number']);
    $doc_date = mysqli_real_escape_string($conn, $_POST['doc_date']);
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $customer_address = mysqli_real_escape_string($conn, $_POST['customer_address']);
    $customer_phone = mysqli_real_escape_string($conn, $_POST['customer_phone']);
    $customer_tax_id = mysqli_real_escape_string($conn, $_POST['customer_tax_id']);
    $items = mysqli_real_escape_string($conn, $_POST['items']);
    $vat_enabled = (int)$_POST['vat_enabled'];
    $vat_type = mysqli_real_escape_string($conn, $_POST['vat_type']);
    $subtotal = (float)$_POST['subtotal'];
    $vat_amount = (float)$_POST['vat_amount'];
    $grand_total = (float)$_POST['grand_total'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $signature1 = mysqli_real_escape_string($conn, $_POST['signature1'] ?? '');
    $signature2 = mysqli_real_escape_string($conn, $_POST['signature2'] ?? '');
    $signature3 = mysqli_real_escape_string($conn, $_POST['signature3'] ?? '');
    
    if ($id) {
        // Update
        $sql = "UPDATE quotations SET 
                doc_number = '$doc_number',
                doc_date = '$doc_date',
                customer_name = '$customer_name',
                customer_address = '$customer_address',
                customer_phone = '$customer_phone',
                customer_tax_id = '$customer_tax_id',
                items = '$items',
                vat_enabled = $vat_enabled,
                vat_type = '$vat_type',
                subtotal = $subtotal,
                vat_amount = $vat_amount,
                grand_total = $grand_total,
                notes = '$notes',
                signature1 = '$signature1',
                signature2 = '$signature2',
                signature3 = '$signature3'
                WHERE id = $id AND company_id = $company_id";
    } else {
        // Insert
        $sql = "INSERT INTO quotations (company_id, doc_number, doc_date, customer_name, customer_address, customer_phone, customer_tax_id, items, vat_enabled, vat_type, subtotal, vat_amount, grand_total, notes, signature1, signature2, signature3)
                VALUES ($company_id, '$doc_number', '$doc_date', '$customer_name', '$customer_address', '$customer_phone', '$customer_tax_id', '$items', $vat_enabled, '$vat_type', $subtotal, $vat_amount, $grand_total, '$notes', '$signature1', '$signature2', '$signature3')";
    }
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'บันทึกใบเสนอราคาเรียบร้อยแล้ว', 'id' => $id ?: mysqli_insert_id($conn)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}

if ($action == 'list') {
    $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
    $where = "WHERE company_id = $company_id";
    
    if ($search) {
        $where .= " AND (doc_number LIKE '%$search%' OR customer_name LIKE '%$search%')";
    }
    
    $sql = "SELECT * FROM quotations $where ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'data' => $data]);
}

if ($action == 'get') {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM quotations WHERE id = $id AND company_id = $company_id";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล']);
    }
}

if ($action == 'delete') {
    $id = (int)$_POST['id'];
    $sql = "DELETE FROM quotations WHERE id = $id AND company_id = $company_id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบใบเสนอราคาเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}
?>

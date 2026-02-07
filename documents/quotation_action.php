<?php
// เริ่มต้นดักจับ output ทั้งหมด
ob_start();

// ตั้งค่าการแสดง error ให้เก็บไว้ใน buffer (ไม่พ่นออกมาทันที)
error_reporting(E_ALL);
ini_set('display_errors', 0); 

// ปิดการพ่น error ของ mysqli เอง
mysqli_report(MYSQLI_REPORT_OFF);

require '../auth_check.php';
include '../config.php';
require_once '../log_helper.php';

$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'];

try {
    // ตรวจสอบว่าข้อมูล POST ถูกส่งมาครบหรือไม่ (กรณีขนาดไฟล์เกินขีดจำกัด)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        throw new Exception("ขนาดข้อมูลใหญ่เกินไป (เกินค่า post_max_size ใน php.ini) กรุณาลดขนาดรูปภาพ");
    }

    $action = $_REQUEST['action'] ?? '';
    $company_id = $_SESSION['company_id'] ?? 0;
    $user_id = $_SESSION['user_id'] ?? 0;
    $active_year = $_SESSION['active_year'] ?? (int)date('Y');

    if ($action == 'save') {
        $id = $_POST['id'] ?? null;
        $doc_number = mysqli_real_escape_string($conn, $_POST['doc_number'] ?? '');
        $doc_date = mysqli_real_escape_string($conn, $_POST['doc_date'] ?? '');
        $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name'] ?? '');
        $customer_address = mysqli_real_escape_string($conn, $_POST['customer_address'] ?? '');
        $customer_phone = mysqli_real_escape_string($conn, $_POST['customer_phone'] ?? '');
        $customer_tax_id = mysqli_real_escape_string($conn, $_POST['customer_tax_id'] ?? '');
        $delivery_time = mysqli_real_escape_string($conn, $_POST['delivery_time'] ?? '');
        $payment_terms = mysqli_real_escape_string($conn, $_POST['payment_terms'] ?? '');
        $items = mysqli_real_escape_string($conn, $_POST['items'] ?? '[]');
        $vat_enabled = (int)($_POST['vat_enabled'] ?? 0);
        $vat_type = mysqli_real_escape_string($conn, $_POST['vat_type'] ?? 'exclude');
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $total_discount = (float)($_POST['total_discount'] ?? 0);
        $vat_amount = (float)($_POST['vat_amount'] ?? 0);
        $grand_total = (float)($_POST['grand_total'] ?? 0);
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $signature1 = mysqli_real_escape_string($conn, $_POST['signature1'] ?? '');
        $signature2 = mysqli_real_escape_string($conn, $_POST['signature2'] ?? '');
        $signature3 = mysqli_real_escape_string($conn, $_POST['signature3'] ?? '');
        $qr_code_image = mysqli_real_escape_string($conn, $_POST['qr_code_image'] ?? '');
        
        $header_name = mysqli_real_escape_string($conn, $_POST['header_name'] ?? '');
        $header_address = mysqli_real_escape_string($conn, $_POST['header_address'] ?? '');
        $header_phone = mysqli_real_escape_string($conn, $_POST['header_phone'] ?? '');
        $header_tax_id = mysqli_real_escape_string($conn, $_POST['header_tax_id'] ?? '');
        $header_logo = mysqli_real_escape_string($conn, $_POST['header_logo'] ?? '');
        
        $issuer_company_id = (int)($_POST['issuer_company_id'] ?? $company_id);
        
        if ($id) {
            $sql = "UPDATE quotations SET 
                    issuer_company_id = $issuer_company_id,
                    doc_number = '$doc_number',
                    doc_date = '$doc_date',
                    customer_name = '$customer_name',
                    customer_address = '$customer_address',
                    customer_phone = '$customer_phone',
                    customer_tax_id = '$customer_tax_id',
                    delivery_time = '$delivery_time',
                    payment_terms = '$payment_terms',
                    items = '$items',
                    vat_enabled = $vat_enabled,
                    vat_type = '$vat_type',
                    subtotal = $subtotal,
                    total_discount = $total_discount,
                    vat_amount = $vat_amount,
                    grand_total = $grand_total,
                    notes = '$notes',
                    signature1 = '$signature1',
                    signature2 = '$signature2',
                    signature3 = '$signature3',
                    qr_code_image = '$qr_code_image',
                    header_name = '$header_name',
                    header_address = '$header_address',
                    header_phone = '$header_phone',
                    header_tax_id = '$header_tax_id',
                    header_logo = '$header_logo',
                    year = $active_year
                    WHERE id = $id AND company_id = $company_id";
        } else {
            $sql = "INSERT INTO quotations (company_id, issuer_company_id, year, doc_number, doc_date, customer_name, customer_address, customer_phone, customer_tax_id, delivery_time, payment_terms, items, vat_enabled, vat_type, subtotal, total_discount, vat_amount, grand_total, notes, signature1, signature2, signature3, qr_code_image, header_name, header_address, header_phone, header_tax_id, header_logo)
                    VALUES ($company_id, $issuer_company_id, $active_year, '$doc_number', '$doc_date', '$customer_name', '$customer_address', '$customer_phone', '$customer_tax_id', '$delivery_time', '$payment_terms', '$items', $vat_enabled, '$vat_type', $subtotal, $total_discount, $vat_amount, $grand_total, '$notes', '$signature1', '$signature2', '$signature3', '$qr_code_image', '$header_name', '$header_address', '$header_phone', '$header_tax_id', '$header_logo')";
        }
        
        if (mysqli_query($conn, $sql)) {
            $saved_id = $id ?: mysqli_insert_id($conn);
            logQuotation($conn, ($id ? "แก้ไข" : "สร้าง") . "ใบเสนอราคา: $doc_number", $id ? 'update' : 'create', $saved_id);
            $response = ['status' => 'success', 'message' => 'บันทึกเรียบร้อยแล้ว', 'id' => $saved_id];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } elseif ($action == 'list') {
        $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
        $sql = "SELECT * FROM quotations WHERE company_id = $company_id " . ($search ? "AND (doc_number LIKE '%$search%' OR customer_name LIKE '%$search%')" : "") . " ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
        $response = ['status' => 'success', 'data' => $data];
    } elseif ($action == 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $sql = "SELECT * FROM quotations WHERE id = $id AND company_id = $company_id";
        $result = mysqli_query($conn, $sql);
        if ($row = mysqli_fetch_assoc($result)) {
            $response = ['status' => 'success', 'data' => $row];
        } else {
            throw new Exception("ไม่พบข้อมูลใบเสนอราคา");
        }
    } elseif ($action == 'convert_to_so') {
        $id = (int)($_POST['id'] ?? 0);
        
        // Fetch quotation data
        $sql = "SELECT * FROM quotations WHERE id = $id AND company_id = $company_id";
        $result = mysqli_query($conn, $sql);
        $q = mysqli_fetch_assoc($result);
        
        if (!$q) {
            throw new Exception("ไม่พบข้อมูลใบเสนอราคา");
        }

        // Generate SO number
        $ty = date('Y') + 543;
        $prefix = "SO" . substr($ty, -2) . date('md');
        
        $count_sql = "SELECT COUNT(*) as count FROM sales_orders WHERE doc_number LIKE '$prefix-%' AND company_id = $company_id";
        $count_res = mysqli_query($conn, $count_sql);
        $count_row = mysqli_fetch_assoc($count_res);
        $next_no = str_pad(($count_row['count'] + 1), 3, '0', STR_PAD_LEFT);
        $so_number = "$prefix-$next_no";

        // Map quotation data to sales_order
        $issuer_company_id = (int)($q['issuer_company_id'] ?: $company_id);
        $doc_date = date('Y-m-d');
        $customer_name = mysqli_real_escape_string($conn, $q['customer_name']);
        $customer_address = mysqli_real_escape_string($conn, $q['customer_address']);
        $customer_phone = mysqli_real_escape_string($conn, $q['customer_phone']);
        $customer_tax_id = mysqli_real_escape_string($conn, $q['customer_tax_id']);
        $items = mysqli_real_escape_string($conn, $q['items']);
        $vat_enabled = (int)$q['vat_enabled'];
        $vat_type = mysqli_real_escape_string($conn, $q['vat_type']);
        $subtotal = (float)$q['subtotal'];
        $total_discount = (float)$q['total_discount'];
        $vat_amount = (float)$q['vat_amount'];
        $grand_total = (float)$q['grand_total'];
        $notes = mysqli_real_escape_string($conn, $q['notes']);
        $conditions = mysqli_real_escape_string($conn, $q['delivery_time'] ? "กำหนดส่ง: " . $q['delivery_time'] : "");
        $signature1 = mysqli_real_escape_string($conn, $q['signature1']);
        $signature2 = mysqli_real_escape_string($conn, $q['signature2']);
        $qr_code_image = mysqli_real_escape_string($conn, $q['qr_code_image']);
        $header_name = mysqli_real_escape_string($conn, $q['header_name']);
        $header_address = mysqli_real_escape_string($conn, $q['header_address']);
        $header_phone = mysqli_real_escape_string($conn, $q['header_phone']);
        $header_tax_id = mysqli_real_escape_string($conn, $q['header_tax_id']);
        $header_logo = mysqli_real_escape_string($conn, $q['header_logo']);

        $sql_so = "INSERT INTO sales_orders (
            company_id, issuer_company_id, year, doc_number, doc_date, 
            customer_name, customer_address, customer_phone, customer_tax_id, 
            items, vat_enabled, vat_type, subtotal, total_discount, 
            vat_amount, grand_total, notes, conditions, signature1, 
            signature2, qr_code_image, header_name, header_address, 
            header_phone, header_tax_id, header_logo
        ) VALUES (
            $company_id, $issuer_company_id, $active_year, '$so_number', '$doc_date',
            '$customer_name', '$customer_address', '$customer_phone', '$customer_tax_id',
            '$items', $vat_enabled, '$vat_type', $subtotal, $total_discount,
            $vat_amount, $grand_total, '$notes', '$conditions', '$signature1',
            '$signature2', '$qr_code_image', '$header_name', '$header_address',
            '$header_phone', '$header_tax_id', '$header_logo'
        )";

        if (mysqli_query($conn, $sql_so)) {
            $so_id = mysqli_insert_id($conn);
            logQuotation($conn, "แปลงใบเสนอราคา $q[doc_number] เป็นใบสั่งขาย $so_number", 'convert', $id);
            $response = ['status' => 'success', 'message' => 'แปลงเป็นใบสั่งขายเรียบร้อยแล้ว', 'so_id' => $so_id];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } elseif ($action == 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // ดึงข้อมูลก่อนลบเพื่อทำ log
        $get_sql = "SELECT doc_number, customer_name FROM quotations WHERE id = $id AND company_id = $company_id";
        $get_res = mysqli_query($conn, $get_sql);
        $quot_data = mysqli_fetch_assoc($get_res);
        
        $sql = "DELETE FROM quotations WHERE id = $id AND company_id = $company_id";
        if (mysqli_query($conn, $sql)) {
            if ($quot_data) {
                logQuotation($conn, "ลบใบเสนอราคา: {$quot_data['doc_number']} (ลูกค้า: {$quot_data['customer_name']})", 'delete', $id);
            }
            $response = ['status' => 'success', 'message' => 'ลบใบเสนอราคาเรียบร้อยแล้ว'];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Action ไม่ถูกต้อง'];
    }

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

// ล้าง output buffer ที่อาจมี warning หลุดออกมา
$unexpected_output = ob_get_clean();

// ส่ง JSON กลับไป
header('Content-Type: application/json');
echo json_encode($response);
?>

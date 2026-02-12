<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); 
mysqli_report(MYSQLI_REPORT_OFF);

require '../auth_check.php';
include '../config.php';
require_once '../log_helper.php';
require_once '../file_helper.php';

$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ'];

try {
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
        $customer_code = mysqli_real_escape_string($conn, $_POST['customer_code'] ?? '');
        $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name'] ?? '');
        $customer_address = mysqli_real_escape_string($conn, $_POST['customer_address'] ?? '');
        $customer_phone = mysqli_real_escape_string($conn, $_POST['customer_phone'] ?? '');
        $customer_email = mysqli_real_escape_string($conn, $_POST['customer_email'] ?? '');
        $customer_tax_id = mysqli_real_escape_string($conn, $_POST['customer_tax_id'] ?? '');
        $payment_terms = mysqli_real_escape_string($conn, $_POST['payment_terms'] ?? '');
        
        // Handle images
        $items_raw = $_POST['items'] ?? '[]';
        $items = mysqli_real_escape_string($conn, processItemsImages($items_raw));

        $vat_enabled = (int)($_POST['vat_enabled'] ?? 0);
        $vat_type = mysqli_real_escape_string($conn, $_POST['vat_type'] ?? 'exclude');
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $total_discount = (float)($_POST['total_discount'] ?? 0);
        $vat_amount = (float)($_POST['vat_amount'] ?? 0);
        $grand_total = (float)($_POST['grand_total'] ?? 0);
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $conditions = mysqli_real_escape_string($conn, $_POST['conditions'] ?? '');
        
        $signature1 = mysqli_real_escape_string($conn, saveBase64Image($_POST['signature1'] ?? '', 'uploads/signatures'));
        $signature2 = mysqli_real_escape_string($conn, saveBase64Image($_POST['signature2'] ?? '', 'uploads/signatures'));
        $signer_name1 = mysqli_real_escape_string($conn, $_POST['signer_name1'] ?? '');
        $signer_name2 = mysqli_real_escape_string($conn, $_POST['signer_name2'] ?? '');
        $qr_code_image = mysqli_real_escape_string($conn, saveBase64Image($_POST['qr_code_image'] ?? '', 'uploads/qrcodes'));
        
        $header_name = mysqli_real_escape_string($conn, $_POST['header_name'] ?? '');
        $header_address = mysqli_real_escape_string($conn, $_POST['header_address'] ?? '');
        $header_phone = mysqli_real_escape_string($conn, $_POST['header_phone'] ?? '');
        $header_tax_id = mysqli_real_escape_string($conn, $_POST['header_tax_id'] ?? '');
        $header_logo = mysqli_real_escape_string($conn, saveBase64Image($_POST['header_logo'] ?? '', 'uploads/logos'));
        
        $issuer_company_id = (int)($_POST['issuer_company_id'] ?? $company_id);
        
        if ($id) {
            $sql = "UPDATE sales_orders SET 
                    issuer_company_id = $issuer_company_id,
                    doc_number = '$doc_number',
                    doc_date = '$doc_date',
                    customer_code = '$customer_code',
                    customer_name = '$customer_name',
                    customer_address = '$customer_address',
                    customer_phone = '$customer_phone',
                    customer_email = '$customer_email',
                    customer_tax_id = '$customer_tax_id',
                    payment_terms = '$payment_terms',
                    items = '$items',
                    vat_enabled = $vat_enabled,
                    vat_type = '$vat_type',
                    subtotal = $subtotal,
                    total_discount = $total_discount,
                    vat_amount = $vat_amount,
                    grand_total = $grand_total,
                    notes = '$notes',
                    conditions = '$conditions',
                    signature1 = '$signature1',
                    signature2 = '$signature2',
                    signer_name1 = '$signer_name1',
                    signer_name2 = '$signer_name2',
                    qr_code_image = '$qr_code_image',
                    header_name = '$header_name',
                    header_address = '$header_address',
                    header_phone = '$header_phone',
                    header_tax_id = '$header_tax_id',
                    header_logo = '$header_logo',
                    year = $active_year
                    WHERE id = $id AND company_id = $company_id";
        } else {
            $sql = "INSERT INTO sales_orders (company_id, issuer_company_id, year, doc_number, doc_date, customer_code, customer_name, customer_address, customer_phone, customer_email, customer_tax_id, payment_terms, items, vat_enabled, vat_type, subtotal, total_discount, vat_amount, grand_total, notes, conditions, signature1, signature2, signer_name1, signer_name2, qr_code_image, header_name, header_address, header_phone, header_tax_id, header_logo)
                    VALUES ($company_id, $issuer_company_id, $active_year, '$doc_number', '$doc_date', '$customer_code', '$customer_name', '$customer_address', '$customer_phone', '$customer_email', '$customer_tax_id', '$payment_terms', '$items', $vat_enabled, '$vat_type', $subtotal, $total_discount, $vat_amount, $grand_total, '$notes', '$conditions', '$signature1', '$signature2', '$signer_name1', '$signer_name2', '$qr_code_image', '$header_name', '$header_address', '$header_phone', '$header_tax_id', '$header_logo')";
        }
        
        if (mysqli_query($conn, $sql)) {
            $saved_id = $id ?: mysqli_insert_id($conn);
            if (function_exists('logAction')) {
                logAction($conn, ($id ? "แก้ไข" : "สร้าง") . "ใบสั่งขาย: $doc_number", $id ? 'update' : 'create', $saved_id);
            }
            $response = ['status' => 'success', 'message' => 'บันทึกเรียบร้อยแล้ว', 'id' => $saved_id];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } elseif ($action == 'list') {
        $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
        $sql = "SELECT * FROM sales_orders WHERE company_id = $company_id " . ($search ? "AND (doc_number LIKE '%$search%' OR customer_name LIKE '%$search%')" : "") . " ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
        $response = ['status' => 'success', 'data' => $data];
    } elseif ($action == 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $sql = "SELECT * FROM sales_orders WHERE id = $id AND company_id = $company_id";
        $result = mysqli_query($conn, $sql);
        if ($row = mysqli_fetch_assoc($result)) {
            $response = ['status' => 'success', 'data' => $row];
        } else {
            throw new Exception("ไม่พบข้อมูลใบสั่งขาย");
        }
    } elseif ($action == 'convert_to_invoice') {
        $id = (int)($_POST['id'] ?? 0);
        $sql = "SELECT * FROM sales_orders WHERE id = $id AND company_id = $company_id";
        $res = mysqli_query($conn, $sql);
        $so = mysqli_fetch_assoc($res);
        if (!$so) throw new Exception("ไม่พบข้อมูลใบสั่งขาย");

        $doc_number = "INV" . date('Ymd') . "-" . sprintf("%03d", rand(1, 999));
        $sql_inv = "INSERT INTO invoices (company_id, issuer_company_id, year, doc_number, doc_date, customer_code, customer_name, customer_address, customer_phone, customer_email, customer_tax_id, payment_terms, items, vat_enabled, vat_type, subtotal, total_discount, vat_amount, grand_total, notes, conditions, signature1, signature2, signer_name1, signer_name2, qr_code_image, header_name, header_address, header_phone, header_tax_id, header_logo, type)
                    VALUES ($company_id, $so[issuer_company_id], $active_year, '$doc_number', CURDATE(), '$so[customer_code]', '$so[customer_name]', '$so[customer_address]', '$so[customer_phone]', '$so[customer_email]', '$so[customer_tax_id]', '$so[payment_terms]', '$so[items]', $so[vat_enabled], '$so[vat_type]', $so[subtotal], $so[total_discount], $so[vat_amount], $so[grand_total], '$so[notes]', '$so[conditions]', '$so[signature1]', '$so[signature2]', '$so[signer_name1]', '$so[signer_name2]', '$so[qr_code_image]', '$so[header_name]', '$so[header_address]', '$so[header_phone]', '$so[header_tax_id]', '$so[header_logo]', 'invoice')";
        
        if (mysqli_query($conn, $sql_inv)) {
            $inv_id = mysqli_insert_id($conn);
            logAction($conn, "แปลงใบสั่งขาย $so[doc_number] เป็นใบแจ้งหนี้ $doc_number", 'convert', $inv_id);
            $response = ['status' => 'success', 'message' => 'แปลงเป็นใบแจ้งหนี้เรียบร้อยแล้ว', 'inv_id' => $inv_id];
        } else throw new Exception(mysqli_error($conn));
    } elseif ($action == 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM sales_orders WHERE id = $id AND company_id = $company_id";
        if (mysqli_query($conn, $sql)) {
            $response = ['status' => 'success', 'message' => 'ลบใบสั่งขายเรียบร้อยแล้ว'];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Action ไม่ถูกต้อง'];
    }

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

$unexpected_output = ob_get_clean();
header('Content-Type: application/json');
echo json_encode($response);
?>

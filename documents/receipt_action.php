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
    if (!isset($conn) || !$conn) {
        throw new Exception("ไม่สามารถเชื่อมต่อฐานข้อมูลได้");
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        throw new Exception("ขนาดข้อมูลใหญ่เกินไป กรุณาลดขนาดรูปภาพ");
    }

    $action = $_REQUEST['action'] ?? '';
    $company_id = $_SESSION['company_id'] ?? 0;
    $active_year = $_SESSION['active_year'] ?? (int)date('Y');

    // Auto-migrate tables if needed
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN issuer_company_id INT DEFAULT NULL");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN header_name VARCHAR(255) DEFAULT NULL");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN header_address TEXT DEFAULT NULL");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN header_phone VARCHAR(50) DEFAULT NULL");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN header_tax_id VARCHAR(50) DEFAULT NULL");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN header_logo LONGTEXT DEFAULT NULL");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN qr_code_image LONGTEXT DEFAULT NULL");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN year INT DEFAULT 2026");
    @mysqli_query($conn, "ALTER TABLE receipts MODIFY COLUMN items LONGTEXT");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN vat_enabled TINYINT(1) DEFAULT 0");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN vat_type VARCHAR(20) DEFAULT 'exclude'");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN subtotal DECIMAL(15,2) DEFAULT 0.00");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN total_discount DECIMAL(15,2) DEFAULT 0.00");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN vat_amount DECIMAL(15,2) DEFAULT 0.00");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN grand_total DECIMAL(15,2) DEFAULT 0.00");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN notes TEXT");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN conditions TEXT");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN signature1 LONGTEXT");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN signature2 LONGTEXT");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN signer_name1 VARCHAR(255)");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN signer_name2 VARCHAR(255)");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN payment_terms TEXT");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN customer_address TEXT");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN customer_phone VARCHAR(50)");
    @mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN customer_tax_id VARCHAR(50)");



    if ($action == 'save') {
        $id = $_POST['id'] ?? null;
        $items = processItemsImages($_POST['items'] ?? '[]', 'uploads/items');
        $signature1 = saveBase64Image($_POST['signature1'] ?? '', 'uploads/signatures');
        $signature2 = saveBase64Image($_POST['signature2'] ?? '', 'uploads/signatures');
        $qr_code_image_raw = saveBase64Image($_POST['qr_code_image'] ?? '', 'uploads/qrcodes');
        
        $header_name = mysqli_real_escape_string($conn, $_POST['header_name'] ?? '');
        $header_address = mysqli_real_escape_string($conn, $_POST['header_address'] ?? '');
        $header_phone = mysqli_real_escape_string($conn, $_POST['header_phone'] ?? '');
        $header_tax_id = mysqli_real_escape_string($conn, $_POST['header_tax_id'] ?? '');
        $header_logo = saveBase64Image($_POST['header_logo'] ?? '', 'uploads/logos');

        $doc_number = mysqli_real_escape_string($conn, $_POST['doc_number'] ?? '');
        $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name'] ?? '');
        $customer_address = mysqli_real_escape_string($conn, $_POST['customer_address'] ?? '');
        $customer_phone = mysqli_real_escape_string($conn, $_POST['customer_phone'] ?? '');
        $customer_tax_id = mysqli_real_escape_string($conn, $_POST['customer_tax_id'] ?? '');
        $items_escaped = mysqli_real_escape_string($conn, $items);
        $signature1_escaped = mysqli_real_escape_string($conn, $signature1);
        $signature2_escaped = mysqli_real_escape_string($conn, $signature2);
        $signer_name1 = mysqli_real_escape_string($conn, $_POST['signer_name1'] ?? '');
        $signer_name2 = mysqli_real_escape_string($conn, $_POST['signer_name2'] ?? '');
        $header_logo_escaped = mysqli_real_escape_string($conn, $header_logo);
        $qr_code_image = mysqli_real_escape_string($conn, $qr_code_image_raw);
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        
        $issuer_company_id = (int)($_POST['issuer_company_id'] ?? $company_id);
        
        $doc_date = mysqli_real_escape_string($conn, $_POST['doc_date'] ?? date('Y-m-d'));
        $vat_enabled = (int)($_POST['vat_enabled'] ?? 0);
        $vat_type = mysqli_real_escape_string($conn, $_POST['vat_type'] ?? 'exclude');
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $total_discount = (float)($_POST['total_discount'] ?? 0);
        $vat_amount = (float)($_POST['vat_amount'] ?? 0);
        $grand_total = (float)($_POST['grand_total'] ?? 0);
        $payment_terms = mysqli_real_escape_string($conn, $_POST['payment_terms'] ?? '');
        $conditions = mysqli_real_escape_string($conn, $_POST['conditions'] ?? '');

        if ($id) {
            $sql = "UPDATE receipts SET 
                    issuer_company_id = $issuer_company_id,
                    doc_number = '$doc_number',
                    doc_date = '$doc_date',
                    customer_name = '$customer_name',
                    customer_address = '$customer_address',
                    customer_phone = '$customer_phone',
                    customer_tax_id = '$customer_tax_id',
                    payment_terms = '$payment_terms',
                    items = '$items_escaped',
                    vat_enabled = $vat_enabled,
                    vat_type = '$vat_type',
                    subtotal = $subtotal,
                    total_discount = $total_discount,
                    vat_amount = $vat_amount,
                    grand_total = $grand_total,
                    notes = '$notes',
                    conditions = '$conditions',
                    signature1 = '$signature1_escaped',
                    signature2 = '$signature2_escaped',
                    signer_name1 = '$signer_name1',
                    signer_name2 = '$signer_name2',
                    header_name = '$header_name',
                    header_address = '$header_address',
                    header_phone = '$header_phone',
                    header_tax_id = '$header_tax_id',
                    header_logo = '$header_logo_escaped',
                    qr_code_image = '$qr_code_image',
                    year = $active_year
                    WHERE id = $id AND company_id = $company_id";
        } else {
            $sql = "INSERT INTO receipts (company_id, issuer_company_id, year, doc_number, doc_date, customer_name, customer_address, customer_phone, customer_tax_id, payment_terms, items, vat_enabled, vat_type, subtotal, total_discount, vat_amount, grand_total, notes, conditions, signature1, signature2, signer_name1, signer_name2, header_name, header_address, header_phone, header_tax_id, header_logo, qr_code_image)
                    VALUES ($company_id, $issuer_company_id, $active_year, '$doc_number', '$doc_date', '$customer_name', '$customer_address', '$customer_phone', '$customer_tax_id', '$payment_terms', '$items_escaped', $vat_enabled, '$vat_type', $subtotal, $total_discount, $vat_amount, $grand_total, '$notes', '$conditions', '$signature1_escaped', '$signature2_escaped', '$signer_name1', '$signer_name2', '$header_name', '$header_address', '$header_phone', '$header_tax_id', '$header_logo_escaped', '$qr_code_image')";
        }
        
        if (mysqli_query($conn, $sql)) {
            $saved_id = $id ?: mysqli_insert_id($conn);
            logReceipt($conn, ($id ? "แก้ไข" : "สร้าง") . "ใบเสร็จรับเงิน: $doc_number", $id ? 'update' : 'create', $saved_id);
            $response = ['status' => 'success', 'message' => 'บันทึกเรียบร้อยแล้ว', 'id' => $saved_id];
        } else {
            $err = mysqli_error($conn);
            file_put_contents('receipt_error.log', date('Y-m-d H:i:s') . " - SAVE ERROR: " . $err . "\nSQL: " . $sql . "\n", FILE_APPEND);
            throw new Exception($err);
        }
    } elseif ($action == 'list') {
        $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
        $sql = "SELECT * FROM receipts WHERE company_id = $company_id " . ($search ? "AND (doc_number LIKE '%$search%' OR customer_name LIKE '%$search%')" : "") . " ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
        $response = ['status' => 'success', 'data' => $data];
    } elseif ($action == 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM receipts WHERE id = $id AND company_id = $company_id";
        if (mysqli_query($conn, $sql)) {
            $response = ['status' => 'success', 'message' => 'ลบใบเสร็จเรียบร้อยแล้ว'];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Action ไม่ถูกต้อง'];
    }

} catch (Throwable $e) {
    file_put_contents('error.log', date('Y-m-d H:i:s') . " - [" . $_SERVER['PHP_SELF'] . "] ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n", FILE_APPEND);
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

$unexpected_output = ob_get_clean();
header('Content-Type: application/json');
echo json_encode($response);
?>

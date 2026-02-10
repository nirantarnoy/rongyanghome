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
        $qr_code_image = mysqli_real_escape_string($conn, saveBase64Image($_POST['qr_code_image'] ?? '', 'uploads/qrcodes'));
        
        $header_name = mysqli_real_escape_string($conn, $_POST['header_name'] ?? '');
        $header_address = mysqli_real_escape_string($conn, $_POST['header_address'] ?? '');
        $header_phone = mysqli_real_escape_string($conn, $_POST['header_phone'] ?? '');
        $header_tax_id = mysqli_real_escape_string($conn, $_POST['header_tax_id'] ?? '');
        $header_logo = mysqli_real_escape_string($conn, saveBase64Image($_POST['header_logo'] ?? '', 'uploads/logos'));
        
        $type = mysqli_real_escape_string($conn, $_POST['type'] ?? 'invoice');
        $issuer_company_id = (int)($_POST['issuer_company_id'] ?? $company_id);
        
        if ($id) {
            $sql = "UPDATE invoices SET 
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
                    qr_code_image = '$qr_code_image',
                    header_name = '$header_name',
                    header_address = '$header_address',
                    header_phone = '$header_phone',
                    header_tax_id = '$header_tax_id',
                    header_logo = '$header_logo',
                    type = '$type',
                    year = $active_year
                    WHERE id = $id AND company_id = $company_id";
        } else {
            $sql = "INSERT INTO invoices (company_id, issuer_company_id, year, doc_number, doc_date, customer_code, customer_name, customer_address, customer_phone, customer_email, customer_tax_id, payment_terms, items, vat_enabled, vat_type, subtotal, total_discount, vat_amount, grand_total, notes, conditions, signature1, signature2, qr_code_image, header_name, header_address, header_phone, header_tax_id, header_logo, type)
                    VALUES ($company_id, $issuer_company_id, $active_year, '$doc_number', '$doc_date', '$customer_code', '$customer_name', '$customer_address', '$customer_phone', '$customer_email', '$customer_tax_id', '$payment_terms', '$items', $vat_enabled, '$vat_type', $subtotal, $total_discount, $vat_amount, $grand_total, '$notes', '$conditions', '$signature1', '$signature2', '$qr_code_image', '$header_name', '$header_address', '$header_phone', '$header_tax_id', '$header_logo', '$type')";
        }
        
        if (mysqli_query($conn, $sql)) {
            $saved_id = $id ?: mysqli_insert_id($conn);
            logAction($conn, ($id ? "แก้ไข" : "สร้าง") . ($type == 'tax_invoice' ? "ใบกำกับภาษี" : "ใบแจ้งหนี้") . ": $doc_number", $id ? 'update' : 'create', $saved_id);
            $response = ['status' => 'success', 'message' => 'บันทึกเรียบร้อยแล้ว', 'id' => $saved_id];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } elseif ($action == 'list') {
        $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
        $type = mysqli_real_escape_string($conn, $_GET['type'] ?? 'invoice');
        $sql = "SELECT * FROM invoices WHERE company_id = $company_id AND type = '$type' " . ($search ? "AND (doc_number LIKE '%$search%' OR customer_name LIKE '%$search%')" : "") . " ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
        $response = ['status' => 'success', 'data' => $data];
    } elseif ($action == 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $sql = "SELECT * FROM invoices WHERE id = $id AND company_id = $company_id";
        $result = mysqli_query($conn, $sql);
        if ($row = mysqli_fetch_assoc($result)) {
            $response = ['status' => 'success', 'data' => $row];
        } else {
            throw new Exception("ไม่พบข้อมูล");
        }
    } elseif ($action == 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM invoices WHERE id = $id AND company_id = $company_id";
        if (mysqli_query($conn, $sql)) {
            $response = ['status' => 'success', 'message' => 'ลบเรียบร้อยแล้ว'];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } elseif ($action == 'convert_to_receipt') {
        $id = (int)($_POST['id'] ?? 0);
        
        // Auto-migrate receipts table if needed
        mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN IF NOT EXISTS payment_terms TEXT AFTER customer_tax_id");
        mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN IF NOT EXISTS total_discount DECIMAL(15,2) DEFAULT 0 AFTER subtotal");
        mysqli_query($conn, "ALTER TABLE receipts ADD COLUMN IF NOT EXISTS conditions TEXT AFTER notes");

        $sql = "SELECT * FROM invoices WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
        mysqli_stmt_execute($stmt);
        $invoice = mysqli_stmt_get_result($stmt)->fetch_assoc();
        
        if (!$invoice) throw new Exception("ไม่พบข้อมูลใบแจ้งหนี้");
        
        // Generate document number: RC-YYYYMMDD-XXX
        $prefix = "RC-" . date('Ymd') . "-";
        $sql_count = "SELECT COUNT(*) as total FROM receipts WHERE company_id = $company_id AND doc_number LIKE '$prefix%'";
        $count_res = mysqli_query($conn, $sql_count);
        $count_row = mysqli_fetch_assoc($count_res);
        $next_num = ($count_row['total'] ?? 0) + 1;
        $doc_number = $prefix . sprintf("%03d", $next_num);

        $sql_receipt = "INSERT INTO receipts (
            company_id, issuer_company_id, year, doc_number, doc_date, 
            customer_name, customer_address, customer_phone, customer_tax_id, 
            payment_terms, items, vat_enabled, vat_type, subtotal, total_discount, 
            vat_amount, grand_total, notes, conditions, signature1, signature2, 
            header_name, header_address, header_phone, header_tax_id, header_logo
        ) VALUES (?, ?, ?, ?, CURRENT_DATE, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $c_id = (int)$invoice['company_id'];
        $i_c_id = (int)$invoice['issuer_company_id'];
        $y = (int)$invoice['year'];
        $c_name = $invoice['customer_name'] ?? '';
        $c_addr = $invoice['customer_address'] ?? '';
        $c_phone = $invoice['customer_phone'] ?? '';
        $c_tax = $invoice['customer_tax_id'] ?? '';
        $p_terms = $invoice['payment_terms'] ?? '';
        $its = $invoice['items'] ?? '[]';
        $v_en = (int)($invoice['vat_enabled'] ?? 0);
        $v_type = $invoice['vat_type'] ?? 'exclude';
        $sub = (float)($invoice['subtotal'] ?? 0);
        $disc = (float)($invoice['total_discount'] ?? 0);
        $v_amt = (float)($invoice['vat_amount'] ?? 0);
        $g_total = (float)($invoice['grand_total'] ?? 0);
        $n = $invoice['notes'] ?? '';
        $cond = $invoice['conditions'] ?? '';
        $s1 = $invoice['signature1'] ?? '';
        $s2 = $invoice['signature2'] ?? '';
        $hn = $invoice['header_name'] ?? '';
        $ha = $invoice['header_address'] ?? '';
        $hp = $invoice['header_phone'] ?? '';
        $ht = $invoice['header_tax_id'] ?? '';
        $hl = $invoice['header_logo'] ?? '';

        $stmt_receipt = mysqli_prepare($conn, $sql_receipt);
        if (!$stmt_receipt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }

        $types = "iiisssssssisddddsssssssss";
        mysqli_stmt_bind_param($stmt_receipt, $types, 
            $c_id, $i_c_id, $y, $doc_number,
            $c_name, $c_addr, $c_phone, $c_tax,
            $p_terms, $its, $v_en, $v_type, 
            $sub, $disc, $v_amt, $g_total,
            $n, $cond, $s1, $s2,
            $hn, $ha, $hp, $ht, $hl
        );
        
        if (mysqli_stmt_execute($stmt_receipt)) {
            $receipt_id = mysqli_insert_id($conn);
            $response = ['status' => 'success', 'message' => 'สร้างใบเสร็จรับเงินเลขที่ ' . $doc_number . ' เรียบร้อยแล้ว', 'receipt_id' => $receipt_id];
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

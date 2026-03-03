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
        $items_raw = processItemsImages($_POST['items'] ?? '[]', 'uploads/items');
        $signature1_raw = saveBase64Image($_POST['signature1'] ?? '', 'uploads/signatures');
        $signature2_raw = saveBase64Image($_POST['signature2'] ?? '', 'uploads/signatures');
        $qr_code_image_raw = saveBase64Image($_POST['qr_code_image'] ?? '', 'uploads/qrcodes');
        $header_logo_raw = saveBase64Image($_POST['header_logo'] ?? '', 'uploads/logos');

        $doc_number = mysqli_real_escape_string($conn, $_POST['doc_number'] ?? '');
        $doc_date = mysqli_real_escape_string($conn, $_POST['doc_date'] ?? '');
        $vendor_code = mysqli_real_escape_string($conn, $_POST['vendor_code'] ?? '');
        $vendor_name = mysqli_real_escape_string($conn, $_POST['vendor_name'] ?? '');
        $vendor_address = mysqli_real_escape_string($conn, $_POST['vendor_address'] ?? '');
        $vendor_phone = mysqli_real_escape_string($conn, $_POST['vendor_phone'] ?? '');
        $vendor_email = mysqli_real_escape_string($conn, $_POST['vendor_email'] ?? '');
        $vendor_tax_id = mysqli_real_escape_string($conn, $_POST['vendor_tax_id'] ?? '');
        $payment_terms = mysqli_real_escape_string($conn, $_POST['payment_terms'] ?? '');
        $items = mysqli_real_escape_string($conn, $items_raw);
        $vat_enabled = (int)($_POST['vat_enabled'] ?? 0);
        $vat_type = mysqli_real_escape_string($conn, $_POST['vat_type'] ?? 'exclude');
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $total_discount = (float)($_POST['total_discount'] ?? 0);
        $vat_amount = (float)($_POST['vat_amount'] ?? 0);
        $grand_total = (float)($_POST['grand_total'] ?? 0);
        $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
        $conditions = mysqli_real_escape_string($conn, $_POST['conditions'] ?? '');
        $signature1 = mysqli_real_escape_string($conn, $signature1_raw);
        $signature2 = mysqli_real_escape_string($conn, $signature2_raw);
        $signer_name1 = mysqli_real_escape_string($conn, $_POST['signer_name1'] ?? '');
        $signer_name2 = mysqli_real_escape_string($conn, $_POST['signer_name2'] ?? '');
        $qr_code_image = mysqli_real_escape_string($conn, $qr_code_image_raw);
        
        $header_name = mysqli_real_escape_string($conn, $_POST['header_name'] ?? '');
        $header_address = mysqli_real_escape_string($conn, $_POST['header_address'] ?? '');
        $header_phone = mysqli_real_escape_string($conn, $_POST['header_phone'] ?? '');
        $header_tax_id = mysqli_real_escape_string($conn, $_POST['header_tax_id'] ?? '');
        $header_logo = mysqli_real_escape_string($conn, $header_logo_raw);
        
        $issuer_company_id = (int)($_POST['issuer_company_id'] ?? $company_id);
        
        if ($id) {
            $sql = "UPDATE goods_receipts SET 
                    issuer_company_id = $issuer_company_id,
                    doc_number = '$doc_number',
                    doc_date = '$doc_date',
                    vendor_code = '$vendor_code',
                    vendor_name = '$vendor_name',
                    vendor_address = '$vendor_address',
                    vendor_phone = '$vendor_phone',
                    vendor_email = '$vendor_email',
                    vendor_tax_id = '$vendor_tax_id',
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
            $sql = "INSERT INTO goods_receipts (company_id, issuer_company_id, year, doc_number, doc_date, vendor_code, vendor_name, vendor_address, vendor_phone, vendor_email, vendor_tax_id, payment_terms, items, vat_enabled, vat_type, subtotal, total_discount, vat_amount, grand_total, notes, conditions, signature1, signature2, signer_name1, signer_name2, qr_code_image, header_name, header_address, header_phone, header_tax_id, header_logo)
                    VALUES ($company_id, $issuer_company_id, $active_year, '$doc_number', '$doc_date', '$vendor_code', '$vendor_name', '$vendor_address', '$vendor_phone', '$vendor_email', '$vendor_tax_id', '$payment_terms', '$items', $vat_enabled, '$vat_type', $subtotal, $total_discount, $vat_amount, $grand_total, '$notes', '$conditions', '$signature1', '$signature2', '$signer_name1', '$signer_name2', '$qr_code_image', '$header_name', '$header_address', '$header_phone', '$header_tax_id', '$header_logo')";
        }
        
        if (mysqli_query($conn, $sql)) {
            $saved_id = $id ?: mysqli_insert_id($conn);
            if (function_exists('logAction')) {
                logAction($conn, ($id ? "แก้ไข" : "สร้าง") . "ใบรับสินค้า: $doc_number", $id ? 'update' : 'create', $saved_id);
            }
            $response = ['status' => 'success', 'message' => 'บันทึกเรียบร้อยแล้ว', 'id' => $saved_id];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } elseif ($action == 'list') {
        $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
        $sql = "SELECT * FROM goods_receipts WHERE company_id = $company_id " . ($search ? "AND (doc_number LIKE '%$search%' OR vendor_name LIKE '%$search%')" : "") . " ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
        $response = ['status' => 'success', 'data' => $data];
    } elseif ($action == 'get_warehouses') {
        $sql = "SELECT id, name FROM stock_warehouses WHERE company_id = $company_id ORDER BY name ASC";
        $result = mysqli_query($conn, $sql);
        if (!$result) throw new Exception(mysqli_error($conn));
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
        $response = ['status' => 'success', 'data' => $data];
    } elseif ($action == 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $sql = "SELECT * FROM goods_receipts WHERE id = $id AND company_id = $company_id";
        $result = mysqli_query($conn, $sql);
        if ($row = mysqli_fetch_assoc($result)) {
            $response = ['status' => 'success', 'data' => $row];
        } else {
            throw new Exception("ไม่พบข้อมูลใบรับสินค้า");
        }
    } elseif ($action == 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM goods_receipts WHERE id = $id AND company_id = $company_id";
        if (mysqli_query($conn, $sql)) {
            $response = ['status' => 'success', 'message' => 'ลบใบรับสินค้าเรียบร้อยแล้ว'];
        } else {
            throw new Exception(mysqli_error($conn));
        }
    } elseif ($action == 'receive_stock') {
        $id = (int)($_POST['id'] ?? 0);
        $warehouse_id = (int)($_POST['warehouse_id'] ?? 1);
        
        $sql = "SELECT * FROM goods_receipts WHERE id = $id AND company_id = $company_id";
        $result = mysqli_query($conn, $sql);
        $gr = mysqli_fetch_assoc($result);
        
        if (!$gr) throw new Exception("ไม่พบข้อมูลใบรับสินค้า");
        if ($gr['is_stocked'] == 1) throw new Exception("ใบรับสินค้านี้รับเข้าคลังไปแล้ว");
        
        $items = json_decode($gr['items'], true);
        if (!$items) throw new Exception("ไม่มีรายการสินค้าในใบรับนี้");
        
        mysqli_begin_transaction($conn);
        try {
            foreach ($items as $item) {
                $item_name = mysqli_real_escape_string($conn, $item['name']);
                $qty = (float)$item['qty'];
                
                $p_sql = "SELECT id FROM stock_products WHERE name = '$item_name' AND company_id = $company_id LIMIT 1";
                $p_res = mysqli_query($conn, $p_sql);
                $product = mysqli_fetch_assoc($p_res);
                
                if (!$product) {
                    $unit = mysqli_real_escape_string($conn, $item['unit'] ?? '');
                    $price = (float)($item['price'] ?? 0);
                    $ins_p = "INSERT INTO stock_products (company_id, year, name, unit, price) VALUES ($company_id, $active_year, '$item_name', '$unit', $price)";
                    mysqli_query($conn, $ins_p);
                    $product_id = mysqli_insert_id($conn);
                } else {
                    $product_id = $product['id'];
                }
                
                $inv_sql = "SELECT id, quantity FROM stock_inventory WHERE product_id = $product_id AND warehouse_id = $warehouse_id AND company_id = $company_id";
                $inv_res = mysqli_query($conn, $inv_sql);
                $inv = mysqli_fetch_assoc($inv_res);
                
                if ($inv) {
                    $new_qty = $inv['quantity'] + $qty;
                    $upd_inv = "UPDATE stock_inventory SET quantity = $new_qty WHERE id = " . $inv['id'];
                } else {
                    $upd_inv = "INSERT INTO stock_inventory (company_id, product_id, warehouse_id, quantity) VALUES ($company_id, $product_id, $warehouse_id, $qty)";
                }
                mysqli_query($conn, $upd_inv);
                
                $tx_note = mysqli_real_escape_string($conn, "รับเข้าจากใบรับสินค้าเลขที่ " . $gr['doc_number']);
                $tx_sql = "INSERT INTO stock_transactions (company_id, year, product_id, warehouse_id, type, qty, note, transaction_date) 
                          VALUES ($company_id, $active_year, $product_id, $warehouse_id, 'in', $qty, '$tx_note', '{$gr['doc_date']}')";
                mysqli_query($conn, $tx_sql);
            }
            
            $upd_gr = "UPDATE goods_receipts SET is_stocked = 1 WHERE id = $id";
            mysqli_query($conn, $upd_gr);
            
            mysqli_commit($conn);
            $response = ['status' => 'success', 'message' => 'รับสินค้าเข้าคลังเรียบร้อยแล้ว'];
        } catch (Exception $e) {
            mysqli_rollback($conn);
            throw $e;
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

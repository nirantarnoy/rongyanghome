<?php
require '../auth_check.php';
require '../config.php';
require '../thai_baht_helper.php';

$company_id = $_SESSION['company_id'];
$edit_id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'invoice';
$doc_data = null;

// Get current company info
$company_sql = "SELECT * FROM company WHERE id = ?";
$company_stmt = mysqli_prepare($conn, $company_sql);
mysqli_stmt_bind_param($company_stmt, "i", $company_id);
mysqli_stmt_execute($company_stmt);
$company_res = mysqli_stmt_get_result($company_stmt);
$company = mysqli_fetch_assoc($company_res);

// Get all companies for selection
$all_companies_sql = "SELECT id, company_name, address, phone, tax_id, logo FROM company ORDER BY company_name ASC";
$all_companies_res = mysqli_query($conn, $all_companies_sql);
$all_companies = [];
while ($row = mysqli_fetch_assoc($all_companies_res)) {
    $all_companies[] = $row;
}

// Load data if editing
if ($edit_id) {
    $sql = "SELECT * FROM invoices WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $edit_id, $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $doc_data = mysqli_fetch_assoc($result);
    if ($doc_data) {
        $type = $doc_data['type'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_id ? 'แก้ไข' : 'สร้าง' ?> <?= $type == 'tax_invoice' ? 'ใบกำกับภาษี' : 'ใบแจ้งหนี้' ?> - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; margin: 0; padding: 0; }
            .print-container { 
                width: 210mm; 
                min-height: 297mm; 
                padding: 10mm; 
                margin: 0 auto;
                background: white;
                box-shadow: none;
            }
            @page {
                size: A4;
                margin: 0;
            }
        }
        
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .doc-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        .doc-company-info {
            text-align: center;
            flex: 1;
        }
        .doc-title {
            font-size: 24px;
            font-weight: bold;
            text-align: right;
            width: 200px;
            color: #d32f2f;
        }
        .doc-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .doc-table th {
            background-color: #92d050;
            color: black;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        .doc-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        .doc-footer {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            border: 1px solid #000;
        }
        .doc-footer-left {
            padding: 10px;
            border-right: 1px solid #000;
        }
        .doc-footer-right {
            background-color: #e2efda;
        }
        .doc-footer-right table {
            width: 100%;
            border-collapse: collapse;
        }
        .doc-footer-right td {
            padding: 5px 10px;
            border: 1px solid #000;
        }
        .doc-amount-words {
            text-align: center;
            padding: 10px;
            border-top: 1px solid #000;
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .doc-signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            text-align: center;
        }
        .signature-box {
            width: 200px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 60px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        .signature-preview {
            max-width: 150px;
            max-height: 80px;
            object-fit: contain;
        }
        .btn-issue-tax {
            background-color: #10b981;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }
        .btn-issue-tax:hover {
            background-color: #059669;
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="no-print bg-gradient-to-r from-emerald-600 to-emerald-700 text-white p-4 shadow-lg">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">📄 <?= $edit_id ? 'แก้ไข' : 'สร้าง' ?> <?= $type == 'tax_invoice' ? 'ใบกำกับภาษี' : 'ใบแจ้งหนี้' ?></h1>
        <a href="index.php?tab=<?= $type == 'tax_invoice' ? 'tax_invoice' : 'invoice' ?>" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">← กลับ</a>
    </div>
</div>

<div class="max-w-5xl mx-auto p-6 no-print">
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 font-sarabun">ข้อมูล<?= $type == 'tax_invoice' ? '<span class="text-rose-600">ใบกำกับภาษี</span>' : 'ใบแจ้งหนี้' ?></h2>
        
        <input type="hidden" id="doc_id" value="<?= $edit_id ?? '' ?>">
        <input type="hidden" id="doc_type" value="<?= $type ?>">
        
        <div class="mb-6 bg-emerald-50 p-6 rounded-2xl border border-emerald-100">
            <label class="block text-sm font-bold text-emerald-800 mb-4 flex items-center gap-2">
                <i class="fas fa-building text-lg"></i>
                ข้อมูลหัวเอกสาร (สามารถแก้ไขได้)
            </label>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">เลือกบริษัทต้นแบบ</label>
                    <select id="issuer_company_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500" onchange="loadCompanyTemplate()">
                        <option value="">-- กำหนดเอง --</option>
                        <?php foreach ($all_companies as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                data-name="<?= htmlspecialchars($c['company_name']) ?>" 
                                data-address="<?= htmlspecialchars($c['address']) ?>" 
                                data-phone="<?= htmlspecialchars($c['phone']) ?>" 
                                data-taxid="<?= htmlspecialchars($c['tax_id']) ?>"
                                data-logo="<?= htmlspecialchars($c['logo'] ?? '') ?>"
                                <?= ($doc_data['issuer_company_id'] ?? $company_id) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ชื่อบริษัทในหัวเอกสาร</label>
                    <input type="text" id="header_name" value="<?= htmlspecialchars($doc_data['header_name'] ?? $company['company_name'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ที่อยู่ในหัวเอกสาร</label>
                    <textarea id="header_address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"><?= htmlspecialchars($doc_data['header_address'] ?? $company['address'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เบอร์โทรศัพท์</label>
                    <input type="text" id="header_phone" value="<?= htmlspecialchars($doc_data['header_phone'] ?? $company['phone'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" id="header_tax_id" value="<?= htmlspecialchars($doc_data['header_tax_id'] ?? $company['tax_id'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">โลโก้หัวเอกสาร</label>
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden bg-white">
                            <img id="header_logo_preview" src="<?= $doc_data['header_logo'] ?? ($company['logo'] ? '../'.$company['logo'] : '') ?>" class="<?= (empty($doc_data['header_logo']) && empty($company['logo'])) ? 'hidden' : '' ?> w-full h-full object-contain">
                            <i id="header_logo_placeholder" class="<?= (!empty($doc_data['header_logo']) || !empty($company['logo'])) ? 'hidden' : '' ?> fas fa-image text-3xl text-gray-300"></i>
                        </div>
                        <input type="file" id="header_logo_input" accept="image/*" onchange="previewHeaderLogo()" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลขที่เอกสาร</label>
                <input type="text" id="doc_number" value="<?= $doc_data['doc_number'] ?? '' ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่</label>
                <input type="date" id="doc_date" value="<?= $doc_data['doc_date'] ?? date('Y-m-d') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>

        <div class="border-t pt-8 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                <i class="fas fa-user-tie text-emerald-600"></i>
                ข้อมูลลูกค้า (Customer)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสลูกค้า</label>
                    <input type="text" id="customer_code" value="<?= htmlspecialchars($doc_data['customer_code'] ?? '') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อลูกค้า / บริษัท</label>
                    <input type="text" id="customer_name" value="<?= htmlspecialchars($doc_data['customer_name'] ?? '') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">ที่อยู่</label>
                <textarea id="customer_address" rows="2" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 resize-none"><?= htmlspecialchars($doc_data['customer_address'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">โทรศัพท์</label>
                    <input type="text" id="customer_phone" value="<?= htmlspecialchars($doc_data['customer_phone'] ?? '') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                    <input type="email" id="customer_email" value="<?= htmlspecialchars($doc_data['customer_email'] ?? '') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผู้เสียภาษี</label>
                    <input type="text" id="customer_tax_id" value="<?= htmlspecialchars($doc_data['customer_tax_id'] ?? '') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เงื่อไขการชำระเงิน</label>
                <input type="text" id="payment_terms" value="<?= htmlspecialchars($doc_data['payment_terms'] ?? 'เงินสด/โอนเงิน') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">QR Code สำหรับชำระเงิน</label>
                <div class="flex items-center gap-4">
                    <input type="file" id="qr_code" accept="image/*" onchange="previewQRCode()" class="flex-1 text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700">
                    <img id="qr_preview" src="<?= $doc_data['qr_code_image'] ?? '' ?>" class="<?= empty($doc_data['qr_code_image']) ? 'hidden' : '' ?> w-16 h-16 object-contain border rounded-xl shadow-sm">
                </div>
            </div>
        </div>

        <div class="border-t pt-8 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-list-ul text-emerald-600"></i>
                    รายการสินค้า
                </h3>
                <button onclick="addItem()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl transition-all flex items-center gap-2 shadow-md">
                    <i class="fas fa-plus"></i>
                    เพิ่มรายการ
                </button>
            </div>

            <div id="items-container" class="space-y-4">
                <!-- Items will be added here -->
            </div>
        </div>

        <div class="border-t pt-8 mb-8 space-y-4">
            <div class="flex items-center gap-4">
                <input type="checkbox" id="vat_enabled" <?= ($doc_data['vat_enabled'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 text-emerald-600 rounded">
                <label for="vat_enabled" class="text-sm font-bold text-gray-700">คิด VAT 7%</label>
            </div>

            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="vat_type" value="exclude" <?= ($doc_data['vat_type'] ?? 'exclude') == 'exclude' ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600">
                    <span class="text-sm text-gray-700">ราคายังไม่รวม VAT</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="vat_type" value="include" <?= ($doc_data['vat_type'] ?? 'exclude') == 'include' ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600">
                    <span class="text-sm text-gray-700">ราคารวม VAT แล้ว</span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ</label>
                <textarea id="notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 resize-none"><?= htmlspecialchars($doc_data['notes'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เงื่อนไข</label>
                <textarea id="conditions" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 resize-none"><?= htmlspecialchars($doc_data['conditions'] ?? "") ?></textarea>
            </div>
        </div>

        <div class="border-t pt-8 mb-10">
            <h3 class="text-lg font-bold text-gray-800 mb-6">ลายเซ็น</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">ลายเซ็นผู้เสนอราคา</label>
                    <input type="file" id="signature1" accept="image/*" onchange="previewSignature(1)" class="w-full text-sm text-gray-500 mb-2">
                    <img id="sig1_preview" src="<?= $doc_data['signature1'] ?? '' ?>" class="signature-preview <?= empty($doc_data['signature1']) ? 'hidden' : '' ?> border rounded-xl bg-gray-50">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">ลายเซ็นผู้อนุมัติ</label>
                    <input type="file" id="signature2" accept="image/*" onchange="previewSignature(2)" class="w-full text-sm text-gray-500 mb-2">
                    <img id="sig2_preview" src="<?= $doc_data['signature2'] ?? '' ?>" class="signature-preview <?= empty($doc_data['signature2']) ? 'hidden' : '' ?> border rounded-xl bg-gray-50">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 no-print border-t pt-8">
            <button onclick="saveDoc()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-4 rounded-2xl font-bold transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> บันทึก
            </button>
            <button onclick="generatePreview()" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-4 rounded-2xl font-bold transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2">
                <i class="fas fa-eye"></i> ดูตัวอย่าง
            </button>
            <button onclick="exportPDF()" class="bg-rose-500 hover:bg-rose-600 text-white px-6 py-4 rounded-2xl font-bold transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button onclick="printDoc()" class="bg-slate-700 hover:bg-slate-800 text-white px-6 py-4 rounded-2xl font-bold transition-all shadow-lg active:scale-95 flex items-center justify-center gap-2">
                <i class="fas fa-print"></i> พิมพ์ A4
            </button>
        </div>

        <?php if ($edit_id && $type == 'invoice'): ?>
        <div class="mt-4 flex justify-center no-print border-t pt-8">
            <button onclick="issueTaxInvoice()" class="btn-issue-tax">
                <i class="fas fa-file-invoice-dollar text-xl"></i>
                ออกใบกำกับภาษี
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print Area -->
<div id="print-area" class="print-container bg-white shadow-2xl mx-auto my-10 p-10 hidden border">
    <!-- Content will be generated by JS -->
</div>

<script>
let itemCount = 0;
const existingItems = <?= json_encode($doc_data['items'] ?? '[]') ?>;

$(document).ready(function() {
    if (existingItems && existingItems !== '[]') {
        try {
            const items = JSON.parse(existingItems);
            items.forEach(item => addItem(item));
        } catch (e) {
            addItem();
        }
    } else {
        addItem();
    }
});

function addItem(data = null) {
    itemCount++;
    const html = `
        <div class="item-row border border-gray-100 rounded-2xl p-4 bg-gray-50/50" data-item="${itemCount}">
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-12 md:col-span-4">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">รายการ</label>
                    <input type="text" class="item-name w-full px-3 py-2 border border-gray-200 rounded-xl text-sm" value="${data?.name || ''}">
                </div>
                <div class="col-span-3 md:col-span-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">จำนวน</label>
                    <input type="number" class="item-qty w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-center" value="${data?.qty || 1}" step="0.01" onchange="calculateDocTotal()">
                </div>
                <div class="col-span-3 md:col-span-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">หน่วย</label>
                    <input type="text" class="item-unit w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-center" value="${data?.unit || ''}">
                </div>
                <div class="col-span-3 md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">ราคา/หน่วย</label>
                    <input type="number" class="item-price w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-right" value="${data?.price || 0}" step="0.01" onchange="calculateDocTotal()">
                </div>
                <div class="col-span-3 md:col-span-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">ส่วนลด</label>
                    <input type="number" class="item-discount w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-right" value="${data?.discount || 0}" step="0.01" onchange="calculateDocTotal()">
                </div>
                <div class="col-span-9 md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">รวม</label>
                    <input type="text" class="item-total w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-bold text-right" readonly value="0">
                </div>
                <div class="col-span-3 md:col-span-1 flex items-end">
                    <button onclick="removeItem(${itemCount})" class="w-full h-[38px] flex items-center justify-center bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white rounded-xl transition-all border border-rose-100">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    $('#items-container').append(html);
    calculateDocTotal();
}

function removeItem(id) {
    if ($('.item-row').length > 1) {
        $(`.item-row[data-item="${id}"]`).remove();
        calculateDocTotal();
    }
}

function calculateDocTotal() {
    let subtotal = 0;
    let totalDiscount = 0;
    
    $('.item-row').each(function() {
        const qty = parseFloat($(this).find('.item-qty').val()) || 0;
        const price = parseFloat($(this).find('.item-price').val()) || 0;
        const discount = parseFloat($(this).find('.item-discount').val()) || 0;
        const total = (qty * price) - discount;
        
        $(this).find('.item-total').val(total.toLocaleString(undefined, {minimumFractionDigits: 2}));
        subtotal += (qty * price);
        totalDiscount += discount;
    });
    
    return { subtotal, totalDiscount };
}

function previewSignature(num) {
    const input = document.getElementById(`signature${num}`);
    const preview = document.getElementById(`sig${num}_preview`);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewHeaderLogo() {
    const input = document.getElementById('header_logo_input');
    const preview = document.getElementById('header_logo_preview');
    const placeholder = document.getElementById('header_logo_placeholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function loadCompanyTemplate() {
    const s = $('#issuer_company_id option:selected');
    if (s.val()) {
        $('#header_name').val(s.data('name'));
        $('#header_address').val(s.data('address'));
        $('#header_phone').val(s.data('phone'));
        $('#header_tax_id').val(s.data('taxid'));
        if (s.data('logo')) {
            $('#header_logo_preview').attr('src', '../' + s.data('logo')).removeClass('hidden');
            $('#header_logo_placeholder').addClass('hidden');
        }
    }
}

function previewQRCode() {
    const input = document.getElementById('qr_code');
    const preview = document.getElementById('qr_preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => { preview.src = e.target.result; preview.classList.remove('hidden'); };
        reader.readAsDataURL(input.files[0]);
    }
}

function saveDoc() {
    const items = [];
    $('.item-row').each(function() {
        const n = $(this).find('.item-name').val().trim();
        if (n) {
            items.push({
                name: n,
                qty: $(this).find('.item-qty').val(),
                unit: $(this).find('.item-unit').val().trim(),
                price: $(this).find('.item-price').val(),
                discount: $(this).find('.item-discount').val()
            });
        }
    });

    const totals = calculateDocTotal();
    const vatEnabled = $('#vat_enabled').is(':checked') ? 1 : 0;
    const vatType = $('input[name="vat_type"]:checked').val();
    
    let vatAmount = 0;
    let net = totals.subtotal - totals.totalDiscount;
    if (vatEnabled) {
        if (vatType === 'exclude') {
            vatAmount = net * 0.07;
            net += vatAmount;
        } else {
            vatAmount = net - (net / 1.07);
        }
    }

    const data = {
        action: 'save',
        id: $('#doc_id').val(),
        type: $('#doc_type').val(),
        doc_number: $('#doc_number').val().trim(),
        doc_date: $('#doc_date').val(),
        customer_code: $('#customer_code').val().trim(),
        customer_name: $('#customer_name').val().trim(),
        customer_address: $('#customer_address').val().trim(),
        customer_phone: $('#customer_phone').val().trim(),
        customer_email: $('#customer_email').val().trim(),
        customer_tax_id: $('#customer_tax_id').val().trim(),
        payment_terms: $('#payment_terms').val().trim(),
        items: JSON.stringify(items),
        vat_enabled: vatEnabled,
        vat_type: vatType,
        subtotal: totals.subtotal,
        total_discount: totals.totalDiscount,
        vat_amount: vatAmount,
        grand_total: net,
        notes: $('#notes').val().trim(),
        conditions: $('#conditions').val().trim(),
        issuer_company_id: $('#issuer_company_id').val(),
        header_name: $('#header_name').val(),
        header_address: $('#header_address').val(),
        header_phone: $('#header_phone').val(),
        header_tax_id: $('#header_tax_id').val(),
        header_logo: $('#header_logo_preview').attr('src') || '',
        signature1: $('#sig1_preview').attr('src') || '',
        signature2: $('#sig2_preview').attr('src') || '',
        qr_code_image: $('#qr_preview').attr('src') || ''
    };

    if (!data.doc_number || !data.customer_name) {
        Swal.fire('คำเตือน', 'กรุณากรอกเลขที่และชื่อลูกค้า', 'warning');
        return;
    }

    $.ajax({
        url: 'invoice_action.php',
        type: 'POST',
        data: data,
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire('สำเร็จ', res.message, 'success').then(() => {
                    if (!$('#doc_id').val()) window.location.href = 'invoice_form.php?id=' + res.id;
                });
            } else Swal.fire('ผิดพลาด', res.message, 'error');
        }
    });
}

function issueTaxInvoice() {
    Swal.fire({
        title: 'ยืนยันการออกใบกำกับภาษี?',
        text: 'ระบบจะเปลี่ยนประเภทเอกสารฉบับนี้เป็นใบกำกับภาษี',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'invoice_action.php',
                type: 'POST',
                data: { action: 'convert_to_tax_invoice', id: $('#doc_id').val() },
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('สำเร็จ', res.message, 'success').then(() => {
                            window.location.reload();
                        });
                    } else Swal.fire('ผิดพลาด', res.message, 'error');
                }
            });
        }
    });
}

function generatePreview() {
    const totals = calculateDocTotal();
    const vatEnabled = $('#vat_enabled').is(':checked');
    const vatType = $('input[name="vat_type"]:checked').val();
    
    let subtotal = totals.subtotal;
    let disc = totals.totalDiscount;
    let net = subtotal - disc;
    let vat = 0;
    let grand = net;
    
    if (vatEnabled) {
        if (vatType === 'exclude') { vat = net * 0.07; grand += vat; }
        else { vat = net - (net / 1.07); }
    }

    let itemRows = '';
    $('.item-row').each((i, el) => {
        const q = parseFloat($(el).find('.item-qty').val()) || 0;
        const p = parseFloat($(el).find('.item-price').val()) || 0;
        const d = parseFloat($(el).find('.item-discount').val()) || 0;
        const t = (q * p) - d;
        itemRows += `
            <tr>
                <td style="text-align:center">${i+1}</td>
                <td>${$(el).find('.item-name').val()}</td>
                <td style="text-align:right">${q.toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                <td style="text-align:center">${$(el).find('.item-unit').val()}</td>
                <td style="text-align:right">${p.toLocaleString(undefined,{minimumFractionDigits:2})}</td>
                <td style="text-align:right">${d > 0 ? d.toLocaleString(undefined,{minimumFractionDigits:2}) : '-'}</td>
                <td style="text-align:right">${t.toLocaleString(undefined,{minimumFractionDigits:2})}</td>
            </tr>`;
    });
    for(let i=$('.item-row').length; i<10; i++) itemRows += '<tr><td style="height:25px"></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';

    const title = $('#doc_type').val() === 'tax_invoice' ? 'ใบกำกับภาษี' : 'ใบแจ้งหนี้';
    const amountWords = ThaiBaht(grand);

    const html = `
        <div class="doc-header">
            <img src="${$('#header_logo_preview').attr('src') || '../assets/logo/logo.png'}" class="doc-logo">
            <div class="doc-company-info">
                <div style="font-weight:bold; font-size:18px">${$('#header_name').val()}</div>
                <div style="font-size:13px">${$('#header_address').val()}</div>
                <div style="font-size:13px">โทร. ${$('#header_phone').val()} เลประจำตัวผู้เสียภาษี ${$('#header_tax_id').val()}</div>
            </div>
            <div class="doc-title">${title}</div>
        </div>
        <div class="doc-info-grid">
            <div style="font-size:14px">
                <div>รหัส : ${$('#customer_code').val()}</div>
                <div>ชื่อ : ${$('#customer_name').val()}</div>
                <div>ที่อยู่ : ${$('#customer_address').val()}</div>
                <div>โทรศัทพ์ : ${$('#customer_phone').val()} รหัสผู้เสียภาษี : ${$('#customer_tax_id').val()}</div>
            </div>
            <div style="text-align:right; font-size:14px">
                <div style="font-weight:bold; font-size:16px">${title}เลขที่ : ${$('#doc_number').val()}</div>
                <div>วันที่ : ${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</div>
                <div>เงื่อนไขการชำระ : ${$('#payment_terms').val()}</div>
            </div>
        </div>
        <table class="doc-table">
            <thead><tr><th style="width:50px">ลำดับ</th><th>รายการ</th><th style="width:80px">จำนวน</th><th style="width:80px">หน่วย</th><th style="width:100px">ราคา</th><th style="width:80px">ส่วนลด</th><th style="width:120px">รวมเงิน</th></tr></thead>
            <tbody>${itemRows}</tbody>
        </table>
        <div class="doc-footer">
            <div class="doc-footer-left">
                <div style="font-size:12px; margin-bottom:10px">หมายเหตุ: <span style="white-space:pre-line">${$('#notes').val()}</span></div>
                <div style="font-size:11px; white-space:pre-line">${$('#conditions').val()}</div>
            </div>
            <div class="doc-footer-right">
                <table>
                    <tr><td>มูลค่าก่อนภาษี</td><td style="text-align:right">${(net - (vatType === 'include' ? vat : 0)).toLocaleString(undefined,{minimumFractionDigits:2})}</td></tr>
                    <tr><td>ภาษีมูลค่าเพิ่ม (7%)</td><td style="text-align:right">${vat.toLocaleString(undefined,{minimumFractionDigits:2})}</td></tr>
                    <tr style="background:#92d050; font-weight:bold"><td>ยอดเงินสุทธิ</td><td style="text-align:right">${grand.toLocaleString(undefined,{minimumFractionDigits:2})}</td></tr>
                </table>
            </div>
        </div>
        <div class="doc-amount-words">( ${amountWords} )</div>
        <div class="doc-signatures">
            <div style="width:120px">${$('#qr_preview').attr('src') ? `<img src="${$('#qr_preview').attr('src')}" style="width:100%">` : ''}</div>
            <div class="signature-box"><div>ผู้เสนอราคา</div><div class="signature-line">${$('#sig1_preview').attr('src') ? `<img src="${$('#sig1_preview').attr('src')}" class="signature-preview">` : ''}</div></div>
            <div class="signature-box"><div>ผู้อนุมัติ</div><div class="signature-line">${$('#sig2_preview').attr('src') ? `<img src="${$('#sig2_preview').attr('src')}" class="signature-preview">` : ''}</div></div>
        </div>`;
    $('#print-area').html(html).removeClass('hidden');
    $('html, body').animate({ scrollTop: $("#print-area").offset().top }, 500);
}

function printDoc() { generatePreview(); setTimeout(() => { window.print(); }, 500); }
function exportPDF() {
    generatePreview();
    const element = document.getElementById('print-area');
    const opt = { margin: 10, filename: $('#doc_number').val() + '.pdf', image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2 }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } };
    html2pdf().set(opt).from(element).save();
}

function ThaiBaht(number) {
    if (isNaN(number)) return "";
    number = number.toFixed(2);
    const [baht, satang] = number.split('.');
    const convert = (num) => {
        const tN = ["ศูนย์", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
        const tU = ["", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน"];
        let res = "";
        for (let i = 0; i < num.length; i++) {
            let d = parseInt(num[i]);
            let u = num.length - i - 1;
            if (d !== 0) {
                if (u % 6 === 1 && d === 2) res += "ยี่";
                else if (u % 6 === 1 && d === 1) res += "";
                else if (u % 6 === 0 && d === 1 && i > 0 && num[i-1] != '0') res += "เอ็ด";
                else res += tN[d];
                res += tU[u % 6];
                if (u >= 6 && u % 6 === 0) res += "ล้าน";
            }
        }
        return res;
    };
    let res = parseInt(baht) === 0 ? "ศูนย์" : convert(baht);
    res += "บาท";
    if (parseInt(satang) === 0) res += "ถ้วน";
    else res += convert(satang) + "สตางค์";
    return res;
}
</script>
</body>
</html>

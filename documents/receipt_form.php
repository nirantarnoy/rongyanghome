<?php
require '../auth_check.php';
require '../config.php';

$company_id = $_SESSION['company_id'];
$edit_id = $_GET['id'] ?? null;
$receipt_data = null;

// Get current company info
$company_sql = "SELECT * FROM company WHERE id = ?";
$company_stmt = mysqli_prepare($conn, $company_sql);
mysqli_stmt_bind_param($company_stmt, "i", $company_id);
mysqli_stmt_execute($company_stmt);
$company_res = mysqli_stmt_get_result($company_stmt);
$company = mysqli_fetch_assoc($company_res);

// Get all companies for selection
$all_companies_sql = "SELECT id, company_name, address, phone, tax_id FROM company ORDER BY company_name ASC";
$all_companies_res = mysqli_query($conn, $all_companies_sql);
$all_companies = [];
while ($row = mysqli_fetch_assoc($all_companies_res)) {
    $all_companies[] = $row;
}

// Load receipt data if editing
if ($edit_id) {
    $sql = "SELECT * FROM receipts WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $edit_id, $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $receipt_data = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบเสร็จรับเงิน - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
        .signature-preview {
            max-width: 150px;
            max-height: 80px;
            object-fit: contain;
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="no-print bg-gradient-to-r from-purple-600 to-purple-700 text-white p-4 shadow-lg">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">📄 <?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบเสร็จรับเงิน/ใบกำกับภาษี</h1>
        <a href="index.php?tab=receipt" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">← กลับ</a>
    </div>
</div>

<div class="max-w-5xl mx-auto p-6">
    <!-- Form Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 no-print">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">ข้อมูลใบเสร็จรับเงิน</h2>
        
        <input type="hidden" id="receipt_id" value="<?= $edit_id ?? '' ?>">
        
        <div class="mb-6 bg-purple-50 p-4 rounded-xl border border-purple-100">
            <label class="block text-sm font-bold text-purple-800 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                ข้อมูลหัวเอกสาร (สามารถแก้ไขได้)
            </label>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">เลือกบริษัทต้นแบบ</label>
                    <select id="issuer_company_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" onchange="loadCompanyTemplate()">
                        <option value="">-- กำหนดเอง --</option>
                        <?php foreach ($all_companies as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                data-name="<?= htmlspecialchars($c['company_name']) ?>" 
                                data-address="<?= htmlspecialchars($c['address']) ?>" 
                                data-phone="<?= htmlspecialchars($c['phone']) ?>" 
                                data-taxid="<?= htmlspecialchars($c['tax_id']) ?>"
                                data-logo="<?= htmlspecialchars($c['logo'] ?? '') ?>"
                                <?= ($receipt_data['issuer_company_id'] ?? $company_id) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ชื่อบริษัทในหัวเอกสาร</label>
                    <input type="text" id="header_name" value="<?= htmlspecialchars($receipt_data['header_name'] ?? $company['company_name'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ที่อยู่ในหัวเอกสาร</label>
                    <textarea id="header_address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($receipt_data['header_address'] ?? $company['address'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เบอร์โทรศัพท์</label>
                    <input type="text" id="header_phone" value="<?= htmlspecialchars($receipt_data['header_phone'] ?? $company['phone'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" id="header_tax_id" value="<?= htmlspecialchars($receipt_data['header_tax_id'] ?? $company['tax_id'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">โลโก้หัวเอกสาร</label>
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden bg-white">
                            <img id="header_logo_preview" src="<?= $receipt_data['header_logo'] ?? ($company['logo'] ? '../'.$company['logo'] : '') ?>" class="<?= (empty($receipt_data['header_logo']) && empty($company['logo'])) ? 'hidden' : '' ?> w-full h-full object-contain">
                            <svg id="header_logo_placeholder" class="<?= (!empty($receipt_data['header_logo']) || !empty($company['logo'])) ? 'hidden' : '' ?> w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="file" id="header_logo_input" accept="image/*" onchange="previewHeaderLogo()" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลขที่เอกสาร</label>
                <input type="text" id="doc_number" value="<?= $receipt_data['doc_number'] ?? '' ?>" placeholder="เช่น RC-<?= date('Ymd') ?>-001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่</label>
                <input type="date" id="doc_date" value="<?= $receipt_data['doc_date'] ?? date('Y-m-d') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ / ผู้ซื้อ</label>
                <input type="text" id="customer_name" value="<?= htmlspecialchars($receipt_data['customer_name'] ?? '') ?>" placeholder="กรอกชื่อลูกค้า" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ที่อยู่</label>
                <input type="text" id="customer_address" value="<?= htmlspecialchars($receipt_data['customer_address'] ?? '') ?>" placeholder="กรอกที่อยู่" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">โทรศัพท์</label>
                <input type="text" id="customer_phone" value="<?= htmlspecialchars($receipt_data['customer_phone'] ?? '') ?>" placeholder="เบอร์โทรศัพท์" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลขประจำตัวผู้เสียภาษี</label>
                <input type="text" id="customer_tax_id" value="<?= htmlspecialchars($receipt_data['customer_tax_id'] ?? '') ?>" placeholder="เลขประจำตัวผู้เสียภาษี" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>
        </div>

        <div class="border-t pt-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">รายการสินค้า</h3>
                <button onclick="addItem()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    เพิ่มรายการ
                </button>
            </div>

            <div id="items-container" class="space-y-4">
                <!-- Items will be added here -->
            </div>
        </div>

        <div class="border-t pt-6 mb-6">
            <div class="flex items-center gap-4 mb-4">
                <input type="checkbox" id="vat_enabled" <?= ($receipt_data['vat_enabled'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 text-purple-600 rounded">
                <label for="vat_enabled" class="text-sm font-medium text-gray-700">คิด VAT 7%</label>
            </div>

            <div class="flex items-center gap-4 mb-4">
                <input type="radio" name="vat_type" value="exclude" id="vat_exclude" <?= ($receipt_data['vat_type'] ?? 'exclude') == 'exclude' ? 'checked' : '' ?> class="w-4 h-4 text-purple-600">
                <label for="vat_exclude" class="text-sm text-gray-700">ราคายังไม่รวม VAT</label>
                
                <input type="radio" name="vat_type" value="include" id="vat_include" <?= ($receipt_data['vat_type'] ?? 'exclude') == 'include' ? 'checked' : '' ?> class="w-4 h-4 text-purple-600">
                <label for="vat_include" class="text-sm text-gray-700">ราคารวม VAT แล้ว</label>
            </div>
        </div>

        <div class="border-t pt-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-2">หมายเหตุ</h3>
            <textarea id="notes" rows="4" placeholder="บันทึกหมายเหตุเพิ่มเติม (ถ้ามี)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none"><?= htmlspecialchars($receipt_data['notes'] ?? '') ?></textarea>
        </div>

        <div class="border-t pt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">ลายเซ็น</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้รับเงิน</label>
                    <input type="file" id="signature1" accept="image/*" onchange="previewSignature(1)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    <img id="sig1_preview" src="<?= $receipt_data['signature1'] ?? '' ?>" class="signature-preview mt-2 <?= empty($receipt_data['signature1']) ? 'hidden' : '' ?> border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้อนุมัติ</label>
                    <input type="file" id="signature2" accept="image/*" onchange="previewSignature(2)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    <img id="sig2_preview" src="<?= $receipt_data['signature2'] ?? '' ?>" class="signature-preview mt-2 <?= empty($receipt_data['signature2']) ? 'hidden' : '' ?> border rounded">
                </div>
            </div>
        </div>

        <div class="flex gap-4 mt-8">
            <button onclick="saveReceipt()" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                💾 บันทึก
            </button>
            <button onclick="generatePreview()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                👁️ ดูตัวอย่าง
            </button>
            <button onclick="exportPDF()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                📄 Export PDF
            </button>
            <button onclick="printReceipt()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                🖨️ Print A4
            </button>
        </div>
    </div>

    <!-- Preview Section -->
    <div id="receipt-preview" class="bg-white rounded-2xl shadow-lg p-8" style="display: none;">
        <!-- Content will be generated here -->
    </div>
</div>

<script>
let itemCount = 0;
const existingItems = <?= json_encode($receipt_data['items'] ?? '[]') ?>;

$(document).ready(function() {
    if (existingItems && existingItems.length > 0) {
        JSON.parse(existingItems).forEach(item => {
            addItem(item);
        });
    } else {
        addItem();
    }
});

function loadCompanyTemplate() {
    const selected = $('#issuer_company_id option:selected');
    if (selected.val()) {
        $('#header_name').val(selected.data('name'));
        $('#header_address').val(selected.data('address'));
        $('#header_phone').val(selected.data('phone'));
        $('#header_tax_id').val(selected.data('taxid'));
        
        const logo = selected.data('logo');
        if (logo) {
            $('#header_logo_preview').attr('src', '../' + logo).removeClass('hidden');
            $('#header_logo_placeholder').addClass('hidden');
        } else {
            $('#header_logo_preview').addClass('hidden').attr('src', '');
            $('#header_logo_placeholder').removeClass('hidden');
        }
    }
}

function previewHeaderLogo() {
    const input = document.getElementById('header_logo_input');
    const preview = document.getElementById('header_logo_preview');
    const placeholder = document.getElementById('header_logo_placeholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function addItem(data = null) {
    itemCount++;
    const html = `
        <div class="item-row border border-gray-200 rounded-lg p-4 bg-gray-50" data-item="${itemCount}">
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">รายการ</label>
                    <input type="text" class="item-name w-full px-3 py-2 border border-gray-300 rounded text-sm" placeholder="ชื่อสินค้า" value="${data?.name || ''}">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">จำนวน</label>
                    <input type="number" class="item-qty w-full px-3 py-2 border border-gray-300 rounded text-sm" value="${data?.qty || 1}" min="0" step="0.01" onchange="calculateTotal()">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">หน่วย</label>
                    <input type="text" class="item-unit w-full px-3 py-2 border border-gray-300 rounded text-sm" placeholder="เช่น ต้น" value="${data?.unit || ''}">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ราคา/หน่วย</label>
                    <input type="number" class="item-price w-full px-3 py-2 border border-gray-300 rounded text-sm" value="${data?.price || 0}" min="0" step="0.01" onchange="calculateTotal()">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ส่วนลด</label>
                    <input type="number" class="item-discount w-full px-3 py-2 border border-gray-300 rounded text-sm" value="${data?.discount || 0}" min="0" step="0.01" onchange="calculateTotal()">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">รวม</label>
                    <input type="text" class="item-total w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm font-bold" readonly value="0">
                </div>
                <div class="col-span-1 flex items-end">
                    <button onclick="removeItem(${itemCount})" class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm">ลบ</button>
                </div>
            </div>
        </div>
    `;
    $('#items-container').append(html);
    calculateTotal();
}

function removeItem(id) {
    $(`.item-row[data-item="${id}"]`).remove();
    calculateTotal();
}

function previewSignature(num) {
    const input = document.getElementById(`signature${num}`);
    const preview = document.getElementById(`sig${num}_preview`);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function calculateTotal() {
    let subtotal = 0;
    let totalDiscount = 0;
    
    $('.item-row').each(function() {
        const qty = parseFloat($(this).find('.item-qty').val()) || 0;
        const price = parseFloat($(this).find('.item-price').val()) || 0;
        const discount = parseFloat($(this).find('.item-discount').val()) || 0;
        
        const total = (qty * price) - discount;
        
        $(this).find('.item-total').val(total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        subtotal += (qty * price);
        totalDiscount += discount;
    });
    
    return { subtotal, totalDiscount };
}

function saveReceipt() {
    const docNumber = $('#doc_number').val().trim();
    const docDate = $('#doc_date').val();
    const customerName = $('#customer_name').val().trim();
    
    if (!docNumber || !docDate || !customerName) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน', 'warning');
        return;
    }
    
    const items = [];
    let hasError = false;
    $('.item-row').each(function() {
        const name = $(this).find('.item-name').val().trim();
        const qty = $(this).find('.item-qty').val();
        const unit = $(this).find('.item-unit').val().trim();
        const price = $(this).find('.item-price').val();
        const discount = $(this).find('.item-discount').val();
        
        if (!name) { hasError = true; return false; }
        items.push({ name, qty, unit, price, discount });
    });
    
    if (items.length === 0 || hasError) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกรายการสินค้าให้ครบถ้วน', 'warning');
        return;
    }

    const totals = calculateTotal();
    const subtotal = totals.subtotal;
    const totalDiscount = totals.totalDiscount;
    const netSubtotal = subtotal - totalDiscount;

    const vatEnabled = $('#vat_enabled').is(':checked') ? 1 : 0;
    const vatType = $('input[name="vat_type"]:checked').val();
    
    let vatAmount = 0;
    let grandTotal = netSubtotal;
    
    if (vatEnabled) {
        if (vatType === 'exclude') {
            vatAmount = netSubtotal * 0.07;
            grandTotal = netSubtotal + vatAmount;
        } else {
            grandTotal = netSubtotal;
            vatAmount = netSubtotal - (netSubtotal / 1.07);
        }
    }

    const data = {
        action: 'save',
        id: $('#receipt_id').val(),
        doc_number: docNumber,
        doc_date: docDate,
        customer_name: customerName,
        customer_address: $('#customer_address').val(),
        customer_phone: $('#customer_phone').val(),
        customer_tax_id: $('#customer_tax_id').val(),
        items: JSON.stringify(items),
        vat_enabled: vatEnabled,
        vat_type: vatType,
        subtotal: subtotal,
        total_discount: totalDiscount,
        vat_amount: vatAmount,
        grand_total: grandTotal,
        notes: $('#notes').val(),
        issuer_company_id: $('#issuer_company_id').val(),
        header_name: $('#header_name').val(),
        header_address: $('#header_address').val(),
        header_phone: $('#header_phone').val(),
        header_tax_id: $('#header_tax_id').val(),
        header_logo: $('#header_logo_preview').attr('src') || '',
        signature1: $('#sig1_preview').attr('src') || '',
        signature2: $('#sig2_preview').attr('src') || ''
    };
    
    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    
    $.ajax({
        url: 'receipt_action.php',
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: response.message, timer: 1500, showConfirmButton: false }).then(() => {
                    if (!$('#receipt_id').val()) $('#receipt_id').val(response.id);
                });
            } else {
                Swal.fire('ผิดพลาด', response.message, 'error');
            }
        }
    });
}

function generatePreview() {
    const totals = calculateTotal();
    let subtotal = totals.subtotal;
    const totalDiscount = totals.totalDiscount;
    const netSubtotal = subtotal - totalDiscount;
    
    const vatEnabled = $('#vat_enabled').is(':checked');
    const vatType = $('input[name="vat_type"]:checked').val();
    
    let vatAmount = 0;
    let grandTotal = netSubtotal;
    
    if (vatEnabled) {
        if (vatType === 'exclude') {
            vatAmount = netSubtotal * 0.07;
            grandTotal = netSubtotal + vatAmount;
        } else {
            grandTotal = netSubtotal;
            vatAmount = netSubtotal - (netSubtotal / 1.07);
        }
    }
    
    let itemsHTML = '';
    let rowNum = 1;
    
    JSON.parse(JSON.stringify(eval($('.item-row').map(function() {
        return {
            name: $(this).find('.item-name').val(),
            qty: parseFloat($(this).find('.item-qty').val()) || 0,
            unit: $(this).find('.item-unit').val() || '',
            price: parseFloat($(this).find('.item-price').val()) || 0,
            discount: parseFloat($(this).find('.item-discount').val()) || 0
        };
    }).get()))).forEach(item => {
        const total = (item.qty * item.price) - item.discount;
        itemsHTML += `
            <tr style="border-bottom: 1px solid #000;">
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${rowNum}</td>
                <td style="padding: 8px; border-right: 1px solid #000;">${item.name || '-'}</td>
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${item.qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${item.unit}</td>
                <td style="padding: 8px; text-align: right; border-right: 1px solid #000;">${item.price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${item.discount > 0 ? item.discount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                <td style="padding: 8px; text-align: right;">${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `;
        rowNum++;
    });

    for (let i = rowNum; i <= 10; i++) {
        itemsHTML += `<tr style="border-bottom: 1px solid #000; height: 35px;"><td style="border-right: 1px solid #000;"></td><td style="border-right: 1px solid #000;"></td><td style="border-right: 1px solid #000;"></td><td style="border-right: 1px solid #000;"></td><td style="border-right: 1px solid #000;"></td><td style="border-right: 1px solid #000;"></td><td></td></tr>`;
    }
    
    const sig1 = $('#sig1_preview').attr('src') || '';
    const sig2 = $('#sig2_preview').attr('src') || '';
    const issuer = $('#issuer_company_id option:selected');
    const thaiAmount = ArabicToThaiBaht(grandTotal);

    const html = `
        <div style="width: 800px; margin: 0 auto; background: white; padding: 20px; font-family: 'Sarabun', sans-serif; color: #000;" id="printable-area">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div style="width: 150px;"><img src="${$('#header_logo_preview').attr('src')}" style="width: 120px;" onerror="this.style.display='none'"></div>
                <div style="flex: 1; text-align: center;">
                    <h1 style="font-size: 18px; font-weight: bold; margin: 0;">${$('#header_name').val()}</h1>
                    <p style="font-size: 14px; margin: 2px 0;">${$('#header_address').val()}</p>
                    <p style="font-size: 14px; margin: 2px 0;">โทร. ${$('#header_phone').val()} ไลน์ OA= @ttgoldenteak</p>
                    <p style="font-size: 14px; margin: 2px 0;">เลขที่ประจำตัวผู้เสียภาษี ${$('#header_tax_id').val()}</p>
                </div>
                <div style="width: 180px; text-align: right;">
                    <h2 style="font-size: 18px; font-weight: bold; margin: 0;">ใบเสร็จรับเงิน/ใบกำกับภาษี</h2>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px;">
                <div style="width: 60%;">
                    <p style="margin: 2px 0;"><strong>รหัส :</strong> RY-001</p>
                    <p style="margin: 2px 0;"><strong>ชื่อ :</strong> ${$('#customer_name').val() || '-'}</p>
                    <p style="margin: 2px 0;"><strong>ที่อยู่ :</strong> ${$('#customer_address').val() || '-'}</p>
                    <p style="margin: 10px 0 2px 0;"><strong>โทรศัพท์ :</strong> ${$('#customer_phone').val() || '-'} &nbsp;&nbsp;&nbsp;&nbsp; <strong>รหัสผู้เสียภาษี :</strong> ${$('#customer_tax_id').val() || '-'}</p>
                </div>
                <div style="width: 35%; text-align: right;">
                    <p style="margin: 2px 0;"><strong>เลขที่ :</strong> ${$('#doc_number').val()}</p>
                    <p style="margin: 2px 0;"><strong>วันที่ :</strong> ${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</p>
                    <p style="margin: 2px 0;"><strong>เงื่อนไขการชำระ :</strong> เงินสด/โอนเงิน</p>
                </div>
            </div>

            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 13px;">
                <thead>
                    <tr style="background-color: #92d050; border-bottom: 1px solid #000;">
                        <th style="border-right: 1px solid #000; padding: 8px; width: 50px;">ลำดับ</th>
                        <th style="border-right: 1px solid #000; padding: 8px;">รายการ</th>
                        <th style="border-right: 1px solid #000; padding: 8px; width: 70px;">จำนวน</th>
                        <th style="border-right: 1px solid #000; padding: 8px; width: 70px;">หน่วยนับ</th>
                        <th style="border-right: 1px solid #000; padding: 8px; width: 90px;">ราคา</th>
                        <th style="border-right: 1px solid #000; padding: 8px; width: 70px;">ส่วนลด</th>
                        <th style="padding: 8px; width: 110px;">รวมเป็นเงิน</th>
                    </tr>
                </thead>
                <tbody>${itemsHTML}</tbody>
            </table>

            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; border-top: none; font-size: 14px;">
                <tr>
                    <td style="width: 65%; padding: 10px; vertical-align: top; border-right: 1px solid #000;">
                        <p style="margin: 0; font-weight: bold;">หมายเหตุ :</p>
                        <p style="margin: 5px 0; white-space: pre-wrap;">${$('#notes').val()}</p>
                    </td>
                    <td style="width: 35%; padding: 0; vertical-align: top;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr style="background-color: #92d050;"><td style="padding: 5px 10px; border-bottom: 1px solid #000; border-right: 1px solid #000;">มูลค่ารวมก่อนเสียภาษี</td><td style="padding: 5px 10px; border-bottom: 1px solid #000; text-align: right;">${netSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td></tr>
                            <tr style="background-color: #92d050;"><td style="padding: 5px 10px; border-bottom: 1px solid #000; border-right: 1px solid #000;">ภาษีมูลค่าเพิ่ม(VAT)</td><td style="padding: 5px 10px; border-bottom: 1px solid #000; text-align: right;">${vatAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td></tr>
                            <tr style="background-color: #92d050;"><td style="padding: 5px 10px; border-bottom: 1px solid #000; border-right: 1px solid #000;">ส่วนลด</td><td style="padding: 5px 10px; border-bottom: 1px solid #000; text-align: right;">-</td></tr>
                            <tr style="background-color: #92d050; font-weight: bold;"><td style="padding: 5px 10px; border-right: 1px solid #000;">ยอดเงินสุทธิ</td><td style="padding: 5px 10px; text-align: right;">${grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr><td colspan="2" style="padding: 5px 10px; text-align: center; font-weight: bold; background-color: #f2f2f2;">( ${thaiAmount} )</td></tr>
            </table>

            <div style="margin-top: 10px; font-size: 13px;">
                <p style="margin: 2px 0; font-weight: bold; text-decoration: underline;">เงื่อนไข :</p>
                <p style="margin: 2px 0;">* กรุณาโทรแจ้งการชำระเงิน 088-923-5426 หรือทักไลน์ OA = @ttgoldenteak เพื่อรับเอกสารใบกำกับภาษี</p>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 20px;">
                <div style="width: 150px; text-align: center;">
                    <div style="border: 1px solid #ccc; padding: 5px;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=https://line.me/R/ti/p/@ttgoldenteak" style="width: 100px;">
                        <p style="font-size: 10px; margin: 5px 0;">Scan Line OA</p>
                    </div>
                </div>
                <div style="flex: 1; display: flex; justify-content: space-around; text-align: center;">
                    <div style="width: 200px;">
                        <p style="margin-bottom: 40px;">ผู้รับเงิน</p>
                        ${sig1 ? `<img src="${sig1}" style="height: 40px; margin-bottom: -10px; display: block; margin-left: auto; margin-right: auto;">` : '<div style="height: 40px;"></div>'}
                        <p style="margin: 0; border-bottom: 1px dotted #000; display: inline-block; min-width: 150px;">นางอัจฉริยา บุญปก กรรมการบริษัท</p>
                        <p style="margin: 5px 0;">วันที่ ${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</p>
                    </div>
                    <div style="width: 200px;">
                        <p style="margin-bottom: 40px;">ผู้อนุมัติ</p>
                        ${sig2 ? `<img src="${sig2}" style="height: 40px; margin-bottom: -10px; display: block; margin-left: auto; margin-right: auto;">` : '<div style="height: 40px;"></div>'}
                        <p style="margin: 0; border-bottom: 1px dotted #000; display: inline-block; min-width: 150px;">นางอัจฉริยา บุญปก กรรมการบริษัท</p>
                        <p style="margin: 5px 0;">วันที่ ${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</p>
                    </div>
                </div>
            </div>
            <div style="background-color: #92d050; text-align: center; padding: 5px; margin-top: 20px; font-size: 12px; font-weight: bold;">BANSAKTHONG RONGYANG CO., LTD.</div>
        </div>
    `;
    $('#receipt-preview').html(html).show();
    $('html, body').animate({ scrollTop: $('#receipt-preview').offset().top - 100 }, 500);
}

function ArabicToThaiBaht(numbers) {
    var number = parseFloat(numbers).toFixed(2);
    var bahtText = "";
    var unit = ["", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน"];
    var numberText = ["ศูนย์", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
    var splitNumber = number.split(".");
    var baht = splitNumber[0];
    var satang = splitNumber[1];
    if (baht == "0") { bahtText = "ศูนย์บาท"; } else {
        var len = baht.length;
        for (var i = 0; i < len; i++) {
            var n = baht.substr(i, 1);
            if (n != "0") {
                if (i == len - 1 && n == "1" && len > 1) bahtText += "เอ็ด";
                else if (i == len - 2 && n == "2") bahtText += "ยี่สิบ";
                else if (i == len - 2 && n == "1") bahtText += "สิบ";
                else bahtText += numberText[n] + unit[len - i - 1];
            }
        }
        bahtText += "บาท";
    }
    if (satang == "00") { bahtText += "ถ้วน"; } else {
        var len = satang.length;
        for (var i = 0; i < len; i++) {
            var n = satang.substr(i, 1);
            if (n != "0") {
                if (i == len - 1 && n == "1" && len > 1) bahtText += "เอ็ด";
                else if (i == len - 2 && n == "2") bahtText += "ยี่สิบ";
                else if (i == len - 2 && n == "1") bahtText += "สิบ";
                else bahtText += numberText[n] + unit[len - i - 1];
            }
        }
        bahtText += "สตางค์";
    }
    return bahtText;
}

function exportPDF() {
    generatePreview();
    setTimeout(() => {
        const element = document.getElementById('printable-area');
        const opt = { margin: 0, filename: `receipt_${$('#doc_number').val()}.pdf`, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2, useCORS: true }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' } };
        html2pdf().set(opt).from(element).save();
    }, 1000);
}

function printReceipt() {
    generatePreview();
    setTimeout(() => { window.print(); }, 500);
}
</script>
</body>
</html>

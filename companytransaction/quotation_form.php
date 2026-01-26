<?php
require '../auth_check.php';
require '../config.php';

$company_id = $_SESSION['company_id'];
$edit_id = $_GET['id'] ?? null;
$quotation_data = null;

// Get company info
$company_sql = "SELECT * FROM company WHERE id = ?";
$company_stmt = mysqli_prepare($conn, $company_sql);
mysqli_stmt_bind_param($company_stmt, "i", $company_id);
mysqli_stmt_execute($company_stmt);
$company_res = mysqli_stmt_get_result($company_stmt);
$company = mysqli_fetch_assoc($company_res);

// Load quotation data if editing
if ($edit_id) {
    $sql = "SELECT * FROM quotations WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $edit_id, $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $quotation_data = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบเสนอราคา - RONGYANG HOME</title>
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

<div class="no-print bg-gradient-to-r from-emerald-600 to-emerald-700 text-white p-4 shadow-lg">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">📄 <?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบเสนอราคา</h1>
        <a href="quotation.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">← กลับ</a>
    </div>
</div>

<div class="max-w-5xl mx-auto p-6">
    <!-- Form Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 no-print">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">ข้อมูลใบเสนอราคา</h2>
        
        <input type="hidden" id="quotation_id" value="<?= $edit_id ?? '' ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลขที่เอกสาร</label>
                <input type="text" id="doc_number" value="<?= $quotation_data['doc_number'] ?? 'QT-'.date('Ymd').'-001' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่</label>
                <input type="date" id="doc_date" value="<?= $quotation_data['doc_date'] ?? date('Y-m-d') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ / ผู้ซื้อ</label>
                <input type="text" id="customer_name" value="<?= htmlspecialchars($quotation_data['customer_name'] ?? '') ?>" placeholder="กรอกชื่อลูกค้า" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ที่อยู่</label>
                <input type="text" id="customer_address" value="<?= htmlspecialchars($quotation_data['customer_address'] ?? '') ?>" placeholder="กรอกที่อยู่" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">โทรศัพท์</label>
                <input type="text" id="customer_phone" value="<?= htmlspecialchars($quotation_data['customer_phone'] ?? '') ?>" placeholder="เบอร์โทรศัพท์" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลขประจำตัวผู้เสียภาษี</label>
                <input type="text" id="customer_tax_id" value="<?= htmlspecialchars($quotation_data['customer_tax_id'] ?? '') ?>" placeholder="เลขประจำตัวผู้เสียภาษี" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>

        <div class="border-t pt-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">รายการสินค้า</h3>
                <button onclick="addItem()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2">
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
                <input type="checkbox" id="vat_enabled" <?= ($quotation_data['vat_enabled'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 text-emerald-600 rounded">
                <label for="vat_enabled" class="text-sm font-medium text-gray-700">คิด VAT 7%</label>
            </div>

            <div class="flex items-center gap-4 mb-4">
                <input type="radio" name="vat_type" value="exclude" id="vat_exclude" <?= ($quotation_data['vat_type'] ?? 'exclude') == 'exclude' ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600">
                <label for="vat_exclude" class="text-sm text-gray-700">ราคายังไม่รวม VAT</label>
                
                <input type="radio" name="vat_type" value="include" id="vat_include" <?= ($quotation_data['vat_type'] ?? 'exclude') == 'include' ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600">
                <label for="vat_include" class="text-sm text-gray-700">ราคารวม VAT แล้ว</label>
            </div>
        </div>

        <div class="border-t pt-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">หมายเหตุ</h3>
            <textarea id="notes" rows="4" placeholder="บันทึกหมายเหตุเพิ่มเติม (ถ้ามี)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none"><?= htmlspecialchars($quotation_data['notes'] ?? '') ?></textarea>
        </div>

        <div class="border-t pt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">ลายเซ็น</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้เสนอราคา</label>
                    <input type="file" id="signature1" accept="image/*" onchange="previewSignature(1)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    <img id="sig1_preview" src="<?= $quotation_data['signature1'] ?? '' ?>" class="signature-preview mt-2 <?= empty($quotation_data['signature1']) ? 'hidden' : '' ?> border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้อนุมัติ</label>
                    <input type="file" id="signature2" accept="image/*" onchange="previewSignature(2)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    <img id="sig2_preview" src="<?= $quotation_data['signature2'] ?? '' ?>" class="signature-preview mt-2 <?= empty($quotation_data['signature2']) ? 'hidden' : '' ?> border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้รับ</label>
                    <input type="file" id="signature3" accept="image/*" onchange="previewSignature(3)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    <img id="sig3_preview" src="<?= $quotation_data['signature3'] ?? '' ?>" class="signature-preview mt-2 <?= empty($quotation_data['signature3']) ? 'hidden' : '' ?> border rounded">
                </div>
            </div>
        </div>

        <div class="flex gap-4 mt-8">
            <button onclick="saveQuotation()" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                💾 บันทึก
            </button>
            <button onclick="generatePreview()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                👁️ ดูตัวอย่าง
            </button>
            <button onclick="exportPDF()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                📄 Export PDF
            </button>
            <button onclick="printQuotation()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                🖨️ Print A4
            </button>
        </div>
    </div>

    <!-- Preview Section -->
    <div id="quotation-preview" class="bg-white rounded-2xl shadow-lg p-8" style="display: none;">
        <!-- Content will be generated here -->
    </div>
</div>

<script>
let itemCount = 0;
const existingItems = <?= json_encode($quotation_data['items'] ?? '[]') ?>;

$(document).ready(function() {
    if (existingItems && existingItems.length > 0) {
        JSON.parse(existingItems).forEach(item => {
            addItem(item);
        });
    } else {
        addItem();
    }
});

function addItem(data = null) {
    itemCount++;
    const html = `
        <div class="item-row border border-gray-200 rounded-lg p-4 bg-gray-50" data-item="${itemCount}">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">รายการ</label>
                    <input type="text" class="item-name w-full px-3 py-2 border border-gray-300 rounded text-sm" placeholder="ชื่อสินค้า" value="${data?.name || ''}">
                </div>
                <div class="col-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">รูปสินค้า (ถ้ามี)</label>
                    <input type="file" accept="image/*" class="item-image w-full text-xs" onchange="previewItemImage(this, ${itemCount})">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">จำนวน</label>
                    <input type="number" class="item-qty w-full px-3 py-2 border border-gray-300 rounded text-sm" value="${data?.qty || 1}" min="1" onchange="calculateTotal()">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ราคา/หน่วย</label>
                    <input type="number" class="item-price w-full px-3 py-2 border border-gray-300 rounded text-sm" value="${data?.price || 0}" min="0" onchange="calculateTotal()">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">รวม</label>
                    <input type="text" class="item-total w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm font-bold" readonly value="0">
                </div>
                <div class="col-span-1 flex items-end">
                    <button onclick="removeItem(${itemCount})" class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm">ลบ</button>
                </div>
            </div>
            <img id="item_img_${itemCount}" src="${data?.image || ''}" class="mt-2 ${data?.image ? '' : 'hidden'} max-w-xs max-h-32 object-contain border rounded">
        </div>
    `;
    $('#items-container').append(html);
    calculateTotal();
}

function removeItem(id) {
    $(`.item-row[data-item="${id}"]`).remove();
    calculateTotal();
}

function previewItemImage(input, id) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $(`#item_img_${id}`).attr('src', e.target.result).removeClass('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
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
    
    $('.item-row').each(function() {
        const qty = parseFloat($(this).find('.item-qty').val()) || 0;
        const price = parseFloat($(this).find('.item-price').val()) || 0;
        const total = qty * price;
        
        $(this).find('.item-total').val(total.toLocaleString());
        subtotal += total;
    });
    
    return subtotal;
}

function saveQuotation() {
    // Validation
    const docNumber = $('#doc_number').val().trim();
    const docDate = $('#doc_date').val();
    const customerName = $('#customer_name').val().trim();
    
    if (!docNumber) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกเลขที่เอกสาร', 'warning');
        return;
    }
    
    if (!docDate) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเลือกวันที่', 'warning');
        return;
    }
    
    if (!customerName) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกชื่อลูกค้า', 'warning');
        return;
    }
    
    // Validate items
    const items = [];
    let hasError = false;
    
    $('.item-row').each(function() {
        const name = $(this).find('.item-name').val().trim();
        const qty = $(this).find('.item-qty').val();
        const price = $(this).find('.item-price').val();
        const image = $(this).find('img').attr('src') || '';
        
        if (!name) {
            hasError = true;
            return false; // break loop
        }
        
        items.push({ name, qty, price, image });
    });
    
    if (items.length === 0) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเพิ่มรายการสินค้าอย่างน้อย 1 รายการ', 'warning');
        return;
    }
    
    if (hasError) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกชื่อสินค้าให้ครบทุกรายการ', 'warning');
        return;
    }
    
    const subtotal = calculateTotal();
    const vatEnabled = $('#vat_enabled').is(':checked') ? 1 : 0;
    const vatType = $('input[name="vat_type"]:checked').val();
    
    let vatAmount = 0;
    let grandTotal = subtotal;
    
    if (vatEnabled) {
        if (vatType === 'exclude') {
            vatAmount = subtotal * 0.07;
            grandTotal = subtotal + vatAmount;
        } else {
            grandTotal = subtotal;
            vatAmount = subtotal - (subtotal / 1.07);
        }
    }
    
    const data = {
        action: 'save',
        id: $('#quotation_id').val(),
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
        vat_amount: vatAmount,
        grand_total: grandTotal,
        notes: $('#notes').val(),
        signature1: $('#sig1_preview').attr('src') || '',
        signature2: $('#sig2_preview').attr('src') || '',
        signature3: $('#sig3_preview').attr('src') || ''
    };
    
    // Show loading
    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: 'quotation_action.php',
        type: 'POST',
        data: data,
        success: function(response) {
            try {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        if (!$('#quotation_id').val()) {
                            $('#quotation_id').val(res.id);
                        }
                    });
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            } catch (e) {
                Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' + e.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้: ' + error, 'error');
        }
    });
}

function generatePreview() {
    const subtotal = calculateTotal();
    const vatEnabled = $('#vat_enabled').is(':checked');
    const vatType = $('input[name="vat_type"]:checked').val();
    
    let vatAmount = 0;
    let grandTotal = subtotal;
    
    if (vatEnabled) {
        if (vatType === 'exclude') {
            vatAmount = subtotal * 0.07;
            grandTotal = subtotal + vatAmount;
        } else {
            grandTotal = subtotal;
            vatAmount = subtotal - (subtotal / 1.07);
            subtotal = subtotal / 1.07;
        }
    }
    
    let itemsHTML = '';
    let rowNum = 1;
    
    $('.item-row').each(function() {
        const name = $(this).find('.item-name').val();
        const qty = $(this).find('.item-qty').val();
        const price = parseFloat($(this).find('.item-price').val()) || 0;
        const total = $(this).find('.item-total').val();
        const imgSrc = $(this).find('img').attr('src');
        
        itemsHTML += `
            <tr class="border-b">
                <td class="px-4 py-3 text-center">${rowNum}</td>
                <td class="px-4 py-3">${name || '-'}</td>
                <td class="px-4 py-3 text-center">
                    ${imgSrc && imgSrc !== '' ? `<img src="${imgSrc}" class="max-w-24 max-h-24 mx-auto object-contain">` : '-'}
                </td>
                <td class="px-4 py-3 text-center">${qty}</td>
                <td class="px-4 py-3 text-right">${price.toLocaleString()}</td>
                <td class="px-4 py-3 text-right font-bold">${total}</td>
            </tr>
        `;
        rowNum++;
    });
    
    const sig1 = $('#sig1_preview').attr('src') || '';
    const sig2 = $('#sig2_preview').attr('src') || '';
    const sig3 = $('#sig3_preview').attr('src') || '';
    
    const html = `
        <div class="max-w-4xl mx-auto bg-white p-8" id="printable-area">
            <!-- Header -->
            <div class="flex justify-between items-start mb-6 border-b pb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($company['company_name'] ?? 'บริษัท') ?></h1>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($company['address'] ?? '') ?></p>
                    <p class="text-sm text-gray-600">โทร: <?= htmlspecialchars($company['phone'] ?? '') ?></p>
                    <p class="text-sm text-gray-600">เลขประจำตัวผู้เสียภาษี: <?= htmlspecialchars($company['tax_id'] ?? '') ?></p>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold text-emerald-600">ใบเสนอราคา (QUOTATION)</h2>
                    <p class="text-sm text-gray-600 mt-2">เลขที่เอกสาร: <span class="font-bold">${$('#doc_number').val()}</span></p>
                    <p class="text-sm text-gray-600">วันที่: <span class="font-bold">${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</span></p>
                </div>
            </div>
            
            <!-- Customer Info -->
            <div class="mb-6">
                <p class="text-sm"><span class="font-bold">ชื่อ / ผู้ซื้อ:</span> ${$('#customer_name').val() || '-'}</p>
                <p class="text-sm"><span class="font-bold">ที่อยู่:</span> ${$('#customer_address').val() || '-'}</p>
                <p class="text-sm"><span class="font-bold">โทรศัพท์:</span> ${$('#customer_phone').val() || '-'}</p>
                <p class="text-sm"><span class="font-bold">เลขประจำตัวผู้เสียภาษี:</span> ${$('#customer_tax_id').val() || '-'}</p>
            </div>
            
            <!-- Items Table -->
            <table class="w-full border-collapse border mb-6">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2 text-sm">ลำดับ</th>
                        <th class="border px-4 py-2 text-sm">รายการ</th>
                        <th class="border px-4 py-2 text-sm">รูปสินค้า</th>
                        <th class="border px-4 py-2 text-sm">จำนวน</th>
                        <th class="border px-4 py-2 text-sm">ราคา/หน่วย</th>
                        <th class="border px-4 py-2 text-sm">รวม</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHTML}
                </tbody>
            </table>
            
            <!-- Summary -->
            <div class="flex justify-end mb-6">
                <div class="w-64">
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-sm">รวมก่อน VAT:</span>
                        <span class="font-bold">${subtotal.toLocaleString()} บาท</span>
                    </div>
                    ${vatEnabled ? `
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-sm">VAT 7%:</span>
                        <span class="font-bold">${vatAmount.toLocaleString()} บาท</span>
                    </div>
                    ` : ''}
                    <div class="flex justify-between py-3 bg-emerald-50 px-3 rounded mt-2">
                        <span class="text-lg font-bold text-emerald-700">รวมทั้งสิ้น:</span>
                        <span class="text-xl font-bold text-emerald-700">${grandTotal.toLocaleString()} บาท</span>
                    </div>
                </div>
            </div>
            
            <!-- Notes -->
            ${$('#notes').val().trim() ? `
            <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-sm font-bold text-gray-700 mb-2">หมายเหตุ:</p>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">${$('#notes').val()}</p>
            </div>
            ` : ''}
            
            <!-- Signatures -->
            <div class="grid grid-cols-3 gap-8 mt-12">
                <div class="text-center">
                    ${sig1 && sig1 !== '' ? `<img src="${sig1}" class="signature-preview mx-auto mb-2">` : '<div class="h-20"></div>'}
                    <div class="border-t border-gray-400 pt-2">
                        <p class="text-sm font-bold">ผู้เสนอราคา</p>
                        <p class="text-xs text-gray-600">ลายเซ็น</p>
                    </div>
                </div>
                <div class="text-center">
                    ${sig2 && sig2 !== '' ? `<img src="${sig2}" class="signature-preview mx-auto mb-2">` : '<div class="h-20"></div>'}
                    <div class="border-t border-gray-400 pt-2">
                        <p class="text-sm font-bold">ผู้อนุมัติ</p>
                        <p class="text-xs text-gray-600">ลายเซ็น</p>
                    </div>
                </div>
                <div class="text-center">
                    ${sig3 && sig3 !== '' ? `<img src="${sig3}" class="signature-preview mx-auto mb-2">` : '<div class="h-20"></div>'}
                    <div class="border-t border-gray-400 pt-2">
                        <p class="text-sm font-bold">ผู้รับ</p>
                        <p class="text-xs text-gray-600">ลายเซ็น</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#quotation-preview').html(html).show();
    $('html, body').animate({ scrollTop: $('#quotation-preview').offset().top - 100 }, 500);
}

function exportPDF() {
    generatePreview();
    
    setTimeout(() => {
        const element = document.getElementById('printable-area');
        const opt = {
            margin: 10,
            filename: `quotation_${$('#doc_number').val()}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        html2pdf().set(opt).from(element).save();
    }, 500);
}

function printQuotation() {
    generatePreview();
    
    setTimeout(() => {
        window.print();
    }, 500);
}
</script>

</body>
</html>

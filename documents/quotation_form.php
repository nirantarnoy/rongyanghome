<?php
require '../auth_check.php';
require '../config.php';

$company_id = $_SESSION['company_id'];
$edit_id = $_GET['id'] ?? null;
$quotation_data = null;

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
        <a href="index.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">← กลับ</a>
    </div>
</div>

<div class="max-w-5xl mx-auto p-6">
    <!-- Form Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 no-print">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">ข้อมูลใบเสนอราคา</h2>
        
        <input type="hidden" id="quotation_id" value="<?= $edit_id ?? '' ?>">
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">ออกในนามบริษัท</label>
            <select id="issuer_company_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" onchange="updateIssuerInfo()">
                <?php foreach ($all_companies as $c): ?>
                    <option value="<?= $c['id'] ?>" 
                        data-name="<?= htmlspecialchars($c['company_name']) ?>" 
                        data-address="<?= htmlspecialchars($c['address']) ?>" 
                        data-phone="<?= htmlspecialchars($c['phone']) ?>" 
                        data-taxid="<?= htmlspecialchars($c['tax_id']) ?>"
                        <?= ($quotation_data['issuer_company_id'] ?? $company_id) == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['company_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ระยะเวลาที่ยื่น</label>
                <input type="text" id="delivery_time" value="<?= htmlspecialchars($quotation_data['delivery_time'] ?? '') ?>" placeholder="เช่น 5-7 วัน" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">QR Code สำหรับชำระเงิน</label>
                <input type="file" id="qr_code" accept="image/*" onchange="previewQRCode()" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <img id="qr_preview" src="<?= $quotation_data['qr_code_image'] ?? '' ?>" class="mt-2 <?= empty($quotation_data['qr_code_image']) ? 'hidden' : '' ?> max-w-xs max-h-32 object-contain border rounded">
            </div>
        </div>

        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm font-medium text-gray-700">เงื่อนไขการชำระเงิน</label>
                <button onclick="manageTemplates('payment_terms')" type="button" class="text-xs bg-indigo-100 text-indigo-700 px-3 py-1 rounded-lg hover:bg-indigo-200 transition-all">
                    ⚙️ จัดการเทมเพลต
                </button>
            </div>
            <div class="flex gap-2 mb-2">
                <select id="payment_terms_template" onchange="loadPaymentTermsTemplate()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- เลือกเทมเพลต --</option>
                </select>
            </div>
            <textarea id="payment_terms" rows="3" placeholder="ระบุเงื่อนไขการชำระเงิน" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none"><?= htmlspecialchars($quotation_data['payment_terms'] ?? '') ?></textarea>
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
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-lg font-bold text-gray-800">หมายเหตุ</h3>
                <button onclick="manageTemplates('notes')" type="button" class="text-xs bg-indigo-100 text-indigo-700 px-3 py-1 rounded-lg hover:bg-indigo-200 transition-all">
                    ⚙️ จัดการเทมเพลต
                </button>
            </div>
            <div class="flex gap-2 mb-2">
                <select id="notes_template" onchange="loadNotesTemplate()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">-- เลือกเทมเพลต --</option>
                </select>
            </div>
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
const allCompanies = <?= json_encode($all_companies) ?>;

$(document).ready(function() {
    if (existingItems && existingItems.length > 0) {
        JSON.parse(existingItems).forEach(item => {
            addItem(item);
        });
    } else {
        addItem();
    }
    
    // Load templates
    loadTemplates();
    updateIssuerInfo();
});

function updateIssuerInfo() {
    // This function can be used to update any UI elements if needed
    // For now, generatePreview will pull from the selected option
}

// Load templates from server
function loadTemplates() {
    $.ajax({
        url: 'template_action.php',
        type: 'GET',
        data: { action: 'get_templates' },
        success: function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                // Populate payment terms templates
                $('#payment_terms_template').html('<option value="">-- เลือกเทมเพลต --</option>');
                res.data.filter(t => t.template_type === 'payment_terms').forEach(t => {
                    $('#payment_terms_template').append(`<option value="${t.id}" data-content="${t.template_content}">${t.template_name}</option>`);
                });
                
                // Populate notes templates
                $('#notes_template').html('<option value="">-- เลือกเทมเพลต --</option>');
                res.data.filter(t => t.template_type === 'notes').forEach(t => {
                    $('#notes_template').append(`<option value="${t.id}" data-content="${t.template_content}">${t.template_name}</option>`);
                });
            }
        }
    });
}

// Load payment terms template
function loadPaymentTermsTemplate() {
    const selected = $('#payment_terms_template option:selected');
    const content = selected.data('content');
    if (content) {
        const currentContent = $('#payment_terms').val().trim();
        if (currentContent) {
            $('#payment_terms').val(currentContent + '\n' + content);
        } else {
            $('#payment_terms').val(content);
        }
    }
}

// Load notes template
function loadNotesTemplate() {
    const selected = $('#notes_template option:selected');
    const content = selected.data('content');
    if (content) {
        const currentContent = $('#notes').val().trim();
        if (currentContent) {
            $('#notes').val(currentContent + '\n' + content);
        } else {
            $('#notes').val(content);
        }
    }
}

// Preview QR Code
function previewQRCode() {
    const input = document.getElementById('qr_code');
    const preview = document.getElementById('qr_preview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Manage templates
function manageTemplates(type) {
    const typeLabel = type === 'payment_terms' ? 'เงื่อนไขการชำระเงิน' : 'หมายเหตุ';
    
    Swal.fire({
        title: `จัดการเทมเพลต${typeLabel}`,
        html: `
            <div class="text-left">
                <div class="mb-4">
                    <button onclick="addNewTemplate('${type}')" class="w-full bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
                        + เพิ่มเทมเพลตใหม่
                    </button>
                </div>
                <div id="template-list-${type}" class="space-y-2 max-h-96 overflow-y-auto">
                    <!-- Templates will be loaded here -->
                </div>
            </div>
        `,
        width: '600px',
        showConfirmButton: false,
        showCloseButton: true,
        didOpen: () => {
            loadTemplateList(type);
        }
    });
}

// Load template list for management
function loadTemplateList(type) {
    $.ajax({
        url: 'template_action.php',
        type: 'GET',
        data: { action: 'get_templates', type: type },
        success: function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                let html = '';
                if (res.data.length === 0) {
                    html = '<p class="text-gray-400 text-center py-4">ยังไม่มีเทมเพลต</p>';
                } else {
                    res.data.forEach(t => {
                        html += `
                            <div class="border rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="font-bold text-gray-800">${t.template_name}</div>
                                        <div class="text-sm text-gray-600 mt-1">${t.template_content}</div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick="editTemplate(${t.id}, '${type}', '${t.template_name.replace(/'/g, "\\'")}', '${t.template_content.replace(/'/g, "\\'").replace(/\n/g, '\\n')}')" class="text-indigo-600 hover:text-indigo-800 text-sm">แก้ไข</button>
                                        <button onclick="deleteTemplate(${t.id}, '${type}')" class="text-red-600 hover:text-red-800 text-sm">ลบ</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                $(`#template-list-${type}`).html(html);
            }
        }
    });
}

// Add new template
function addNewTemplate(type) {
    const typeLabel = type === 'payment_terms' ? 'เงื่อนไขการชำระเงิน' : 'หมายเหตุ';
    
    Swal.fire({
        title: `เพิ่มเทมเพลต${typeLabel}`,
        html: `
            <div class="text-left space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อเทมเพลต</label>
                    <input id="template_name" class="swal2-input !m-0 !w-full" placeholder="ระบุชื่อเทมเพลต">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">เนื้อหา</label>
                    <textarea id="template_content" class="swal2-textarea !m-0 !w-full !h-32" placeholder="ระบุเนื้อหา"></textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
            const name = $('#template_name').val().trim();
            const content = $('#template_content').val().trim();
            
            if (!name || !content) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }
            
            return { name, content };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'template_action.php',
                type: 'POST',
                data: {
                    action: 'save_template',
                    template_type: type,
                    template_name: result.value.name,
                    template_content: result.value.content
                },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire('สำเร็จ', res.message, 'success').then(() => {
                            loadTemplates();
                            manageTemplates(type);
                        });
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

// Edit template
function editTemplate(id, type, name, content) {
    const typeLabel = type === 'payment_terms' ? 'เงื่อนไขการชำระเงิน' : 'หมายเหตุ';
    
    Swal.fire({
        title: `แก้ไขเทมเพลต${typeLabel}`,
        html: `
            <div class="text-left space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">ชื่อเทมเพลต</label>
                    <input id="template_name" class="swal2-input !m-0 !w-full" value="${name}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">เนื้อหา</label>
                    <textarea id="template_content" class="swal2-textarea !m-0 !w-full !h-32">${content}</textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
            const name = $('#template_name').val().trim();
            const content = $('#template_content').val().trim();
            
            if (!name || !content) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }
            
            return { name, content };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'template_action.php',
                type: 'POST',
                data: {
                    action: 'save_template',
                    id: id,
                    template_type: type,
                    template_name: result.value.name,
                    template_content: result.value.content
                },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire('สำเร็จ', res.message, 'success').then(() => {
                            loadTemplates();
                            manageTemplates(type);
                        });
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

// Delete template
function deleteTemplate(id, type) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบเทมเพลตนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'template_action.php',
                type: 'POST',
                data: { action: 'delete_template', id: id },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        loadTemplates();
                        loadTemplateList(type);
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
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
            <div class="mt-2 flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">รูปสินค้า (ถ้ามี)</label>
                    <input type="file" accept="image/*" class="item-image w-full text-xs" onchange="previewItemImage(this, ${itemCount})">
                </div>
                <img id="item_img_${itemCount}" src="${data?.image || ''}" class="${data?.image ? '' : 'hidden'} max-w-xs max-h-20 object-contain border rounded">
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
        const unit = $(this).find('.item-unit').val().trim();
        const price = $(this).find('.item-price').val();
        const discount = $(this).find('.item-discount').val();
        const image = $(this).find('img').attr('src') || '';
        
        if (!name) {
            hasError = true;
            return false; // break loop
        }
        
        items.push({ name, qty, unit, price, discount, image });
    });
    
    if (items.length === 0) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเพิ่มรายการสินค้าอย่างน้อย 1 รายการ', 'warning');
        return;
    }
    
    if (hasError) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกชื่อสินค้าให้ครบทุกรายการ', 'warning');
        return;
    }

    const totals = calculateTotal();
    const subtotal = totals.subtotal;
    const totalDiscount = totals.totalDiscount;
    const netSubtotal = subtotal - totalDiscount;

    const vatEnabled = $('#vat_enabled').is(':checked') ? 1 : 0;
    const vatType = $('input[name="vat_type"]:checked').val();
    
    let vatAmount = 0;
    let grandTotal = subtotal;
    
    if (vatEnabled) {
        if (vatType === 'exclude') {
            vatAmount = netSubtotal * 0.07;
            grandTotal = netSubtotal + vatAmount;
        } else {
            grandTotal = netSubtotal;
            vatAmount = netSubtotal - (netSubtotal / 1.07);
        }
    } else {
        grandTotal = netSubtotal;
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
        delivery_time: $('#delivery_time').val(),
        payment_terms: $('#payment_terms').val(),
        items: JSON.stringify(items),
        vat_enabled: vatEnabled,
        vat_type: vatType,
        subtotal: subtotal,
        total_discount: totalDiscount,
        vat_amount: vatAmount,
        grand_total: grandTotal,
        notes: $('#notes').val(),
        issuer_company_id: $('#issuer_company_id').val(),
        signature1: $('#sig1_preview').attr('src') || '',
        signature2: $('#sig2_preview').attr('src') || '',
        signature3: $('#sig3_preview').attr('src') || '',
        qr_code_image: $('#qr_preview').attr('src') || ''
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
                let res;
                if (typeof response === 'object') {
                    res = response;
                } else {
                    res = JSON.parse(response);
                }

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
                    Swal.fire('ผิดพลาด', res.message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ', 'error');
                }
            } catch (e) {
                console.error('Server Response Error:', e);
                console.log('Raw Response:', response);
                
                let displayResponse = typeof response === 'string' ? response : JSON.stringify(response);
                
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาดในการบันทึก',
                    html: `<div class="text-left">
                            <p class="text-sm text-red-600 font-bold mb-2">สาเหตุ: ${e.message}</p>
                            <p class="text-xs text-gray-500 mb-1">ข้อมูลที่ได้รับจากเซิร์ฟเวอร์:</p>
                            <div class="text-xs bg-gray-100 p-2 rounded overflow-auto max-h-40 border">
                                ${displayResponse.substring(0, 1000)}
                            </div>
                           </div>`,
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้: ' + error, 'error');
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
            subtotal = subtotal / 1.07; // This is a bit tricky with discounts, but let's keep it simple
        }
    }
    
    let itemsHTML = '';
    let rowNum = 1;
    
    $('.item-row').each(function() {
        const name = $(this).find('.item-name').val();
        const qty = parseFloat($(this).find('.item-qty').val()) || 0;
        const unit = $(this).find('.item-unit').val() || '';
        const price = parseFloat($(this).find('.item-price').val()) || 0;
        const discount = parseFloat($(this).find('.item-discount').val()) || 0;
        const total = (qty * price) - discount;
        
        itemsHTML += `
            <tr style="border-bottom: 1px solid #000;">
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${rowNum}</td>
                <td style="padding: 8px; border-right: 1px solid #000;">${name || '-'}</td>
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${unit}</td>
                <td style="padding: 8px; text-align: right; border-right: 1px solid #000;">${price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${discount > 0 ? discount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                <td style="padding: 8px; text-align: right;">${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `;
        rowNum++;
    });

    // Fill empty rows to maintain height
    for (let i = rowNum; i <= 10; i++) {
        itemsHTML += `
            <tr style="border-bottom: 1px solid #000; height: 35px;">
                <td style="border-right: 1px solid #000;"></td>
                <td style="border-right: 1px solid #000;"></td>
                <td style="border-right: 1px solid #000;"></td>
                <td style="border-right: 1px solid #000;"></td>
                <td style="border-right: 1px solid #000;"></td>
                <td style="border-right: 1px solid #000;"></td>
                <td></td>
            </tr>
        `;
    }
    
    const sig1 = $('#sig1_preview').attr('src') || '';
    const sig2 = $('#sig2_preview').attr('src') || '';
    
    const issuer = $('#issuer_company_id option:selected');
    const issuerName = issuer.data('name');
    const issuerAddress = issuer.data('address');
    const issuerPhone = issuer.data('phone');
    const issuerTaxId = issuer.data('taxid');

    const thaiAmount = ArabicToThaiBaht(grandTotal);

    const html = `
        <div style="width: 800px; margin: 0 auto; background: white; padding: 20px; font-family: 'Sarabun', sans-serif; color: #000; position: relative;" id="printable-area">
            <!-- Header Section -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div style="width: 150px;">
                    <img src="../assets/logo/logo.png" style="width: 120px; height: auto;">
                </div>
                <div style="flex: 1; text-align: center; padding: 0 10px;">
                    <h1 style="font-size: 18px; font-weight: bold; margin: 0;">${issuerName}</h1>
                    <p style="font-size: 14px; margin: 2px 0;">${issuerAddress}</p>
                    <p style="font-size: 14px; margin: 2px 0;">โทร. ${issuerPhone} ไลน์ OA= @ttgoldenteak</p>
                    <p style="font-size: 14px; margin: 2px 0;">เลขที่ประจำตัวผู้เสียภาษี ${issuerTaxId}</p>
                </div>
                <div style="width: 150px; text-align: right;">
                    <h2 style="font-size: 20px; font-weight: bold; margin: 0;">ใบเสนอราคา</h2>
                </div>
            </div>

            <!-- Info Section -->
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px;">
                <div style="width: 60%;">
                    <p style="margin: 2px 0;"><strong>รหัส :</strong> RY-001</p>
                    <p style="margin: 2px 0;"><strong>ชื่อ :</strong> ${$('#customer_name').val() || '-'}</p>
                    <p style="margin: 2px 0;"><strong>ที่อยู่ :</strong> ${$('#customer_address').val() || '-'}</p>
                    <p style="margin: 10px 0 2px 0;"><strong>โทรศัพท์ :</strong> ${$('#customer_phone').val() || '-'} &nbsp;&nbsp;&nbsp;&nbsp; <strong>อีเมลล์ :</strong> - &nbsp;&nbsp;&nbsp;&nbsp; <strong>รหัสผู้เสียภาษี :</strong> ${$('#customer_tax_id').val() || '-'}</p>
                </div>
                <div style="width: 35%; text-align: right;">
                    <p style="margin: 2px 0;"><strong>เลขที่ :</strong> ${$('#doc_number').val()}</p>
                    <p style="margin: 2px 0;"><strong>วันที่ยื่น :</strong> ${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</p>
                    <p style="margin: 2px 0;"><strong>ระยะเวลา :</strong> ${$('#delivery_time').val() || '-'}</p>
                    <p style="margin: 2px 0;"><strong>เงื่อนไขการชำระ :</strong> เงินสด/โอนเงิน</p>
                </div>
            </div>

            <!-- Items Table -->
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 13px; margin-bottom: 0;">
                <thead>
                    <tr style="background-color: #92d050; color: #000; border-bottom: 1px solid #000;">
                        <th style="border-right: 1px solid #000; padding: 8px; width: 50px;">ลำดับ</th>
                        <th style="border-right: 1px solid #000; padding: 8px;">รายการ</th>
                        <th style="border-right: 1px solid #000; padding: 8px; width: 70px;">จำนวน</th>
                        <th style="border-right: 1px solid #000; padding: 8px; width: 70px;">หน่วยนับ</th>
                        <th style="border-right: 1px solid #000; padding: 8px; width: 90px;">ราคา</th>
                        <th style="border-right: 1px solid #000; padding: 8px; width: 70px;">ส่วนลด</th>
                        <th style="padding: 8px; width: 110px;">รวมเป็นเงิน</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHTML}
                </tbody>
            </table>

            <!-- Footer Summary -->
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; border-top: none; font-size: 14px;">
                <tr>
                    <td style="width: 65%; padding: 10px; vertical-align: top; border-right: 1px solid #000;">
                        <p style="margin: 0; font-weight: bold;">หมายเหตุ :</p>
                        <div style="text-align: center; color: red; font-weight: bold; margin-top: 10px;">
                            <p style="margin: 5px 0;">ชำระมัดจำ 50 % ก่อนผลิต = ${(grandTotal / 2).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท</p>
                            <p style="margin: 5px 0;">ชำระเมื่อได้รับสินค้าอีก 50 % = ${(grandTotal / 2).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท</p>
                        </div>
                    </td>
                    <td style="width: 35%; padding: 0; vertical-align: top;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr style="background-color: #92d050;">
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; border-right: 1px solid #000;">มูลค่ารวมก่อนเสียภาษี</td>
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; text-align: right;">${netSubtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                            <tr style="background-color: #92d050;">
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; border-right: 1px solid #000;">ภาษีมูลค่าเพิ่ม(VAT)</td>
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; text-align: right;">${vatAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                            <tr style="background-color: #92d050;">
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; border-right: 1px solid #000;">ส่วนลด</td>
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; text-align: right;">-</td>
                            </tr>
                            <tr style="background-color: #92d050; font-weight: bold;">
                                <td style="padding: 5px 10px; border-right: 1px solid #000;">ยอดเงินสุทธิ</td>
                                <td style="padding: 5px 10px; text-align: right;">${grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding: 5px 10px; text-align: center; font-weight: bold; background-color: #f2f2f2;">
                        ( ${thaiAmount} )
                    </td>
                </tr>
            </table>

            <!-- Conditions & Bank Info -->
            <div style="margin-top: 10px; font-size: 13px;">
                <p style="margin: 2px 0; font-weight: bold; text-decoration: underline;">เงื่อนไข :</p>
                <p style="margin: 2px 0;">* โอนชำระมัดจำสินค้าก่อนผลิต 50% และเมื่อได้รับสินค้าโอนชำระอีก 50% กับเจ้าหน้าที่ขนส่ง</p>
                <p style="margin: 2px 0;">* กรุณาชำระเงินโดยการโอนเข้าบัญชี ธ.กสิกรไทย สาขาแพร่ ออมทรัพย์ ชื่อบัญชี บจก.บ้านสักทองร้องแหย่ง เลขที่ 1971292055</p>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 20px;">
                <div style="width: 180px;">
                    <img src="../assets/logo/bank_acc.png" style="width: 100%; border: 1px solid #eee;" onerror="this.style.display='none'">
                    <!-- Placeholder if image not found -->
                    <div style="border: 1px solid #ccc; padding: 10px; text-align: center; font-size: 10px;" id="bank_placeholder">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=1971292055" style="width: 80px;">
                        <p style="margin: 5px 0;">ธ.กสิกรไทย<br>1971-292-055</p>
                    </div>
                </div>
                <div style="flex: 1; display: flex; justify-content: space-around; text-align: center;">
                    <div style="width: 200px;">
                        <p style="margin-bottom: 40px;">ผู้เสนอราคา</p>
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

            <!-- Bottom Green Bar -->
            <div style="background-color: #92d050; color: #000; text-align: center; padding: 5px; margin-top: 20px; font-size: 12px; font-weight: bold;">
                BANSAKTHONG RONGYANG CO., LTD.
            </div>
        </div>
    `;
    
    $('#quotation-preview').html(html).show();
    $('html, body').animate({ scrollTop: $('#quotation-preview').offset().top - 100 }, 500);
}

// Thai Baht Text Function
function ArabicToThaiBaht(numbers) {
    var number = parseFloat(numbers).toFixed(2);
    var bahtText = "";
    var unit = ["", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน"];
    var numberText = ["ศูนย์", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
    
    var splitNumber = number.split(".");
    var baht = splitNumber[0];
    var satang = splitNumber[1];
    
    if (baht == "0") {
        bahtText = "ศูนย์บาท";
    } else {
        var len = baht.length;
        for (var i = 0; i < len; i++) {
            var n = baht.substr(i, 1);
            if (n != "0") {
                if (i == len - 1 && n == "1" && len > 1) {
                    bahtText += "เอ็ด";
                } else if (i == len - 2 && n == "2") {
                    bahtText += "ยี่สิบ";
                } else if (i == len - 2 && n == "1") {
                    bahtText += "สิบ";
                } else {
                    bahtText += numberText[n] + unit[len - i - 1];
                }
            }
        }
        bahtText += "บาท";
    }
    
    if (satang == "00") {
        bahtText += "ถ้วน";
    } else {
        var len = satang.length;
        for (var i = 0; i < len; i++) {
            var n = satang.substr(i, 1);
            if (n != "0") {
                if (i == len - 1 && n == "1" && len > 1) {
                    bahtText += "เอ็ด";
                } else if (i == len - 2 && n == "2") {
                    bahtText += "ยี่สิบ";
                } else if (i == len - 2 && n == "1") {
                    bahtText += "สิบ";
                } else {
                    bahtText += numberText[n] + unit[len - i - 1];
                }
            }
        }
        bahtText += "สตางค์";
    }
    return bahtText;
}

function exportPDF() {
    if (typeof html2pdf === 'undefined') {
        Swal.fire('ผิดพลาด', 'ไม่พบไลบรารีสำหรับสร้าง PDF กรุณารีโหลดหน้าเว็บ', 'error');
        return;
    }

    generatePreview();
    
    Swal.fire({
        title: 'กำลังเตรียมไฟล์ PDF...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Wait for preview to render and images to load
    setTimeout(() => {
        const element = document.getElementById('printable-area');
        if (!element) {
            Swal.close();
            Swal.fire('ผิดพลาด', 'ไม่พบพื้นที่สำหรับสร้าง PDF', 'error');
            return;
        }

        const opt = {
            margin: 0,
            filename: `quotation_${$('#doc_number').val() || 'document'}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2, 
                useCORS: true,
                letterRendering: true,
                scrollY: 0,
                windowWidth: 790
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        html2pdf().set(opt).from(element).save().then(() => {
            Swal.close();
        }).catch(err => {
            Swal.close();
            console.error('PDF Error:', err);
            Swal.fire('ผิดพลาด', 'ไม่สามารถสร้าง PDF ได้: ' + err.message, 'error');
        });
    }, 1500);
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

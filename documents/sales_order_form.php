<?php
require '../auth_check.php';
require '../config.php';
require '../thai_baht_helper.php';
require_once '../file_helper.php';

$company_id = $_SESSION['company_id'];
$edit_id = $_GET['id'] ?? null;
$so_data = null;

// Get current company info
$company_sql = "SELECT * FROM company WHERE id = ?";
$company_stmt = mysqli_prepare($conn, $company_sql);
mysqli_stmt_bind_param($company_stmt, "i", $company_id);
mysqli_stmt_execute($company_stmt);
$company_res = mysqli_stmt_get_result($company_stmt);
$company = mysqli_fetch_assoc($company_res);

// Get all companies for selection (for issuer)
$all_companies_sql = "SELECT id, company_name, address, phone, tax_id, logo FROM company ORDER BY company_name ASC";
$all_companies_res = mysqli_query($conn, $all_companies_sql);
$all_companies = [];
while ($row = mysqli_fetch_assoc($all_companies_res)) {
    $all_companies[] = $row;
}

// Load SO data if editing
if ($edit_id) {
    $sql = "SELECT * FROM sales_orders WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $edit_id, $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && $so_data = mysqli_fetch_assoc($result)) {
        // Resolve paths
        $so_data['header_logo'] = getFullPath($so_data['header_logo']);
        $so_data['qr_code_image'] = getFullPath($so_data['qr_code_image']);
        $so_data['signature1'] = getFullPath($so_data['signature1']);
        $so_data['signature2'] = getFullPath($so_data['signature2']);
        processItemsPaths($so_data['items']);
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบสั่งขาย - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        @media print {
            html, body {
                height: auto !important;
                overflow: visible !important;
                background: white !important;
            }
            body * {
                visibility: hidden;
            }
            #print-area, #print-area *, #printable-area, #printable-area * {
                visibility: visible !important;
            }
            #print-area {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 210mm !important;
                margin: 0 !important;
                padding: 10mm !important;
                box-sizing: border-box !important;
                display: block !important;
                background: white !important;
                z-index: 9999 !important;
            }
            @page {
                size: A4;
                margin: 0;
            }
            .swal2-container, .no-print {
                display: none !important;
            }
        }
        #print-area {
            background: white;
            padding: 20mm;
            width: 210mm;
            margin: 20px auto;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
            border-radius: 4px;
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
        <h1 class="text-xl font-bold">📄 <?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบสั่งขาย</h1>
        <a href="index.php?tab=sales_order" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">← กลับ</a>
    </div>
</div>

<div class="max-w-5xl mx-auto p-6 no-print">
    <!-- Form Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">ข้อมูลใบสั่งขาย</h2>
        
        <input type="hidden" id="so_id" value="<?= $edit_id ?? '' ?>">
        
        <div class="mb-6 bg-blue-50 p-4 rounded-xl border border-blue-100">
            <label class="block text-sm font-bold text-blue-800 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                ข้อมูลหัวเอกสาร (สามารถแก้ไขได้)
            </label>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">เลือกบริษัทต้นแบบ</label>
                    <select id="issuer_company_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" onchange="loadCompanyTemplate()">
                        <option value="">-- กำหนดเอง --</option>
                        <?php foreach ($all_companies as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                data-name="<?= htmlspecialchars($c['company_name']) ?>" 
                                data-address="<?= htmlspecialchars($c['address']) ?>" 
                                data-phone="<?= htmlspecialchars($c['phone']) ?>" 
                                data-taxid="<?= htmlspecialchars($c['tax_id']) ?>"
                                data-logo="<?= htmlspecialchars($c['logo'] ?? '') ?>"
                                <?= ($so_data['issuer_company_id'] ?? $company_id) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ชื่อบริษัทในหัวเอกสาร</label>
                    <input type="text" id="header_name" value="<?= htmlspecialchars($so_data['header_name'] ?? $company['company_name'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ที่อยู่ในหัวเอกสาร</label>
                    <textarea id="header_address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($so_data['header_address'] ?? $company['address'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เบอร์โทรศัพท์</label>
                    <input type="text" id="header_phone" value="<?= htmlspecialchars($so_data['header_phone'] ?? $company['phone'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" id="header_tax_id" value="<?= htmlspecialchars($so_data['header_tax_id'] ?? $company['tax_id'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">โลโก้หัวเอกสาร</label>
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden bg-white">
                            <img id="header_logo_preview" src="<?= $so_data['header_logo'] ?? ($company['logo'] ? '../'.$company['logo'] : '') ?>" class="<?= (empty($so_data['header_logo']) && empty($company['logo'])) ? 'hidden' : '' ?> w-full h-full object-contain">
                            <svg id="header_logo_placeholder" class="<?= (!empty($so_data['header_logo']) || !empty($company['logo'])) ? 'hidden' : '' ?> w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="file" id="header_logo_input" accept="image/*" onchange="previewHeaderLogo()" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลขที่ใบสั่งขาย</label>
                <input type="text" id="doc_number" value="<?= $so_data['doc_number'] ?? '' ?>" placeholder="เช่น SO<?= date('ymd') ?>001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่สั่งขาย</label>
                <input type="date" id="doc_date" value="<?= $so_data['doc_date'] ?? date('Y-m-d') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>

        <div class="border-t pt-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">ข้อมูลลูกค้า (Customer)</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสลูกค้า</label>
                    <input type="text" id="customer_code" value="<?= htmlspecialchars($so_data['customer_code'] ?? '') ?>" placeholder="เช่น RY-001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อลูกค้า / บริษัท</label>
                    <input type="text" id="customer_name" value="<?= htmlspecialchars($so_data['customer_name'] ?? '') ?>" placeholder="ชื่อลูกค้า" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">ที่อยู่</label>
                <textarea id="customer_address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($so_data['customer_address'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">โทรศัพท์</label>
                    <input type="text" id="customer_phone" value="<?= htmlspecialchars($so_data['customer_phone'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                    <input type="email" id="customer_email" value="<?= htmlspecialchars($so_data['customer_email'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" id="customer_tax_id" value="<?= htmlspecialchars($so_data['customer_tax_id'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>
        </div>

        <div class="mb-6 bg-indigo-50 p-4 rounded-xl border border-indigo-100">
            <div class="flex justify-between items-center mb-3">
                <label class="block text-sm font-bold text-indigo-800 flex items-center gap-2">
                    <i class="fas fa-file-invoice-dollar"></i> เงื่อนไขการชำระเงิน
                </label>
                <div class="flex items-center gap-2">
                    <select id="payment_terms_template" onchange="loadPaymentTermsTemplate()" class="text-xs px-2 py-1 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none min-w-[150px]">
                        <option value="">-- เลือกเทมเพลต --</option>
                    </select>
                    <button onclick="manageTemplates('payment_terms')" type="button" class="text-xs bg-white text-indigo-600 border border-indigo-200 px-2 py-1 rounded-lg hover:bg-indigo-100 transition-all">
                        ⚙️ จัดการ
                    </button>
                </div>
            </div>
            <textarea id="payment_terms" rows="2" placeholder="ระบุเงื่อนไขการชำระเงิน" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none bg-white text-sm"><?= htmlspecialchars($so_data['payment_terms'] ?? 'เงินสด/โอนเงิน') ?></textarea>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพท้ายเอกสาร (QR Code)</label>
            <input type="file" id="qr_code" accept="image/*" onchange="previewQRCode()" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
            <img id="qr_preview" src="<?= $so_data['qr_code_image'] ?? '' ?>" class="mt-2 <?= empty($so_data['qr_code_image']) ? 'hidden' : '' ?> max-w-xs max-h-32 object-contain border rounded">
        </div>

        <div class="border-t pt-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">รายการสินค้า/บริการ</h3>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="openStockSelector()" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-4 py-2 rounded-xl transition-all flex items-center gap-2 border border-emerald-200 shadow-sm active:scale-95">
                        <i class="fas fa-warehouse"></i>
                        <span>ดึงจากคลังสินค้า</span>
                    </button>
                    <button onclick="addItem()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl transition-all flex items-center gap-2 shadow-md active:scale-95">
                        <i class="fas fa-plus-circle"></i>
                        <span>เพิ่มรายการ</span>
                    </button>
                </div>
            </div>

            <div id="items-container" class="space-y-4">
                <!-- Items will be added here -->
            </div>
        </div>

        <div class="border-t pt-6 mb-6">
            <div class="flex items-center gap-4 mb-4">
                <input type="checkbox" id="vat_enabled" <?= ($so_data['vat_enabled'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 text-emerald-600 rounded">
                <label for="vat_enabled" class="text-sm font-medium text-gray-700">คิด VAT 7%</label>
            </div>

            <div class="flex items-center gap-4 mb-4">
                <input type="radio" name="vat_type" value="exclude" id="vat_exclude" <?= ($so_data['vat_type'] ?? 'exclude') == 'exclude' ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600">
                <label for="vat_exclude" class="text-sm text-gray-700">ราคายังไม่รวม VAT</label>
                
                <input type="radio" name="vat_type" value="include" id="vat_include" <?= ($so_data['vat_type'] ?? 'exclude') == 'include' ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600">
                <label for="vat_include" class="text-sm text-gray-700">ราคารวม VAT แล้ว</label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                <div class="flex justify-between items-center mb-3">
                    <label class="text-sm font-bold text-gray-700">หมายเหตุ (Notes)</label>
                    <div class="flex items-center gap-2">
                        <select id="notes_template" onchange="loadNotesTemplate()" class="text-xs px-2 py-1 border border-gray-300 rounded-lg outline-none min-w-[150px]">
                            <option value="">-- เลือกเทมเพลต --</option>
                        </select>
                        <button onclick="manageTemplates('notes')" type="button" class="text-xs bg-white text-gray-600 border border-gray-300 px-2 py-1 rounded-lg hover:bg-gray-100 transition-all">
                            ⚙️
                        </button>
                    </div>
                </div>
                <textarea id="notes" rows="4" placeholder="บันทึกหมายเหตุเพิ่มเติม (ถ้ามี)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none text-sm bg-white"><?= htmlspecialchars($so_data['notes'] ?? '') ?></textarea>
            </div>
            
            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                <div class="flex justify-between items-center mb-3">
                    <label class="text-sm font-bold text-emerald-800">เงื่อนไข (Conditions)</label>
                    <div class="flex items-center gap-2">
                        <select id="conditions_template" onchange="loadConditionsTemplate()" class="text-xs px-2 py-1 border border-emerald-200 rounded-lg outline-none min-w-[150px]">
                            <option value="">-- เลือกเทมเพลต --</option>
                        </select>
                        <button onclick="manageTemplates('conditions')" type="button" class="text-xs bg-white text-emerald-600 border border-emerald-200 px-2 py-1 rounded-lg hover:bg-emerald-100 transition-all">
                            ⚙️
                        </button>
                    </div>
                </div>
                <textarea id="conditions" rows="4" placeholder="ระบุเงื่อนไขเพิ่มเติมที่ส่วนท้ายเอกสาร" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none text-sm bg-white"><?= htmlspecialchars($so_data['conditions'] ?? "* กรุณาส่งภายใน 30 วัน\n* ส่งที่Email: rongyanghome@gmail.com, หรือไลน์ OA= @ttgoldenteak") ?></textarea>
            </div>
        </div>

        <div class="border-t pt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">ลายเซ็น</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="flex gap-2">
                        <input type="file" id="signature1" accept="image/*" onchange="previewSignature(1)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        <button type="button" onclick="openSignaturePad(1)" class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg hover:bg-emerald-200 transition-all text-xs flex items-center gap-1">
                            <i class="fas fa-pen-nib"></i> เซ็นชื่อ
                        </button>
                        <button type="button" onclick="clearSignature(1)" class="bg-rose-50 text-rose-600 px-3 py-1 rounded-lg hover:bg-rose-100 transition-all text-xs flex items-center gap-1">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
                    <img id="sig1_preview" src="<?= $so_data['signature1'] ?? '' ?>" class="signature-preview mt-2 <?= empty($so_data['signature1']) ? 'hidden' : '' ?> border rounded">
                    <input type="text" id="signer_name1" value="<?= htmlspecialchars($so_data['signer_name1'] ?? '') ?>" placeholder="ชื่อผู้สั่งขาย (ถ้ามี)" class="w-full px-3 py-2 mt-2 border border-emerald-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้อนุมัติ</label>
                    <div class="flex gap-2">
                        <input type="file" id="signature2" accept="image/*" onchange="previewSignature(2)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        <button type="button" onclick="openSignaturePad(2)" class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg hover:bg-emerald-200 transition-all text-xs flex items-center gap-1">
                            <i class="fas fa-pen-nib"></i> เซ็นชื่อ
                        </button>
                        <button type="button" onclick="clearSignature(2)" class="bg-rose-50 text-rose-600 px-3 py-1 rounded-lg hover:bg-rose-100 transition-all text-xs flex items-center gap-1">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
                    <img id="sig2_preview" src="<?= $so_data['signature2'] ?? '' ?>" class="signature-preview mt-2 <?= empty($so_data['signature2']) ? 'hidden' : '' ?> border rounded">
                    <input type="text" id="signer_name2" value="<?= htmlspecialchars($so_data['signer_name2'] ?? '') ?>" placeholder="ชื่อผู้อนุมัติ (ถ้ามี)" class="w-full px-3 py-2 mt-2 border border-emerald-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mt-10 pt-8 border-t no-print">
            <button onclick="saveSO()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> บันทึก
            </button>
            <button onclick="generatePreview()" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-3 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-eye"></i> ดูตัวอย่าง
            </button>
            
            <?php if ($edit_id): ?>
            <button onclick="convertToInvoice(<?= $edit_id ?>)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-file-invoice"></i> ออกใบแจ้งหนี้
            </button>
            <button onclick="orderProduction(<?= $edit_id ?>)" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-industry"></i> สั่งผลิต
            </button>
            <button onclick="requestMaterials(<?= $edit_id ?>)" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-3 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-boxes"></i> เบิกสินค้า
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Print Preview Section -->
<div id="print-area" style="display: none;"></div>

<script>
let itemCount = 0;
const existingItems = <?= json_encode($so_data['items'] ?? '[]') ?>;

$(document).ready(function() {
    if (existingItems && existingItems !== '[]') {
        try {
            const items = JSON.parse(existingItems);
            items.forEach(item => addItem(item));
        } catch (e) {
            console.error("Error parsing items", e);
            addItem();
        }
    } else {
        addItem();
    }
    loadTemplates();
});

function addItem(data = null) {
    itemCount++;
    const html = `
        <div class="item-row border border-gray-200 rounded-lg p-4 bg-gray-50" data-item="${itemCount}">
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-4">
                    <label class="block text-xs font-medium text-gray-600 mb-1">รายการ</label>
                    <input type="text" class="item-name w-full px-3 py-2 border border-gray-300 rounded text-sm" placeholder="ชื่อสินค้า/บริการ" value="${data?.name || ''}">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">จำนวน</label>
                    <input type="number" class="item-qty w-full px-3 py-2 border border-gray-300 rounded text-sm" value="${data?.qty || 1}" min="0" step="0.01" onchange="calculateTotal()">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">หน่วย</label>
                    <input type="text" class="item-unit w-full px-3 py-2 border border-gray-300 rounded text-sm" placeholder="หน่วย" value="${data?.unit || ''}">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ราคา/หน่วย</label>
                    <input type="number" class="item-price w-full px-3 py-2 border border-gray-300 rounded text-sm" value="${data?.price || 0}" min="0" step="0.01" onchange="calculateTotal()">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ส่วนลด</label>
                    <input type="number" class="item-discount w-full px-3 py-2 border border-gray-300 rounded text-sm" value="${data?.discount || 0}" min="0" step="0.01" onchange="calculateTotal()">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">รวม</label>
                    <input type="text" class="item-total w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm font-bold" readonly value="0">
                </div>
                <div class="col-span-1 flex items-end">
                    <button onclick="removeItem(${itemCount})" class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">รูปสินค้า (ถ้ามี)</label>
                    <input type="file" accept="image/*" class="item-image w-full text-xs" onchange="previewItemImage(this, ${itemCount})">
                </div>
                <img id="item_img_${itemCount}" src="${data?.image || ''}" class="${data?.image ? '' : 'hidden'} max-w-xs max-h-20 object-contain border rounded shadow-sm">
            </div>
        </div>
    `;
    $('#items-container').append(html);
    calculateTotal();
}

function removeItem(id) {
    if ($('.item-row').length > 1) {
        $(`.item-row[data-item="${id}"]`).remove();
        calculateTotal();
    }
}

function previewItemImage(input, id) {
    if (input.files && input.files[0]) {
        compressImage(input.files[0], 600, 600, 0.6).then(compressedBase64 => {
            $(`#item_img_${id}`).attr('src', compressedBase64).removeClass('hidden');
        });
    }
}

function compressImage(file, maxWidth, maxHeight, quality) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function(event) {
            const img = new Image();
            img.src = event.target.result;
            img.onload = function() {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height *= maxWidth / width;
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width *= maxHeight / height;
                        height = maxHeight;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                resolve(canvas.toDataURL('image/jpeg', quality));
            };
            img.onerror = reject;
        };
        reader.onerror = reject;
    });
}

function getFullPath(path) {
    if (!path) return '';
    if (path.indexOf('data:image') === 0) return path;
    if (path.indexOf('http') === 0) return path;
    if (path.indexOf('../') === 0) return path;
    return '../' + path;
}

function openStockSelector() {
    Swal.fire({
        title: 'เลือกสินค้าจากคลังสินค้า',
        html: `
            <div class="text-left space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700">เลือกคลังสินค้า</label>
                    <select id="swal_warehouse_id" class="swal2-input !m-0 !w-full" onchange="loadWarehouseProducts(this.value)">
                        <option value="">-- เลือกคลังสินค้า --</option>
                    </select>
                </div>
                <div id="swal_product_list" class="space-y-2 max-h-96 overflow-y-auto border rounded-lg p-2 bg-gray-50 min-h-[150px]">
                    <p class="text-center text-gray-400 py-12">กรุณาเลือกคลังสินค้าเพื่อดูรายการ</p>
                </div>
            </div>
        `,
        width: '700px',
        showConfirmButton: false,
        showCloseButton: true,
        didOpen: () => {
            $.ajax({
                url: '../stock/stock_action.php',
                type: 'GET',
                data: { action: 'get_warehouses_json' },
                success: function(res) {
                    if (Array.isArray(res)) {
                        const select = $('#swal_warehouse_id');
                        res.forEach(wh => {
                            select.append(`<option value="${wh.id}">${wh.name}</option>`);
                        });
                    }
                }
            });
        }
    });
}

function loadWarehouseProducts(whId) {
    if (!whId) {
        $('#swal_product_list').html('<p class="text-center text-gray-400 py-12">กรุณาเลือกคลังสินค้าเพื่อดูรายการ</p>');
        return;
    }
    
    $('#swal_product_list').html('<div class="flex flex-col items-center justify-center py-12 text-gray-500"><svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังโหลดข้อมูลสินค้า...</div>');
    
    $.ajax({
        url: '../stock/stock_action.php',
        type: 'GET',
        data: { action: 'get_warehouse_products', warehouse_id: whId },
        success: function(res) {
            let html = '';
            if (!res || res.length === 0) {
                html = '<div class="text-center text-gray-400 py-12 flex flex-col items-center gap-2"><svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg><p>ไม่มีสินค้าในคลังนี้</p></div>';
            } else {
                res.forEach(p => {
                    const productJson = JSON.stringify(p).replace(/'/g, "&#39;").replace(/"/g, '&quot;');
                    html += `
                        <div class="flex items-center justify-between p-3 bg-white border rounded-lg hover:border-emerald-500 hover:shadow-md transition-all cursor-pointer group" onclick='addSelectedProduct(${productJson})'>
                            <div class="flex items-center gap-3">
                                <div class="w-14 h-14 bg-gray-50 rounded border overflow-hidden flex items-center justify-center">
                                    ${p.image_url ? `<img src="../stock/${p.image_url}" class="w-full h-full object-contain">` : '<svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>'}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 group-hover:text-emerald-600 transition-colors">${p.name}</div>
                                    <div class="text-xs text-gray-500">SKU: ${p.sku || '-'} | คงเหลือ: <span class="font-bold text-gray-700">${p.balance}</span> ${p.unit}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-emerald-600 text-lg">฿${parseFloat(p.price).toLocaleString()}</div>
                                <div class="text-[10px] text-gray-400 group-hover:text-emerald-500 font-bold uppercase tracking-wider">คลิกเพื่อเลือก</div>
                            </div>
                        </div>
                    `;
                });
            }
            $('#swal_product_list').html(html);
        }
    });
}

function addSelectedProduct(p) {
    addItem({
        name: p.name,
        qty: 1,
        unit: p.unit,
        price: p.price,
        discount: 0
    });
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true
    });
    Toast.fire({
        icon: 'success',
        title: `เพิ่ม ${p.name} เรียบร้อยแล้ว`
    });
}

function loadTemplates() {
    $.ajax({
        url: 'template_action.php',
        type: 'GET',
        data: { action: 'get_templates' },
        success: function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                $('#payment_terms_template').html('<option value="">-- เลือกเทมเพลต --</option>');
                res.data.filter(t => t.template_type === 'payment_terms').forEach(t => {
                    $('#payment_terms_template').append(`<option value="${t.id}" data-name="${t.template_name}" data-content="${t.template_content}">${t.template_name}</option>`);
                });
                
                $('#notes_template').html('<option value="">-- เลือกเทมเพลต --</option>');
                res.data.filter(t => t.template_type === 'notes').forEach(t => {
                    $('#notes_template').append(`<option value="${t.id}" data-name="${t.template_name}" data-content="${t.template_content}">${t.template_name}</option>`);
                });

                $('#conditions_template').html('<option value="">-- เลือกเทมเพลต --</option>');
                res.data.filter(t => t.template_type === 'conditions').forEach(t => {
                    $('#conditions_template').append(`<option value="${t.id}" data-name="${t.template_name}" data-content="${t.template_content}">${t.template_name}</option>`);
                });
            }
        }
    });
}

function loadPaymentTermsTemplate() {
    const selected = $('#payment_terms_template option:selected');
    const content = selected.data('content');
    if (content) {
        $('#payment_terms').val(content);
    }
}

function loadNotesTemplate() {
    const selected = $('#notes_template option:selected');
    const content = selected.data('content');
    if (content) {
        $('#notes').val(content);
    }
}

function loadConditionsTemplate() {
    const selected = $('#conditions_template option:selected');
    const content = selected.data('content');
    if (content) {
        $('#conditions').val(content);
    }
}

function manageTemplates(type) {
    let typeLabel = '';
    switch(type) {
        case 'payment_terms': typeLabel = 'เงื่อนไขการชำระเงิน'; break;
        case 'notes': typeLabel = 'หมายเหตุ'; break;
        case 'conditions': typeLabel = 'เงื่อนไข'; break;
    }
    Swal.fire({
        title: `จัดการเทมเพลต${typeLabel}`,
        html: `
            <div class="text-left">
                <div class="mb-4">
                    <button onclick="addNewTemplate('${type}')" class="w-full bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
                        + เพิ่มเทมเพลตใหม่
                    </button>
                </div>
                <div id="template-list-${type}" class="space-y-2 max-h-96 overflow-y-auto"></div>
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

function addNewTemplate(type) {
    let typeLabel = '';
    switch(type) {
        case 'payment_terms': typeLabel = 'เงื่อนไขการชำระเงิน'; break;
        case 'notes': typeLabel = 'หมายเหตุ'; break;
        case 'conditions': typeLabel = 'เงื่อนไข'; break;
    }
    Swal.fire({
        title: `เพิ่มเทมเพลต${typeLabel}ใหม่`,
        html: `
            <input id="template_name" class="swal2-input" placeholder="ชื่อเทมเพลต">
            <textarea id="template_content" class="swal2-textarea" placeholder="เนื้อหา"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        preConfirm: () => {
            return {
                name: $('#template_name').val(),
                content: $('#template_content').val()
            }
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
                success: function() {
                    loadTemplates();
                    manageTemplates(type);
                }
            });
        }
    });
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

function previewSignature(num) {
    const input = document.getElementById(`signature${num}`);
    const preview = document.getElementById(`sig${num}_preview`);
    
    if (input.files && input.files[0]) {
        compressImage(input.files[0], 600, 300, 0.7).then(compressedBase64 => {
            preview.src = compressedBase64;
            preview.classList.remove('hidden');
        });
    }
}

function previewHeaderLogo() {
    const input = document.getElementById('header_logo_input');
    const preview = document.getElementById('header_logo_preview');
    const placeholder = document.getElementById('header_logo_placeholder');
    if (input.files && input.files[0]) {
        compressImage(input.files[0], 500, 500, 0.7).then(compressedBase64 => {
            preview.src = compressedBase64;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        });
    }
}

function loadCompanyTemplate() {
    const selected = $('#issuer_company_id option:selected');
    if (selected.val()) {
        $('#header_name').val(selected.data('name'));
        $('#header_address').val(selected.data('address'));
        $('#header_phone').val(selected.data('phone'));
        $('#header_tax_id').val(selected.data('taxid'));
        
        const logo = selected.data('logo');
        if (logo) {
            $('#header_logo_preview').attr('src', getFullPath(logo)).removeClass('hidden');
            $('#header_logo_placeholder').addClass('hidden');
        } else {
            $('#header_logo_preview').addClass('hidden').attr('src', '');
            $('#header_logo_placeholder').removeClass('hidden');
        }
    }
}

function previewQRCode() {
    const input = document.getElementById('qr_code');
    const preview = document.getElementById('qr_preview');
    if (input.files && input.files[0]) {
        compressImage(input.files[0], 500, 500, 0.7).then(compressedBase64 => {
            preview.src = compressedBase64;
            preview.classList.remove('hidden');
        });
    }
}

function saveSO() {
    const docNumber = $('#doc_number').val().trim();
    const docDate = $('#doc_date').val();
    const customerName = $('#customer_name').val().trim();
    
    if (!docNumber || !docDate || !customerName) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกเลขที่, วันที่ และชื่อลูกค้า', 'warning');
        return;
    }
    
    const items = [];
    $('.item-row').each(function() {
        const name = $(this).find('.item-name').val().trim();
        const qty = $(this).find('.item-qty').val();
        const unit = $(this).find('.item-unit').val().trim();
        const price = $(this).find('.item-price').val();
        const discount = $(this).find('.item-discount').val();
        const image = $(this).find('img').attr('src') || '';
        if (name) {
            items.push({ name, qty, unit, price, discount, image });
        }
    });

    const totals = calculateTotal();
    const vatEnabled = $('#vat_enabled').is(':checked') ? 1 : 0;
    const vatType = $('input[name="vat_type"]:checked').val();
    
    let vatAmount = 0;
    let grandTotal = totals.subtotal - totals.totalDiscount;
    
    if (vatEnabled) {
        if (vatType === 'exclude') {
            vatAmount = grandTotal * 0.07;
            grandTotal += vatAmount;
        } else {
            vatAmount = grandTotal * 7 / 107;
        }
    }
    
    const data = {
        action: 'save',
        id: $('#so_id').val(),
        doc_number: docNumber,
        doc_date: docDate,
        customer_code: $('#customer_code').val(),
        customer_name: customerName,
        customer_address: $('#customer_address').val(),
        customer_phone: $('#customer_phone').val(),
        customer_email: $('#customer_email').val(),
        customer_tax_id: $('#customer_tax_id').val(),
        payment_terms: $('#payment_terms').val(),
        items: JSON.stringify(items),
        vat_enabled: vatEnabled,
        vat_type: vatType,
        subtotal: totals.subtotal,
        total_discount: totals.totalDiscount,
        vat_amount: vatAmount,
        grand_total: grandTotal,
        notes: $('#notes').val(),
        conditions: $('#conditions').val(),
        issuer_company_id: $('#issuer_company_id').val(),
        header_name: $('#header_name').val(),
        header_address: $('#header_address').val(),
        header_phone: $('#header_phone').val(),
        header_tax_id: $('#header_tax_id').val(),
        header_logo: $('#header_logo_preview').attr('src') || '',
        signature1: $('#sig1_preview').attr('src') || '',
        signature2: $('#sig2_preview').attr('src') || '',
        signer_name1: $('#signer_name1').val(),
        signer_name2: $('#signer_name2').val(),
        qr_code_image: $('#qr_preview').attr('src') || ''
    };
    
    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: 'sales_order_action.php',
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire('สำเร็จ', response.message, 'success').then(() => {
                    if (!$('#so_id').val()) {
                        window.location.href = 'sales_order_form.php?id=' + response.id;
                    }
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
            vatAmount = netSubtotal * 7 / 107;
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
        const image = $(this).find('img').attr('src') || '';
        const total = (qty * price) - discount;
        
        itemsHTML += `
            <tr style="border-bottom: 1px solid #000;">
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${rowNum}</td>
                <td style="padding: 8px; border-right: 1px solid #000;">
                    <div style="font-weight: bold;">${name || '-'}</div>
                    ${image ? `<img src="${image}" style="max-height: 60px; margin-top: 5px; border: 1px solid #eee;">` : ''}
                </td>
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${unit}</td>
                <td style="padding: 8px; text-align: right; border-right: 1px solid #000;">${price.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="padding: 8px; text-align: center; border-right: 1px solid #000;">${discount > 0 ? discount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                <td style="padding: 8px; text-align: right;">${total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `;
        rowNum++;
    });

    // Removed empty rows loop as per user request to show only actual data
    
    const sig1 = $('#sig1_preview').attr('src') || '';
    const sig2 = $('#sig2_preview').attr('src') || '';
    
    const thaiAmount = ArabicToThaiBaht(grandTotal);

    const html = `
        <div style="width: 100%; max-width: 210mm; background: white; font-family: 'Sarabun', sans-serif; color: #000; position: relative; box-sizing: border-box;" id="printable-area">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div style="width: 150px;">
                    ${$('#header_logo_preview').attr('src') ? `<img src="${$('#header_logo_preview').attr('src')}" style="width: 120px; height: auto;">` : `<img src="../assets/logo/logo.png" style="width: 120px; height: auto;">`}
                </div>
                <div style="flex: 1; text-align: center; padding: 0 10px;">
                    <h1 style="font-size: 18px; font-weight: bold; margin: 0;">${$('#header_name').val()}</h1>
                    <p style="font-size: 14px; margin: 2px 0;">${$('#header_address').val()}</p>
                    <p style="font-size: 14px; margin: 2px 0;">โทร. ${$('#header_phone').val()}</p>
                    <p style="font-size: 14px; margin: 2px 0;">เลขที่ประจำตัวผู้เสียภาษี ${$('#header_tax_id').val()}</p>
                </div>
                <div style="width: 150px; text-align: right;">
                    <h2 style="font-size: 20px; font-weight: bold; margin: 0;">ใบสั่งขาย</h2>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 14px;">
                <div style="width: 60%;">
                    <p style="margin: 2px 0;"><strong>ชื่อ :</strong> ${$('#customer_name').val() || '-'}</p>
                    <p style="margin: 2px 0;"><strong>ที่อยู่ :</strong> ${$('#customer_address').val() || '-'}</p>
                    <p style="margin: 10px 0 2px 0;"><strong>โทรศัพท์ :</strong> ${$('#customer_phone').val() || '-'} &nbsp;&nbsp;&nbsp;&nbsp; <strong>อีเมล :</strong> ${$('#customer_email').val() || '-'} &nbsp;&nbsp;&nbsp;&nbsp; <strong>รหัสผู้เสียภาษี :</strong> ${$('#customer_tax_id').val() || '-'}</p>
                </div>
            <div style="width: 35%; text-align: right;">
                    <p style="margin: 2px 0;"><strong>เลขที่ :</strong> ${$('#doc_number').val()}</p>
                    <p style="margin: 2px 0;"><strong>วันที่ :</strong> ${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</p>
                    <p style="margin: 2px 0;"><strong>เงื่อนไขการชำระ :</strong> ${$('#payment_terms_template option:selected').val() ? $('#payment_terms_template option:selected').text() : ($('#payment_terms').val() || '-')}</p>
                </div>
            </div>

            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; font-size: 13px; margin-bottom: 0;">
                <thead>
                    <tr style="background-color: #92d050; color: #000; border-bottom: 1px solid #000;">
                        <th style="border-right: 1px solid #000; padding: 8px; width: 50px; white-space: nowrap;">ลำดับ</th>
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

            <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; border-top: none; font-size: 14px;">
                <tr>
                    <td style="width: 65%; padding: 10px; vertical-align: top; border-right: 1px solid #000;">
                        <p style="margin: 0; font-weight: bold;">หมายเหตุ :</p>
                        <p style="margin: 5px 0; font-size: 12px; white-space: pre-line;">${$('#notes').val()}</p>
                    </td>
                    <td style="width: 35%; padding: 0; vertical-align: top;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr style="background-color: #92d050;">
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; border-right: 1px solid #000;">มูลค่ารวมก่อนเสียภาษี</td>
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; text-align: right;">${(grandTotal - vatAmount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                            <tr style="background-color: #92d050;">
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; border-right: 1px solid #000;">ภาษีมูลค่าเพิ่ม(VAT)</td>
                                <td style="padding: 5px 10px; border-bottom: 1px solid #000; text-align: right;">${vatAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
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

            <div style="margin-top: 10px; font-size: 13px;">
                <p style="margin: 2px 0; font-weight: bold; text-decoration: underline;">เงื่อนไข :</p>
                <div style="white-space: pre-line; line-height: 1.5;">${$('#conditions').val()}</div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 20px;">
                <div style="width: 150px;">
                    ${$('#qr_preview').attr('src') ? 
                        `<img src="${$('#qr_preview').attr('src')}" style="width: 100%; object-fit: contain;">` : 
                        `<div style="border: 1px solid #ccc; padding: 5px; text-align: center; font-size: 10px;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=1971292055" style="width: 70px;">
                            <p style="margin: 2px 0;">ธ.กสิกรไทย<br>1971-292-055</p>
                         </div>`
                    }
                </div>
                <div style="flex: 1; display: flex; justify-content: space-around; text-align: center;">
                    <div style="width: 200px;">
                        <p style="margin-bottom: 60px;">ผู้สั่งขาย</p>
                        ${sig1 ? `<img src="${sig1}" style="height: 50px; object-fit: contain; margin-bottom: -10px; display: block; margin-left: auto; margin-right: auto;">` : '<div style="height: 50px;"></div>'}
                        <div style="border-bottom: 1px dotted #000; margin: 0 auto; width: 150px;"></div>
                        <p style="margin: 5px 0;">${$('#signer_name1').val()}</p>
                    </div>
                    <div style="width: 200px;">
                        <p style="margin-bottom: 60px;">ผู้อนุมัติ</p>
                        ${sig2 ? `<img src="${sig2}" style="height: 50px; object-fit: contain; margin-bottom: -10px; display: block; margin-left: auto; margin-right: auto;">` : '<div style="height: 50px;"></div>'}
                        <div style="border-bottom: 1px dotted #000; margin: 0 auto; width: 150px;"></div>
                        <p style="margin: 5px 0;">${$('#signer_name2').val()}</p>
                    </div>
                </div>
            </div>

            <div style="background-color: #92d050; color: #000; text-align: center; padding: 5px; margin-top: 20px; font-size: 12px; font-weight: bold;">
                BANSAKTHONG RONGYANG CO., LTD.
            </div>
        </div>
    `;
    
    Swal.fire({
        title: 'ตัวอย่างเอกสาร',
        html: `
            <div id="modal-preview-container" class="text-left overflow-auto" style="max-height: 80vh;">
                ${html}
            </div>
            <div class="mt-4 flex gap-2 justify-center no-print">
                <button onclick="window.print()" class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-emerald-700 transition-all flex items-center gap-2">
                    <i class="fas fa-print"></i> พิมพ์เอกสาร (A4)
                </button>
                <button onclick="exportPDF()" class="bg-rose-500 text-white px-6 py-2 rounded-lg font-bold hover:bg-rose-600 transition-all flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i> บันทึกเป็น PDF
                </button>
            </div>
        `,
        width: '900px',
        showConfirmButton: false,
        showCloseButton: true,
        didOpen: () => {
            $('#print-area').html(html).removeClass('hidden');
        }
    });
}

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
    generatePreview();
    setTimeout(() => {
        const element = document.getElementById('printable-area');
        const opt = {
            margin: 0,
            filename: `SO_${$('#doc_number').val()}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }, 500);
}

function convertToInvoice(id) {
    Swal.fire({
        title: 'ยืนยันการแปลงเอกสาร?',
        text: 'คุณต้องการแปลงใบสั่งขายนี้เป็นใบแจ้งหนี้ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, แปลงเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'sales_order_action.php',
                type: 'POST',
                data: { action: 'convert_to_invoice', id: id },
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: res.message,
                            showCancelButton: true,
                            confirmButtonText: 'ไปหน้าใบแจ้งหนี้',
                            cancelButtonText: 'อยู่ที่เดิม'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'invoice_form.php?id=' + res.inv_id;
                            }
                        });
                    } else Swal.fire('ผิดพลาด', res.message, 'error');
                }
            });
        }
    });
}

function orderProduction(id) {
    // Assuming production flow exists
    window.location.href = '../stock/index.php?tab=production&so_id=' + id;
}

function requestMaterials(id) {
    // Assuming requisition flow exists
    window.location.href = '../stock/index.php?tab=requisition&so_id=' + id;
}

function exportPDF() {
    generatePreview();
    Swal.fire({
        title: 'กำลังเตรียมไฟล์ PDF...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    setTimeout(() => {
        const element = document.getElementById('print-area');
        const opt = {
            margin: [5, 5],
            filename: `SO_${$('#doc_number').val() || 'document'}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { 
                scale: 2, 
                useCORS: true,
                letterRendering: true,
                scrollY: 0,
                scrollX: 0
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait', compress: true }
        };
        html2pdf().set(opt).from(element).save().then(() => {
            Swal.close();
        });
    }, 500);
}

function openSignaturePad(num) {
    Swal.fire({
        title: 'เซ็นชื่อ',
        html: `
            <div style="border: 1px solid #ccc; background: white; border-radius: 8px; margin-bottom: 10px;">
                <canvas id="signature-pad-${num}" width="400" height="200" style="touch-action: none; cursor: crosshair;"></canvas>
            </div>
            <button type="button" id="clear-signature-${num}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">ล้างค่า</button>
        `,
        showCancelButton: true,
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก',
        didOpen: () => {
            const canvas = document.getElementById(`signature-pad-${num}`);
            const signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: 'rgb(0, 0, 0)'
            });
            
            document.getElementById(`clear-signature-${num}`).addEventListener('click', () => {
                signaturePad.clear();
            });

            window[`pad_${num}`] = signaturePad;
        },
        preConfirm: () => {
            const signaturePad = window[`pad_${num}`];
            if (signaturePad.isEmpty()) {
                Swal.showValidationMessage('กรุณาเซ็นชื่อก่อนตกลง');
                return false;
            }
            return signaturePad.toDataURL('image/png');
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const dataURL = result.value;
            $(`#sig${num}_preview`).attr('src', dataURL).removeClass('hidden');
            delete window[`pad_${num}`];
        }
    });
}

function clearSignature(num) {
    $(`#sig${num}_preview`).attr('src', '').addClass('hidden');
    $(`#signature${num}`).val('');
}
</script>

</body>
</html>

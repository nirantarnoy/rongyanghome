<?php
require '../auth_check.php';
require '../config.php';
require '../thai_baht_helper.php';
require_once '../file_helper.php';

$company_id = $_SESSION['company_id'];
$edit_id = $_GET['id'] ?? null;
$gr_data = null;

// Get current company info
$company_sql = "SELECT * FROM company WHERE id = ?";
$company_stmt = mysqli_prepare($conn, $company_sql);
mysqli_stmt_bind_param($company_stmt, "i", $company_id);
mysqli_stmt_execute($company_stmt);
$company_res = mysqli_stmt_get_result($company_stmt);
$company = mysqli_fetch_assoc($company_res);

// Get all companies for selection (for issuer)
$all_companies_sql = "SELECT id, company_name, address, phone, tax_id FROM company ORDER BY company_name ASC";
$all_companies_res = mysqli_query($conn, $all_companies_sql);
$all_companies = [];
while ($row = mysqli_fetch_assoc($all_companies_res)) {
    $all_companies[] = $row;
}

// Load GR data if editing
if ($edit_id) {
    $sql = "SELECT * FROM goods_receipts WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $edit_id, $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $gr_data = mysqli_fetch_assoc($result);
    if ($gr_data) {
        $gr_data['header_logo'] = getFullPath($gr_data['header_logo']);
        $gr_data['qr_code_image'] = getFullPath($gr_data['qr_code_image']);
        $gr_data['signature1'] = getFullPath($gr_data['signature1']);
        $gr_data['signature2'] = getFullPath($gr_data['signature2']);
        processItemsPaths($gr_data['items']);
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบรับสินค้า - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        
        .gr-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .gr-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        .gr-company-info {
            text-align: center;
            flex: 1;
        }
        .gr-title {
            font-size: 24px;
            font-weight: bold;
            text-align: right;
            width: 150px;
        }
        .gr-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .gr-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .gr-table th {
            background-color: #76b852;
            color: black;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-weight: bold;
        }
        .gr-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        .gr-footer {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            border: 1px solid #000;
        }
        .gr-footer-left {
            padding: 10px;
            border-right: 1px solid #000;
        }
        .gr-footer-right {
            background-color: #e2efda;
        }
        .gr-footer-right table {
            width: 100%;
            border-collapse: collapse;
        }
        .gr-footer-right td {
            padding: 5px 10px;
            border: 1px solid #000;
        }
        .gr-amount-words {
            text-align: center;
            padding: 10px;
            border-top: 1px solid #000;
            font-weight: bold;
        }
        .gr-signatures {
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
    </style>
</head>
<body class="bg-gray-50">

<div class="no-print bg-gradient-to-r from-emerald-600 to-emerald-700 text-white p-4 shadow-lg">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">📦 <?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบรับสินค้า</h1>
        <a href="index.php?tab=goods_receipt" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">← กลับ</a>
    </div>
</div>

<div class="max-w-5xl mx-auto p-6 no-print">
    <!-- Form Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">ข้อมูลใบรับสินค้า</h2>
        
        <input type="hidden" id="gr_id" value="<?= $edit_id ?? '' ?>">
        
        <div class="mb-6 bg-emerald-50 p-4 rounded-xl border border-emerald-100">
            <label class="block text-sm font-bold text-emerald-800 mb-3 flex items-center gap-2">
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
                                <?= ($gr_data['issuer_company_id'] ?? $company_id) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ชื่อบริษัทในหัวเอกสาร</label>
                    <input type="text" id="header_name" value="<?= htmlspecialchars($gr_data['header_name'] ?? $company['company_name'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ที่อยู่ในหัวเอกสาร</label>
                    <textarea id="header_address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500"><?= htmlspecialchars($gr_data['header_address'] ?? $company['address'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เบอร์โทรศัพท์</label>
                    <input type="text" id="header_phone" value="<?= htmlspecialchars($gr_data['header_phone'] ?? $company['phone'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" id="header_tax_id" value="<?= htmlspecialchars($gr_data['header_tax_id'] ?? $company['tax_id'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">โลโก้หัวเอกสาร</label>
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden bg-white">
                            <img id="header_logo_preview" src="<?= $gr_data['header_logo'] ?? ($company['logo'] ? '../'.$company['logo'] : '') ?>" class="<?= (empty($gr_data['header_logo']) && empty($company['logo'])) ? 'hidden' : '' ?> w-full h-full object-contain">
                            <svg id="header_logo_placeholder" class="<?= (!empty($gr_data['header_logo']) || !empty($company['logo'])) ? 'hidden' : '' ?> w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="file" id="header_logo_input" accept="image/*" onchange="previewHeaderLogo()" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลขที่ใบรับสินค้า</label>
                <input type="text" id="doc_number" value="<?= $gr_data['doc_number'] ?? '' ?>" placeholder="เช่น RR<?= date('ymd') ?>001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่รับสินค้า</label>
                <input type="date" id="doc_date" value="<?= $gr_data['doc_date'] ?? date('Y-m-d') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
        </div>

        <div class="border-t pt-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">ข้อมูลผู้ขาย/ผู้ส่งสินค้า</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัส</label>
                    <input type="text" id="vendor_code" value="<?= htmlspecialchars($gr_data['vendor_code'] ?? '') ?>" placeholder="เช่น RY-001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อร้าน / บริษัท</label>
                    <input type="text" id="vendor_name" value="<?= htmlspecialchars($gr_data['vendor_name'] ?? '') ?>" placeholder="ชื่อผู้ขาย" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">ที่อยู่</label>
                <textarea id="vendor_address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($gr_data['vendor_address'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">โทรศัพท์</label>
                    <input type="text" id="vendor_phone" value="<?= htmlspecialchars($gr_data['vendor_phone'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                    <input type="email" id="vendor_email" value="<?= htmlspecialchars($gr_data['vendor_email'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" id="vendor_tax_id" value="<?= htmlspecialchars($gr_data['vendor_tax_id'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เงื่อนไขการชำระเงิน</label>
                <input type="text" id="payment_terms" value="<?= htmlspecialchars($gr_data['payment_terms'] ?? 'เงินสด/โอนเงิน') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพท้ายเอกสาร (QR Code)</label>
                <input type="file" id="qr_code" accept="image/*" onchange="previewQRCode()" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                <img id="qr_preview" src="<?= $gr_data['qr_code_image'] ?? '' ?>" class="mt-2 <?= empty($gr_data['qr_code_image']) ? 'hidden' : '' ?> max-w-xs max-h-32 object-contain border rounded">
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
                <input type="checkbox" id="vat_enabled" <?= ($gr_data['vat_enabled'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 text-emerald-600 rounded">
                <label for="vat_enabled" class="text-sm font-medium text-gray-700">คิด VAT 7%</label>
            </div>

            <div class="flex items-center gap-4 mb-4">
                <input type="radio" name="vat_type" value="exclude" id="vat_exclude" <?= ($gr_data['vat_type'] ?? 'exclude') == 'exclude' ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600">
                <label for="vat_exclude" class="text-sm text-gray-700">ราคายังไม่รวม VAT</label>
                
                <input type="radio" name="vat_type" value="include" id="vat_include" <?= ($gr_data['vat_type'] ?? 'exclude') == 'include' ? 'checked' : '' ?> class="w-4 h-4 text-emerald-600">
                <label for="vat_include" class="text-sm text-gray-700">ราคารวม VAT แล้ว</label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ</label>
                <textarea id="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($gr_data['notes'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เงื่อนไข</label>
                <textarea id="conditions" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($gr_data['conditions'] ?? "* ไลน์อีเมลล์ ฟอร์มใบรับสินค้า มาที่ Email: rongyanghome@gmail.com, หรือไลน์ OA= @ttgoldenteak\n* กรุณาชำระเงินโดยการโอนเข้าบัญชี ธ.กสิกรไทย สาขาแพร่ ออมทรัพย์ ชื่อบัญชี บจก.บ้านสักทองร้องแหย่ง เลขที่ 1971292055") ?></textarea>
            </div>
        </div>

        <div class="border-t pt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">ลายเซ็น</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้รับสินค้า</label>
                    <div class="flex gap-2">
                        <input type="file" id="signature1" accept="image/*" onchange="previewSignature(1)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        <button type="button" onclick="openSignaturePad(1)" class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg hover:bg-emerald-200 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-pen-nib"></i> เซ็นชื่อ
                        </button>
                        <button type="button" onclick="clearSignature(1)" class="bg-rose-50 text-rose-600 px-3 py-1 rounded-lg hover:bg-rose-100 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
                    <img id="sig1_preview" src="<?= $gr_data['signature1'] ?? '' ?>" class="signature-preview mt-2 <?= empty($gr_data['signature1']) ? 'hidden' : '' ?> border rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้อนุมัติ</label>
                    <div class="flex gap-2">
                        <input type="file" id="signature2" accept="image/*" onchange="previewSignature(2)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        <button type="button" onclick="openSignaturePad(2)" class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg hover:bg-emerald-200 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-pen-nib"></i> เซ็นชื่อ
                        </button>
                        <button type="button" onclick="clearSignature(2)" class="bg-rose-50 text-rose-600 px-3 py-1 rounded-lg hover:bg-rose-100 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
                    <img id="sig2_preview" src="<?= $gr_data['signature2'] ?? '' ?>" class="signature-preview mt-2 <?= empty($gr_data['signature2']) ? 'hidden' : '' ?> border rounded">
                </div>
            </div>
        </div>

        <div class="flex gap-4 mt-8">
            <button onclick="saveGR()" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                💾 บันทึก
            </button>
            <button onclick="generatePreview()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-all">
                👁️ ดูตัวอย่าง / พิมพ์
            </button>
        </div>
    </div>
</div>

<!-- Print Preview Section -->
<div id="print-area" class="print-container bg-white shadow-2xl mx-auto my-10 p-10 hidden">
    <!-- Content will be generated by JS -->
</div>

<script>
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

let itemCount = 0;
const existingItems = <?= json_encode($gr_data['items'] ?? '[]') ?>;

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
                    <button onclick="removeItem(${itemCount})" class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm">ลบ</button>
                </div>
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

function saveGR() {
    const docNumber = $('#doc_number').val().trim();
    const docDate = $('#doc_date').val();
    const vendorName = $('#vendor_name').val().trim();
    
    if (!docNumber || !docDate || !vendorName) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกเลขที่, วันที่ และชื่อผู้ขาย', 'warning');
        return;
    }
    
    const items = [];
    $('.item-row').each(function() {
        const name = $(this).find('.item-name').val().trim();
        const qty = $(this).find('.item-qty').val();
        const unit = $(this).find('.item-unit').val().trim();
        const price = $(this).find('.item-price').val();
        const discount = $(this).find('.item-discount').val();
        if (name) {
            items.push({ name, qty, unit, price, discount });
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
            vatAmount = grandTotal - (grandTotal / 1.07);
        }
    }
    
    const data = {
        action: 'save',
        id: $('#gr_id').val(),
        doc_number: docNumber,
        doc_date: docDate,
        vendor_code: $('#vendor_code').val(),
        vendor_name: vendorName,
        vendor_address: $('#vendor_address').val(),
        vendor_phone: $('#vendor_phone').val(),
        vendor_email: $('#vendor_email').val(),
        vendor_tax_id: $('#vendor_tax_id').val(),
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
        qr_code_image: $('#qr_preview').attr('src') || ''
    };
    
    Swal.fire({
        title: 'กำลังบันทึก...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: 'goods_receipt_action.php',
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire('สำเร็จ', response.message, 'success').then(() => {
                    if (!$('#gr_id').val()) {
                        window.location.href = 'goods_receipt_form.php?id=' + response.id;
                    }
                });
            } else {
                Swal.fire('ผิดพลาด', response.message, 'error');
            }
        }
    });
}

function generatePreview() {
    const issuer = $('#issuer_company_id option:selected');
    const docNumber = $('#doc_number').val();
    const docDate = new Date($('#doc_date').val()).toLocaleDateString('th-TH');
    
    const totals = calculateTotal();
    const vatEnabled = $('#vat_enabled').is(':checked');
    const vatType = $('input[name="vat_type"]:checked').val();
    
    let subtotal = totals.subtotal;
    let discount = totals.totalDiscount;
    let netBeforeVat = subtotal - discount;
    let vatAmount = 0;
    let grandTotal = netBeforeVat;
    
    if (vatEnabled) {
        if (vatType === 'exclude') {
            vatAmount = netBeforeVat * 0.07;
            grandTotal = netBeforeVat + vatAmount;
        } else {
            vatAmount = netBeforeVat - (netBeforeVat / 1.07);
            netBeforeVat = netBeforeVat - vatAmount;
        }
    }

    let itemsHtml = '';
    $('.item-row').each(function(index) {
        const name = $(this).find('.item-name').val();
        const qty = parseFloat($(this).find('.item-qty').val()) || 0;
        const unit = $(this).find('.item-unit').val();
        const price = parseFloat($(this).find('.item-price').val()) || 0;
        const disc = parseFloat($(this).find('.item-discount').val()) || 0;
        const total = (qty * price) - disc;
        
        itemsHtml += `
            <tr>
                <td style="text-align: center;">${index + 1}</td>
                <td>${name}</td>
                <td style="text-align: right;">${qty.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                <td style="text-align: center;">${unit}</td>
                <td style="text-align: right;">${price > 0 ? price.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                <td style="text-align: right;">${disc > 0 ? disc.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                <td style="text-align: right;">${total.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
            </tr>
        `;
    });

    const minRows = 10;
    const currentRows = $('.item-row').length;
    for (let i = currentRows; i < minRows; i++) {
        itemsHtml += `<tr><td style="height: 25px;"></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>`;
    }

    const amountWords = ThaiBaht(grandTotal);

    const html = `
        <div class="gr-header">
            <img src="${$('#header_logo_preview').attr('src')}" class="gr-logo" onerror="this.style.display='none'">
            <div class="gr-company-info">
                <div style="font-weight: bold; font-size: 18px;">${$('#header_name').val()}</div>
                <div style="font-size: 14px;">${$('#header_address').val()}</div>
                <div style="font-size: 14px;">โทร. ${$('#header_phone').val()}</div>
                <div style="font-size: 14px;">เลขที่ประจำตัวผู้เสียภาษี ${$('#header_tax_id').val()}</div>
            </div>
            <div class="gr-title">ใบรับสินค้า</div>
        </div>

        <div class="gr-info-grid">
            <div style="font-size: 14px;">
                <div>รหัส : ${$('#vendor_code').val()}</div>
                <div>ชื่อ : ${$('#vendor_name').val()}</div>
                <div>ที่อยู่ : ${$('#vendor_address').val()}</div>
                <br>
                <div style="display: flex; gap: 20px;">
                    <span>โทรศัพท์ : ${$('#vendor_phone').val()}</span>
                    <span>อีเมล : ${$('#vendor_email').val()}</span>
                </div>
                <div>รหัสผู้เสียภาษี : ${$('#vendor_tax_id').val()}</div>
            </div>
            <div style="text-align: right; font-size: 14px;">
                <div>เลขที่ : ${docNumber}</div>
                <div>วันที่รับสินค้า : ${docDate}</div>
                <br>
                <div>เงื่อนไขการชำระ : ${$('#payment_terms').val()}</div>
            </div>
        </div>

        <table class="gr-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ลำดับ</th>
                    <th>รายการ</th>
                    <th style="width: 80px;">จำนวน</th>
                    <th style="width: 80px;">หน่วยนับ</th>
                    <th style="width: 100px;">ราคา</th>
                    <th style="width: 80px;">ส่วนลด</th>
                    <th style="width: 120px;">รวมเป็นเงิน</th>
                </tr>
            </thead>
            <tbody>
                ${itemsHtml}
            </tbody>
        </table>

        <div class="gr-footer">
            <div class="gr-footer-left">
                <div style="font-size: 12px;">หมายเหตุ : ${$('#notes').val()}</div>
                <div style="margin-top: 20px; font-size: 12px; font-weight: bold;">เงื่อนไข :</div>
                <div style="font-size: 11px; white-space: pre-line;">${$('#conditions').val()}</div>
            </div>
            <div class="gr-footer-right">
                <table>
                    <tr>
                        <td style="font-weight: bold;">มูลค่ารวมก่อนเสียภาษี</td>
                        <td style="text-align: right;">${netBeforeVat.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">ภาษีมูลค่าเพิ่ม(VAT)</td>
                        <td style="text-align: right;">${vatAmount.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">ส่วนลด</td>
                        <td style="text-align: right;">${discount > 0 ? discount.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-'}</td>
                    </tr>
                    <tr style="background-color: #76b852;">
                        <td style="font-weight: bold;">ยอดเงินสุทธิ</td>
                        <td style="text-align: right; font-weight: bold;">${grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="gr-amount-words">( ${amountWords} )</div>

        <div class="gr-signatures">
            <div style="width: 150px;">
                ${$('#qr_preview').attr('src') ? `<img src="${$('#qr_preview').attr('src')}" style="width: 100%; object-fit: contain;">` : ''}
            </div>
            <div class="signature-box">
                <div>ผู้รับสินค้า</div>
                <div class="signature-line">
                    ${$('#sig1_preview').attr('src') ? `<img src="${$('#sig1_preview').attr('src')}" class="signature-preview">` : ''}
                </div>
                <div style="font-size: 12px;">......................................................</div>
            </div>
            <div class="signature-box">
                <div>ผู้อนุมัติ</div>
                <div class="signature-line">
                    ${$('#sig2_preview').attr('src') ? `<img src="${$('#sig2_preview').attr('src')}" class="signature-preview">` : ''}
                </div>
                <div style="font-size: 12px;">......................................................</div>
            </div>
        </div>
    `;

    $('#print-area').html(html).removeClass('hidden');
    
    $('html, body').animate({
        scrollTop: $("#print-area").offset().top
    }, 500);

    if ($('#print-btn-container').length === 0) {
        $('<div id="print-btn-container" class="max-w-5xl mx-auto mt-4 mb-10 flex gap-4 no-print">' +
          '<button onclick="window.print()" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition-all">🖨️ พิมพ์เอกสาร (A4)</button>' +
          '<button onclick="exportPDF()" class="flex-1 bg-red-600 text-white py-3 rounded-lg font-bold shadow-lg hover:bg-red-700 transition-all">📄 บันทึกเป็น PDF</button>' +
          '</div>').insertAfter('#print-area');
    }
}

function ThaiBaht(number) {
    if (isNaN(number)) return "";
    number = number.toFixed(2);
    const [baht, satang] = number.split('.');
    
    const convert = (num) => {
        const thaiNum = ["ศูนย์", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
        const thaiUnit = ["", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน"];
        let res = "";
        for (let i = 0; i < num.length; i++) {
            let digit = parseInt(num[i]);
            let unit = num.length - i - 1;
            if (digit !== 0) {
                if (unit === 1 && digit === 2) res += "ยี่";
                else if (unit === 1 && digit === 1) res += "";
                else if (unit === 0 && digit === 1 && num.length > 1) res += "เอ็ด";
                else res += thaiNum[digit];
                res += thaiUnit[unit % 6];
                if (unit >= 6 && unit % 6 === 0) res += "ล้าน";
            }
        }
        return res;
    };

    let result = "";
    if (parseInt(baht) === 0) result = "ศูนย์";
    else result = convert(baht);
    result += "บาท";

    if (parseInt(satang) === 0) result += "ถ้วน";
    else result += convert(satang) + "สตางค์";

    return result;
}

function exportPDF() {
    const element = document.getElementById('print-area');
    const opt = {
        margin: 0,
        filename: `GR_${$('#doc_number').val()}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
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

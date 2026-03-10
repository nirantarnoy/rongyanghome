<?php
require '../auth_check.php';
require '../config.php';
require_once '../file_helper.php';
require '../thai_baht_helper.php';

$company_id = $_SESSION['company_id'];
$edit_id = $_GET['id'] ?? null;
$po_data = null;

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

// Load PO data if editing
if ($edit_id) {
    $sql = "SELECT * FROM purchase_orders WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $edit_id, $company_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $po_data = mysqli_fetch_assoc($result);
    if ($po_data) {
        $po_data['header_logo'] = getFullPath($po_data['header_logo']);
        $po_data['qr_code_image'] = getFullPath($po_data['qr_code_image'] ?? $po_data['qr_code'] ?? '');
        $po_data['signature1'] = getFullPath($po_data['signature1']);
        $po_data['signature2'] = getFullPath($po_data['signature2']);
        processItemsPaths($po_data['items']);
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบสั่งซื้อ - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        
        /* Print Styles to match the image */
        .po-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            gap: 20px;
        }
        .po-logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
        }
        .po-company-info {
            text-align: left;
            flex: 1;
            padding-top: 5px;
        }
        .po-title {
            font-size: 28px;
            font-weight: bold;
            text-align: right;
            color: #b91c1c;
            line-height: 1;
        }
        .po-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 25px;
        }
        .po-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            table-layout: fixed;
        }
        .po-table th {
            background-color: #f3f4f6;
            color: #1f2937;
            border: 1px solid #1f2937;
            padding: 10px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
        .po-table td {
            border: 1px solid #1f2937;
            padding: 8px;
            vertical-align: middle;
            font-size: 14px;
            word-wrap: break-word;
        }
        .po-footer {
            display: grid;
            grid-template-columns: 1fr 250px;
            border: 1px solid #1f2937;
            margin-top: -1px;
        }
        .po-footer-left {
            padding: 12px;
            border-right: 1px solid #1f2937;
        }
        .po-footer-right table {
            width: 100%;
            border-collapse: collapse;
        }
        .po-footer-right td {
            padding: 8px 12px;
            border-bottom: 1px solid #1f2937;
            font-size: 14px;
        }
        .po-amount-words {
            text-align: center;
            padding: 10px;
            border: 1px solid #1f2937;
            border-top: none;
            font-weight: bold;
            background-color: #f9fafb;
            font-size: 14px;
        }
        .po-signatures {
            display: grid;
            grid-template-columns: 150px 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
            align-items: flex-end;
        }
        .signature-box {
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px dashed #374151;
            margin: 25px auto 5px;
            width: 80%;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .signature-preview {
            max-width: 140px;
            max-height: 70px;
            object-fit: contain;
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="no-print bg-gradient-to-r from-green-600 to-green-700 text-white p-4 shadow-lg">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">🛒 <?= $edit_id ? 'แก้ไข' : 'สร้าง' ?>ใบสั่งซื้อ</h1>
        <a href="index.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">← กลับ</a>
    </div>
</div>

<div class="max-w-5xl mx-auto p-6 no-print">
    <!-- Form Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">ข้อมูลใบสั่งซื้อ</h2>
        
        <input type="hidden" id="po_id" value="<?= $edit_id ?? '' ?>">
        
        <div class="mb-6 bg-green-50 p-4 rounded-xl border border-green-100">
            <label class="block text-sm font-bold text-green-800 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-7h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                ข้อมูลหัวเอกสาร (สามารถแก้ไขได้)
            </label>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">เลือกบริษัทต้นแบบ</label>
                    <select id="issuer_company_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" onchange="loadCompanyTemplate()">
                        <option value="">-- กำหนดเอง --</option>
                        <?php foreach ($all_companies as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                data-name="<?= htmlspecialchars($c['company_name']) ?>" 
                                data-address="<?= htmlspecialchars($c['address']) ?>" 
                                data-phone="<?= htmlspecialchars($c['phone']) ?>" 
                                data-taxid="<?= htmlspecialchars($c['tax_id']) ?>"
                                data-logo="<?= htmlspecialchars($c['logo'] ?? '') ?>"
                                <?= ($po_data['issuer_company_id'] ?? $company_id) == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['company_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ชื่อบริษัทในหัวเอกสาร</label>
                    <input type="text" id="header_name" value="<?= htmlspecialchars($po_data['header_name'] ?? $company['company_name'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">ที่อยู่ในหัวเอกสาร</label>
                    <textarea id="header_address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"><?= htmlspecialchars($po_data['header_address'] ?? $company['address'] ?? '') ?></textarea>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เบอร์โทรศัพท์</label>
                    <input type="text" id="header_phone" value="<?= htmlspecialchars($po_data['header_phone'] ?? $company['phone'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" id="header_tax_id" value="<?= htmlspecialchars($po_data['header_tax_id'] ?? $company['tax_id'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">โลโก้หัวเอกสาร</label>
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden bg-white">
                            <img id="header_logo_preview" src="<?= $po_data['header_logo'] ?? ($company['logo'] ? '../'.$company['logo'] : '') ?>" class="<?= (empty($po_data['header_logo']) && empty($company['logo'])) ? 'hidden' : '' ?> w-full h-full object-contain">
                            <svg id="header_logo_placeholder" class="<?= (!empty($po_data['header_logo']) || !empty($company['logo'])) ? 'hidden' : '' ?> w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="file" id="header_logo_input" accept="image/*" onchange="previewHeaderLogo()" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เลขที่ใบสั่งซื้อ</label>
                <input type="text" id="doc_number" value="<?= $po_data['doc_number'] ?? '' ?>" placeholder="เช่น PO<?= date('ymd') ?>001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่สั่งซื้อ</label>
                <input type="date" id="doc_date" value="<?= $po_data['doc_date'] ?? date('Y-m-d') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
            </div>
        </div>

        <div class="border-t pt-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">ข้อมูลผู้ขาย (Vendor)</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผู้ขาย</label>
                    <input type="text" id="vendor_code" value="<?= htmlspecialchars($po_data['vendor_code'] ?? '') ?>" placeholder="เช่น RY-001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อร้าน / บริษัท</label>
                    <input type="text" id="vendor_name" value="<?= htmlspecialchars($po_data['vendor_name'] ?? '') ?>" placeholder="ชื่อผู้ขาย" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">ที่อยู่</label>
                <textarea id="vendor_address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"><?= htmlspecialchars($po_data['vendor_address'] ?? '') ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">โทรศัพท์</label>
                    <input type="text" id="vendor_phone" value="<?= htmlspecialchars($po_data['vendor_phone'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                    <input type="email" id="vendor_email" value="<?= htmlspecialchars($po_data['vendor_email'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">เลขประจำตัวผู้เสียภาษี</label>
                    <input type="text" id="vendor_tax_id" value="<?= htmlspecialchars($po_data['vendor_tax_id'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
            </div>
        </div>

        <div class="mb-6 bg-green-50 p-4 rounded-xl border border-green-100">
            <div class="flex justify-between items-center mb-3">
                <label class="block text-sm font-bold text-green-800 flex items-center gap-2">
                    <i class="fas fa-file-invoice-dollar"></i> เงื่อนไขการชำระเงิน
                </label>
                <div class="flex items-center gap-2">
                    <select id="payment_terms_template" onchange="loadPaymentTermsTemplate()" class="text-xs px-2 py-1 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-500 outline-none min-w-[150px]">
                        <option value="">-- เลือกเทมเพลต --</option>
                    </select>
                    <button onclick="manageTemplates('payment_terms')" type="button" class="text-xs bg-white text-green-600 border border-green-200 px-2 py-1 rounded-lg hover:bg-green-100 transition-all">
                        ⚙️ จัดการ
                    </button>
                </div>
            </div>
            <textarea id="payment_terms" rows="2" placeholder="ระบุเงื่อนไขการชำระเงิน" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none bg-white text-sm"><?= htmlspecialchars($po_data['payment_terms'] ?? 'เงินสด/โอนเงิน') ?></textarea>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">รูปภาพท้ายเอกสาร (QR Code)</label>
            <input type="file" id="qr_code" accept="image/*" onchange="previewQRCode()" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
            <img id="qr_preview" src="<?= $po_data['qr_code_image'] ?? '' ?>" class="mt-2 <?= empty($po_data['qr_code_image']) ? 'hidden' : '' ?> max-w-xs max-h-32 object-contain border rounded">
        </div>

        <div class="border-t pt-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">รายการสินค้า</h3>
                <button onclick="addItem()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2">
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
                <input type="checkbox" id="vat_enabled" <?= ($po_data['vat_enabled'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 text-green-600 rounded">
                <label for="vat_enabled" class="text-sm font-medium text-gray-700">คิด VAT 7%</label>
            </div>

            <div class="flex items-center gap-4 mb-4">
                <input type="radio" name="vat_type" value="exclude" id="vat_exclude" <?= ($po_data['vat_type'] ?? 'exclude') == 'exclude' ? 'checked' : '' ?> class="w-4 h-4 text-green-600">
                <label for="vat_exclude" class="text-sm text-gray-700">ราคายังไม่รวม VAT</label>
                
                <input type="radio" name="vat_type" value="include" id="vat_include" <?= ($po_data['vat_type'] ?? 'exclude') == 'include' ? 'checked' : '' ?> class="w-4 h-4 text-green-600">
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
                <textarea id="notes" rows="4" placeholder="บันทึกหมายเหตุเพิ่มเติม (ถ้ามี)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none text-sm bg-white"><?= htmlspecialchars($po_data['notes'] ?? '') ?></textarea>
            </div>
            
            <div class="bg-green-50 p-4 rounded-xl border border-green-100">
                <div class="flex justify-between items-center mb-3">
                    <label class="text-sm font-bold text-green-800">เงื่อนไข (Conditions)</label>
                    <div class="flex items-center gap-2">
                        <select id="conditions_template" onchange="loadConditionsTemplate()" class="text-xs px-2 py-1 border border-green-200 rounded-lg outline-none min-w-[150px]">
                            <option value="">-- เลือกเทมเพลต --</option>
                        </select>
                        <button onclick="manageTemplates('conditions')" type="button" class="text-xs bg-white text-green-600 border border-green-200 px-2 py-1 rounded-lg hover:bg-green-100 transition-all">
                            ⚙️
                        </button>
                    </div>
                </div>
                <textarea id="conditions" rows="4" placeholder="ระบุเงื่อนไขเพิ่มเติมที่ส่วนท้ายเอกสาร" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none text-sm bg-white"><?= htmlspecialchars($po_data['conditions'] ?? '* ไลน์อีเมลล์ ฟอร์มใบสั่งซื้อ มาที่ Email: rongyanghome@gmail.com, หรือไลน์ OA= @ttgoldenteak') ?></textarea>
            </div>
        </div>

        <div class="border-t pt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">ลายเซ็น</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้สั่งซื้อ</label>
                    <div class="flex gap-2">
                        <input type="file" id="signature1" accept="image/*" onchange="previewSignature(1)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                        <button type="button" onclick="openSignaturePad(1)" class="bg-green-100 text-green-700 px-3 py-1 rounded-lg hover:bg-green-200 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-pen-nib"></i> เซ็นชื่อ
                        </button>
                        <button type="button" onclick="clearSignature(1)" class="bg-rose-50 text-rose-600 px-3 py-1 rounded-lg hover:bg-rose-100 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
                    <img id="sig1_preview" src="<?= $po_data['signature1'] ?? '' ?>" class="signature-preview mt-2 <?= empty($po_data['signature1']) ? 'hidden' : '' ?> border rounded">
                    <input type="text" id="signer_name1" value="<?= htmlspecialchars($po_data['signer_name1'] ?? '') ?>" placeholder="ชื่อผู้สั่งซื้อ (ถ้ามี)" class="w-full px-3 py-2 mt-2 border border-green-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ลายเซ็นผู้อนุมัติ</label>
                    <div class="flex gap-2">
                        <input type="file" id="signature2" accept="image/*" onchange="previewSignature(2)" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                        <button type="button" onclick="openSignaturePad(2)" class="bg-green-100 text-green-700 px-3 py-1 rounded-lg hover:bg-green-200 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-pen-nib"></i> เซ็นชื่อ
                        </button>
                        <button type="button" onclick="clearSignature(2)" class="bg-rose-50 text-rose-600 px-3 py-1 rounded-lg hover:bg-rose-100 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
                    <img id="sig2_preview" src="<?= $po_data['signature2'] ?? '' ?>" class="signature-preview mt-2 <?= empty($po_data['signature2']) ? 'hidden' : '' ?> border rounded">
                    <input type="text" id="signer_name2" value="<?= htmlspecialchars($po_data['signer_name2'] ?? '') ?>" placeholder="ชื่อผู้อนุมัติ (ถ้ามี)" class="w-full px-3 py-2 mt-2 border border-green-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-10 pt-8 border-t no-print">
            <button onclick="savePO()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> บันทึก
            </button>
            <button onclick="generatePreview()" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-3 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-eye"></i> ดูตัวอย่าง
            </button>
            
            <?php if ($edit_id): ?>
            <button onclick="receiveGoods(<?= $edit_id ?>)" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2">
                <i class="fas fa-box-open"></i> รับสินค้า
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Print Area -->
<div id="print-area" style="display: none;"></div>

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
const existingItems = <?= json_encode($po_data['items'] ?? '[]') ?>;

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

function savePO() {
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
            vatAmount = grandTotal * 7 / 107;
        }
    }
    
    const data = {
        action: 'save',
        id: $('#po_id').val(),
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
        url: 'purchase_order_action.php',
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire('สำเร็จ', response.message, 'success').then(() => {
                    if (!$('#po_id').val()) {
                        window.location.href = 'purchase_order_form.php?id=' + response.id;
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
            grandTotal = netBeforeVat;
            vatAmount = netBeforeVat * 7 / 107;
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

    // Removed empty rows loop as per user request to show only actual data

    const amountWords = getThaiBaht(grandTotal);

    const html = `
        <div class="po-header">
            ${$('#header_logo_preview').attr('src') ? `<img src="${$('#header_logo_preview').attr('src')}" class="po-logo">` : `<img src="../assets/logo/logo.png" class="po-logo" onerror="this.style.display='none'">`}
            <div class="po-company-info">
                <div style="font-weight: bold; font-size: 18px;">${$('#header_name').val()}</div>
                <div style="font-size: 14px;">${$('#header_address').val()}</div>
                <div style="font-size: 14px;">โทร. ${$('#header_phone').val()}</div>
                <div style="font-size: 14px;">เลขที่ประจำตัวผู้เสียภาษี ${$('#header_tax_id').val()}</div>
            </div>
            <div class="po-title">ใบสั่งซื้อ</div>
        </div>

        <div class="po-info-grid">
            <div style="font-size: 14px;">
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
                <div>วันที่สั่งซื้อ : ${docDate}</div>
                <br>
                <div>เงื่อนไขการชำระ : ${$('#payment_terms_template option:selected').val() ? $('#payment_terms_template option:selected').text() : ($('#payment_terms').val() || '-')}</div>
            </div>
        </div>

        <table class="po-table">
            <thead>
                <tr>
                    <th style="width: 50px; white-space: nowrap;">ลำดับ</th>
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

        <div class="po-footer">
            <div class="po-footer-left">
                <div style="font-size: 12px;">หมายเหตุ : ${$('#notes').val()}</div>
            </div>
            <div class="po-footer-right">
                <table>
                    <tr>
                        <td style="font-weight: bold;">มูลค่ารวมก่อนเสียภาษี</td>
                        <td style="text-align: right;">${(grandTotal - vatAmount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">ภาษีมูลค่าเพิ่ม(VAT)</td>
                        <td style="text-align: right;">${vatAmount.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>

                    <tr style="background-color: #76b852;">
                        <td style="font-weight: bold;">ยอดเงินสุทธิ</td>
                        <td style="text-align: right; font-weight: bold;">${grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="po-amount-words">( ${amountWords} )</div>
        <div style="margin-top: 10px; font-size: 13px;">
            <p style="margin: 2px 0; font-weight: bold; text-decoration: underline;">เงื่อนไข :</p>
            <div style="white-space: pre-line; line-height: 1.5;">${$('#conditions').val()}</div>
        </div>

        <div class="po-signatures">
            <div style="width: 150px;">
                ${$('#qr_preview').attr('src') ? `<img src="${$('#qr_preview').attr('src')}" style="width: 100%; object-fit: contain;">` : ''}
            </div>
            <div class="signature-box">
                <div>ผู้สั่งซื้อ</div>
                <div class="signature-line">
                    ${$('#sig1_preview').attr('src') ? `<img src="${$('#sig1_preview').attr('src')}" class="signature-preview">` : ''}
                </div>
                <div style="font-size: 12px;">${$('#signer_name1').val()}</div>
            </div>
            <div class="signature-box">
                <div>ผู้อนุมัติ</div>
                <div class="signature-line">
                    ${$('#sig2_preview').attr('src') ? `<img src="${$('#sig2_preview').attr('src')}" class="signature-preview">` : ''}
                </div>
                <div style="font-size: 12px;">${$('#signer_name2').val()}</div>
            </div>
        </div>
    `;
    $('#print-area').html(html);

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
        showCloseButton: true
    });
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

function getFullPath(path) {
    if (!path) return '';
    if (path.indexOf('data:image') === 0) return path;
    if (path.indexOf('http') === 0) return path;
    if (path.indexOf('../') === 0) return path;
    return '../' + path;
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

function getThaiBaht(amount) {
    // We'll call a PHP script via AJAX to get the Thai words, or use a JS implementation
    // For simplicity, let's use a small JS function for Thai Baht conversion
    return ThaiBaht(amount);
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

function receiveGoods(id) {
    window.location.href = 'goods_receipt_form.php?po_id=' + id;
}

function printDoc() { 
    generatePreview(); 
    // Close the Swal modal before printing
    Swal.close(); 
    setTimeout(() => { 
        window.print(); 
    }, 800); 
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
            filename: `PO_${$('#doc_number').val() || 'document'}.pdf`,
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
                    <button onclick="addNewTemplate('${type}')" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
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

function editTemplate(id, type, name, content) {
    let typeLabel = '';
    switch(type) {
        case 'payment_terms': typeLabel = 'เงื่อนไขการชำระเงิน'; break;
        case 'notes': typeLabel = 'หมายเหตุ'; break;
        case 'conditions': typeLabel = 'เงื่อนไข'; break;
    }
    
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
</script>

</body>
</html>

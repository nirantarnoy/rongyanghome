<?php
require '../auth_check.php';
require '../config.php';
require '../thai_baht_helper.php';
require_once '../file_helper.php';

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
    
    if ($result && $doc_data = mysqli_fetch_assoc($result)) {
        $type = $doc_data['type'];
        // Resolve paths
        $doc_data['header_logo'] = getFullPath($doc_data['header_logo']);
        $doc_data['qr_code_image'] = getFullPath($doc_data['qr_code_image']);
        $doc_data['signature1'] = getFullPath($doc_data['signature1']);
        $doc_data['signature2'] = getFullPath($doc_data['signature2']);
        processItemsPaths($doc_data['items']);
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
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
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

        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            gap: 20px;
        }
        .doc-logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
        }
        .doc-company-info {
            text-align: left;
            flex: 1;
            padding-top: 5px;
        }
        .doc-title {
            font-size: 28px;
            font-weight: bold;
            text-align: right;
            color: #b91c1c;
            line-height: 1;
        }
        .doc-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 25px;
        }
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            table-layout: fixed;
        }
        .doc-table th {
            background-color: #f3f4f6;
            color: #1f2937;
            border: 1px solid #1f2937;
            padding: 10px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }
        .doc-table td {
            border: 1px solid #1f2937;
            padding: 8px;
            vertical-align: middle;
            font-size: 14px;
            word-wrap: break-word;
        }
        .doc-footer {
            display: grid;
            grid-template-columns: 1fr 250px;
            border: 1px solid #1f2937;
            margin-top: -1px;
        }
        .doc-footer-left {
            padding: 12px;
            border-right: 1px solid #1f2937;
        }
        .doc-footer-right table {
            width: 100%;
            border-collapse: collapse;
        }
        .doc-footer-right td {
            padding: 8px 12px;
            border-bottom: 1px solid #1f2937;
            font-size: 14px;
        }
        .doc-footer-right tr:last-child td {
            border-bottom: none;
        }
        .doc-amount-words {
            text-align: center;
            padding: 10px;
            border: 1px solid #1f2937;
            border-top: none;
            font-weight: bold;
            background-color: #f9fafb;
            font-size: 14px;
        }
        .doc-signatures {
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
            margin: 10px auto 5px;
            width: 80%;
            min-height: 60px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        .signature-preview {
            max-width: 140px;
            max-height: 70px;
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
        .btn-issue-receipt {
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }
        .btn-issue-receipt:hover {
            background-color: #2563eb;
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
            <textarea id="payment_terms" rows="2" placeholder="ระบุเงื่อนไขการชำระเงิน" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none bg-white text-sm"><?= htmlspecialchars($doc_data['payment_terms'] ?? 'เงินสด/โอนเงิน') ?></textarea>
        </div>

        <div class="mb-8">
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
                <textarea id="notes" rows="4" placeholder="บันทึกหมายเหตุเพิ่มเติม (ถ้ามี)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none text-sm bg-white"><?= htmlspecialchars($doc_data['notes'] ?? '') ?></textarea>
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
                <textarea id="conditions" rows="4" placeholder="ระบุเงื่อนไขเพิ่มเติมที่ส่วนท้ายเอกสาร" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none text-sm bg-white"><?= htmlspecialchars($doc_data['conditions'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="border-t pt-8 mb-10">
            <h3 class="text-lg font-bold text-gray-800 mb-6">ลายเซ็น</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">ผู้รับเงิน</label>
                    <div class="flex gap-2">
                        <input type="file" id="signature1" accept="image/*" onchange="previewSignature(1)" class="w-full text-sm text-gray-500 mb-2">
                        <button type="button" onclick="openSignaturePad(1)" class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg hover:bg-emerald-200 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-pen-nib"></i> เซ็นชื่อ
                        </button>
                        <button type="button" onclick="clearSignature(1)" class="bg-rose-50 text-rose-600 px-3 py-1 rounded-lg hover:bg-rose-100 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
                    <img id="sig1_preview" src="<?= $doc_data['signature1'] ?? '' ?>" class="signature-preview <?= empty($doc_data['signature1']) ? 'hidden' : '' ?> border rounded-xl bg-gray-50">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">ผู้อนุมัติ</label>
                    <div class="flex gap-2">
                        <input type="file" id="signature2" accept="image/*" onchange="previewSignature(2)" class="w-full text-sm text-gray-500 mb-2">
                        <button type="button" onclick="openSignaturePad(2)" class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg hover:bg-emerald-200 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-pen-nib"></i> เซ็นชื่อ
                        </button>
                        <button type="button" onclick="clearSignature(2)" class="bg-rose-50 text-rose-600 px-3 py-1 rounded-lg hover:bg-rose-100 transition-all text-xs flex items-center gap-1 h-fit">
                            <i class="fas fa-trash-alt"></i> ลบ
                        </button>
                    </div>
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

        <?php if ($edit_id): ?>
        <div class="mt-4 flex justify-center no-print border-t pt-8 gap-4">
            <?php if ($type == 'invoice'): ?>
                <button onclick="issueTaxInvoice()" class="btn-issue-tax">
                    <i class="fas fa-file-invoice-dollar text-xl"></i>
                    ออกใบกำกับภาษี
                </button>
            <?php endif; ?>
            
            <button onclick="issueReceipt()" class="btn-issue-receipt">
                <i class="fas fa-receipt text-xl"></i>
                ออกใบเสร็จรับเงิน
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print Area -->
<div id="print-area" style="display: none;"></div>

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
    
    // Load templates
    loadTemplates();
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
        compressImage(input.files[0], 500, 500, 0.7).then(compressedBase64 => {
            preview.src = compressedBase64;
            preview.classList.remove('hidden');
        });
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
    // Removed empty rows loop as per user request

    const title = $('#doc_type').val() === 'tax_invoice' ? 'ใบกำกับภาษี' : 'ใบแจ้งหนี้';
    const amountWords = ThaiBaht(grand);

    const html = `
        <div class="doc-header">
            <img src="${$('#header_logo_preview').attr('src') || '../assets/logo/logo.png'}" class="doc-logo">
            <div class="doc-company-info">
                <div style="font-weight:bold; font-size:18px">${$('#header_name').val()}</div>
                <div style="font-size:13px">${$('#header_address').val()}</div>
                <div style="font-size:13px">โทร. ${$('#header_phone').val()} เลขประจำตัวผู้เสียภาษี ${$('#header_tax_id').val()}</div>
            </div>
            <div class="doc-title">${title}</div>
        </div>
        <div class="doc-info-grid">
            <div style="font-size:14px">
                <div>รหัส : ${$('#customer_code').val()}</div>
                <div>ชื่อ : ${$('#customer_name').val()}</div>
                <div>ที่อยู่ : ${$('#customer_address').val()}</div>
                <div>โทรศัพท์ : ${$('#customer_phone').val()} รหัสผู้เสียภาษี : ${$('#customer_tax_id').val()}</div>
            </div>
            <div style="text-align:right; font-size:14px">
                <div style="font-weight:bold; font-size:16px">${title} เลขที่ : ${$('#doc_number').val()}</div>
                <div>วันที่ : ${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</div>
                <div>เงื่อนไขการชำระ : ${$('#payment_terms_template option:selected').val() ? $('#payment_terms_template option:selected').text() : ($('#payment_terms').val() || '-')}</div>
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
            <div class="signature-box">
                <div>ผู้รับเงิน</div>
                <div class="signature-line">${$('#sig1_preview').attr('src') ? `<img src="${$('#sig1_preview').attr('src')}" class="signature-preview">` : ''}</div>
                <div style="margin-top: 5px; font-size: 12px;">วันที่ ${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</div>
            </div>
            <div class="signature-box">
                <div>ผู้อนุมัติ</div>
                <div class="signature-line">${$('#sig2_preview').attr('src') ? `<img src="${$('#sig2_preview').attr('src')}" class="signature-preview">` : ''}</div>
                <div style="margin-top: 5px; font-size: 12px;">วันที่ ${new Date($('#doc_date').val()).toLocaleDateString('th-TH')}</div>
            </div>
        </div>`;
    $('#print-area').html(html).removeClass('hidden');

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

function printDoc() { generatePreview(); setTimeout(() => { window.print(); }, 800); }
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
            filename: ($('#doc_number').val() || 'document') + '.pdf', 
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

// Template Management
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

function issueReceipt() {
    Swal.fire({
        title: 'ยืนยันการออกใบเสร็จ?',
        text: 'คุณต้องการสร้างใบเสร็จรับเงินจากใบแจ้งหนี้นี้ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, สร้างเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังสร้างใบเสร็จ...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: 'invoice_action.php',
                type: 'POST',
                data: { action: 'convert_to_receipt', id: $('#doc_id').val() },
                success: function(response) {
                    try {
                        let res = typeof response === 'object' ? response : JSON.parse(response);
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: res.message,
                                showCancelButton: true,
                                confirmButtonText: 'ไปหน้าใบเสร็จ',
                                cancelButtonText: 'อยู่ที่เดิม'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'receipt_form.php?id=' + res.receipt_id;
                                }
                            });
                        } else {
                            Swal.fire('ผิดพลาด', res.message, 'error');
                        }
                    } catch (e) {
                        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการประมวลผล', 'error');
                    }
                }
            });
        }
    });
}
</script>
</body>
</html>

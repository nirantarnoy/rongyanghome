<?php
require '../auth_check.php';
require '../config.php';

$id = $_GET['id'] ?? 0;
$company_id = $_SESSION['company_id'];

// Get material requisition with production order and main product details
        $sql = "SELECT mr.*, 
               po.order_no as production_order_no, 
               po.customer_name, 
               po.project_name, 
               po.qty as production_qty, 
               po.unit as production_unit,
               po.order_date as production_order_date,
               po.due_date as production_due_date,
               po.ordered_by as production_ordered_by,
               po.foreman,
               po.instructions,
               po.qc_standards,
               po.dimensions,
               p.name as main_product_name,
               p.sku as main_product_sku
        FROM material_requisitions mr
        LEFT JOIN stock_production_orders po ON mr.production_order_id = po.id
        LEFT JOIN stock_products p ON po.product_id = p.id
        WHERE mr.id = ? AND mr.company_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$requisition = mysqli_fetch_assoc($result);

if (!$requisition) {
    die("ไม่พบใบเบิกจ่ายวัสดุ");
}

// Get requisition items (components)
$items_sql = "SELECT mri.*, p.name as product_name, p.sku, p.unit as product_unit, p.price
              FROM material_requisition_items mri
              LEFT JOIN stock_products p ON mri.product_id = p.id
              WHERE mri.requisition_id = ?
              ORDER BY mri.id";
$items_stmt = mysqli_prepare($conn, $items_sql);
mysqli_stmt_bind_param($items_stmt, "i", $id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
}

// Get company info
$comp_sql = "SELECT * FROM company WHERE id = ?";
$comp_stmt = mysqli_prepare($conn, $comp_sql);
mysqli_stmt_bind_param($comp_stmt, "i", $company_id);
mysqli_stmt_execute($comp_stmt);
$comp_result = mysqli_stmt_get_result($comp_stmt);
$company = mysqli_fetch_assoc($comp_result);

function generateBarcodeSVG($text) {
    // Simple 1D barcode simulation using SVG
    $width = 200;
    $height = 40;
    $svg = '<svg width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" xmlns="http://www.w3.org/2000/svg">';
    $seed = crc32($text);
    srand($seed);
    $x = 10;
    while ($x < $width - 10) {
        $w = rand(1, 3);
        if (rand(0, 1)) {
            $svg .= '<rect x="'.$x.'" y="0" width="'.$w.'" height="'.$height.'" fill="black" />';
        }
        $x += $w + rand(1, 2);
    }
    $svg .= '</svg>';
    return $svg;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเบิกวัสดุ - <?= $requisition['requisition_no'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; font-size: 14px; line-height: 1.4; color: #333; background: #f5f5f5; padding: 20px; }
        
        .container { 
            width: 210mm; 
            min-height: 297mm;
            margin: 0 auto; 
            background: white; 
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        /* Top Header */
        .top-header {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 5px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .company-header {
            text-align: center;
            flex: 1;
            font-weight: bold;
            line-height: 1.2;
        }

        /* Document Title */
        .doc-title-container {
            text-align: center;
            margin: 20px 0;
        }
        .doc-title {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Main Info Section */
        .main-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .doc-id-section {
            display: flex;
            flex-direction: column;
        }

        .doc-id {
            font-size: 28px;
            font-weight: 700;
            color: #000;
        }

        .barcode-container {
            margin-top: 5px;
        }

        .logo-container {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .info-item {
            display: flex;
            gap: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #4b5563;
            min-width: 120px;
        }

        .info-value {
            color: #111827;
            flex: 1;
        }

        /* Tables */
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #1f2937;
            border-left: 4px solid #10b981;
            padding-left: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Summary */
        .summary-row {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
            margin-top: 10px;
            padding: 10px;
            background: #f0fdf4;
            border-radius: 5px;
        }

        /* Instructions Section */
        .instructions-box {
            padding: 15px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }

        /* Footer / Signatures */
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            text-align: center;
            width: 280px;
        }

        .signature-line {
            border-bottom: 1px solid #9ca3af;
            margin-bottom: 8px;
            height: 50px;
        }

        @media print {
            body { background: none; padding: 0; }
            .container { box-shadow: none; width: 100%; padding: 0; }
            .no-print { display: none; }
            .info-grid { background: #fff !important; border: 1px solid #ccc; }
            .summary-row { background: #fff !important; border: 1px solid #ccc; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Top Header -->
        <div class="top-header">
            <div>พิมพ์เมื่อ: <?= date('d/m/Y H:i') ?></div>
            <div class="company-header">
                <?= htmlspecialchars($company['company_name'] ?? 'บริษัท บ้านสักทองร้องแหย่ง จำกัด') ?>
            </div>
            <div>หน้า 1 / 1</div>
        </div>

        <!-- Document Title -->
        <div class="doc-title-container">
            <div class="doc-title">ใบเบิกจ่ายวัสดุ</div>
        </div>

        <!-- Main Info Section -->
        <div class="main-info">
            <div class="doc-id-section" style="font-size: 12px;">
                <div style="font-weight: bold; font-size: 16px;"><?= htmlspecialchars($company['company_name']) ?></div>
                <div style="white-space: pre-wrap;"><?= htmlspecialchars($company['address']) ?></div>
                <div>เลขประจำตัวผู้เสียภาษี: <?= htmlspecialchars($company['tax_id']) ?></div>
            </div>
            <div class="logo-container">
                <?php 
                $logo_path = '../assets/logo/logo.png';
                if (file_exists($logo_path)): 
                ?>
                <img src="<?= $logo_path ?>" alt="Logo">
                <?php else: ?>
                <div style="font-size: 10px; color: #ccc; border: 1px dashed #ccc; padding: 20px;">LOGO</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">วันที่เบิก:</span>
                <span class="info-value"><?= date('d/m/Y', strtotime($requisition['requisition_date'])) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">ผู้รับผิดชอบ:</span>
                <span class="info-value"><?= htmlspecialchars($requisition['requested_by'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">ช่าง/หัวหน้างาน:</span>
                <span class="info-value"><b><?= htmlspecialchars($requisition['foreman'] ?? '-') ?></b></span>
            </div>
            <div class="info-item">
                <span class="info-label">ใบสั่งผลิตอ้างอิง:</span>
                <span class="info-value"><b><?= htmlspecialchars($requisition['production_order_no'] ?? '-') ?></b></span>
            </div>
            <div class="info-item">
                <span class="info-label">กำหนดเสร็จ (PO):</span>
                <span class="info-value"><?= $requisition['production_due_date'] ? date('d/m/Y', strtotime($requisition['production_due_date'])) : '-' ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">ลูกค้า/โครงการ:</span>
                <span class="info-value"><?= htmlspecialchars($requisition['customer_name'] ?? '-') ?> <?= $requisition['project_name'] ? '('.htmlspecialchars($requisition['project_name']).')' : '' ?></span>
            </div>
            <div class="info-item" style="grid-column: span 2;">
                <span class="info-label">สินค้าที่ผลิต:</span>
                <span class="info-value">[<?= htmlspecialchars($requisition['main_product_sku'] ?? '-') ?>] <?= htmlspecialchars($requisition['main_product_name'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">จำนวนที่ผลิต:</span>
                <span class="info-value"><?= number_format($requisition['production_qty'] ?? 0, 2) ?> <?= htmlspecialchars($requisition['production_unit'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">ขนาด/มิติ:</span>
                <span class="info-value"><?= htmlspecialchars($requisition['dimensions'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">วัตถุประสงค์:</span>
                <span class="info-value"><?= htmlspecialchars($requisition['purpose'] ?? '-') ?></span>
            </div>
        </div>

        <?php if ($requisition['instructions']): ?>
        <div class="section-title">ขั้นตอนการทำงาน / คำแนะนำ</div>
        <div class="instructions-box"><?= htmlspecialchars($requisition['instructions']) ?></div>
        <?php endif; ?>

        <?php if ($requisition['qc_standards']): ?>
        <div class="section-title">มาตรฐานการตรวจสอบ (QC)</div>
        <div class="instructions-box" style="background: #fff1f2; border-color: #fecdd3;"><?= htmlspecialchars($requisition['qc_standards']) ?></div>
        <?php endif; ?>

        <!-- Components Table -->
        <div class="section-title">รายการวัสดุที่เบิก (Components)</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">ลำดับ</th>
                    <th style="width: 45%;">รายการสินค้า / รายละเอียด</th>
                    <th style="width: 15%;" class="text-center">จำนวน</th>
                    <th style="width: 15%;" class="text-right">ราคาต่อหน่วย</th>
                    <th style="width: 20%;" class="text-right">ราคารวม</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                $total_amount = 0;
                foreach ($items as $item): 
                    $price = $item['price'] ?? 0;
                    $line_total = $item['qty_requested'] * $price;
                    $total_amount += $line_total;
                ?>
                <tr>
                    <td class="text-center"><?= $i++ ?></td>
                    <td>
                        <div style="font-weight: 600;">[<?= htmlspecialchars($item['sku'] ?? '-') ?>] <?= htmlspecialchars($item['product_name']) ?></div>
                    </td>
                    <td class="text-center">
                        <span style="font-size: 16px; font-weight: 700;"><?= number_format($item['qty_requested'], 2) ?></span> <?= htmlspecialchars($item['unit'] ?? $item['product_unit']) ?>
                    </td>
                    <td class="text-right"><?= number_format($price, 2) ?></td>
                    <td class="text-right"><?= number_format($line_total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; color: #999;">ไม่พบรายการวัสดุ</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="summary-row">
            รวมเป็นเงินทั้งสิ้น <?= number_format($total_amount, 2) ?> บาท
        </div>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <p>ผู้เบิก (Requested By)</p>
                <div class="signature-line"></div>
                <p>( <?= htmlspecialchars($requisition['requested_by'] ?? '................................................') ?> )</p>
                <p style="font-size: 12px; margin-top: 5px;">วันที่ <?= date('d/m/Y', strtotime($requisition['requisition_date'])) ?></p>
            </div>
            <div class="signature-box">
                <p>ผู้อนุมัติ (Approved By)</p>
                <div class="signature-line"></div>
                <p>( <?= htmlspecialchars($requisition['approved_by'] ?? '................................................') ?> )</p>
                <p style="font-size: 12px; margin-top: 5px;">วันที่ <?= $requisition['approved_date'] ? date('d/m/Y', strtotime($requisition['approved_date'])) : '..../..../....' ?></p>
            </div>
        </div>

        <!-- Print Button -->
        <div class="no-print" style="margin-top: 50px; text-align: center; display: flex; justify-content: center; gap: 15px;">
            <button onclick="window.print()" style="background: #10B981; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <span>🖨️</span> พิมพ์เอกสาร
            </button>
            <button onclick="window.close()" style="background: #6B7280; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                ปิดหน้าต่าง
            </button>
        </div>
    </div>
</body>
</html>

<?php
require '../auth_check.php';
require '../config.php';

$id = $_GET['id'] ?? 0;
$company_id = $_SESSION['company_id'];

// Get production order
$sql = "SELECT po.*, p.name as product_name, p.sku, p.unit as product_unit
        FROM stock_production_orders po
        LEFT JOIN stock_products p ON po.product_id = p.id
        WHERE po.id = ? AND po.company_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    die("ไม่พบใบสั่งผลิต");
}

// Get BOM items
$bom_sql = "SELECT pob.*, p.name as product_name, p.sku, p.unit
            FROM stock_production_bom pob
            LEFT JOIN stock_products p ON pob.product_id = p.id
            WHERE pob.production_order_id = ?
            ORDER BY pob.id";
$bom_stmt = mysqli_prepare($conn, $bom_sql);
mysqli_stmt_bind_param($bom_stmt, "i", $id);
mysqli_stmt_execute($bom_stmt);
$bom_result = mysqli_stmt_get_result($bom_stmt);
$bom_items = [];
while ($item = mysqli_fetch_assoc($bom_result)) {
    $bom_items[] = $item;
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
    <title>ใบสั่งผลิต - <?= $order['order_no'] ?></title>
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
            border-left: 4px solid #6366f1;
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
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 8px;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }

        .qc-box {
            padding: 15px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
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
            .instructions-box, .qc-box { background: #fff !important; border: 1px solid #ccc; }
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
            <div class="doc-title">ใบสั่งผลิต</div>
        </div>

        <!-- Main Info Section -->
        <div class="main-info">
            <div class="doc-id-section">
                <div class="doc-id"><?= htmlspecialchars($order['order_no']) ?></div>
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
                <span class="info-label">วันที่สั่งผลิต:</span>
                <span class="info-value"><?= date('d/m/Y', strtotime($order['order_date'])) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">กำหนดเสร็จ:</span>
                <span class="info-value"><b><?= $order['due_date'] ? date('d/m/Y', strtotime($order['due_date'])) : '-' ?></b></span>
            </div>
            <div class="info-item">
                <span class="info-label">ลูกค้า:</span>
                <span class="info-value"><?= htmlspecialchars($order['customer_name'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">โครงการ:</span>
                <span class="info-value"><?= htmlspecialchars($order['project_name'] ?? '-') ?></span>
            </div>
            <div class="info-item" style="grid-column: span 2;">
                <span class="info-label">สินค้าที่ผลิต:</span>
                <span class="info-value">[<?= htmlspecialchars($order['sku'] ?? '-') ?>] <?= htmlspecialchars($order['product_name'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">จำนวนที่ผลิต:</span>
                <span class="info-value"><?= number_format($order['qty'], 2) ?> <?= htmlspecialchars($order['unit'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">ขนาด/มิติ:</span>
                <span class="info-value"><?= htmlspecialchars($order['dimensions'] ?? '-') ?></span>
            </div>
        </div>

        <div class="section-title">รายการวัสดุที่ใช้ (BOM - Bill of Materials)</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;" class="text-center">ลำดับ</th>
                    <th style="width: 50%;">รายการวัสดุ / SKU</th>
                    <th style="width: 20%;" class="text-center">จำนวนที่ใช้</th>
                    <th style="width: 20%;" class="text-center">หน่วย</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($bom_items as $item): 
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div style="font-size: 12px; color: #666;">SKU: <?= htmlspecialchars($item['sku'] ?? '-') ?></div>
                    </td>
                    <td class="text-center"><?= number_format($item['qty'], 2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($bom_items)): ?>
                <tr>
                    <td colspan="4" class="text-center" style="padding: 20px; color: #999;">ไม่มีรายการวัสดุ (BOM)</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($order['instructions']): ?>
        <div class="section-title">ขั้นตอนการทำงาน / คำแนะนำ</div>
        <div class="instructions-box"><?= htmlspecialchars($order['instructions']) ?></div>
        <?php endif; ?>

        <?php if ($order['qc_standards']): ?>
        <div class="section-title">มาตรฐานการตรวจสอบ (QC)</div>
        <div class="qc-box"><?= htmlspecialchars($order['qc_standards']) ?></div>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <p>ผู้สั่งผลิต (Ordered By)</p>
                <div class="signature-line"></div>
                <p>( <?= htmlspecialchars($order['ordered_by'] ?? '................................................') ?> )</p>
                <p style="font-size: 12px; margin-top: 5px;">วันที่ <?= date('d/m/Y', strtotime($order['order_date'])) ?></p>
            </div>
            <div class="signature-box">
                <p>ผู้รับคำสั่ง / หัวหน้าช่าง (Foreman)</p>
                <div class="signature-line"></div>
                <p>( <?= htmlspecialchars($order['foreman'] ?? '................................................') ?> )</p>
                <p style="font-size: 12px; margin-top: 5px;">วันที่ ...........................</p>
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center; font-size: 11px; color: #6b7280; border-top: 1px dashed #ccc; padding-top: 10px;">
            เอกสารนี้เป็นส่วนหนึ่งของระบบจัดการสต็อกและฝ่ายผลิต - <?= htmlspecialchars($company['company_name'] ?? '') ?>
        </div>

        <!-- Print Button -->
        <div class="no-print" style="margin-top: 50px; text-align: center; display: flex; justify-content: center; gap: 15px;">
            <button onclick="window.print()" style="background: #6366F1; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <span>🖨️</span> พิมพ์ใบสั่งผลิต
            </button>
            <button onclick="window.close()" style="background: #6B7280; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                ปิดหน้าต่าง
            </button>
        </div>
    </div>
</body>
</html>

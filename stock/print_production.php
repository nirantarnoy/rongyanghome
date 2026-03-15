<?php
require '../auth_check.php';
require '../config.php';

$id = $_GET['id'] ?? 0;
$company_id = $_SESSION['company_id'];

// Get production order
$sql = "SELECT po.*, p.name as product_name, p.sku, p.unit as product_unit, p.price as product_price
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

// Get byproducts
$bp_sql = "SELECT * FROM stock_production_byproducts WHERE production_order_id = ? ORDER BY id";
$bp_stmt = mysqli_prepare($conn, $bp_sql);
mysqli_stmt_bind_param($bp_stmt, "i", $id);
mysqli_stmt_execute($bp_stmt);
$bp_result = mysqli_stmt_get_result($bp_stmt);
$byproducts = [];
while ($bp = mysqli_fetch_assoc($bp_result)) {
    $byproducts[] = $bp;
}


// Get company info
$comp_sql = "SELECT * FROM company WHERE id = ?";
$comp_stmt = mysqli_prepare($conn, $comp_sql);
mysqli_stmt_bind_param($comp_stmt, "i", $company_id);
mysqli_stmt_execute($comp_stmt);
$comp_result = mysqli_stmt_get_result($comp_stmt);
$company = mysqli_fetch_assoc($comp_result);

// Calculate duration
$duration = '-';
if (!empty($order['order_date']) && !empty($order['due_date']) && $order['due_date'] != '0000-00-00') {
    $d1 = new DateTime($order['order_date']);
    $d2 = new DateTime($order['due_date']);
    $diff = $d1->diff($d2);
    $duration = $diff->days;
    if ($duration == 0) $duration = 1; // At least 1 day if start/end are same
}

// Get Warehouse names from requisition items
$wh_sql = "SELECT DISTINCT w.name 
           FROM material_requisition_items mri
           JOIN stock_warehouses w ON mri.warehouse_id = w.id
           JOIN material_requisitions mr ON mri.requisition_id = mr.id
           WHERE mr.production_order_id = ? AND mr.company_id = ?";
$wh_stmt = mysqli_prepare($conn, $wh_sql);
mysqli_stmt_bind_param($wh_stmt, "ii", $id, $company_id);
mysqli_stmt_execute($wh_stmt);
$wh_res = mysqli_stmt_get_result($wh_stmt);
$wh_names = [];
while ($wh_row = mysqli_fetch_assoc($wh_res)) {
    $wh_names[] = $wh_row['name'];
}
$warehouse_display = !empty($wh_names) ? implode(', ', $wh_names) : '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบสั่งผลิต - <?= $order['order_no'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; font-size: 13px; line-height: 1.4; color: #000; background: #f5f5f5; padding: 20px; }
        
        .container { 
            width: 210mm; 
            min-height: 297mm;
            margin: 0 auto; 
            background: white; 
            padding: 15mm;
            position: relative;
        }

        .header-table { width: 100%; margin-bottom: 20px; border: none; }
        .header-table td { border: none; padding: 0; vertical-align: top; }
        
        .logo-box { width: 80px; height: 80px; margin-right: 15px; }
        .logo-box img { width: 100%; height: 100%; object-fit: contain; }

        .company-name { font-size: 16px; font-weight: bold; margin-top: 25px; }
        .doc-title-box { text-align: right; }
        .doc-title { font-size: 24px; font-weight: bold; }
        .doc-no { font-size: 20px; font-weight: bold; margin-top: 5px; }

        .meta-info { width: 100%; margin: 15px 0; border: none; }
        .meta-info td { border: none; padding: 3px 0; }
        .meta-label { font-weight: bold; width: 100px; }

        .section-title { font-size: 16px; font-weight: bold; border-bottom: none; margin-bottom: 10px; margin-top: 20px; text-decoration: none; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #000; padding: 6px 8px; }
        table.data-table th { background-color: #fff; text-align: center; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }

        .signature-section { margin-top: 40px; display: flex; justify-content: space-around; text-align: center; }
        .sig-box { width: 200px; }
        .sig-line { border-bottom: 1px dotted #000; margin: 30px 0 5px 0; }

        .total-box { text-align: right; padding: 8px; font-weight: bold; font-size: 14px; }

        @media print {
            body { background: none; padding: 0; }
            .container { box-shadow: none; width: 100%; padding: 0; margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 80px;">
                    <div class="logo-box">
                        <?php 
                        $logo_path = '../assets/logo/logo.png';
                        if (file_exists($logo_path)): 
                        ?>
                        <img src="<?= $logo_path ?>" alt="Logo">
                        <?php else: ?>
                        <div style="border: 1px solid #000; padding: 10px; text-align: center;">LOGO</div>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? 'บริษัท บ้านสักทองร้องแหย่ง จำกัด') ?></div>
                </td>
                <td class="doc-title-box">
                    <div class="doc-title">ใบสั่งผลิต</div>
                    <div class="doc-no">เลขที่ <?= htmlspecialchars($order['order_no']) ?></div>
                </td>
            </tr>
        </table>

        <!-- Meta Info -->
        <table class="meta-info">
            <tr>
                <td class="meta-label">รับผิดชอบ:</td>
                <td><?= htmlspecialchars($order['foreman'] ?? 'บจก.บ้านสักทองร้องแหย่ง') ?></td>
                <td class="meta-label">วันที่สั่งผลิต:</td>
                <td><?= date('d/m/Y', strtotime($order['order_date'])) ?></td>
            </tr>
            <tr>
                <td class="meta-label">ลูกค้า:</td>
                <td><?= htmlspecialchars($order['customer_name'] ?? '-') ?></td>
                <td class="meta-label">กำหนดเสร็จ:</td>
                <td><?= $order['due_date'] ? date('d/m/Y', strtotime($order['due_date'])) : '-' ?></td>
            </tr>
            <tr>
                <td class="meta-label">โครงการ:</td>
                <td colspan="3"><?= htmlspecialchars($order['project_name'] ?? '-') ?></td>
            </tr>
        </table>

        <!-- ปฏิบัติการ -->
        <div class="section-title">ปฏิบัติการ</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">ปฏิบัติการ (ขั้นตอน)</th>
                    <th style="width: 35%;">ศูนย์งาน</th>
                    <th style="width: 15%;">ระยะเวลา (วัน)</th>
                    <th style="width: 25%;">ผู้ปฏิบัติงาน/หัวหน้างาน</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center"><?= nl2br(htmlspecialchars($order['instructions'] ?: 'เลื่อย')) ?></td>
                    <td><?= htmlspecialchars($order['project_name'] ?: 'โรงงานบริษัทบ้านสักทองร้องแหย่งจำกัด') ?></td>
                    <td class="text-center"><?= $duration ?></td>
                    <td class="text-center"><?= htmlspecialchars($order['foreman'] ?: '-') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- สินค้าที่จะผลิต -->
        <div class="section-title">สินค้าที่จะผลิต</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">ที่</th>
                    <th style="width: 45%;">สินค้า ที่จะผลิต</th>
                    <th style="width: 15%;">จำนวนที่จะผลิต</th>
                    <th style="width: 10%;">หน่วย</th>
                    <th style="width: 12%;">ราคาต่อหน่วย</th>
                    <th style="width: 13%;">รวมราคา</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $price = $order['product_price'] ?? 0;
                $total_prod = $order['qty'] * $price;
                ?>
                <tr>
                    <td class="text-center">1</td>
                    <td>
                        <?= htmlspecialchars($order['product_name']) ?>
                        <?php if (!empty($order['dimensions'])): ?>
                            <br><small style="color: #666;">ขนาด/มิติ: <?= htmlspecialchars($order['dimensions']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= number_format($order['qty'], 2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($order['unit']) ?></td>
                    <td class="text-right"><?= number_format($price, 2) ?></td>
                    <td class="text-right"><?= number_format($total_prod, 2) ?></td>
                </tr>
            </tbody>
        </table>
        <div class="total-box">รวมราคา <?= number_format($total_prod, 2) ?> บาท</div>

        <?php if (!empty($order['qc_standards'])): ?>
        <div style="margin-top: 5px; margin-bottom: 15px;">
            <strong>มาตรฐานการตรวจสอบ (QC):</strong>
            <div style="border: 1px solid #ccc; padding: 8px; border-radius: 4px; margin-top: 3px; font-size: 12px;">
                <?= nl2br(htmlspecialchars($order['qc_standards'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ส่วนประกอบ -->
        <div class="section-title">ส่วนประกอบ คลังสินค้า: <?= htmlspecialchars($warehouse_display ?: '_________________') ?></div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">ที่</th>
                    <th style="width: 45%;">วัสดุ</th>
                    <th style="width: 15%;">จำนวน</th>
                    <th style="width: 10%;">หน่วย</th>
                    <th style="width: 12%;">ราคาต่อหน่วย</th>
                    <th style="width: 13%;">รวมราคา</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_bom = 0;
                foreach ($bom_items as $item): 
                    // Get price from store or database if needed, but assuming it's joined
                    $item_price = 0; // default 0 if not exist
                    // Let's try to get price from products table
                    $p_sql = "SELECT price FROM stock_products WHERE id = ?";
                    $p_stmt = mysqli_prepare($conn, $p_sql);
                    mysqli_stmt_bind_param($p_stmt, "i", $item['product_id']);
                    mysqli_stmt_execute($p_stmt);
                    $p_res = mysqli_stmt_get_result($p_stmt);
                    if($p_row = mysqli_fetch_assoc($p_res)) $item_price = $p_row['price'];
                    
                    $subtotal = $item['qty'] * $item_price;
                    $total_bom += $subtotal;
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="text-center"><?= number_format($item['qty'], 2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                    <td class="text-right"><?= number_format($item_price, 2) ?></td>
                    <td class="text-right"><?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($bom_items)): ?>
                <tr>
                    <td colspan="6" class="text-center">ไม่มีข้อมูล</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="total-box">รวมราคา <?= number_format($total_bom, 2) ?> บาท</div>

        <!-- Signatures -->
        <div class="signature-section" style="margin-top: 30px;">
            <div class="sig-box">
                <p class="font-bold">ผู้เบิกผลิต</p>
                <div class="sig-line"></div>
                <p>( ................................................ )</p>
            </div>
            <div class="sig-box">
                <p class="font-bold">ผู้อนุมัติ</p>
                <div class="sig-line"></div>
                <p>( ................................................ )</p>
            </div>
        </div>

        <!-- ผลิตภัณฑ์พลอยได้ -->
        <div class="section-title" style="margin-top: 50px;">ผลิตภัณฑ์พลอยได้หรือเศษผลผลิตคงเหลือ</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">ที่</th>
                    <th style="width: 45%;">วัสดุ</th>
                    <th style="width: 15%;">จำนวน</th>
                    <th style="width: 10%;">หน่วย</th>
                    <th style="width: 12%;">ราคาต่อหน่วย</th>
                    <th style="width: 13%;">รวมราคา</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_bp = 0;
                foreach ($byproducts as $bp): 
                    $total_bp += $bp['total'];
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($bp['name']) ?></td>
                    <td class="text-center"><?= number_format($bp['qty'], 2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($bp['unit']) ?></td>
                    <td class="text-right"><?= number_format($bp['price'], 2) ?></td>
                    <td class="text-right"><?= number_format($bp['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($byproducts)): ?>
                <tr>
                    <td colspan="6" class="text-center">ไม่มีข้อมูล</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="total-box">รวมราคา <?= number_format($total_bp, 2) ?> บาท</div>


        <!-- Buttons -->
        <div class="no-print" style="margin-top: 50px; text-align: center;">
            <button onclick="window.print()" style="background: #10B981; color: white; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-family: 'Sarabun';">พิมพ์เอกสาร</button>
            <button onclick="window.close()" style="margin-left: 10px; background: #6B7280; color: white; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-family: 'Sarabun';">ปิด</button>
        </div>
    </div>
</body>
</html>

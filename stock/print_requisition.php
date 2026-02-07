<?php
require '../auth_check.php';
require '../config.php';

$id = $_GET['id'] ?? 0;
$company_id = $_SESSION['company_id'];

$sql = "SELECT * FROM stock_requisitions WHERE id = ? AND company_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$req = mysqli_fetch_assoc($res);

if (!$req) {
    die("ไม่พบข้อมูลใบเบิก");
}

$sql_items = "SELECT ri.*, p.name as product_name, p.sku, p.unit, w.name as warehouse_name 
              FROM stock_requisition_items ri 
              JOIN stock_products p ON ri.product_id = p.id 
              LEFT JOIN stock_warehouses w ON ri.warehouse_id = w.id
              WHERE ri.requisition_id = ?";
$stmt_items = mysqli_prepare($conn, $sql_items);
mysqli_stmt_bind_param($stmt_items, "i", $id);
mysqli_stmt_execute($stmt_items);
$res_items = mysqli_stmt_get_result($stmt_items);

// Get company info
$comp_sql = "SELECT * FROM company WHERE id = ?";
$comp_stmt = mysqli_prepare($conn, $comp_sql);
mysqli_stmt_bind_param($comp_stmt, "i", $company_id);
mysqli_stmt_execute($comp_stmt);
$company = mysqli_fetch_assoc(mysqli_stmt_get_result($comp_stmt));

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเบิกสินค้า - <?= htmlspecialchars($req['req_no']) ?></title>
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
        .meta-info td { border: none; padding: 4px 5px; }
        .meta-label { font-weight: bold; width: 100px; border-bottom: 1px solid #eee; }
        .meta-underline { border-bottom: 1px solid #ccc; min-width: 150px; display: inline-block; padding-bottom: 2px; }

        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; margin-top: 20px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #000; padding: 8px; }
        table.data-table th { background-color: #fff; text-align: center; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .signature-section { margin-top: 60px; display: flex; justify-content: space-around; text-align: center; }
        .sig-box { width: 200px; }
        .sig-line { border-bottom: 1px dotted #000; margin: 30px 0 5px 0; }

        .total-box { text-align: right; padding: 8px; font-weight: bold; font-size: 14px; }
        
        .remark-box { margin-top: 40px; }
        .remark-title { font-weight: bold; margin-bottom: 10px; }
        .remark-lines { border-bottom: 1px dotted #000; margin-bottom: 10px; height: 20px; }

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
                    <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? $company['name'] ?? 'บริษัท บ้านสักทองร้องแหย่ง จำกัด') ?></div>
                </td>
                <td class="doc-title-box">
                    <div class="doc-title">ใบเบิกสินค้า</div>
                    <div class="doc-no">เลขที่ <?= htmlspecialchars($req['req_no']) ?></div>
                </td>
            </tr>
        </table>

        <!-- Meta Info -->
        <table class="meta-info" style="margin-bottom: 30px;">
            <tr>
                <td class="meta-label">รับผิดชอบ:</td>
                <td style="width: 35%;"><span class="meta-underline"><?= htmlspecialchars($req['requester_name'] ?? '-') ?></span></td>
                <td class="meta-label">วันที่เบิก:</td>
                <td><span class="meta-underline"><?= date('d-m-Y', strtotime($req['requisition_date'])) ?></span></td>
            </tr>
            <tr>
                <td class="meta-label">ตำแหน่งปลายทาง:</td>
                <td><span class="meta-underline">________________________</span></td>
                <td class="meta-label">เอกสารอ้างอิง:</td>
                <td><span class="meta-underline"><?= htmlspecialchars($req['so_no'] ?? $req['po_no'] ?? '-') ?></span></td>
            </tr>
        </table>

        <div style="margin: 20px 0; font-weight: bold;">
            คลังสินค้า <span style="border-bottom: 1px solid #ccc; min-width: 300px; display: inline-block;">________________________________</span>
        </div>

        <!-- สินค้าที่เบิก -->
        <div class="section-title">สินค้าที่เบิก</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">ที่</th>
                    <th style="width: 45%;">สินค้า</th>
                    <th style="width: 15%;">จำนวน</th>
                    <th style="width: 10%;">หน่วย</th>
                    <th style="width: 12%;">ราคาต่อหน่วย</th>
                    <th style="width: 13%;">รวมราคา</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                $total_all = 0;
                while ($item = mysqli_fetch_assoc($res_items)): 
                    // Get price from store or database if needed
                    $item_price = 0;
                    $p_sql = "SELECT price FROM stock_products WHERE id = ?";
                    $p_stmt = mysqli_prepare($conn, $p_sql);
                    mysqli_stmt_bind_param($p_stmt, "i", $item['product_id']);
                    mysqli_stmt_execute($p_stmt);
                    $p_res = mysqli_stmt_get_result($p_stmt);
                    if($p_row = mysqli_fetch_assoc($p_res)) $item_price = $p_row['price'];
                    
                    $subtotal = $item['qty'] * $item_price;
                    $total_all += $subtotal;
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="text-center"><?= number_format($item['qty'], 2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                    <td class="text-right"><?= number_format($item_price, 2) ?></td>
                    <td class="text-right"><?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($no == 1): ?>
                <tr>
                    <td colspan="6" class="text-center">ไม่มีรายการสินค้า</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="total-box">รวมราคา <?= number_format($total_all, 2) ?> บาท</div>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="sig-box">
                <p class="font-bold">ผู้เบิก</p>
                <div class="sig-line"></div>
                <p>( ................................................ )</p>
            </div>
            <div class="sig-box">
                <p class="font-bold">ผู้อนุมัติ</p>
                <div class="sig-line"></div>
                <p>( ................................................ )</p>
            </div>
        </div>

        <!-- หมายเหตุ -->
        <div class="remark-box">
            <p class="remark-title">หมายเหตุ</p>
            <div class="remark-lines"></div>
            <div class="remark-lines"></div>
            <div class="remark-lines"></div>
        </div>

        <!-- Buttons -->
        <div class="no-print" style="margin-top: 50px; text-align: center;">
            <button onclick="window.print()" style="background: #10B981; color: white; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-family: 'Sarabun';">พิมพ์เอกสาร</button>
            <button onclick="window.close()" style="margin-left: 10px; background: #6B7280; color: white; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-family: 'Sarabun';">ปิด</button>
        </div>
    </div>
</body>
</html>

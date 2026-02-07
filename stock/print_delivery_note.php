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
    die("ไม่พบข้อมูล");
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
    <title>ใบส่งสินค้า - <?= htmlspecialchars($req['req_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page { size: A4; margin: 10mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; font-size: 13px; line-height: 1.4; color: #000; background: #eee; }
        
        .container { 
            width: 210mm; 
            min-height: 297mm;
            margin: 20px auto; 
            background: white; 
            padding: 10mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .logo-box { width: 100px; }
        .logo-box img { width: 100px; height: 100px; object-fit: contain; }
        
        .company-info { flex: 1; padding: 0 20px; text-align: center; }
        .company-name { font-size: 18px; font-weight: bold; color: #d32f2f; }
        
        .doc-title-box { width: 180px; text-align: right; }
        .doc-title { font-size: 24px; font-weight: bold; color: #d32f2f; }
        .doc-no { font-size: 16px; font-weight: bold; margin-top: 5px; }

        .info-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .customer-box, .order-box { border: 1px solid #000; padding: 10px; border-radius: 5px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data-table th, table.data-table td { border: 1px solid #000; padding: 6px 8px; }
        table.data-table th { background-color: #fce4ec; text-align: center; }
        
        .footer-section { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 50px; text-align: center; }
        .sig-box { border: none; }
        .sig-line { border-bottom: 1px dotted #000; margin: 40px auto 5px; width: 80%; }

        @media print {
            body { background: none; }
            .container { margin: 0; box-shadow: none; width: 100%; border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <div class="logo-box">
                <img src="../assets/logo/logo.png" alt="Logo" onerror="this.src='https://via.placeholder.com/100?text=LOGO'">
            </div>
            <div class="company-info">
                <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? 'บริษัท บ้านสักทองร้องแหย่ง จำกัด') ?></div>
                <div style="font-size: 12px;"><?= htmlspecialchars($company['address'] ?? '') ?></div>
                <div style="font-size: 12px;">โทร. <?= htmlspecialchars($company['phone'] ?? '') ?> | เลขประจำตัวผู้เสียภาษี: <?= htmlspecialchars($company['tax_id'] ?? '') ?></div>
            </div>
            <div class="doc-title-box">
                <div class="doc-title">ใบส่งสินค้า</div>
                <div class="doc-no">เลขที่ <?= htmlspecialchars($req['req_no']) ?></div>
            </div>
        </div>

        <div class="info-grid">
            <div class="customer-box">
                <p><strong>ชื่อลูกค้า:</strong> <?= htmlspecialchars($req['customer_name']) ?></p>
                <p><strong>ที่อยู่:</strong> <?= nl2br(htmlspecialchars($req['shipping_address'] ?? '-')) ?></p>
                <p><strong>โทรศัพท์:</strong> <?= htmlspecialchars($req['phone'] ?? '-') ?></p>
            </div>
            <div class="order-box">
                <p><strong>วันที่ส่ง:</strong> <?= date('d/m/Y', strtotime($req['requisition_date'])) ?></p>
                <p><strong>อ้างอิง SO:</strong> <?= htmlspecialchars($req['so_no'] ?? '-') ?></p>
                <p><strong>อ้างอิง PO:</strong> <?= htmlspecialchars($req['po_no'] ?? '-') ?></p>
                <p><strong>จัดส่งโดย:</strong> <?= htmlspecialchars($req['shipping_method'] ?? '-') ?></p>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50px;">ลำดับ</th>
                    <th>รายการสินค้า</th>
                    <th style="width: 100px;">จำนวน</th>
                    <th style="width: 100px;">หน่วย</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                while ($item = mysqli_fetch_assoc($res_items)): 
                ?>
                <tr>
                    <td align="center"><?= $i++ ?></td>
                    <td><?= htmlspecialchars($item['product_name']) ?> (<?= htmlspecialchars($item['sku']) ?>)</td>
                    <td align="right"><?= number_format($item['qty'], 2) ?></td>
                    <td align="center"><?= htmlspecialchars($item['unit'] ?? 'หน่วย') ?></td>
                </tr>
                <?php endwhile; ?>
                <?php for($j=$i; $j<=10; $j++): ?>
                <tr>
                    <td align="center" style="height: 30px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="footer-section">
            <div class="sig-box">
                <p><strong>ผู้รับสินค้า</strong></p>
                <div class="sig-line"></div>
                <p>วันที่ ........../........../..........</p>
            </div>
            <div class="sig-box">
                <p><strong>ผู้ส่งสินค้า / คนขับรถ</strong></p>
                <div class="sig-line"></div>
                <p>วันที่ ........../........../..........</p>
            </div>
            <div class="sig-box">
                <p><strong>ผู้อนุมัติ</strong></p>
                <div class="sig-line"></div>
                <p>วันที่ ........../........../..........</p>
            </div>
        </div>

        <div class="no-print" style="margin-top: 40px; text-align: center; border-top: 1px solid #ddd; padding-top: 20px;">
            <button onclick="window.print()" style="background: #e91e63; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold;">
                <i class="fas fa-print"></i> พิมพ์ใบส่งสินค้า
            </button>
            <button onclick="window.close()" style="margin-left: 10px; background: #607d8b; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">ปิด</button>
        </div>
    </div>
</body>
</html>

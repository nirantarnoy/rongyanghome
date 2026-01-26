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
$comp_sql = "SELECT * FROM companies WHERE id = ?";
$comp_stmt = mysqli_prepare($conn, $comp_sql);
mysqli_stmt_bind_param($comp_stmt, "i", $company_id);
mysqli_stmt_execute($comp_stmt);
$company = mysqli_fetch_assoc(mysqli_stmt_get_result($comp_stmt));

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเบิกสินค้า - <?= htmlspecialchars($req['req_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #eee;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-info h1 {
            margin: 0;
            font-size: 24px;
            color: #111;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 22px;
            color: #111;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }
        td {
            border: 1px solid #dee2e6;
            padding: 10px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 50px;
            text-align: center;
        }
        .signature-box {
            border-top: 1px solid #333;
            padding-top: 10px;
            margin-top: 40px;
        }
        @media print {
            body { padding: 0; }
            .print-container { border: none; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align: center; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #10B981; color: white; border: none; border-radius: 5px; cursor: pointer; font-family: 'Prompt';">
        พิมพ์เอกสาร
    </button>
</div>

<div class="print-container">
    <div class="header">
        <div class="company-info">
            <h1><?= htmlspecialchars($company['name'] ?? 'RONGYANG HOME') ?></h1>
            <p><?= nl2br(htmlspecialchars($company['address'] ?? '')) ?></p>
            <p>โทร: <?= htmlspecialchars($company['phone'] ?? '-') ?></p>
        </div>
        <div class="doc-title">
            <h2>ใบเบิกสินค้า</h2>
            <p><strong>เลขที่:</strong> <?= htmlspecialchars($req['req_no']) ?></p>
            <p><strong>วันที่:</strong> <?= date('d/m/Y', strtotime($req['requisition_date'])) ?></p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h3>ข้อมูลลูกค้า / สถานที่จัดส่ง</h3>
            <p><strong>ชื่อลูกค้า:</strong> <?= htmlspecialchars($req['customer_name']) ?></p>
            <p><strong>ที่อยู่:</strong> <?= nl2br(htmlspecialchars($req['shipping_address'] ?? '-')) ?></p>
            <p><strong>เบอร์โทร:</strong> <?= htmlspecialchars($req['phone'] ?? '-') ?></p>
        </div>
        <div class="info-box">
            <h3>รายละเอียดเพิ่มเติม</h3>
            <p><strong>ผู้เบิก:</strong> <?= htmlspecialchars($req['requester_name'] ?? '-') ?></p>
            <p><strong>เลขที่ PO:</strong> <?= htmlspecialchars($req['po_no'] ?? '-') ?></p>
            <p><strong>เลขที่ SO:</strong> <?= htmlspecialchars($req['so_no'] ?? '-') ?></p>
            <p><strong>การจัดส่ง:</strong> <?= htmlspecialchars($req['shipping_method'] ?? '-') ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px; text-align: center;">ลำดับ</th>
                <th>รายการสินค้า</th>
                <th style="width: 120px;">คลังสินค้า</th>
                <th style="width: 100px; text-align: right;">จำนวน</th>
                <th style="width: 80px;">หน่วย</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            while ($item = mysqli_fetch_assoc($res_items)): 
            ?>
            <tr>
                <td style="text-align: center;"><?= $i++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($item['product_name']) ?></strong><br>
                    <small style="color: #666;">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                </td>
                <td><?= htmlspecialchars($item['warehouse_name'] ?? '-') ?></td>
                <td style="text-align: right; font-weight: 600;"><?= number_format($item['qty']) ?></td>
                <td><?= htmlspecialchars($item['unit']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="footer-grid">
        <div>
            <div class="signature-box">
                ผู้เบิกสินค้า<br>
                วันที่ ____/____/____
            </div>
        </div>
        <div>
            <div class="signature-box">
                ผู้จ่ายสินค้า / คลังสินค้า<br>
                วันที่ ____/____/____
            </div>
        </div>
        <div>
            <div class="signature-box">
                ผู้อนุมัติ<br>
                วันที่ ____/____/____
            </div>
        </div>
    </div>
</div>

<script>
    // Auto print if needed
    // window.onload = function() { window.print(); }
</script>

</body>
</html>

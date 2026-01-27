<?php
require '../auth_check.php';
require '../config.php';

$company_id = $_SESSION['company_id'];
$tab = $_GET['tab'] ?? 'quotation';

// Get total quotations for counter
$count_sql = "SELECT COUNT(*) as total FROM quotations WHERE company_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $company_id);
mysqli_stmt_execute($count_stmt);
$count_res = mysqli_stmt_get_result($count_stmt);
$total_quotations = mysqli_fetch_assoc($count_res)['total'] ?? 0;

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเอกสาร - RONGYANG HOME</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="documents.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">

    <div class="doc-header">
        <div class="absolute top-4 right-4 flex items-center gap-3 bg-white/10 backdrop-blur-md p-2 rounded-xl border border-white/20" style="position: absolute; top: 1rem; right: 1rem; display: flex; align-items: center; gap: 0.75rem; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 0.5rem; border-radius: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.2);">
            <div class="text-right hidden sm:block">
                <div style="font-size: 0.625rem; font-weight: 700; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.05em; color: white;">ผู้ใช้งาน</div>
                <div style="font-size: 0.875rem; font-weight: 700; color: white;"><?= $_SESSION['user_login'] ?></div>
            </div>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="../index.php" style="background: rgba(255, 255, 255, 0.2); color: white; padding: 0.5rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: inline-flex; align-items: center; justify-content: center;" title="กลับระบบหลัก">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </a>
            <?php endif; ?>
            <a href="../logout.php" style="background: rgba(239, 68, 68, 0.8); color: white; padding: 0.5rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: inline-flex; align-items: center; justify-content: center;" title="ออกจากระบบ">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            </a>
        </div>
        <div class="text-center text-white">
            <div class="flex justify-center mb-4 text-4xl">
                <span>📄</span>
            </div>
            <h1 class="text-3xl font-bold mb-2">ระบบจัดการเอกสาร</h1>
            <p class="text-lg opacity-90"><?= $_SESSION['company_name'] ?></p>
        </div>
    </div>

    <div class="container mx-auto px-4 max-w-6xl">
        
        <div class="doc-nav">
            <a href="?tab=quotation" class="nav-tab <?= $tab == 'quotation' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span class="text-sm font-bold">ใบเสนอราคา</span>
            </a>
            <a href="?tab=invoice" class="nav-tab <?= $tab == 'invoice' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i>
                <span class="text-sm font-bold">ใบแจ้งหนี้</span>
            </a>
            <a href="?tab=receipt" class="nav-tab <?= $tab == 'receipt' ? 'active' : '' ?>">
                <i class="fas fa-receipt"></i>
                <span class="text-sm font-bold">ใบเสร็จรับเงิน</span>
            </a>
            <a href="?tab=purchase_order" class="nav-tab <?= $tab == 'purchase_order' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i>
                <span class="text-sm font-bold">ใบสั่งซื้อ</span>
            </a>
            <a href="?tab=sales_order" class="nav-tab <?= $tab == 'sales_order' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i>
                <span class="text-sm font-bold">ใบสั่งขาย</span>
            </a>
            <a href="?tab=goods_receipt" class="nav-tab <?= $tab == 'goods_receipt' ? 'active' : '' ?>">
                <i class="fas fa-box-open"></i>
                <span class="text-sm font-bold">ใบรับสินค้า</span>
            </a>
            <a href="?tab=settings" class="nav-tab <?= $tab == 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span class="text-sm font-bold">ตั้งค่า</span>
            </a>
            <a href="../dashboard.php" class="nav-tab">
                <i class="fas fa-home"></i>
                <span class="text-sm font-bold">หน้าหลัก</span>
            </a>
        </div>

        <div class="tab-content pb-12">
            <?php
            switch ($tab) {
                case 'quotation':
                    include 'tabs/quotation_tab.php';
                    break;
                case 'invoice':
                    include 'tabs/invoice_tab.php';
                    break;
                case 'receipt':
                    include 'tabs/receipt_tab.php';
                    break;
                case 'purchase_order':
                    include 'tabs/purchase_order_tab.php';
                    break;
                case 'sales_order':
                    include 'tabs/sales_order_tab.php';
                    break;
                case 'goods_receipt':
                    include 'tabs/goods_receipt_tab.php';
                    break;
                case 'settings':
                    include 'tabs/runno_tab.php';
                    break;
                default:
                    include 'tabs/quotation_tab.php';
                    break;
            }
            ?>
        </div>
    </div>

    <script>
        // Global AJAX setup for company_id
        $.ajaxSetup({
            data: { company_id: <?= $company_id ?> }
        });
    </script>
</body>
</html>

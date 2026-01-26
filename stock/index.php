<?php
require '../auth_check.php';
require '../config.php';

$company_id = $_SESSION['company_id'];
$tab = $_GET['tab'] ?? 'warehouse';

// Get total items for counter
$count_sql = "SELECT COUNT(*) as total FROM stock_products WHERE company_id = ?";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($count_stmt, "i", $company_id);
mysqli_stmt_execute($count_stmt);
$count_res = mysqli_stmt_get_result($count_stmt);
$total_items = mysqli_fetch_assoc($count_res)['total'] ?? 0;

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบสต็อกและการผลิต - RONGYANG HOME</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="stock.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <div class="stock-header" style="background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); border-radius: 1.5rem; margin: 1rem; padding: 2rem 3rem; box-shadow: 0 10px 40px rgba(124, 58, 237, 0.3); position: relative;">
        <div class="absolute top-4 right-4 flex items-center gap-3 bg-white/10 backdrop-blur-md p-2 rounded-xl border border-white/20" style="position: absolute; top: 1rem; right: 1rem; display: flex; align-items: center; gap: 0.75rem; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 0.5rem; border-radius: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.2);">
            <div class="text-right hidden sm:block" style="text-align: right; display: none;">
                <div style="font-size: 0.625rem; font-weight: 700; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.05em; color: white;">ผู้ใช้งาน</div>
                <div style="font-size: 0.875rem; font-weight: 700; color: white;"><?= $_SESSION['user_login'] ?></div>
            </div>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="../index.php" style="background: rgba(255, 255, 255, 0.2); color: white; padding: 0.5rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: inline-flex; align-items: center; justify-content: center;" title="กลับระบบหลัก" onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </a>
            <?php endif; ?>
            <a href="../logout.php" style="background: rgba(239, 68, 68, 0.8); color: white; padding: 0.5rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.2s; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: inline-flex; align-items: center; justify-content: center;" title="ออกจากระบบ" onmouseover="this.style.background='rgba(220, 38, 38, 1)'" onmouseout="this.style.background='rgba(239, 68, 68, 0.8)'">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            </a>
        </div>
        <div style="text-align: center; color: white;">
            <div style="display: flex; justify-content: center; margin-bottom: 1rem; font-size: 2.5rem;">
                <span>📦</span>
            </div>
            <h1 style="font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem; letter-spacing: -0.025em;">ระบบจัดการสต็อกและการผลิต</h1>
            <p style="font-size: 1rem; opacity: 0.9; font-weight: 500;"><?= $_SESSION['company_name'] ?></p>
        </div>
    </div>

    <style>
        @media (min-width: 640px) {
            .text-right.hidden.sm\:block {
                display: block !important;
            }
        }
    </style>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
        
        <div class="stock-nav">
            <a href="?tab=warehouse" class="nav-tab <?= $tab == 'warehouse' ? 'active' : '' ?>">
                <i class="fas fa-boxes"></i>
                <span>สินค้า</span>
            </a>
            <a href="?tab=warehouses_manage" class="nav-tab <?= $tab == 'warehouses_manage' ? 'active' : '' ?>">
                <i class="fas fa-warehouse"></i>
                <span>จัดการคลังสินค้า</span>
            </a>
            <a href="?tab=transactions" class="nav-tab <?= $tab == 'transactions' ? 'active' : '' ?>">
                <i class="fas fa-exchange-alt"></i>
                <span>รับเข้า-เบิกออก</span>
            </a>
            <a href="?tab=production" class="nav-tab <?= $tab == 'production' ? 'active' : '' ?>">
                <i class="fas fa-industry"></i>
                <span>สั่งผลิต</span>
            </a>
            <a href="?tab=requisitions" class="nav-tab <?= $tab == 'requisitions' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i>
                <span>ใบเบิก</span>
            </a>
            <a href="?tab=reports" class="nav-tab <?= $tab == 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>รายงาน</span>
            </a>
            <a href="../dashboard.php" class="nav-tab">
                <i class="fas fa-home"></i>
                <span>หน้าหลัก</span>
            </a>
        </div>

        <div class="tab-content">
            <?php
            switch ($tab) {
                case 'warehouse':
                    include 'tabs/warehouse.php';
                    break;
                case 'warehouses_manage':
                    include 'tabs/warehouses_manage.php';
                    break;
                case 'transactions':
                    include 'tabs/transactions.php';
                    break;
                case 'production':
                    include 'tabs/production.php';
                    break;
                case 'requisitions':
                    include 'tabs/requisitions.php';
                    break;
                case 'reports':
                    include 'tabs/reports.php';
                    break;
                default:
                    include 'tabs/warehouse.php';
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

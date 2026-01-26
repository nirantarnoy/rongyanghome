<?php
require 'auth_check.php';
include 'config.php';

$user_role = $_SESSION['user_role'] ?? 'user';
$company_id = $_SESSION['company_id'];

// Only admin can manage year settings
if ($user_role !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get current active year
$year_sql = "SELECT active_year FROM year_settings WHERE company_id = ?";
$year_stmt = mysqli_prepare($conn, $year_sql);
mysqli_stmt_bind_param($year_stmt, "i", $company_id);
mysqli_stmt_execute($year_stmt);
$year_res = mysqli_stmt_get_result($year_stmt);
$year_data = mysqli_fetch_assoc($year_res);
$active_year = $year_data['active_year'] ?? date('Y');

// Get available years from data
$available_years = [];
$current_year = (int)date('Y');
for ($i = $current_year - 5; $i <= $current_year + 1; $i++) {
    $available_years[] = $i;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการปีทำงาน - RONGYANG HOME</title>
    <script src="assets/js/tailwindcss.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php include 'navbar.php'; ?>

<div class="container max-w-4xl mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            จัดการปีทำงาน
        </h1>
        <p class="text-gray-500 mt-1">กำหนดปีที่ใช้งานในระบบ ข้อมูลทุก module จะแยกตามปีที่เลือก</p>
    </div>

    <!-- Current Year Display -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-8 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-indigo-100 text-sm font-medium mb-2">ปีที่ใช้งานในปัจจุบัน</p>
                <h2 class="text-5xl font-bold"><?= $active_year ?></h2>
                <p class="text-indigo-100 text-sm mt-2">ข้อมูลทั้งหมดในระบบจะแสดงเฉพาะปีนี้</p>
            </div>
            <div class="text-6xl opacity-20">
                📅
            </div>
        </div>
    </div>

    <!-- Year Selection -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h3 class="text-xl font-bold text-gray-800 mb-6">เลือกปีที่ต้องการใช้งาน</h3>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <?php foreach ($available_years as $year): ?>
            <button onclick="changeYear(<?= $year ?>)" 
                    class="year-btn p-6 rounded-xl border-2 transition-all duration-200 <?= $year == $active_year ? 'border-indigo-600 bg-indigo-50 text-indigo-700 font-bold' : 'border-gray-200 hover:border-indigo-300 hover:bg-gray-50' ?>">
                <div class="text-3xl font-bold"><?= $year ?></div>
                <?php if ($year == $active_year): ?>
                <div class="text-xs mt-2 text-indigo-600">✓ ใช้งานอยู่</div>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-1">หมายเหตุ:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700">
                        <li>การเปลี่ยนปีจะมีผลกับทุก module (Stock, Projects, Company Transaction)</li>
                        <li>ข้อมูลของปีอื่นๆ จะยังคงอยู่ แต่จะไม่แสดงในระบบ</li>
                        <li>คุณสามารถสลับกลับมาดูข้อมูลปีก่อนหน้าได้ตลอดเวลา</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics by Year -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mt-6">
        <h3 class="text-xl font-bold text-gray-800 mb-6">สถิติข้อมูลแยกตามปี</h3>
        
        <div class="space-y-3">
            <?php
            foreach ($available_years as $year) {
                // Count data for each year
                $counts = [];
                
                // Stock products
                $stock_sql = "SELECT COUNT(*) as count FROM stock_products WHERE company_id = ? AND year = ?";
                $stock_stmt = mysqli_prepare($conn, $stock_sql);
                if ($stock_stmt) {
                    mysqli_stmt_bind_param($stock_stmt, "ii", $company_id, $year);
                    mysqli_stmt_execute($stock_stmt);
                    $stock_res = mysqli_stmt_get_result($stock_stmt);
                    $counts['stock'] = mysqli_fetch_assoc($stock_res)['count'] ?? 0;
                } else {
                    $counts['stock'] = 0;
                }
                
                // Quotations
                $quot_sql = "SELECT COUNT(*) as count FROM quotations WHERE company_id = ? AND year = ?";
                $quot_stmt = mysqli_prepare($conn, $quot_sql);
                if ($quot_stmt) {
                    mysqli_stmt_bind_param($quot_stmt, "ii", $company_id, $year);
                    mysqli_stmt_execute($quot_stmt);
                    $quot_res = mysqli_stmt_get_result($quot_stmt);
                    $counts['quotations'] = mysqli_fetch_assoc($quot_res)['count'] ?? 0;
                } else {
                    $counts['quotations'] = 0;
                }
                
                // Projects
                $proj_sql = "SELECT COUNT(*) as count FROM projects_list WHERE company_id = ? AND year = ?";
                $proj_stmt = mysqli_prepare($conn, $proj_sql);
                if ($proj_stmt) {
                    mysqli_stmt_bind_param($proj_stmt, "ii", $company_id, $year);
                    mysqli_stmt_execute($proj_stmt);
                    $proj_res = mysqli_stmt_get_result($proj_stmt);
                    $counts['projects'] = mysqli_fetch_assoc($proj_res)['count'] ?? 0;
                } else {
                    $counts['projects'] = 0;
                }
                
                $total = $counts['stock'] + $counts['quotations'] + $counts['projects'];
            ?>
            <div class="flex items-center justify-between p-4 rounded-lg <?= $year == $active_year ? 'bg-indigo-50 border border-indigo-200' : 'bg-gray-50' ?>">
                <div class="flex items-center gap-4">
                    <div class="text-2xl font-bold <?= $year == $active_year ? 'text-indigo-700' : 'text-gray-700' ?>">
                        <?= $year ?>
                    </div>
                    <?php if ($year == $active_year): ?>
                    <span class="px-2 py-1 bg-indigo-600 text-white text-xs font-bold rounded-full">ใช้งานอยู่</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-6 text-sm">
                    <div class="text-center">
                        <div class="text-gray-500 text-xs">Stock</div>
                        <div class="font-bold text-gray-800"><?= number_format($counts['stock']) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-gray-500 text-xs">Quotations</div>
                        <div class="font-bold text-gray-800"><?= number_format($counts['quotations']) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-gray-500 text-xs">Projects</div>
                        <div class="font-bold text-gray-800"><?= number_format($counts['projects']) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-gray-500 text-xs">รวม</div>
                        <div class="font-bold text-indigo-600"><?= number_format($total) ?></div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<script>
function changeYear(year) {
    Swal.fire({
        title: 'ยืนยันการเปลี่ยนปี?',
        html: `คุณต้องการเปลี่ยนปีทำงานเป็น <b>${year}</b> ใช่หรือไม่?<br><small class="text-gray-500">ระบบจะแสดงข้อมูลเฉพาะปีที่เลือก</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, เปลี่ยนเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'year_action.php',
                type: 'POST',
                data: { action: 'change_year', year: year },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
                }
            });
        }
    });
}
</script>

</body>
</html>

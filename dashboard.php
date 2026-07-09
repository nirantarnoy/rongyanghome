<?php
require 'auth_check.php';
require 'config.php';
require 'functions.php';

// Auto-fix schema for missing column 'project_value' on production server
try {
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM projects_list LIKE 'project_value'");
    if ($check_col && mysqli_num_rows($check_col) == 0) {
        mysqli_query($conn, "ALTER TABLE projects_list ADD project_value DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    }
} catch (Exception $e) {
    // Ignore errors to prevent breaking the page
}

$user_role = $_SESSION['user_role'] ?? 'user';

// Only admin can view dashboard
if ($user_role !== 'admin') {
    header("Location: index.php");
    exit();
}

$company_id = $_SESSION['company_id'] ?? 0;
$active_year = $_SESSION['active_year'] ?? date('Y');

// Get Date Range
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to month start
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$is_today = ($start_date == date('Y-m-d') && $end_date == date('Y-m-d'));

/**
 * Function to get counts and sums for a specific module
 */
function getModuleStats($conn, $module_type, $company_id, $start, $end) {
    $sql = "SELECT 
        SUM(CASE WHEN c.direction = 'income' THEN t.amount ELSE 0 END) as total_income,
        SUM(CASE WHEN c.direction = 'expense' THEN t.amount ELSE 0 END) as total_expense,
        COUNT(t.id) as total_transactions
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.module_type = ? AND t.company_id = ? AND t.transaction_date BETWEEN ? AND ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiss", $module_type, $company_id, $start, $end);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
    
    return [
        'income' => (float)($data['total_income'] ?? 0),
        'expense' => (float)($data['total_expense'] ?? 0),
        'profit' => (float)(($data['total_income'] ?? 0) - ($data['total_expense'] ?? 0)),
        'count' => (int)($data['total_transactions'] ?? 0)
    ];
}

/**
 * Function to get trend data for a specific module
 */
function getModuleTrend($conn, $module_type, $company_id, $start, $end) {
    $sql = "SELECT 
        t.transaction_date as date, 
        SUM(CASE WHEN c.direction = 'income' THEN t.amount ELSE 0 END) as income,
        SUM(CASE WHEN c.direction = 'expense' THEN t.amount ELSE 0 END) as expense
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.module_type = ? AND t.company_id = ? AND t.transaction_date BETWEEN ? AND ?
    GROUP BY t.transaction_date
    ORDER BY t.transaction_date ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiss", $module_type, $company_id, $start, $end);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $labels = [];
    $income = [];
    $expense = [];
    
    $temp_data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $temp_data[$row['date']] = $row;
    }
    
    $current = strtotime($start);
    $last = strtotime($end);
    while ($current <= $last) {
        $d = date('Y-m-d', $current);
        $labels[] = date('d/m', $current);
        $income[] = (float)($temp_data[$d]['income'] ?? 0);
        $expense[] = (float)($temp_data[$d]['expense'] ?? 0);
        $current = strtotime('+1 day', $current);
    }
    
    return ['labels' => $labels, 'income' => $income, 'expense' => $expense];
}

/**
 * Function to get category distribution for a specific module
 */
function getModuleCategoryStats($conn, $module_type, $company_id, $start, $end) {
    $sql = "SELECT c.name, SUM(t.amount) as total
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.module_type = ? AND t.company_id = ? AND c.direction = 'expense' AND t.transaction_date BETWEEN ? AND ?
    GROUP BY c.id, c.name
    ORDER BY total DESC
    LIMIT 5";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiss", $module_type, $company_id, $start, $end);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $labels = [];
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $labels[] = $row['name'];
        $data[] = (float)$row['total'];
    }
    
    return ['labels' => $labels, 'data' => $data];
}

/**
 * Function to get pending income (sum of project_value - income for each project, if > 0)
 */
function getPendingIncome($conn, $module_type, $company_id) {
    // Get total project value
    $sql_pv = "SELECT SUM(project_value) as total_project_value FROM projects_list WHERE module_type = ? AND company_id = ?";
    $stmt_pv = mysqli_prepare($conn, $sql_pv);
    mysqli_stmt_bind_param($stmt_pv, "ii", $module_type, $company_id);
    mysqli_stmt_execute($stmt_pv);
    $res_pv = mysqli_stmt_get_result($stmt_pv);
    $total_project_value = (float)(mysqli_fetch_assoc($res_pv)['total_project_value'] ?? 0);

    // Get total income
    $sql_income = "SELECT SUM(CASE WHEN c.direction = 'income' THEN t.amount ELSE 0 END) as total_income
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.module_type = ? AND t.company_id = ?";
    $stmt_income = mysqli_prepare($conn, $sql_income);
    mysqli_stmt_bind_param($stmt_income, "ii", $module_type, $company_id);
    mysqli_stmt_execute($stmt_income);
    $res_income = mysqli_stmt_get_result($stmt_income);
    $total_income = (float)(mysqli_fetch_assoc($res_income)['total_income'] ?? 0);
    
    return $total_project_value - $total_income;
}

/**
 * Function to get estimated next month expense
 */
function getEstimatedNextMonthExpense($conn, $module_type, $company_id) {
    $current_month = (int)date('n');
    $current_year = date('Y');
    
    if ($current_month == 1) {
        $months_passed = 1;
        $start_date = "$current_year-01-01";
        $end_date = "$current_year-01-31";
    } else {
        $months_passed = $current_month - 1;
        $start_date = "$current_year-01-01";
        $end_date = date('Y-m-t', strtotime("$current_year-".($current_month-1)."-01"));
    }
    
    $sql = "SELECT SUM(t.amount) as total_expense
            FROM transactions t
            JOIN categories c ON t.category_id = c.id
            WHERE t.module_type = ? AND t.company_id = ? AND c.direction = 'expense' 
            AND t.transaction_date BETWEEN ? AND ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiss", $module_type, $company_id, $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $total_expense = (float)(mysqli_fetch_assoc($res)['total_expense'] ?? 0);
    
    return $total_expense / $months_passed;
}

/**
 * Function to get outstanding debt (sum of project_value)
 */
function getOutstandingDebt($conn, $module_type, $company_id) {
    $sql = "SELECT SUM(project_value) as total_debt FROM projects_list WHERE module_type = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $module_type, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    return (float)(mysqli_fetch_assoc($res)['total_debt'] ?? 0);
}

// 1. Module 2 Stats (Bansakthong / Personal)
$stats_m2 = getModuleStats($conn, 2, $company_id, $start_date, $end_date);
$trend_m2 = getModuleTrend($conn, 2, $company_id, $start_date, $end_date);
$cat_m2 = getModuleCategoryStats($conn, 2, $company_id, $start_date, $end_date);
$estimated_expense_m2 = getEstimatedNextMonthExpense($conn, 2, $company_id);
$outstanding_debt_m2 = getOutstandingDebt($conn, 2, $company_id);

// 2. Module 1 Stats (Projects)
$stats_m1 = getModuleStats($conn, 1, $company_id, $start_date, $end_date);
$trend_m1 = getModuleTrend($conn, 1, $company_id, $start_date, $end_date);
$cat_m1 = getModuleCategoryStats($conn, 1, $company_id, $start_date, $end_date);
$pending_income_m1 = getPendingIncome($conn, 1, $company_id);

// 3. Project Count
$sql_projects = "SELECT COUNT(*) as total_projects FROM projects_list WHERE company_id = ?";
$stmt_projects = mysqli_prepare($conn, $sql_projects);
mysqli_stmt_bind_param($stmt_projects, "i", $company_id);
mysqli_stmt_execute($stmt_projects);
$projects_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_projects))['total_projects'] ?? 0;

// 4. Recent Transactions (Overall)
$sql_recent = "SELECT t.*, c.name as category_name, c.icon as category_icon, c.direction, p.project_name
FROM transactions t
LEFT JOIN categories c ON t.category_id = c.id
LEFT JOIN projects_list p ON t.project_id = p.id
WHERE t.company_id = ? AND t.transaction_date BETWEEN ? AND ?
ORDER BY t.transaction_date DESC, t.id DESC
LIMIT 10";
$stmt_recent = mysqli_prepare($conn, $sql_recent);
mysqli_stmt_bind_param($stmt_recent, "iss", $company_id, $start_date, $end_date);
mysqli_stmt_execute($stmt_recent);
$recent_res = mysqli_stmt_get_result($stmt_recent);
$recent_transactions = [];
while ($row = mysqli_fetch_assoc($recent_res)) {
    $recent_transactions[] = $row;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .section-box {
            background: #fff;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

<?php include 'navbar.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Executive Dashboard</h1>
            <p class="text-slate-500 mt-1">ภาพรวมรายรับ-รายจ่ายประจำวันที่ <?= date('d/m/Y') ?></p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-4">
            <div class="flex gap-2">
                <a href="dashboard.php?start_date=<?= date('Y-m-d') ?>&end_date=<?= date('Y-m-d') ?>" class="px-4 py-2 bg-white text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-slate-50 hover:text-indigo-600 transition-all">วันนี้</a>
                <a href="dashboard.php?start_date=<?= date('Y-m-d', strtotime('-6 days')) ?>&end_date=<?= date('Y-m-d') ?>" class="px-4 py-2 bg-white text-slate-600 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-slate-50 hover:text-indigo-600 transition-all">7 Days</a>
            </div>
            <form method="GET" class="flex items-center gap-2 bg-white p-1 rounded-xl border border-slate-200 shadow-sm">
                <input type="date" name="start_date" value="<?= $start_date ?>" class="text-sm border-none focus:ring-0 text-slate-600 bg-transparent px-3">
                <span class="text-slate-400">-</span>
                <input type="date" name="end_date" value="<?= $end_date ?>" class="text-sm border-none focus:ring-0 text-slate-600 bg-transparent px-3">
                <button type="submit" class="bg-indigo-600 text-white p-2 rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Section 1: รายรับรายจ่ายบ้านสักทอง -->
    <div class="section-box">
        <div class="flex items-center gap-4 mb-8">
            <div class="p-4 bg-emerald-500 rounded-2xl text-white shadow-lg shadow-emerald-200">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"></path></svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-800">รายรับรายจ่ายบ้านสักทอง</h2>
                <p class="text-slate-500">บันทึกรายรับ-รายจ่ายโรงงาน</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- KPIs Module 2 -->
            <?php renderKPICard("รายรับรวม", $stats_m2['income'], "emerald"); ?>
            <?php renderKPICard("รายจ่ายรวม", $stats_m2['expense'], "red"); ?>
            <?php renderKPICard("กำไร/ขาดทุน", $stats_m2['profit'], "indigo"); ?>
            <?php renderKPICard("จำนวนรายการ", $stats_m2['count'], "blue", "รายการ"); ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-red-200 shadow-sm relative group cursor-help">
                <span class="text-red-600 font-bold text-lg">รายจ่ายคาดการณ์เดือนหน้า</span>
                <span class="text-xl font-bold text-red-600 px-8 py-2 border-2 border-red-500 rounded-xl border-dashed min-w-[200px] text-center"><?= number_format($estimated_expense_m2, 2) ?> บาท</span>
                
                <!-- Tooltip -->
                <div class="absolute left-0 -bottom-14 hidden group-hover:block w-max bg-gray-800 text-white text-xs p-2 rounded z-10 shadow-lg">
                    (เอายอดรายจ่ายรวมตั้งแต่ต้นปีถึงเดือนที่แล้ว หารด้วย จำนวนเดือนที่ผ่านไปแล้ว)
                </div>
            </div>
            <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-red-200 shadow-sm relative group cursor-help">
                <span class="text-red-600 font-bold text-lg">รายจ่ายคงค้างเงินกู้/OD</span>
                <span class="text-xl font-bold text-red-600 px-8 py-2 border-2 border-red-500 rounded-xl border-dashed min-w-[200px] text-center"><?= number_format($outstanding_debt_m2, 2) ?> บาท</span>
                
                <!-- Tooltip -->
                <div class="absolute left-0 -bottom-10 hidden group-hover:block w-max bg-gray-800 text-white text-xs p-2 rounded z-10 shadow-lg">
                    เอายอดหนี้ค้างจ่าย/ยอดทุนที่เหลือของแต่ละโครงการมารวมกัน
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <span class="w-2 h-6 bg-indigo-500 rounded-full"></span> แนวโน้มรายรับ-รายจ่าย
                </h3>
                <div class="h-80"><canvas id="trendChartM2"></canvas></div>
            </div>
            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <span class="w-2 h-6 bg-orange-500 rounded-full"></span> สัดส่วนรายจ่ายสูงสุด
                </h3>
                <div class="h-64"><canvas id="catChartM2"></canvas></div>
                <div class="mt-4 space-y-2">
                    <?php renderCatList($cat_m2); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 2: โปรเจคบ้าน & เฟอร์นิเจอร์ -->
    <div class="section-box border-orange-100">
        <div class="flex items-center gap-4 mb-8">
            <div class="p-4 bg-orange-500 rounded-2xl text-white shadow-lg shadow-orange-200">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-800">โปรเจคบ้าน & เฟอร์นิเจอร์</h2>
                <p class="text-slate-500">บันทึกรายรับ-รายจ่ายตามโครงการ</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- KPIs Module 1 -->
            <?php renderKPICard("รายรับรวม", $stats_m1['income'], "emerald"); ?>
            <?php renderKPICard("รายจ่ายรวม", $stats_m1['expense'], "red"); ?>
            <?php renderKPICard("กำไร/ขาดทุน", $stats_m1['profit'], "indigo"); ?>
            <?php renderKPICard("จำนวนรายการ", $stats_m1['count'], "blue", "รายการ", "โครงการทั้งหมด: $projects_count โครงการ"); ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-red-200 shadow-sm relative group cursor-help">
                <span class="text-red-600 font-bold text-lg">รายได้ค้างรับรวม</span>
                <span class="text-xl font-bold text-red-600 px-8 py-2 border-2 border-red-500 rounded-xl border-dashed min-w-[200px] text-center"><?= number_format($pending_income_m1, 2) ?> บาท</span>
                
                <!-- Tooltip -->
                <div class="absolute left-0 -bottom-10 hidden group-hover:block w-max bg-gray-800 text-white text-xs p-2 rounded z-10 shadow-lg">
                    มูลค่าโครงการรวมทั้งหมด - รายรับรวมทั้งหมด
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <span class="w-2 h-6 bg-indigo-500 rounded-full"></span> แนวโน้มรายรับ-รายจ่าย
                </h3>
                <div class="h-80"><canvas id="trendChartM1"></canvas></div>
            </div>
            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <span class="w-2 h-6 bg-orange-500 rounded-full"></span> สัดส่วนรายจ่ายสูงสุด
                </h3>
                <div class="h-64"><canvas id="catChartM1"></canvas></div>
                <div class="mt-4 space-y-2">
                    <?php renderCatList($cat_m1); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="section-box">
        <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
            <span class="p-2 bg-blue-100 rounded-lg text-blue-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </span>
            รายการล่าสุด
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-600 font-medium">
                    <tr>
                        <th class="px-6 py-4">วันที่</th>
                        <th class="px-6 py-4">หมวดหมู่</th>
                        <th class="px-6 py-4">โครงการ / รายละเอียด</th>
                        <th class="px-6 py-4 text-right">จำนวนเงิน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recent_transactions as $rt): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 text-slate-500"><?= date('d/m/Y', strtotime($rt['transaction_date'])) ?></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="text-xl"><?= $rt['category_icon'] ?></span>
                                <span class="font-medium text-slate-700"><?= htmlspecialchars($rt['category_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-slate-600"><?= htmlspecialchars($rt['project_name'] ?: ($rt['module_type'] == 2 ? 'โรงงาน/ส่วนตัว' : '-')) ?></div>
                            <?php if($rt['note']): ?>
                                <div class="text-xs text-slate-400 truncate max-w-xs"><?= htmlspecialchars($rt['note']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right font-bold <?= $rt['direction'] == 'income' ? 'text-emerald-600' : 'text-red-600' ?>">
                            <?= ($rt['direction'] == 'income' ? '+' : '-') . number_format($rt['amount'], 2) ?> ฿
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
/**
 * Helper to render KPI Card
 */
function renderKPICard($title, $value, $color, $unit = "฿", $sub = "จากช่วงเวลาที่เลือก") {
    $bg = "bg-$color-50";
    $text = "text-$color-600";
    ?>
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?= $title ?></p>
                <h3 class="text-2xl font-extrabold <?= $text ?> mt-2"><?= number_format($value, ($unit == "฿" ? 2 : 0)) ?> <span class="text-lg font-bold"><?= $unit ?></span></h3>
            </div>
            <div class="<?= $bg ?> p-3 rounded-xl <?= $text ?>">
                <?php if($color == 'emerald') echo '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'; ?>
                <?php if($color == 'red') echo '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'; ?>
                <?php if($color == 'indigo') echo '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>'; ?>
                <?php if($color == 'blue') echo '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 002-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>'; ?>
            </div>
        </div>
        <div class="mt-4 text-xs text-slate-400 font-medium"><?= $sub ?></div>
    </div>
    <?php
}

function renderCatList($cat_data) {
    foreach ($cat_data['labels'] as $idx => $label): ?>
    <div class="flex justify-between items-center text-sm">
        <span class="text-slate-600 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" style="background-color: <?= ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16'][$idx] ?? '#cbd5e1' ?>;"></span>
            <?= htmlspecialchars($label) ?>
        </span>
        <span class="font-bold text-slate-800"><?= number_format($cat_data['data'][$idx]) ?> ฿</span>
    </div>
    <?php endforeach;
}
?>

<script>
const commonTrendOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top', labels: { font: { family: "'Prompt', sans-serif", weight: 'bold' } } },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleFont: { family: "'Prompt', sans-serif" },
            bodyFont: { family: "'Prompt', sans-serif" },
            padding: 12, cornerRadius: 8
        }
    },
    scales: {
        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family: "'Prompt', sans-serif" } } },
        x: { grid: { display: false }, ticks: { font: { family: "'Prompt', sans-serif" } } }
    }
};

const commonCatOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: { legend: { display: false } }
};

// Trend M2
new Chart(document.getElementById('trendChartM2'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_m2['labels']) ?>,
        datasets: [
            { label: 'รายรับ', data: <?= json_encode($trend_m2['income']) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', borderWidth: 3, tension: 0.4, fill: true },
            { label: 'รายจ่าย', data: <?= json_encode($trend_m2['expense']) ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', borderWidth: 3, tension: 0.4, fill: true }
        ]
    },
    options: commonTrendOptions
});

// Cat M2
new Chart(document.getElementById('catChartM2'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($cat_m2['labels']) ?>,
        datasets: [{
            data: <?= json_encode($cat_m2['data']) ?>,
            backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16'],
            borderWidth: 0, hoverOffset: 4
        }]
    },
    options: commonCatOptions
});

// Trend M1
new Chart(document.getElementById('trendChartM1'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_m1['labels']) ?>,
        datasets: [
            { label: 'รายรับ', data: <?= json_encode($trend_m1['income']) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', borderWidth: 3, tension: 0.4, fill: true },
            { label: 'รายจ่าย', data: <?= json_encode($trend_m1['expense']) ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', borderWidth: 3, tension: 0.4, fill: true }
        ]
    },
    options: commonTrendOptions
});

// Cat M1
new Chart(document.getElementById('catChartM1'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($cat_m1['labels']) ?>,
        datasets: [{
            data: <?= json_encode($cat_m1['data']) ?>,
            backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16'],
            borderWidth: 0, hoverOffset: 4
        }]
    },
    options: commonCatOptions
});
</script>

</body>
</html>

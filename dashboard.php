<?php
require 'auth_check.php';
require 'config.php';
require 'functions.php';

$user_role = $_SESSION['user_role'] ?? 'user';

// Only admin can view dashboard
if ($user_role !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get Date Range
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$is_today = ($start_date == date('Y-m-d') && $end_date == date('Y-m-d'));

// 1. Financial Summary (Based on Range)
$sql_finance = "SELECT 
    SUM(CASE WHEN c.direction = 'income' THEN t.amount ELSE 0 END) as total_income,
    SUM(CASE WHEN c.direction = 'expense' THEN t.amount ELSE 0 END) as total_expense,
    COUNT(t.id) as total_transactions
FROM transactions t
LEFT JOIN categories c ON t.category_id = c.id
WHERE t.transaction_date BETWEEN ? AND ?";
$stmt_finance = mysqli_prepare($conn, $sql_finance);
mysqli_stmt_bind_param($stmt_finance, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt_finance);
$res_finance = mysqli_stmt_get_result($stmt_finance);
$finance = mysqli_fetch_assoc($res_finance);

$total_income = $finance['total_income'] ?? 0;
$total_expense = $finance['total_expense'] ?? 0;
$total_profit = $total_income - $total_expense;
$total_transactions = $finance['total_transactions'] ?? 0;

// 2. Project Summary
$sql_projects = "SELECT COUNT(*) as total_projects FROM projects_list";
$res_projects = mysqli_query($conn, $sql_projects);
$projects_count = mysqli_fetch_assoc($res_projects)['total_projects'] ?? 0;

// 3. Financial Trend (Last 7 Days OR Range)
$trend_start = $start_date;
$trend_end = $end_date;
if ($is_today) {
    $trend_start = date('Y-m-d', strtotime('-6 days'));
}

$sql_trend = "SELECT 
    t.transaction_date as date, 
    SUM(CASE WHEN c.direction = 'income' THEN t.amount ELSE 0 END) as income,
    SUM(CASE WHEN c.direction = 'expense' THEN t.amount ELSE 0 END) as expense
FROM transactions t
LEFT JOIN categories c ON t.category_id = c.id
WHERE t.transaction_date BETWEEN ? AND ?
GROUP BY t.transaction_date
ORDER BY t.transaction_date ASC";
$stmt_trend = mysqli_prepare($conn, $sql_trend);
mysqli_stmt_bind_param($stmt_trend, "ss", $trend_start, $trend_end);
mysqli_stmt_execute($stmt_trend);
$res_trend = mysqli_stmt_get_result($stmt_trend);

$trend_labels = [];
$trend_income = [];
$trend_expense = [];

// Fill in gaps for trend
$current = strtotime($trend_start);
$last = strtotime($trend_end);
$temp_data = [];
while ($row = mysqli_fetch_assoc($res_trend)) {
    $temp_data[$row['date']] = $row;
}

while ($current <= $last) {
    $d = date('Y-m-d', $current);
    $trend_labels[] = date('d/m', $current);
    $trend_income[] = $temp_data[$d]['income'] ?? 0;
    $trend_expense[] = $temp_data[$d]['expense'] ?? 0;
    $current = strtotime('+1 day', $current);
}

// 4. Category Distribution (Top Expenses)
$sql_cat = "SELECT c.name, SUM(t.amount) as total
FROM transactions t
JOIN categories c ON t.category_id = c.id
WHERE c.direction = 'expense' AND t.transaction_date BETWEEN ? AND ?
GROUP BY c.id, c.name
ORDER BY total DESC
LIMIT 5";
$stmt_cat = mysqli_prepare($conn, $sql_cat);
mysqli_stmt_bind_param($stmt_cat, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt_cat);
$res_cat = mysqli_stmt_get_result($stmt_cat);
$cat_labels = [];
$cat_data = [];
while ($row = mysqli_fetch_assoc($res_cat)) {
    $cat_labels[] = $row['name'];
    $cat_data[] = $row['total'];
}

// 5. Recent Transactions
$sql_recent = "SELECT t.*, c.name as category_name, c.icon as category_icon, c.direction, p.project_name
FROM transactions t
LEFT JOIN categories c ON t.category_id = c.id
LEFT JOIN projects_list p ON t.project_id = p.id
WHERE t.transaction_date BETWEEN ? AND ?
ORDER BY t.transaction_date DESC, t.id DESC
LIMIT 5";
$stmt_recent = mysqli_prepare($conn, $sql_recent);
mysqli_stmt_bind_param($stmt_recent, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt_recent);
$res_recent = mysqli_stmt_get_result($stmt_recent);
$recent_transactions = [];
while ($row = mysqli_fetch_assoc($res_recent)) {
    $recent_transactions[] = $row;
}

// 6. Top Projects by Activity
$sql_top_projects = "SELECT p.project_name, SUM(t.amount) as total_amount, COUNT(t.id) as trans_count
FROM transactions t
JOIN projects_list p ON t.project_id = p.id
WHERE t.transaction_date BETWEEN ? AND ?
GROUP BY p.id, p.project_name
ORDER BY total_amount DESC
LIMIT 5";
$stmt_top = mysqli_prepare($conn, $sql_top_projects);
mysqli_stmt_bind_param($stmt_top, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt_top);
$res_top = mysqli_stmt_get_result($stmt_top);
$top_projects = [];
while ($row = mysqli_fetch_assoc($res_top)) {
    $top_projects[] = $row;
}


?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-slate-900 bg-opacity-30 backdrop-blur-sm z-50 flex items-center justify-center hidden transition-opacity duration-300 opacity-0">
    <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-indigo-100 border-t-indigo-600 mb-4"></div>
        <p class="text-slate-600 font-medium animate-pulse">กำลังประมวลผลข้อมูล...</p>
    </div>
</div>

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
                <a href="dashboard.php" class="px-3 py-1.5 bg-white text-slate-600 text-sm font-medium rounded-lg border border-slate-200 shadow-sm hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                    วันนี้
                </a>
                <a href="dashboard.php?start_date=<?= date('Y-m-d', strtotime('-6 days')) ?>&end_date=<?= date('Y-m-d') ?>" class="px-3 py-1.5 bg-white text-slate-600 text-sm font-medium rounded-lg border border-slate-200 shadow-sm hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                    7 Days
                </a>
            </div>
            <form method="GET" class="flex items-center gap-2 bg-white p-1 rounded-lg border border-slate-200 shadow-sm">
                <input type="date" name="start_date" value="<?= $start_date ?>" class="text-sm border-none focus:ring-0 text-slate-600 bg-transparent">
                <span class="text-slate-400">-</span>
                <input type="date" name="end_date" value="<?= $end_date ?>" class="text-sm border-none focus:ring-0 text-slate-600 bg-transparent">
                <button type="submit" class="bg-indigo-600 text-white p-1.5 rounded-md hover:bg-indigo-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>
            </form>
            <?php if($is_today): ?>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                Live Update
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Income -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">รายรับรวม</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-2"><?= number_format($total_income, 2) ?> ฿</h3>
                </div>
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-slate-400">
                <span>จากช่วงเวลาที่เลือก</span>
            </div>
        </div>

        <!-- Total Expense -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">รายจ่ายรวม</p>
                    <h3 class="text-2xl font-bold text-red-600 mt-2"><?= number_format($total_expense, 2) ?> ฿</h3>
                </div>
                <div class="p-3 bg-red-50 rounded-xl text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-slate-400">
                <span>จากช่วงเวลาที่เลือก</span>
            </div>
        </div>

        <!-- Total Profit -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">กำไร/ขาดทุน</p>
                    <h3 class="text-2xl font-bold <?= $total_profit >= 0 ? 'text-indigo-600' : 'text-orange-600' ?> mt-2">
                        <?= ($total_profit >= 0 ? '+' : '') . number_format($total_profit, 2) ?> ฿
                    </h3>
                </div>
                <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-4">
                <?php 
                    $profit_percent = ($total_income > 0) ? ($total_profit / $total_income) * 100 : 0;
                    $profit_percent = max(0, min(100, $profit_percent));
                ?>
                <div class="bg-indigo-500 h-1.5 rounded-full" style="width: <?= $profit_percent ?>%"></div>
            </div>
        </div>

        <!-- Total Transactions -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">จำนวนรายการ</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-2"><?= number_format($total_transactions) ?> <span class="text-lg text-slate-400 font-normal">รายการ</span></h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 002-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
            </div>
            <p class="text-sm text-slate-500 mt-4">โครงการทั้งหมด: <?= $projects_count ?> โครงการ</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Financial Trend -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <span class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                </span>
                แนวโน้มรายรับ-รายจ่าย
            </h2>
            <div class="h-80">
                <canvas id="financialTrendChart"></canvas>
            </div>
        </div>

        <!-- Category Distribution -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <span class="p-2 bg-orange-100 rounded-lg text-orange-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                </span>
                สัดส่วนรายจ่ายสูงสุด
            </h2>
            <div class="h-64 flex justify-center">
                <canvas id="categoryDistributionChart"></canvas>
            </div>
            <div class="mt-6 space-y-2">
                <?php foreach ($cat_labels as $idx => $label): ?>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-600 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" style="background-color: <?= ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16'][$idx] ?? '#cbd5e1' ?>;"></span>
                        <?= htmlspecialchars($label) ?>
                    </span>
                    <span class="font-bold text-slate-800"><?= number_format($cat_data[$idx]) ?> ฿</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Top Projects -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <span class="p-2 bg-amber-100 rounded-lg text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                </span>
                Top 5 โครงการ (ยอดเงินสูงสุด)
            </h2>
            <div class="space-y-4">
                <?php foreach ($top_projects as $index => $tp): ?>
                <div class="flex items-center p-3 hover:bg-slate-50 rounded-xl transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold">
                        <?= $index + 1 ?>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex justify-between mb-1">
                            <h4 class="font-semibold text-slate-700"><?= htmlspecialchars($tp['project_name']) ?></h4>
                            <span class="font-bold text-indigo-600"><?= number_format($tp['total_amount']) ?> ฿</span>
                        </div>
                        <div class="text-xs text-slate-400"><?= $tp['trans_count'] ?> รายการ</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <span class="p-2 bg-blue-100 rounded-lg text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
                รายการล่าสุด
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-medium">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">วันที่</th>
                            <th class="px-4 py-3">หมวดหมู่</th>
                            <th class="px-4 py-3">โครงการ</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">จำนวนเงิน</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($recent_transactions) > 0): ?>
                            <?php foreach ($recent_transactions as $rt): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-500"><?= date('d/m/Y', strtotime($rt['transaction_date'])) ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span><?= $rt['category_icon'] ?></span>
                                        <span class="font-medium text-slate-700"><?= htmlspecialchars($rt['category_name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($rt['project_name']) ?></td>
                                <td class="px-4 py-3 text-right font-bold <?= $rt['direction'] == 'income' ? 'text-emerald-600' : 'text-red-600' ?>">
                                    <?= ($rt['direction'] == 'income' ? '+' : '-') . number_format($rt['amount'], 2) ?> ฿
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-8 text-slate-400">ไม่มีรายการในช่วงเวลานี้</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    // Financial Trend Chart
    const ctxTrend = document.getElementById('financialTrendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?= json_encode($trend_labels) ?>,
            datasets: [
                {
                    label: 'รายรับ',
                    data: <?= json_encode($trend_income) ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'รายจ่าย',
                    data: <?= json_encode($trend_expense) ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { font: { family: "'Prompt', sans-serif" } } },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { family: "'Prompt', sans-serif" },
                    bodyFont: { family: "'Prompt', sans-serif" },
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: "'Prompt', sans-serif" } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Prompt', sans-serif" } }
                }
            }
        }
    });

    // Category Distribution Chart
    const ctxCat = document.getElementById('categoryDistributionChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cat_labels) ?>,
            datasets: [{
                data: <?= json_encode($cat_data) ?>,
                backgroundColor: ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Loading Overlay Logic
    const loadingOverlay = document.getElementById('loadingOverlay');

    function showLoading() {
        loadingOverlay.classList.remove('hidden');
        setTimeout(() => {
            loadingOverlay.classList.remove('opacity-0');
        }, 10);
    }

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            showLoading();
        });
    });

    document.querySelectorAll('a[href^="dashboard.php"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!e.ctrlKey && !e.metaKey && !e.shiftKey && this.target !== '_blank') {
                showLoading();
            }
        });
    });

    window.addEventListener('pageshow', function() {
        loadingOverlay.classList.add('opacity-0');
        setTimeout(() => {
            loadingOverlay.classList.add('hidden');
        }, 300);
    });
</script>

</body>
</html>


</body>
</html>

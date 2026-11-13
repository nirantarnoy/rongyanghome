<?php
session_start();
if (!isset($_SESSION['user_login'])) {
    header("Location: ../login.php");
    exit();
}

include '../config.php';

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$module_type = isset($_GET['module_type']) ? (int)$_GET['module_type'] : 1;

if ($project_id == 0) {
    header("Location: index.php");
    exit();
}

// Fetch project details
$sql = "SELECT * FROM projects_list WHERE id = $project_id AND company_id = {$_SESSION['company_id']}";
$result = mysqli_query($conn, $sql);
$project = mysqli_fetch_assoc($result);

if (!$project) {
    die("ไม่พบข้อมูลโครงการ");
}

$display_year = "2569";
$theme_color = $module_type == 1 ? '#a88c5a' : '#10b981';
$bg_color = $module_type == 1 ? '#f6f3e9' : '#f0f9f4';
$header_bg = $module_type == 1 ? '#a88c5a' : '#10b981';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปโครงการ: <?php echo $project['project_name']; ?> - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: <?php echo $bg_color; ?>;
        }
        .header-box {
            background-color: <?php echo $header_bg; ?>;
            border-radius: 1.5rem;
            color: white;
        }
        .stat-card {
            background-color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .transaction-row {
            background-color: white;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body class="p-4 md:p-10">

    <div class="max-w-6xl mx-auto">
        <!-- Breadcrumb -->
        <div class="mb-6 flex items-center gap-2 text-gray-500 text-sm">
            <a href="<?php echo $module_type == 1 ? 'index.php' : '../companytransaction/index.php'; ?>?view=project" class="hover:text-gray-800 transition-colors">จัดการโครงการ</a>
            <span>/</span>
            <span class="font-bold text-gray-800">สรุปโครงการ</span>
        </div>

        <!-- Header -->
        <div class="header-box p-8 mb-10 shadow-lg relative">
            <div class="absolute top-4 right-4 flex items-center gap-3 bg-white/10 backdrop-blur-md p-2 rounded-xl border border-white/20">
                <div class="text-right hidden sm:block">
                    <div class="text-xs font-bold opacity-80 uppercase tracking-wider">ผู้ใช้งาน</div>
                    <div class="text-sm font-bold"><?= $_SESSION['user_login'] ?></div>
                </div>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="../index.php" class="bg-white/20 hover:bg-white/30 text-white p-2 rounded-lg transition-all shadow-lg" title="กลับระบบหลัก">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </a>
                <?php endif; ?>
                <a href="../logout.php" class="bg-red-500/80 hover:bg-red-600 text-white p-2 rounded-lg transition-all shadow-lg" title="ออกจากระบบ">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </a>
            </div>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-3xl"><?php echo $module_type == 1 ? '📁' : '🌳'; ?></span>
                        <h1 class="text-3xl font-bold"><?php echo $project['project_name']; ?></h1>
                    </div>
                    <p class="opacity-90"><?php echo $project['note'] ?: 'ไม่มีหมายเหตุ'; ?></p>
                </div>
                <div class="text-right">
                    <p class="text-sm opacity-80">สรุปภาพรวมโครงการ</p>
                    <p class="text-lg font-bold">ประจำปี <?php echo $display_year; ?></p>
                </div>
            </div>
        </div>

        <!-- Filter & Export Toolbar -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
            <!-- Date Range Filter Bar -->
            <div class="bg-white p-3 rounded-2xl border border-gray-200 shadow-sm flex flex-wrap items-center gap-2">
                <!-- Status Badge -->
                <div class="mr-2">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">ช่วงเวลา</span>
                    <span class="text-xs font-medium text-gray-700" id="dashDateBadge">📅 ข้อมูลทั้งหมด</span>
                </div>

                <!-- Quick Buttons -->
                <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl text-xs">
                    <button type="button" class="dash-quick-btn px-2.5 py-1 rounded-lg font-medium text-gray-600 hover:bg-white hover:text-gray-900 transition-all" onclick="setDashboardDateRange('today')">วันนี้</button>
                    <button type="button" class="dash-quick-btn px-2.5 py-1 rounded-lg font-medium text-gray-600 hover:bg-white hover:text-gray-900 transition-all" onclick="setDashboardDateRange('7days')">7 วัน</button>
                    <button type="button" class="dash-quick-btn px-2.5 py-1 rounded-lg font-medium text-gray-600 hover:bg-white hover:text-gray-900 transition-all" onclick="setDashboardDateRange('this_month')">เดือนนี้</button>
                    <button type="button" class="dash-quick-btn px-2.5 py-1 rounded-lg font-medium text-gray-600 hover:bg-white hover:text-gray-900 transition-all" onclick="setDashboardDateRange('this_year')">ปีนี้</button>
                    <button type="button" class="dash-quick-btn active px-2.5 py-1 rounded-lg font-bold bg-white text-gray-800 shadow-xs transition-all" onclick="setDashboardDateRange('all')">ทั้งหมด</button>
                </div>

                <!-- Date Inputs -->
                <div class="flex items-center gap-1.5 text-xs">
                    <input type="date" id="dashStartDate" class="bg-gray-50 border border-gray-200 text-gray-800 rounded-xl px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs cursor-pointer">
                    <span class="text-gray-400 font-bold">ถึง</span>
                    <input type="date" id="dashEndDate" class="bg-gray-50 border border-gray-200 text-gray-800 rounded-xl px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 text-xs cursor-pointer">
                </div>

                <!-- Apply & Reset Buttons -->
                <button type="button" onclick="applyDashboardDateFilter()" style="background-color: <?php echo $theme_color; ?>;" class="text-white px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1 shadow-xs transition-all hover:opacity-90">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    กรองข้อมูล
                </button>
                <button type="button" onclick="resetDashboardDateFilter()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-2.5 py-1.5 rounded-xl font-medium text-xs transition-all" title="ล้างตัวกรอง">
                    ล้างค่า
                </button>
            </div>

            <!-- Export Action -->
            <a id="btnExportExcel" href="export_project.php?id=<?php echo $project_id; ?>&module_type=<?php echo $module_type; ?>" class="bg-white hover:bg-gray-50 text-emerald-600 border border-emerald-200 px-4 py-2.5 rounded-2xl font-bold flex items-center gap-2 transition-all shadow-sm hover:shadow text-sm shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                ส่งออกรายงาน (Excel)
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="stat-card border-l-4 border-emerald-500">
                <p class="text-sm text-gray-500 mb-1">รายรับรวม</p>
                <p id="dashIncome" class="text-3xl font-bold text-emerald-600">0.00 ฿</p>
            </div>
            <div class="stat-card border-l-4 border-red-500">
                <p class="text-sm text-gray-500 mb-1">รายจ่ายรวม</p>
                <p id="dashExpense" class="text-3xl font-bold text-red-600">0.00 ฿</p>
            </div>
            <div class="stat-card border-l-4 border-amber-500">
                <p class="text-sm text-gray-500 mb-1">กำไร/ขาดทุน</p>
                <p id="dashProfit" class="text-3xl font-bold text-amber-600">+0.00 ฿</p>
            </div>
        </div>

        <!-- Main Content 3 Columns -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10 items-start">
            
            <!-- Col 1: All Transactions -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">รายการบันทึกทั้งหมด</h2>
                    <div class="text-sm text-gray-500" id="transCount">0 รายการ</div>
                </div>
                <div id="transactionList" class="space-y-1">
                    <div class="text-center py-10 text-gray-400 italic">กำลังโหลดข้อมูล...</div>
                </div>
            </div>

            <!-- Col 2: Income Summary By Category -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-emerald-700 mb-6 flex flex-col">
                    <span>ยอดบันทึกรายรับ</span>
                    <span class="text-sm font-normal text-gray-500">แยกตามหมวดหมู่</span>
                </h2>
                <div id="incomeCatList" class="space-y-2">
                    <div class="text-center py-10 text-gray-400 italic">ไม่มีข้อมูล</div>
                </div>
            </div>

            <!-- Col 3: Expense Summary By Category -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-red-700 mb-6 flex flex-col">
                    <span>ยอดบันทึกรายจ่าย</span>
                    <span class="text-sm font-normal text-gray-500">แยกตามหมวดหมู่</span>
                </h2>
                <div id="expenseCatList" class="space-y-2">
                    <div class="text-center py-10 text-gray-400 italic">ไม่มีข้อมูล</div>
                </div>
            </div>

        </div>
    </div>

    <script>
        let currentStartDate = '';
        let currentEndDate = '';
        const baseExportUrl = 'export_project.php?id=<?php echo $project_id; ?>&module_type=<?php echo $module_type; ?>';

        $(document).ready(function() {
            loadStats();
            loadTransactions();
        });

        function formatThaiDate(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return `${parts[2]}/${parts[1]}/${parseInt(parts[0]) + 543}`;
            }
            return dateStr;
        }

        function setDashboardDateRange(range) {
            const today = new Date();
            let start = '';
            let end = '';

            const formatDate = (d) => {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            };

            if (range === 'today') {
                start = formatDate(today);
                end = formatDate(today);
            } else if (range === '7days') {
                const past7 = new Date();
                past7.setDate(today.getDate() - 6);
                start = formatDate(past7);
                end = formatDate(today);
            } else if (range === 'this_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                start = formatDate(firstDay);
                end = formatDate(lastDay);
            } else if (range === 'this_year') {
                start = `${today.getFullYear()}-01-01`;
                end = `${today.getFullYear()}-12-31`;
            } else if (range === 'all') {
                start = '';
                end = '';
            }

            $('#dashStartDate').val(start);
            $('#dashEndDate').val(end);

            $('.dash-quick-btn').removeClass('active bg-white text-gray-800 font-bold shadow-xs').addClass('font-medium text-gray-600');
            $(`.dash-quick-btn[onclick="setDashboardDateRange('${range}')"]`).addClass('active bg-white text-gray-800 font-bold shadow-xs').removeClass('font-medium text-gray-600');

            applyDashboardDateFilter(false);
        }

        function applyDashboardDateFilter(manual = true) {
            const start = $('#dashStartDate').val();
            const end = $('#dashEndDate').val();

            if (manual) {
                $('.dash-quick-btn').removeClass('active bg-white text-gray-800 font-bold shadow-xs').addClass('font-medium text-gray-600');
                if (!start && !end) {
                    $('.dash-quick-btn[onclick="setDashboardDateRange(\'all\')"]').addClass('active bg-white text-gray-800 font-bold shadow-xs').removeClass('font-medium text-gray-600');
                }
            }

            if (start && end) {
                $('#dashDateBadge').html(`📅 <span class="font-bold text-gray-800">${formatThaiDate(start)}</span> - <span class="font-bold text-gray-800">${formatThaiDate(end)}</span>`);
            } else if (start) {
                $('#dashDateBadge').html(`📅 ตั้งแต่ <span class="font-bold text-gray-800">${formatThaiDate(start)}</span>`);
            } else if (end) {
                $('#dashDateBadge').html(`📅 จนถึง <span class="font-bold text-gray-800">${formatThaiDate(end)}</span>`);
            } else {
                $('#dashDateBadge').text('📅 ข้อมูลทั้งหมด');
            }

            // Update export link with date filter parameters
            let exportUrl = baseExportUrl;
            if (start) exportUrl += '&start_date=' + encodeURIComponent(start);
            if (end) exportUrl += '&end_date=' + encodeURIComponent(end);
            $('#btnExportExcel').attr('href', exportUrl);

            currentStartDate = start;
            currentEndDate = end;
            loadStats(start, end);
            loadTransactions(start, end);
        }

        function resetDashboardDateFilter() {
            setDashboardDateRange('all');
        }

        function loadStats(startDate = '', endDate = '') {
            $.ajax({
                url: 'transaction_action.php',
                type: 'GET',
                data: { 
                    action: 'get_dashboard_stats', 
                    module_type: <?php echo $module_type; ?>,
                    project_id: <?php echo $project_id; ?>,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    try {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            const d = res.data;
                            $('#dashIncome').text(d.total_income.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿');
                            $('#dashExpense').text(d.total_expense.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿');
                            const profitSign = d.total_profit >= 0 ? '+' : '';
                            $('#dashProfit').text(profitSign + d.total_profit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿');
                            $('#transCount').text(d.total_count + ' รายการ');

                            let expenseHtml = '';
                            if (d.categories_summary && d.categories_summary.expense && d.categories_summary.expense.length > 0) {
                                d.categories_summary.expense.forEach(c => {
                                    expenseHtml += `
                                        <div class="bg-white p-4 rounded-2xl border border-red-50 shadow-sm mb-3">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-xl">
                                                    ${c.icon}
                                                </div>
                                                <div class="font-bold text-gray-800">${c.name}</div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[10px] text-gray-400 uppercase tracking-wider">รายจ่ายรวม</p>
                                                <p class="text-lg font-bold text-red-600">-${c.total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ฿</p>
                                            </div>
                                        </div>
                                    `;
                                });
                            }
                            $('#expenseCatList').html(expenseHtml || '<div class="text-center py-10 text-gray-400 italic">ไม่มีข้อมูลรายจ่าย</div>');

                            let incomeHtml = '';
                            if (d.categories_summary && d.categories_summary.income && d.categories_summary.income.length > 0) {
                                d.categories_summary.income.forEach(c => {
                                    incomeHtml += `
                                        <div class="bg-white p-4 rounded-2xl border border-emerald-50 shadow-sm mb-3">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-xl">
                                                    ${c.icon}
                                                </div>
                                                <div class="font-bold text-gray-800">${c.name}</div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[10px] text-gray-400 uppercase tracking-wider">รายรับรวม</p>
                                                <p class="text-lg font-bold text-emerald-600">+${c.total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ฿</p>
                                            </div>
                                        </div>
                                    `;
                                });
                            }
                            $('#incomeCatList').html(incomeHtml || '<div class="text-center py-10 text-gray-400 italic">ไม่มีข้อมูลรายรับ</div>');
                        }
                    } catch (e) { console.error(e); }
                }
            });
        }

        function loadTransactions(startDate = '', endDate = '') {
            $('#transactionList').html('<div class="text-center py-10 text-gray-400 italic">กำลังโหลดข้อมูล...</div>');
            $.ajax({
                url: 'transaction_action.php',
                type: 'GET',
                data: { 
                    action: 'list', 
                    module_type: <?php echo $module_type; ?>,
                    project_id: <?php echo $project_id; ?>,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    try {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            renderTransactionList(res.data);
                        }
                    } catch (e) { console.error(e); }
                }
            });
        }

        function renderTransactionList(data) {
            let html = '';
            if (!data || data.length === 0) {
                html = '<div class="text-center py-20 text-gray-400 italic">ยังไม่มีข้อมูลรายการในช่วงเวลานี้</div>';
            } else {
                data.forEach(t => {
                    const amountClass = t.direction === 'income' ? 'text-emerald-600' : 'text-red-600';
                    const amountPrefix = t.direction === 'income' ? '+' : '-';
                    html += `
                        <div class="transaction-row">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-4 flex-1 min-w-[200px]">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-2xl shadow-sm">
                                        ${t.category_icon || '📋'}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-800">${t.category_name}</div>
                                        <p class="text-sm text-gray-500">${t.note || '-'}</p>
                                    </div>
                                </div>
                                <div class="text-right min-w-[120px]">
                                    <p class="text-xs text-gray-400 mb-1">${t.transaction_date}</p>
                                    <p class="text-lg font-bold ${amountClass}">${amountPrefix}${parseFloat(t.amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ฿</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            $('#transactionList').html(html);
        }
    </script>
</body>
</html>

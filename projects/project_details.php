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
        <div class="header-box p-8 mb-10 shadow-lg">
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

        <!-- Transactions List -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">รายการบันทึกทั้งหมด</h2>
                <div class="text-sm text-gray-500" id="transCount">0 รายการ</div>
            </div>
            
            <div id="transactionList" class="space-y-1">
                <!-- Loaded via AJAX -->
                <div class="text-center py-10 text-gray-400 italic">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            loadStats();
            loadTransactions();
        });

        function loadStats() {
            $.ajax({
                url: 'transaction_action.php',
                type: 'GET',
                data: { 
                    action: 'get_dashboard_stats', 
                    module_type: <?php echo $module_type; ?>,
                    project_id: <?php echo $project_id; ?>
                },
                success: function(response) {
                    try {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            const d = res.data;
                            $('#dashIncome').text(d.total_income.toLocaleString() + ' ฿');
                            $('#dashExpense').text(d.total_expense.toLocaleString() + ' ฿');
                            const profitSign = d.total_profit >= 0 ? '+' : '';
                            $('#dashProfit').text(profitSign + d.total_profit.toLocaleString() + ' ฿');
                            $('#transCount').text(d.total_count + ' รายการ');
                        }
                    } catch (e) { console.error(e); }
                }
            });
        }

        function loadTransactions() {
            $.ajax({
                url: 'transaction_action.php',
                type: 'GET',
                data: { 
                    action: 'list', 
                    module_type: <?php echo $module_type; ?>,
                    search: '', // We can add filtering here if needed
                    project_id: <?php echo $project_id; ?> // Need to update action to support this
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
            // Filter data by project_id if the action doesn't support it yet
            const filteredData = data.filter(t => t.project_id == <?php echo $project_id; ?>);
            
            let html = '';
            if (filteredData.length === 0) {
                html = '<div class="text-center py-20 text-gray-400 italic">ยังไม่มีข้อมูลรายการ</div>';
            } else {
                filteredData.forEach(t => {
                    const amountClass = t.direction === 'income' ? 'text-emerald-600' : 'text-red-600';
                    const amountPrefix = t.direction === 'income' ? '+' : '-';
                    html += `
                        <div class="transaction-row">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-4 flex-1 min-w-[200px]">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-2xl shadow-sm">
                                        ${t.category_icon}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-800">${t.category_name}</div>
                                        <p class="text-sm text-gray-500">${t.note || '-'}</p>
                                    </div>
                                </div>
                                <div class="text-right min-w-[120px]">
                                    <p class="text-xs text-gray-400 mb-1">${t.transaction_date}</p>
                                    <p class="text-lg font-bold ${amountClass}">${amountPrefix}${parseFloat(t.amount).toLocaleString()} ฿</p>
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

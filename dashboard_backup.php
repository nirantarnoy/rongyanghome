<?php
require 'auth_check.php';
require 'config.php';
require 'functions.php';

// Get Date Range
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$is_today = ($start_date == date('Y-m-d') && $end_date == date('Y-m-d'));

// --- TARGET LOGIC ---
// Get Current Year/Week for Target (Based on Selected End Date)
$target_date_ts = strtotime($end_date);
$current_year = date('Y', $target_date_ts);
$current_week = date('W', $target_date_ts);

// Calculate Start/End Date of Week for Target Calculation
$dto = new DateTime();
$dto->setISODate($current_year, $current_week);
$week_start_date = $dto->format('Y-m-d');
$dto->modify('+6 days');
$week_end_date = $dto->format('Y-m-d');

// 1. Get Target
$target_qty = 0;
$sql_target = "SELECT TARGET_QTY FROM USRN_WEEKLY_TARGET WHERE YEAR = ? AND WEEK = ?";
$stmt_target = sqlsrv_query($conn, $sql_target, [$current_year, $current_week]);
if ($stmt_target && sqlsrv_has_rows($stmt_target)) {
    $row = sqlsrv_fetch_array($stmt_target, SQLSRV_FETCH_ASSOC);
    $target_qty = $row['TARGET_QTY'];
}

// 2. Get Actual Production for Target (Weekly)
$actual_weekly_qty = 0;
$sql_actual_weekly = "SELECT SUM(QTY) as TOTAL_QTY FROM USRN_PTB_REC_NEW 
               WHERE CAST(REC_TRANS_DATE AS DATE) BETWEEN ? AND ?";
$stmt_actual_weekly = sqlsrv_query($conn, $sql_actual_weekly, [$week_start_date, $week_end_date]);
if ($stmt_actual_weekly && sqlsrv_has_rows($stmt_actual_weekly)) {
    $row = sqlsrv_fetch_array($stmt_actual_weekly, SQLSRV_FETCH_ASSOC);
    $actual_weekly_qty = $row['TOTAL_QTY'] ?? 0;
}

// Calculate Percentage
$percent = ($target_qty > 0) ? ($actual_weekly_qty / $target_qty) * 100 : 0;
$percent_display = number_format($percent, 1);

// Color Logic for Target
// Default Red (Tailwind red-500)
$r = 239; $g = 68; $b = 68;

if ($percent >= 50) {
    // Gradient from Light Green (Emerald 300: #6ee7b7) to Dark Green (Emerald 800: #065f46)
    $start_r = 110; $start_g = 231; $start_b = 183;
    $end_r = 6;     $end_g = 95;    $end_b = 70;
    
    $ratio = min(1, ($percent - 50) / 50); // 0 to 1
    
    $r = round($start_r + ($end_r - $start_r) * $ratio);
    $g = round($start_g + ($end_g - $start_g) * $ratio);
    $b = round($start_b + ($end_b - $start_b) * $ratio);
}
$rgb_color = "rgb($r, $g, $b)";
// --- END TARGET LOGIC ---


// 1. Machine Status & Real-time OEE Calculation
// Pass dates to getAllMachine
$machines = getAllMachine($conn, $start_date, $end_date);
$total_machines = count($machines);
$running_count = 0;
$maintenance_count = 0;
$stopped_count = 0;
$total_qty_today = 0;
$sum_oee = 0;
$valid_oee_count = 0;
$machine_performance_list = [];

// Determine Time Range for OEE/Metrics
if ($is_today) {
    // Current Shift Logic (Original)
    $now = time();
    $current_hour = (int)date('H');
    $today_date = date('Y-m-d');
    if ($current_hour >= 8 && $current_hour < 20) {
        $shift_start_str = $today_date . ' 08:00:00';
    } else {
        if ($current_hour >= 20) {
            $shift_start_str = $today_date . ' 20:00:00';
        } else {
            $shift_start_str = date('Y-m-d', strtotime('-1 day')) . ' 20:00:00';
        }
    }
    $query_start = $shift_start_str;
    $query_end = date('Y-m-d H:i:s'); // Now
} else {
    // Custom Range Logic
    $shift_start_str = $start_date . ' 00:00:00'; // For OEE calc base
    $query_start = $start_date . ' 00:00:00';
    $query_end = $end_date . ' 23:59:59';
    $now = strtotime($query_end); // Simulate 'now' as end of range
}

// Fetch QTY (Based on Range)
// Fetch QTY (Based on Range)
if ($is_today) {
    $sql_shift_qty = "SELECT MACHINE_NO, SUM(QTY) as qty 
                      FROM USRN_PTB_REC_TEMP 
                      WHERE TRANS_DATE BETWEEN ? AND ?
                      GROUP BY MACHINE_NO";
} else {
    // For historical data, use USRN_PTB_REC_NEW
    $sql_shift_qty = "SELECT MACHINE_NO, SUM(QTY) as qty 
                      FROM USRN_PTB_REC_NEW 
                      WHERE REC_TRANS_DATE BETWEEN ? AND ?
                      GROUP BY MACHINE_NO";
}
$stmt_shift = sqlsrv_query($conn, $sql_shift_qty, [$query_start, $query_end]);
$shift_qtys = [];
if ($stmt_shift) {
    while ($row = sqlsrv_fetch_array($stmt_shift, SQLSRV_FETCH_ASSOC)) {
        $shift_qtys[$row['MACHINE_NO']] = $row['qty'];
    }
}

// Fetch Downtime (Based on Range)
$sql_shift_dt = "SELECT MACHINE_NO, SUM(PAUSE_DURATION) as duration 
                 FROM USRN_PTB_PROD_PAUSE_TRANS 
                 WHERE TRANS_DATE BETWEEN ? AND ?
                 GROUP BY MACHINE_NO";
$stmt_dt = sqlsrv_query($conn, $sql_shift_dt, [$query_start, $query_end]);
$shift_downtimes = [];
if ($stmt_dt) {
    while ($row = sqlsrv_fetch_array($stmt_dt, SQLSRV_FETCH_ASSOC)) {
        $shift_downtimes[$row['MACHINE_NO']] = $row['duration'];
    }
}

foreach ($machines as $m) {
    $machine_no = $m['LINEID'];
    
    // Status (Only relevant for Today)
    // Status (Only relevant for Today)
    if ($is_today) {
        if ($m['status'] == 'running') {
            $running_count++;
        } elseif ($m['status'] == 'maintenance') {
            $maintenance_count++;
        } else {
            $stopped_count++;
        }
    } else {
        if ($m['status'] == 'running') $running_count++;
        elseif ($m['status'] == 'maintenance') $maintenance_count++;
        else $stopped_count++;
    }
    
    // Total QTY (Daily/Range - for display)
    // Use $shift_qty (which is correctly sourced based on date) instead of $m['QTY'] (which is always TEMP)
    $shift_qty = $shift_qtys[$machine_no] ?? 0;
    $total_qty_today += $shift_qty;

    // Calculate OEE
    // Standard Data
    $std = getMachineStandard($conn, $machine_no);
    $std_output_hour = $std['standard_output_per_hour'] ?? 0;
    
    // Downtime
    $downtime_sec = $shift_downtimes[$machine_no] ?? 0;

    // Time Calculation
    if ($is_today) {
        $elapsed_sec = $now - strtotime($shift_start_str);
    } else {
        $elapsed_sec = strtotime($query_end) - strtotime($query_start);
    }
    
    if ($elapsed_sec < 0) $elapsed_sec = 0;
    
    $operating_sec = $elapsed_sec - $downtime_sec;
    if ($operating_sec < 0) $operating_sec = 0;

    // Availability
    $availability = ($elapsed_sec > 0) ? ($operating_sec / $elapsed_sec) : 0;

    // Performance
    $theo_output = ($operating_sec / 3600) * $std_output_hour;
    $performance = ($theo_output > 0) ? ($shift_qty / $theo_output) : 0;

    // Quality (Assume 100% for now)
    $quality = 1;

    $oee = $availability * $performance * $quality * 100;
    
    if ($oee > 0) {
        $sum_oee += $oee;
        $valid_oee_count++;
    }

    $machine_performance_list[] = [
        'machine_no' => $machine_no,
        'oee' => $oee,
        'qty' => $shift_qty,
        'status' => $m['status']
    ];
}

$avg_oee = ($valid_oee_count > 0) ? ($sum_oee / $valid_oee_count) : 0;

// 2. Production Trend (Last 7 Days OR Range)
$sql_trend = "SELECT CAST(REC_TRANS_DATE AS DATE) as date, SUM(QTY) as total_qty
              FROM USRN_PTB_REC_NEW
              WHERE REC_TRANS_DATE BETWEEN ? AND ?
              GROUP BY CAST(REC_TRANS_DATE AS DATE)
              ORDER BY date ASC";
$trend_start = $start_date;
$trend_end = $end_date;
if($is_today) {
    $trend_start = date('Y-m-d', strtotime('-6 days'));
}
$stmt_trend = sqlsrv_query($conn, $sql_trend, [$trend_start, $trend_end]);
$trend_labels = [];
$trend_data = [];
if ($stmt_trend) {
    while ($row = sqlsrv_fetch_array($stmt_trend, SQLSRV_FETCH_ASSOC)) {
        $trend_labels[] = $row['date']->format('d/m');
        $trend_data[] = $row['total_qty'];
    }
}

// 3. Recent Downtime (In Range)
$sql_dt_log = "SELECT TOP 5 MACHINE_NO, PAUSE_DURATION, REASON, TRANS_DATE 
               FROM USRN_PTB_PROD_PAUSE_TRANS 
               WHERE TRANS_DATE BETWEEN ? AND ?
               ORDER BY TRANS_DATE DESC";
$stmt_dt = sqlsrv_query($conn, $sql_dt_log, [$query_start, $query_end]);
$recent_downtimes = [];
if ($stmt_dt) {
    while ($row = sqlsrv_fetch_array($stmt_dt, SQLSRV_FETCH_ASSOC)) {
        $recent_downtimes[] = $row;
    }
}

// 4. Downtime Reasons (In Range)
$sql_reason = "SELECT REASON, COUNT(*) as count 
               FROM USRN_PTB_PROD_PAUSE_TRANS 
               WHERE TRANS_DATE BETWEEN ? AND ?
               GROUP BY REASON
               ORDER BY count DESC";
$stmt_reason = sqlsrv_query($conn, $sql_reason, [$query_start, $query_end]);
$reason_labels = [];
$reason_data = [];
if ($stmt_reason) {
    while ($row = sqlsrv_fetch_array($stmt_reason, SQLSRV_FETCH_ASSOC)) {
        $reason_labels[] = $row['REASON'];
        $reason_data[] = $row['count'];
    }
}

// Sort machines by OEE for Top 5
usort($machine_performance_list, function($a, $b) {
    return $b['oee'] <=> $a['oee'];
});
$top_machines = array_slice($machine_performance_list, 0, 5);

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
            <p class="text-slate-500 mt-1">ภาพรวมการผลิตประจำวันที่ <?= date('d/m/Y') ?></p>
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

    <!-- TARGET SECTION (Inserted Here) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative mb-8">
        
        <div class="relative p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
            
            <!-- Left: Stats -->
            <div class="space-y-6">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <a href="dashboard_target.php" class="text-2xl font-bold text-slate-800 hover:text-indigo-600 transition-colors flex items-center gap-2 group">
                            เป้าหมายการผลิตรายสัปดาห์
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                        <a href="target_setting.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            ตั้งค่า
                        </a>
                    </div>
                    <div class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-1">สัปดาห์ที่ <?= $current_week ?> / <?= $current_year ?></div>
                    <div class="text-slate-500 text-sm mb-4">ช่วงวันที่: <?= date('d/m/Y', strtotime($week_start_date)) ?> - <?= date('d/m/Y', strtotime($week_end_date)) ?></div>
                    
                    <div class="flex items-baseline gap-2">
                        <span class="text-5xl font-bold text-slate-800"><?= number_format($actual_weekly_qty) ?></span>
                        <span class="text-lg text-slate-400">เส้น (ผลิตได้)</span>
                    </div>
                </div>

                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-500 font-medium">เป้าหมาย (Target)</span>
                        <span class="text-xl font-bold text-indigo-600"><?= number_format($target_qty) ?></span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="h-3 rounded-full transition-all duration-1000" style="width: <?= min($percent, 100) ?>%; background-color: <?= $rgb_color ?>;"></div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs font-medium">
                        <span class="text-slate-400">0%</span>
                        <span style="color: <?= $rgb_color ?>;"><?= $percent_display ?>%</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 rounded-xl bg-green-50 border border-green-100">
                        <div class="text-green-600 text-xs font-bold mb-1">คงเหลือ (Remaining)</div>
                        <div class="text-xl font-bold text-green-700">
                            <?= number_format(max(0, $target_qty - $actual_weekly_qty)) ?>
                        </div>
                    </div>
                    <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                        <div class="text-blue-600 text-xs font-bold mb-1">เฉลี่ยต่อวัน (Avg/Day)</div>
                        <div class="text-xl font-bold text-blue-700">
                            <?php 
                                // Calculate days passed in this week (or 7 if past week)
                                $days_passed = min(7, max(1, (time() - strtotime($week_start_date)) / 86400));
                                echo number_format($actual_weekly_qty / $days_passed, 0);
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Visual -->
            <div class="flex flex-col items-center justify-center relative">
                <!-- Circular Progress (CSS based) -->
                <div class="relative w-56 h-56">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="112" cy="112" r="90" stroke="currentColor" stroke-width="18" fill="transparent" class="text-slate-100" />
                        <circle cx="112" cy="112" r="90" stroke="currentColor" stroke-width="18" fill="transparent" 
                                stroke-dasharray="565" 
                                stroke-dashoffset="<?= 565 - (565 * min($percent, 100) / 100) ?>" 
                                class="transition-all duration-1000 ease-out" style="color: <?= $rgb_color ?>;" 
                                stroke-linecap="round" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-bold" style="color: <?= $rgb_color ?>;"><?= number_format($percent, 0) ?>%</span>
                        <span class="text-slate-400 text-sm font-medium mt-1">ความสำเร็จ</span>
                    </div>
                </div>
                
                <?php if ($percent >= 100): ?>
                    <div class="mt-6 px-4 py-1.5 bg-emerald-100 text-emerald-700 rounded-full text-sm font-bold animate-bounce">
                        🎉 ทะลุเป้าหมายแล้ว!
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <!-- END TARGET SECTION -->

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Production -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">ยอดผลิตรวมวันนี้</p>
                    <h3 class="text-3xl font-bold text-indigo-600 mt-2"><?= number_format($total_qty_today) ?></h3>
                </div>
                <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-green-600">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                <span>กำลังผลิตต่อเนื่อง</span>
            </div>
        </div>

        <!-- Average OEE -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">Average OEE</p>
                    <h3 class="text-3xl font-bold text-emerald-600 mt-2"><?= number_format($avg_oee, 1) ?>%</h3>
                </div>
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-4">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: <?= $avg_oee ?>%"></div>
            </div>
        </div>

        <!-- Active Machines -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">เครื่องจักรทำงาน</p>
                    <h3 class="text-3xl font-bold text-slate-800 mt-2"><?= $running_count ?> <span class="text-lg text-slate-400 font-normal">/ <?= $total_machines ?></span></h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                </div>
            </div>
            <div class="flex items-center gap-4 mt-4 text-sm">
                <span class="text-orange-500 font-medium flex items-center gap-1"><span class="w-2 h-2 bg-orange-500 rounded-full"></span> ซ่อม: <?= $maintenance_count ?></span>
                <span class="text-red-500 font-medium flex items-center gap-1"><span class="w-2 h-2 bg-red-500 rounded-full"></span> หยุด: <?= $stopped_count ?></span>
            </div>
        </div>

        <!-- Downtime Today -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-slate-500 uppercase">Downtime วันนี้</p>
                    <h3 class="text-3xl font-bold text-red-500 mt-2"><?= count($recent_downtimes) ?> <span class="text-lg text-slate-400 font-normal">ครั้ง</span></h3>
                </div>
                <div class="p-3 bg-red-50 rounded-xl text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <p class="text-sm text-slate-500 mt-4">ตรวจสอบล่าสุดเมื่อสักครู่</p>
        </div>
    </div>

    <!-- Charts Section: Trend & Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- Production Trend (2/3 width) -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <span class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                </span>
                แนวโน้มการผลิต (7 วันล่าสุด)
            </h2>
            <div class="h-80">
                <canvas id="productionTrendChart"></canvas>
            </div>
        </div>

        <!-- Machine Status (1/3 width) -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <span class="p-2 bg-blue-100 rounded-lg text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </span>
                สถานะเครื่องจักร
            </h2>
            <div class="h-64 flex justify-center">
                <canvas id="machineStatusChart"></canvas>
            </div>
            <div class="mt-6 grid grid-cols-3 gap-2 text-center">
                <div class="p-3 bg-green-50 rounded-xl">
                    <span class="block text-xl font-bold text-green-600"><?= $running_count ?></span>
                    <span class="text-[10px] text-green-800 uppercase">Run</span>
                </div>
                <div class="p-3 bg-orange-50 rounded-xl">
                    <span class="block text-xl font-bold text-orange-600"><?= $maintenance_count ?></span>
                    <span class="text-[10px] text-orange-800 uppercase">Maint.</span>
                </div>
                <div class="p-3 bg-red-50 rounded-xl">
                    <span class="block text-xl font-bold text-red-600"><?= $stopped_count ?></span>
                    <span class="text-[10px] text-red-800 uppercase">Stop</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Reasons, Top Machines, Recent Downtime (3 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Downtime Reason Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <span class="p-2 bg-orange-100 rounded-lg text-orange-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </span>
                สาเหตุการหยุดเครื่อง
            </h2>
            <div class="h-80">
                <canvas id="downtimeReasonChart"></canvas>
            </div>
        </div>

        <!-- Top Performing Machines -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <span class="p-2 bg-amber-100 rounded-lg text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                </span>
                Top 5 เครื่องจักร (OEE สูงสุด)
            </h2>
            <div class="space-y-4">
                <?php foreach ($top_machines as $index => $tm): ?>
                <div class="flex items-center p-3 hover:bg-slate-50 rounded-xl transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold">
                        <?= $index + 1 ?>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex justify-between mb-1">
                            <h4 class="font-semibold text-slate-700">Machine <?= htmlspecialchars($tm['machine_no']) ?></h4>
                            <span class="font-bold text-indigo-600"><?= number_format($tm['oee'], 1) ?>%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: <?= $tm['oee'] ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Downtime Logs -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <span class="p-2 bg-red-100 rounded-lg text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
                การหยุดเครื่องล่าสุด (วันนี้)
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-600 font-medium">
                        <tr>
                            <th class="px-4 py-3 rounded-l-lg">เวลา</th>
                            <th class="px-4 py-3">เครื่อง</th>
                            <th class="px-4 py-3">สาเหตุ</th>
                            <th class="px-4 py-3 rounded-r-lg text-right">ระยะเวลา</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($recent_downtimes) > 0): ?>
                            <?php foreach ($recent_downtimes as $dt): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-500"><?= $dt['TRANS_DATE']->format('H:i') ?></td>
                                <td class="px-4 py-3 font-medium text-slate-700"><?= htmlspecialchars($dt['MACHINE_NO']) ?></td>
                                <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($dt['REASON']) ?></td>
                                <td class="px-4 py-3 text-right font-bold text-red-500"><?= number_format($dt['PAUSE_DURATION']/60, 0) ?> น.</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-8 text-slate-400">ไม่มีรายการหยุดเครื่องวันนี้</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    // Production Trend Chart
    const ctxTrend = document.getElementById('productionTrendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?= json_encode($trend_labels) ?>,
            datasets: [{
                label: 'ยอดผลิต (ชิ้น)',
                data: <?= json_encode($trend_data) ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#4f46e5',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { family: "'Prompt', sans-serif" },
                    bodyFont: { family: "'Prompt', sans-serif" },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false
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

    // Machine Status Chart
    const ctxStatus = document.getElementById('machineStatusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Running', 'Maintenance', 'Stopped'],
            datasets: [{
                data: [<?= $running_count ?>, <?= $maintenance_count ?>, <?= $stopped_count ?>],
                backgroundColor: ['#10b981', '#f97316', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Generate Random Colors
    function generateRandomColors(count) {
        const colors = [];
        for (let i = 0; i < count; i++) {
            const r = Math.floor(Math.random() * 200); // Avoid too light
            const g = Math.floor(Math.random() * 200);
            const b = Math.floor(Math.random() * 200);
            colors.push(`rgba(${r}, ${g}, ${b}, 0.7)`);
        }
        return colors;
    }

    // Downtime Reason Chart
    const ctxReason = document.getElementById('downtimeReasonChart').getContext('2d');
    const reasonData = <?= json_encode($reason_data) ?>;
    
    new Chart(ctxReason, {
        type: 'bar',
        data: {
            labels: <?= json_encode($reason_labels) ?>,
            datasets: [{
                label: 'จำนวนครั้ง',
                data: reasonData,
                backgroundColor: generateRandomColors(reasonData.length),
                borderRadius: 6,
                barThickness: 30 // Thicker bars
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            // indexAxis: 'x', // Default is vertical
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { family: "'Prompt', sans-serif" },
                    bodyFont: { family: "'Prompt', sans-serif" },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: "'Prompt', sans-serif" }, stepSize: 1 }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Prompt', sans-serif" } }
                }
            }
        }
    });

    // Loading Overlay Logic
    const loadingOverlay = document.getElementById('loadingOverlay');

    function showLoading() {
        loadingOverlay.classList.remove('hidden');
        // Small delay to allow display:block to apply before opacity transition
        setTimeout(() => {
            loadingOverlay.classList.remove('opacity-0');
        }, 10);
    }

    // Attach to Forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            showLoading();
        });
    });

    // Attach to Quick Links (Today, 7 Days)
    document.querySelectorAll('a[href^="dashboard.php"]').forEach(link => {
        link.addEventListener('click', function(e) {
            // Only show if not opening in new tab/window
            if (!e.ctrlKey && !e.metaKey && !e.shiftKey && this.target !== '_blank') {
                showLoading();
            }
        });
    });

    // Hide on page load (in case of back button)
    window.addEventListener('pageshow', function() {
        loadingOverlay.classList.add('opacity-0');
        setTimeout(() => {
            loadingOverlay.classList.add('hidden');
        }, 300);
    });
</script>

</body>
</html>

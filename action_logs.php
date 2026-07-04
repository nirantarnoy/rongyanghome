<?php
require 'auth_check.php';
include 'config.php';

$company_id = $_SESSION['company_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

// Only admin can view action logs
if ($user_role !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch users list for dropdown filter
$users_sql = "SELECT id, username, full_name FROM users WHERE company_id = ? ORDER BY full_name ASC, username ASC";
$users_stmt = mysqli_prepare($conn, $users_sql);
$users_list = [];
if ($users_stmt) {
    mysqli_stmt_bind_param($users_stmt, "i", $company_id);
    mysqli_stmt_execute($users_stmt);
    $users_res = mysqli_stmt_get_result($users_stmt);
    while ($u = mysqli_fetch_assoc($users_res)) {
        $users_list[] = $u;
    }
    mysqli_stmt_close($users_stmt);
}

// Thai Date Formatter Helper
function formatThaiDate($dateStr) {
    if (empty($dateStr)) return '';
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $time = strtotime($dateStr);
    $day = date('j', $time);
    $month = $months[(int)date('n', $time)];
    $year = (int)date('Y', $time) + 543; // Buddhist Era
    return "วันที่ $day $month $year";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติกิจกรรม - RONGYANG HOME</title>
    <script src="assets/js/tailwindcss.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php include 'navbar.php'; ?>

<div class="container max-w-7xl mx-auto px-4 py-8">
    <?php
    $selected_date = $_GET['date'] ?? '';
    $selected_user = $_GET['user_id'] ?? '';
    $limit_option = $_GET['limit'] ?? '50';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    ?>
    <!-- Header & Filter Section -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                ประวัติกิจกรรมในระบบ
            </h1>
            <p class="text-slate-500 mt-1">ตรวจสอบการทำรายการต่างๆ ของผู้ใช้งานในระบบ</p>
        </div>
        
        <!-- Filter Form -->
        <div class="flex items-center flex-wrap gap-3">
            <form method="GET" class="flex items-center flex-wrap gap-3">
                <!-- User Selector -->
                <div class="relative">
                    <select name="user_id" onchange="this.form.submit()"
                            class="pl-4 pr-10 py-2 border border-slate-200 rounded-full text-sm font-medium text-slate-700 bg-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 shadow-sm cursor-pointer hover:border-slate-300 transition-colors appearance-none">
                        <option value="">-- ผู้ใช้งานทั้งหมด --</option>
                        <?php foreach ($users_list as $u): ?>
                            <?php 
                            $u_display = htmlspecialchars($u['username'] ?? '');
                            if (empty($u_display)) $u_display = htmlspecialchars($u['full_name'] ?? '');
                            $selected = ((string)$u['id'] === (string)$selected_user) ? 'selected' : '';
                            ?>
                            <option value="<?= $u['id'] ?>" <?= $selected ?>><?= $u_display ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Limit Selector -->
                <div class="relative">
                    <select name="limit" onchange="this.form.submit()"
                            class="pl-4 pr-10 py-2 border border-slate-200 rounded-full text-sm font-medium text-slate-700 bg-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 shadow-sm cursor-pointer hover:border-slate-300 transition-colors appearance-none">
                        <option value="20" <?= $limit_option === '20' ? 'selected' : '' ?>>20 รายการ</option>
                        <option value="50" <?= $limit_option === '50' ? 'selected' : '' ?>>50 รายการ</option>
                        <option value="all" <?= $limit_option === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
                <!-- Date Picker -->
                <div class="relative">
                    <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" 
                           class="pl-4 pr-4 py-2 border border-slate-200 rounded-full text-sm font-medium text-slate-700 bg-white focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 shadow-sm cursor-pointer hover:border-slate-300 transition-colors"
                           onchange="this.form.submit()">
                </div>
                
                <?php if (!empty($selected_date) || !empty($selected_user)): ?>
                    <a href="action_logs.php" class="text-slate-400 hover:text-slate-600 transition-colors p-2 bg-slate-50 hover:bg-slate-100 rounded-full border border-slate-200 shadow-sm" title="ล้างตัวกรอง">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                <?php endif; ?>
                <span class="text-sm font-semibold text-slate-600">เลือกวันเวลา</span>
            </form>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">วันที่-เวลา</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">ผู้ใช้งาน</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">Module</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">กิจกรรม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php
                    $where_clause = "WHERE l.company_id = ?";
                    $params = [$company_id];
                    $types = "i";

                    if (!empty($selected_date)) {
                        $where_clause .= " AND DATE(l.created_at) = ?";
                        $params[] = $selected_date;
                        $types .= "s";
                    }
                    if (!empty($selected_user)) {
                        $where_clause .= " AND l.user_id = ?";
                        $params[] = (int)$selected_user;
                        $types .= "i";
                    }

                    // Count total records
                    $count_sql = "SELECT COUNT(*) as total FROM action_logs l $where_clause";
                    $count_stmt = mysqli_prepare($conn, $count_sql);
                    $total_records = 0;
                    if ($count_stmt) {
                        mysqli_stmt_bind_param($count_stmt, $types, ...$params);
                        mysqli_stmt_execute($count_stmt);
                        $count_res = mysqli_stmt_get_result($count_stmt);
                        if ($row = mysqli_fetch_assoc($count_res)) {
                            $total_records = $row['total'];
                        }
                        mysqli_stmt_close($count_stmt);
                    }

                    $total_pages = 1;
                    $offset = 0;
                    $limit_sql = "";
                    $limit_val = 50;
                    if ($limit_option !== 'all') {
                        $limit_val = (int)$limit_option;
                        $total_pages = ceil($total_records / $limit_val);
                        if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
                        $offset = ($page - 1) * $limit_val;
                        $limit_sql = "LIMIT ? OFFSET ?";
                    }

                    $log_sql = "SELECT l.*, u.full_name, u.username 
                               FROM action_logs l 
                               LEFT JOIN users u ON l.user_id = u.id 
                               $where_clause 
                               ORDER BY l.created_at DESC 
                               $limit_sql";
                    
                    try {
                        $log_stmt = mysqli_prepare($conn, $log_sql);
                        if ($log_stmt) {
                            if ($limit_option !== 'all') {
                                $log_params = array_merge($params, [$limit_val, $offset]);
                                $log_types = $types . "ii";
                            } else {
                                $log_params = $params;
                                $log_types = $types;
                            }
                            mysqli_stmt_bind_param($log_stmt, $log_types, ...$log_params);
                            mysqli_stmt_execute($log_stmt);
                            $log_res = mysqli_stmt_get_result($log_stmt);

                            if (mysqli_num_rows($log_res) == 0) {
                                echo "<tr><td colspan='5' class='px-6 py-12 text-center text-gray-400'>ไม่พบประวัติกิจกรรม</td></tr>";
                            } else {
                                $current_date_group = null;
                                while ($log = mysqli_fetch_assoc($log_res)) {
                                    $log_date = date('Y-m-d', strtotime($log['created_at']));
                                    if ($current_date_group !== $log_date) {
                                        $current_date_group = $log_date;
                                        $thai_date_label = formatThaiDate($log_date);
                                        echo "
                                        <tr class='bg-emerald-50/60 font-bold text-emerald-900 border-y border-emerald-100/50'>
                                            <td colspan='5' class='px-6 py-3 text-sm'>
                                                <div class='flex items-center gap-2'>
                                                    <svg class='w-4 h-4 text-emerald-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'></path>
                                                    </svg>
                                                    $thai_date_label
                                                </div>
                                            </td>
                                        </tr>";
                                    }

                                    $type_colors = [
                                        'create' => 'bg-emerald-100 text-emerald-700',
                                        'update' => 'bg-blue-100 text-blue-700',
                                        'delete' => 'bg-red-100 text-red-700',
                                        'view' => 'bg-gray-100 text-gray-700'
                                    ];
                                    $color_class = $type_colors[$log['action_type']] ?? 'bg-gray-100 text-gray-700';
                                    $type_label = strtoupper($log['action_type']);
                                    
                                    $module_colors = [
                                        'stock' => 'bg-purple-100 text-purple-700',
                                        'quotation' => 'bg-indigo-100 text-indigo-700',
                                        'project' => 'bg-amber-100 text-amber-700',
                                        'transaction' => 'bg-teal-100 text-teal-700'
                                    ];
                                    $module_color = $module_colors[$log['module']] ?? 'bg-gray-100 text-gray-700';
                                    $module_label = strtoupper($log['module']);
                                    
                                    $user_display = $log['full_name'] ? htmlspecialchars($log['full_name']) : htmlspecialchars($log['username'] ?? 'System');
                                    $time_display = date('H:i:s', strtotime($log['created_at'])) . " น.";

                                    echo "
                                    <tr class='hover:bg-gray-50/80 transition-colors'>
                                        <td class='px-6 py-4 text-sm text-gray-500 font-medium'>$time_display</td>
                                        <td class='px-6 py-4'>
                                            <div class='text-sm font-semibold text-gray-800'>$user_display</div>
                                        </td>
                                        <td class='px-6 py-4'>
                                            <span class='px-2.5 py-1 rounded-full text-xs font-bold $module_color'>$module_label</span>
                                        </td>
                                        <td class='px-6 py-4'>
                                            <span class='px-2.5 py-1 rounded-full text-xs font-bold $color_class'>$type_label</span>
                                        </td>
                                        <td class='px-6 py-4 text-sm text-gray-600 font-medium'>" . htmlspecialchars($log['activity']) . "</td>
                                    </tr>";
                                }
                            }
                            mysqli_stmt_close($log_stmt);
                        } else {
                            throw new Exception("ไม่สามารถเตรียมคำสั่ง SQL ได้");
                        }
                    } catch (Throwable $e) {
                        echo "<tr><td colspan='5' class='px-6 py-12 text-center text-red-500 font-bold'>
                            เกิดข้อผิดพลาด หรือตาราง action_logs ยังไม่ได้ถูกสร้าง<br>
                            <span class='text-sm font-normal text-gray-400'>รายละเอียดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</span><br>
                            <a href='create_unified_action_logs.php' target='_blank' class='mt-4 inline-block px-6 py-2 bg-emerald-600 text-white rounded-full text-sm font-medium hover:bg-emerald-700 transition-colors shadow-sm'>คลิกที่นี่เพื่อติดตั้งตารางข้อมูลและ Migrate ข้อมูล logs</a>
                        </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($limit_option !== 'all' && $total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                แสดงผล <span class="font-bold text-gray-700"><?= number_format($offset + 1) ?></span> ถึง 
                <span class="font-bold text-gray-700"><?= number_format(min($offset + $limit_val, $total_records)) ?></span> 
                จาก <span class="font-bold text-gray-700"><?= number_format($total_records) ?></span> รายการ
            </div>
            <div class="flex items-center gap-1">
                <?php 
                $query_string = http_build_query(array_merge($_GET, ['page' => '__PAGE__']));
                $link_template = "action_logs.php?" . $query_string;
                
                // Previous button
                if ($page > 1) {
                    $prev_link = str_replace('__PAGE__', $page - 1, $link_template);
                    echo "<a href='$prev_link' class='px-3 py-1 text-sm font-medium text-slate-600 bg-slate-50 border border-slate-200 rounded-md hover:bg-slate-100'>ก่อนหน้า</a>";
                }
                
                // Page numbers
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    $first_link = str_replace('__PAGE__', 1, $link_template);
                    echo "<a href='$first_link' class='px-3 py-1 text-sm font-medium text-slate-600 border border-transparent rounded-md hover:bg-slate-50'>1</a>";
                    if ($start_page > 2) echo "<span class='px-2 text-slate-400'>...</span>";
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $page_link = str_replace('__PAGE__', $i, $link_template);
                    if ($i === $page) {
                        echo "<span class='px-3 py-1 text-sm font-bold text-white bg-emerald-500 rounded-md shadow-sm'>$i</span>";
                    } else {
                        echo "<a href='$page_link' class='px-3 py-1 text-sm font-medium text-slate-600 border border-transparent rounded-md hover:bg-slate-50'>$i</a>";
                    }
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo "<span class='px-2 text-slate-400'>...</span>";
                    $last_link = str_replace('__PAGE__', $total_pages, $link_template);
                    echo "<a href='$last_link' class='px-3 py-1 text-sm font-medium text-slate-600 border border-transparent rounded-md hover:bg-slate-50'>$total_pages</a>";
                }
                
                // Next button
                if ($page < $total_pages) {
                    $next_link = str_replace('__PAGE__', $page + 1, $link_template);
                    echo "<a href='$next_link' class='px-3 py-1 text-sm font-medium text-slate-600 bg-slate-50 border border-slate-200 rounded-md hover:bg-slate-100'>ถัดไป</a>";
                }
                ?>
            </div>
        </div>
        <?php elseif ($total_records > 0): ?>
        <div class="px-6 py-4 border-t border-gray-100 text-sm text-gray-500">
            แสดงผลทั้งหมด <span class="font-bold text-gray-700"><?= number_format($total_records) ?></span> รายการ
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>

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
    <!-- Header Section -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            ประวัติกิจกรรมในระบบ
        </h1>
        <p class="text-gray-500 mt-1">ตรวจสอบการทำรายการต่างๆ ของผู้ใช้งานในระบบ</p>
    </div>

    <!-- Filter Section -->
    <div class="mb-6 bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">ตั้งแต่วันที่</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" class="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">ถึงวันที่</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" class="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl text-sm font-bold transition-all shadow-sm">
                ค้นหา
            </button>
            <a href="action_logs.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-2 rounded-xl text-sm font-bold transition-all">
                ล้างตัวกรอง
            </a>
        </form>
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
                    $date_from = $_GET['date_from'] ?? '';
                    $date_to = $_GET['date_to'] ?? '';

                    $where_clause = "WHERE l.company_id = ?";

                    if (!empty($date_from)) {
                        $where_clause .= " AND DATE(l.created_at) >= ?";
                    }
                    if (!empty($date_to)) {
                        $where_clause .= " AND DATE(l.created_at) <= ?";
                    }

                    $log_sql = "SELECT l.*, u.full_name, u.username 
                               FROM action_logs l 
                               LEFT JOIN users u ON l.user_id = u.id 
                               $where_clause 
                               ORDER BY l.created_at DESC 
                               LIMIT 500";
                    
                    $log_stmt = mysqli_prepare($conn, $log_sql);
                    if ($log_stmt) {
                        if (!empty($date_from) && !empty($date_to)) {
                            mysqli_stmt_bind_param($log_stmt, "iss", $company_id, $date_from, $date_to);
                        } else if (!empty($date_from)) {
                            mysqli_stmt_bind_param($log_stmt, "is", $company_id, $date_from);
                        } else if (!empty($date_to)) {
                            mysqli_stmt_bind_param($log_stmt, "is", $company_id, $date_to);
                        } else {
                            mysqli_stmt_bind_param($log_stmt, "i", $company_id);
                        }
                        mysqli_stmt_execute($log_stmt);
                        $log_res = mysqli_stmt_get_result($log_stmt);

                        if (mysqli_num_rows($log_res) == 0) {
                            echo "<tr><td colspan='5' class='px-6 py-12 text-center text-gray-400'>ไม่พบประวัติกิจกรรม</td></tr>";
                        } else {
                            while ($log = mysqli_fetch_assoc($log_res)) {
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

                                echo "
                                <tr class='hover:bg-gray-50/80 transition-colors'>
                                    <td class='px-6 py-4 text-sm text-gray-500'>" . date('d/m/Y H:i:s', strtotime($log['created_at'])) . "</td>
                                    <td class='px-6 py-4'>
                                        <div class='text-sm font-medium text-gray-900'>$user_display</div>
                                    </td>
                                    <td class='px-6 py-4'>
                                        <span class='px-2.5 py-1 rounded-full text-xs font-bold $module_color'>$module_label</span>
                                    </td>
                                    <td class='px-6 py-4'>
                                        <span class='px-2.5 py-1 rounded-full text-xs font-bold $color_class'>$type_label</span>
                                    </td>
                                    <td class='px-6 py-4 text-sm text-gray-600'>" . htmlspecialchars($log['activity']) . "</td>
                                </tr>";
                            }
                        }
                    } else {
                        // Error handling, maybe table doesn't exist
                        echo "<tr><td colspan='5' class='px-6 py-12 text-center text-red-500 font-bold'>เกิดข้อผิดพลาด หรือตาราง action_logs ยังไม่ได้ถูกสร้าง<br><span class='text-sm font-normal text-gray-400'>กรุณารันไฟล์ create_unified_action_logs.php เพื่อสร้างฐานข้อมูล</span></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>

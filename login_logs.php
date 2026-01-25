<?php
require 'auth_check.php';
include 'config.php';

$company_id = $_SESSION['company_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

// Only admin can view login logs
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
    <title>ประวัติการเข้าสู่ระบบ - RONGYANG HOME</title>
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
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            ประวัติการเข้าสู่ระบบ
        </h1>
        <p class="text-gray-500 mt-1">ตรวจสอบประวัติการเข้าใช้งานระบบของผู้ใช้งาน</p>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">วันที่-เวลา</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">ผู้ใช้งาน</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">อุปกรณ์ / Browser</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php
                    $log_sql = "SELECT l.*, u.full_name 
                               FROM login_logs l 
                               LEFT JOIN users u ON l.user_id = u.id 
                               WHERE l.company_id = ? 
                               ORDER BY l.login_at DESC 
                               LIMIT 200";
                    $log_stmt = mysqli_prepare($conn, $log_sql);
                    mysqli_stmt_bind_param($log_stmt, "i", $company_id);
                    mysqli_stmt_execute($log_stmt);
                    $log_res = mysqli_stmt_get_result($log_stmt);

                    if (mysqli_num_rows($log_res) == 0) {
                        echo "<tr><td colspan='4' class='px-6 py-12 text-center text-gray-400'>ไม่พบประวัติการเข้าสู่ระบบ</td></tr>";
                    } else {
                        while ($log = mysqli_fetch_assoc($log_res)) {
                            $user_display = $log['full_name'] ? htmlspecialchars($log['full_name']) : htmlspecialchars($log['username']);
                            $ua = htmlspecialchars($log['user_agent']);
                            
                            // Simple Browser Detection
                            $browser = "Unknown";
                            if (strpos($ua, 'Edg') !== false) $browser = "Edge";
                            elseif (strpos($ua, 'Chrome') !== false) $browser = "Chrome";
                            elseif (strpos($ua, 'Safari') !== false) $browser = "Safari";
                            elseif (strpos($ua, 'Firefox') !== false) $browser = "Firefox";
                            
                            echo "
                            <tr class='hover:bg-gray-50/80 transition-colors'>
                                <td class='px-6 py-4 text-sm text-gray-500'>" . date('d/m/Y H:i:s', strtotime($log['login_at'])) . "</td>
                                <td class='px-6 py-4'>
                                    <div class='text-sm font-medium text-gray-900'>$user_display</div>
                                    <div class='text-xs text-gray-400'>@" . htmlspecialchars($log['username']) . "</div>
                                </td>
                                <td class='px-6 py-4'>
                                    <span class='px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600'>" . htmlspecialchars($log['ip_address']) . "</span>
                                </td>
                                <td class='px-6 py-4'>
                                    <div class='text-sm text-gray-600'>$browser</div>
                                    <div class='text-[10px] text-gray-400 truncate max-w-xs' title='$ua'>$ua</div>
                                </td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>

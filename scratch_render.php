<?php
require 'config.php';
$company_id = 1;
$date_from = '2026-01-19';
$date_to = '2026-02-19';

$params = [$company_id];
$types = "i";
$where_clause = "WHERE l.company_id = ?";

if (!empty($date_from)) {
    $where_clause .= " AND DATE(l.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $where_clause .= " AND DATE(l.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$log_sql = "SELECT l.*, u.full_name, u.username 
           FROM action_logs l 
           LEFT JOIN users u ON l.user_id = u.id 
           $where_clause 
           ORDER BY l.created_at DESC 
           LIMIT 500";

$log_stmt = mysqli_prepare($conn, $log_sql);
mysqli_stmt_bind_param($log_stmt, $types, ...$params);
mysqli_stmt_execute($log_stmt);
$log_res = mysqli_stmt_get_result($log_stmt);

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
echo "\nDONE!\n";
?>

<?php
// Logs Tab - Action History
?>

<div class="content-card">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-history" style="color: var(--accent-purple);"></i> ประวัติกิจกรรมในระบบ
    </h2>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #F9FAFB; border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 1rem; text-align: left;">วันที่-เวลา</th>
                    <th style="padding: 1rem; text-align: left;">ผู้ใช้งาน</th>
                    <th style="padding: 1rem; text-align: left;">ประเภท</th>
                    <th style="padding: 1rem; text-align: left;">กิจกรรม</th>
                </tr>
            </thead>
            <tbody id="actionLogsList">
                <?php
                $log_sql = "SELECT l.*, u.full_name, u.username 
                           FROM stock_action_logs l 
                           LEFT JOIN users u ON l.user_id = u.id 
                           WHERE l.company_id = ? 
                           ORDER BY l.created_at DESC 
                           LIMIT 100";
                $log_stmt = mysqli_prepare($conn, $log_sql);
                mysqli_stmt_bind_param($log_stmt, "i", $company_id);
                mysqli_stmt_execute($log_stmt);
                $log_res = mysqli_stmt_get_result($log_stmt);

                if (mysqli_num_rows($log_res) == 0) {
                    echo '<tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">ไม่พบประวัติกิจกรรม</td></tr>';
                } else {
                    while ($log = mysqli_fetch_assoc($log_res)) {
                        $type_colors = [
                            'create' => ['bg' => '#D1FAE5', 'text' => '#065F46', 'label' => 'CREATE'],
                            'update' => ['bg' => '#DBEAFE', 'text' => '#1E40AF', 'label' => 'UPDATE'],
                            'delete' => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'label' => 'DELETE'],
                            'view' => ['bg' => '#F3F4F6', 'text' => '#374151', 'label' => 'VIEW']
                        ];
                        $c = $type_colors[$log['action_type']] ?? ['bg' => '#EEE', 'text' => '#333', 'label' => 'UNKNOWN'];
                        $type_badge = "<span style='background: {$c['bg']}; color: {$c['text']}; padding: 0.2rem 0.6rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 700;'>{$c['label']}</span>";
                        
                        $user_display = $log['full_name'] ? htmlspecialchars($log['full_name']) : htmlspecialchars($log['username'] ?? 'System');

                        echo "
                        <tr style='border-bottom: 1px solid var(--border-color);'>
                            <td style='padding: 1rem; color: var(--text-muted);'>" . date('d/m/Y H:i:s', strtotime($log['created_at'])) . "</td>
                            <td style='padding: 1rem; font-weight: 500;'>$user_display</td>
                            <td style='padding: 1rem;'>$type_badge</td>
                            <td style='padding: 1rem;'>" . htmlspecialchars($log['activity']) . "</td>
                        </tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

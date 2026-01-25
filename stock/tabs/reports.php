<?php
// Reports Tab - Stock Summaries
?>

<div class="grid-form" style="margin-bottom: 2rem;">
    <div class="content-card" style="text-align: center;">
        <i class="fas fa-boxes fa-2x" style="color: var(--accent-purple); margin-bottom: 1rem;"></i>
        <div style="font-size: 0.9rem; color: var(--text-muted);">สินค้าทั้งหมด</div>
        <div style="font-size: 1.8rem; font-weight: 700;"><?= $total_items ?></div>
    </div>
    <div class="content-card" style="text-align: center;">
        <i class="fas fa-exclamation-triangle fa-2x" style="color: #EF4444; margin-bottom: 1rem;"></i>
        <div style="font-size: 0.9rem; color: var(--text-muted);">สินค้าสต็อกต่ำ</div>
        <div style="font-size: 1.8rem; font-weight: 700;">
            <?php
            $low_sql = "SELECT COUNT(*) as total FROM stock_products p 
                        WHERE company_id = ? AND 
                        (SELECT SUM(CASE WHEN type='in' THEN qty ELSE -qty END) FROM stock_transactions WHERE product_id = p.id) <= p.min_stock";
            $low_stmt = mysqli_prepare($conn, $low_sql);
            mysqli_stmt_bind_param($low_stmt, "i", $company_id);
            mysqli_stmt_execute($low_stmt);
            $low_res = mysqli_stmt_get_result($low_stmt);
            echo mysqli_fetch_assoc($low_res)['total'] ?? 0;
            ?>
        </div>
    </div>
    <div class="content-card" style="text-align: center;">
        <i class="fas fa-industry fa-2x" style="color: #3B82F6; margin-bottom: 1rem;"></i>
        <div style="font-size: 0.9rem; color: var(--text-muted);">กำลังผลิต</div>
        <div style="font-size: 1.8rem; font-weight: 700;">
            <?php
            $prod_sql = "SELECT COUNT(*) as total FROM stock_production_orders WHERE company_id = ? AND status = 'in_progress'";
            $prod_stmt = mysqli_prepare($conn, $prod_sql);
            mysqli_stmt_bind_param($prod_stmt, "i", $company_id);
            mysqli_stmt_execute($prod_stmt);
            $prod_res = mysqli_stmt_get_result($prod_stmt);
            echo mysqli_fetch_assoc($prod_res)['total'] ?? 0;
            ?>
        </div>
    </div>
</div>

<div class="content-card">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem;">สรุปสต็อกสินค้าคงเหลือ</h2>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #F9FAFB; border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 1rem; text-align: left;">สินค้า</th>
                    <th style="padding: 1rem; text-align: left;">หมวดหมู่</th>
                    <th style="padding: 1rem; text-align: right;">รับเข้าสะสม</th>
                    <th style="padding: 1rem; text-align: right;">เบิกออกสะสม</th>
                    <th style="padding: 1rem; text-align: right;">คงเหลือปัจจุบัน</th>
                    <th style="padding: 1rem; text-align: center;">สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $report_sql = "SELECT p.*, c.name as category_name,
                               SUM(CASE WHEN t.type = 'in' THEN t.qty ELSE 0 END) as total_in,
                               SUM(CASE WHEN t.type = 'out' THEN t.qty ELSE 0 END) as total_out
                               FROM stock_products p
                               LEFT JOIN stock_categories c ON p.category_id = c.id
                               LEFT JOIN stock_transactions t ON p.id = t.product_id
                               WHERE p.company_id = ?
                               GROUP BY p.id
                               ORDER BY p.name ASC";
                $report_stmt = mysqli_prepare($conn, $report_sql);
                mysqli_stmt_bind_param($report_stmt, "i", $company_id);
                mysqli_stmt_execute($report_stmt);
                $report_res = mysqli_stmt_get_result($report_stmt);
                
                while ($row = mysqli_fetch_assoc($report_res)) {
                    $current = ($row['total_in'] ?? 0) - ($row['total_out'] ?? 0);
                    $status = ($current <= $row['min_stock']) ? '<span style="color: #EF4444; font-weight: 600;">สต็อกต่ำ</span>' : '<span style="color: #10B981; font-weight: 600;">ปกติ</span>';
                    
                    echo '
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 1rem;">'.htmlspecialchars($row['name']).' ('.htmlspecialchars($row['sku']).')</td>
                        <td style="padding: 1rem;">'.htmlspecialchars($row['category_name'] ?? 'ทั่วไป').'</td>
                        <td style="padding: 1rem; text-align: right;">'.number_format($row['total_in'] ?? 0).'</td>
                        <td style="padding: 1rem; text-align: right;">'.number_format($row['total_out'] ?? 0).'</td>
                        <td style="padding: 1rem; text-align: right; font-weight: 700;">'.number_format($current).' '.$row['unit'].'</td>
                        <td style="padding: 1rem; text-align: center;">'.$status.'</td>
                    </tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

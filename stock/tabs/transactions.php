<?php
// Transactions Tab - Stock In/Out
?>

<div class="content-card">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-exchange-alt" style="color: var(--accent-purple);"></i> บันทึก รับเข้า - เบิกออก
    </h2>
    
    <form id="transactionForm" class="grid-form">
        <div class="form-group">
            <label>ประเภทรายการ</label>
            <select name="type" class="form-control" required>
                <option value="in">รับเข้าสินค้า (+)</option>
                <option value="out">เบิกออกสินค้า (-)</option>
            </select>
        </div>
        <div class="form-group">
            <label>เลือกสินค้า</label>
            <select name="product_id" class="form-control" required>
                <option value="">-- เลือกสินค้า --</option>
                <?php
                $prod_sql = "SELECT id, name, sku FROM stock_products WHERE company_id = ? ORDER BY name ASC";
                $prod_stmt = mysqli_prepare($conn, $prod_sql);
                mysqli_stmt_bind_param($prod_stmt, "i", $company_id);
                mysqli_stmt_execute($prod_stmt);
                $prod_res = mysqli_stmt_get_result($prod_stmt);
                while ($prod = mysqli_fetch_assoc($prod_res)) {
                    echo "<option value='{$prod['id']}'>{$prod['name']} ({$prod['sku']})</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>จำนวน</label>
            <input type="number" name="qty" class="form-control" min="1" required>
        </div>
        <div class="form-group">
            <label>คลังสินค้า</label>
            <select name="warehouse_id" class="form-control" required>
                <option value="">-- เลือกคลังสินค้า --</option>
                <?php
                $w_sql = "SELECT id, name FROM stock_warehouses WHERE company_id = ? ORDER BY name ASC";
                $w_stmt = mysqli_prepare($conn, $w_sql);
                mysqli_stmt_bind_param($w_stmt, "i", $company_id);
                mysqli_stmt_execute($w_stmt);
                $w_res = mysqli_stmt_get_result($w_stmt);
                while ($w = mysqli_fetch_assoc($w_res)) {
                    echo "<option value='{$w['id']}'>{$w['name']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>วันที่รายการ</label>
            <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group" style="grid-column: span 3;">
            <label>หมายเหตุ / รายละเอียด</label>
            <input type="text" name="note" class="form-control" placeholder="เช่น รับจาก Supplier A, เบิกไปใช้หน้างาน B">
        </div>
        <div style="grid-column: span 2; text-align: right;">
            <button type="submit" class="btn-primary">
                <i class="fas fa-check-circle"></i> ยืนยันรายการ
            </button>
        </div>
    </form>
</div>

<div class="content-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin: 0; font-size: 1.3rem;">ประวัติรายการล่าสุด</h2>
        <div style="position: relative; width: 300px;">
            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9CA3AF;"></i>
            <input type="text" id="transSearch" class="form-control" placeholder="ค้นหาสินค้า, คลัง, หมายเหตุ..." style="padding-left: 2.5rem;" onkeyup="loadTransactions()">
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #F9FAFB; border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 1rem; text-align: left;">วันที่</th>
                    <th style="padding: 1rem; text-align: left;">ประเภท</th>
                    <th style="padding: 1rem; text-align: left;">สินค้า</th>
                    <th style="padding: 1rem; text-align: right;">จำนวน</th>
                    <th style="padding: 1rem; text-align: left;">หมายเหตุ</th>
                    <th style="padding: 1rem; text-align: center;">จัดการ</th>
                </tr>
            </thead>
            <tbody id="transactionHistory">
                <!-- History will be loaded here via AJAX -->
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    loadTransactions();

    $('#transactionForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'stock_action.php?action=add_transaction',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire('สำเร็จ', res.message, 'success');
                    $('#transactionForm')[0].reset();
                    $('input[name="transaction_date"]').val('<?= date('Y-m-d') ?>');
                    loadTransactions();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }
        });
    });
});

function loadTransactions() {
    const search = $('#transSearch').val();
    $.ajax({
        url: 'stock_action.php?action=get_transactions',
        type: 'GET',
        data: { search: search },
        success: function(html) {
            $('#transactionHistory').html(html);
        }
    });
}

function deleteTransaction(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบรายการนี้ใช่หรือไม่? (สต็อกจะถูกปรับคืน)",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2430',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'stock_action.php?action=delete_transaction',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('ลบแล้ว!', res.message, 'success');
                        loadTransactions();
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

<?php
// Requisitions Tab - Requisition Forms
?>

<div class="content-card">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-file-invoice" style="color: var(--accent-purple);"></i> สร้างใบเบิกสินค้า
    </h2>
    
    <form id="requisitionForm" class="grid-form">
        <div class="form-group">
            <label>เลขที่ใบเบิก *</label>
            <input type="text" name="req_no" class="form-control" value="REQ-<?= date('YmdHis') ?>" required>
        </div>
        <div class="form-group">
            <label>วันที่เบิก *</label>
            <input type="date" name="requisition_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        
        <div class="form-group">
            <label>เลขที่ PO/ใบสั่งซื้อ</label>
            <input type="text" name="po_no" class="form-control" placeholder="ระบุเลขที่ PO">
        </div>
        <div class="form-group">
            <label>เลขที่ใบสั่งขาย</label>
            <input type="text" name="so_no" class="form-control" placeholder="ระบุเลขที่ SO">
        </div>

        <div class="form-group">
            <label>ชื่อลูกค้า *</label>
            <input type="text" name="customer_name" class="form-control" placeholder="ระบุชื่อลูกค้า" required>
        </div>
        <div class="form-group">
            <label>เบอร์โทรศัพท์</label>
            <input type="text" name="phone" class="form-control" placeholder="ระบุเบอร์โทรศัพท์">
        </div>

        <div class="form-group" style="grid-column: span 2;">
            <label>ที่อยู่จัดส่ง</label>
            <textarea name="shipping_address" class="form-control" rows="3" placeholder="ระบุที่อยู่จัดส่ง..."></textarea>
        </div>

        <div class="form-group" style="grid-column: span 2;">
            <label>ช่องทางการจัดส่ง</label>
            <input type="text" name="shipping_method" class="form-control" placeholder="เช่น รถบริษัท, Kerry, Flash">
        </div>
        
        <div style="grid-column: 1/-1;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; margin-top: 1rem; border-top: 1px solid var(--border-color); pt-4">รายการสินค้า *</h3>
            <div id="reqItemsContainer">
                <div class="req-item-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-end;">
                    <div class="form-group" style="flex: 2;">
                        <label>เลือกสินค้า</label>
                        <select name="items[0][product_id]" class="form-control" required>
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
                    <div class="form-group" style="flex: 1;">
                        <label>จำนวน</label>
                        <input type="number" name="items[0][qty]" class="form-control" min="1" required>
                    </div>
                    <button type="button" class="btn-primary" style="background: #EF4444; padding: 0.8rem;" onclick="removeRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <button type="button" class="btn-primary" style="background: #10B981;" onclick="addRow()">
                <i class="fas fa-plus"></i> เพิ่มรายการ
            </button>
        </div>

        <div style="grid-column: 1/-1; text-align: right; margin-top: 2rem;">
            <button type="submit" class="btn-primary" style="padding: 1rem 2.5rem; font-size: 1rem;">
                <i class="fas fa-save"></i> บันทึกใบเบิกสินค้า
            </button>
        </div>
    </form>
</div>

<div class="content-card">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem;">ประวัติใบเบิก</h2>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #F9FAFB; border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 1rem; text-align: left;">วันที่เบิก</th>
                    <th style="padding: 1rem; text-align: left;">เลขที่ใบเบิก</th>
                    <th style="padding: 1rem; text-align: left;">ลูกค้า</th>
                    <th style="padding: 1rem; text-align: center;">สถานะ</th>
                    <th style="padding: 1rem; text-align: center;">จัดการ</th>
                </tr>
            </thead>
            <tbody id="requisitionHistory">
                <!-- Requisitions will be loaded here via AJAX -->
            </tbody>
        </table>
    </div>
</div>

<script>
let rowCount = 1;

function addRow() {
    const html = `
    <div class="req-item-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-end;">
        <div class="form-group" style="flex: 2;">
            <label>เลือกสินค้า</label>
            <select name="items[${rowCount}][product_id]" class="form-control" required>
                <option value="">-- เลือกสินค้า --</option>
                <?php
                mysqli_data_seek($prod_res, 0);
                while ($prod = mysqli_fetch_assoc($prod_res)) {
                    echo "<option value='{$prod['id']}'>{$prod['name']} ({$prod['sku']})</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group" style="flex: 1;">
            <label>จำนวน</label>
            <input type="number" name="items[${rowCount}][qty]" class="form-control" min="1" required>
        </div>
        <button type="button" class="btn-primary" style="background: #EF4444; padding: 0.8rem;" onclick="removeRow(this)">
            <i class="fas fa-trash"></i>
        </button>
    </div>`;
    $('#reqItemsContainer').append(html);
    rowCount++;
}

function removeRow(btn) {
    if ($('.req-item-row').length > 1) {
        $(btn).closest('.req-item-row').remove();
    }
}

$(document).ready(function() {
    loadRequisitions();

    $('#requisitionForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'stock_action.php?action=add_requisition',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire('สำเร็จ', res.message, 'success');
                    $('#requisitionForm')[0].reset();
                    $('#reqItemsContainer').html('');
                    rowCount = 0;
                    addRow();
                    loadRequisitions();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }
        });
    });
});

function loadRequisitions() {
    $.ajax({
        url: 'stock_action.php?action=get_requisitions',
        type: 'GET',
        success: function(html) {
            $('#requisitionHistory').html(html);
        }
    });
}

function updateRequisitionStatus(id, status) {
    $.ajax({
        url: 'stock_action.php?action=update_requisition_status',
        type: 'POST',
        data: { id: id, status: status },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                loadRequisitions();
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        }
    });
}
</script>

<?php
// Production Tab - Production Orders
?>

<div class="content-card">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-industry" style="color: var(--accent-purple);"></i> สร้างใบสั่งผลิต
    </h2>
    
    <form id="productionForm" class="grid-form">
        <div class="form-group">
            <label>เลขที่ใบสั่งผลิต *</label>
            <input type="text" name="order_no" class="form-control" value="PO-<?= date('YmdHis') ?>" required>
        </div>
        <div class="form-group">
            <label>วันที่สั่งผลิต *</label>
            <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        
        <div class="form-group">
            <label>วันที่กำหนดเสร็จ</label>
            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
            <label>ชื่อลูกค้า *</label>
            <input type="text" name="customer_name" class="form-control" placeholder="ระบุชื่อลูกค้า" required>
        </div>

        <div class="form-group">
            <label>ชื่อโครงการ</label>
            <input type="text" name="project_name" class="form-control" placeholder="ระบุชื่อโครงการ">
        </div>
        <div class="form-group">
            <label>ชื่อสินค้า *</label>
            <select name="product_id" id="prod_select" class="form-control" required onchange="updateProductInfo(this)">
                <option value="">-- เลือกสินค้า --</option>
                <?php
                $prod_sql = "SELECT id, name, sku, unit FROM stock_products WHERE company_id = ? ORDER BY name ASC";
                $prod_stmt = mysqli_prepare($conn, $prod_sql);
                mysqli_stmt_bind_param($prod_stmt, "i", $company_id);
                mysqli_stmt_execute($prod_stmt);
                $prod_res = mysqli_stmt_get_result($prod_stmt);
                $products_data = [];
                while ($prod = mysqli_fetch_assoc($prod_res)) {
                    $products_data[$prod['id']] = $prod;
                    echo "<option value='{$prod['id']}'>{$prod['name']}</option>";
                }
                ?>
            </select>
            <script>const productsData = <?= json_encode($products_data) ?>;</script>
        </div>

        <div class="form-group">
            <label>รหัสสินค้า (SKU)</label>
            <input type="text" name="sku" id="prod_sku" class="form-control" placeholder="SKU">
        </div>
        <div class="form-group">
            <label>จำนวนที่ผลิต *</label>
            <input type="number" name="qty" class="form-control" min="1" required>
        </div>

        <div class="form-group">
            <label>หน่วย *</label>
            <input type="text" name="unit" id="prod_unit" class="form-control" placeholder="เช่น ชิ้น, ชุด" required>
        </div>
        <div class="form-group">
            <label>ขนาด/มิติ</label>
            <input type="text" name="dimensions" class="form-control" placeholder="เช่น 2.4x1.2 เมตร">
        </div>

        <div style="grid-column: 1/-1; margin-top: 1rem;">
            <label style="font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 1rem;">รายการวัสดุ (BOM)</label>
            <div id="bomItemsContainer">
                <!-- BOM items will be added here -->
                <p id="noBomText" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">ยังไม่มีรายการวัสดุ</p>
            </div>
            <button type="button" class="btn-primary" style="background: var(--accent-purple); padding: 0.6rem 1.2rem; font-size: 0.9rem;" onclick="addBomRow()">
                <i class="fas fa-plus"></i> เพิ่มวัสดุจากคลัง
            </button>
        </div>

        <div class="form-group" style="grid-column: span 2; margin-top: 1rem;">
            <label>ขั้นตอนการทำงาน/คำแนะนำ</label>
            <textarea name="instructions" class="form-control" rows="3" placeholder="ระบุขั้นตอนการทำงาน..."></textarea>
        </div>

        <div class="form-group" style="grid-column: span 2;">
            <label>มาตรฐานการตรวจสอบ (QC)</label>
            <textarea name="qc_standards" class="form-control" rows="3" placeholder="ระบุมาตรฐานการตรวจสอบ..."></textarea>
        </div>

        <div class="form-group">
            <label>ผู้สั่งผลิต</label>
            <input type="text" name="ordered_by" class="form-control" placeholder="ชื่อผู้สั่งผลิต">
        </div>
        <div class="form-group">
            <label>ผู้รับคำสั่ง/หัวหน้าช่าง</label>
            <input type="text" name="foreman" class="form-control" placeholder="ชื่อหัวหน้าช่าง">
        </div>

        <div style="grid-column: 1/-1; text-align: center; margin-top: 2rem;">
            <button type="submit" class="btn-primary" style="background: #10B981; width: 100%; padding: 1rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-check-square"></i> สร้างใบสั่งผลิต
            </button>
        </div>
    </form>
</div>

<div class="content-card">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-list-ul" style="color: var(--accent-purple);"></i> รายการใบสั่งผลิต
    </h2>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #F9FAFB; border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 1rem; text-align: left;">เลขที่ใบสั่ง</th>
                    <th style="padding: 1rem; text-align: left;">วันที่สั่ง</th>
                    <th style="padding: 1rem; text-align: left;">ลูกค้า/โครงการ</th>
                    <th style="padding: 1rem; text-align: left;">สินค้า</th>
                    <th style="padding: 1rem; text-align: right;">จำนวน</th>
                    <th style="padding: 1rem; text-align: center;">สถานะ</th>
                    <th style="padding: 1rem; text-align: center;">จัดการ</th>
                </tr>
            </thead>
            <tbody id="productionHistory">
                <!-- Production orders will be loaded here via AJAX -->
            </tbody>
        </table>
    </div>
</div>

<script>
let bomRowCount = 0;

function updateProductInfo(select) {
    const id = select.value;
    if (id && productsData[id]) {
        $('#prod_sku').val(productsData[id].sku);
        $('#prod_unit').val(productsData[id].unit);
    } else {
        $('#prod_sku').val('');
        $('#prod_unit').val('');
    }
}

function addBomRow() {
    $('#noBomText').hide();
    const html = `
    <div class="bom-item-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-end; background: #F9FAFB; padding: 1rem; border-radius: 0.8rem; border: 1px solid var(--border-color);">
        <div class="form-group" style="flex: 3;">
            <label>วัสดุ/วัตถุดิบ</label>
            <select name="bom[${bomRowCount}][product_id]" class="form-control" required>
                <option value="">-- เลือกวัสดุ --</option>
                <?php
                mysqli_data_seek($prod_res, 0);
                while ($prod = mysqli_fetch_assoc($prod_res)) {
                    echo "<option value='{$prod['id']}'>{$prod['name']} ({$prod['sku']})</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group" style="flex: 1;">
            <label>จำนวนที่ใช้</label>
            <input type="number" step="0.01" name="bom[${bomRowCount}][qty]" class="form-control" min="0.01" required>
        </div>
        <button type="button" class="btn-primary" style="background: #EF4444; padding: 0.8rem;" onclick="removeBomRow(this)">
            <i class="fas fa-trash"></i>
        </button>
    </div>`;
    $('#bomItemsContainer').append(html);
    bomRowCount++;
}

function removeBomRow(btn) {
    $(btn).closest('.bom-item-row').remove();
    if ($('.bom-item-row').length === 0) {
        $('#noBomText').show();
    }
}

$(document).ready(function() {
    loadProductionOrders();

    $('#productionForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'stock_action.php?action=add_production',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire('สำเร็จ', res.message, 'success');
                    $('#productionForm')[0].reset();
                    $('#bomItemsContainer').html('<p id="noBomText" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">ยังไม่มีรายการวัสดุ</p>');
                    bomRowCount = 0;
                    loadProductionOrders();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }
        });
    });
});

function loadProductionOrders() {
    $.ajax({
        url: 'stock_action.php?action=get_productions',
        type: 'GET',
        success: function(html) {
            $('#productionHistory').html(html);
        }
    });
}

function updateProductionStatus(id, status) {
    $.ajax({
        url: 'stock_action.php?action=update_production_status',
        type: 'POST',
        data: { id: id, status: status },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                loadProductionOrders();
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        }
    });
}
</script>

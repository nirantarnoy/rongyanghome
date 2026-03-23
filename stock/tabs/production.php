<?php
// Production Tab - Production Orders
?>

<div class="content-card">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-industry" style="color: var(--accent-purple);"></i> สร้างใบสั่งผลิต
    </h2>
    
    <form id="productionForm" class="grid-form">
        <input type="hidden" name="id" id="production_id">
         <div class="form-group">
            <label>เลขที่ใบสั่งผลิต *</label>
            <input type="text" name="order_no" class="form-control" value="WO<?php 
                $ty = date('Y') + 543; 
                echo substr($ty, -2) . date('mdHis'); 
            ?>" required>
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

                // Fetch warehouses for later use in JS
                $wh_sql = "SELECT id, name FROM stock_warehouses WHERE company_id = ? ORDER BY name ASC";
                $wh_stmt = mysqli_prepare($conn, $wh_sql);
                mysqli_stmt_bind_param($wh_stmt, "i", $company_id);
                mysqli_stmt_execute($wh_stmt);
                $wh_res = mysqli_stmt_get_result($wh_stmt);
                $warehouses = [];
                while ($wh = mysqli_fetch_assoc($wh_res)) {
                    $warehouses[] = $wh;
                }
                ?>
            </select>
            <script>
                const productsData = <?= json_encode($products_data) ?>;
            </script>
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
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="btn-primary" style="background: var(--accent-purple); padding: 0.6rem 1.2rem; font-size: 0.9rem;" onclick="addBomRow()">
                    <i class="fas fa-plus"></i> เพิ่มวัสดุ
                </button>
                <button type="button" class="btn-primary" style="background: #10B981; padding: 0.6rem 1.2rem; font-size: 0.9rem;" onclick="openStockSelector()">
                    <i class="fas fa-warehouse"></i> ดึงจากคลังสินค้า
                </button>
            </div>
        </div>

        <div style="grid-column: 1/-1; margin-top: 2rem;">
            <label style="font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 1rem;">ผลิตภัณฑ์พลอยได้หรือเศษผลผลิตคงเหลือ</label>
            <div id="byproductItemsContainer" style="margin-bottom: 1rem;">
                <p id="noByproductText" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">ยังไม่มีรายการผลิตภัณฑ์พลอยได้</p>
            </div>
            <button type="button" class="btn-primary" style="background: #6366F1; padding: 0.6rem 1.2rem; font-size: 0.9rem;" onclick="addByproductRow()">
                <i class="fas fa-plus"></i> เพิ่มผลิตภัณฑ์พลอยได้
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

        <div style="grid-column: 1/-1; text-align: center; margin-top: 2rem; display: flex; justify-content: center; gap: 1rem;">
            <button type="button" id="btnCancelProductionEdit" class="btn-primary" style="background: #6B7280; display: none; width: 200px;">
                ยกเลิก
            </button>
            <button type="submit" id="btnSubmitProduction" class="btn-primary" style="background: #10B981; width: 100%; padding: 1rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-check-square"></i> สร้างใบสั่งผลิต
            </button>
        </div>
    </form>
</div>

<div class="content-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-list-ul" style="color: var(--accent-purple);"></i> รายการใบสั่งผลิต
        </h2>
        <div style="position: relative; width: 300px;">
            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9CA3AF;"></i>
            <input type="text" id="prodSearch" class="form-control" placeholder="ค้นหาเลขที่, ลูกค้า, สินค้า..." style="padding-left: 2.5rem;" onkeyup="loadProductionOrders()">
        </div>
    </div>
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

<!-- View Production Modal -->
<div id="viewProductionModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
    <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 2rem; border-radius: 1rem; width: 80%; max-width: 800px; position: relative; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <span class="close" onclick="closeProductionModal()" style="position: absolute; right: 1.5rem; top: 1rem; font-size: 2rem; cursor: pointer; color: #9CA3AF;">&times;</span>
        <div id="viewProductionContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
let bomRowCount = 0;
let byproductRowCount = 0;


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

function addByproductRow() {
    $('#noByproductText').hide();
    const html = `
    <div class="byproduct-item-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-end; background: #F0FDF4; padding: 1rem; border-radius: 0.8rem; border: 1px solid #BBF7D0;">
        <div class="form-group" style="flex: 2;">
            <label>รายการผลิตภัณฑ์พลอยได้</label>
            <input type="text" name="byproducts[${byproductRowCount}][name]" class="form-control" placeholder="ชื่อสินค้า/เศษวัสดุ" required>
        </div>
        <div class="form-group" style="flex: 1;">
            <label>จำนวน</label>
            <input type="number" step="0.01" name="byproducts[${byproductRowCount}][qty]" class="form-control byproduct-qty" min="0" value="0" required onchange="calculateByproductTotal(this)">
        </div>
        <div class="form-group" style="flex: 1;">
            <label>หน่วย</label>
            <input type="text" name="byproducts[${byproductRowCount}][unit]" class="form-control" placeholder="หน่วย" required>
        </div>
        <div class="form-group" style="flex: 1;">
            <label>ราคา/หน่วย</label>
            <input type="number" step="0.01" name="byproducts[${byproductRowCount}][price]" class="form-control byproduct-price" min="0" value="0" required onchange="calculateByproductTotal(this)">
        </div>
        <div class="form-group" style="flex: 1;">
            <label>รวมราคา</label>
            <input type="number" step="0.01" name="byproducts[${byproductRowCount}][total]" class="form-control byproduct-total" value="0" readonly>
        </div>
        <button type="button" class="btn-primary" style="background: #EF4444; padding: 0.8rem;" onclick="removeByproductRow(this)">
            <i class="fas fa-trash"></i>
        </button>
    </div>`;
    $('#byproductItemsContainer').append(html);
    byproductRowCount++;
}

function addByproductRowWithData(data) {
    $('#noByproductText').hide();
    const html = `
    <div class="byproduct-item-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-end; background: #F0FDF4; padding: 1rem; border-radius: 0.8rem; border: 1px solid #BBF7D0;">
        <div class="form-group" style="flex: 2;">
            <label>รายการผลิตภัณฑ์พลอยได้</label>
            <input type="text" name="byproducts[${byproductRowCount}][name]" class="form-control" value="${data.name}" required>
        </div>
        <div class="form-group" style="flex: 1;">
            <label>จำนวน</label>
            <input type="number" step="0.01" name="byproducts[${byproductRowCount}][qty]" class="form-control byproduct-qty" min="0" value="${data.qty}" required onchange="calculateByproductTotal(this)">
        </div>
        <div class="form-group" style="flex: 1;">
            <label>หน่วย</label>
            <input type="text" name="byproducts[${byproductRowCount}][unit]" class="form-control" value="${data.unit}" required>
        </div>
        <div class="form-group" style="flex: 1;">
            <label>ราคา/หน่วย</label>
            <input type="number" step="0.01" name="byproducts[${byproductRowCount}][price]" class="form-control byproduct-price" min="0" value="${data.price}" required onchange="calculateByproductTotal(this)">
        </div>
        <div class="form-group" style="flex: 1;">
            <label>รวมราคา</label>
            <input type="number" step="0.01" name="byproducts[${byproductRowCount}][total]" class="form-control byproduct-total" value="${data.total}" readonly>
        </div>
        <button type="button" class="btn-primary" style="background: #EF4444; padding: 0.8rem;" onclick="removeByproductRow(this)">
            <i class="fas fa-trash"></i>
        </button>
    </div>`;
    $('#byproductItemsContainer').append(html);
    byproductRowCount++;
}

function calculateByproductTotal(el) {
    const row = $(el).closest('.byproduct-item-row');
    const qty = parseFloat(row.find('.byproduct-qty').val()) || 0;
    const price = parseFloat(row.find('.byproduct-price').val()) || 0;
    row.find('.byproduct-total').val((qty * price).toFixed(2));
}

function removeByproductRow(btn) {
    $(btn).closest('.byproduct-item-row').remove();
    if ($('.byproduct-item-row').length === 0) {
        $('#noByproductText').show();
    }
}

$(document).ready(function() {
    loadProductionOrders();

    $('#productionForm').on('submit', function(e) {
        e.preventDefault();
        const productionId = $('#production_id').val();
        const action = productionId ? 'update_production' : 'add_production';
        const byproductRows = $('.byproduct-item-row');
        
        async function submitForm(saveByproducts = 0) {
            let formData = $('#productionForm').serialize();
            if (saveByproducts) {
                formData += '&save_byproducts_as_products=1';
            }

            $.ajax({
                url: 'stock_action.php?action=' + action,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        if (res.warnings && res.warnings.length > 0) {
                            Swal.fire({
                                title: 'สำเร็จ',
                                html: res.message + '<br><br>' + 
                                      '<div style="text-align: left; background: #FFF9C4; padding: 1rem; border-radius: 0.5rem; border: 1px solid #FFD600; font-size: 0.9rem; color: #856404;">' +
                                      '<strong>การแจ้งเตือน:</strong><br>' + res.warnings.join('<br>') + 
                                      '</div>',
                                icon: 'success'
                            });
                        } else {
                            Swal.fire('สำเร็จ', res.message, 'success');
                        }
                        resetProductionForm();
                        loadProductionOrders();
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้: ' + error + '<br><br>กรุณาตรวจสอบ Console สำหรับรายละเอียด', 'error');
                }
            });
        }

        if (byproductRows.length > 0) {
            Swal.fire({
                title: 'สินค้าพลอยได้',
                text: 'ต้องการบันทึกสินค้าพลอยได้เหล่านี้เป็นสินค้าใหม่ในระบบด้วยหรือไม่?',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'ใช่, บันทึกเป็นสินค้าใหม่',
                denyButtonText: 'ไม่ต้องการ, บันทึกเฉพาะใบสั่งผลิต',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#10B981',
                denyButtonColor: '#6B7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitForm(1);
                } else if (result.isDenied) {
                    submitForm(0);
                }
            });
        } else {
            submitForm(0);
        }
    });

    $('#btnCancelProductionEdit').on('click', function() {
        resetProductionForm();
    });
});

function resetProductionForm() {
    $('#productionForm')[0].reset();
    $('#production_id').val('');
    $('#bomItemsContainer').html('<p id="noBomText" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">ยังไม่มีรายการวัสดุ</p>');
    bomRowCount = 0;
    $('#byproductItemsContainer').html('<p id="noByproductText" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">ยังไม่มีรายการผลิตภัณฑ์พลอยได้</p>');
    byproductRowCount = 0;
    $('#btnSubmitProduction').html('<i class="fas fa-check-square"></i> สร้างใบสั่งผลิต');
    $('#btnCancelProductionEdit').hide();
    $('.content-card h2:first').html('<i class="fas fa-industry" style="color: var(--accent-purple);"></i> สร้างใบสั่งผลิต');
    // Reset order number to default
    const now = new Date();
    const ty = (now.getFullYear() + 543).toString().slice(-2);
    const dateStr = ty + 
                    (now.getMonth() + 1).toString().padStart(2, '0') + 
                    now.getDate().toString().padStart(2, '0') + 
                    now.getHours().toString().padStart(2, '0') + 
                    now.getMinutes().toString().padStart(2, '0') + 
                    now.getSeconds().toString().padStart(2, '0');
    $('input[name="order_no"]').val('WO' + dateStr);
}

function loadProductionOrders() {
    const search = $('#prodSearch').val();
    $.ajax({
        url: 'stock_action.php?action=get_productions',
        type: 'GET',
        data: { search: search },
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

function editProduction(id) {
    $.ajax({
        url: 'stock_action.php?action=get_production',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                const order = res.data;
                const bom = res.bom;
                const byproducts = res.byproducts;

                $('#production_id').val(order.id);
                $('input[name="order_no"]').val(order.order_no);
                
                // Format dates to YYYY-MM-DD for input[type=date]
                if (order.order_date) $('input[name="order_date"]').val(order.order_date.split(' ')[0]);
                if (order.due_date) $('input[name="due_date"]').val(order.due_date.split(' ')[0]);
                
                $('input[name="customer_name"]').val(order.customer_name);
                $('input[name="project_name"]').val(order.project_name);
                $('#prod_select').val(order.product_id);
                $('#prod_sku').val(order.sku);
                $('input[name="qty"]').val(order.qty);
                $('#prod_unit').val(order.unit);
                $('input[name="dimensions"]').val(order.dimensions);
                $('textarea[name="instructions"]').val(order.instructions);
                $('textarea[name="qc_standards"]').val(order.qc_standards);
                $('input[name="ordered_by"]').val(order.ordered_by);
                $('input[name="foreman"]').val(order.foreman);
                
                // Load BOM
                $('#bomItemsContainer').html('');
                bomRowCount = 0;
                if (bom && bom.length > 0) {
                    $('#noBomText').hide();
                    bom.forEach(item => {
                        addBomRowWithData(item.product_id, item.qty);
                    });
                } else {
                    $('#bomItemsContainer').html('<p id="noBomText" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">ยังไม่มีรายการวัสดุ</p>');
                }

                // Load Byproducts
                $('#byproductItemsContainer').html('');
                byproductRowCount = 0;
                if (byproducts && byproducts.length > 0) {
                    $('#noByproductText').hide();
                    byproducts.forEach(item => {
                        addByproductRowWithData(item);
                    });
                } else {
                    $('#byproductItemsContainer').html('<p id="noByproductText" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">ยังไม่มีรายการผลิตภัณฑ์พลอยได้</p>');
                }
                
                $('#btnSubmitProduction').html('<i class="fas fa-save"></i> อัปเดตใบสั่งผลิต');
                $('#btnCancelProductionEdit').show();
                $('.content-card h2:first').html('<i class="fas fa-edit" style="color: var(--accent-purple);"></i> แก้ไขใบสั่งผลิต');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        },
        error: function() {
            Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลใบสั่งผลิตได้', 'error');
        }
    });
}

function addBomRowWithData(productId, qty) {
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
            <input type="number" step="0.01" name="bom[${bomRowCount}][qty]" class="form-control" min="0.01" value="${qty}" required>
        </div>
        <button type="button" class="btn-primary" style="background: #EF4444; padding: 0.8rem;" onclick="removeBomRow(this)">
            <i class="fas fa-trash"></i>
        </button>
    </div>`;
    $('#bomItemsContainer').append(html);
    $(`select[name="bom[${bomRowCount}][product_id]"]`).val(productId);
    bomRowCount++;
}

function deleteProduction(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบใบสั่งผลิตนี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2430',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'stock_action.php?action=delete_production',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('ลบแล้ว!', res.message, 'success');
                        loadProductionOrders();
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

function viewProduction(id) {
    $.ajax({
        url: 'stock_action.php?action=get_production_details',
        type: 'GET',
        data: { id: id },
        success: function(html) {
            $('#viewProductionContent').html(html);
            $('#viewProductionModal').fadeIn(200);
        }
    });
}

function closeProductionModal() {
    $('#viewProductionModal').fadeOut(200);
}

// Close modal when clicking outside
$(window).on('click', function(event) {
    if ($(event.target).is('#viewProductionModal')) {
        closeProductionModal();
    }
});

function finishProduction(id) {
    // 1. Get Production details first to show in confirmation
    $.ajax({
        url: 'stock_action.php?action=get_production',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(res) {
            if (res.status !== 'success') {
                Swal.fire('ผิดพลาด', res.message, 'error');
                return;
            }
            const order = res.data;
            const byproducts = res.byproducts || [];

            // 2. Fetch warehouses for the selection
            const warehouses = <?= json_encode($warehouses ?? []) ?>;
            let whOptions = "";
            warehouses.forEach(wh => {
                whOptions += `<option value="${wh.id}">${wh.name}</option>`;
            });

            let byproductHtml = "";
            if (byproducts.length > 0) {
                byproductHtml = `<div style="margin-top: 1.5rem; border-top: 2px solid var(--accent-purple); padding-top: 1rem;">
                    <h4 style="margin: 0 0 1rem 0; color: var(--accent-purple); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-boxes"></i> สินค้าพลอยได้
                    </h4>`;
                
                byproducts.forEach((bp, index) => {
                    byproductHtml += `
                        <div style="text-align: left; background: #F0FDF4; padding: 0.8rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #BBF7D0;">
                            <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem;">${bp.name} (${bp.qty} ${bp.unit})</div>
                            <label style="display: block; margin-bottom: 0.3rem; font-size: 0.85rem; font-weight: 500;">เลือกคลังสินค้าสำหรับรับเข้า</label>
                            <select id="bp_warehouse_${bp.id}" class="swal2-input bp-warehouse-select" data-bp-id="${bp.id}" style="width: 100%; margin: 0; display: block; height: 2.5rem; font-size: 0.9rem;">
                                <option value="">-- เลือกคลังสินค้า --</option>
                                ${whOptions}
                            </select>
                        </div>
                    `;
                });
                byproductHtml += `</div>`;
            }

            Swal.fire({
                title: 'ยืนยันการผลิตสำเร็จ',
                html: `
                    <div style="text-align: left; background: #f9fafb; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #e5e7eb;">
                        <p style="margin: 0;"><strong>เลขที่:</strong> ${order.order_no}</p>
                        <p style="margin: 0;"><strong>สินค้า:</strong> ${order.product_name || order.sku}</p>
                    </div>
                    <div style="text-align: left;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">ระบุจำนวนที่ผลิตได้จริง (${order.unit})</label>
                        <input type="number" id="finish_qty" class="swal2-input" value="${order.qty}" style="width: 80%; margin: 0 auto 1rem auto; display: block;">
                        
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">คลังสินค้าที่จะนำหน้าเข้า</label>
                        <select id="finish_warehouse" class="swal2-input" style="width: 80%; margin: 0 auto; display: block;">
                            <option value="">-- เลือกคลังสินค้า --</option>
                            ${whOptions}
                        </select>
                        
                        ${byproductHtml}
                        
                        <p style="font-size: 0.8rem; color: #6B7280; margin-top: 1rem; border-top: 1px dashed #ccc; padding-top: 0.5rem;">
                            * ระบบจะทำการเพิ่มสต็อกสินค้าสำเร็จรูป และตัดสต็อกวัสดุ (BOM) ให้อัตโนมัติ
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'ยืนยันเสร็จสิ้น',
                cancelButtonText: 'ยกเลิก',
                width: '600px',
                preConfirm: () => {
                    const warehouse_id = Swal.getPopup().querySelector('#finish_warehouse').value;
                    const prod_qty = Swal.getPopup().querySelector('#finish_qty').value;
                    
                    if (!warehouse_id) {
                        Swal.showValidationMessage(`กรุณาเลือกคลังสินค้าหลัก`);
                        return false;
                    }
                    if (!prod_qty || prod_qty <= 0) {
                        Swal.showValidationMessage(`กรุณาระบุจำนวนที่ถูกต้อง`);
                        return false;
                    }

                    // Collect byproduct warehouse selections
                    const byproductWarehouses = {};
                    let bpValid = true;
                    $('.bp-warehouse-select').each(function() {
                        const bpId = $(this).data('bp-id');
                        const whId = $(this).val();
                        if (!whId) {
                            bpValid = false;
                        }
                        byproductWarehouses[bpId] = whId;
                    });

                    if (!bpValid) {
                        Swal.showValidationMessage(`กรุณาเลือกคลังสินค้าสำหรับสินค้าพลอยได้ทุกรายการ`);
                        return false;
                    }

                    return { 
                        warehouse_id: warehouse_id, 
                        prod_qty: prod_qty,
                        byproduct_warehouses: byproductWarehouses 
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'stock_action.php?action=complete_production',
                        type: 'POST',
                        data: { 
                            id: id, 
                            warehouse_id: result.value.warehouse_id,
                            prod_qty: result.value.prod_qty,
                            byproduct_warehouses: result.value.byproduct_warehouses
                        },
                        dataType: 'json',
                        success: function(res) {
                            if (res.status === 'success') {
                                Swal.fire('สำเร็จ', res.message, 'success');
                                loadProductionOrders();
                            } else {
                                Swal.fire('ผิดพลาด', res.message, 'error');
                            }
                        }
                    });
                }
            });
        }
    });
}

function approveMaterialReq(id) {
    Swal.fire({
        title: 'ยืนยันการอนุมัติ?',
        text: "ระบบจะทำการตัดสต็อกสินค้าตามรายการในใบเบิกนี้",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'อนุมัติและตัดสต็อก',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'stock_action.php?action=approve_material_requisition',
                type: 'POST',
                data: { requisition_id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('สำเร็จ', res.message, 'success');
                        // Refresh the modal content
                        const prodId = $('#production_id').val();
                        // If we are in edit mode, we have the ID in #production_id
                        // But if we just clicked "view", we might need to get it from the modal content or a global variable
                        // For now, let's try to find it from the modal content if #production_id is empty
                        const currentProdId = prodId || $('#viewProductionContent').find('a[href*="print_production.php"]').attr('href').split('id=')[1];
                        if (currentProdId) viewProduction(currentProdId);
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

function rejectMaterialReq(id) {
    Swal.fire({
        title: 'ยืนยันการปฏิเสธ?',
        text: "คุณต้องการปฏิเสธใบเบิกวัสดุนี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'ปฏิเสธ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'stock_action.php?action=reject_material_requisition',
                type: 'POST',
                data: { requisition_id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('สำเร็จ', res.message, 'success');
                        const currentProdId = $('#production_id').val() || $('#viewProductionContent').find('a[href*="print_production.php"]').attr('href').split('id=')[1];
                        if (currentProdId) viewProduction(currentProdId);
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

function openStockSelector() {
    Swal.fire({
        title: 'เลือกสินค้าจากคลังสินค้า',
        html: `
            <div class="text-left space-y-4" style="text-align: left;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">เลือกคลังสินค้า</label>
                    <select id="swal_warehouse_id" class="form-control" style="width: 100%;" onchange="loadWarehouseProducts(this.value)">
                        <option value="">-- เลือกคลังสินค้า --</option>
                    </select>
                </div>
                <div id="swal_product_list" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; rounded: 0.5rem; padding: 0.5rem; background: #f9fafb; min-height: 150px;">
                    <p style="text-align: center; color: #9ca3af; padding: 3rem;">กรุณาเลือกคลังสินค้าเพื่อดูรายการ</p>
                </div>
            </div>
        `,
        width: '700px',
        showConfirmButton: false,
        showCloseButton: true,
        didOpen: () => {
            $.ajax({
                url: 'stock_action.php?action=get_warehouses_json',
                type: 'GET',
                success: function(res) {
                    if (Array.isArray(res)) {
                        const select = $('#swal_warehouse_id');
                        res.forEach(wh => {
                            select.append(`<option value="${wh.id}">${wh.name}</option>`);
                        });
                    }
                }
            });
        }
    });
}

function loadWarehouseProducts(whId) {
    if (!whId) {
        $('#swal_product_list').html('<p style="text-align: center; color: #9ca3af; padding: 3rem;">กรุณาเลือกคลังสินค้าเพื่อดูรายการ</p>');
        return;
    }
    
    $('#swal_product_list').html('<p style="text-align: center; color: #6b7280; padding: 3rem;"><i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...</p>');
    
    $.ajax({
        url: 'stock_action.php?action=get_warehouse_products',
        type: 'GET',
        data: { warehouse_id: whId },
        success: function(res) {
            let html = '';
            if (!res || res.length === 0) {
                html = '<p style="text-align: center; color: #9ca3af; padding: 3rem;">ไม่มีสินค้าในคลังนี้</p>';
            } else {
                res.forEach(p => {
                    const productJson = JSON.stringify(p).replace(/'/g, "&#39;").replace(/"/g, '&quot;');
                    html += `
                        <div onclick='addSelectedBomProduct(${productJson})' style="display: flex; justify-content: space-between; align-items: center; padding: 0.8rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#A855F7'; this.style.backgroundColor='#FAF5FF';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='white';">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 0.3rem; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    ${p.image_url ? `<img src="${p.image_url}" style="width: 100%; height: 100%; object-fit: contain;">` : '<i class="fas fa-box" style="color: #d1d5db;"></i>'}
                                </div>
                                <div style="text-align: left;">
                                    <div style="font-weight: 600; color: #1f2937;">${p.name}</div>
                                    <div style="font-size: 0.75rem; color: #6b7280;">SKU: ${p.sku || '-'} | คงเหลือ: <span style="font-weight: 600; color: #374151;">${p.balance}</span> ${p.unit}</div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; color: #7C3AED;">฿${parseFloat(p.price).toLocaleString()}</div>
                                <div style="font-size: 0.7rem; color: #9ca3af; text-transform: uppercase; font-weight: 600;">คลิกเพื่อเลือก</div>
                            </div>
                        </div>
                    `;
                });
            }
            $('#swal_product_list').html(html);
        }
    });
}

function addSelectedBomProduct(p) {
    addBomRowWithData(p.id, 1);
    Swal.close();
}
</script>

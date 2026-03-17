<?php
// Requisitions Tab - Requisition Forms
?>

<div class="content-card">
    <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fas fa-file-invoice" style="color: var(--accent-purple);"></i> สร้างใบเบิกสินค้า
    </h2>
    
    <form id="requisitionForm" class="grid-form">
        <input type="hidden" name="id" id="requisition_id">
        <div class="form-group">
            <label>เลขที่ใบเบิก *</label>
            <input type="text" name="req_no" class="form-control" value="WH<?php 
                $ty = date('Y') + 543; 
                echo substr($ty, -2) . date('mdHis'); 
            ?>" required>
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
            <label>ชื่อผู้เบิก</label>
            <input type="text" name="requester_name" class="form-control" placeholder="ระบุชื่อผู้เบิก">
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
            <h3 style="font-size: 1rem; margin-bottom: 1rem; margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">รายการสินค้า *</h3>
            <div id="reqItemsContainer">
                <!-- Initial row will be added by JS -->
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="btn-primary" style="background: #10B981;" onclick="addRow()">
                    <i class="fas fa-plus"></i> เพิ่มรายการ
                </button>
                <button type="button" class="btn-primary" style="background: #6366F1;" onclick="openStockSelector()">
                    <i class="fas fa-warehouse"></i> ดึงจากคลังสินค้า
                </button>
            </div>
        </div>

        <div style="grid-column: 1/-1; text-align: right; margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
            <button type="button" id="btnCancelReqEdit" class="btn-primary" onclick="resetReqForm()" style="background: #6B7280; display: none;">
                <i class="fas fa-times"></i> ยกเลิก
            </button>
            <button type="submit" id="btnSubmitReq" class="btn-primary" style="padding: 1rem 2.5rem; font-size: 1rem;">
                <i class="fas fa-save"></i> บันทึกใบเบิกสินค้า
            </button>
        </div>
    </form>
</div>

<div class="content-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin: 0; font-size: 1.3rem;">ประวัติใบเบิก</h2>
        <div style="position: relative; width: 300px;">
            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9CA3AF;"></i>
            <input type="text" id="reqSearch" class="form-control" placeholder="ค้นหาเลขที่, ลูกค้า, ผู้เบิก..." style="padding-left: 2.5rem;" onkeyup="loadRequisitions()">
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background: #F9FAFB; border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 1rem; text-align: left;">วันที่เบิก</th>
                    <th style="padding: 1rem; text-align: left;">เลขที่ใบเบิก</th>
                    <th style="padding: 1rem; text-align: left;">ลูกค้า</th>
                    <th style="padding: 1rem; text-align: center;">สถานะ</th>
                    <th style="padding: 1rem; text-align: center;">จัดการ</th>
                    <th style="padding: 1rem; text-align: center; color: #DC2626;">บันทึกรายจ่าย</th>
                </tr>
            </thead>
            <tbody id="requisitionHistory">
                <!-- Requisitions will be loaded here via AJAX -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal บันทึกค่าใช้จ่ายโครงการ -->
<div id="projectExpenseModal" class="modal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
    <div class="modal-content" style="background-color: #fefefe; margin: 2% auto; padding: 2rem; border-radius: 1.5rem; width: 90%; max-width: 700px; position: relative; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
        <span class="close" onclick="closeExpenseModal()" style="position: absolute; right: 1.5rem; top: 1rem; font-size: 2rem; cursor: pointer; color: #9CA3AF;">&times;</span>
        
        <h2 style="margin-top: 0; margin-bottom: 2rem; color: #DC2626; font-size: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-file-invoice-dollar"></i> บันทึกค่าใช้จ่ายโครงการ
        </h2>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; background: #F9FAFB; padding: 1.5rem; border-radius: 1rem; border: 1px solid #E5E7EB;">
            <div>
                <p style="font-size: 0.85rem; color: #6B7280; margin: 0;">เลขที่ใบเบิก</p>
                <p style="font-weight: bold; margin: 0.25rem 0;" id="expDispReqNo">-</p>
            </div>
            <div>
                <p style="font-size: 0.85rem; color: #6B7280; margin: 0;">วันที่เบิก</p>
                <p style="font-weight: bold; margin: 0.25rem 0;" id="expDispDate">-</p>
            </div>
            <div>
                <p style="font-size: 0.85rem; color: #6B7280; margin: 0;">เลขที่ SO / PO</p>
                <p style="font-weight: bold; margin: 0.25rem 0;" id="expDispRef">-</p>
            </div>
            <div>
                <p style="font-size: 0.85rem; color: #6B7280; margin: 0;">จำนวนรวมราคา</p>
                <p style="font-weight: bold; margin: 0.25rem 0; color: #DC2626; font-size: 1.25rem;" id="expDispTotal">0 บาท</p>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-weight: bold; margin-bottom: 0.5rem; color: #374151;">เลือกโครงการ</label>
            <select id="expProjectSelect" class="form-control" style="width: 100%;">
                <option value="">-- เลือกโครงการ --</option>
            </select>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-weight: bold; margin-bottom: 0.5rem; color: #374151;">เลือกหมวดหมู่รายจ่าย</label>
            <select id="expCategorySelect" class="form-control" style="width: 100%;">
                <option value="">-- เลือกหมวดหมู่ --</option>
            </select>
        </div>

        <div style="margin-bottom: 2rem;">
            <label style="display: block; font-weight: bold; margin-bottom: 0.5rem; color: #374151;">หมายเหตุ</label>
            <textarea id="expNote" class="form-control" style="width: 100%;" rows="2"></textarea>
        </div>

        <div style="border-top: 1px solid #E5E7EB; padding-top: 1.5rem;">
             <label style="display: block; font-weight: bold; margin-bottom: 0.5rem; color: #374151;">รายการสินค้า</label>
             <div id="expItemsList" style="max-height: 150px; overflow-y: auto; font-size: 0.9rem;">
                 <!-- JS -->
             </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1rem; margin-top: 2rem;">
            <button onclick="closeExpenseModal()" style="background: #E5E7EB; color: #374151; border: none; padding: 1rem; border-radius: 0.75rem; font-weight: bold; cursor: pointer;">
                ปิดหน้าต่าง
            </button>
            <button onclick="saveProjectExpense()" style="background: #22C55E; color: white; border: none; padding: 1rem; border-radius: 0.75rem; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-save"></i> กดบันทึกค่าใช้จ่าย
            </button>
        </div>
    </div>
</div>

<!-- View Requisition Modal -->
<div id="viewReqModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
    <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 2rem; border-radius: 1rem; width: 80%; max-width: 800px; position: relative; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <span class="close" onclick="closeModal()" style="position: absolute; right: 1.5rem; top: 1rem; font-size: 2rem; cursor: pointer; color: #9CA3AF;">&times;</span>
        <div id="viewReqContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<?php
// Pre-load products and warehouses for JS
$prod_sql = "SELECT id, name, sku FROM stock_products WHERE company_id = ? ORDER BY name ASC";
$prod_stmt = mysqli_prepare($conn, $prod_sql);
mysqli_stmt_bind_param($prod_stmt, "i", $company_id);
mysqli_stmt_execute($prod_stmt);
$prod_res = mysqli_stmt_get_result($prod_stmt);
$products = [];
while ($p = mysqli_fetch_assoc($prod_res)) $products[] = $p;

$w_sql = "SELECT id, name FROM stock_warehouses WHERE company_id = ? ORDER BY name ASC";
$w_stmt = mysqli_prepare($conn, $w_sql);
mysqli_stmt_bind_param($w_stmt, "i", $company_id);
mysqli_stmt_execute($w_stmt);
$w_res = mysqli_stmt_get_result($w_stmt);
$warehouses = [];
while ($w = mysqli_fetch_assoc($w_res)) $warehouses[] = $w;
?>

<script>
let rowCount = 0;
const products = <?= json_encode($products) ?>;
const warehouses = <?= json_encode($warehouses) ?>;

function addRow() {
    addRowWithData(null, null, null);
}

function addRowWithData(productId, warehouseId, qty) {
    let prodOptions = '<option value="">-- เลือกสินค้า --</option>';
    products.forEach(p => {
        prodOptions += `<option value="${p.id}">${p.name} (${p.sku})</option>`;
    });

    let whOptions = '<option value="">-- เลือกคลัง --</option>';
    warehouses.forEach(w => {
        whOptions += `<option value="${w.id}">${w.name}</option>`;
    });

    const currentIndex = rowCount;
    const html = `
    <div class="req-item-row" id="row_${currentIndex}" style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; align-items: flex-end; background: #F9FAFB; padding: 1rem; border-radius: 0.5rem; border: 1px solid #EEE;">
        <div class="form-group" style="flex: 1 1 200px; margin-bottom: 0;">
            <label>สินค้า</label>
            <select name="items[${currentIndex}][product_id]" class="form-control prod-select" required onchange="checkStock(${currentIndex})">
                ${prodOptions}
            </select>
        </div>
        <div class="form-group" style="flex: 1 1 150px; margin-bottom: 0;">
            <label>คลังสินค้า</label>
            <select name="items[${currentIndex}][warehouse_id]" class="form-control wh-select" required onchange="checkStock(${currentIndex})">
                ${whOptions}
            </select>
        </div>
        <div class="form-group" style="flex: 1 1 100px; margin-bottom: 0;">
            <label>คงเหลือ</label>
            <input type="text" class="form-control stock-display" readonly value="0" style="background: #E5E7EB; font-weight: bold; text-align: center;">
        </div>
        <div class="form-group" style="flex: 1 1 100px; margin-bottom: 0;">
            <label>จำนวนเบิก</label>
            <input type="number" name="items[${currentIndex}][qty]" class="form-control qty-input" min="1" required onkeyup="validateQty(${currentIndex})" onchange="validateQty(${currentIndex})">
        </div>
        <div style="flex: 0 0 auto; margin-bottom: 0;">
            <button type="button" class="btn-primary" style="background: #EF4444; padding: 0.8rem; height: 42px;" onclick="removeRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>`;
    $('#reqItemsContainer').append(html);
    
    if (productId) $(`#row_${currentIndex} .prod-select`).val(productId);
    if (warehouseId) $(`#row_${currentIndex} .wh-select`).val(warehouseId);
    if (qty) $(`#row_${currentIndex} .qty-input`).val(qty);
    
    if (productId && warehouseId) checkStock(currentIndex);
    
    rowCount++;
}

function checkStock(index) {
    const productId = $(`#row_${index} .prod-select`).val();
    const warehouseId = $(`#row_${index} .wh-select`).val();
    const stockDisplay = $(`#row_${index} .stock-display`);
    
    if (productId && warehouseId) {
        $.ajax({
            url: 'stock_action.php?action=get_stock_balance',
            type: 'GET',
            data: { product_id: productId, warehouse_id: warehouseId },
            dataType: 'json',
            success: function(res) {
                stockDisplay.val(res.balance);
                validateQty(index);
            }
        });
    } else {
        stockDisplay.val(0);
    }
}

function validateQty(index) {
    const stock = parseInt($(`#row_${index} .stock-display`).val()) || 0;
    const qty = parseInt($(`#row_${index} .qty-input`).val()) || 0;
    const input = $(`#row_${index} .qty-input`);
    
    if (qty > stock) {
        input.css('border-color', '#EF4444');
        input.css('background-color', '#FEF2F2');
    } else {
        input.css('border-color', '');
        input.css('background-color', '');
    }
}

function removeRow(btn) {
    if ($('.req-item-row').length > 1) {
        $(btn).closest('.req-item-row').remove();
    }
}

$(document).ready(function() {
    addRow(); // Add first row
    loadRequisitions();

    $('#requisitionForm').on('submit', function(e) {
        e.preventDefault();
        
        // Final validation
        let hasError = false;
        $('.req-item-row').each(function() {
            const stock = parseInt($(this).find('.stock-display').val()) || 0;
            const qty = parseInt($(this).find('.qty-input').val()) || 0;
            if (qty > stock) {
                hasError = true;
            }
        });

        if (hasError) {
            Swal.fire('ผิดพลาด', 'จำนวนเบิกต้องไม่มากกว่าจำนวนคงเหลือในคลัง', 'error');
            return;
        }

        const formData = $(this).serialize();
        const reqId = $('#requisition_id').val();
        const action = reqId ? 'update_requisition' : 'add_requisition';
        
        $.ajax({
            url: 'stock_action.php?action=' + action,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire('สำเร็จ', res.message, 'success');
                    resetReqForm();
                    loadRequisitions();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }
        });
    });
});

function resetReqForm() {
    $('#requisitionForm')[0].reset();
    $('#requisition_id').val('');
    
    // Generate new req_no
    const now = new Date();
    const ty = (now.getFullYear() + 543).toString().slice(-2);
    const dateStr = ty + 
                    (now.getMonth() + 1).toString().padStart(2, '0') + 
                    now.getDate().toString().padStart(2, '0') + 
                    now.getHours().toString().padStart(2, '0') + 
                    now.getMinutes().toString().padStart(2, '0') + 
                    now.getSeconds().toString().padStart(2, '0');
    $('input[name="req_no"]').val('WH' + dateStr);
    
    $('#reqItemsContainer').html('');
    rowCount = 0;
    addRow();
    
    $('#btnSubmitReq').html('<i class="fas fa-save"></i> บันทึกใบเบิกสินค้า');
    $('#btnCancelReqEdit').hide();
    $('.content-card:first h2').html('<i class="fas fa-file-invoice" style="color: var(--accent-purple);"></i> สร้างใบเบิกสินค้า');
}

function editRequisition(id) {
    $.ajax({
        url: 'stock_action.php',
        type: 'GET',
        data: { action: 'get_requisition_json', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const req = response.data;
                // Allow editing regardless of status (like production)
                // if (req.status !== 'pending') { ... } 
                $('#requisition_id').val(req.id);
                $('input[name="req_no"]').val(req.req_no);
                $('input[name="requisition_date"]').val(req.requisition_date);
                $('input[name="po_no"]').val(req.po_no);
                $('input[name="so_no"]').val(req.so_no);
                $('input[name="customer_name"]').val(req.customer_name);
                $('input[name="requester_name"]').val(req.requester_name);
                $('input[name="phone"]').val(req.phone);
                $('textarea[name="shipping_address"]').val(req.shipping_address);
                $('input[name="shipping_method"]').val(req.shipping_method);
                
                $('#reqItemsContainer').html('');
                rowCount = 0;
                
                req.items.forEach(item => {
                    addRowWithData(item.product_id, item.warehouse_id, item.qty);
                });
                
                $('#btnSubmitReq').html('<i class="fas fa-save"></i> อัปเดตใบเบิกสินค้า');
                $('#btnCancelReqEdit').show();
                $('.content-card:first h2').html('<i class="fas fa-edit" style="color: var(--accent-purple);"></i> แก้ไขใบเบิกสินค้า');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                Swal.fire('ข้อผิดพลาด', response.message || 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
        }
    });
}

function loadRequisitions() {
    const search = $('#reqSearch').val();
    $.ajax({
        url: 'stock_action.php?action=get_requisitions',
        type: 'GET',
        data: { search: search },
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

function viewRequisition(id) {
    $.ajax({
        url: 'stock_action.php?action=get_requisition_details',
        type: 'GET',
        data: { id: id },
        success: function(html) {
            $('#viewReqContent').html(html);
            $('#viewReqModal').fadeIn(200);
        }
    });
}

function closeModal() {
    $('#viewReqModal').fadeOut(200);
}

function deleteRequisition(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบใบเบิกนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#9CA3AF',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'stock_action.php?action=delete_requisition',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('ลบแล้ว!', res.message, 'success');
                        loadRequisitions();
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

let activeReqData = null;

function openProjectExpenseModal(id) {
    $.ajax({
        url: 'stock_action.php',
        type: 'GET',
        data: { action: 'get_requisition_json', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                activeReqData = response.data;
                $('#expDispReqNo').text(activeReqData.req_no);
                $('#expDispDate').text(new Date(activeReqData.requisition_date).toLocaleDateString('th-TH'));
                $('#expDispRef').text((activeReqData.so_no || '-') + ' / ' + (activeReqData.po_no || '-'));
                $('#expDispTotal').text(parseFloat(activeReqData.grand_total).toLocaleString() + ' บาท');
                $('#expNote').val(`บันทึกรายจ่ายจากใบเบิกเลขที่ ${activeReqData.req_no}`);
                
                // Render items
                let itemsHtml = '';
                activeReqData.items.forEach(it => {
                    itemsHtml += `<div style="display: flex; justify-content: space-between; border-bottom: 1px solid #EEE; padding: 0.5rem 0;">
                        <span>${it.product_name}</span>
                        <span style="font-weight: bold;">${it.qty} ${it.unit}</span>
                    </div>`;
                });
                $('#expItemsList').html(itemsHtml);
                
                loadExpenseFormOptions();
                $('#projectExpenseModal').fadeIn(200);
            }
        }
    });
}

function loadExpenseFormOptions() {
    $.ajax({
        url: '../projects/transaction_action.php',
        type: 'GET',
        data: { action: 'get_form_data', module_type: 1 },
        dataType: 'json',
        success: function(data) {
            let pOptions = '<option value="">-- เลือกโครงการ --</option>';
            data.projects.forEach(p => {
                pOptions += `<option value="${p.id}">${p.project_name}</option>`;
            });
            $('#expProjectSelect').html(pOptions);

            let cOptions = '<option value="">-- เลือกหมวดหมู่ --</option>';
            data.categories.filter(c => c.direction === 'expense').forEach(c => {
                cOptions += `<option value="${c.id}">${c.name}</option>`;
            });
            $('#expCategorySelect').html(cOptions);
        }
    });
}

function closeExpenseModal() {
    $('#projectExpenseModal').fadeOut(200);
}

function saveProjectExpense() {
    const projectId = $('#expProjectSelect').val();
    const categoryId = $('#expCategorySelect').val();
    const note = $('#expNote').val();
    
    if (!projectId || !categoryId) {
        Swal.fire('ผิดพลาด', 'กรุณาเลือกโครงการและหมวดหมู่', 'error');
        return;
    }
    
    $.ajax({
        url: '../projects/transaction_action.php',
        type: 'POST',
        data: {
            action: 'save',
            project_id: projectId,
            category_id: categoryId,
            transaction_date: activeReqData.requisition_date,
            amount: activeReqData.grand_total,
            note: note,
            module_type: 1
        },
        dataType: 'json',
        success: function(data) {
            if (data.status === 'success') {
                Swal.fire('สำเร็จ', data.message, 'success');
                closeExpenseModal();
            } else {
                Swal.fire('ผิดพลาด', data.message, 'error');
            }
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
                        ${warehouses.map(w => `<option value="${w.id}">${w.name}</option>`).join('')}
                    </select>
                </div>
                <div id="swal_product_list" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; rounded: 0.5rem; padding: 0.5rem; background: #f9fafb; min-height: 150px;">
                    <p style="text-align: center; color: #9ca3af; padding: 3rem;">กรุณาเลือกคลังสินค้าเพื่อดูรายการ</p>
                </div>
            </div>
        `,
        width: '700px',
        showConfirmButton: false,
        showCloseButton: true
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
                        <div onclick='addSelectedReqProduct(${productJson}, ${whId})' style="display: flex; justify-content: space-between; align-items: center; padding: 0.8rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#A855F7'; this.style.backgroundColor='#FAF5FF';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='white';">
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
                                <div style="font-size: 0.7rem; color: #9ca3af; text-transform: uppercase; font-weight: 600;">คลิกเพื่อเบิก</div>
                            </div>
                        </div>
                    `;
                });
            }
            $('#swal_product_list').html(html);
        }
    });
}

function addSelectedReqProduct(p, whId) {
    addRowWithData(p.id, whId, 1);
    Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        icon: 'success',
        title: 'เพิ่มสินค้า ' + p.name + ' เรียบร้อย'
    });
}

// Close modal when clicking outside
$(window).on('click', function(event) {
    if ($(event.target).is('#viewReqModal')) {
        closeModal();
    }
});
</script>

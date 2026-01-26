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
                </tr>
            </thead>
            <tbody id="requisitionHistory">
                <!-- Requisitions will be loaded here via AJAX -->
            </tbody>
        </table>
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
    let prodOptions = '<option value="">-- เลือกสินค้า --</option>';
    products.forEach(p => {
        prodOptions += `<option value="${p.id}">${p.name} (${p.sku})</option>`;
    });

    let whOptions = '<option value="">-- เลือกคลัง --</option>';
    warehouses.forEach(w => {
        whOptions += `<option value="${w.id}">${w.name}</option>`;
    });

    const html = `
    <div class="req-item-row" id="row_${rowCount}" style="display: grid; grid-template-columns: 2fr 1.5fr 1fr 1fr auto; gap: 1rem; margin-bottom: 1rem; align-items: flex-end; background: #F9FAFB; padding: 1rem; border-radius: 0.5rem; border: 1px solid #EEE;">
        <div class="form-group">
            <label>สินค้า</label>
            <select name="items[${rowCount}][product_id]" class="form-control prod-select" required onchange="checkStock(${rowCount})">
                ${prodOptions}
            </select>
        </div>
        <div class="form-group">
            <label>คลังสินค้า</label>
            <select name="items[${rowCount}][warehouse_id]" class="form-control wh-select" required onchange="checkStock(${rowCount})">
                ${whOptions}
            </select>
        </div>
        <div class="form-group">
            <label>คงเหลือ</label>
            <input type="text" class="form-control stock-display" readonly value="0" style="background: #E5E7EB; font-weight: bold; text-align: center;">
        </div>
        <div class="form-group">
            <label>จำนวนเบิก</label>
            <input type="number" name="items[${rowCount}][qty]" class="form-control qty-input" min="1" required onkeyup="validateQty(${rowCount})" onchange="validateQty(${rowCount})">
        </div>
        <button type="button" class="btn-primary" style="background: #EF4444; padding: 0.8rem; height: 42px;" onclick="removeRow(this)">
            <i class="fas fa-trash"></i>
        </button>
    </div>`;
    $('#reqItemsContainer').append(html);
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

// Close modal when clicking outside
$(window).on('click', function(event) {
    if ($(event.target).is('#viewReqModal')) {
        closeModal();
    }
});
</script>

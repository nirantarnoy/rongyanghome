<?php
// Warehouse Management Tab
?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <div class="content-card">
        <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-plus-circle" style="color: var(--accent-purple);"></i> เพิ่ม/แก้ไขคลังสินค้า
        </h2>
        
        <form id="warehouseForm" class="grid-form">
            <input type="hidden" name="id" id="warehouse_id">
            <div class="form-group" style="grid-column: span 2;">
                <label>ชื่อคลังสินค้า</label>
                <input type="text" name="name" class="form-control" placeholder="ระบุชื่อคลังสินค้า" required>
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>สถานที่ / รายละเอียด</label>
                <textarea name="location" class="form-control" rows="3" placeholder="ระบุสถานที่หรือรายละเอียดเพิ่มเติม..."></textarea>
            </div>
            <div style="grid-column: span 2; text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" id="btnCancelWarehouseEdit" class="btn-primary" style="background: #6B7280; display: none;">
                    ยกเลิก
                </button>
                <button type="submit" id="btnSubmitWarehouse" class="btn-primary">
                    <i class="fas fa-save"></i> บันทึกข้อมูลคลัง
                </button>
            </div>
        </form>
    </div>

    <div class="content-card">
        <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-warehouse" style="color: var(--accent-purple);"></i> รายการคลังสินค้า
        </h2>
        <div id="warehouseList">
            <!-- Warehouses will be loaded here -->
            <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>กำลังโหลดข้อมูล...</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadWarehouses();

    $('#warehouseForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const warehouseId = $('#warehouse_id').val();
        const action = warehouseId ? 'update_warehouse' : 'add_warehouse';
        
        $.ajax({
            url: 'stock_action.php?action=' + action,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire('สำเร็จ', res.message, 'success');
                    resetWarehouseForm();
                    loadWarehouses();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }
        });
    });

    $('#btnCancelWarehouseEdit').on('click', function() {
        resetWarehouseForm();
    });
});

function loadWarehouses() {
    $.ajax({
        url: 'stock_action.php?action=get_warehouses',
        type: 'GET',
        success: function(html) {
            $('#warehouseList').html(html);
        }
    });
}

function resetWarehouseForm() {
    $('#warehouseForm')[0].reset();
    $('#warehouse_id').val('');
    $('#btnSubmitWarehouse').html('<i class="fas fa-save"></i> บันทึกข้อมูลคลัง');
    $('#btnCancelWarehouseEdit').hide();
    $('.content-card h2:first').html('<i class="fas fa-plus-circle" style="color: var(--accent-purple);"></i> เพิ่ม/แก้ไขคลังสินค้า');
}

function editWarehouse(id, name, location) {
    $('#warehouse_id').val(id);
    $('input[name="name"]').val(name);
    $('textarea[name="location"]').val(location);
    
    $('#btnSubmitWarehouse').html('<i class="fas fa-save"></i> อัปเดตข้อมูลคลัง');
    $('#btnCancelWarehouseEdit').show();
    $('.content-card h2:first').html('<i class="fas fa-edit" style="color: var(--accent-purple);"></i> แก้ไขข้อมูลคลัง');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function deleteWarehouse(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบคลังสินค้านี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2430',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'stock_action.php?action=delete_warehouse',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('ลบแล้ว!', res.message, 'success');
                        loadWarehouses();
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

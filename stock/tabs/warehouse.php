<?php
// Warehouse Tab - Product Management
?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <div class="content-card">
        <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-plus-circle" style="color: var(--accent-purple);"></i> เพิ่มสินค้า / วัตถุดิบใหม่
        </h2>
        
        <form id="productForm" class="grid-form">
            <input type="hidden" name="id" id="product_id">
            <div class="form-group">
                <label>ชื่อสินค้า / วัตถุดิบ</label>
                <input type="text" name="name" class="form-control" placeholder="ระบุชื่อสินค้า" required>
            </div>
            <div class="form-group">
                <label>SKU / รหัสสินค้า</label>
                <input type="text" name="sku" class="form-control" placeholder="เช่น PROD-001">
            </div>
            <div class="form-group">
                <label>หมวดหมู่</label>
                <select name="category_id" id="category_select" class="form-control">
                    <option value="">-- เลือกหมวดหมู่ --</option>
                    <!-- Categories will be loaded here -->
                </select>
            </div>
            <div class="form-group">
                <label>หน่วยนับ</label>
                <input type="text" name="unit" class="form-control" placeholder="เช่น ชิ้น, กก., กล่อง">
            </div>
            <div class="form-group">
                <label>ราคาต่อหน่วย</label>
                <input type="number" step="0.01" name="price" id="prod_price" class="form-control" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>สต็อกขั้นต่ำ (แจ้งเตือน)</label>
                <input type="number" name="min_stock" class="form-control" value="0">
            </div>

            <!-- New VAT Fields -->
            <div class="form-group" style="grid-column: 1 / -1; background: #f8fafc; padding: 1.5rem; border-radius: 0.8rem; border: 1px solid #e2e8f0; margin-top: 0.5rem;">
                <div style="display: flex; flex-direction: column; gap: 1.2rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="flex: 1;">
                            <label style="color: #ef4444; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                ราคาก่อน vat 7%
                                <input type="checkbox" name="has_vat" value="1" id="has_vat" style="width: 1.2rem; height: 1.2rem; cursor: pointer;">
                            </label>
                            <input type="number" step="0.01" name="price_before_vat" id="price_before_vat" class="form-control" style="margin-top: 0.5rem;" placeholder="0.00">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 2rem; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="price_display_mode" value="before_vat" style="width: 1.1rem; height: 1.1rem;"> 
                            แสดงราคาก่อน Vat 7%
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="price_display_mode" value="unit" checked style="width: 1.1rem; height: 1.1rem;"> 
                            แสดงราคาต่อหน่วย
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label>รูปภาพสินค้า (Upload)</label>
                <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*">
                <div id="imagePreview" style="margin-top: 0.5rem; display: none;">
                    <img src="" style="max-width: 200px; border-radius: 0.5rem; border: 1px solid var(--border-color);">
                </div>
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>รายละเอียดเพิ่มเติม</label>
                <textarea name="description" class="form-control" rows="3" placeholder="ระบุรายละเอียดสินค้า..."></textarea>
            </div>
            <div style="grid-column: span 2; text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" id="btnCancelEdit" class="btn-primary" style="background: #6B7280; display: none;">
                    ยกเลิก
                </button>
                <button type="submit" id="btnSubmitProduct" class="btn-primary">
                    <i class="fas fa-save"></i> บันทึกข้อมูลสินค้า
                </button>
            </div>
        </form>
    </div>

    <div class="content-card">
        <h2 style="margin-top: 0; margin-bottom: 1.5rem; font-size: 1.3rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-tags" style="color: var(--accent-purple);"></i> จัดการหมวดหมู่
        </h2>
        <form id="categoryForm" style="margin-bottom: 1.5rem;">
            <div class="form-group">
                <label>ชื่อหมวดหมู่ใหม่</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" name="cat_name" class="form-control" placeholder="ชื่อหมวดหมู่" required>
                    <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem;">เพิ่ม</button>
                </div>
            </div>
        </form>
        <div id="categoryList" style="max-height: 300px; overflow-y: auto;">
            <!-- Category list will be loaded here -->
        </div>
    </div>
</div>

<div class="content-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin: 0; font-size: 1.3rem;">รายการสินค้าในคลัง</h2>
        <div style="position: relative;">
            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" id="searchProduct" class="form-control" style="padding-left: 2.5rem; width: 250px;" placeholder="ค้นหาสินค้า...">
        </div>
    </div>

    <div id="productList" class="product-list">
        <!-- Products will be loaded here via AJAX -->
        <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>กำลังโหลดข้อมูล...</p>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadProducts();
    loadCategories();

    $('#productForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const productId = $('#product_id').val();
        const action = productId ? 'update_product' : 'add_product';
        
        $.ajax({
            url: 'stock_action.php?action=' + action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire('สำเร็จ', res.message, 'success');
                    resetProductForm();
                    loadProducts();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }
        });
    });

    $('#btnCancelEdit').on('click', function() {
        resetProductForm();
    });

    $('#product_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview img').attr('src', e.target.result);
                $('#imagePreview').show();
            }
            reader.readAsDataURL(file);
        }
    });

    // VAT Calculations
    $('#prod_price').on('input', function() {
        if ($('#has_vat').is(':checked')) {
            const price = parseFloat($(this).val()) || 0;
            const beforeVat = (price / 1.07).toFixed(2);
            $('#price_before_vat').val(beforeVat);
        }
    });

    $('#price_before_vat').on('input', function() {
        const beforeVat = parseFloat($(this).val()) || 0;
        const price = (beforeVat * 1.07).toFixed(2);
        $('#prod_price').val(price);
    });

    $('#has_vat').on('change', function() {
        if ($(this).is(':checked')) {
            const price = parseFloat($('#prod_price').val()) || 0;
            if (price > 0) {
                const beforeVat = (price / 1.07).toFixed(2);
                $('#price_before_vat').val(beforeVat);
            }
            $('input[name="price_display_mode"][value="before_vat"]').prop('checked', true);
        } else {
            $('input[name="price_display_mode"][value="unit"]').prop('checked', true);
        }
    });

    function resetProductForm() {
        $('#productForm')[0].reset();
        $('#product_id').val('');
        $('#prod_price').val('');
        $('#price_before_vat').val('');
        $('#has_vat').prop('checked', false);
        $('input[name="price_display_mode"][value="unit"]').prop('checked', true);
        $('#btnSubmitProduct').html('<i class="fas fa-save"></i> บันทึกข้อมูลสินค้า');
        $('#btnCancelEdit').hide();
        $('#imagePreview').hide();
        $('#imagePreview img').attr('src', '');
        $('.content-card h2:first').html('<i class="fas fa-plus-circle" style="color: var(--accent-purple);"></i> เพิ่มสินค้า / วัตถุดิบใหม่');
    }

    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        const name = $(this).find('input[name="cat_name"]').val();
        
        $.ajax({
            url: 'stock_action.php?action=add_category',
            type: 'POST',
            data: { name: name },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#categoryForm')[0].reset();
                    loadCategories();
                } else {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                }
            }
        });
    });

    $('#searchProduct').on('keyup', function() {
        const query = $(this).val().toLowerCase();
        $('.product-card').each(function() {
            const name = $(this).find('.product-name').text().toLowerCase();
            const sku = $(this).find('.product-sku').text().toLowerCase();
            if (name.includes(query) || sku.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});

function loadProducts() {
    $.ajax({
        url: 'stock_action.php?action=get_products',
        type: 'GET',
        success: function(html) {
            $('#productList').html(html);
        }
    });
}

function loadCategories() {
    $.ajax({
        url: 'stock_action.php?action=get_categories',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            let selectHtml = '<option value="">-- เลือกหมวดหมู่ --</option>';
            let listHtml = '';
            res.forEach(cat => {
                selectHtml += `<option value="${cat.id}">${cat.name}</option>`;
                listHtml += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; border-bottom: 1px solid #EEE;">
                    <span>${cat.name}</span>
                    <button onclick="deleteCategory(${cat.id})" style="background: none; border: none; color: #EF4444; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>`;
            });
            $('#category_select').html(selectHtml);
            $('#categoryList').html(listHtml || '<p style="text-align: center; color: #999;">ยังไม่มีหมวดหมู่</p>');
        }
    });
}

function deleteCategory(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "สินค้าในหมวดหมู่นี้จะกลายเป็น 'ทั่วไป'",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2430',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'stock_action.php?action=delete_category',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        loadCategories();
                    }
                }
            });
        }
    });
}

function editProduct(id) {
    $.ajax({
        url: 'stock_action.php?action=get_product',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(product) {
            if (product) {
                $('#product_id').val(product.id);
                $('input[name="name"]').val(product.name);
                $('input[name="sku"]').val(product.sku);
                $('select[name="category_id"]').val(product.category_id);
                $('input[name="unit"]').val(product.unit);
                $('#prod_price').val(product.price);
                $('#price_before_vat').val(product.price_before_vat);
                $('#has_vat').prop('checked', product.has_vat == 1);
                $(`input[name="price_display_mode"][value="${product.price_display_mode || 'unit'}"]`).prop('checked', true);
                $('input[name="min_stock"]').val(product.min_stock);
                $('textarea[name="description"]').val(product.description);
                
                if (product.image_url) {
                    $('#imagePreview img').attr('src', product.image_url);
                    $('#imagePreview').show();
                } else {
                    $('#imagePreview').hide();
                }
                
                $('#btnSubmitProduct').html('<i class="fas fa-save"></i> อัปเดตข้อมูลสินค้า');
                $('#btnCancelEdit').show();
                $('.content-card h2:first').html('<i class="fas fa-edit" style="color: var(--accent-purple);"></i> แก้ไขข้อมูลสินค้า');
                
                // Scroll to form
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    });
}

function deleteProduct(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบสินค้านี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2430',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'stock_action.php?action=delete_product',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบแล้ว!',
                            text: res.message,
                            confirmButtonColor: '#10b981'
                        });
                        loadProducts();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่สามารถลบได้',
                            html: '<div style="text-align: left;">' + res.message + '</div>',
                            confirmButtonColor: '#dc2430',
                            footer: '<span style="color: #6B7280; font-size: 0.875rem;">💡 กรุณาลบหรือแก้ไขรายการที่เกี่ยวข้องก่อน</span>'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                        confirmButtonColor: '#dc2430'
                    });
                }
            });
        }
    });
}
</script>

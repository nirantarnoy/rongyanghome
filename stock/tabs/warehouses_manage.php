<?php
// Warehouse Management Tab
?>

<div id="mainWarehouseView" style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
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

<div id="warehouseDetailsView" style="display: none; background: white; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 1rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <h2 id="detailsWarehouseName" style="margin: 0; font-size: 1.5rem; color: var(--text-dark); display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-warehouse" style="color: var(--accent-purple);"></i> <span id="spanWarehouseName">คลังสินค้า</span>
        </h2>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <input type="text" id="searchWarehouseProducts" placeholder="ค้นหาสินค้า..." class="form-control" style="width: 250px;">
            <button onclick="exportWarehouseExcel()" class="btn-primary" style="background: #10B981; border: none; padding: 0.5rem 1rem; color: white; border-radius: 0.4rem; cursor: pointer;">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button onclick="exportWarehousePDF()" class="btn-primary" style="background: #EF4444; border: none; padding: 0.5rem 1rem; color: white; border-radius: 0.4rem; cursor: pointer;">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
            <button onclick="closeWarehouseDetails()" class="btn-primary" style="background: #6B7280; border: none; padding: 0.5rem 1rem; color: white; border-radius: 0.4rem; cursor: pointer;">
                <i class="fas fa-arrow-left"></i> กลับ
            </button>
        </div>
    </div>
    
    <div id="warehouseProductsList" style="overflow-x: auto;">
        <!-- Table will be loaded here -->
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

function viewWarehouseDetails(id, name) {
    $('#mainWarehouseView').hide();
    $('#warehouseDetailsView').show();
    $('#spanWarehouseName').text(name);
    $('#searchWarehouseProducts').val('');
    
    // Load products
    $('#warehouseProductsList').html('<div style="text-align: center; padding: 3rem;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>กำลังโหลดข้อมูล...</p></div>');
    loadWarehouseProductsTable(id, '');
    
    // Save current ID for search/export
    $('#warehouseDetailsView').data('warehouse-id', id);
    $('#warehouseDetailsView').data('warehouse-name', name);
}

var currentWhSortBy = 'sku';
var currentWhSortOrder = 'ASC';

function closeWarehouseDetails() {
    $('#warehouseDetailsView').hide();
    $('#mainWarehouseView').show();
}

function loadWarehouseProductsTable(id, search) {
    $.ajax({
        url: 'stock_action.php?action=get_warehouse_details_html',
        type: 'GET',
        data: { 
            id: id, 
            search: search,
            sort_by: currentWhSortBy,
            sort_order: currentWhSortOrder
        },
        success: function(html) {
            $('#warehouseProductsList').html(html);
        }
    });
}

function setWhProductSort(field) {
    if (currentWhSortBy === field) {
        currentWhSortOrder = (currentWhSortOrder === 'ASC') ? 'DESC' : 'ASC';
    } else {
        currentWhSortBy = field;
        currentWhSortOrder = 'ASC';
    }
    
    const id = $('#warehouseDetailsView').data('warehouse-id');
    const search = $('#searchWarehouseProducts').val();
    loadWarehouseProductsTable(id, search);
}

// Search feature inside warehouse details
$(document).ready(function() {
    let searchTimeout;
    $('#searchWarehouseProducts').on('input', function() {
        clearTimeout(searchTimeout);
        const search = $(this).val();
        const id = $('#warehouseDetailsView').data('warehouse-id');
        searchTimeout = setTimeout(function() {
            loadWarehouseProductsTable(id, search);
        }, 500);
    });
    
    // Select all logic
    $(document).on('change', '#selectAllWarehouseProducts', function() {
        $('.export-checkbox').prop('checked', $(this).prop('checked'));
    });
});

function exportWarehouseExcel() {
    const table = document.getElementById("whProductsTable");
    if (!table) return;
    
    let csv = [];
    let rows = table.querySelectorAll("tr");
    
    for (let i = 0; i < rows.length; i++) {
        if(rows[i].style.display === 'none') continue;
        
        let cols = rows[i].querySelectorAll("td, th");
        if(cols.length === 0) continue;
        
        // Skip unselected normal rows (categories don't have .export-checkbox)
        let checkbox = rows[i].querySelector(".export-checkbox");
        if (checkbox && !checkbox.checked) {
            continue;
        }
        
        // If this is a category row, we can just export its text
        let isCategory = rows[i].classList.contains('category-row');
        
        let rowData = [];
        let startIndex = 0;
        
        // Skip checkbox column for data rows (th also has no class but we know it's index 0)
        if (!isCategory && cols.length > 1) {
            startIndex = 1;
        }
        
        for (let j = startIndex; j < cols.length; j++) {
            // Get text, avoiding icons
            let text = "";
            
            // Special handling for the second column which has the star icon
            if (!isCategory && j === 1) {
                // Try to get text without the icon
                let tempDiv = document.createElement("div");
                tempDiv.innerHTML = cols[j].innerHTML;
                let icons = tempDiv.querySelectorAll('i, svg');
                icons.forEach(el => el.remove());
                text = tempDiv.innerText || tempDiv.textContent;
            } else if (isCategory) {
               // category row text
               let tempDiv = document.createElement("div");
               tempDiv.innerHTML = cols[j].innerHTML;
               let icons = tempDiv.querySelectorAll('i, svg');
               icons.forEach(el => el.remove());
               text = tempDiv.innerText || tempDiv.textContent;
            } else {
                text = cols[j].innerText || cols[j].textContent;
            }
            
            text = text.trim().replace(/(\\r\\n|\\n|\\r)/gm, " ").replace(/"/g, '""');
            rowData.push('"' + text + '"');
        }
        
        // Add empty cells to category row so it aligns somewhat
        if (isCategory && rowData.length === 1 && cols.length === 1) {
            rowData.push('""','""','""','""','""'); // Padding for alignment
        }
            
        if(rowData.length > 0) {
            csv.push(rowData.join(","));
        }
    }
    
    // Add BOM for Excel Thai language support
    let csvContent = "\ufeff" + csv.join("\r\n");
    let csvFile = new Blob([csvContent], {type: "text/csv;charset=utf-8;"});
    let downloadLink = document.createElement("a");
    downloadLink.download = $('#warehouseDetailsView').data('warehouse-name') + ".csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

function exportWarehousePDF() {
    // Clone the table
    const tableBlock = document.getElementById("whProductsTable");
    if (!tableBlock) return;
    
    const cloneTable = tableBlock.cloneNode(true);
    // Remove unchecked rows
    const rows = cloneTable.querySelectorAll('tr');
    for(let i = rows.length - 1; i >= 0; i--) {
        let cb = rows[i].querySelector('.export-checkbox');
        if (cb && !cb.checked) {
            rows[i].remove();
        } else {
            // Remove the first child/column (the checkbox)
            if (rows[i].children.length > 1) {
                rows[i].removeChild(rows[i].children[0]);
            }
        }
    }
    
    const tableHtml = cloneTable.outerHTML;
    const whName = $('#warehouseDetailsView').data('warehouse-name');
    
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>' + whName + '</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: "Sarabun", sans-serif; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 14px; }');
    printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
    printWindow.document.write('th { background-color: #f2f2f2; }');
    printWindow.document.write('.category-row td { background-color: #e9ecef !important; font-weight: bold; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h2>' + whName + '</h2>');
    printWindow.document.write(tableHtml);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}
</script>

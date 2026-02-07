<div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-bold text-gray-800">รายการใบสั่งซื้อ</h2>
        <div class="flex gap-3 w-full md:w-auto">
            <div class="relative flex-1 md:w-64">
                <input type="text" id="searchInputPO" placeholder="ค้นหา..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                       onkeyup="loadPOs()">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <a href="purchase_order_form.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2 whitespace-nowrap">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                สร้างใหม่
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">เลขที่</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">วันที่</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">ผู้ขาย</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">ยอดรวม</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">จัดการ</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">บันทึกรายรับ</th>
                </tr>
            </thead>
            <tbody id="poList" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">กำลังโหลด...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal บันทึกรายรับโครงการ -->
<div id="projectLinkModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl">
        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800" id="modalTitle">บันทึกรายรับโครงการ</h3>
            <button onclick="closeProjectModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">เลขที่ใบสั่งซื้อ</p>
                    <p class="font-bold text-gray-800" id="dispDocNumber">-</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">เลขที่ SO</p>
                    <p class="font-bold text-gray-800" id="dispSONumber">-</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">วันที่</p>
                    <p class="font-bold text-gray-800" id="dispDocDate">-</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">ยอดรวม</p>
                    <p class="font-bold text-green-600 text-lg" id="dispGrandTotal">0 ฿</p>
                </div>
            </div>

            <div class="space-y-3 pt-4 border-t">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">เลือกโครงการ</label>
                    <select id="projectSelect" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">-- เลือกโครงการ --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">เลือกหมวดหมู่รายรับ</label>
                    <select id="categorySelect" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">-- เลือกหมวดหมู่ --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">หมายเหตุ</label>
                    <textarea id="transactionNote" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-green-500" rows="2"></textarea>
                </div>
            </div>

            <div class="pt-4 border-t">
                <label class="block text-sm font-bold text-gray-700 mb-2">รายการสินค้า</label>
                <div class="max-h-40 overflow-y-auto bg-gray-50 rounded-lg border p-2 text-sm" id="itemsList">
                    <!-- Items will be listed here -->
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-4 flex justify-between items-center gap-3">
            <button onclick="closeProjectModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 rounded-xl transition-all">
                ปิดหน้าต่าง
            </button>
            <button onclick="saveProjectTransaction()" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-3 rounded-xl shadow-lg transition-all" id="btnSaveTransaction">
                กดบันทึกรายรับ
            </button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadPOs();
});

function loadPOs() {
    const search = $('#searchInputPO').val();
    
    $.ajax({
        url: 'purchase_order_action.php',
        type: 'GET',
        data: { action: 'list', search: search },
        success: function(response) {
            if (response.status === 'success') {
                renderPOList(response.data);
            }
        }
    });
}

function renderPOList(data) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">ไม่พบข้อมูล</td></tr>';
    } else {
        data.forEach(item => {
            const date = new Date(item.doc_date).toLocaleDateString('th-TH');
            html += `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${item.doc_number}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${date}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${item.vendor_name || '-'}</td>
                    <td class="px-6 py-4 text-sm text-right font-bold text-green-600">${parseFloat(item.grand_total).toLocaleString()} ฿</td>
                    <td class="px-6 py-4 text-center space-x-2">
                        <a href="purchase_order_form.php?id=${item.id}" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition-all text-sm font-medium">
                            ✏️ แก้ไข
                        </a>
                        <button onclick="deletePO(${item.id})" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-all text-sm font-medium">
                            🗑️ ลบ
                        </button>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button onclick="openProjectLinkModal(${item.id})" class="inline-flex items-center px-3 py-1.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 shadow-sm transition-all text-sm font-bold">
                            กดเพื่อบันทึก
                        </button>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#poList').html(html);
}

let activePOData = null;

function openProjectLinkModal(id) {
    $.ajax({
        url: 'purchase_order_action.php',
        type: 'GET',
        data: { action: 'get', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                activePOData = response.data;
                $('#dispDocNumber').text(activePOData.doc_number);
                $('#dispDocDate').text(new Date(activePOData.doc_date).toLocaleDateString('th-TH'));
                $('#dispSONumber').text(activePOData.so_number || '-');
                $('#dispGrandTotal').text(parseFloat(activePOData.grand_total).toLocaleString() + ' ฿');
                $('#transactionNote').val(`บันทึกรายรับจากใบสั่งซื้อเลขที่ ${activePOData.doc_number}`);
                
                // Render items
                let itemsHtml = '';
                const items = JSON.parse(activePOData.items || '[]');
                items.forEach(it => {
                    itemsHtml += `<div class="flex justify-between border-b py-1"><span>${it.name}</span> <span class="font-bold">${it.qty} ${it.unit}</span></div>`;
                });
                $('#itemsList').html(itemsHtml);
                
                loadFormOptions();
                $('#projectLinkModal').removeClass('hidden');
            }
        }
    });
}

function loadFormOptions() {
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
            $('#projectSelect').html(pOptions);

            let cOptions = '<option value="">-- เลือกหมวดหมู่ --</option>';
            data.categories.filter(c => c.direction === 'income').forEach(c => {
                cOptions += `<option value="${c.id}">${c.name}</option>`;
            });
            $('#categorySelect').html(cOptions);
        }
    });
}

function closeProjectModal() {
    $('#projectLinkModal').addClass('hidden');
}

function saveProjectTransaction() {
    const projectId = $('#projectSelect').val();
    const categoryId = $('#categorySelect').val();
    const note = $('#transactionNote').val();
    
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
            transaction_date: activePOData.doc_date,
            amount: activePOData.grand_total,
            note: note,
            module_type: 1
        },
        dataType: 'json',
        success: function(data) {
            if (data.status === 'success') {
                Swal.fire('สำเร็จ', data.message, 'success');
                closeProjectModal();
            } else {
                Swal.fire('ผิดพลาด', data.message, 'error');
            }
        }
    });
}

function deletePO(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบใบสั่งซื้อนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'purchase_order_action.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('ลบแล้ว!', response.message, 'success');
                        loadPOs();
                    } else {
                        Swal.fire('ผิดพลาด', response.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

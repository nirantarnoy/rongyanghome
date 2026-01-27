<div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-bold text-gray-800">รายการใบสั่งขาย</h2>
        <div class="flex gap-3 w-full md:w-auto">
            <div class="relative flex-1 md:w-64">
                <input type="text" id="searchInputSO" placeholder="ค้นหา..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       onkeyup="loadSOs()">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <a href="sales_order_form.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2 whitespace-nowrap">
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
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">ลูกค้า</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">ยอดรวม</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">จัดการ</th>
                </tr>
            </thead>
            <tbody id="soList" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">กำลังโหลด...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    loadSOs();
});

function loadSOs() {
    const search = $('#searchInputSO').val();
    
    $.ajax({
        url: 'sales_order_action.php',
        type: 'GET',
        data: { action: 'list', search: search },
        success: function(response) {
            if (response.status === 'success') {
                renderSOList(response.data);
            }
        }
    });
}

function renderSOList(data) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">ไม่พบข้อมูล</td></tr>';
    } else {
        data.forEach(item => {
            const date = new Date(item.doc_date).toLocaleDateString('th-TH');
            html += `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${item.doc_number}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${date}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${item.customer_name || '-'}</td>
                    <td class="px-6 py-4 text-sm text-right font-bold text-blue-600">${parseFloat(item.grand_total).toLocaleString()} ฿</td>
                    <td class="px-6 py-4 text-center space-x-2">
                        <a href="sales_order_form.php?id=${item.id}" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition-all text-sm font-medium">
                            ✏️ แก้ไข
                        </a>
                        <button onclick="deleteSO(${item.id})" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-all text-sm font-medium">
                            🗑️ ลบ
                        </button>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#soList').html(html);
}

function deleteSO(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบใบสั่งขายนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'sales_order_action.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('ลบแล้ว!', response.message, 'success');
                        loadSOs();
                    } else {
                        Swal.fire('ผิดพลาด', response.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

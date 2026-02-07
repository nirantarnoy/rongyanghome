<div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-bold text-gray-800">รายการใบเสนอราคา</h2>
        <div class="flex gap-3 w-full md:w-auto">
            <div class="relative flex-1 md:w-64">
                <input type="text" id="searchInput" placeholder="ค้นหา..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                       onkeyup="loadQuotations()">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <a href="quotation_form.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition-all flex items-center gap-2 whitespace-nowrap">
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
            <tbody id="quotationList" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">กำลังโหลด...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    loadQuotations();
});

function loadQuotations() {
    const search = $('#searchInput').val();
    
    $.ajax({
        url: 'quotation_action.php',
        type: 'GET',
        data: { action: 'list', search: search },
        success: function(response) {
            try {
                let res;
                if (typeof response === 'object') {
                    res = response;
                } else {
                    res = JSON.parse(response);
                }
                
                if (res.status === 'success') {
                    renderList(res.data);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                console.log('Raw response:', response);
            }
        }
    });
}

function renderList(data) {
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
                    <td class="px-6 py-4 text-sm text-right font-bold text-emerald-600">${parseFloat(item.grand_total).toLocaleString()} ฿</td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick="convertToSO(${item.id})" class="w-9 h-9 flex items-center justify-center bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="แปลงเป็นใบสั่งขาย">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <a href="quotation_form.php?id=${item.id}" class="w-9 h-9 flex items-center justify-center bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deleteQuotation(${item.id})" class="w-9 h-9 flex items-center justify-center bg-rose-100 text-rose-700 rounded-lg hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="ลบ">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#quotationList').html(html);
}

function convertToSO(id) {
    Swal.fire({
        title: 'ยืนยันการแปลงเอกสาร?',
        text: 'คุณต้องการแปลงใบเสนอราคานี้เป็นใบสั่งขายใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, แปลงเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังประมวลผล...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: 'quotation_action.php',
                type: 'POST',
                data: { action: 'convert_to_so', id: id },
                success: function(response) {
                    try {
                        let res = typeof response === 'object' ? response : JSON.parse(response);
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: res.message,
                                showCancelButton: true,
                                confirmButtonText: 'ไปหน้าใบสั่งขาย',
                                cancelButtonText: 'อยู่ที่เดิม'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'sales_order_form.php?id=' + res.so_id;
                                }
                            });
                        } else {
                            Swal.fire('ผิดพลาด', res.message, 'error');
                        }
                    } catch (e) {
                        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการประมวลผล', 'error');
                    }
                }
            });
        }
    });
}

function deleteQuotation(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบใบเสนอราคานี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'quotation_action.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                success: function(response) {
                    try {
                        let res;
                        if (typeof response === 'object') {
                            res = response;
                        } else {
                            res = JSON.parse(response);
                        }

                        if (res.status === 'success') {
                            Swal.fire('ลบแล้ว!', res.message, 'success');
                            loadQuotations();
                        } else {
                            Swal.fire('ผิดพลาด', res.message, 'error');
                        }
                    } catch (e) {
                        console.error('Delete Error:', e);
                        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการประมวลผล', 'error');
                    }
                }
            });
        }
    });
}
</script>

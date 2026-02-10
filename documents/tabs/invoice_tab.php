<?php
$current_type = $tab === 'tax_invoice' ? 'tax_invoice' : 'invoice';
$title = $current_type === 'tax_invoice' ? 'ใบกำกับภาษี' : 'ใบแจ้งหนี้';
?>
<div class="bg-white rounded-2xl shadow-sm p-6 mb-8 border border-gray-100">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800"><?= $title ?></h2>
            <p class="text-sm text-gray-500">จัดการรายการ<?= $title ?>ทั้งหมดของคุณ</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative flex-1 md:w-64">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="search_invoice" placeholder="ค้นหา..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all text-sm">
            </div>
            <a href="invoice_form.php?type=<?= $current_type ?>" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-xl font-bold transition-all shadow-md active:scale-95 flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>สร้างใหม่</span>
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">เลขที่</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">วันที่</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ลูกค้า</th>
                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">ยอดรวม</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody id="invoice_list" class="divide-y divide-gray-100">
                <!-- Data will be loaded via AJAX -->
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    loadInvoices();
    
    $('#search_invoice').on('input', function() {
        loadInvoices($(this).val());
    });
});

function loadInvoices(search = '') {
    $.ajax({
        url: 'invoice_action.php',
        type: 'GET',
        data: { action: 'list', type: '<?= $current_type ?>', search: search },
        success: function(res) {
            if (res.status === 'success') {
                let html = '';
                if (res.data.length === 0) {
                    html = '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">ไม่พบข้อมูล</td></tr>';
                } else {
                    res.data.forEach(item => {
                        html += `
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-bold text-gray-800">${item.doc_number}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">${new Date(item.doc_date).toLocaleDateString('th-TH')}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">${item.customer_name}</td>
                                <td class="px-6 py-4 text-sm text-right font-bold text-emerald-600">${parseFloat(item.grand_total).toLocaleString()} ฿</td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="invoice_form.php?id=${item.id}" class="w-9 h-9 flex items-center justify-center bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="แก้ไข">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="issueReceiptFromList(${item.id})" class="w-9 h-9 flex items-center justify-center bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="ออกใบเสร็จ">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                        <button onclick="deleteInvoice(${item.id})" class="w-9 h-9 flex items-center justify-center bg-rose-100 text-rose-700 rounded-lg hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#invoice_list').html(html);
            }
        }
    });
}

function deleteInvoice(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณไม่สามารถกู้คืนข้อมูลนี้ได้!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'invoice_action.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('สำเร็จ', res.message, 'success');
                        loadInvoices();
                    } else Swal.fire('ผิดพลาด', res.message, 'error');
                }
            });
        }
    });
}
function issueReceiptFromList(id) {
    Swal.fire({
        title: 'ยืนยันการออกใบเสร็จ?',
        text: 'คุณต้องการสร้างใบเสร็จรับเงินจากใบแจ้งหนี้นี้ใช่หรือไม่?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, สร้างเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังสร้างใบเสร็จ...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: 'invoice_action.php',
                type: 'POST',
                data: { action: 'convert_to_receipt', id: id },
                success: function(response) {
                    try {
                        let res = typeof response === 'object' ? response : JSON.parse(response);
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: res.message,
                                showCancelButton: true,
                                confirmButtonText: 'ไปหน้าใบเสร็จ',
                                cancelButtonText: 'อยู่ที่เดิม'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'receipt_form.php?id=' + res.receipt_id;
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
</script>

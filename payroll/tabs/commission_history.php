<div class="grid grid-cols-1 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
            <div>
                <h4 class="font-bold text-slate-800 text-base">ประวัติค่าคอมมิชชั่นรายชิ้น</h4>
                <p class="text-xs text-slate-400 mt-0.5">เรียกดู แก้ไข หรือตรวจสอบรายละเอียดการปันส่วนค่าคอมมิชชั่นเฟอร์นิเจอร์</p>
            </div>
            <button onclick="switchTab('commission_calc')" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10 gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>สร้างรายการคิดค่าคอมใหม่</span>
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">วันที่ทำรายการ</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">จำนวนสินค้า</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">ยอดราคาสินค้ารวม</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">ยอดค่าคอมมิชชั่นรวม</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">สถานะ</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="commissionHistoryTableBody" class="divide-y divide-slate-100">
                    <!-- Dynamic -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function loadCommissionHistory() {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_commission_transactions' },
            success: function(data) {
                let html = '';
                if (data && data.length > 0) {
                    data.forEach(function(row) {
                        const dateText = formatThaiDate(row.transaction_date);
                        const statusBadge = row.status === 'approved' 
                            ? `<span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded-full border border-emerald-100"><i class="fa-solid fa-circle-check mr-0.5"></i> อนุมัติแล้ว</span>`
                            : `<span class="px-2.5 py-0.5 bg-amber-50 text-amber-600 text-[10px] font-bold rounded-full border border-amber-100"><i class="fa-solid fa-clock mr-0.5"></i> แบบร่าง</span>`;

                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-bold text-slate-700">${dateText}</td>
                            <td class="px-6 py-4 text-xs text-slate-500 font-semibold">${row.total_items} รายการ</td>
                            <td class="px-6 py-4 text-xs text-right font-bold text-slate-600">${parseFloat(row.total_amount).toLocaleString('th-TH', {minimumFractionDigits: 2})} บาท</td>
                            <td class="px-6 py-4 text-xs text-right font-extrabold text-blue-600">${parseFloat(row.total_commission).toLocaleString('th-TH', {minimumFractionDigits: 2})} บาท</td>
                            <td class="px-6 py-4 text-center">${statusBadge}</td>
                            <td class="px-6 py-4 text-xs text-right space-x-2">
                                <button onclick="editCommissionTransaction(${row.id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="แก้ไข">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button onclick="deleteCommissionTransaction(${row.id})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="ลบ">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = `
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 opacity-30"></i>
                                <div>ยังไม่มีประวัติการบันทึกค่าคอมมิชชั่น</div>
                            </div>
                        </td>
                    </tr>`;
                }
                $('#commissionHistoryTableBody').html(html);
            }
        });
    }

    function editCommissionTransaction(id) {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_commission_transaction', id: id },
            success: function(res) {
                if (res.status === 'success') {
                    // Switch to calculate tab
                    switchTab('commission_calc');
                    
                    activeEditCommId = res.commission.id;
                    $('#commission_transaction_date').val(res.commission.transaction_date);
                    $('#commissionItemsContainer').html('');
                    
                    res.items.forEach(function(item) {
                        addCommissionItemRow(item);
                    });
                    updateInvoiceTotals();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    }

    function deleteCommissionTransaction(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: 'ยอดค่าคอมมิชชั่นปันส่วนของรายการนี้จะถูกถอนออกจากการคำนวณเงินเดือนสิ้นเดือนนี้ด้วย',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ลบรายการ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'POST',
                    data: { action: 'delete_commission_transaction', id: id },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('ลบเรียบร้อย', res.message, 'success');
                            loadCommissionHistory();
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

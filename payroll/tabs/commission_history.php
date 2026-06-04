<div class="grid grid-cols-1 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
            <div>
                <h4 class="font-bold text-slate-800 text-lg">ประวัติค่าคอมมิชชั่นรายชิ้น</h4>
                <p class="text-sm text-slate-400 mt-0.5">เรียกดู แก้ไข หรือตรวจสอบรายละเอียดการปันส่วนค่าคอมมิชชั่นเฟอร์นิเจอร์</p>
            </div>
            <button onclick="switchTab('commission_calc')" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition-all shadow-md shadow-blue-500/10 gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>สร้างรายการคิดค่าคอมใหม่</span>
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">วันที่ทำรายการ</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">จำนวนสินค้า</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider text-right">ยอดราคาสินค้ารวม</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider text-right">ยอดค่าคอมมิชชั่นรวม</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider text-center">สถานะ</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="commissionHistoryTableBody" class="divide-y divide-slate-100">
                    <!-- Dynamic -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Commission Details Modal -->
<div id="viewCommissionModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" onclick="closeViewModal()"></div>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden transform transition-all text-left relative z-10">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 text-blue-600 p-2 rounded-xl">
                    <i class="fa-solid fa-file-invoice-dollar text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 text-lg">รายละเอียดการปันส่วนค่าคอมมิชชั่น</h3>
                    <p class="text-sm text-slate-500 font-medium">วันที่ปิดการขาย: <span id="modal_view_date" class="text-slate-700 font-bold"></span></p>
                </div>
            </div>
            <button type="button" onclick="closeViewModal()" class="text-slate-400 hover:text-rose-500 hover:bg-rose-50 p-2 rounded-xl transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <!-- Body (Scrollable) -->
        <div class="p-6 overflow-y-auto flex-1 bg-slate-50/30">
            <!-- Items Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-4 py-3 text-sm font-bold text-slate-500 uppercase">สินค้า</th>
                                <th class="px-4 py-3 text-sm font-bold text-slate-500 uppercase text-center">จำนวน</th>
                                <th class="px-4 py-3 text-sm font-bold text-slate-500 uppercase text-right">ราคารวม (บาท)</th>
                                <th class="px-4 py-3 text-sm font-bold text-purple-500 uppercase text-right">แอดมิน</th>
                                <th class="px-4 py-3 text-sm font-bold text-blue-500 uppercase text-right">คนปิดการขาย</th>
                                <th class="px-4 py-3 text-sm font-bold text-amber-500 uppercase text-right">ผู้ช่วย</th>
                            </tr>
                        </thead>
                        <tbody id="modal_view_items_body" class="divide-y divide-slate-100">
                            <!-- Injected rows -->
                        </tbody>
                        <tfoot class="bg-slate-50 border-t border-slate-200">
                            <tr>
                                <th colspan="2" class="px-4 py-3 text-base font-bold text-slate-600 text-right">รวมทั้งหมด:</th>
                                <th class="px-4 py-3 text-base font-extrabold text-slate-800 text-right" id="modal_view_total_sales">0.00</th>
                                <th class="px-4 py-3 text-base font-bold text-purple-600 text-right" id="modal_view_total_admin">0.00</th>
                                <th class="px-4 py-3 text-base font-bold text-blue-600 text-right" id="modal_view_total_sales_comm">0.00</th>
                                <th class="px-4 py-3 text-base font-bold text-amber-600 text-right" id="modal_view_total_helper">0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Summary Total -->
            <div class="mt-6 flex justify-end">
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-6 py-4 flex items-center gap-6 shadow-sm">
                    <span class="text-base font-bold text-emerald-700 uppercase tracking-wider">รวมค่าคอมมิชชั่นทั้งสิ้น</span>
                    <span class="text-2xl font-extrabold text-emerald-600" id="modal_view_grand_total">0.00 บาท</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <span class="text-base font-bold text-slate-500 flex items-center gap-2">
                สถานะ: <span id="modal_view_status"></span>
            </span>
            <div class="flex gap-3">
                <button type="button" onclick="closeViewModal()" class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold rounded-xl text-base transition-all shadow-sm">
                    ปิดหน้าต่าง
                </button>
                <button type="button" id="modal_btn_edit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-base transition-all shadow-md shadow-blue-500/20 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square"></i> แก้ไขข้อมูล
                </button>
            </div>
        </div>
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
                            ? `<span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-600 text-xs font-bold rounded-full border border-emerald-100"><i class="fa-solid fa-circle-check mr-0.5"></i> อนุมัติแล้ว</span>`
                            : `<span class="px-2.5 py-0.5 bg-amber-50 text-amber-600 text-xs font-bold rounded-full border border-amber-100"><i class="fa-solid fa-clock mr-0.5"></i> แบบร่าง</span>`;

                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-bold text-slate-700">${dateText}</td>
                            <td class="px-6 py-4 text-sm text-slate-500 font-semibold">${row.total_items} รายการ</td>
                            <td class="px-6 py-4 text-sm text-right font-bold text-slate-600">${parseFloat(row.total_amount).toLocaleString('th-TH', {minimumFractionDigits: 2})} บาท</td>
                            <td class="px-6 py-4 text-sm text-right font-extrabold text-blue-600">${parseFloat(row.total_commission).toLocaleString('th-TH', {minimumFractionDigits: 2})} บาท</td>
                            <td class="px-6 py-4 text-center">${statusBadge}</td>
                            <td class="px-6 py-4 text-sm text-right space-x-2">
                                <button onclick="viewCommissionTransaction(${row.id})" class="p-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-900 rounded-lg transition-all" title="เรียกดูรายละเอียด">
                                    <i class="fa-solid fa-eye text-lg"></i>
                                </button>
                                <button onclick="editCommissionTransaction(${row.id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="แก้ไข">
                                    <i class="fa-solid fa-pen-to-square text-lg"></i>
                                </button>
                                <button onclick="deleteCommissionTransaction(${row.id})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="ลบ">
                                    <i class="fa-solid fa-trash text-lg"></i>
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

    function viewCommissionTransaction(id) {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_commission_transaction', id: id },
            success: function(res) {
                if (res.status === 'success') {
                    // Populate modal
                    $('#modal_view_date').text(formatThaiDate(res.commission.transaction_date));
                    
                    const statusBadge = res.commission.status === 'approved' 
                        ? `<span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-md text-xs">อนุมัติแล้ว</span>`
                        : `<span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-md text-xs">แบบร่าง</span>`;
                    $('#modal_view_status').html(statusBadge);

                    $('#modal_btn_edit').attr('onclick', `closeViewModal(); editCommissionTransaction(${id});`);

                    let tbody = '';
                    let totalAdmin = 0, totalSalesComm = 0, totalHelper = 0, totalSalesAmount = 0;

                    res.items.forEach(function(item) {
                        totalSalesAmount += parseFloat(item.total_price);
                        totalAdmin += parseFloat(item.admin_commission);
                        totalSalesComm += parseFloat(item.sales_commission);
                        
                        let helperComm = 0;
                        if (item.helper1_employee_id) helperComm += parseFloat(item.helper1_commission);
                        if (item.helper2_employee_id) helperComm += parseFloat(item.helper2_commission);
                        totalHelper += helperComm;

                        tbody += `
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-bold text-slate-700">${item.product_code}</div>
                                    <div class="text-xs font-semibold text-slate-500">${item.product_name}</div>
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-slate-600 text-center">${item.quantity} ${item.unit}</td>
                                <td class="px-4 py-3 text-sm font-bold text-slate-700 text-right">${parseFloat(item.total_price).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="text-sm font-extrabold text-purple-600">${parseFloat(item.admin_commission).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                                    ${item.admin_employee_id ? `<div class="text-[11px] font-semibold text-purple-400 mt-0.5">${parseFloat(item.admin_rate).toFixed(2)}%</div>` : '<div class="text-[11px] text-slate-300">-</div>'}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="text-sm font-extrabold text-blue-600">${parseFloat(item.sales_commission).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                                    <div class="text-[11px] font-semibold text-blue-400 mt-0.5">${parseFloat(item.sales_rate).toFixed(2)}%</div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="text-sm font-extrabold text-amber-600">${parseFloat(helperComm).toLocaleString('th-TH', {minimumFractionDigits: 2})}</div>
                                </td>
                            </tr>
                        `;
                    });

                    $('#modal_view_items_body').html(tbody);
                    $('#modal_view_total_sales').text(totalSalesAmount.toLocaleString('th-TH', {minimumFractionDigits: 2}));
                    $('#modal_view_total_admin').text(totalAdmin.toLocaleString('th-TH', {minimumFractionDigits: 2}));
                    $('#modal_view_total_sales_comm').text(totalSalesComm.toLocaleString('th-TH', {minimumFractionDigits: 2}));
                    $('#modal_view_total_helper').text(totalHelper.toLocaleString('th-TH', {minimumFractionDigits: 2}));
                    
                    const grandTotal = totalAdmin + totalSalesComm + totalHelper;
                    $('#modal_view_grand_total').text(grandTotal.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');

                    // Show modal
                    $('#viewCommissionModal').removeClass('hidden');
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    }

    function closeViewModal() {
        $('#viewCommissionModal').addClass('hidden');
    }

    function editCommissionTransaction(id) {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_commission_transaction', id: id },
            success: function(res) {
                if (res.status === 'success') {
                    // Set global variable so that loadCommissionCalc can pick it up
                    // AFTER loading employees list
                    window.pendingEditCommissionData = res;
                    
                    // Switch to calculate tab, which triggers loadCommissionCalc
                    switchTab('commission_calc');
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

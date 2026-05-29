<div class="grid grid-cols-1 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Header & Action -->
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
            <div>
                <h4 class="font-bold text-slate-800 text-base">รายการเงินเพิ่ม - เงินหัก (Master)</h4>
                <p class="text-xs text-slate-400 mt-0.5">สร้างและจัดการประเภทรายการสำหรับเงินเพิ่ม (Allowances) และเงินหัก (Deductions)</p>
            </div>
            <button onclick="openAdjustmentModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10 gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>เพิ่มรายการใหม่</span>
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ลำดับ</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ชื่อรายการ</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="adjustmentsTableBody" class="divide-y divide-slate-100">
                    <!-- Loaded dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Adjustment Modal -->
<div id="adjustmentModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-slate-900/40 backdrop-blur-sm" onclick="closeAdjustmentModal()"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="adjustmentModalTitle" class="text-lg font-bold text-slate-800">เพิ่มรายการเงินเพิ่ม-เงินหัก</h3>
                    <button onclick="closeAdjustmentModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                
                <form id="adjustmentForm" class="space-y-4">
                    <input type="hidden" id="adjustment_id" name="id">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">ชื่อรายการ <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="adjustment_name" required placeholder="เช่น ค่าเบี้ยขยัน, ค่าปรับสาย"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none text-slate-700 font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">ประเภทรายการ <span class="text-rose-500">*</span></label>
                        <select name="type" id="adjustment_type" required
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none text-slate-700 font-semibold">
                            <option value="allowance">เงินเพิ่ม (Allowance)</option>
                            <option value="deduction">เงินหัก (Deduction)</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3">
                <button type="button" onclick="saveAdjustmentItem()" 
                        class="inline-flex justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10">
                    บันทึกข้อมูล
                </button>
                <button type="button" onclick="closeAdjustmentModal()"
                        class="inline-flex justify-center px-4 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 text-xs transition-all">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function loadAdjustmentsTable() {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_adjustment_items' },
            success: function(data) {
                let html = '';
                if (data && data.length > 0) {
                    data.forEach(function(item, index) {
                        const typeBadge = item.type === 'allowance'
                            ? `<span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-600 text-xs font-bold rounded-full border border-emerald-100"><i class="fa-solid fa-circle-plus mr-1"></i> เงินเพิ่ม</span>`
                            : `<span class="px-2.5 py-0.5 bg-rose-50 text-rose-600 text-xs font-bold rounded-full border border-rose-100"><i class="fa-solid fa-circle-minus mr-1"></i> เงินหัก</span>`;

                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-500">${index + 1}</td>
                            <td class="px-6 py-4 text-sm text-slate-700 font-semibold">${item.name}</td>
                            <td class="px-6 py-4 text-sm">${typeBadge}</td>
                            <td class="px-6 py-4 text-sm text-right space-x-2">
                                <button onclick="editAdjustment(${item.id}, '${item.name}', '${item.type}')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="แก้ไข">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button onclick="deleteAdjustment(${item.id})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="ลบ">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = `
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-tags text-3xl mb-2 opacity-30"></i>
                                <div>ยังไม่มีข้อมูลรายการเงินเพิ่ม-เงินหัก</div>
                            </div>
                        </td>
                    </tr>`;
                }
                $('#adjustmentsTableBody').html(html);
            }
        });
    }

    function openAdjustmentModal() {
        $('#adjustmentForm')[0].reset();
        $('#adjustment_id').val('');
        $('#adjustmentModalTitle').text('เพิ่มรายการเงินเพิ่ม-เงินหัก');
        $('#adjustmentModal').removeClass('hidden');
    }

    function closeAdjustmentModal() {
        $('#adjustmentModal').addClass('hidden');
    }

    function editAdjustment(id, name, type) {
        $('#adjustment_id').val(id);
        $('#adjustment_name').val(name);
        $('#adjustment_type').val(type);
        $('#adjustmentModalTitle').text('แก้ไขข้อมูลรายการ');
        $('#adjustmentModal').removeClass('hidden');
    }

    function saveAdjustmentItem() {
        const formData = $('#adjustmentForm').serialize();
        $.ajax({
            url: 'payroll_action.php',
            type: 'POST',
            data: formData + '&action=save_adjustment_item',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    });
                    closeAdjustmentModal();
                    loadAdjustmentsTable();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    }

    function deleteAdjustment(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: 'หากลบแล้ว ประวัติบันทึกเงินเพิ่ม/หักรายวันของรายการนี้จะถูกลบไปด้วย!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'POST',
                    data: { action: 'delete_adjustment_item', id: id },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('ลบสำเร็จ!', res.message, 'success');
                            loadAdjustmentsTable();
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

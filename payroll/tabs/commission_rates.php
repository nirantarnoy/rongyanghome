<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="space-y-6 lg:col-span-1">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-6">
            <div>
                <h4 class="font-bold text-slate-800 text-base">อัตราค่าคอมมิชชั่นเริ่มต้น</h4>
                <p class="text-xs text-slate-400 mt-0.5">ระบุอัตราเปอร์เซ็นต์ค่าคอมมิชชั่นเริ่มต้นสำหรับพนักงานเปิดการขายและผู้ช่วย</p>
            </div>
            
            <form id="commissionSettingsForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">อัตราพนักงานเปิดการขาย (%)</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" max="100" name="sales_rate" id="comm_sales_rate" required
                               class="w-full pl-4 pr-12 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 transition-all">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">อัตราผู้ช่วยติดตามงานช่างรวม (%)</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" max="100" name="helper_rate" id="comm_helper_rate" required
                               class="w-full pl-4 pr-12 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 transition-all">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1.5">หมายเหตุ: ค่าคอมมิชชั่นผู้ช่วยจะถูกแบ่งเท่าๆ กันตามจำนวนผู้ช่วยที่ถูกเลือกในรายการขาย (สูงสุด 2 คน)</p>
                </div>

                <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>บันทึกตั้งค่าค่าคอมมิชชั่น</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function loadCommissionSettings() {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_commission_settings' },
            success: function(res) {
                if (res) {
                    $('#comm_sales_rate').val(res.sales_rate);
                    $('#comm_helper_rate').val(res.helper_rate);
                }
            }
        });
    }

    $(document).ready(function() {
        $('#commissionSettingsForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            $.ajax({
                url: 'payroll_action.php',
                type: 'POST',
                data: formData + '&action=save_commission_settings',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadCommissionSettings();
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                    }
                }
            });
        });
    });
</script>

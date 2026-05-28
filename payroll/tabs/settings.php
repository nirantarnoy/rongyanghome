<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column: General Payroll Settings -->
    <div class="space-y-6 lg:col-span-1">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-6">
            <div>
                <h4 class="font-bold text-slate-800 text-base">การจ่ายเงินเดือน</h4>
                <p class="text-xs text-slate-400 mt-0.5">ระบุการตั้งค่าพื้นฐานสำหรับวันทำรายการจ่ายเงิน</p>
            </div>
            
            <form id="payDayForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">กำหนดวันจ่ายเงินเดือน (เลือกได้หลายวัน)</label>
                    <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 max-h-[220px] overflow-y-auto p-3 bg-slate-50 border border-slate-200 rounded-xl">
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <label class="flex items-center gap-1.5 cursor-pointer p-1.5 hover:bg-slate-200/50 rounded-lg transition-colors">
                                <input type="checkbox" value="<?= $d ?>" class="pay-day-chk w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                                <span class="text-xs font-semibold text-slate-700"><?= $d ?></span>
                            </label>
                        <?php endfor; ?>
                        <label class="col-span-4 sm:col-span-6 flex items-center gap-1.5 cursor-pointer p-2 bg-blue-50/50 hover:bg-blue-100/50 border border-blue-100 rounded-lg transition-colors mt-1">
                            <input type="checkbox" value="L" class="pay-day-chk w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                            <span class="text-xs font-bold text-blue-700"><i class="fa-solid fa-calendar-day mr-1"></i> วันสิ้นเดือน (30 หรือ 31)</span>
                        </label>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">หมายเหตุ: ระบบรองรับการระบุวันจ่ายเงินเดือนหลายวันต่อเดือน สำหรับการจ่ายงวด 2 หรือ 3 ครั้ง</p>
                </div>
                <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>บันทึกการตั้งค่า</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Holidays Settings -->
    <div class="space-y-6 lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <!-- Header & Action -->
            <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
                <div>
                    <h4 class="font-bold text-slate-800 text-base">จัดการวันหยุดประจำปี</h4>
                    <p class="text-xs text-slate-400 mt-0.5">เพิ่มข้อมูลวันหยุดบริษัทเพื่อให้ระบบงดการลงเวลาเข้างาน</p>
                </div>
                <button onclick="openHolidayModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10 gap-1.5">
                    <i class="fa-solid fa-plus"></i>
                    <span>เพิ่มวันหยุดใหม่</span>
                </button>
            </div>

            <!-- Table of Holidays -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ลำดับ</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">วันที่หยุด</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ชื่อวันหยุด</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="holidaysTableBody" class="divide-y divide-slate-100">
                        <!-- Loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Holiday Modal -->
<div id="holidayModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-slate-900/40 backdrop-blur-sm" onclick="closeHolidayModal()"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="holidayModalTitle" class="text-lg font-bold text-slate-800">เพิ่มวันหยุดใหม่</h3>
                    <button onclick="closeHolidayModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                
                <form id="holidayForm" class="space-y-4">
                    <input type="hidden" id="holiday_id" name="id">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">วันที่หยุด <span class="text-rose-500">*</span></label>
                        <input type="date" name="holiday_date" id="holiday_date" required
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none text-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5">ชื่อวันหยุด / รายละเอียด <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="holiday_name" required placeholder="เช่น วันขึ้นปีใหม่, วันสงกรานต์"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none text-slate-700">
                    </div>
                </form>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3">
                <button type="button" onclick="saveHoliday()" 
                        class="inline-flex justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10">
                    บันทึกข้อมูล
                </button>
                <button type="button" onclick="closeHolidayModal()"
                        class="inline-flex justify-center px-4 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 text-xs transition-all">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function loadSettings() {
        // Load settings pay_day value
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_settings' },
            success: function(data) {
                // Clear checkboxes
                $('.pay-day-chk').prop('checked', false);
                
                if (data.pay_day) {
                    const days = data.pay_day.split(',');
                    days.forEach(function(day) {
                        $(`.pay-day-chk[value="${day.trim()}"]`).prop('checked', true);
                    });
                }
            }
        });

        // Load holidays list table
        loadHolidaysTable();
    }

    function loadHolidaysTable() {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_holidays' },
            success: function(data) {
                let html = '';
                if (data.length > 0) {
                    data.forEach(function(h, index) {
                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-500">${index + 1}</td>
                            <td class="px-6 py-4 text-sm text-slate-700 font-semibold">${formatThaiDate(h.holiday_date)}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${h.name}</td>
                            <td class="px-6 py-4 text-sm text-right space-x-2">
                                <button onclick="editHoliday(${h.id}, '${h.holiday_date}', '${h.name}')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="แก้ไข">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button onclick="deleteHoliday(${h.id})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="ลบ">
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
                                <i class="fa-solid fa-calendar-xmark text-3xl mb-2 opacity-30"></i>
                                <div>ยังไม่มีข้อมูลวันหยุดบริษัท</div>
                            </div>
                        </td>
                    </tr>`;
                }
                $('#holidaysTableBody').html(html);
            }
        });
    }

    // Submit pay_day form
    $('#payDayForm').on('submit', function(e) {
        e.preventDefault();
        
        // Collect checked days
        const selectedDays = [];
        $('.pay-day-chk:checked').each(function() {
            selectedDays.push($(this).val());
        });
        
        if (selectedDays.length === 0) {
            Swal.fire('คำเตือน', 'กรุณาเลือกวันจ่ายเงินเดือนอย่างน้อย 1 วัน', 'warning');
            return;
        }

        const pay_day_str = selectedDays.join(',');
        
        $.ajax({
            url: 'payroll_action.php',
            type: 'POST',
            data: { action: 'save_settings', pay_day: pay_day_str },
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    });

    // Holiday Modal Controls
    function openHolidayModal() {
        $('#holidayForm')[0].reset();
        $('#holiday_id').val('');
        $('#holidayModalTitle').text('เพิ่มวันหยุดใหม่');
        $('#holidayModal').removeClass('hidden');
    }

    function closeHolidayModal() {
        $('#holidayModal').addClass('hidden');
    }

    function editHoliday(id, date, name) {
        $('#holiday_id').val(id);
        $('#holiday_date').val(date);
        $('#holiday_name').val(name);
        $('#holidayModalTitle').text('แก้ไขข้อมูลวันหยุด');
        $('#holidayModal').removeClass('hidden');
    }

    function saveHoliday() {
        const formData = $('#holidayForm').serialize();
        $.ajax({
            url: 'payroll_action.php',
            type: 'POST',
            data: formData + '&action=save_holiday',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    });
                    closeHolidayModal();
                    loadHolidaysTable();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    }

    function deleteHoliday(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: 'คุณต้องการลบวันหยุดประจำปีนี้ใช่หรือไม่?',
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
                    data: { action: 'delete_holiday', id: id },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('ลบสำเร็จ!', res.message, 'success');
                            loadHolidaysTable();
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

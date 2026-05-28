<!-- Date & Actions Bar -->
<div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <!-- Date Selector -->
    <div class="flex items-center gap-3">
        <span class="text-xs font-semibold text-slate-500 uppercase">วันที่บันทึกงาน:</span>
        <input type="date" id="attendance_date_input" value="<?= date('Y-m-d') ?>" onchange="loadAttendanceList()"
               class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none font-bold text-slate-700 transition-all">
    </div>

    <!-- Quick Tooltip Actions -->
    <div class="flex flex-wrap gap-2">
        <button onclick="setAllNormal()" class="inline-flex items-center px-3 py-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200 hover:border-emerald-300 font-bold rounded-xl text-xs transition-all gap-1.5">
            <i class="fa-solid fa-circle-check"></i>
            <span>เช็คอินเข้างานปกติทั้งหมด</span>
        </button>
        <button onclick="setAllCheckout()" class="inline-flex items-center px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 hover:border-blue-300 font-bold rounded-xl text-xs transition-all gap-1.5">
            <i class="fa-solid fa-clock"></i>
            <span>เช็คเอาท์ออกงานทั้งหมด</span>
        </button>
        <button onclick="saveAttendanceBatch()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10 gap-1.5">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>บันทึกข้อมูลเวลาทำงาน</span>
        </button>
    </div>
</div>

<!-- Daily Status Stats Widget -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100 flex items-center justify-between">
        <div>
            <span class="text-[10px] text-emerald-600 font-bold uppercase">มาทำงานปกติ</span>
            <h4 class="text-xl font-bold text-emerald-700 mt-0.5" id="att-summary-normal">0 คน</h4>
        </div>
        <i class="fa-solid fa-circle-check text-emerald-300 text-2xl"></i>
    </div>
    <div class="bg-amber-50 rounded-2xl p-4 border border-amber-100 flex items-center justify-between">
        <div>
            <span class="text-[10px] text-amber-600 font-bold uppercase">มาทำงานสาย</span>
            <h4 class="text-xl font-bold text-amber-700 mt-0.5" id="att-summary-late">0 คน</h4>
        </div>
        <i class="fa-solid fa-triangle-exclamation text-amber-300 text-2xl"></i>
    </div>
    <div class="bg-rose-50 rounded-2xl p-4 border border-rose-100 flex items-center justify-between">
        <div>
            <span class="text-[10px] text-rose-600 font-bold uppercase">ขาดงาน</span>
            <h4 class="text-xl font-bold text-rose-700 mt-0.5" id="att-summary-absent">0 คน</h4>
        </div>
        <i class="fa-solid fa-circle-xmark text-rose-300 text-2xl"></i>
    </div>
    <div class="bg-blue-50 rounded-2xl p-4 border border-blue-100 flex items-center justify-between">
        <div>
            <span class="text-[10px] text-blue-600 font-bold uppercase">ลางาน</span>
            <h4 class="text-xl font-bold text-blue-700 mt-0.5" id="att-summary-leave">0 คน</h4>
        </div>
        <i class="fa-solid fa-umbrella-beach text-blue-300 text-2xl"></i>
    </div>
</div>

<!-- Attendance Form/Table Card -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-4 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">รหัสพนักงาน</th>
                    <th class="px-4 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ชื่อ-นามสกุล</th>
                    <th class="px-4 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">แผนก / ตำแหน่ง</th>
                    <th class="px-4 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-4 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">เวลาเข้า</th>
                    <th class="px-4 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">เวลาออก</th>
                    <th class="px-4 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ประเภทการลา</th>
                    <th class="px-4 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">หมายเหตุ</th>
                    <th class="px-4 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">บันทึกเดี่ยว</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody" class="divide-y divide-slate-100">
                <!-- Loaded dynamically -->
            </tbody>
        </table>
    </div>
</div>

<script>
    let currentAttendanceList = [];

    function loadAttendanceList() {
        const dateStr = $('#attendance_date_input').val();
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_attendance', work_date: dateStr },
            success: function(data) {
                currentAttendanceList = data;
                let html = '';
                
                let sumNormal = 0, sumLate = 0, sumAbsent = 0, sumLeave = 0;

                if (data.length > 0) {
                    data.forEach(function(row) {
                        let status = row.status || 'normal';
                        let checkIn = row.check_in ? row.check_in.substring(0, 5) : '';
                        let checkOut = row.check_out ? row.check_out.substring(0, 5) : '';
                        let leaveType = row.leave_type || '';
                        let note = row.note || '';

                        // Count sums
                        if (status === 'normal') sumNormal++;
                        if (status === 'late') sumLate++;
                        if (status === 'absent') sumAbsent++;
                        if (status === 'leave') sumLeave++;

                        // Build statuses options
                        let optNormal = status === 'normal' ? 'selected' : '';
                        let optLate = status === 'late' ? 'selected' : '';
                        let optAbsent = status === 'absent' ? 'selected' : '';
                        let optLeave = status === 'leave' ? 'selected' : '';

                        // Build leave types options
                        let loptBusiness = leaveType === 'business' ? 'selected' : '';
                        let loptSick = leaveType === 'sick' ? 'selected' : '';
                        let loptAnnual = leaveType === 'annual' ? 'selected' : '';
                        let loptOther = leaveType === 'other' ? 'selected' : '';

                        // Disable time inputs for absent/leave
                        let timeDisabled = (status === 'absent' || status === 'leave') ? 'disabled' : '';
                        let leaveDisabled = (status !== 'leave') ? 'disabled' : '';

                        let avatarHtml = '';
                        if (row.photo) {
                            avatarHtml = `<img src="../${row.photo}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0">`;
                        } else {
                            let initials = (row.first_name ? row.first_name.charAt(0) : '') + (row.last_name ? row.last_name.charAt(0) : '');
                            avatarHtml = `<div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 font-bold text-xs flex items-center justify-center border border-blue-100 shadow-sm flex-shrink-0">${initials}</div>`;
                        }

                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors" data-employee-id="${row.employee_id}">
                            <td class="px-4 py-3 text-xs font-bold text-slate-800">${row.emp_code}</td>
                            <td class="px-4 py-3 text-xs font-semibold text-slate-700">
                                <div class="flex items-center gap-3">
                                    ${avatarHtml}
                                    <span>${row.first_name} ${row.last_name}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                <div class="font-medium">${row.position}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">${row.department}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <select onchange="onRowStatusChange(${row.employee_id}, this.value)" class="row-status px-2 py-1.5 border border-slate-200 rounded-lg bg-slate-50 text-xs font-semibold text-slate-700 outline-none focus:ring-1 focus:ring-blue-500 transition-all">
                                    <option value="normal" ${optNormal}>มาทำงานปกติ</option>
                                    <option value="late" ${optLate}>มาสาย</option>
                                    <option value="absent" ${optAbsent}>ขาดงาน</option>
                                    <option value="leave" ${optLeave}>ลางาน</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <input type="time" value="${checkIn}" ${timeDisabled}
                                       class="row-check-in px-2 py-1.5 border border-slate-200 rounded-lg bg-slate-50 text-xs text-slate-700 outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-40 transition-all">
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <input type="time" value="${checkOut}" ${timeDisabled}
                                       class="row-check-out px-2 py-1.5 border border-slate-200 rounded-lg bg-slate-50 text-xs text-slate-700 outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-40 transition-all">
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <select ${leaveDisabled} class="row-leave-type px-2 py-1.5 border border-slate-200 rounded-lg bg-slate-50 text-xs font-semibold text-slate-700 outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-40 transition-all">
                                    <option value="" ${leaveType === '' ? 'selected' : ''}>-- ประเภทการลา --</option>
                                    <option value="business" ${loptBusiness}>ลากิจ</option>
                                    <option value="sick" ${loptSick}>ลาป่วย</option>
                                    <option value="annual" ${loptAnnual}>ลาพักร้อน</option>
                                    <option value="other" ${loptOther}>ลาอื่นๆ</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <input type="text" value="${note}" placeholder="ระบุเหตุผล/หมายเหตุ"
                                       class="row-note w-full px-2 py-1.5 border border-slate-200 rounded-lg bg-slate-50 text-xs text-slate-700 outline-none focus:ring-1 focus:ring-blue-500 transition-all">
                            </td>
                            <td class="px-4 py-3 text-xs text-right">
                                <button onclick="saveSingleRow(${row.employee_id})" class="p-2 bg-slate-100 hover:bg-blue-600 text-slate-500 hover:text-white rounded-lg transition-all" title="บันทึกพนักงานรายนี้">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = `
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-users-slash text-3xl mb-2 opacity-30"></i>
                                <div>ยังไม่มีข้อมูลพนักงานในระบบเพื่อทำการลงบันทึกเวลา</div>
                            </div>
                        </td>
                    </tr>`;
                }

                $('#attendanceTableBody').html(html);

                // Set summary widgets
                $('#att-summary-normal').text(sumNormal + ' คน');
                $('#att-summary-late').text(sumLate + ' คน');
                $('#att-summary-absent').text(sumAbsent + ' คน');
                $('#att-summary-leave').text(sumLeave + ' คน');
            }
        });
    }

    // Toggle Inputs dynamically on Status Change
    function onRowStatusChange(empId, status) {
        const row = $(`tr[data-employee-id="${empId}"]`);
        const checkIn = row.find('.row-check-in');
        const checkOut = row.find('.row-check-out');
        const leaveType = row.find('.row-leave-type');

        if (status === 'absent' || status === 'leave') {
            checkIn.val('').prop('disabled', true);
            checkOut.val('').prop('disabled', true);
        } else {
            checkIn.prop('disabled', false);
            checkOut.prop('disabled', false);
            if (!checkIn.val()) checkIn.val('08:00');
            if (!checkOut.val()) checkOut.val('17:00');
        }

        if (status === 'leave') {
            leaveType.prop('disabled', false);
            if (!leaveType.val()) leaveType.val('business');
        } else {
            leaveType.val('').prop('disabled', true);
        }
    }

    // Quick Tool Actions
    function setAllNormal() {
        $('#attendanceTableBody tr').each(function() {
            const row = $(this);
            row.find('.row-status').val('normal');
            row.find('.row-check-in').val('08:00').prop('disabled', false);
            row.find('.row-check-out').val('17:00').prop('disabled', false);
            row.find('.row-leave-type').val('').prop('disabled', true);
        });
    }

    function setAllCheckout() {
        $('#attendanceTableBody tr').each(function() {
            const row = $(this);
            const status = row.find('.row-status').val();
            if (status === 'normal' || status === 'late') {
                row.find('.row-check-out').val('17:00');
            }
        });
    }

    // Save Single Row
    function saveSingleRow(empId) {
        const dateStr = $('#attendance_date_input').val();
        const row = $(`tr[data-employee-id="${empId}"]`);
        
        const status = row.find('.row-status').val();
        const checkIn = row.find('.row-check-in').val();
        const checkOut = row.find('.row-check-out').val();
        const leaveType = row.find('.row-leave-type').val();
        const note = row.find('.row-note').val();

        if (status === 'leave' && !leaveType) {
            Swal.fire('คำเตือน', 'กรุณาระบุประเภทการลาหยุด', 'warning');
            return;
        }

        $.ajax({
            url: 'payroll_action.php',
            type: 'POST',
            data: {
                action: 'save_attendance',
                employee_id: empId,
                work_date: dateStr,
                status: status,
                check_in: checkIn,
                check_out: checkOut,
                leave_type: leaveType,
                note: note
            },
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'บันทึกเวลาทำงานพนักงานคนนี้เรียบร้อยแล้ว',
                        timer: 1000,
                        showConfirmButton: false
                    });
                    loadAttendanceList();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    }

    // Save Batch
    function saveAttendanceBatch() {
        const dateStr = $('#attendance_date_input').val();
        const attendanceData = [];
        let validationError = false;

        $('#attendanceTableBody tr').each(function() {
            const row = $(this);
            const empId = row.attr('data-employee-id');
            if (empId) {
                const status = row.find('.row-status').val();
                const checkIn = row.find('.row-check-in').val();
                const checkOut = row.find('.row-check-out').val();
                const leaveType = row.find('.row-leave-type').val();
                const note = row.find('.row-note').val();

                if (status === 'leave' && !leaveType) {
                    validationError = true;
                    row.find('.row-leave-type').addClass('border-rose-500 bg-rose-50');
                } else {
                    row.find('.row-leave-type').removeClass('border-rose-500 bg-rose-50');
                }

                attendanceData.push({
                    employee_id: empId,
                    status: status,
                    check_in: checkIn,
                    check_out: checkOut,
                    leave_type: leaveType,
                    note: note
                });
            }
        });

        if (validationError) {
            Swal.fire('ข้อมูลไม่ถูกต้อง', 'กรุณาระบุประเภทการลาให้กับพนักงานที่มีสถานะ ลาหยุดงาน', 'warning');
            return;
        }

        if (attendanceData.length === 0) {
            Swal.fire('ไม่มีข้อมูล', 'ไม่มีพนักงานในตารางเพื่อทำการบันทึก', 'info');
            return;
        }

        $.ajax({
            url: 'payroll_action.php',
            type: 'POST',
            data: {
                action: 'save_attendance_batch',
                work_date: dateStr,
                attendance_data: JSON.stringify(attendanceData)
            },
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadAttendanceList();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    }
</script>

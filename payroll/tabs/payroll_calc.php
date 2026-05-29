<!-- Sub Tabs navigation -->
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div class="flex bg-slate-200/60 p-1 rounded-xl">
        <button onclick="switchCalcSubTab('calculator')" id="subbtn-calculator" class="px-4 py-2 text-xs font-bold rounded-lg transition-all text-slate-700 bg-white shadow-sm">
            <i class="fa-solid fa-calculator mr-1"></i> คำนวณเงินเดือนรอบปัจจุบัน
        </button>
        <button onclick="switchCalcSubTab('history')" id="subbtn-history" class="px-4 py-2 text-xs font-bold rounded-lg transition-all text-slate-500 hover:text-slate-700 ml-1">
            <i class="fa-solid fa-history mr-1"></i> ประวัติรอบบัญชีและสรุปยอดจ่าย
        </button>
    </div>

    <!-- Date selector (Only shows when on calculator tab) -->
    <div id="calcPeriodSelector" class="flex items-center gap-3">
        <span class="text-xs font-semibold text-slate-500 uppercase">เลือกงวดเงินเดือน:</span>
        <input type="month" id="payroll_month_input" value="<?= date('Y-m') ?>" onchange="loadPayrollCalculation()"
               class="px-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-xs outline-none font-bold text-slate-700 transition-all">
    </div>
</div>

<!-- ========================================== -->
<!-- VIEW: CALCULATOR (MAIN) -->
<!-- ========================================== -->
<div id="calc-view-calculator" class="space-y-6">
    <!-- Summary Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-600 rounded-2xl p-5 text-white shadow-md shadow-blue-500/10 flex items-center justify-between">
            <div>
                <span class="text-[10px] text-blue-200 font-bold uppercase tracking-wider">ยอดเงินรวมสั่งจ่าย</span>
                <h3 class="text-2xl font-bold mt-1" id="sum-total-payout">0.00 บาท</h3>
            </div>
            <div class="bg-blue-500/30 p-3 rounded-xl text-white">
                <i class="fa-solid fa-receipt text-2xl"></i>
            </div>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">พนักงานทั้งหมดที่จ่าย</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1" id="sum-total-employees">0 คน</h3>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl text-slate-400 border border-slate-100">
                <i class="fa-solid fa-users text-2xl"></i>
            </div>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">ค่าจ้างเฉลี่ย / คน</span>
                <h3 class="text-2xl font-bold text-slate-800 mt-1" id="sum-average-payout">0.00 บาท</h3>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl text-slate-400 border border-slate-100">
                <i class="fa-solid fa-calculator text-2xl"></i>
            </div>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">สถานะการคำนวณ</span>
                <div class="mt-1" id="payroll-run-status-badge">
                    <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-xs font-bold rounded-full">ยังไม่บันทึก</span>
                </div>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl text-slate-400 border border-slate-100">
                <i class="fa-solid fa-file-invoice text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Details Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4">
            <h4 class="text-xs font-bold text-slate-700 uppercase flex items-center gap-1.5">
                <i class="fa-solid fa-list-check text-blue-500"></i> รายละเอียดการสั่งจ่ายพนักงานรายบุคคล
            </h4>

            <div class="flex gap-2">
                <button onclick="savePayrollRun('pending')" id="btn-save-draft" class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold rounded-xl text-xs transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i> บันทึกร่างแบบร่าง
                </button>
                <button onclick="savePayrollRun('approved')" id="btn-save-approve" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-check"></i> อนุมัติการจ่ายเงินเดือน
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">รหัสพนักงาน</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ชื่อ-นามสกุล</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ประเภท/อัตรา</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">สถิติงาน (มา/ขาด/ลา)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right font-bold text-slate-600">เงินได้พื้นฐาน</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right text-emerald-600">เงินเพิ่มพิเศษ</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right text-rose-600">เงินหักทั้งหมด</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right font-bold text-blue-600">ยอดสุทธิที่จ่าย</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">สลิปเงินเดือน</th>
                    </tr>
                </thead>
                <tbody id="payrollTableBody" class="divide-y divide-slate-100">
                    <!-- Dynamically populated -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- VIEW: HISTORY & REPORTS -->
<!-- ========================================== -->
<div id="calc-view-history" class="space-y-6 hidden">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h4 class="text-xs font-bold text-slate-700 uppercase flex items-center gap-1.5">
                <i class="fa-solid fa-chart-pie text-blue-500"></i> ประวัติรอบบัญชีเงินเดือนและยอดจ่ายสะสม
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ประจำงวด/เดือน</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">จำนวนพนักงาน</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ยอดเงินจ่ายสุทธิทั้งหมด</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">สถานะรอบบัญชี</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">วันที่ปรับปรุงล่าสุด</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="payrollHistoryTableBody" class="divide-y divide-slate-100">
                    <!-- Loaded dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    let activeCalcSubTab = 'calculator';
    let currentPayrollRun = null;
    let computedDetails = [];

    function switchCalcSubTab(subTab) {
        activeCalcSubTab = subTab;
        $('.tab-pane').addClass('hidden');
        $('#tab-payroll_calc').removeClass('hidden');

        // Toggle buttons
        $('#subbtn-calculator').removeClass('bg-white shadow-sm text-slate-700').addClass('text-slate-500 hover:text-slate-700');
        $('#subbtn-history').removeClass('bg-white shadow-sm text-slate-700').addClass('text-slate-500 hover:text-slate-700');
        
        $(`#subbtn-${subTab}`).addClass('bg-white shadow-sm text-slate-700').removeClass('text-slate-500 hover:text-slate-700');

        // Toggle views
        $('#calc-view-calculator').addClass('hidden');
        $('#calc-view-history').addClass('hidden');
        $(`#calc-view-${subTab}`).removeClass('hidden');

        if (subTab === 'calculator') {
            $('#calcPeriodSelector').removeClass('hidden');
            loadPayrollCalculation();
        } else {
            $('#calcPeriodSelector').addClass('hidden');
            loadPayrollHistory();
        }
    }

    function loadPayrollCalc() {
        switchCalcSubTab('calculator');
    }

    function loadPayrollCalculation() {
        const monthPeriod = $('#payroll_month_input').val();
        if (!monthPeriod) return;

        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_payroll_run', month_period: monthPeriod },
            success: function(res) {
                computedDetails = res.details || [];
                currentPayrollRun = res;

                // Render badge status
                let badge = '';
                if (res.status === 'saved') {
                    if (res.run_status === 'approved') {
                        badge = `<span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 text-xs font-bold rounded-full border border-emerald-200"><i class="fa-solid fa-circle-check mr-0.5"></i> อนุมัติแล้ว</span>`;
                    } else {
                        badge = `<span class="px-2.5 py-1 bg-amber-50 text-amber-600 text-xs font-bold rounded-full border border-amber-200"><i class="fa-solid fa-clock mr-0.5"></i> แบบร่าง</span>`;
                    }
                } else {
                    badge = `<span class="px-2.5 py-1 bg-slate-100 text-slate-500 text-xs font-bold rounded-full border border-slate-200">ยังไม่บันทึก (คำนวณสด)</span>`;
                }
                $('#payroll-run-status-badge').html(badge);

                // Render details table
                let html = '';
                let totalPayout = 0;
                let totalEmp = computedDetails.length;

                if (totalEmp > 0) {
                    computedDetails.forEach(function(row) {
                        totalPayout += parseFloat(row.net_pay);
                        
                        let avatarHtml = '';
                        if (row.photo) {
                            avatarHtml = `<img src="../${row.photo}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0">`;
                        } else {
                            let initials = (row.first_name ? row.first_name.charAt(0) : '') + (row.last_name ? row.last_name.charAt(0) : '');
                            avatarHtml = `<div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 font-bold text-xs flex items-center justify-center border border-blue-100 shadow-sm flex-shrink-0">${initials}</div>`;
                        }

                        let typeBadge = row.wage_type === 'daily'
                            ? `<span class="px-1.5 py-0.5 bg-amber-50 text-amber-600 text-[10px] font-bold rounded border border-amber-100">รายวัน: ${parseFloat(row.rate).toLocaleString()}</span>`
                            : `<span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-bold rounded border border-blue-100">รายเดือน: ${parseFloat(row.rate).toLocaleString()}</span>`;

                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-bold text-slate-800">${row.emp_code}</td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-700">
                                <div class="flex items-center gap-3">
                                    ${avatarHtml}
                                    <div>
                                        <span>${row.first_name} ${row.last_name}</span>
                                        <div class="text-[10px] text-slate-400 font-normal mt-0.5">${row.position} | ${row.department}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs">${typeBadge}</td>
                            <td class="px-6 py-4 text-xs text-center font-bold">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100" title="วันทำงานปกติ + สาย">${row.present_days} มา</span>
                                    <span class="text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-100" title="วันขาดงาน">${row.absent_days} ขาด</span>
                                    <span class="text-purple-600 bg-purple-50 px-2 py-0.5 rounded border border-purple-100" title="วันหยุดลางาน">${row.leave_days} ลา</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-right font-bold text-slate-700">${parseFloat(row.base_earnings).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                            <td class="px-6 py-4 text-xs text-right font-semibold text-emerald-500">${parseFloat(row.allowance || 0) > 0 ? '+' + parseFloat(row.allowance).toLocaleString('th-TH', {minimumFractionDigits: 2}) : '0.00'}</td>
                            <td class="px-6 py-4 text-xs text-right font-semibold text-rose-500">${parseFloat(row.deductions) > 0 ? '-' + parseFloat(row.deductions).toLocaleString('th-TH', {minimumFractionDigits: 2}) : '0.00'}</td>
                            <td class="px-6 py-4 text-xs text-right font-bold text-blue-600">${parseFloat(row.net_pay).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                            <td class="px-6 py-4 text-xs text-right">
                                <button onclick="printPaySlip(${row.employee_id})" class="inline-flex items-center px-2.5 py-1.5 bg-slate-100 hover:bg-blue-600 text-slate-600 hover:text-white rounded-lg transition-all text-[11px] font-bold gap-1 shadow-sm">
                                    <i class="fa-solid fa-print"></i> พิมพ์สลิป
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
                                <div>ไม่พบข้อมูลพนักงานในระบบสำหรับงวดนี้</div>
                            </div>
                        </td>
                    </tr>`;
                }

                $('#payrollTableBody').html(html);

                // Set summary widgets
                $('#sum-total-payout').text(totalPayout.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');
                $('#sum-total-employees').text(totalEmp + ' คน');
                
                let avg = totalEmp > 0 ? (totalPayout / totalEmp) : 0;
                $('#sum-average-payout').text(avg.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');
            }
        });
    }

    function savePayrollRun(status) {
        const monthPeriod = $('#payroll_month_input').val();
        if (!monthPeriod || computedDetails.length === 0) {
            Swal.fire('ข้อผิดพลาด', 'ไม่มีข้อมูลเงินเดือนเพื่อบันทึก', 'warning');
            return;
        }

        Swal.fire({
            title: status === 'approved' ? 'อนุมัติรอบจ่ายเงินเดือน?' : 'บันทึกแบบร่าง?',
            text: status === 'approved' ? 'การอนุมัติเงินเดือนจะล็อคระบบข้อมูลจ่ายในเดือนนี้' : 'คุณต้องการบันทึกการคำนวณนี้เป็นแบบร่างหรือไม่',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3563e9',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'POST',
                    data: {
                        action: 'save_payroll_run',
                        month_period: monthPeriod,
                        status: status,
                        details: JSON.stringify(computedDetails)
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
                            loadPayrollCalculation();
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                        }
                    }
                });
            }
        });
    }

    function loadPayrollHistory() {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_payroll_runs' },
            success: function(data) {
                let html = '';
                if (data.length > 0) {
                    data.forEach(function(row) {
                        let badge = row.status === 'approved'
                            ? `<span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 text-[11px] font-bold rounded-full border border-emerald-200"><i class="fa-solid fa-circle-check mr-0.5"></i> อนุมัติแล้ว</span>`
                            : `<span class="px-2.5 py-1 bg-amber-50 text-amber-600 text-[11px] font-bold rounded-full border border-amber-200"><i class="fa-solid fa-clock mr-0.5"></i> แบบร่าง</span>`;

                        // format thai month
                        const parts = row.month_period.split('-');
                        const years = parseInt(parts[0]) + 543;
                        const months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                        const monthText = months[parseInt(parts[1]) - 1] + ' ' + years;

                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-bold text-slate-800">${monthText}</td>
                            <td class="px-6 py-4 text-xs text-slate-600">${row.total_employees} คน</td>
                            <td class="px-6 py-4 text-xs text-slate-700 font-bold text-blue-600">${parseFloat(row.total_net_pay).toLocaleString('th-TH', {minimumFractionDigits: 2})} บาท</td>
                            <td class="px-6 py-4">${badge}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">${formatThaiDate(row.updated_at)}</td>
                            <td class="px-6 py-4 text-xs text-right space-x-2">
                                <button onclick="viewHistoryMonth('${row.month_period}')" class="px-2.5 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-all font-bold text-[11px]" title="เรียกดูรายละเอียด">
                                    <i class="fa-solid fa-eye"></i> ตรวจสอบ
                                </button>
                                <button onclick="deletePayrollPeriod(${row.id})" class="px-2.5 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-lg transition-all font-bold text-[11px]" title="ลบรอบบัญชีนี้">
                                    <i class="fa-solid fa-trash"></i> ลบข้อมูล
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
                                <div>ยังไม่มีประวัติการคำนวณเงินเดือนในระบบ</div>
                            </div>
                        </td>
                    </tr>`;
                }
                $('#payrollHistoryTableBody').html(html);
            }
        });
    }

    function viewHistoryMonth(month) {
        $('#payroll_month_input').val(month);
        switchCalcSubTab('calculator');
    }

    function deletePayrollPeriod(id) {
        Swal.fire({
            title: 'ยืนยันการลบรอบบัญชีเงินเดือน?',
            text: 'การลบข้อมูลนี้จะลบสลิปเงินเดือนพนักงานสะสมทั้งหมดในงวดนี้อย่างถาวร!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'POST',
                    data: { action: 'delete_payroll_run', id: id },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('ลบข้อมูลแล้ว', res.message, 'success');
                            loadPayrollHistory();
                        } else {
                            Swal.fire('ข้อผิดพลาด', res.message, 'error');
                        }
                    }
                });
            }
        });
    }

    function thaiBaht(number) {
        if (isNaN(number) || number === null) return "ศูนย์บาทถ้วน";
        let numStr = parseFloat(number).toFixed(2);
        let parts = numStr.split('.');
        let baht = parseInt(parts[0]);
        let satang = parseInt(parts[1]);

        let bahtText = convertToThaiWords(baht);
        let satangText = convertToThaiWords(satang);

        let fullText = "";
        if (bahtText !== "") {
            fullText += bahtText + "บาท";
        }
        if (satangText !== "" && satang !== 0) {
            fullText += satangText + "สตางค์";
        } else {
            fullText += "ถ้วน";
        }
        return fullText === "ถ้วน" ? "ศูนย์บาทถ้วน" : fullText;
    }

    function convertToThaiWords(number) {
        const txtnum1 = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า', 'สิบ'];
        const txtnum2 = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
        let numStr = String(number);
        let len = numStr.length;
        let res = "";

        for (let i = 0; i < len; i++) {
            let digit = parseInt(numStr.charAt(i));
            if (digit !== 0) {
                if (i === (len - 1) && digit === 1 && len > 1) {
                    res += 'เอ็ด';
                } else if (i === (len - 2) && digit === 2) {
                    res += 'ยี่สิบ';
                } else if (i === (len - 2) && digit === 1) {
                    res += 'สิบ';
                } else {
                    res += txtnum1[digit] + txtnum2[len - i - 1];
                }
            }
        }
        return res;
    }

    // Prints a beautiful pay slip
    function printPaySlip(empId) {
        const emp = computedDetails.find(e => e.employee_id == empId);
        if (!emp) return;

        const monthPeriod = $('#payroll_month_input').val();
        const parts = monthPeriod.split('-');
        const years = parseInt(parts[0]) + 543;
        const months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        const monthText = months[parseInt(parts[1]) - 1] + ' ' + years;

        const printWindow = window.open('', '_blank', 'width=800,height=600');
        
        let photoHtml = '';
        if (emp.photo) {
            photoHtml = `<img src="../${emp.photo}" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc;">`;
        } else {
            let initials = (emp.first_name ? emp.first_name.charAt(0) : '') + (emp.last_name ? emp.last_name.charAt(0) : '');
            photoHtml = `<div style="width: 70px; height: 70px; border-radius: 50%; border: 1px solid #ccc; background-color: #f1f5f9; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; color: #1e3a8a;">${initials}</div>`;
        }

        const slipContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Pay Slip - ${emp.first_name} ${emp.last_name}</title>
            <style>
                body {
                    font-family: 'Garuda', 'Tahoma', sans-serif;
                    padding: 20px;
                    background-color: #fff;
                    color: #333;
                    font-size: 12px;
                }
                .slip-card {
                    border: 2px solid #000;
                    padding: 20px;
                    max-width: 700px;
                    margin: 0 auto;
                    position: relative;
                }
                .header-table {
                    width: 100%;
                    margin-bottom: 20px;
                    border-bottom: 2px double #000;
                    padding-bottom: 10px;
                }
                .info-table {
                    width: 100%;
                    margin-bottom: 15px;
                }
                .info-table td {
                    padding: 3px 0;
                }
                .data-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                .data-table th, .data-table td {
                    border: 1px solid #000;
                    padding: 6px;
                }
                .data-table th {
                    background-color: #f2f2f2;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                .footer-sign {
                    width: 100%;
                    margin-top: 40px;
                }
                .footer-sign td {
                    width: 50%;
                    height: 80px;
                    vertical-align: bottom;
                }
                @media print {
                    body {
                        padding: 0;
                    }
                    .slip-card {
                        border: 2px solid #000;
                    }
                }
            </style>
        </head>
        <body>
            <div class="slip-card">
                <table class="header-table">
                    <tr>
                        <td style="width: 80px; text-align: left;">
                            ${photoHtml}
                        </td>
                        <td>
                            <h2 style="margin: 0; font-size: 18px; font-weight: bold;"><?= htmlspecialchars($company_name) ?></h2>
                            <p style="margin: 3px 0 0 0; font-size: 11px;"><?= htmlspecialchars($company_address) ?></p>
                            <h3 style="margin: 8px 0 0 0; text-decoration: underline; font-size: 14px;">ใบแจ้งยอดเงินเดือน / PAY SLIP</h3>
                        </td>
                        <td class="text-right" style="vertical-align: top;">
                            <strong>งวดเดือน:</strong> ${monthText}
                        </td>
                    </tr>
                </table>

                <table class="info-table">
                    <tr>
                        <td style="width: 15%"><strong>รหัสพนักงาน:</strong></td>
                        <td style="width: 35%">${emp.emp_code}</td>
                        <td style="width: 15%"><strong>แผนก:</strong></td>
                        <td style="width: 35%">${emp.department}</td>
                    </tr>
                    <tr>
                        <td><strong>ชื่อ-นามสกุล:</strong></td>
                        <td>${emp.first_name} ${emp.last_name}</td>
                        <td><strong>ตำแหน่ง:</strong></td>
                        <td>${emp.position}</td>
                    </tr>
                    <tr>
                        <td><strong>ประเภทค่าจ้าง:</strong></td>
                        <td>${emp.wage_type === 'daily' ? 'รายวัน' : 'รายเดือน'}</td>
                        <td><strong>อัตราค่าจ้าง:</strong></td>
                        <td>${parseFloat(emp.rate).toLocaleString()} บาท</td>
                    </tr>
                    <tr>
                        <td><strong>สถิติการทำงาน:</strong></td>
                        <td colspan="3">มาทำงาน: ${emp.present_days} วัน | ขาดงาน: ${emp.absent_days} วัน | ลางาน: ${emp.leave_days} วัน</td>
                    </tr>
                </table>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 60%">รายการเงินได้ (Earnings)</th>
                            <th style="width: 40%">จำนวนเงิน (บาท)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>เงินเดือนประจำ / ค่าจ้างขั้นต้น</td>
                            <td class="text-right">${parseFloat(emp.base_earnings).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                        </tr>
                        ${parseFloat(emp.allowance || 0) > 0 ? `
                        <tr>
                            <td>เงินเพิ่มพิเศษ (ค่าเดินทาง, ค่าน้ำมัน, ค่าอาหาร, อื่นๆ)</td>
                            <td class="text-right">+${parseFloat(emp.allowance).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                        </tr>
                        ` : ''}
                        <tr style="font-weight: bold; background-color: #f2f2f2;">
                            <td>รวมเงินได้ทั้งหมด (Total Earnings)</td>
                            <td class="text-right">${(parseFloat(emp.base_earnings) + parseFloat(emp.allowance || 0)).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                        </tr>
                    </tbody>
                </table>

                <table class="data-table" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th style="width: 60%">รายการเงินหัก (Deductions)</th>
                            <th style="width: 40%">จำนวนเงิน (บาท)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>หักลางาน/ขาดงาน (Absence deductions)</td>
                            <td class="text-right text-rose-500">${parseFloat(emp.deductions) > 0 ? '-' + parseFloat(emp.deductions).toLocaleString('th-TH', {minimumFractionDigits: 2}) : '0.00'}</td>
                        </tr>
                        <tr style="height: 60px;">
                            <td></td>
                            <td></td>
                        </tr>
                        <tr style="font-weight: bold; background-color: #f2f2f2;">
                            <td>รวมเงินหักทั้งหมด (Total Deductions)</td>
                            <td class="text-right">${parseFloat(emp.deductions).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                        </tr>
                    </tbody>
                </table>

                <table class="data-table" style="margin-top: 15px;">
                    <tr style="font-weight: bold; background-color: #e2e8f0; font-size: 13px;">
                        <td style="width: 60%; padding: 8px;">เงินได้สุทธิ (Net Payout)<br><span style="font-size: 10px; font-weight: normal; color: #555;">(${thaiBaht(emp.net_pay)})</span></td>
                        <td style="width: 40%; padding: 8px;" class="text-right">${parseFloat(emp.net_pay).toLocaleString('th-TH', {minimumFractionDigits: 2})} บาท</td>
                    </tr>
                </table>

                <table class="footer-sign">
                    <tr>
                        <td class="text-center">
                            __________________________<br><br>
                            ผู้จ่ายเงิน / Employer
                        </td>
                        <td class="text-center">
                            __________________________<br><br>
                            ผู้รับเงิน / Employee
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>`;

        printWindow.document.write(slipContent);
        printWindow.document.close();
        
        // Wait for fonts/images to load then print
        setTimeout(function() {
            printWindow.print();
        }, 500);
    }
</script>

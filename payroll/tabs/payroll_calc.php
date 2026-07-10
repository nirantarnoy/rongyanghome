<!-- Sub Tabs navigation -->
<div class="flex items-center justify-between mb-6 flex-wrap gap-4">
    <div class="flex bg-slate-200/60 p-1 rounded-xl">
        <button onclick="switchCalcSubTab('calculator')" id="subbtn-calculator" class="px-4 py-2 text-sm font-bold rounded-lg transition-all text-slate-700 bg-white shadow-sm">
            <i class="fa-solid fa-calculator mr-1"></i> คำนวณเงินเดือนรอบปัจจุบัน
        </button>
        <button onclick="switchCalcSubTab('history')" id="subbtn-history" class="px-4 py-2 text-sm font-bold rounded-lg transition-all text-slate-500 hover:text-slate-700 ml-1">
            <i class="fa-solid fa-history mr-1"></i> ประวัติรอบบัญชีและสรุปยอดจ่าย
        </button>
    </div>

    <!-- Date selector (Only shows when on calculator tab) -->
    <div id="calcPeriodSelector" class="flex items-center gap-4 flex-wrap">
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-slate-500 uppercase">เลือกงวดเงินเดือน:</span>
            <input type="month" id="payroll_month_input" value="<?= date('Y-m') ?>" onchange="onMonthChange()"
                   class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm outline-none font-bold text-slate-700 transition-all">
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-slate-500 uppercase">จากวันที่:</span>
            <input type="date" id="payroll_start_date" onchange="loadPayrollCalculation()"
                   class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm outline-none font-bold text-slate-700 transition-all">
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-slate-500 uppercase">ถึงวันที่:</span>
            <input type="date" id="payroll_end_date" onchange="loadPayrollCalculation()"
                   class="px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm outline-none font-bold text-slate-700 transition-all">
        </div>
    </div>
</div>

<!-- Cycle selector buttons -->
<h4 class="text-lg font-bold text-slate-800 mb-4 hidden" id="cycle-header-text">เลือกงวดการจ่ายเงิน</h4>
<div id="calcCycleSelector" class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-6 hidden">
    <div class="md:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <button onclick="selectCycle(1)" id="btn-cycle-1" class="p-4 rounded-xl border border-blue-600 bg-blue-600 text-white transition-all text-center shadow-md shadow-blue-500/20">
            <h5 class="font-bold">งวดที่ 1 (1-10)</h5>
            <div class="text-xs text-blue-100 mt-1" id="cycle-1-dates">1 - 10 มิถุนายน 2569</div>
        </button>
        <button onclick="selectCycle(2)" id="btn-cycle-2" class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition-all text-center">
            <h5 class="font-bold text-slate-800">งวดที่ 2 (11-20)</h5>
            <div class="text-xs text-slate-500 mt-1" id="cycle-2-dates">11 - 20 มิถุนายน 2569</div>
        </button>
        <button onclick="selectCycle(3)" id="btn-cycle-3" class="p-4 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition-all text-center">
            <h5 class="font-bold text-slate-800">งวดสิ้นเดือน (21-30)</h5>
            <div class="text-xs text-slate-500 mt-1" id="cycle-3-dates">21 - 30 มิถุนายน 2569 <span class="block mt-0.5 text-[10px] opacity-70">(หักเงินกู้/ยืม เฉพาะรอบสิ้นเดือน)</span></div>
        </button>
    </div>
    
    <!-- Debt Alert Widget -->
    <div class="md:col-span-4 bg-orange-50 border border-orange-100 rounded-xl p-4 shadow-sm">
        <div class="flex items-center gap-2 mb-3">
            <div class="bg-orange-400 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"><i class="fa-solid fa-exclamation"></i></div>
            <span class="font-bold text-slate-800">แจ้งเตือน</span>
        </div>
        <div class="space-y-2 text-sm text-slate-600">
            <div class="flex justify-between items-center">
                <span class="flex items-center gap-2"><span class="text-orange-400 text-xl leading-none">•</span> พนักงานมียอดหนี้คงเหลือ</span>
                <span class="font-bold text-slate-800" id="alert-debt-employees">0 คน</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center gap-2"><span class="text-orange-400 text-xl leading-none">•</span> ยอดหนี้คงเหลือรวม</span>
                <span class="font-bold text-rose-600" id="alert-debt-total">0.00 บาท</span>
            </div>
            <div class="flex items-center gap-2 mt-1 text-xs">
                <span class="text-orange-400 text-xl leading-none">•</span> จะถูกหักในรอบสิ้นเดือน
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- VIEW: CALCULATOR (MAIN) -->
<!-- ========================================== -->
<div id="calc-view-calculator" class="space-y-6">
    <!-- Dynamic Summary Panel -->
    <div id="cycleSummaryPanel" class="hidden mb-6 mt-4">
        <!-- Rendered via JS based on selected cycle -->
    </div>

    <!-- Details Table Wrapper -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Toolbar -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4 bg-white">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <label class="text-xs text-slate-500 absolute -top-2 left-2 bg-white px-1">เลือกแผนก</label>
                    <select class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-700 outline-none focus:border-blue-500 min-w-[150px]">
                        <option value="">ทั้งหมด</option>
                    </select>
                </div>
                <button onclick="loadPayrollCalculation(true)" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold rounded-lg text-sm transition-all flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-arrows-rotate"></i> รีเฟรชข้อมูล
                </button>
                <button onclick="Swal.fire('แจ้งเตือน', 'ฟังก์ชันพิมพ์รายงานสรุปกำลังอยู่ระหว่างการพัฒนา', 'info')" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold rounded-lg text-sm transition-all flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-print"></i> พิมพ์รายงานสรุป
                </button>
            </div>

            <div class="flex gap-2">
                <button onclick="savePayrollRun('approved')" id="btn-save-approve" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-sm transition-all shadow-md shadow-blue-500/20 flex items-center gap-2">
                    <i class="fa-solid fa-money-check-dollar"></i> <span id="btn-pay-text">จ่ายงวดที่ 1 (1-10)</span>
                </button>
                <button onclick="Swal.fire('แจ้งเตือน', 'ฟังก์ชันดูยอดหนี้สิ้นเดือนกำลังอยู่ระหว่างการพัฒนา', 'info')" class="px-4 py-2 bg-white border border-blue-200 text-blue-600 hover:bg-blue-50 font-bold rounded-lg text-sm transition-all flex items-center gap-2 shadow-sm">
                    <i class="fa-regular fa-eye"></i> ดูยอดหนี้สิ้นเดือน
                </button>
                <button onclick="printAllSlips()" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold rounded-lg text-sm transition-all flex items-center gap-2 shadow-sm" id="btn-print-slip">
                    <i class="fa-solid fa-print"></i> พิมพ์สลิปรอบ 1-10
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead id="payrollTableHead">
                    <!-- Dynamic Headers based on cycle -->
                </thead>
                <tbody id="payrollTableBody" class="divide-y divide-slate-100">
                    <!-- Dynamically populated -->
                </tbody>
                <tfoot id="payrollTableFoot" class="bg-slate-50 border-t border-slate-200 hidden">
                    <!-- Totals row -->
                </tfoot>
            </table>
        </div>
    </div>
    
    <!-- Info Footer -->
    <div class="bg-blue-50 text-blue-700 p-4 rounded-xl text-sm flex items-start gap-3 mt-4 border border-blue-100 hidden" id="cycle-info-footer">
        <i class="fa-solid fa-circle-info mt-0.5"></i>
        <span><b id="footer-note-title">หมายเหตุ:</b> <span id="footer-note-text">รอบที่ 1 (1-10) จะยังไม่หักเงินกู้/ยืม ระบบจะแสดงยอดหนี้คงเหลือเพื่อให้ทราบเท่านั้น เมื่อถึงรอบสิ้นเดือน (21-30) ระบบจะทำการหักเงินกู้/ยืมให้อัตโนมัติ</span></span>
    </div>
</div>

<!-- ========================================== -->
<!-- VIEW: HISTORY & REPORTS -->
<!-- ========================================== -->
<div id="calc-view-history" class="space-y-6 hidden">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h4 class="text-sm font-bold text-slate-700 uppercase flex items-center gap-1.5">
                <i class="fa-solid fa-chart-pie text-blue-500"></i> ประวัติรอบบัญชีเงินเดือนและยอดจ่ายสะสม
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ประจำงวด/เดือน</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">จำนวนพนักงาน</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ยอดเงินจ่ายสุทธิทั้งหมด</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">สถานะรอบบัญชี</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">วันที่ปรับปรุงล่าสุด</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider text-right">การจัดการ</th>
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
    let selectedCycle = 1;

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
            $('#calcCycleSelector').removeClass('hidden');
            selectCycle(1);
        } else {
            $('#calcPeriodSelector').addClass('hidden');
            $('#calcCycleSelector').addClass('hidden');
            loadPayrollHistory();
        }
    }

    function loadPayrollCalc() {
        switchCalcSubTab('calculator');
    }

    function renderCycleTableHeaders() {
        let cycleText = selectedCycle === 1 ? '1 (1-10)' : (selectedCycle === 2 ? '2 (11-20)' : 'สิ้นเดือน (21-30)');
        let html = `
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-600">
                <th class="px-4 py-3 text-sm font-semibold uppercase tracking-wider text-center w-12 border-r border-slate-100">
                    <input type="checkbox" id="selectAllEmployees" checked onchange="toggleSelectAllEmployees(this)"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer">
                </th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">รหัส<br>พนักงาน</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">ชื่อ-นามสกุล<br><span class="text-[10px] font-normal text-slate-500">ตำแหน่ง</span></th>
        `;

        if (selectedCycle === 1 || selectedCycle === 2) {
            html += `
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">เงินได้งวดนี้ ${selectedCycle === 1 ? '(1-10)' : '(11-20)'}</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">เงินเพิ่ม</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">หักทั่วไป<br><span class="text-[10px] font-normal text-slate-500">(ขาดงาน, ลา, ปรับ)</span></th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">หนี้คงเหลือ<br><span class="text-[10px] font-normal text-slate-500">(รอหักสิ้นเดือน)</span></th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">สถานะหนี้</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">จ่ายจริง<br>รอบนี้</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center">จัดการ</th>
            `;
        } else {
            html += `
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">รายได้รอบนี้</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">เงินเพิ่ม</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">เงินหักทั่วไป</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100 text-rose-600">เงินกู้/ยืม<br><span class="text-[10px] font-normal">(หักรอบนี้)</span></th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100 font-bold text-blue-600">จ่ายสุทธิรอบนี้</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">ยอดหนี้คงเหลือ</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center border-r border-slate-100">สถานะ</th>
                <th class="px-4 py-3 text-[13px] font-bold tracking-wider text-center">จัดการ</th>
            `;
        }
        html += '</tr>';
        $('#payrollTableHead').html(html);
    }

    function selectCycle(cycle) {
        selectedCycle = cycle;
        const monthPeriod = $('#payroll_month_input').val();
        if (!monthPeriod) return;

        // Visual update for buttons
        $('#btn-cycle-1, #btn-cycle-2, #btn-cycle-3').removeClass('border-blue-600 bg-blue-600 text-white shadow-md shadow-blue-500/20').addClass('border-slate-200 bg-slate-50 text-slate-800 hover:bg-slate-100');
        
        $(`#btn-cycle-${cycle}`).addClass('border-blue-600 bg-blue-600 text-white shadow-md shadow-blue-500/20').removeClass('border-slate-200 bg-slate-50 text-slate-800 hover:bg-slate-100');
        $(`#btn-cycle-${cycle} h5`).removeClass('text-slate-800').addClass('text-white');
        $(`#btn-cycle-${cycle} div`).removeClass('text-slate-500 text-rose-500').addClass('text-blue-100');
        
        // Reset others to normal
        [1,2,3].forEach(c => {
            if(c !== cycle) {
                $(`#btn-cycle-${c} h5`).removeClass('text-white').addClass('text-slate-800');
                $(`#btn-cycle-${c} div`).removeClass('text-blue-100').addClass('text-slate-500');
                if (c === 3) $(`#btn-cycle-3 div span`).removeClass('text-blue-200').addClass('text-slate-500 opacity-70');
            }
        });

        if (cycle === 3) $(`#btn-cycle-3 div span`).removeClass('text-slate-500 opacity-70').addClass('text-blue-100');

        let cycleText = cycle === 1 ? '1 (1-10)' : (cycle === 2 ? '2 (11-20)' : 'สิ้นเดือน (21-30)');
        $('#btn-pay-text').text(`จ่ายงวดที่ ${cycleText}`);
        $('#btn-print-slip').html(`<i class="fa-solid fa-print"></i> พิมพ์สลิปรอบ ${cycleText}`);

        $('#cycle-info-footer').removeClass('hidden');
        if (cycle === 3) {
            $('#footer-note-text').text('รอบสิ้นเดือน (21-30) ระบบจะทำการหักเงินกู้/ยืมให้อัตโนมัติตามยอดที่ได้กำหนดไว้');
            $('#cycle-info-footer').removeClass('bg-blue-50 text-blue-700 border-blue-100').addClass('bg-rose-50 text-rose-700 border-rose-100');
        } else {
            $('#footer-note-text').text(`รอบที่ ${cycleText} จะยังไม่หักเงินกู้/ยืม ระบบจะแสดงยอดหนี้คงเหลือเพื่อให้ทราบเท่านั้น เมื่อถึงรอบสิ้นเดือน (21-30) ระบบจะทำการหักเงินกู้/ยืมให้อัตโนมัติ`);
            $('#cycle-info-footer').removeClass('bg-rose-50 text-rose-700 border-rose-100').addClass('bg-blue-50 text-blue-700 border-blue-100');
        }

        const parts = monthPeriod.split('-');
        const year = parseInt(parts[0]);
        const month = parseInt(parts[1]);
        const lastDay = new Date(year, month, 0).getDate();

        if (cycle === 1) {
            $('#payroll_start_date').val(`${year}-${String(month).padStart(2, '0')}-01`);
            $('#payroll_end_date').val(`${year}-${String(month).padStart(2, '0')}-10`);
        } else if (cycle === 2) {
            $('#payroll_start_date').val(`${year}-${String(month).padStart(2, '0')}-11`);
            $('#payroll_end_date').val(`${year}-${String(month).padStart(2, '0')}-20`);
        } else {
            $('#payroll_start_date').val(`${year}-${String(month).padStart(2, '0')}-21`);
            $('#payroll_end_date').val(`${year}-${String(month).padStart(2, '0')}-${lastDay}`);
        }

        loadPayrollCalculation(true);
    }

    function loadPayrollCalculation(recalculate = false) {
        const monthPeriod = $('#payroll_month_input').val();
        const startDate = $('#payroll_start_date').val();
        const endDate = $('#payroll_end_date').val();
        if (!monthPeriod || !startDate || !endDate) return;

        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { 
                action: 'get_payroll_run', 
                month_period: monthPeriod,
                start_date: startDate,
                end_date: endDate,
                recalculate: recalculate ? 'true' : 'false',
                cycle: selectedCycle
            },
            success: function(res) {
                if (res.status === 'error') {
                    Swal.fire('ข้อผิดพลาด', res.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
                    $('#payrollTableBody').html('<tr><td colspan="10" class="text-center text-red-500 py-8">' + (res.message || 'Error') + '</td></tr>');
                    return;
                }
                computedDetails = res.details || [];
                currentPayrollRun = res;
                renderCycleTableHeaders();

                if (res.status === 'saved') {
                    $('#payroll_start_date').val(res.start_date);
                    $('#payroll_end_date').val(res.end_date);
                }

                // Toggle action buttons based on status
                const isLocked = (res.status === 'saved' && res.run_status === 'approved');
                if (isLocked) {
                    $('#btn-recalculate').addClass('hidden');
                    $('#btn-save-draft').addClass('hidden');
                    $('#btn-save-approve').addClass('hidden');
                } else {
                    $('#btn-recalculate').removeClass('hidden');
                    $('#btn-save-draft').removeClass('hidden');
                    $('#btn-save-approve').removeClass('hidden');
                }

                // Render badge status
                let badge = '';
                if (res.status === 'saved') {
                    if (res.run_status === 'approved') {
                        badge = `<span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 text-sm font-bold rounded-full border border-emerald-200"><i class="fa-solid fa-circle-check mr-0.5"></i> อนุมัติแล้ว</span>`;
                    } else {
                        badge = `<span class="px-2.5 py-1 bg-amber-50 text-amber-600 text-sm font-bold rounded-full border border-amber-200"><i class="fa-solid fa-clock mr-0.5"></i> แบบร่าง</span>`;
                    }
                } else {
                    badge = `<span class="px-2.5 py-1 bg-slate-100 text-slate-500 text-sm font-bold rounded-full border border-slate-200">ยังไม่บันทึก (คำนวณสด)</span>`;
                }
                $('#payroll-run-status-badge').html(badge);

                // Render details table
                let html = '';
                let totalEmp = computedDetails.length;

                if (totalEmp > 0) {
                    const disabledAttr = isLocked ? 'disabled' : '';
                    computedDetails.forEach(function(row) {
                        let avatarHtml = '';
                        if (row.photo) {
                            avatarHtml = `<img src="../${row.photo}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0">`;
                        } else {
                            let initials = (row.first_name ? row.first_name.charAt(0) : '') + (row.last_name ? row.last_name.charAt(0) : '');
                            avatarHtml = `<div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 font-bold text-sm flex items-center justify-center border border-blue-100 shadow-sm flex-shrink-0">${initials}</div>`;
                        }

                        let typeBadge = row.wage_type === 'daily'
                            ? `<span class="px-1.5 py-0.5 bg-amber-50 text-amber-600 text-xs font-bold rounded border border-amber-100">รายวัน: ${parseFloat(row.rate).toLocaleString()}</span>`
                            : `<span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 text-xs font-bold rounded border border-blue-100">รายเดือน: ${parseFloat(row.rate).toLocaleString()}</span>`;


                        let cols = '';
                        if (selectedCycle === 1 || selectedCycle === 2) {
                            cols = `
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-slate-800 border-r border-slate-100">${parseFloat(row.base_earnings).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-emerald-500 border-r border-slate-100">${parseFloat(row.allowance || 0).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-rose-500 border-r border-slate-100">${parseFloat(row.deductions).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-slate-800 border-r border-slate-100">${parseFloat(row.remaining_debt || 0).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center border-r border-slate-100">${parseFloat(row.remaining_debt || 0) > 0 ? '<span class="text-amber-500 bg-amber-50 px-2 py-1 rounded text-xs font-bold">รอหักสิ้นเดือน</span>' : '<span class="text-emerald-500 bg-emerald-50 px-2 py-1 rounded text-[11px] font-bold">ไม่มีหนี้</span>'}</td>
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-blue-600 border-r border-slate-100">${parseFloat(row.net_pay).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center">
                                    <button onclick="viewEmployeeDetails(${row.employee_id})" class="inline-flex items-center px-3 py-1.5 border border-slate-200 hover:bg-slate-50 text-blue-600 rounded-lg transition-all text-xs font-bold gap-1 shadow-sm">
                                        <i class="fa-regular fa-eye"></i> ดูรายละเอียด
                                    </button>
                                </td>
                            `;
                        } else {
                            cols = `
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-slate-800 border-r border-slate-100">${parseFloat(row.base_earnings).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-emerald-500 border-r border-slate-100">${parseFloat(row.allowance || 0).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-rose-500 border-r border-slate-100">${parseFloat(row.deductions).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-rose-600 border-r border-slate-100">${parseFloat(row.loan_deduction || 0).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-blue-600 border-r border-slate-100">${parseFloat(row.net_pay).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center font-bold text-slate-800 border-r border-slate-100">${parseFloat(row.remaining_debt || 0).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-3 text-[13px] text-center border-r border-slate-100">
                                    ${parseFloat(row.remaining_debt) > 0 ? '<span class="text-amber-500 bg-amber-50 px-2 py-1 rounded text-[11px] font-bold">ผ่อนต่อ</span>' : '<span class="text-emerald-500 bg-emerald-50 px-2 py-1 rounded text-[11px] font-bold">ไม่มีหนี้</span>'}
                                </td>
                                <td class="px-4 py-3 text-[13px] text-center">
                                    <button onclick="viewEmployeeDetails(${row.employee_id})" class="inline-flex items-center px-3 py-1.5 border border-slate-200 hover:bg-slate-50 text-blue-600 rounded-lg transition-all text-xs font-bold gap-1 shadow-sm">
                                        <i class="fa-regular fa-eye"></i> ดูรายละเอียด
                                    </button>
                                </td>
                            `;
                        }

                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors border-b border-slate-100 last:border-0">
                            <td class="px-4 py-3 text-center border-r border-slate-100">
                                <input type="checkbox" class="employee-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer" 
                                       data-emp-id="${row.employee_id}" checked ${disabledAttr} onchange="onEmployeeCheckboxChange()">
                            </td>
                            <td class="px-4 py-3 text-[13px] font-bold text-slate-800 text-center border-r border-slate-100">${row.emp_code}</td>
                            <td class="px-4 py-3 text-[13px] font-semibold text-slate-700 border-r border-slate-100 min-w-[200px]">
                                <div class="flex items-center gap-3">
                                    ${avatarHtml}
                                    <div class="text-left">
                                        <span class="font-bold text-slate-800">${row.first_name} ${row.last_name}</span>
                                        <div class="text-[11px] text-slate-500 mt-0.5">${row.position} ${row.department ? '| ' + row.department : ''}</div>
                                    </div>
                                </div>
                            </td>
                            ${cols}
                        </tr>`;
                    });
                } else {
                    html = `
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-users-slash text-3xl mb-2 opacity-30"></i>
                                <div>ไม่พบข้อมูลพนักงานในระบบสำหรับงวดนี้</div>
                            </div>
                        </td>
                    </tr>`;
                }

                $('#payrollTableBody').html(html);

                // Set selectAll Employees check state
                $('#selectAllEmployees').prop('checked', true).prop('disabled', isLocked);
                
                // Calculate and update the metrics
                updatePayrollSummaryAndPayload();
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error in get_payroll_run:", status, error, xhr.responseText);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์หรือรูปแบบข้อมูลไม่ถูกต้อง. โปรดตรวจสอบการตั้งค่า.', 'error');
            }
        });
    }

    function savePayrollRun(status) {
        const monthPeriod = $('#payroll_month_input').val();
        const startDate = $('#payroll_start_date').val();
        const endDate = $('#payroll_end_date').val();
        const filteredDetails = getFilteredPayrollDetails();

        if (!monthPeriod || !startDate || !endDate || filteredDetails.length === 0) {
            Swal.fire('ข้อผิดพลาด', 'ไม่มีข้อมูลเงินเดือนที่เลือกเพื่อบันทึก', 'warning');
            return;
        }

        Swal.fire({
            title: status === 'approved' ? 'อนุมัติรอบจ่ายเงินเดือน?' : 'บันทึกแบบร่าง?',
            text: status === 'approved' ? 'การอนุมัติเงินเดือนจะล็อคระบบข้อมูลจ่ายในงวดนี้' : 'คุณต้องการบันทึกการคำนวณนี้เป็นแบบร่างหรือไม่',
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
                        start_date: startDate,
                        end_date: endDate,
                        cycle: selectedCycle,
                        status: status,
                        details: JSON.stringify(filteredDetails)
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
                            ? `<span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 text-sm font-bold rounded-full border border-emerald-200"><i class="fa-solid fa-circle-check mr-0.5"></i> อนุมัติแล้ว</span>`
                            : `<span class="px-2.5 py-1 bg-amber-50 text-amber-600 text-sm font-bold rounded-full border border-amber-200"><i class="fa-solid fa-clock mr-0.5"></i> แบบร่าง</span>`;

                        // format thai month
                        const parts = row.month_period.split('-');
                        const years = parseInt(parts[0]) + 543;
                        const months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                        const monthText = months[parseInt(parts[1]) - 1] + ' ' + years;

                        let periodText = monthText;
                        if (row.start_date && row.end_date) {
                            periodText += `<br><span class="text-xs text-slate-400 font-normal">(${formatThaiDate(row.start_date)} - ${formatThaiDate(row.end_date)})</span>`;
                        }

                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-bold text-slate-800">${periodText}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${row.total_employees} คน</td>
                            <td class="px-6 py-4 text-sm text-slate-700 font-bold text-blue-600">${parseFloat(row.total_net_pay).toLocaleString('th-TH', {minimumFractionDigits: 2})} บาท</td>
                            <td class="px-6 py-4">${badge}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">${formatThaiDate(row.updated_at)}</td>
                            <td class="px-6 py-4 text-sm text-right space-x-2">
                                <button onclick="viewHistoryMonth('${row.month_period}', '${row.start_date || ''}', '${row.end_date || ''}')" class="px-2.5 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition-all font-bold text-sm" title="เรียกดูรายละเอียด">
                                    <i class="fa-solid fa-eye"></i> ตรวจสอบ
                                </button>
                                <button onclick="deletePayrollPeriod(${row.id})" class="px-2.5 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-lg transition-all font-bold text-sm" title="ลบรอบบัญชีนี้">
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

    function viewHistoryMonth(month, start_date = '', end_date = '') {
        $('#payroll_month_input').val(month);
        if (start_date && end_date) {
            $('#payroll_start_date').val(start_date);
            $('#payroll_end_date').val(end_date);
        } else {
            onMonthChange();
        }
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

    // Prints all pay slips for selected employees
    function printAllSlips() {
        const selectedEmpIds = [];
        $('.employee-checkbox:checked').each(function() {
            selectedEmpIds.push($(this).data('emp-id'));
        });
        
        if (selectedEmpIds.length === 0) {
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกพนักงานที่ต้องการพิมพ์สลิปเงินเดือน', 'warning');
            return;
        }
        
        // Loop through each and print (in a real app, this should generate a single PDF or printable page with multiple slips)
        // For now, we'll just show an info message if there are many, or print them
        if (selectedEmpIds.length > 5) {
            Swal.fire('แจ้งเตือน', 'ฟังก์ชันพิมพ์สลิปแบบกลุ่ม (หลายคนพร้อมกัน) กำลังอยู่ระหว่างการพัฒนา', 'info');
        } else {
            selectedEmpIds.forEach(id => {
                printPaySlip(id);
            });
        }
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
                        ${parseFloat(emp.loan_deduction || 0) > 0 ? `
                        <tr>
                            <td>หักชำระเงินกู้ / เงินยืม (Loan/Borrow payment)</td>
                            <td class="text-right text-rose-500">-${parseFloat(emp.loan_deduction).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                        </tr>
                        ` : ''}
                        <tr style="height: 60px;">
                            <td></td>
                            <td></td>
                        </tr>
                        <tr style="font-weight: bold; background-color: #f2f2f2;">
                            <td>รวมเงินหักทั้งหมด (Total Deductions)</td>
                            <td class="text-right">${(parseFloat(emp.deductions) + parseFloat(emp.loan_deduction || 0)).toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
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

    function toggleSelectAllEmployees(masterCheckbox) {
        $('.employee-checkbox').prop('checked', masterCheckbox.checked);
        updatePayrollSummaryAndPayload();
    }

    function onEmployeeCheckboxChange() {
        const allChecked = $('.employee-checkbox:checked').length === $('.employee-checkbox').length;
        $('#selectAllEmployees').prop('checked', allChecked);
        updatePayrollSummaryAndPayload();
    }

    function updatePayrollSummaryAndPayload() {
        let totalPayout = 0;
        let totalBase = 0;
        let totalAllowance = 0;
        let totalDeductions = 0;
        let totalLoanDeductions = 0;
        let totalRemainingDebt = 0;
        let debtEmpCount = 0;
        let selectedEmpCount = 0;
        
        $('.employee-checkbox').each(function() {
            const empId = $(this).attr('data-emp-id');
            const isChecked = $(this).is(':checked');
            
            const detail = computedDetails.find(d => d.employee_id == empId);
            if (detail) {
                if (isChecked) {
                    totalPayout += parseFloat(detail.net_pay);
                    totalBase += parseFloat(detail.base_earnings);
                    totalAllowance += parseFloat(detail.allowance || 0);
                    totalDeductions += parseFloat(detail.deductions || 0);
                    totalLoanDeductions += parseFloat(detail.loan_deduction || 0);
                    if (parseFloat(detail.remaining_debt || 0) > 0) {
                        totalRemainingDebt += parseFloat(detail.remaining_debt);
                        debtEmpCount++;
                    }
                    selectedEmpCount++;
                }
            }
        });

        $('#sum-total-employees').text(selectedEmpCount + ' คน');
        $('#alert-debt-employees').text(debtEmpCount + ' คน');
        $('#alert-debt-total').text(totalRemainingDebt.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');
        
        let cycleLabel = selectedCycle === 1 ? '(1-10)' : (selectedCycle === 2 ? '(11-20)' : 'สิ้นเดือน');
        let html = `
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-start gap-4">
                    <div class="bg-blue-50 text-blue-500 w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-wallet text-lg"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-bold mb-1 block">เงินได้รวมงวดนี้ ${cycleLabel}</span>
                        <h3 class="text-xl font-bold text-slate-800 leading-none">${totalBase.toLocaleString('th-TH', {minimumFractionDigits: 2})}</h3>
                        <div class="text-[10px] text-slate-400 mt-1 font-bold">บาท</div>
                    </div>
                </div>
                
                <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-start gap-4">
                    <div class="bg-emerald-50 text-emerald-500 w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-money-bill-trend-up text-lg"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-bold mb-1 block">เงินเพิ่ม</span>
                        <h3 class="text-xl font-bold text-slate-800 leading-none">${totalAllowance.toLocaleString('th-TH', {minimumFractionDigits: 2})}</h3>
                        <div class="text-[10px] text-slate-400 mt-1 font-bold">บาท</div>
                    </div>
                </div>
                
                <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-start gap-4">
                    <div class="bg-rose-50 text-rose-500 w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-file-invoice-dollar text-lg"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-bold mb-1 block">เงินหักทั่วไป</span>
                        <h3 class="text-xl font-bold text-slate-800 leading-none">${totalDeductions.toLocaleString('th-TH', {minimumFractionDigits: 2})}</h3>
                        <div class="text-[10px] text-slate-400 mt-1 font-bold">บาท</div>
                    </div>
                </div>
                
                <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-start gap-4 relative">
                    <div class="bg-purple-50 text-purple-500 w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-file-contract text-lg"></i>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 font-bold mb-1 flex items-center gap-1">หนี้คงเหลือพนักงาน <i class="fa-regular fa-circle-question text-[10px] text-slate-400 cursor-pointer" title="ยอดหนี้ที่จะรอหักในรอบสิ้นเดือน"></i></span>
                        <h3 class="text-xl font-bold text-slate-800 leading-none">${totalRemainingDebt.toLocaleString('th-TH', {minimumFractionDigits: 2})}</h3>
                        <div class="text-[10px] text-slate-400 mt-1 font-bold">บาท</div>
                    </div>
                </div>
                
                <div class="bg-blue-600 rounded-2xl p-4 shadow-md shadow-blue-500/20 flex items-start gap-4">
                    <div class="bg-white/20 text-white w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-hand-holding-dollar text-lg"></i>
                    </div>
                    <div>
                        <span class="text-xs text-blue-100 font-bold mb-1 block">ยอดจ่ายสุทธิรอบนี้</span>
                        <h3 class="text-xl font-bold text-white leading-none">${totalPayout.toLocaleString('th-TH', {minimumFractionDigits: 2})}</h3>
                        <div class="text-[10px] text-blue-200 mt-1 font-bold">บาท</div>
                    </div>
                </div>
            </div>`;

        $('#cycleSummaryPanel').html(html).removeClass('hidden');

        // Update table footer
        let tfootHtml = '';
        if (selectedCycle === 1 || selectedCycle === 2) {
            tfootHtml = `
            <tr>
                <td colspan="3" class="px-4 py-3 text-right font-bold text-slate-800 border-r border-slate-100">รวมทั้งสิ้น</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-slate-800 border-r border-slate-100">${totalBase.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-emerald-500 border-r border-slate-100">${totalAllowance > 0 ? '+' : ''}${totalAllowance.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-rose-500 border-r border-slate-100">${totalDeductions.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-slate-800 border-r border-slate-100">${totalRemainingDebt.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-center border-r border-slate-100"></td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-blue-600 border-r border-slate-100">${totalPayout.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-center"></td>
            </tr>`;
        } else {
            tfootHtml = `
            <tr>
                <td colspan="3" class="px-4 py-3 text-right font-bold text-slate-800 border-r border-slate-100">รวมทั้งสิ้น</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-slate-800 border-r border-slate-100">${totalBase.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-emerald-500 border-r border-slate-100">${totalAllowance > 0 ? '+' : ''}${totalAllowance.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-rose-500 border-r border-slate-100">${totalDeductions.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-rose-600 border-r border-slate-100">${totalLoanDeductions.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-blue-600 border-r border-slate-100">${totalPayout.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-[13px] text-center font-bold text-slate-800 border-r border-slate-100">${totalRemainingDebt.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                <td class="px-4 py-3 text-center border-r border-slate-100"></td>
                <td class="px-4 py-3 text-center"></td>
            </tr>`;
        }
        if (selectedEmpCount > 0) {
            $('#payrollTableFoot').html(tfootHtml).removeClass('hidden');
        } else {
            $('#payrollTableFoot').addClass('hidden');
        }

        // Optional: keep totalPayout updated in the top widgets if needed
        $('#sum-total-payout').text(totalPayout.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');
    }

    function getFilteredPayrollDetails() {
        let filtered = [];
        $('.employee-checkbox:checked').each(function() {
            const empId = $(this).attr('data-emp-id');
            const detail = computedDetails.find(d => d.employee_id == empId);
            if (detail) {
                filtered.push(detail);
            }
        });
        return filtered;
    }

    function onMonthChange() {
        const monthVal = $('#payroll_month_input').val();
        if (monthVal) {
            const parts = monthVal.split('-');
            const year = parseInt(parts[0]);
            const month = parseInt(parts[1]);
            
            const firstDay = `${year}-${String(month).padStart(2, '0')}-01`;
            const lastDayDate = new Date(year, month, 0);
            const lastDay = `${year}-${String(month).padStart(2, '0')}-${String(lastDayDate.getDate()).padStart(2, '0')}`;
            
            $('#payroll_start_date').val(firstDay);
            $('#payroll_end_date').val(lastDay);
        }
        loadPayrollCalculation();
    }

    $(document).ready(function() {
        if ($('#payroll_month_input').length > 0 && !$('#payroll_start_date').val()) {
            onMonthChange();
        }
    });
</script>

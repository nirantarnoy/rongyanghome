<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="space-y-6 lg:col-span-1">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-6">
            <div>
                <h4 class="font-bold text-slate-800 text-lg">อัตราค่าคอมมิชชั่นเริ่มต้น</h4>
                <p class="text-sm text-slate-400 mt-0.5">ระบุอัตราเปอร์เซ็นต์ค่าคอมมิชชั่นเริ่มต้นสำหรับพนักงานเปิดการขายและผู้ช่วย</p>
            </div>
            
            <form id="commissionSettingsForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">แอดมินรับลูกค้า/ช่วยตอบ</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" max="100" name="admin_rate" id="comm_admin_rate" required
                               class="w-full pl-4 pr-12 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 transition-all">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">%</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">คนปิดการขาย(ทำใบเสนอราคา/ใบสั่งขาย/ลูกค้าโอนมัดจำและโอนชำระครบ) (%)</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" max="100" name="sales_rate" id="comm_sales_rate" required
                               class="w-full pl-4 pr-12 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 transition-all">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">%</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">ผู้ช่วยติดตามงานช่าง/QCงาน/ติดต่อขนส่ง /ช่วยตอบลูกค้า</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" max="100" name="helper_rate" id="comm_helper_rate" required
                               class="w-full pl-4 pr-12 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 transition-all">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">%</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1.5">หมายเหตุ: ค่าคอมมิชชั่นผู้ช่วยจะถูกแบ่งเท่าๆ กันตามจำนวนผู้ช่วยที่ถูกเลือกในรายการขาย (สูงสุด 2 คน)</p>
                </div>

                <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm shadow-md shadow-blue-500/10 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>บันทึกตั้งค่าค่าคอมมิชชั่น</span>
                </button>
            </form>
        </div>
    </div>
    <!-- Right Panel: Dashboard -->
    <div class="space-y-6 lg:col-span-2">
        <!-- Top Info Header -->
        <div class="bg-[#8b5a2b] rounded-t-2xl p-4 flex items-center justify-between text-white shadow-sm relative overflow-hidden">
            <div class="relative z-10 flex items-center gap-3">
                <div class="bg-white/20 p-2.5 rounded-xl">
                    <i class="fa-solid fa-file-invoice-dollar text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold">ระบบคำนวณค่าคอมมิชชั่น แอดมินหลายคน (ช่วยกันตอบ)</h2>
                    <p class="text-sm text-white/80 mt-0.5">สำหรับธุรกิจเฟอร์นิเจอร์ไม้สัก (เฉพาะรายการเฟอร์นิเจอร์)</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-b-2xl p-6 shadow-sm border border-slate-100 -mt-6">
            
            <!-- Static Info Panels -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <!-- โครงสร้างค่าคอมมิชชั่น -->
                <div class="border border-[#8b5a2b]/20 rounded-xl overflow-hidden flex flex-col">
                    <div class="bg-[#8b5a2b] text-white text-center py-2 font-bold text-sm">โครงสร้างค่าคอมมิชชั่น</div>
                    <table class="w-full text-xs text-left flex-1 bg-white">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-2 py-1.5 font-semibold text-center w-8">ลำดับ</th>
                                <th class="px-2 py-1.5 font-semibold">ตำแหน่ง</th>
                                <th class="px-2 py-1.5 font-semibold text-center">อัตรา</th>
                                <th class="px-2 py-1.5 font-semibold">หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-2 py-2 text-center"><span class="w-5 h-5 rounded-full bg-emerald-500 text-white inline-flex items-center justify-center text-[10px]">1</span></td>
                                <td class="px-2 py-2 font-bold text-slate-700">แอดมินรับลูกค้า <span class="text-[10px] text-slate-400 block font-normal">(First Contact)</span></td>
                                <td class="px-2 py-2 text-center font-bold text-purple-600">1.00%</td>
                                <td class="px-2 py-2 text-[10px] text-slate-500">คนแรกที่รับลูกค้า/สร้าง Lead</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-2 text-center"><span class="w-5 h-5 rounded-full bg-blue-500 text-white inline-flex items-center justify-center text-[10px]">2</span></td>
                                <td class="px-2 py-2 font-bold text-slate-700">เซลล์ปิดการขาย <span class="text-[10px] text-slate-400 block font-normal">(Closer)</span></td>
                                <td class="px-2 py-2 text-center font-bold text-blue-600">2.00%</td>
                                <td class="px-2 py-2 text-[10px] text-slate-500">คนที่ลูกค้าโอนมัดจำ</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-2 text-center"><span class="w-5 h-5 rounded-full bg-amber-500 text-white inline-flex items-center justify-center text-[10px]">3</span></td>
                                <td class="px-2 py-2 font-bold text-slate-700">ผู้ช่วยติดตามงาน <span class="text-[10px] text-slate-400 block font-normal">/ ประสานงาน</span></td>
                                <td class="px-2 py-2 text-center font-bold text-amber-600">0.50%</td>
                                <td class="px-2 py-2 text-[10px] text-slate-500">แบ่งให้ผู้ช่วย 2 คน เท่าๆ กัน</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-[#fdf6e3]">
                            <tr>
                                <th colspan="2" class="px-2 py-2 text-right font-bold text-slate-700">รวม</th>
                                <th class="px-2 py-2 text-center font-extrabold text-[#8b5a2b]">3.50%</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- ตัวอย่างการคำนวณ -->
                <div class="border border-[#8b5a2b]/20 rounded-xl overflow-hidden flex flex-col">
                    <div class="bg-[#8b5a2b] text-white text-center py-2 font-bold text-sm">ตัวอย่างการคำนวณ <span class="text-xs font-normal opacity-90">(ยอดขาย 98,500 บาท)</span></div>
                    <div class="p-3 text-xs space-y-3 flex-1 bg-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-emerald-500 text-white inline-flex items-center justify-center text-[10px] flex-shrink-0">1</span>
                                <span class="font-bold text-slate-700">แอดมินรับลูกค้า (1%)</span>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-slate-400">98,500 x 1%</div>
                                <div class="font-bold text-purple-600">= 985 บาท</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-blue-500 text-white inline-flex items-center justify-center text-[10px] flex-shrink-0">2</span>
                                <span class="font-bold text-slate-700">เซลล์ปิดการขาย (2%)</span>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-slate-400">98,500 x 2%</div>
                                <div class="font-bold text-blue-600">= 1,970 บาท</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-amber-500 text-white inline-flex items-center justify-center text-[10px] flex-shrink-0">3</span>
                                <div>
                                    <span class="font-bold text-slate-700 block">ผู้ช่วยติดตามงาน (0.5%)</span>
                                    <span class="text-[10px] text-slate-400">แบ่ง 2 คน คนละ 0.25%</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-slate-400">98,500 x 0.25%</div>
                                <div class="font-bold text-amber-600">= 246.25 บาท/คน</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-[#8b5a2b] text-white p-2 flex justify-between items-center px-4 mt-auto">
                        <span class="font-bold text-sm">รวมค่าคอมมิชชั่นทั้งสิ้น</span>
                        <span class="font-extrabold text-sm">3,447.50 บาท</span>
                    </div>
                </div>

                <!-- กฎและเงื่อนไข -->
                <div class="border border-[#8b5a2b]/20 rounded-xl overflow-hidden bg-white flex flex-col">
                    <div class="bg-[#8b5a2b] text-white text-center py-2 font-bold text-sm">กฎและเงื่อนไข</div>
                    <ul class="p-4 text-[11px] text-slate-600 space-y-3 flex-1">
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                            <span><strong class="text-slate-800">ผู้รับลูกค้า</strong> = คนที่สร้าง Lead เข้าระบบก่อน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                            <span><strong class="text-slate-800">ผู้ปิดการขาย</strong> = คนที่ลูกค้าโอนมัดจำ หรือเซ็นสัญญาตกลง</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                            <span>หากผู้รับลูกค้าและผู้ปิดการขายเป็นคนเดียวกันจะได้ <strong>3.00%</strong></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                            <span>ผู้ช่วยปิดงานสามารถใส่ชื่อพนักงานในระบบ (มีผู้ช่วยได้สูงสุด 2 คน)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                            <span>ยอดคอมจ่ายเมื่อได้รับเงินมัดจำหรือยอดชำระตามที่บริษัทกำหนด</span>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Filter Month -->
            <div class="flex flex-wrap gap-4 justify-between items-center mb-6 border-b border-slate-100 pb-4">
                <div class="flex gap-2">
                    <button type="button" onclick="switchDashTab('transactions')" id="dash_tab_transactions" class="px-4 py-2 font-bold text-sm rounded-xl bg-blue-600 text-white shadow-sm transition-all">
                        1. บันทึกรายการขาย
                    </button>
                    <button type="button" onclick="switchDashTab('summary')" id="dash_tab_summary" class="px-4 py-2 font-bold text-sm rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition-all">
                        2. สรุปรายบุคคล & ภาพรวม
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-slate-500">ประจำเดือน:</span>
                    <input type="month" id="dash_comm_month" value="<?= date('Y-m') ?>" onchange="loadCommissionDashboard()" 
                           class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-slate-700 text-sm outline-none">
                </div>
            </div>

            <!-- Tab 1: Transactions -->
            <div id="dash_view_transactions">
                <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-800 text-white">
                                <th class="px-4 py-3 text-xs font-bold uppercase">วันที่</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase">สินค้ารายการขาย</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase text-right">ยอดขาย (บาท)</th>
                                <th class="px-4 py-3 text-xs font-bold text-purple-200 uppercase text-center">แอดมิน</th>
                                <th class="px-4 py-3 text-xs font-bold text-blue-200 uppercase text-center">คนปิดการขาย</th>
                                <th class="px-4 py-3 text-xs font-bold text-amber-200 uppercase text-center">ผู้ช่วย 1</th>
                                <th class="px-4 py-3 text-xs font-bold text-amber-200 uppercase text-center">ผู้ช่วย 2</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase text-center">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="dash_transactions_body" class="divide-y divide-slate-100">
                            <!-- JS injected -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Summary -->
            <div id="dash_view_summary" class="hidden">
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <!-- Left: Employee Summary -->
                    <div class="xl:col-span-2">
                        <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <thead>
                                    <tr class="bg-slate-800 text-white">
                                        <th class="px-4 py-3 text-xs font-bold uppercase">ชื่อพนักงาน</th>
                                        <th class="px-4 py-3 text-xs font-bold text-purple-200 uppercase text-right">แอดมิน</th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-200 uppercase text-right">คนปิดการขาย</th>
                                        <th class="px-4 py-3 text-xs font-bold text-amber-200 uppercase text-right">ผู้ช่วย</th>
                                        <th class="px-4 py-3 text-xs font-bold text-emerald-200 uppercase text-right">รวมค่าคอม</th>
                                        <th class="px-4 py-3 text-xs font-bold text-emerald-200 uppercase text-right">สัดส่วน</th>
                                    </tr>
                                </thead>
                                <tbody id="dash_employee_body" class="divide-y divide-slate-100">
                                    <!-- JS injected -->
                                </tbody>
                                <tfoot class="bg-slate-50 border-t border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-sm font-bold text-slate-700 text-right">รวมทั้งสิ้น:</th>
                                        <th class="px-4 py-3 text-sm font-extrabold text-purple-600 text-right" id="dash_sum_admin">0.00</th>
                                        <th class="px-4 py-3 text-sm font-extrabold text-blue-600 text-right" id="dash_sum_sales">0.00</th>
                                        <th class="px-4 py-3 text-sm font-extrabold text-amber-600 text-right" id="dash_sum_helper">0.00</th>
                                        <th class="px-4 py-3 text-sm font-extrabold text-emerald-600 text-right" id="dash_sum_total">0.00</th>
                                        <th class="px-4 py-3 text-sm font-bold text-slate-500 text-right">100%</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Right: Grand Totals & Chart -->
                    <div class="space-y-6">
                        <div class="bg-[#fdf6e3] border border-[#f5e6c8] rounded-xl p-5 shadow-sm">
                            <h4 class="font-bold text-[#8b5a2b] text-base mb-4 border-b border-[#f5e6c8] pb-2">Dashboard สรุปภาพรวม</h4>
                            
                            <div class="mb-4">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-1">ยอดขายรวม (เดือนนี้)</span>
                                <div class="flex items-center justify-between">
                                    <span class="text-xl font-extrabold text-slate-800" id="dash_grand_sales">0.00 บาท</span>
                                    <i class="fa-solid fa-chart-line text-xl text-slate-300"></i>
                                </div>
                            </div>

                            <div class="mb-6">
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-1">ค่าคอมมิชชั่นทั้งสิ้น</span>
                                <div class="flex items-center justify-between">
                                    <span class="text-xl font-extrabold text-emerald-600" id="dash_grand_comm">0.00 บาท</span>
                                    <i class="fa-solid fa-sack-dollar text-xl text-emerald-200"></i>
                                </div>
                            </div>

                            <div>
                                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block mb-3 text-center">สัดส่วนค่าคอมตามตำแหน่ง</span>
                                <div class="h-48 w-full relative">
                                    <canvas id="commissionPieChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let commPieChart = null;

    function loadCommissionSettings() {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_commission_settings' },
            success: function(res) {
                if (res) {
                    $('#comm_admin_rate').val(res.admin_rate);
                    $('#comm_sales_rate').val(res.sales_rate);
                    $('#comm_helper_rate').val(res.helper_rate);
                }
            }
        });
    }

    function switchDashTab(tab) {
        if (tab === 'transactions') {
            $('#dash_tab_transactions').removeClass('bg-slate-100 text-slate-500 hover:bg-slate-200').addClass('bg-blue-600 text-white');
            $('#dash_tab_summary').removeClass('bg-blue-600 text-white').addClass('bg-slate-100 text-slate-500 hover:bg-slate-200');
            $('#dash_view_transactions').removeClass('hidden');
            $('#dash_view_summary').addClass('hidden');
        } else {
            $('#dash_tab_summary').removeClass('bg-slate-100 text-slate-500 hover:bg-slate-200').addClass('bg-blue-600 text-white');
            $('#dash_tab_transactions').removeClass('bg-blue-600 text-white').addClass('bg-slate-100 text-slate-500 hover:bg-slate-200');
            $('#dash_view_summary').removeClass('hidden');
            $('#dash_view_transactions').addClass('hidden');
        }
    }

    function loadCommissionDashboard() {
        const month = $('#dash_comm_month').val();
        
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_commission_dashboard', month: month },
            success: function(res) {
                if (res.status === 'success') {
                    renderDashboard(res.data);
                }
            }
        });
    }

    function renderDashboard(items) {
        let tHtml = '';
        let empMap = {};
        
        let grandSales = 0;
        let grandAdmin = 0;
        let grandSalesComm = 0;
        let grandHelper = 0;
        let grandTotalComm = 0;

        items.forEach(function(item) {
            const salesPrice = parseFloat(item.total_price);
            const adminComm = parseFloat(item.admin_commission);
            const salesComm = parseFloat(item.sales_commission);
            
            let helperCommTotal = 0;
            if (item.helper1_employee_id) helperCommTotal += parseFloat(item.helper1_commission);
            if (item.helper2_employee_id) helperCommTotal += parseFloat(item.helper2_commission);

            grandSales += salesPrice;
            grandAdmin += adminComm;
            grandSalesComm += salesComm;
            grandHelper += helperCommTotal;
            grandTotalComm += (adminComm + salesComm + helperCommTotal);

            if (item.admin_employee_id) {
                if (!empMap[item.admin_name]) empMap[item.admin_name] = {admin:0, sales:0, helper:0};
                empMap[item.admin_name].admin += adminComm;
            }
            if (item.sales_employee_id) {
                if (!empMap[item.sales_name]) empMap[item.sales_name] = {admin:0, sales:0, helper:0};
                empMap[item.sales_name].sales += salesComm;
            }
            if (item.helper1_employee_id) {
                if (!empMap[item.h1_name]) empMap[item.h1_name] = {admin:0, sales:0, helper:0};
                empMap[item.h1_name].helper += parseFloat(item.helper1_commission);
            }
            if (item.helper2_employee_id) {
                if (!empMap[item.h2_name]) empMap[item.h2_name] = {admin:0, sales:0, helper:0};
                empMap[item.h2_name].helper += parseFloat(item.helper2_commission);
            }

            const statusBadge = item.status === 'approved' 
                ? '<span class="text-emerald-500 font-bold text-xs">อนุมัติแล้ว</span>' 
                : '<span class="text-amber-500 font-bold text-xs">แบบร่าง</span>';

            tHtml += `
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2 text-sm font-bold text-slate-600">${formatThaiDate(item.transaction_date).split(' ')[0] + ' ' + formatThaiDate(item.transaction_date).split(' ')[1]}</td>
                    <td class="px-4 py-2">
                        <div class="text-xs font-bold text-slate-700">${item.product_code || '-'}</div>
                        <div class="text-xs text-slate-500">${item.product_name}</div>
                    </td>
                    <td class="px-4 py-2 text-sm font-bold text-slate-700 text-right">${salesPrice.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-2 text-sm text-center">${item.admin_name || '-'}</td>
                    <td class="px-4 py-2 text-sm text-center">${item.sales_name || '-'}</td>
                    <td class="px-4 py-2 text-sm text-center">${item.h1_name || '-'}</td>
                    <td class="px-4 py-2 text-sm text-center">${item.h2_name || '-'}</td>
                    <td class="px-4 py-2 text-center">${statusBadge}</td>
                </tr>
            `;
        });

        if (items.length === 0) {
            tHtml = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400 font-bold">ไม่พบข้อมูลในเดือนนี้</td></tr>';
        }

        $('#dash_transactions_body').html(tHtml);

        let eHtml = '';
        for (const [name, data] of Object.entries(empMap)) {
            const empTotal = data.admin + data.sales + data.helper;
            const pct = grandTotalComm > 0 ? (empTotal / grandTotalComm) * 100 : 0;
            
            eHtml += `
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2.5 text-sm font-bold text-slate-700">${name}</td>
                    <td class="px-4 py-2.5 text-sm text-right ${data.admin > 0 ? 'font-bold text-purple-600' : 'text-slate-300'}">${data.admin > 0 ? data.admin.toLocaleString('th-TH', {minimumFractionDigits: 2}) : '-'}</td>
                    <td class="px-4 py-2.5 text-sm text-right ${data.sales > 0 ? 'font-bold text-blue-600' : 'text-slate-300'}">${data.sales > 0 ? data.sales.toLocaleString('th-TH', {minimumFractionDigits: 2}) : '-'}</td>
                    <td class="px-4 py-2.5 text-sm text-right ${data.helper > 0 ? 'font-bold text-amber-600' : 'text-slate-300'}">${data.helper > 0 ? data.helper.toLocaleString('th-TH', {minimumFractionDigits: 2}) : '-'}</td>
                    <td class="px-4 py-2.5 text-sm font-extrabold text-emerald-600 text-right">${empTotal.toLocaleString('th-TH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-2.5 text-xs font-bold text-slate-500 text-right">${pct.toFixed(2)}%</td>
                </tr>
            `;
        }
        
        if (Object.keys(empMap).length === 0) {
            eHtml = '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-400 font-bold">ไม่มีข้อมูล</td></tr>';
        }

        $('#dash_employee_body').html(eHtml);

        $('#dash_sum_admin').text(grandAdmin.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        $('#dash_sum_sales').text(grandSalesComm.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        $('#dash_sum_helper').text(grandHelper.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        $('#dash_sum_total').text(grandTotalComm.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        
        $('#dash_grand_sales').text(grandSales.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');
        $('#dash_grand_comm').text(grandTotalComm.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');

        updatePieChart(grandAdmin, grandSalesComm, grandHelper);
    }

    function updatePieChart(admin, sales, helper) {
        const ctx = document.getElementById('commissionPieChart');
        if (!ctx) return;

        if (commPieChart) {
            commPieChart.destroy();
        }

        commPieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['แอดมิน', 'คนปิดการขาย', 'ผู้ช่วย'],
                datasets: [{
                    data: [admin, sales, helper],
                    backgroundColor: ['#9333ea', '#2563eb', '#d97706'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: { family: "'Prompt', sans-serif", size: 11 },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    }
                },
                cutout: '70%'
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

    // We also want to load the dashboard when this file loads (via index switchTab)
    const originalSettingsLoad = loadCommissionSettings;
    loadCommissionSettings = function() {
        originalSettingsLoad();
        loadCommissionDashboard();
    };
</script>

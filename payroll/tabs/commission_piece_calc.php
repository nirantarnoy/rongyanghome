<div class="space-y-6">
    <!-- Header Controls -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h4 class="font-bold text-slate-800 text-base">ระบบคิดค่าคอมมิชชั่นขายเฟอร์นิเจอร์ (รายชิ้น)</h4>
            <p class="text-xs text-slate-400 mt-0.5">ระบุรายการขายและสัดส่วนพนักงานเพื่อคำนวณค่าคอมมิชชั่น</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-slate-500 uppercase">วันที่ปิดการขาย:</span>
            <input type="date" id="commission_piece_transaction_date" value="<?= date('Y-m-d') ?>"
                   class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none font-bold text-slate-700 transition-all">
        </div>
    </div>

    <!-- Items Wrapper -->
    <div id="commissionPieceItemsContainer" class="space-y-6">
        <!-- Item Blocks will be appended here dynamically -->
    </div>

    <!-- Add Item Button & Totals Panel -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <button type="button" onclick="addCommissionPieceItemRow()" 
                class="inline-flex items-center px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold rounded-xl text-xs transition-all gap-1.5 border border-blue-100">
            <i class="fa-solid fa-plus"></i>
            <span>เพิ่มรายการสินค้า</span>
        </button>

        <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-500">รวมราคาสินค้า (รวม VAT):</span>
                <input type="text" id="piece_invoice_total_sales" readonly value="0.00"
                       class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 w-36 text-right outline-none">
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-500">รวมค่าคอม:</span>
                <input type="text" id="piece_invoice_total_commission" readonly value="0.00"
                       class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 w-36 text-right outline-none">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end gap-3">
        <button type="button" onclick="resetCommissionPieceForm()"
                class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 text-xs transition-all">
            ยกเลิก
        </button>
        <button type="button" onclick="savePieceCommission(false)"
                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10">
            บันทึกชั่วคราว
        </button>
        <button type="button" onclick="savePieceCommission(true)"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-emerald-500/10">
            บันทึกและยืนยัน
        </button>
    </div>
</div>

<script>
    let employeesPieceList = [];
    let defaultPieceAdminRate = 1.00;
    let defaultPieceSalesRate = 2.00;
    let defaultPieceHelperRate = 0.50;
    let commPieceItemIndex = 0;
    let activeEditPieceCommId = 0;

    function loadCommissionPieceCalc() {
        // Load settings and employees first
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_commission_settings' },
            success: function(rates) {
                if (rates) {
                    defaultPieceAdminRate = parseFloat(rates.admin_rate || 1.00);
                    defaultPieceSalesRate = parseFloat(rates.sales_rate || 2.00);
                    defaultPieceHelperRate = parseFloat(rates.helper_rate || 0.50);
                }
                
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'GET',
                    data: { action: 'get_commission_employees' },
                    success: function(emps) {
                        employeesPieceList = emps || [];
                        resetCommissionPieceForm();
                    }
                });
            }
        });
    }

    function resetCommissionPieceForm() {
        activeEditPieceCommId = 0;
        $('#commission_piece_transaction_date').val(new Date().toISOString().split('T')[0]);
        $('#commissionPieceItemsContainer').html('');
        addCommissionPieceItemRow();
        updatePieceInvoiceTotals();
    }

    function addCommissionPieceItemRow(data = null) {
        commPieceItemIndex++;
        const index = commPieceItemIndex;
        
        let employeeOptions = '<option value="">-- เลือกพนักงาน --</option>';
        employeesPieceList.forEach(function(emp) {
            employeeOptions += `<option value="${emp.id}">${emp.name}</option>`;
        });

        const itemHtml = `
        <div class="item-block bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-6 relative" id="item-block-${index}">
            <!-- Delete Row Button -->
            <button onclick="removePieceItemRow(${index})" class="absolute top-4 right-4 text-slate-300 hover:text-rose-500 transition-colors p-1" title="ลบรายการสินค้า">
                <i class="fa-solid fa-trash text-sm"></i>
            </button>

            <!-- Row 1: Product Fields -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">ลำดับ</label>
                    <span class="row-number font-bold text-slate-500 text-sm block py-2.5"></span>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">รหัสสินค้า</label>
                    <input type="text" class="prod-code w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 text-xs transition-all" placeholder="เช่น FUR-001">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">รายการสินค้า *</label>
                    <input type="text" class="prod-name w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 text-xs transition-all" placeholder="เช่น โซฟาเข้ามุม">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">หน่วย</label>
                    <input type="text" class="prod-unit w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-semibold text-slate-700 text-xs transition-all" placeholder="ตัว/ชุด">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">จำนวน *</label>
                    <input type="number" class="prod-qty w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 text-xs transition-all text-center" value="1" min="1">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">ราคาต่อหน่วย (บาท) *</label>
                    <input type="number" step="0.01" class="prod-price w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 text-xs text-right transition-all" placeholder="0.00">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase mb-1">ราคารวม (บาท)</label>
                    <input type="text" readonly class="prod-total w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-700 text-xs text-right outline-none" value="0.00">
                </div>
            </div>

            <!-- Row 2: Sub-panels for Commissions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-slate-100">
                
                <!-- Col A: Admin -->
                <div class="bg-slate-50/50 rounded-2xl p-4 border border-slate-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-user-gear text-purple-500"></i>
                            <span>แอดมินรับลูกค้า/ช่วยตอบ</span>
                        </span>
                        <span class="text-[10px] text-purple-600 font-bold bg-purple-50 px-2 py-0.5 rounded-full border border-purple-100">รับ <span class="admin-label-rate">${defaultPieceAdminRate}</span>%</span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1">แอดมิน (เลือกหรือไม่เลือกก็ได้)</label>
                            <select class="admin-emp-select w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-xs font-semibold text-slate-700 transition-all">
                                ${employeeOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1">ตำแหน่ง</label>
                            <input type="text" readonly class="admin-emp-position w-full px-3 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-semibold text-slate-500 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">อัตราค่าคอม (%)</label>
                                <input type="number" step="0.01" class="admin-rate-input w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-xs font-bold text-slate-700 text-center transition-all" value="${defaultPieceAdminRate}">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">ค่าคอมที่ได้รับ</label>
                                <div class="admin-commission-box text-center py-1.5 px-3 bg-purple-50/50 border border-purple-200 text-purple-600 font-extrabold text-xs rounded-xl">
                                    0.00 บาท
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Col B: Salesperson -->
                <div class="bg-slate-50/50 rounded-2xl p-4 border border-slate-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-user-tie text-blue-500"></i>
                            <span>คนปิดการขาย</span>
                        </span>
                        <span class="text-[10px] text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">รับ <span class="sales-label-rate">${defaultPieceSalesRate}</span>%</span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1">คนปิดการขาย *</label>
                            <select class="sales-emp-select w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-xs font-semibold text-slate-700 transition-all">
                                ${employeeOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1">ตำแหน่ง</label>
                            <input type="text" readonly class="sales-emp-position w-full px-3 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-semibold text-slate-500 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">อัตราค่าคอม (%)</label>
                                <input type="number" step="0.01" class="sales-rate-input w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-xs font-bold text-slate-700 text-center transition-all" value="${defaultPieceSalesRate}">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">ค่าคอมที่ได้รับ</label>
                                <div class="sales-commission-box text-center py-1.5 px-3 bg-blue-50/50 border border-blue-200 text-blue-600 font-extrabold text-xs rounded-xl">
                                    0.00 บาท
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Col C: Helpers -->
                <div class="bg-slate-50/50 rounded-2xl p-4 border border-slate-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-users text-amber-500"></i>
                            <span>ผู้ช่วยติดตามงานช่าง</span>
                        </span>
                        <span class="text-[10px] text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">รวม <span class="helper-label-rate">${defaultPieceHelperRate}</span>%</span>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1">ผู้ช่วยคนที่ 1 (เลือกหรือไม่เลือกก็ได้)</label>
                            <select class="helper1-emp-select w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-xs font-semibold text-slate-700 transition-all">
                                ${employeeOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1">ผู้ช่วยคนที่ 2 (เลือกหรือไม่เลือกก็ได้)</label>
                            <select class="helper2-emp-select w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-xs font-semibold text-slate-700 transition-all">
                                ${employeeOptions}
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">อัตรา (ต่อคน) (%)</label>
                                <input type="text" readonly class="helper-rate-per-person w-full px-3 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 text-center outline-none" value="0.00">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-1">ค่าคอม (ต่อคน)</label>
                                <input type="text" readonly class="helper-commission-per-person w-full px-3 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-bold text-slate-500 text-right outline-none" value="0.00 บาท">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Col D: Summary Card -->
                <div class="bg-slate-50/30 rounded-2xl p-5 border border-slate-100 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-700 block mb-3 uppercase tracking-wider">สรุปค่าคอมมิชชั่น</span>
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>ยอดขายรวม (รวม VAT)</span>
                                <span class="font-bold text-slate-700"><span class="sum-card-sales">0.00</span> บาท</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>ค่าคอมแอดมิน <span class="admin-sum-pct">1.00</span>%</span>
                                <span class="font-bold text-slate-700"><span class="sum-card-admin-comm">0.00</span> บาท</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>ค่าคอมคนปิดการขาย <span class="sales-sum-pct">2.00</span>%</span>
                                <span class="font-bold text-slate-700"><span class="sum-card-sales-comm">0.00</span> บาท</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>ค่าคอมผู้ช่วยติดตาม <span class="helper-sum-pct">0.50</span>%</span>
                                <span class="font-bold text-slate-700"><span class="sum-card-helper-comm">0.00</span> บาท</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100 text-center mt-4">
                        <div class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider mb-0.5">รวมค่าคอมทั้งหมด</div>
                        <div class="text-emerald-700 font-extrabold text-sm sum-card-total">0.00 บาท</div>
                    </div>
                </div>

            </div>
        </div>
        `;

        $('#commissionPieceItemsContainer').append(itemHtml);
        
        const row = $(`#item-block-${index}`);
        if (data) {
            row.find('.prod-code').val(data.product_code);
            row.find('.prod-name').val(data.product_name);
            row.find('.prod-unit').val(data.unit);
            row.find('.prod-qty').val(data.quantity);
            row.find('.prod-price').val(data.unit_price);

            row.find('.admin-emp-select').val(data.admin_employee_id || '');
            row.find('.admin-rate-input').val(data.admin_rate || defaultPieceAdminRate);
            row.find('.sales-emp-select').val(data.sales_employee_id);
            row.find('.sales-rate-input').val(data.sales_rate);
            row.find('.helper1-emp-select').val(data.helper1_employee_id || '');
            row.find('.helper2-emp-select').val(data.helper2_employee_id || '');
            
            // Set positions
            const adminEmp = employeesPieceList.find(e => e.id == data.admin_employee_id);
            if (adminEmp) row.find('.admin-emp-position').val(adminEmp.position);

            const emp = employeesPieceList.find(e => e.id == data.sales_employee_id);
            if (emp) row.find('.sales-emp-position').val(emp.position);
        }

        reorderPieceRows();
        calculatePieceItem(row);
    }

    function removePieceItemRow(index) {
        if ($('.item-block').length <= 1) {
            Swal.fire('ข้อผิดพลาด', 'ต้องมีรายการสินค้าอย่างน้อย 1 รายการ', 'warning');
            return;
        }
        $(`#item-block-${index}`).remove();
        reorderPieceRows();
        updatePieceInvoiceTotals();
    }

    function reorderPieceRows() {
        $('.item-block').each(function(idx) {
            $(this).find('.row-number').text(idx + 1);
        });
    }

    function calculatePieceItem(row) {
        const qty = parseInt(row.find('.prod-qty').val()) || 0;
        const price = parseFloat(row.find('.prod-price').val()) || 0.00;
        const total = qty * price;
        row.find('.prod-total').val(total.toFixed(2));

        // Admin calculation
        const adminEmpSelected = !!row.find('.admin-emp-select').val();
        let adminComm = 0;
        let adminRate = 0;
        if (adminEmpSelected) {
            adminRate = parseFloat(row.find('.admin-rate-input').val()) || 0.00;
            adminComm = (total * adminRate) / 100;
        }
        row.find('.admin-commission-box').text(adminComm.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');
        row.find('.admin-label-rate').text(adminRate.toFixed(2));
        row.find('.admin-sum-pct').text(adminRate.toFixed(2));

        // Salesperson calculation
        const salesRate = parseFloat(row.find('.sales-rate-input').val()) || 0.00;
        const salesComm = (total * salesRate) / 100;
        row.find('.sales-commission-box').text(salesComm.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');
        row.find('.sales-label-rate').text(salesRate.toFixed(2));
        row.find('.sales-sum-pct').text(salesRate.toFixed(2));

        // Helpers calculation
        const h1Selected = !!row.find('.helper1-emp-select').val();
        const h2Selected = !!row.find('.helper2-emp-select').val();
        
        let helperCount = 0;
        if (h1Selected) helperCount++;
        if (h2Selected) helperCount++;

        let helperRatePerPerson = 0;
        let helperCommPerPerson = 0;
        let helperCommTotal = 0;

        if (helperCount > 0) {
            helperRatePerPerson = defaultPieceHelperRate / helperCount;
            helperCommPerPerson = (total * helperRatePerPerson) / 100;
            helperCommTotal = helperCommPerPerson * helperCount;
        }

        row.find('.helper-label-rate').text(defaultPieceHelperRate.toFixed(2));
        row.find('.helper-sum-pct').text(defaultPieceHelperRate.toFixed(2));
        row.find('.helper-rate-per-person').val(helperRatePerPerson.toFixed(4));
        row.find('.helper-commission-per-person').val(helperCommPerPerson.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');

        // Sum Card values
        row.find('.sum-card-sales').text(total.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        row.find('.sum-card-admin-comm').text(adminComm.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        row.find('.sum-card-sales-comm').text(salesComm.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        row.find('.sum-card-helper-comm').text(helperCommTotal.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        
        const rowTotalComm = adminComm + salesComm + helperCommTotal;
        row.find('.sum-card-total').text(rowTotalComm.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท');

        updatePieceInvoiceTotals();
    }

    function updatePieceInvoiceTotals() {
        let totalSales = 0;
        let totalComm = 0;

        $('.item-block').each(function() {
            const qty = parseInt($(this).find('.prod-qty').val()) || 0;
            const price = parseFloat($(this).find('.prod-price').val()) || 0.00;
            const total = qty * price;
            totalSales += total;

            const adminEmp = $(this).find('.admin-emp-select').val();
            let adminComm = 0;
            if (adminEmp) {
                const adminRate = parseFloat($(this).find('.admin-rate-input').val()) || 0.00;
                adminComm = (total * adminRate) / 100;
            }

            const salesRate = parseFloat($(this).find('.sales-rate-input').val()) || 0.00;
            const salesComm = (total * salesRate) / 100;

            const h1 = $(this).find('.helper1-emp-select').val();
            const h2 = $(this).find('.helper2-emp-select').val();
            let helperCount = 0;
            if (h1) helperCount++;
            if (h2) helperCount++;
            
            let helperComm = 0;
            if (helperCount > 0) {
                helperComm = (total * defaultPieceHelperRate) / 100;
            }
            
            totalComm += (adminComm + salesComm + helperComm);
        });

        $('#piece_invoice_total_sales').val(totalSales.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        $('#piece_invoice_total_commission').val(totalComm.toLocaleString('th-TH', {minimumFractionDigits: 2}));
    }

    function savePieceCommission(isApprove) {
        const transDate = $('#commission_piece_transaction_date').val();
        if (!transDate) {
            Swal.fire('คำเตือน', 'กรุณาระบุวันที่ปิดการขาย', 'warning');
            return;
        }

        const items = [];
        let validationError = false;
        let validationMsg = '';

        $('.item-block').each(function() {
            const row = $(this);
            const pName = row.find('.prod-name').val();
            const qty = parseInt(row.find('.prod-qty').val()) || 0;
            const price = parseFloat(row.find('.prod-price').val()) || 0;
            const adminEmp = row.find('.admin-emp-select').val();
            const adminRate = parseFloat(row.find('.admin-rate-input').val()) || 0;
            const salesEmp = row.find('.sales-emp-select').val();
            const salesRate = parseFloat(row.find('.sales-rate-input').val()) || 0;

            if (!pName) {
                validationError = true;
                validationMsg = 'กรุณาระบุรายการสินค้าสำหรับทุกแถว';
                return false;
            }
            if (qty <= 0 || price <= 0) {
                validationError = true;
                validationMsg = 'กรุณาระบุจำนวนและราคาต่อหน่วยให้ถูกต้อง';
                return false;
            }
            if (!salesEmp) {
                validationError = true;
                validationMsg = 'กรุณาเลือกพนักงานคนปิดการขายสำหรับทุกแถว';
                return false;
            }

            const total = qty * price;
            const adminComm = adminEmp ? (total * adminRate) / 100 : 0;
            const salesComm = (total * salesRate) / 100;

            const h1 = row.find('.helper1-emp-select').val();
            const h2 = row.find('.helper2-emp-select').val();
            
            let helperCount = 0;
            if (h1) helperCount++;
            if (h2) helperCount++;

            let helperRatePerPerson = 0;
            let helperCommPerPerson = 0;
            let helperCommTotal = 0;

            if (helperCount > 0) {
                helperRatePerPerson = defaultPieceHelperRate / helperCount;
                helperCommPerPerson = (total * helperRatePerPerson) / 100;
                helperCommTotal = helperCommPerPerson * helperCount;
            }

            items.push({
                product_code: row.find('.prod-code').val(),
                product_name: pName,
                unit: row.find('.prod-unit').val(),
                quantity: qty,
                unit_price: price,
                total_price: total,
                admin_employee_id: adminEmp || null,
                admin_rate: adminRate,
                admin_commission: adminComm,
                sales_employee_id: salesEmp,
                sales_rate: salesRate,
                sales_commission: salesComm,
                helper1_employee_id: h1 || null,
                helper1_rate: helperRatePerPerson,
                helper1_commission: helperCommPerPerson,
                helper2_employee_id: h2 || null,
                helper2_rate: helperRatePerPerson,
                helper2_commission: helperCommPerPerson,
                item_total_commission: adminComm + salesComm + helperCommTotal
            });
        });

        if (validationError) {
            Swal.fire('ข้อมูลไม่สมบูรณ์', validationMsg, 'warning');
            return;
        }

        const rawTotalSales = parseFloat($('#piece_invoice_total_sales').val().replace(/,/g, '')) || 0;
        const rawTotalComm = parseFloat($('#piece_invoice_total_commission').val().replace(/,/g, '')) || 0;
        const status = isApprove ? 'approved' : 'draft';

        const postData = {
            action: 'save_commission_transaction', commission_type: 'piece',
            transaction_date: transDate,
            total_amount: rawTotalSales,
            total_commission: rawTotalComm,
            status: status,
            items: JSON.stringify(items)
        };

        if (activeEditPieceCommId > 0) {
            postData.id = activeEditPieceCommId;
        }

        Swal.fire({
            title: isApprove ? 'บันทึกและอนุมัติยอด?' : 'บันทึกเป็นแบบร่าง?',
            text: isApprove ? 'หากอนุมัติแล้ว ข้อมูลจะถูกนำไปคำนวณเงินเดือนสำหรับรอบสิ้นเดือนทันที' : 'บันทึกประวัติเพื่อเข้ามาแก้ไขภายหลัง',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: isApprove ? '#10b981' : '#3b82f6',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'POST',
                    data: postData,
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'บันทึกสำเร็จ',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            
                            if (activeEditPieceCommId > 0) {
                                // Go back to history tab
                                switchTab('commission_piece_history');
                            } else {
                                resetCommissionPieceForm();
                            }
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                        }
                    }
                });
            }
        });
    }

    // Attach real-time listeners for updates
    $(document).on('change keyup', '.prod-qty, .prod-price, .admin-rate-input, .sales-rate-input', function() {
        const row = $(this).closest('.item-block');
        calculatePieceItem(row);
    });

    $(document).on('change', '.admin-emp-select', function() {
        const empId = $(this).val();
        const row = $(this).closest('.item-block');
        const emp = employeesPieceList.find(e => e.id == empId);
        if (emp) {
            row.find('.admin-emp-position').val(emp.position);
        } else {
            row.find('.admin-emp-position').val('');
        }
        calculatePieceItem(row);
    });

    $(document).on('change', '.sales-emp-select', function() {
        const empId = $(this).val();
        const row = $(this).closest('.item-block');
        const emp = employeesPieceList.find(e => e.id == empId);
        if (emp) {
            row.find('.sales-emp-position').val(emp.position);
        } else {
            row.find('.sales-emp-position').val('');
        }
        calculatePieceItem(row);
    });

    $(document).on('change', '.helper1-emp-select, .helper2-emp-select', function() {
        const row = $(this).closest('.item-block');
        calculatePieceItem(row);
    });
</script>

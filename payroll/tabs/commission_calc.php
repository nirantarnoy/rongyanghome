<div class="space-y-6">
    <!-- Header Controls -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h4 class="font-bold text-slate-800 text-lg">ระบบคิดค่าคอมมิชชั่นขายเฟอร์นิเจอร์ (แบบเหมารายเดือน)</h4>
            <p class="text-sm text-slate-400 mt-0.5">ระบุยอดขายรวมและอัตราค่าคอมเพื่อแบ่งจ่ายให้พนักงาน</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-slate-500 uppercase">วันที่ปิดยอด:</span>
            <input type="date" id="commission_transaction_date" value="<?= date('Y-m-d') ?>"
                   class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none font-bold text-slate-700 transition-all">
        </div>
    </div>

    <!-- Items Wrapper -->
    <div id="commissionItemsContainer" class="space-y-4">
        <!-- Item Blocks will be appended here dynamically -->
    </div>

    <!-- Add Item Button & Totals Panel -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <button type="button" onclick="addCommissionItemRow()" 
                class="inline-flex items-center px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold rounded-xl text-sm transition-all gap-1.5 border border-blue-100">
            <i class="fa-solid fa-plus"></i>
            <span>เพิ่มรายการค่าคอมพนักงาน</span>
        </button>

        <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-slate-500">รวมยอดขายอ้างอิง:</span>
                <input type="text" id="invoice_total_sales" readonly value="0.00"
                       class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-base font-bold text-slate-700 w-36 text-right outline-none">
            </div>

            <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-slate-500">รวมค่าคอมทั้งหมด:</span>
                <input type="text" id="invoice_total_commission" readonly value="0.00"
                       class="px-4 py-2 bg-emerald-50 border border-emerald-200 rounded-xl text-base font-bold text-emerald-700 w-36 text-right outline-none">
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end gap-3">
        <button type="button" onclick="resetCommissionForm()"
                class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 text-sm transition-all">
            ยกเลิก
        </button>
        <button type="button" onclick="saveCommission(false)"
                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm transition-all shadow-md shadow-blue-500/10">
            บันทึกชั่วคราว
        </button>
        <button type="button" onclick="saveCommission(true)"
                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition-all shadow-md shadow-emerald-500/10">
            บันทึกและยืนยัน
        </button>
    </div>
</div>

<script>
    let employeesList = [];
    let commissionSettings = null;
    let defaultSalesRate = 1.00;
    let commItemIndex = 0;
    let activeEditCommId = 0;

    function loadCommissionCalc() {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_commission_settings' },
            success: function(rates) {
                if (rates) {
                    commissionSettings = rates;
                    defaultSalesRate = parseFloat(rates.sales_rate || 1.00);
                }
                
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'GET',
                    data: { action: 'get_commission_employees' },
                    success: function(emps) {
                        employeesList = emps || [];
                        
                        if (window.pendingEditCommissionData) {
                            const res = window.pendingEditCommissionData;
                            window.pendingEditCommissionData = null;
                            
                            activeEditCommId = res.commission.id;
                            $('#commission_transaction_date').val(res.commission.transaction_date);
                            $('#commissionItemsContainer').html('');
                            
                            res.items.forEach(function(item) {
                                addCommissionItemRow(item);
                            });
                            updateInvoiceTotals();
                        } else {
                            resetCommissionForm();
                        }
                    }
                });
            }
        });
    }

    function resetCommissionForm() {
        activeEditCommId = 0;
        $('#commission_transaction_date').val(new Date().toISOString().split('T')[0]);
        $('#commissionItemsContainer').html('');
        addCommissionItemRow();
        updateInvoiceTotals();
    }

    function addCommissionItemRow(data = null) {
        commItemIndex++;
        const index = commItemIndex;
        
        let employeeOptions = '<option value="">-- เลือกพนักงาน --</option>';
        employeesList.forEach(function(emp) {
            employeeOptions += `<option value="${emp.id}">${emp.name} (${emp.position})</option>`;
        });

        const itemHtml = `
        <div class="item-block bg-white rounded-xl shadow-sm border border-slate-100 p-5 relative" id="item-block-${index}">
            <button onclick="removeItemRow(${index})" class="absolute top-3 right-3 text-slate-300 hover:text-rose-500 transition-colors p-1" title="ลบรายการ">
                <i class="fa-solid fa-trash text-base"></i>
            </button>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mt-2">
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">ลำดับ</label>
                    <span class="row-number font-bold text-slate-500 text-sm block py-2.5"></span>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">แพลตฟอร์ม / รายการ *</label>
                    <select class="prod-name w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-semibold text-slate-700 transition-all">
                        <option value="">-- เลือกแพลตฟอร์ม --</option>
                        <option value="Shopee">Shopee</option>
                        <option value="Lazada">Lazada</option>
                        <option value="Tiktok">Tiktok</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">ยอดขายรวม (บาท) *</label>
                    <input type="number" step="0.01" class="prod-price w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 text-sm text-right transition-all" placeholder="0.00">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">พนักงานขาย *</label>
                    <select class="sales-emp-select w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-semibold text-slate-700 transition-all">
                        ${employeeOptions}
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">ค่าคอม (%)</label>
                    <input type="number" step="0.01" class="sales-rate-input w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none font-bold text-slate-700 text-sm text-center transition-all" value="${defaultSalesRate}">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">ยอดค่าคอม (บาท)</label>
                    <input type="text" readonly class="prod-total w-full px-3 py-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg font-extrabold text-sm text-right outline-none" value="0.00">
                </div>
            </div>
        </div>
        `;

        $('#commissionItemsContainer').append(itemHtml);
        
        const row = $(`#item-block-${index}`);
        if (data) {
            row.find('.prod-name').val(data.product_name);
            row.find('.prod-price').val(data.total_price); // total price acts as total sales here
            row.find('.sales-emp-select').val(data.sales_employee_id);
            row.find('.sales-rate-input').val(data.sales_rate);
        }

        reorderRows();
        calculateItem(row);
    }

    function removeItemRow(index) {
        if ($('.item-block').length <= 1) {
            Swal.fire('ข้อผิดพลาด', 'ต้องมีรายการอย่างน้อย 1 รายการ', 'warning');
            return;
        }
        $(`#item-block-${index}`).remove();
        reorderRows();
        updateInvoiceTotals();
    }

    function reorderRows() {
        $('.item-block').each(function(idx) {
            $(this).find('.row-number').text(idx + 1);
        });
    }

    function calculateItem(row) {
        const salesAmount = parseFloat(row.find('.prod-price').val()) || 0.00;
        const rate = parseFloat(row.find('.sales-rate-input').val()) || 0.00;
        const commission = (salesAmount * rate) / 100;
        
        row.find('.prod-total').val(commission.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        updateInvoiceTotals();
    }

    function updateInvoiceTotals() {
        let maxSales = 0; 
        let totalSales = 0;
        let totalComm = 0;

        $('.item-block').each(function() {
            const salesAmount = parseFloat($(this).find('.prod-price').val()) || 0.00;
            const rate = parseFloat($(this).find('.sales-rate-input').val()) || 0.00;
            const commission = (salesAmount * rate) / 100;
            
            totalSales += salesAmount;
            totalComm += commission;
        });

        $('#invoice_total_sales').val(totalSales.toLocaleString('th-TH', {minimumFractionDigits: 2}));
        $('#invoice_total_commission').val(totalComm.toLocaleString('th-TH', {minimumFractionDigits: 2}));
    }

    function saveCommission(isApprove) {
        const transDate = $('#commission_transaction_date').val();
        if (!transDate) {
            Swal.fire('คำเตือน', 'กรุณาระบุวันที่ปิดยอด', 'warning');
            return;
        }

        const items = [];
        let validationError = false;
        let validationMsg = '';

        $('.item-block').each(function() {
            const row = $(this);
            const pName = row.find('.prod-name').val();
            const price = parseFloat(row.find('.prod-price').val()) || 0;
            const salesEmp = row.find('.sales-emp-select').val();
            const salesRate = parseFloat(row.find('.sales-rate-input').val()) || 0;

            if (!pName) {
                validationError = true;
                validationMsg = 'กรุณาระบุรายการสำหรับทุกแถว';
                return false;
            }
            if (price <= 0) {
                validationError = true;
                validationMsg = 'กรุณาระบุยอดขายรวมให้ถูกต้อง';
                return false;
            }
            if (!salesEmp) {
                validationError = true;
                validationMsg = 'กรุณาเลือกพนักงานขายสำหรับทุกแถว';
                return false;
            }

            const salesComm = (price * salesRate) / 100;

            items.push({
                product_code: '', // not used
                product_name: pName,
                unit: 'เหมา',
                quantity: 1, // default
                unit_price: price, // store total sales here
                total_price: price, // store total sales here
                admin_employee_id: null,
                admin_rate: 0,
                admin_commission: 0,
                sales_employee_id: salesEmp,
                sales_rate: salesRate,
                sales_commission: salesComm,
                helper1_employee_id: null,
                helper1_rate: 0,
                helper1_commission: 0,
                helper2_employee_id: null,
                helper2_rate: 0,
                helper2_commission: 0,
                item_total_commission: salesComm
            });
        });

        if (validationError) {
            Swal.fire('ข้อมูลไม่สมบูรณ์', validationMsg, 'warning');
            return;
        }

        const rawTotalSales = parseFloat($('#invoice_total_sales').val().replace(/,/g, '')) || 0;
        const rawTotalComm = parseFloat($('#invoice_total_commission').val().replace(/,/g, '')) || 0;
        const status = isApprove ? 'approved' : 'draft';

        const postData = {
            action: 'save_commission_transaction',
            transaction_date: transDate,
            total_amount: rawTotalSales,
            total_commission: rawTotalComm,
            status: status,
            items: JSON.stringify(items)
        };

        if (activeEditCommId > 0) {
            postData.id = activeEditCommId;
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
                            
                            if (activeEditCommId > 0) {
                                switchTab('commission_history');
                            } else {
                                resetCommissionForm();
                            }
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                        }
                    }
                });
            }
        });
    }

    $(document).on('change keyup', '.prod-price, .sales-rate-input', function() {
        const row = $(this).closest('.item-block');
        calculateItem(row);
    });

    $(document).on('change', '.sales-emp-select', function() {
        const row = $(this).closest('.item-block');
        calculateItem(row);
    });

    $(document).on('change', '.prod-name', function() {
        const row = $(this).closest('.item-block');
        const platform = $(this).val();
        let newRate = defaultSalesRate;
        if (commissionSettings) {
            if (platform === 'Shopee') newRate = parseFloat(commissionSettings.shopee_rate);
            else if (platform === 'Lazada') newRate = parseFloat(commissionSettings.lazada_rate);
            else if (platform === 'Tiktok') newRate = parseFloat(commissionSettings.tiktok_rate);
        }
        row.find('.sales-rate-input').val(newRate);
        calculateItem(row);
    });
</script>

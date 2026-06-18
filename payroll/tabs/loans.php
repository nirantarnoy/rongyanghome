<!-- Summary Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <!-- Card 1: Total Loan Outstanding -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between transition-all hover:shadow-md">
        <div class="space-y-1">
            <span class="text-sm font-semibold text-slate-400 uppercase tracking-wider">ยอดค้างชำระเงินกู้</span>
            <h3 class="text-2xl font-bold text-slate-800 mt-1" id="summary-total-loan-balance">0.00 บาท</h3>
            <span class="text-xs font-semibold text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full" id="summary-total-loan-count">0 สัญญา</span>
        </div>
        <div class="bg-blue-50 p-4 rounded-2xl text-blue-600">
            <i class="fa-solid fa-file-invoice-dollar text-2xl"></i>
        </div>
    </div>

    <!-- Card 2: Total Borrow Outstanding -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between transition-all hover:shadow-md">
        <div class="space-y-1">
            <span class="text-sm font-semibold text-slate-400 uppercase tracking-wider">ยอดค้างชำระเงินยืม</span>
            <h3 class="text-2xl font-bold text-slate-800 mt-1" id="summary-total-borrow-balance">0.00 บาท</h3>
            <span class="text-xs font-semibold text-amber-500 bg-amber-50 px-2 py-0.5 rounded-full" id="summary-total-borrow-count">0 สัญญา</span>
        </div>
        <div class="bg-amber-50 p-4 rounded-2xl text-amber-600">
            <i class="fa-solid fa-hand-holding-dollar text-2xl"></i>
        </div>
    </div>

    <!-- Card 3: Repaid This Month -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between transition-all hover:shadow-md">
        <div class="space-y-1">
            <span class="text-sm font-semibold text-slate-400 uppercase tracking-wider">ยอดชำระคืนรวม</span>
            <h3 class="text-2xl font-bold text-slate-800 mt-1" id="summary-total-repaid">0.00 บาท</h3>
            <span class="text-xs font-semibold text-emerald-500 bg-emerald-50 px-2 py-0.5 rounded-full">ทั้งหมดในระบบ</span>
        </div>
        <div class="bg-emerald-50 p-4 rounded-2xl text-emerald-600">
            <i class="fa-solid fa-circle-check text-2xl"></i>
        </div>
    </div>

    <!-- Card 4: Active Borrowers -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between transition-all hover:shadow-md">
        <div class="space-y-1">
            <span class="text-sm font-semibold text-slate-400 uppercase tracking-wider">กำลังผ่อนชำระ</span>
            <h3 class="text-2xl font-bold text-slate-800 mt-1" id="summary-active-count">0 คน</h3>
            <span class="text-xs font-semibold text-purple-500 bg-purple-50 px-2 py-0.5 rounded-full" id="summary-total-count">รวม 0 รายการ</span>
        </div>
        <div class="bg-purple-50 p-4 rounded-2xl text-purple-600">
            <i class="fa-solid fa-users-gear text-2xl"></i>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <!-- Search Input -->
        <div class="relative w-full md:w-64">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-sm"></i>
            </span>
            <input type="text" id="loanSearch" onkeyup="filterLoans()" placeholder="ค้นหาชื่อพนักงาน หรือ เลขสัญญา..." 
                   class="block w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
        </div>

        <!-- Filter by Type -->
        <select id="filterType" onchange="filterLoans()" 
                class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none text-slate-600">
            <option value="all">ทุกประเภท</option>
            <option value="loan">เงินกู้บริษัท</option>
            <option value="borrow">เงินยืม</option>
        </select>

        <!-- Filter by Status -->
        <select id="filterStatus" onchange="filterLoans()" 
                class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none text-slate-600">
            <option value="active">กำลังผ่อนชำระ (Active)</option>
            <option value="paid_off">ชำระหมดแล้ว (Paid off)</option>
            <option value="all">ทั้งหมด</option>
        </select>
    </div>

    <!-- Create Button -->
    <div class="flex items-center gap-2">
        <button onclick="openGlobalPaymentModal()" class="inline-flex items-center px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold border border-emerald-200 rounded-xl text-sm transition-all shadow-sm gap-1.5">
            <i class="fa-solid fa-plus"></i>
            <span>สร้างรายการชำระเงินกู้</span>
        </button>
        <button onclick="openLoanModal('add')" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition-all shadow-md shadow-blue-500/10 gap-1.5 self-start md:self-auto">
            <i class="fa-solid fa-plus-circle"></i>
            <span>สร้างสัญญาเงินกู้/เงินยืม</span>
        </button>
    </div>
</div>

<!-- Loans Table Card -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">พนักงาน</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ประเภท</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">เลขที่เอกสาร / สัญญา</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">วันที่กู้ยืม</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ยอดเงินทั้งหมด</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ยอดคงเหลือ</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">การผ่อนชำระ</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider text-right">การจัดการ</th>
                </tr>
            </thead>
            <tbody id="loansTableBody" class="divide-y divide-slate-100">
                <!-- Loaded dynamically -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Save Loan (Create / Edit) -->
<div id="loanModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-slate-900/40 backdrop-blur-sm" onclick="closeLoanModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 id="loanModalTitle" class="text-lg font-bold text-slate-800">สร้างสัญญาใหม่</h3>
                    <button onclick="closeLoanModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                
                <form id="loanForm" class="space-y-4">
                    <input type="hidden" id="loan_id" name="id">
                    
                    <!-- Employee Selector -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-500 mb-1.5">เลือกพนักงาน <span class="text-rose-500">*</span></label>
                        <select name="employee_id" id="loan_employee_id" required 
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                            <!-- Populated dynamically -->
                        </select>
                    </div>

                    <!-- Type Selection -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">ประเภทธุรกรรม</label>
                            <select name="type" id="loan_type" onchange="toggleLoanTypeFields(this.value)"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                                <option value="loan">เงินกู้บริษัท (ผ่อนชำระรายเดือน)</option>
                                <option value="borrow">เงินยืม (ชำระครั้งเดียว / ระบุจำนวนหัก)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">เลขที่สัญญา / เอกสาร <span class="text-rose-500">*</span></label>
                            <input type="text" name="contract_no" id="loan_contract_no" required placeholder="L-69001"
                                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                        </div>
                    </div>

                    <!-- Date & Amount -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">วันที่ทำสัญญา <span class="text-rose-500">*</span></label>
                            <input type="date" name="loan_date" id="loan_loan_date" required
                                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">จำนวนเงินกู้ยืม <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" name="amount" id="loan_amount" required placeholder="10000" onkeyup="calculateMonthlyDeduction()"
                                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm font-bold text-slate-800 outline-none transition-all">
                        </div>
                    </div>

                    <!-- Repayment Configuration -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Installment Count (Loans only) -->
                        <div id="grp_installments">
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">จำนวนงวดที่ต้องการผ่อน</label>
                            <input type="number" name="total_installments" id="loan_total_installments" placeholder="10" onkeyup="calculateMonthlyDeduction()"
                                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                        </div>

                        <!-- Monthly Deduction (Computed or set manually) -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5" id="lbl_monthly_deduction">หักเงินรายเดือน (บาท)</label>
                            <input type="number" step="0.01" name="monthly_deduction" id="loan_monthly_deduction" placeholder="1000"
                                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm font-bold text-slate-800 outline-none transition-all">
                            
                            <!-- Auto Deduct Checkbox -->
                            <label class="flex items-center gap-2 mt-3 p-2 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
                                <input type="checkbox" name="auto_deduct" id="loan_auto_deduct" value="1" checked
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm font-semibold text-slate-700">หักจากเงินเดือนอัตโนมัติ <br/><span class="text-xs text-slate-500 font-normal">(หากไม่เลือก จะต้องบันทึกชำระเงินสดเอง)</span></span>
                            </label>
                        </div>
                    </div>

                    <!-- Due Date (Borrows only) -->
                    <div id="grp_due_date" class="hidden">
                        <label class="block text-sm font-semibold text-slate-500 mb-1.5">กำหนดชำระคืนเต็มจำนวน</label>
                        <input type="date" name="due_date" id="loan_due_date"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                    </div>

                    <!-- Status Selection -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-500 mb-1.5">สถานะสัญญา</label>
                        <select name="status" id="loan_status"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                            <option value="active">กำลังผ่อนชำระ (Active)</option>
                            <option value="paid_off">ชำระเสร็จสิ้น (Paid off)</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 rounded-b-3xl border-t border-slate-100">
                <button type="button" onclick="closeLoanModal()" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">ยกเลิก</button>
                <button type="button" onclick="saveLoan()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm transition-all shadow-md shadow-blue-500/10">บันทึกสัญญา</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Repayment History -->
<div id="historyModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-slate-900/40 backdrop-blur-sm" onclick="closeHistoryModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">ประวัติการชำระคืนเงินกู้/ยืม</h3>
                        <p class="text-sm text-slate-400 mt-1" id="history-loan-subtitle">พนักงาน: ...</p>
                    </div>
                    <button onclick="closeHistoryModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                
                <div class="overflow-y-auto max-h-96 rounded-xl border border-slate-100">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 text-xs uppercase font-semibold">
                                <th class="px-4 py-3">วันที่ชำระ</th>
                                <th class="px-4 py-3">จำนวนเงิน</th>
                                <th class="px-4 py-3">ช่องทาง / งวดเดือน</th>
                                <th class="px-4 py-3">หมายเหตุ</th>
                                <th class="px-4 py-3 text-right">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody" class="divide-y divide-slate-100 text-sm text-slate-600">
                            <!-- Loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 flex items-center justify-end rounded-b-3xl border-t border-slate-100">
                <button type="button" onclick="closeHistoryModal()" class="px-4 py-2 bg-slate-800 text-white font-bold rounded-xl text-sm transition-all">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Manual Repayment (Cash) -->
<div id="paymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-slate-900/40 backdrop-blur-sm" onclick="closePaymentModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-slate-800">บันทึกชำระเงินคืนด้วยเงินสด</h3>
                    <button onclick="closePaymentModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                
                <form id="paymentForm" class="space-y-4">
                    <input type="hidden" id="pay_loan_id" name="loan_id">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-500 mb-1.5">จำนวนเงินชำระคืน (บาท) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="amount" id="pay_amount" required placeholder="0.00"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-sm font-bold text-slate-800 outline-none transition-all">
                        <span class="text-xs text-slate-400 mt-1 block" id="pay_remaining_lbl">ยอดค้างชำระทั้งหมด: 0.00 บาท</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">วันที่ชำระคืน <span class="text-rose-500">*</span></label>
                            <input type="date" name="payment_date" id="pay_date" required
                                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">หมายเหตุ</label>
                            <input type="text" name="note" id="pay_note" value="ชำระคืนด้วยเงินสด"
                                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 flex items-center justify-end gap-3 rounded-b-3xl border-t border-slate-100">
                <button type="button" onclick="closePaymentModal()" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">ยกเลิก</button>
                <button type="button" onclick="saveManualRepayment()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition-all shadow-md shadow-emerald-500/10">บันทึกรับชำระ</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Global Manual Repayment (Cash) -->
<div id="globalPaymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-slate-900/40 backdrop-blur-sm" onclick="closeGlobalPaymentModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-slate-800">รายการชำระเงินกู้</h3>
                    <button onclick="closeGlobalPaymentModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                
                <form id="globalPaymentForm" class="space-y-4">
                    <!-- Employee Selector -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-500 mb-1.5">เลือกพนักงาน <span class="text-rose-500">*</span></label>
                        <select id="gp_employee_id" required onchange="onGlobalEmployeeChange()"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                            <!-- Populated dynamically -->
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">ประเภทธุรกรรม</label>
                            <input type="text" value="ชำระด้วยเงินสด" readonly
                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-slate-500 text-sm font-bold outline-none cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">เลขที่สัญญา / เอกสาร <span class="text-rose-500">*</span></label>
                            <select name="loan_id" id="gp_loan_id" required onchange="onGlobalLoanChange()"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                                <option value="">-- เลือกสัญญา --</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">วันที่ชำระ <span class="text-rose-500">*</span></label>
                            <input type="date" name="payment_date" id="gp_payment_date" required
                                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-500 mb-1.5">จำนวนเงินกู้ยืม <span class="text-slate-400 font-normal">(ยอดรวม/คงเหลือ)</span></label>
                            <input type="text" id="gp_total_amount" readonly placeholder="0.00"
                                   class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-slate-500 text-sm font-bold outline-none cursor-not-allowed">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-500 mb-1.5">จำนวนเงินที่ต้องการชำระในงวดนี้ <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="amount" id="gp_amount" required placeholder="0.00"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm font-bold text-blue-600 outline-none transition-all text-center text-lg">
                    </div>
                    
                    <input type="hidden" name="note" value="ชำระคืนด้วยเงินสด">
                </form>
                
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-end justify-between">
                    <div>
                        <div class="text-xs text-slate-400 font-semibold mb-6">ลายเซ็นผู้รับเงิน</div>
                        <div class="w-48 border-b-2 border-slate-300"></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="closeGlobalPaymentModal()" class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">ยกเลิก</button>
                        <button type="button" onclick="saveGlobalManualRepayment()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm transition-all shadow-md shadow-blue-500/20">กดชำระเงิน</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let rawLoansList = [];

    function loadLoansTab() {
        // Load summary cards & list
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_loans' },
            success: function(data) {
                rawLoansList = data || [];
                renderLoansTable(rawLoansList);
                calculateLoansSummary(rawLoansList);
            }
        });
    }

    function calculateLoansSummary(list) {
        let totalLoanBalance = 0;
        let totalBorrowBalance = 0;
        let loanCount = 0;
        let borrowCount = 0;
        let activeCount = 0;
        let totalRepaid = 0;
        
        list.forEach(item => {
            let bal = parseFloat(item.remaining_balance) || 0;
            let orig = parseFloat(item.amount) || 0;
            if (item.status === 'active') {
                activeCount++;
                if (item.type === 'loan') {
                    totalLoanBalance += bal;
                    loanCount++;
                } else {
                    totalBorrowBalance += bal;
                    borrowCount++;
                }
            } else {
                if (item.type === 'loan') loanCount++;
                else borrowCount++;
            }
            totalRepaid += (orig - bal);
        });

        $('#summary-total-loan-balance').text(totalLoanBalance.toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' ฿');
        $('#summary-total-loan-count').text(loanCount + ' สัญญา');

        $('#summary-total-borrow-balance').text(totalBorrowBalance.toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' ฿');
        $('#summary-total-borrow-count').text(borrowCount + ' เอกสาร');

        $('#summary-total-repaid').text(totalRepaid.toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' ฿');

        $('#summary-active-count').text(activeCount + ' ราย');
        $('#summary-total-count').text('รวมทั้งหมด ' + list.length + ' รายการ');
    }

    function renderLoansTable(list) {
        let html = '';
        if (list.length > 0) {
            list.forEach((item, idx) => {
                let statusBadge = item.status === 'active'
                    ? `<span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 text-xs font-bold rounded-full border border-emerald-200"><i class="fa-solid fa-spinner mr-0.5"></i> กำลังผ่อนชำระ</span>`
                    : `<span class="px-2.5 py-1 bg-slate-100 text-slate-500 text-xs font-bold rounded-full border border-slate-200">ชำระเสร็จสิ้น</span>`;
                
                let typeBadge = item.type === 'loan'
                    ? `<span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-xs font-bold rounded-md border border-blue-100">เงินกู้บริษัท</span>`
                    : `<span class="px-2 py-0.5 bg-amber-50 text-amber-600 text-xs font-bold rounded-md border border-amber-100">เงินยืม</span>`;
                
                let avatarHtml = '';
                if (item.photo) {
                    avatarHtml = `<img src="../${item.photo}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0">`;
                } else {
                    avatarHtml = `<div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 font-bold text-sm flex items-center justify-center border border-slate-200 shadow-sm flex-shrink-0">${item.name.substring(0, 1)}</div>`;
                }

                let repaymentDetail = '';
                if (item.type === 'loan') {
                    let totalInst = item.total_installments ? ` / ${item.total_installments} งวด` : '';
                    repaymentDetail = `<div class="font-semibold text-slate-700">งวดละ: ${parseFloat(item.monthly_deduction).toLocaleString('th-TH')} ฿</div>
                                       <div class="text-xs text-slate-400 mt-0.5">${totalInst}</div>`;
                } else {
                    let dueStr = item.due_date ? formatThaiDate(item.due_date) : 'ไม่ได้ระบุ';
                    repaymentDetail = `<div class="font-semibold text-slate-700">หักเดือนละ: ${parseFloat(item.monthly_deduction).toLocaleString('th-TH')} ฿</div>
                                       <div class="text-xs text-rose-500 mt-0.5"><i class="fa-regular fa-clock mr-0.5"></i> กำหนดคืน: ${dueStr}</div>`;
                }

                let remainingPct = Math.round((parseFloat(item.remaining_balance) / parseFloat(item.amount)) * 100);
                remainingPct = isNaN(remainingPct) ? 0 : remainingPct;

                html += `
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center gap-3">
                            ${avatarHtml}
                            <div>
                                <div class="font-bold text-slate-800">${item.name}</div>
                                <div class="text-xs text-slate-400 mt-0.5">${item.emp_code} • ${item.position}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">${typeBadge}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-700">${item.contract_no}</td>
                    <td class="px-6 py-4 text-sm text-slate-500">${formatThaiDate(item.loan_date)}</td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-800">${parseFloat(item.amount).toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿</td>
                    <td class="px-6 py-4 text-sm">
                        <div class="font-bold text-slate-800">${parseFloat(item.remaining_balance).toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿</div>
                        <div class="w-24 bg-slate-100 h-1.5 rounded-full mt-1.5 overflow-hidden">
                            <div class="bg-blue-500 h-full rounded-full" style="width: ${100 - remainingPct}%"></div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">${repaymentDetail}</td>
                    <td class="px-6 py-4">${statusBadge}</td>
                    <td class="px-6 py-4 text-sm text-right space-x-1.5">
                        <button onclick="openRepaymentHistory(${item.id}, '${item.name} (${item.contract_no})')" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="ประวัติการผ่อนชำระ">
                            <i class="fa-solid fa-list-check"></i>
                        </button>
                        ${item.status === 'active' ? `
                            <button onclick="openManualRepayment(${item.id}, ${item.remaining_balance})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="รับชำระเป็นเงินสด">
                                <i class="fa-solid fa-cash-register"></i>
                            </button>
                        ` : ''}
                        <button onclick="openLoanModal('edit', ${item.id})" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-all" title="แก้ไข">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button onclick="deleteLoan(${item.id})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="ลบ">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
        } else {
            html = `
            <tr>
                <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center">
                        <i class="fa-solid fa-receipt text-3xl mb-2 opacity-30"></i>
                        <div>ไม่พบข้อมูลสัญญาเงินกู้/เงินยืมในระบบ</div>
                    </div>
                </td>
            </tr>`;
        }
        $('#loansTableBody').html(html);
    }

    function filterLoans() {
        let search = $('#loanSearch').val().toLowerCase();
        let type = $('#filterType').val();
        let status = $('#filterStatus').val();

        let filtered = rawLoansList.filter(item => {
            let matchSearch = item.name.toLowerCase().includes(search) || 
                              item.contract_no.toLowerCase().includes(search) || 
                              item.emp_code.toLowerCase().includes(search);
            
            let matchType = type === 'all' || item.type === type;
            let matchStatus = status === 'all' || item.status === status;

            return matchSearch && matchType && matchStatus;
        });

        renderLoansTable(filtered);
    }

    function toggleLoanTypeFields(val) {
        if (val === 'loan') {
            $('#grp_installments').removeClass('hidden');
            $('#grp_due_date').addClass('hidden');
            $('#lbl_monthly_deduction').text('หักเงินรายเดือน (บาท)');
            $('#loan_monthly_deduction').prop('readonly', true);
        } else {
            $('#grp_installments').addClass('hidden');
            $('#grp_due_date').removeClass('hidden');
            $('#lbl_monthly_deduction').text('หักชำระต่อเดือน (ถ้ามี)');
            $('#loan_monthly_deduction').prop('readonly', false);
        }
        calculateMonthlyDeduction();
    }

    function calculateMonthlyDeduction() {
        let type = $('#loan_type').val();
        if (type === 'loan') {
            let amount = parseFloat($('#loan_amount').val()) || 0;
            let installments = parseInt($('#loan_total_installments').val()) || 1;
            let monthly = Math.round((amount / installments) * 100) / 100;
            $('#loan_monthly_deduction').val(monthly);
        }
    }

    function openLoanModal(mode, id = null, defaultEmpId = null) {
        // Load employees select list
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_employees' },
            success: function(emps) {
                let html = '<option value="">-- เลือกพนักงาน --</option>';
                if (emps && emps.length > 0) {
                    emps.forEach(emp => {
                        html += `<option value="${emp.id}">${emp.emp_code} - ${emp.first_name} ${emp.last_name} (${emp.position})</option>`;
                    });
                }
                $('#loan_employee_id').html(html);

                // Set form state
                $('#loanForm')[0].reset();
                $('#loan_id').val('');
                $('#loan_loan_date').val(new Date().toISOString().split('T')[0]);
                $('#loan_status').val('active');
                toggleLoanTypeFields('loan');

                if (defaultEmpId) {
                    $('#loan_employee_id').val(defaultEmpId);
                }

                if (mode === 'add') {
                    $('#loanModalTitle').text('สร้างสัญญาเงินกู้ / เงินยืมใหม่');
                    $('#loanModal').removeClass('hidden');
                } else if (mode === 'edit' && id) {
                    $('#loanModalTitle').text('แก้ไขสัญญาเงินกู้ / เงินยืม');
                    
                    $.ajax({
                        url: 'payroll_action.php',
                        type: 'GET',
                        data: { action: 'get_loan', id: id },
                        success: function(res) {
                            if (res.status === 'error') {
                                Swal.fire('ผิดพลาด', res.message, 'error');
                                return;
                            }
                            $('#loan_id').val(res.id);
                            $('#loan_employee_id').val(res.employee_id);
                            $('#loan_type').val(res.type);
                            $('#loan_contract_no').val(res.contract_no);
                            $('#loan_loan_date').val(res.loan_date);
                            $('#loan_amount').val(res.amount);
                            $('#loan_total_installments').val(res.total_installments || '');
                            $('#loan_monthly_deduction').val(res.monthly_deduction);
                            $('#loan_due_date').val(res.due_date || '');
                            $('#loan_status').val(res.status);
                            $('#loan_auto_deduct').prop('checked', res.auto_deduct == 1);

                            toggleLoanTypeFields(res.type);
                            if (res.type === 'borrow') {
                                $('#loan_monthly_deduction').val(res.monthly_deduction);
                            }
                            $('#loanModal').removeClass('hidden');
                        }
                    });
                }
            }
        });
    }

    function closeLoanModal() {
        $('#loanModal').addClass('hidden');
    }

    function saveLoan() {
        const form = $('#loanForm')[0];
        if (!form.reportValidity()) return;

        const formData = $(form).serialize() + '&action=save_loan';
        
        $.ajax({
            url: 'payroll_action.php',
            type: 'POST',
            data: formData,
            success: function(res) {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                if (parsed.status === 'success') {
                    Swal.fire('สำเร็จ!', parsed.message, 'success');
                    closeLoanModal();
                    loadLoansTab();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', parsed.message, 'error');
                }
            }
        });
    }

    function deleteLoan(id) {
        Swal.fire({
            title: 'ยืนยันการลบสัญญา?',
            html: '<span class="text-base text-slate-500">การลบสัญญานี้จะลบประวัติการชำระเงินทั้งหมดที่เกี่ยวข้องออกอย่างถาวร</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ลบสัญญา',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'POST',
                    data: { action: 'delete_loan', id: id },
                    success: function(res) {
                        let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                        if (parsed.status === 'success') {
                            Swal.fire('ลบสำเร็จ!', parsed.message, 'success');
                            loadLoansTab();
                        } else {
                            Swal.fire('ผิดพลาด', parsed.message, 'error');
                        }
                    }
                });
            }
        });
    }

    // Repayment Log actions
    function openRepaymentHistory(loanId, name) {
        $('#history-loan-subtitle').text('พนักงาน: ' + name);
        loadRepaymentHistory(loanId);
    }

    function loadRepaymentHistory(loanId) {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_loan_payments', loan_id: loanId },
            success: function(data) {
                let html = '';
                if (data && data.length > 0) {
                    data.forEach(p => {
                        let channel = p.payroll_run_id 
                            ? `<span class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded text-xs font-semibold">หักเงินเดือน ${p.month_period}</span>`
                            : `<span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded text-xs font-semibold">เงินสด</span>`;
                        
                        html += `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-medium">${formatThaiDate(p.payment_date)}</td>
                            <td class="px-4 py-3 font-bold text-slate-800">${parseFloat(p.amount).toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿</td>
                            <td class="px-4 py-3">${channel}</td>
                            <td class="px-4 py-3 text-slate-400 italic">${p.note || '-'}</td>
                            <td class="px-4 py-3 text-right">
                                <button onclick="deleteRepayment(${p.id}, ${loanId})" class="p-1 text-rose-600 hover:bg-rose-50 rounded transition-all" title="ยกเลิกรายการชำระ">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = `<tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">ยังไม่มีประวัติการชำระเงินคืน</td></tr>`;
                }
                $('#historyTableBody').html(html);
                $('#historyModal').removeClass('hidden');
            }
        });
    }

    function closeHistoryModal() {
        $('#historyModal').addClass('hidden');
    }

    function openManualRepayment(loanId, remaining) {
        $('#pay_loan_id').val(loanId);
        $('#pay_amount').val(remaining);
        $('#pay_date').val(new Date().toISOString().split('T')[0]);
        $('#pay_note').val('ชำระคืนด้วยเงินสด');
        $('#pay_remaining_lbl').text('ยอดค้างชำระทั้งหมด: ' + remaining.toLocaleString('th-TH', { minimumFractionDigits: 2 }) + ' บาท');
        $('#paymentModal').removeClass('hidden');
    }

    function closePaymentModal() {
        $('#paymentModal').addClass('hidden');
    }

    function saveManualRepayment() {
        const form = $('#paymentForm')[0];
        if (!form.reportValidity()) return;

        const formData = $(form).serialize() + '&action=save_loan_payment';
        $.ajax({
            url: 'payroll_action.php',
            type: 'POST',
            data: formData,
            success: function(res) {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                if (parsed.status === 'success') {
                    Swal.fire('ชำระคืนสำเร็จ!', parsed.message, 'success');
                    closePaymentModal();
                    loadLoansTab();
                } else {
                    Swal.fire('ผิดพลาด', parsed.message, 'error');
                }
            }
        });
    }

    function deleteRepayment(id, loanId) {
        Swal.fire({
            title: 'ยกเลิกรายการชำระคืน?',
            html: '<span class="text-base text-slate-500">ยอดเงินคงเหลือของสัญญาจะถูกปรับเพิ่มกลับตามจำนวนชำระนี้</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ยกเลิกรายการชำระ',
            cancelButtonText: 'กลับ'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'POST',
                    data: { action: 'delete_loan_payment', id: id },
                    success: function(res) {
                        let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                        if (parsed.status === 'success') {
                            Swal.fire('ยกเลิกรายการสำเร็จ!', parsed.message, 'success');
                            loadRepaymentHistory(loanId); // reload history table
                            loadLoansTab(); // reload summary card / main table
                        } else {
                            Swal.fire('ผิดพลาด', parsed.message, 'error');
                        }
                    }
                });
            }
        });
    }

    // Global Payment Modal
    function openGlobalPaymentModal() {
        $('#globalPaymentForm')[0].reset();
        $('#gp_payment_date').val(new Date().toISOString().split('T')[0]);
        $('#gp_loan_id').html('<option value="">-- เลือกสัญญา --</option>');
        $('#gp_total_amount').val('');
        
        // Populate employee dropdown with unique active borrowers
        let activeLoans = rawLoansList.filter(l => l.status === 'active');
        let uniqueEmps = new Map();
        activeLoans.forEach(l => {
            if (!uniqueEmps.has(l.employee_id)) {
                uniqueEmps.set(l.employee_id, {
                    id: l.employee_id,
                    name: l.name,
                    code: l.emp_code
                });
            }
        });
        
        let empHtml = '<option value="">-- เลือกพนักงาน --</option>';
        uniqueEmps.forEach(emp => {
            empHtml += `<option value="${emp.id}">${emp.code} - ${emp.name}</option>`;
        });
        $('#gp_employee_id').html(empHtml);
        
        $('#globalPaymentModal').removeClass('hidden');
    }

    function onGlobalEmployeeChange() {
        let empId = $('#gp_employee_id').val();
        let loansHtml = '<option value="">-- เลือกสัญญา --</option>';
        $('#gp_total_amount').val('');
        $('#gp_amount').val('');

        if (empId) {
            let empLoans = rawLoansList.filter(l => l.status === 'active' && l.employee_id == empId);
            empLoans.forEach(l => {
                loansHtml += `<option value="${l.id}">${l.contract_no} (คงเหลือ: ${parseFloat(l.remaining_balance).toLocaleString('th-TH')} ฿)</option>`;
            });
        }
        $('#gp_loan_id').html(loansHtml);
    }

    function onGlobalLoanChange() {
        let loanId = $('#gp_loan_id').val();
        if (loanId) {
            let loan = rawLoansList.find(l => l.id == loanId);
            if (loan) {
                let amt = parseFloat(loan.amount).toLocaleString('th-TH', {minimumFractionDigits:2});
                let rem = parseFloat(loan.remaining_balance).toLocaleString('th-TH', {minimumFractionDigits:2});
                $('#gp_total_amount').val(`${amt} (เหลือ ${rem})`);
                
                // Auto fill the usual monthly deduction amount, bounded by remaining balance
                let suggested = Math.min(parseFloat(loan.monthly_deduction) || 0, parseFloat(loan.remaining_balance));
                if (suggested <= 0) suggested = parseFloat(loan.remaining_balance);
                $('#gp_amount').val(suggested > 0 ? suggested : '');
            }
        } else {
            $('#gp_total_amount').val('');
            $('#gp_amount').val('');
        }
    }

    function closeGlobalPaymentModal() {
        $('#globalPaymentModal').addClass('hidden');
    }

    function saveGlobalManualRepayment() {
        const form = $('#globalPaymentForm')[0];
        if (!form.reportValidity()) return;

        const formData = $(form).serialize() + '&action=save_loan_payment';
        $.ajax({
            url: 'payroll_action.php',
            type: 'POST',
            data: formData,
            success: function(res) {
                let parsed = typeof res === 'string' ? JSON.parse(res) : res;
                if (parsed.status === 'success') {
                    Swal.fire('ชำระคืนสำเร็จ!', parsed.message, 'success');
                    closeGlobalPaymentModal();
                    loadLoansTab();
                } else {
                    Swal.fire('ผิดพลาด', parsed.message, 'error');
                }
            }
        });
    }
</script>

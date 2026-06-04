<!-- Search & Department Filters -->
<div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
        <!-- Search bar -->
        <div class="relative w-full md:w-64">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-base"></i>
            </span>
            <input type="text" id="calcSearchInput" onkeyup="filterLeaveCalculations()" placeholder="ค้นหาชื่อพนักงาน..." 
                   class="block w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none transition-all text-slate-700">
        </div>
        
        <!-- Department Select -->
        <select id="calcDeptSelect" onchange="filterLeaveCalculations()"
                class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-sm outline-none font-medium text-slate-700 transition-all">
            <option value="">-- แผนกทั้งหมด --</option>
        </select>
    </div>

    <!-- Quick info badge -->
    <div class="text-sm font-semibold text-slate-500 bg-slate-100 border border-slate-200 px-4 py-2 rounded-xl">
        <i class="fa-solid fa-circle-info text-blue-500 mr-1.5"></i>
        <span>การลานับตามเหตุการณ์ในระบบบันทึกเวลาทำงานที่ผ่านมา</span>
    </div>
</div>

<!-- Leave Calculations Card -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">รหัสพนักงาน</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ชื่อ-นามสกุล</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ลากิจ</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ลาป่วย</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ลาพักร้อน</th>
                    <th class="px-6 py-4 text-sm font-semibold text-slate-500 uppercase tracking-wider">ลาอื่นๆ</th>
                </tr>
            </thead>
            <tbody id="calculationsTableBody" class="divide-y divide-slate-100">
                <!-- Loaded dynamically -->
            </tbody>
        </table>
    </div>
</div>

<script>
    let calculationsRawData = [];

    function loadLeaveCalculations() {
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'calculate_leaves' },
            success: function(data) {
                calculationsRawData = data;
                
                // Populate Department Dropdown
                let departments = new Set();
                data.forEach(function(row) {
                    if (row.department) {
                        departments.add(row.department);
                    }
                });
                
                let deptSelectHtml = '<option value="">-- แผนกทั้งหมด --</option>';
                departments.forEach(function(dept) {
                    deptSelectHtml += `<option value="${dept}">${dept}</option>`;
                });
                $('#calcDeptSelect').html(deptSelectHtml);

                renderCalculationsTable(data);
            }
        });
    }

    function renderCalculationsTable(data) {
        let html = '';
        if (data.length > 0) {
            data.forEach(function(emp) {
                // Calculate progress percentages
                let bizPct = emp.business_max > 0 ? Math.round((emp.business_used / emp.business_max) * 100) : 0;
                let sickPct = emp.sick_max > 0 ? Math.round((emp.sick_used / emp.sick_max) * 100) : 0;
                let annPct = emp.annual_max > 0 ? Math.round((emp.annual_used / emp.annual_max) * 100) : 0;
                let othPct = emp.other_max > 0 ? Math.round((emp.other_used / emp.other_max) * 100) : 0;

                // Color classes for progress bar based on percentage
                const getBarColor = (pct) => {
                    if (pct >= 90) return 'bg-rose-500';
                    if (pct >= 70) return 'bg-amber-500';
                    return 'bg-blue-600';
                };

                let avatarHtml = '';
                if (emp.photo) {
                    avatarHtml = `<img src="../${emp.photo}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0">`;
                } else {
                    let initials = emp.name ? emp.name.split(' ').map(n => n.charAt(0)).join('') : '';
                    avatarHtml = `<div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 font-bold text-sm flex items-center justify-center border border-blue-100 shadow-sm flex-shrink-0">${initials}</div>`;
                }

                html += `
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 text-sm font-bold text-slate-800">${emp.emp_code}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-700">
                        <div class="flex items-center gap-3">
                            ${avatarHtml}
                            <div>
                                <div>${emp.name}</div>
                                <div class="text-xs text-slate-400 mt-0.5">${emp.position} | ${emp.department}</div>
                            </div>
                        </div>
                    </td>
                    
                    <!-- Business Leave Column -->
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center justify-between font-semibold text-slate-700 mb-1.5">
                            <span>ใช้ไป ${emp.business_used} วัน</span>
                            <span class="text-slate-400">สูงสุด ${emp.business_max} วัน</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full ${getBarColor(bizPct)}" style="width: ${Math.min(bizPct, 100)}%"></div>
                        </div>
                    </td>

                    <!-- Sick Leave Column -->
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center justify-between font-semibold text-slate-700 mb-1.5">
                            <span>ใช้ไป ${emp.sick_used} วัน</span>
                            <span class="text-slate-400">สูงสุด ${emp.sick_max} วัน</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full ${getBarColor(sickPct)}" style="width: ${Math.min(sickPct, 100)}%"></div>
                        </div>
                    </td>

                    <!-- Annual Leave Column -->
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center justify-between font-semibold text-slate-700 mb-1.5">
                            <span>ใช้ไป ${emp.annual_used} วัน</span>
                            <span class="text-slate-400">สูงสุด ${emp.annual_max} วัน</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full ${getBarColor(annPct)}" style="width: ${Math.min(annPct, 100)}%"></div>
                        </div>
                    </td>

                    <!-- Other Leave Column -->
                    <td class="px-6 py-4 text-sm">
                        <div class="flex items-center justify-between font-semibold text-slate-700 mb-1.5">
                            <span>ใช้ไป ${emp.other_used} วัน</span>
                            <span class="text-slate-400">สูงสุด ${emp.other_max} วัน</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full ${getBarColor(othPct)}" style="width: ${Math.min(othPct, 100)}%"></div>
                        </div>
                    </td>
                </tr>`;
            });
        } else {
            html = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center">
                        <i class="fa-solid fa-calculator text-3xl mb-2 opacity-30"></i>
                        <div>ยังไม่มีข้อมูลเพื่อประมวลผลคำนวณวันลา</div>
                    </div>
                </td>
            </tr>`;
        }
        $('#calculationsTableBody').html(html);
    }

    function filterLeaveCalculations() {
        const search = $('#calcSearchInput').val().toLowerCase();
        const dept = $('#calcDeptSelect').val();
        
        let filtered = calculationsRawData.filter(function(emp) {
            let matchSearch = emp.name.toLowerCase().includes(search) || emp.emp_code.toLowerCase().includes(search);
            let matchDept = dept === '' || emp.department === dept;
            return matchSearch && matchDept;
        });

        renderCalculationsTable(filtered);
    }
</script>

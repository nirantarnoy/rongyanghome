<!-- Summary Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <!-- Card 1: Total Employees -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-5 hover:shadow-md transition-shadow">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <span class="text-xs text-slate-400 font-medium">พนักงานทั้งหมด</span>
            <h3 class="text-2xl font-bold text-slate-800 mt-1" id="dash-total-emp">0 คน</h3>
            <p class="text-[10px] text-emerald-500 font-semibold mt-1"><i class="fa-solid fa-circle-check"></i> สถานะปกติ</p>
        </div>
    </div>

    <!-- Card 2: Total Salary -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-5 hover:shadow-md transition-shadow">
        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
            <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div>
            <span class="text-xs text-slate-400 font-medium">เงินเดือนรวมทั้งหมด</span>
            <h3 class="text-xl font-bold text-slate-800 mt-1 truncate" id="dash-total-salary">0.00 บาท</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-1">อ้างอิงข้อมูลปัจจุบัน</p>
        </div>
    </div>

    <!-- Card 3: Pay Day -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-5 hover:shadow-md transition-shadow">
        <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div>
            <span class="text-xs text-slate-400 font-medium">กำหนดจ่ายเงินเดือน</span>
            <h3 class="text-2xl font-bold text-slate-800 mt-1" id="dash-pay-day">ทุกวันที่ 10</h3>
            <p class="text-[10px] text-slate-400 font-semibold mt-1">สามารถเปลี่ยนได้ในเมนูตั้งค่า</p>
        </div>
    </div>

    <!-- Card 4: Active Rate -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-5 hover:shadow-md transition-shadow">
        <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl">
            <i class="fa-solid fa-user-check"></i>
        </div>
        <div>
            <span class="text-xs text-slate-400 font-medium">มาทำงานวันนี้</span>
            <h3 class="text-2xl font-bold text-slate-800 mt-1" id="dash-attendance-count">0 คน</h3>
            <p class="text-[10px] text-purple-500 font-semibold mt-1" id="dash-attendance-rate">คิดเป็น 0% ของทั้งหมด</p>
        </div>
    </div>
</div>

<!-- Charts & Lists Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Chart Column (Doughnut) -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 lg:col-span-2 space-y-6">
        <div>
            <h4 class="font-bold text-slate-800 text-base">สัดส่วนการเข้าทำงานวันนี้</h4>
            <p class="text-xs text-slate-400 mt-0.5">แบ่งตามสถานะการเข้างานของพนักงานทั้งหมดในวันนี้</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
            <!-- Doughnut Chart Container -->
            <div class="relative w-48 h-48 mx-auto">
                <canvas id="attendanceChart"></canvas>
            </div>
            
            <!-- Details List -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="text-sm font-medium text-slate-600">มาทำงานปกติ</span>
                    </div>
                    <span class="font-bold text-slate-800" id="stat-normal">0 คน</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <span class="text-sm font-medium text-slate-600">มาสาย</span>
                    </div>
                    <span class="font-bold text-slate-800" id="stat-late">0 คน</span>
                </div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                        <span class="text-sm font-medium text-slate-600">ขาดงาน</span>
                    </div>
                    <span class="font-bold text-slate-800" id="stat-absent">0 คน</span>
                </div>
                <div class="flex items-center justify-between pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-sm font-medium text-slate-600">ลาหยุดงาน</span>
                    </div>
                    <span class="font-bold text-slate-800" id="stat-leave">0 คน</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Holidays List Column -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
        <div class="space-y-4">
            <div>
                <h4 class="font-bold text-slate-800 text-base">วันหยุดบริษัทประจำปี</h4>
                <p class="text-xs text-slate-400 mt-0.5">วันหยุดนักขัตฤกษ์และวันหยุดพิเศษ</p>
            </div>
            
            <!-- Holidays list wrapper -->
            <div class="space-y-3 overflow-y-auto max-h-[220px]" id="dash-holidays-list">
                <!-- Loaded via AJAX -->
                <div class="text-center py-6 text-slate-400 text-xs">
                    <i class="fa-solid fa-spinner animate-spin text-lg mb-2"></i>
                    <div>กำลังโหลดข้อมูล...</div>
                </div>
            </div>
        </div>
        
        <button onclick="switchTab('settings')" class="w-full mt-4 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 hover:border-slate-300 font-semibold rounded-xl text-xs transition-colors flex items-center justify-center gap-2">
            <span>จัดการวันหยุดระบบ</span>
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>
</div>

<script>
    let attendanceChart = null;

    function loadDashboardSummary() {
        // Load stats
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_dashboard_summary' },
            success: function(data) {
                $('#dash-total-emp').text(data.total_employees + ' คน');
                $('#dash-total-salary').text(formatCurrency(data.total_salary));
                
                let normal = data.attendance_today.normal || 0;
                let late = data.attendance_today.late || 0;
                let absent = data.attendance_today.absent || 0;
                let leave = data.attendance_today.leave || 0;
                
                let activeCount = normal + late;
                $('#dash-attendance-count').text(activeCount + ' คน');
                
                let rate = data.total_employees > 0 ? Math.round((activeCount / data.total_employees) * 100) : 0;
                $('#dash-attendance-rate').text(`คิดเป็น ${rate}% ของทั้งหมด`);
                
                $('#stat-normal').text(normal + ' คน');
                $('#stat-late').text(late + ' คน');
                $('#stat-absent').text(absent + ' คน');
                $('#stat-leave').text(leave + ' คน');

                // Render Chart
                renderAttendanceChart(normal, late, absent, leave);
            }
        });

        // Load pay_day setting
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'get_settings' },
            success: function(data) {
                if (data.pay_day) {
                    let formatted = data.pay_day.split(',').map(function(d) {
                        return d.trim() === 'L' ? 'สิ้นเดือน' : d.trim();
                    }).join(', ');
                    $('#dash-pay-day').text(`ทุกวันที่ ${formatted}`);
                } else {
                    $('#dash-pay-day').text('-');
                }
            }
        });

        // Load holidays
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_holidays' },
            success: function(data) {
                let html = '';
                if (data.length > 0) {
                    data.forEach(function(h) {
                        html += `
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex flex-col items-center justify-center leading-none">
                                    <span class="text-[10px] font-bold uppercase">${new Date(h.holiday_date).toLocaleString('th-TH', { month: 'short' })}</span>
                                    <span class="text-sm font-bold mt-0.5">${new Date(h.holiday_date).getDate()}</span>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-700">${h.name}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">${formatThaiDate(h.holiday_date)}</div>
                                </div>
                            </div>
                        </div>`;
                    });
                } else {
                    html = `
                    <div class="text-center py-8 text-slate-400 text-xs">
                        <i class="fa-solid fa-calendar-xmark text-2xl mb-2 opacity-30"></i>
                        <div>ยังไม่มีข้อมูลวันหยุดบริษัท</div>
                    </div>`;
                }
                $('#dash-holidays-list').html(html);
            }
        });
    }

    function renderAttendanceChart(normal, late, absent, leave) {
        let ctx = document.getElementById('attendanceChart').getContext('2d');
        
        // Destroy existing chart if it exists
        if (attendanceChart !== null) {
            attendanceChart.destroy();
        }

        // Draw center text custom plugin
        const centerTextPlugin = {
            id: 'centerText',
            beforeDraw: function(chart) {
                let width = chart.width,
                    height = chart.height,
                    ctx = chart.ctx;
                ctx.restore();
                let fontSize = (height / 114).toFixed(2);
                ctx.font = fontSize + "em Prompt, sans-serif";
                ctx.textBaseline = "middle";

                let text = (normal + late + absent + leave).toString();
                let textX = Math.round((width - ctx.measureText(text).width) / 2);
                let textY = height / 2;

                ctx.fillStyle = '#1e293b';
                ctx.font = "bold " + fontSize + "em Prompt";
                ctx.fillText(text, textX, textY - 8);

                ctx.fillStyle = '#64748b';
                ctx.font = "500 0.65em Prompt";
                let labelText = "คนทั้งหมด";
                let labelX = Math.round((width - ctx.measureText(labelText).width) / 2);
                ctx.fillText(labelText, labelX, textY + 12);
                ctx.save();
            }
        };

        attendanceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['มาทำงานปกติ', 'มาสาย', 'ขาดงาน', 'ลาหยุดงาน'],
                datasets: [{
                    data: [normal, late, absent, leave],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#3b82f6'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                cutout: '75%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            },
            plugins: [centerTextPlugin]
        });
    }
</script>

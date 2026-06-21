<?php
require_once '../auth_check.php';
require_once '../config.php';

$allowed_modules = isset($_SESSION['allowed_modules']) ? explode(',', $_SESSION['allowed_modules']) : [];
$user_role = $_SESSION['user_role'] ?? 'user';
$is_admin = ($user_role === 'admin');

if (!$is_admin && !in_array('payroll', $allowed_modules)) {
    header("Location: ../index.php");
    exit();
}

$company_id = $_SESSION['company_id'] ?? 1;

// Fetch company details for payroll documents
$comp_query = mysqli_query($conn, "SELECT * FROM company WHERE id = $company_id");
$company_info = mysqli_fetch_assoc($comp_query);
$company_name = $company_info['company_name'] ?? 'บริษัท ----';
$company_address = $company_info['address'] ?? '---';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll - ระบบจัดการเงินเดือน</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="../assets/js/tailwindcss.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery & SweetAlert2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8fafc; /* slate-50 */
        }
        .active-tab {
            background-color: #1e3a8a; /* deep blue */
            border-left: 4px solid #3b82f6; /* bright blue accent */
            color: #ffffff !important;
        }
        .sidebar-link {
            transition: all 0.2s;
            color: #94a3b8; /* slate-400 */
        }
        .sidebar-link:hover {
            background-color: #1e293b; /* slate-800 */
            color: #f1f5f9; /* slate-100 */
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        <!-- LEFT SIDEBAR -->
        <div class="w-64 bg-[#0f172a] text-slate-300 flex flex-col justify-between shadow-xl flex-shrink-0 z-20">
            <div>
                <!-- Brand Header -->
                <div class="p-6 border-b border-slate-800 flex items-center gap-3">
                    <div class="bg-blue-600 p-2.5 rounded-xl text-white shadow-lg shadow-blue-500/20">
                        <i class="fa-solid fa-wallet text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-white text-xl tracking-tight leading-none">Pell Payroll</h2>
                        <span class="text-sm text-slate-500 font-medium">ระบบจ่ายเงินเดือน</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="px-4 py-6 space-y-1.5">
                    <button onclick="switchTab('dashboard')" id="btn-dashboard" class="sidebar-link active-tab flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-chart-line w-5"></i>
                        <span>แดชบอร์ด</span>
                    </button>
                    
                    <button onclick="switchTab('employees')" id="btn-employees" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-users w-5"></i>
                        <span>ประวัติพนักงาน</span>
                    </button>

                    <button onclick="switchTab('attendance')" id="btn-attendance" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-clock-rotate-left w-5"></i>
                        <span>บันทึกการเข้าทำงาน</span>
                    </button>

                    <button onclick="switchTab('calculations')" id="btn-calculations" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-calculator w-5"></i>
                        <span>แฟ้มคำนวณวันลา</span>
                    </button>

                    <button onclick="switchTab('payroll_calc')" id="btn-payroll_calc" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-money-check-dollar w-5"></i>
                        <span>คำนวณเงินเดือน</span>
                    </button>

                    <button onclick="switchTab('adjustments')" id="btn-adjustments" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-tags w-5"></i>
                        <span>เงินเพิ่ม - เงินหัก (Master)</span>
                    </button>

                    <button onclick="switchTab('loans')" id="btn-loans" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-hand-holding-dollar w-5"></i>
                        <span>เงินกู้ / เงินยืมพนักงาน</span>
                    </button>

                    <button onclick="switchTab('settings')" id="btn-settings" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-sliders w-5"></i>
                        <span>ตั้งค่าระบบ</span>
                    </button>

                    <div class="h-px bg-slate-800 my-2"></div>
                    <div class="px-4 py-1 text-xs font-bold text-slate-500 uppercase tracking-wider">ระบบค่าคอมมิชชั่น</div>

                    <button onclick="switchTab('commission_rates')" id="btn-commission_rates" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-percent w-5"></i>
                        <span>อัตราค่าคอมมิชชั่น</span>
                    </button>

                    <button onclick="switchTab('commission_calc')" id="btn-commission_calc" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-calculator w-5"></i>
                        <span>คิดค่าคอมแบบเหมารายเดือน</span>
                    </button>

                    <button onclick="switchTab('commission_history')" id="btn-commission_history" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-clock-rotate-left w-5"></i>
                        <span>ประวัติค่าคอมแบบเหมารายเดือน</span>
                    </button>

                    <button onclick="switchTab('commission_piece_calc')" id="btn-commission_piece_calc" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-file-invoice-dollar w-5"></i>
                        <span>คิดค่าคอมรายชิ้น (นอกแพลตฟอร์ม)</span>
                    </button>

                    <button onclick="switchTab('commission_piece_history')" id="btn-commission_piece_history" class="sidebar-link flex items-center gap-3 w-full px-4 py-3 rounded-xl text-base font-medium text-left">
                        <i class="fa-solid fa-clock-rotate-left w-5"></i>
                        <span>ประวัติค่าคอมรายชิ้น</span>
                    </button>
                </div>
            </div>

            <!-- Sidebar Footer & Logged User info -->
            <div class="p-4 border-t border-slate-800 space-y-4">
                <div class="bg-slate-900/60 p-3.5 rounded-xl border border-slate-800/50">
                    <div class="text-xs text-slate-500 font-bold uppercase tracking-wider">ข้อมูลบริษัท</div>
                    <div class="text-base font-bold text-white mt-1 truncate"><?= htmlspecialchars($_SESSION['company_name'] ?? 'บริษัท รงยาง โฮม จำกัด') ?></div>
                    <div class="text-sm text-slate-400 mt-0.5">ปีทำงาน: <?= $_SESSION['active_year'] ?? date('Y') ?></div>
                </div>

                <div class="space-y-2">
                    <a href="../index.php" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                        <i class="fa-solid fa-house"></i>
                        <span>กลับระบบหลัก</span>
                    </a>
                    <a href="../logout.php" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-xl text-sm font-semibold text-red-400 hover:text-red-300 hover:bg-red-950/30 transition-colors">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                </div>
                <div class="text-center text-xs text-slate-600 mt-2">Version 1.0.0</div>
            </div>
        </div>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- TOP BAR -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 flex-shrink-0 z-10">
                <div class="flex items-center gap-2">
                    <h2 id="page-title" class="text-2xl font-bold text-slate-800">แดชบอร์ด</h2>
                    <span class="text-slate-300">|</span>
                    <span class="text-base text-slate-500" id="page-subtitle">ภาพรวมระบบเงินเดือน</span>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Date/Time Display -->
                    <div class="hidden md:flex items-center gap-2 text-base text-slate-600 bg-slate-50 px-4 py-1.5 rounded-full border border-slate-200">
                        <i class="fa-solid fa-calendar-day text-slate-400"></i>
                        <span class="font-medium" id="header-date"><?= date('d M Y') ?></span>
                    </div>

                    <!-- User Account Dropdown -->
                    <div class="flex items-center gap-3 pl-6 border-l border-slate-200">
                        <div class="text-right hidden sm:block">
                            <div class="text-base font-bold text-slate-800 leading-tight"><?= htmlspecialchars($_SESSION['user_login'] ?? 'แอดมิน') ?></div>
                            <div class="text-xs text-slate-500 font-semibold uppercase mt-0.5"><?= htmlspecialchars($_SESSION['user_role'] ?? 'Administrator') ?></div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-700 font-bold text-lg shadow-sm">
                            <?= strtoupper(substr($_SESSION['user_login'] ?? 'A', 0, 1)) ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- SWITCHABLE TABS WRAPPER -->
            <main class="flex-1 overflow-y-auto bg-slate-50 p-8">
                <!-- TAB: DASHBOARD -->
                <div id="tab-dashboard" class="tab-pane space-y-6">
                    <?php include 'tabs/dashboard.php'; ?>
                </div>

                <!-- TAB: EMPLOYEES -->
                <div id="tab-employees" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/employees.php'; ?>
                </div>

                <!-- TAB: ATTENDANCE -->
                <div id="tab-attendance" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/attendance.php'; ?>
                </div>

                <!-- TAB: CALCULATIONS -->
                <div id="tab-calculations" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/calculations.php'; ?>
                </div>

                <!-- TAB: PAYROLL CALCULATION -->
                <div id="tab-payroll_calc" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/payroll_calc.php'; ?>
                </div>

                <!-- TAB: ADJUSTMENTS -->
                <div id="tab-adjustments" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/adjustments.php'; ?>
                </div>

                <!-- TAB: LOANS & BORROWS -->
                <div id="tab-loans" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/loans.php'; ?>
                </div>

                <!-- TAB: SETTINGS -->
                <div id="tab-settings" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/settings.php'; ?>
                </div>

                <!-- TAB: COMMISSION RATES -->
                <div id="tab-commission_rates" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/commission_rates.php'; ?>
                </div>

                <!-- TAB: COMMISSION CALCULATION -->
                <div id="tab-commission_calc" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/commission_calc.php'; ?>
                </div>

                <!-- TAB: COMMISSION HISTORY -->
                <div id="tab-commission_history" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/commission_history.php'; ?>
                </div>

                <!-- TAB: COMMISSION PIECE CALCULATION -->
                <div id="tab-commission_piece_calc" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/commission_piece_calc.php'; ?>
                </div>

                <!-- TAB: COMMISSION PIECE HISTORY -->
                <div id="tab-commission_piece_history" class="tab-pane hidden space-y-6">
                    <?php include 'tabs/commission_piece_history.php'; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Switch Tab Script -->
    <script>
        function switchTab(tabId) {
            // Hide all tab panes
            $('.tab-pane').addClass('hidden');
            // Show selected tab pane
            $('#tab-' + tabId).removeClass('hidden');

            // Deactivate all sidebar buttons
            $('.sidebar-link').removeClass('active-tab');
            // Activate selected sidebar button
            $('#btn-' + tabId).addClass('active-tab');

            // Update header title
            let title = 'แดชบอร์ด';
            let subtitle = 'ภาพรวมระบบเงินเดือน';
            switch(tabId) {
                case 'dashboard':
                    title = 'แดชบอร์ด';
                    subtitle = 'ภาพรวมระบบเงินเดือน';
                    loadDashboardSummary();
                    break;
                case 'employees':
                    title = 'ประวัติพนักงาน';
                    subtitle = 'จัดการข้อมูลพื้นฐานพนักงาน';
                    loadEmployeesList();
                    break;
                case 'attendance':
                    title = 'บันทึกการเข้าทำงาน';
                    subtitle = 'ลงเวลาเข้า-ออก ขาด ลา มาสาย';
                    loadAttendanceList();
                    break;
                case 'calculations':
                    title = 'แฟ้มคำนวณวันลา';
                    subtitle = 'ตรวจสอบและคำนวณประวัติการลาสะสม';
                    loadLeaveCalculations();
                    break;
                case 'payroll_calc':
                    title = 'คำนวณเงินเดือน';
                    subtitle = 'คำนวณค่าจ้างประจำเดือน พิมพ์สลิป และดูยอดสรุป';
                    loadPayrollCalc();
                    break;
                case 'adjustments':
                    title = 'เงินเพิ่ม - เงินหัก (Master)';
                    subtitle = 'จัดการรายการเงินเพิ่มและเงินหักสำหรับพนักงาน';
                    loadAdjustmentsTable();
                    break;
                case 'loans':
                    title = 'เงินกู้ / เงินยืมพนักงาน';
                    subtitle = 'จัดการสัญญาเงินกู้และเงินยืมพนักงาน บันทึกการหักชำระและประวัติ';
                    loadLoansTab();
                    break;
                case 'settings':
                    title = 'ตั้งค่าระบบ';
                    subtitle = 'กำหนดวันหยุดประจำปีและข้อมูลทั่วไป';
                    loadSettings();
                    break;
                case 'commission_rates':
                    title = 'อัตราค่าคอมมิชชั่น';
                    subtitle = 'จัดการอัตราการจ่ายเปอร์เซ็นต์ค่าคอมมิชชั่น';
                    loadCommissionSettings();
                    break;
                case 'commission_calc':
                    title = 'คิดค่าคอมแบบเหมารายเดือน';
                    subtitle = 'คำนวณและปันส่วนค่าคอมมิชชั่นเฟอร์นิเจอร์ตามยอดขาย';
                    loadCommissionCalc();
                    break;
                case 'commission_history':
                    title = 'ประวัติค่าคอมแบบเหมารายเดือน';
                    subtitle = 'เรียกดู แก้ไข หรือตรวจสอบรายละเอียดค่าคอมมิชชั่นย้อนหลัง';
                    loadCommissionHistory();
                    break;
                case 'commission_piece_calc':
                    title = 'คิดค่าคอมรายชิ้น (นอกแพลตฟอร์ม)';
                    subtitle = 'คำนวณและปันส่วนค่าคอมมิชชั่นเฟอร์นิเจอร์แบบรายชิ้นให้แอดมิน, เซลล์, ผู้ช่วย';
                    loadCommissionPieceCalc();
                    break;
                case 'commission_piece_history':
                    title = 'ประวัติค่าคอมรายชิ้น';
                    subtitle = 'เรียกดู แก้ไข หรือตรวจสอบรายละเอียดค่าคอมมิชชั่นแบบรายชิ้นย้อนหลัง';
                    loadCommissionPieceHistory();
                    break;
            }
            $('#page-title').text(title);
            $('#page-subtitle').text(subtitle);
        }

        // Format Date Thai helper
        function formatThaiDate(dateStr) {
            if (!dateStr) return '-';
            const months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            const day = d.getDate();
            const month = months[d.getMonth()];
            const year = d.getFullYear() + 543;
            return `${day} ${month} ${year}`;
        }

        // Format Currency Helper
        function formatCurrency(amount) {
            return parseFloat(amount).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท';
        }

        $(document).ready(function() {
            // Initial Dashboard load
            loadDashboardSummary();
            
            // Format today's date in header in Thai
            const today = new Date();
            const thaiMonthsFull = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
            $('#header-date').text(`${today.getDate()} ${thaiMonthsFull[today.getMonth()]} ${today.getFullYear() + 543}`);
        });
    </script>
</body>
</html>

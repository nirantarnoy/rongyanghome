<?php
require '../auth_check.php';
include 'db.php';

$view = $_GET['view'] ?? 'overview';
$active_year = $_SESSION['active_year'] ?? date('Y');
$display_year = (int)$active_year + 543; // convert to Buddhist Era

// Fetch project list for dropdowns
$proj_sql = "SELECT id, project_name, project_code FROM projects_list WHERE module_type = 1 AND company_id = {$_SESSION['company_id']} ORDER BY id DESC";
$proj_res = mysqli_query($conn, $proj_sql);
$all_projects = [];
while ($row = mysqli_fetch_assoc($proj_res)) {
    $all_projects[] = $row;
}

// Fetch subcontractor list for dropdowns
$sub_sql = "SELECT id, name, team_type FROM subcontractors WHERE company_id = {$_SESSION['company_id']} ORDER BY name ASC";
$sub_res = mysqli_query($conn, $sub_sql);
$all_subcontractors = [];
while ($row = mysqli_fetch_assoc($sub_res)) {
    $all_subcontractors[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบผู้รับเหมาและโปรเจค - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8fafc; /* slate-50 */
        }
        
        /* Select2 Tailwind customization */
        .select2-container .select2-selection--single {
            height: 42px !important;
            border-radius: 0.75rem !important;
            border: 1px solid #e2e8f0 !important;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #334155 !important;
            font-size: 0.875rem !important;
            line-height: 1.25rem !important;
            padding-left: 1rem !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #10b981 !important;
        }

        @media print {
            .hide-on-print {
                display: none !important;
            }
            .hide-checkbox-on-print {
                display: none !important;
            }
            /* Hide UI elements that are not part of the report */
            #sidebar, .md\\:hidden.bg-slate-900, header, .custom-card button {
                display: none !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
            }
            .custom-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }
        }
        
        .sidebar {
            background-color: #0f172a; /* slate-900 */
        }

        .active-menu {
            background-color: #1e293b; /* slate-800 */
            border-left: 4px solid #10b981; /* emerald-500 */
            color: #10b981;
        }

        .custom-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.02);
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease-in-out;
        }

        .custom-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }

        .progress-ring__circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row">

    <!-- Mobile Header/Nav -->
    <div class="md:hidden bg-slate-900 text-white p-4 flex justify-between items-center shadow-md">
        <div class="flex items-center gap-2">
            <span class="text-2xl">👷</span>
            <span class="font-bold text-xl">RONGYANG HOME</span>
        </div>
        <button id="mobile-menu-btn" class="text-white focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>
    </div>

    <!-- Sidebar Layout -->
    <aside id="sidebar" class="sidebar text-slate-300 w-full md:w-64 flex-shrink-0 flex flex-col justify-between min-h-screen hidden md:flex">
        <div>
            <!-- Logo Section -->
            <div class="p-6 border-b border-slate-800 flex items-center gap-3">
                <div class="bg-emerald-500 text-white p-2 rounded-xl shadow-md">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-white text-xl leading-tight">Rongyang Home</h2>
                    <span class="text-sm text-slate-500">Subcontractor System</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-1">
                <a href="../index.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>หน้าหลักระบบ</span>
                </a>

                <div class="pt-4 pb-2 px-4 text-sm font-bold text-slate-600 uppercase tracking-wider">
                    ผู้รับเหมา / โปรเจค
                </div>

                <a href="index.php?view=overview" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-lg font-medium" <?= $view === 'overview' ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path>
                    </svg>
                    <span>ภาพรวม</span>
                </a>

                <a href="index.php?view=subcontractors" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-lg font-medium" <?= $view === 'subcontractors' ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>ผู้รับเหมา</span>
                </a>

                <a href="index.php?view=projects" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-lg font-medium" <?= ($view === 'projects' || $view === 'project_detail') ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                    </svg>
                    <span>โปรเจค / งาน</span>
                </a>

                <a href="index.php?view=payments" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-lg font-medium" <?= ($view === 'payments' || $view === 'new_payment') ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>การจ่ายเงิน</span>
                </a>

                <a href="index.php?view=expenses" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-lg font-medium" <?= $view === 'expenses' ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>ค่าใช้จ่ายเพิ่มเติม</span>
                </a>

                <a href="index.php?view=cost_report" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-lg font-medium" <?= $view === 'cost_report' ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>รายงานต้นทุน</span>
                </a>


                <a href="index.php?view=settings" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all text-lg font-medium" <?= $view === 'settings' ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>การตั้งค่าสถานะงาน</span>
                </a>
            </nav>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-slate-800 text-sm text-slate-500">

            <p class="mt-2">Version 1.0.0</p>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0">
        <!-- Top bar -->
        <header class="bg-white border-b border-slate-100 px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <nav class="flex text-sm text-slate-400 gap-2 mb-1">
                    <a href="index.php?view=overview" class="hover:text-slate-600">หน้าหลัก</a>
                    <span>/</span>
                    <span class="text-slate-600 font-medium">
                        <?php
                        switch($view) {
                            case 'overview': echo 'ภาพรวม'; break;
                            case 'subcontractors': echo 'ผู้รับเหมา'; break;
                            case 'projects': echo 'โปรเจค / งาน'; break;
                            case 'project_detail': echo 'รายละเอียดโปรเจค'; break;
                            case 'payments': echo 'ประวัติการจ่ายเงิน'; break;
                            case 'new_payment': echo 'บันทึกการจ่ายเงิน'; break;
                            case 'expenses': echo 'ค่าใช้จ่ายเพิ่มเติม'; break;
                            case 'cost_report': echo 'รายงานต้นทุน'; break;
                            case 'settings': echo 'การตั้งค่าสถานะงาน'; break;
                            case 'settings': echo 'การตั้งค่าสถานะงาน'; break;
                        }
                        ?>
                    </span>
                </nav>
                <h1 class="text-xl font-bold text-slate-800">
                    <?php
                    switch($view) {
                        case 'overview': echo 'ภาพรวมผู้รับเหมาและโปรเจค'; break;
                        case 'subcontractors': echo 'รายชื่อผู้รับเหมา'; break;
                        case 'projects': echo 'โครงการทั้งหมด'; break;
                        case 'project_detail': echo 'รายละเอียดโครงการ'; break;
                        case 'payments': echo 'ประวัติการจ่ายเงินผู้รับเหมา'; break;
                        case 'new_payment': echo 'การจ่ายเงินผู้รับเหมา (ใหม่)'; break;
                        case 'expenses': echo 'ค่าใช้จ่ายเพิ่มเติมของโครงการ'; break;
                        case 'cost_report': echo 'รายงานสรุปต้นทุนโครงการ'; break;
                    }
                    ?>
                </h1>
            </div>

            <!-- Header Action Info -->
            <div class="flex items-center gap-4">
                <!-- Date selector -->
                <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-2 flex items-center gap-2 text-base text-slate-600">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">ปีงบประมาณ <?=$display_year?></span>
                </div>

                <!-- User profile -->
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-base font-bold text-slate-800"><?= $_SESSION['user_login'] ?></p>
                        <span class="text-sm text-slate-400 capitalize"><?= $_SESSION['user_role'] ?? 'User' ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-xl shadow-sm border border-emerald-500/20">
                        <?= mb_substr($_SESSION['user_login'], 0, 1) ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- View Content Router Container -->
        <div class="flex-1 p-6 space-y-6">

            <?php if ($view === 'overview'): ?>
                <!-- ================== VIEW: OVERVIEW ================== -->
                <!-- Stats Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div class="custom-card p-5 flex items-center gap-4">
                        <div class="bg-blue-500/10 text-blue-600 p-4 rounded-2xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm text-slate-400 font-medium">ผู้รับเหมาทั้งหมด</span>
                            <h3 id="stat-subcontractors" class="text-xl font-bold text-slate-800 mt-1">0 ราย</h3>
                            <span id="stat-subcontractors-working" class="text-sm text-blue-500">กำลังทำงาน 0 ราย</span>
                        </div>
                    </div>

                    <div class="custom-card p-5 flex items-center gap-4">
                        <div class="bg-emerald-500/10 text-emerald-600 p-4 rounded-2xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm text-slate-400 font-medium">โปรเจ็คกำลังดำเนินการ</span>
                            <h3 id="stat-projects" class="text-xl font-bold text-slate-800 mt-1">0 โปรเจค</h3>
                            <span id="stat-projects-value" class="text-sm text-emerald-500">มูลค่ารวม 0.00 บาท</span>
                        </div>
                    </div>

                    <div class="custom-card p-5 flex items-center gap-4">
                        <div class="bg-amber-500/10 text-amber-600 p-4 rounded-2xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm text-slate-400 font-medium">มูลค่างานรวมทั้งหมด</span>
                            <h3 id="stat-total-value" class="text-xl font-bold text-slate-800 mt-1">0.00 บาท</h3>
                            <span id="stat-total-projects" class="text-sm text-amber-500">จาก 0 โปรเจค</span>
                        </div>
                    </div>

                    <div class="custom-card p-5 flex items-center gap-4">
                        <div class="bg-teal-500/10 text-teal-600 p-4 rounded-2xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm text-slate-400 font-medium">จ่ายแล้วรวม</span>
                            <h3 id="stat-paid" class="text-xl font-bold text-slate-800 mt-1">0.00 บาท</h3>
                            <span id="stat-paid-percent" class="text-sm text-teal-600">คิดเป็น 0%</span>
                        </div>
                    </div>

                    <div class="custom-card p-5 flex items-center gap-4">
                        <div class="bg-rose-500/10 text-rose-600 p-4 rounded-2xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm text-slate-400 font-medium">คงเหลือที่ต้องจ่าย</span>
                            <h3 id="stat-remaining" class="text-xl font-bold text-slate-800 mt-1">0.00 บาท</h3>
                            <span id="stat-remaining-percent" class="text-sm text-rose-600">คิดเป็น 0%</span>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Recent Payments -->
                    <div class="custom-card p-6 lg:col-span-2 space-y-6">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <span class="text-xl">💸</span> ประวัติการจ่ายค่าแรงผู้รับเหมาล่าสุด
                            </h3>
                            <a href="index.php?view=payments" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">ดูทั้งหมด</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-base text-slate-600">
                                <thead>
                                    <tr class="text-slate-400 text-sm uppercase border-b border-slate-100">
                                        <th class="py-3 font-semibold">เลขที่เอกสาร</th>
                                        <th class="py-3 font-semibold">ผู้รับเหมา</th>
                                        <th class="py-3 font-semibold">โปรเจค</th>
                                        <th class="py-3 font-semibold text-right">จำนวนเงินสุทธิ</th>
                                        <th class="py-3 font-semibold text-center">วันที่จ่าย</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-payments-list">
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-slate-400 italic">กำลังโหลดข้อมูล...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right: Project Progress Summary -->
                    <div class="custom-card p-6 space-y-6">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <span class="text-xl">📊</span> ความคืบหน้าโครงการ
                            </h3>
                            <a href="index.php?view=projects" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">จัดการโครงการ</a>
                        </div>
                        <div id="project-progress-list" class="space-y-4 max-h-[350px] overflow-y-auto pr-1">
                            <div class="text-center py-10 text-slate-400 italic">กำลังโหลดข้อมูล...</div>
                        </div>
                    </div>
                </div>

                <script>
                    $(document).ready(function() {
                        loadOverviewData();
                    });

                    function loadOverviewData() {
                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'get_overview_stats' },
                            success: function(res) {
                                if (res.status === 'success') {
                                    const s = res.stats;
                                    $('#stat-subcontractors').text(s.subcontractors_total + ' ราย');
                                    $('#stat-subcontractors-working').text('กำลังทำงาน ' + s.subcontractors_working + ' ราย');
                                    $('#stat-projects').text(s.projects_active + ' โปรเจค');
                                    $('#stat-projects-value').text('มูลค่ารวม ' + s.projects_active_value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                                    $('#stat-total-value').text(s.projects_total_value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                                    $('#stat-total-projects').text('จาก ' + s.projects_total + ' โปรเจค');
                                    $('#stat-paid').text(s.total_paid.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                                    $('#stat-paid-percent').text('คิดเป็น ' + s.paid_percent + '%');
                                    $('#stat-remaining').text(s.total_remaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                                    $('#stat-remaining-percent').text('คิดเป็น ' + s.remaining_percent + '%');
                                }
                            }
                        });

                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'payment_list' },
                            success: function(res) {
                                if (res.status === 'success') {
                                    let html = '';
                                    const payments = res.data.slice(0, 5); // get top 5
                                    if (payments.length === 0) {
                                        html = '<tr><td colspan="5" class="py-6 text-center text-slate-400 italic">ไม่มีข้อมูลการจ่ายเงิน</td></tr>';
                                    } else {
                                        payments.forEach(p => {
                                            html += `
                                                <tr class="border-b border-slate-50 hover:bg-slate-50 transition-all">
                                                    <td class="py-3 font-semibold text-slate-700">${p.payment_number}</td>
                                                    <td class="py-3">
                                                        <p class="font-medium text-slate-800">${p.contractor_name}</p>
                                                        <span class="text-sm text-slate-400">${p.contractor_team}</span>
                                                    </td>
                                                    <td class="py-3 text-slate-500">${p.project_name}</td>
                                                    <td class="py-3 text-right font-bold text-emerald-600">${p.net_amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ฿</td>
                                                    <td class="py-3 text-center text-slate-500">${p.payment_date}</td>
                                                </tr>
                                            `;
                                        });
                                    }
                                    $('#recent-payments-list').html(html);
                                }
                            }
                        });

                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'project_list' },
                            success: function(res) {
                                if (res.status === 'success') {
                                    let html = '';
                                    const projects = res.data.slice(0, 5); // get top 5
                                    if (projects.length === 0) {
                                        html = '<div class="text-center py-6 text-slate-400 italic">ไม่มีโครงการในระบบ</div>';
                                    } else {
                                        projects.forEach(p => {
                                            html += `
                                                <div class="space-y-1">
                                                    <div class="flex justify-between text-base">
                                                        <a href="index.php?view=project_detail&id=${p.id}" class="font-bold text-slate-700 hover:text-emerald-600 transition-colors">${p.project_name}</a>
                                                        <span class="font-semibold text-slate-500">${p.progress_percent}%</span>
                                                    </div>
                                                    <div class="w-full bg-slate-100 rounded-full h-2">
                                                        <div class="bg-emerald-500 h-2 rounded-full" style="width: ${p.progress_percent}%"></div>
                                                    </div>
                                                    <div class="flex justify-between text-xs text-slate-400">
                                                        <span>ผู้รับเหมาหลัก: ${p.contractor_name || '-'}</span>
                                                        <span>คงเหลือ: ${p.remaining_installments.toLocaleString()} ฿</span>
                                                    </div>
                                                </div>
                                            `;
                                        });
                                    }
                                    $('#project-progress-list').html(html);
                                }
                            }
                        });
                    }
                </script>

            <?php elseif ($view === 'subcontractors'): ?>
                <!-- ================== VIEW: SUBCONTRACTORS ================== -->
                <!-- Filters and Actions -->
                <div class="custom-card p-5 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                            <input type="text" id="sub-search" class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl outline-none text-lg focus:border-emerald-500" placeholder="ค้นหาชื่อผู้รับเหมา, เบอร์โทร..." onkeyup="loadSubcontractors()">
                        </div>

                        <select id="sub-team-filter" class="py-2 px-4 border border-slate-200 rounded-xl outline-none text-lg text-slate-600 focus:border-emerald-500" onchange="loadSubcontractors()">
                            <option value="">ทีมทั้งหมด</option>
                            <option value="ทีมโครงสร้าง">ทีมโครงสร้าง</option>
                            <option value="ทีมไม้">ทีมไม้</option>
                            <option value="ทีมสี/ตกแต่ง">ทีมสี/ตกแต่ง</option>
                            <option value="ทีมไฟฟ้า">ทีมไฟฟ้า</option>
                            <option value="ทีมปูน/ก่อฉาบ">ทีมปูน/ก่อฉาบ</option>
                            <option value="ทีมกระเบื้อง">ทีมกระเบื้อง</option>
                            <option value="ทีมหลังคา">ทีมหลังคา</option>
                            <option value="ทีมงานระบบ">ทีมงานระบบ</option>
                            <option value="ทีมอลูมิเนียม">ทีมอลูมิเนียม</option>
                            <option value="ทีมสแตนเลส">ทีมสแตนเลส</option>
                        </select>

                        <select id="sub-status-filter" class="py-2 px-4 border border-slate-200 rounded-xl outline-none text-lg text-slate-600 focus:border-emerald-500" onchange="loadSubcontractors()">
                            <option value="">สถานะทั้งหมด</option>
                            <option value="กำลังทำงาน">กำลังทำงาน</option>
                            <option value="รอเริ่มงาน">รอเริ่มงาน</option>
                            <option value="หยุดงานชั่วคราว">หยุดงานชั่วคราว</option>
                            <option value="เสร็จสิ้น">เสร็จสิ้น</option>
                        </select>
                    </div>

                    <button onclick="openSubcontractorModal()" class="w-full md:w-auto bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>เพิ่มผู้รับเหมา</span>
                    </button>
                </div>

                <!-- Table Card -->
                <div class="custom-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-base text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-sm">
                                <tr>
                                    <th class="py-4 px-6 font-bold w-16 text-center">ลำดับ</th>
                                    <th class="py-4 px-6 font-bold">ชื่อผู้รับเหมา / เบอร์โทร</th>
                                    <th class="py-4 px-6 font-bold">ทีม / ประเภทงาน</th>
                                    <th class="py-4 px-6 font-bold">โปรเจ็คที่ทำอยู่</th>
                                    <th class="py-4 px-6 font-bold text-right">มูลค่างานรวม</th>
                                    <th class="py-4 px-6 font-bold text-right">จ่ายแล้ว</th>
                                    <th class="py-4 px-6 font-bold text-right">คงเหลือ</th>
                                    <th class="py-4 px-6 font-bold text-center">สถานะ</th>
                                    <th class="py-4 px-6 font-bold text-center w-32">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="subcontractors-table-body" class="divide-y divide-slate-100">
                                <tr>
                                    <td colspan="9" class="py-10 text-center text-slate-400 italic">กำลังโหลดข้อมูลผู้รับเหมา...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    $(document).ready(function() {
                        loadSubcontractors();
                    });

                    function loadSubcontractors() {
                        const search = $('#sub-search').val();
                        const team = $('#sub-team-filter').val();
                        const status = $('#sub-status-filter').val();

                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: {
                                action: 'subcontractor_list',
                                search: search,
                                team_type: team,
                                status: status
                            },
                            success: function(res) {
                                if (res.status === 'success') {
                                    let html = '';
                                    if (res.data.length === 0) {
                                        html = '<tr><td colspan="9" class="py-10 text-center text-slate-400 italic">ไม่พบข้อมูลผู้รับเหมา</td></tr>';
                                    } else {
                                        res.data.forEach((s, idx) => {
                                            let statusBadge = '';
                                            if (s.status === 'กำลังทำงาน') {
                                                statusBadge = '<span class="px-2.5 py-1 text-sm font-semibold rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">กำลังทำงาน</span>';
                                            } else if (s.status === 'รอเริ่มงาน') {
                                                statusBadge = '<span class="px-2.5 py-1 text-sm font-semibold rounded-full bg-amber-50 text-amber-600 border border-amber-100">รอเริ่มงาน</span>';
                                            } else if (s.status === 'หยุดงานชั่วคราว') {
                                                statusBadge = '<span class="px-2.5 py-1 text-sm font-semibold rounded-full bg-rose-50 text-rose-600 border border-rose-100">หยุดชั่วคราว</span>';
                                            } else {
                                                statusBadge = '<span class="px-2.5 py-1 text-sm font-semibold rounded-full bg-slate-50 text-slate-500 border border-slate-100">เสร็จสิ้น</span>';
                                            }

                                            html += `
                                                <tr class="hover:bg-slate-50/55 transition-all">
                                                    <td class="py-4 px-6 text-center font-bold text-slate-400">${idx + 1}</td>
                                                    <td class="py-4 px-6">
                                                        <div class="font-bold text-slate-800 text-base">${s.name}</div>
                                                        <div class="text-sm text-slate-400 flex items-center gap-1 mt-0.5">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                            ${s.phone || '-'}
                                                        </div>
                                                    </td>
                                                    <td class="py-4 px-6 font-semibold text-slate-600">${s.team_type}</td>
                                                    <td class="py-4 px-6 max-w-[200px] truncate" title="${s.active_projects}">
                                                        <span class="text-sm bg-slate-100 text-slate-600 font-semibold px-2 py-1 rounded">
                                                            ${s.active_projects}
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-6 text-right font-bold text-slate-700">${s.total_work.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                                                    <td class="py-4 px-6 text-right font-bold text-emerald-600">${s.paid_amount.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                                                    <td class="py-4 px-6 text-right font-bold text-rose-600">${s.remaining_amount.toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                                                    <td class="py-4 px-6 text-center">${statusBadge}</td>
                                                    <td class="py-4 px-6 text-center">
                                                        <div class="flex items-center justify-center gap-2">
                                                            <button onclick="openSubcontractorModal(${JSON.stringify(s).replace(/"/g, '&quot;')})" class="p-1.5 text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 rounded-lg transition-all" title="แก้ไขข้อมูล">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                            </button>
                                                            <button onclick="deleteSubcontractor(${s.id})" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="ลบข้อมูล">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            `;
                                        });
                                    }
                                    $('#subcontractors-table-body').html(html);
                                }
                            }
                        });
                    }

                    function openSubcontractorModal(data = null) {
                        const title = data ? 'แก้ไขข้อมูลผู้รับเหมา' : 'เพิ่มผู้รับเหมาใหม่';
                        const name = data ? data.name : '';
                        const phone = data ? data.phone : '';
                        const team = data ? data.team_type : 'ทีมโครงสร้าง';
                        const status = data ? data.status : 'กำลังทำงาน';
                        const id = data ? data.id : 0;

                        Swal.fire({
                            title: title,
                            html: `
                                <div class="text-left space-y-4 p-2">
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">ชื่อผู้รับเหมา *</label>
                                        <input type="text" id="m-name" class="swal2-input !m-0 !w-full" placeholder="ระบุชื่อผู้รับเหมา" value="${name}">
                                    </div>
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">เบอร์โทรศัพท์</label>
                                        <input type="text" id="m-phone" class="swal2-input !m-0 !w-full" placeholder="08X-XXX-XXXX" value="${phone}">
                                    </div>
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">ประเภทงาน / ทีม</label>
                                        <select id="m-team" class="swal2-input !m-0 !w-full select-style">
                                            <option value="ทีมโครงสร้าง" ${team==='ทีมโครงสร้าง'?'selected':''}>ทีมโครงสร้าง</option>
                                            <option value="ทีมไม้" ${team==='ทีมไม้'?'selected':''}>ทีมไม้</option>
                                            <option value="ทีมสี/ตกแต่ง" ${team==='ทีมสี/ตกแต่ง'?'selected':''}>ทีมสี/ตกแต่ง</option>
                                            <option value="ทีมไฟฟ้า" ${team==='ทีมไฟฟ้า'?'selected':''}>ทีมไฟฟ้า</option>
                                            <option value="ทีมปูน/ก่อฉาบ" ${team==='ทีมปูน/ก่อฉาบ'?'selected':''}>ทีมปูน/ก่อฉาบ</option>
                                            <option value="ทีมกระเบื้อง" ${team==='ทีมกระเบื้อง'?'selected':''}>ทีมกระเบื้อง</option>
                                            <option value="ทีมหลังคา" ${team==='ทีมหลังคา'?'selected':''}>ทีมหลังคา</option>
                                            <option value="ทีมงานระบบ" ${team==='ทีมงานระบบ'?'selected':''}>ทีมงานระบบ</option>
                                            <option value="ทีมอลูมิเนียม" ${team==='ทีมอลูมิเนียม'?'selected':''}>ทีมอลูมิเนียม</option>
                                            <option value="ทีมสแตนเลส" ${team==='ทีมสแตนเลส'?'selected':''}>ทีมสแตนเลส</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">สถานะ</label>
                                        <select id="m-status" class="swal2-input !m-0 !w-full">
                                            <option value="กำลังทำงาน" ${status==='กำลังทำงาน'?'selected':''}>กำลังทำงาน</option>
                                            <option value="รอเริ่มงาน" ${status==='รอเริ่มงาน'?'selected':''}>รอเริ่มงาน</option>
                                            <option value="หยุดงานชั่วคราว" ${status==='หยุดงานชั่วคราว'?'selected':''}>หยุดงานชั่วคราว</option>
                                            <option value="เสร็จสิ้น" ${status==='เสร็จสิ้น'?'selected':''}>เสร็จสิ้น</option>
                                        </select>
                                    </div>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'บันทึก',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#10b981',
                            preConfirm: () => {
                                const nameVal = $('#m-name').val();
                                const phoneVal = $('#m-phone').val();
                                const teamVal = $('#m-team').val();
                                const statusVal = $('#m-status').val();

                                if (!nameVal) {
                                    Swal.showValidationMessage('กรุณาระบุชื่อผู้รับเหมา');
                                    return false;
                                }
                                return { name: nameVal, phone: phoneVal, team_type: teamVal, status: statusVal };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: {
                                        action: 'subcontractor_save',
                                        id: id,
                                        ...result.value
                                    },
                                    success: function(res) {
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
                                            loadSubcontractors();
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error');
                                        }
                                    }
                                });
                            }
                        });
                    }

                    function deleteSubcontractor(id) {
                        Swal.fire({
                            title: 'ยืนยันการลบ?',
                            text: "คุณต้องการลบผู้รับเหมารายนี้ใช่หรือไม่ (ข้อมูลประวัติจะไม่หายหากไม่มีการเชื่อมโยง)",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'ใช่, ลบเลย',
                            cancelButtonText: 'ยกเลิก'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: { action: 'subcontractor_delete', id: id },
                                    success: function(res) {
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
                                            loadSubcontractors();
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error');
                                        }
                                    }
                                });
                            }
                        });
                    }
                </script>

            <?php elseif ($view === 'projects'): ?>
                <!-- ================== VIEW: PROJECTS ================== -->
                <!-- Search & Filters -->
                <div class="custom-card p-5 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                            <input type="text" id="proj-search" class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl outline-none text-lg focus:border-emerald-500" placeholder="ค้นหาโปรเจค, รหัส, ลูกค้า..." onkeyup="loadProjects()">
                        </div>

                        <select id="proj-status-filter" class="py-2 px-4 border border-slate-200 rounded-xl outline-none text-lg text-slate-600 focus:border-emerald-500" onchange="loadProjects()">
                            <option value="">สถานะโปรเจคทั้งหมด</option>
                            <option value="กำลังดำเนินการ">กำลังดำเนินการ</option>
                            <option value="รอเริ่มงาน">รอเริ่มงาน</option>
                            <option value="เสร็จสิ้น">เสร็จสิ้น</option>
                            <option value="ยกเลิก">ยกเลิก</option>
                        </select>
                    </div>

                    <!-- Manage Projects button which allows creating detailed project data -->
                    <button onclick="openProjectModal()" class="w-full md:w-auto bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>เพิ่มรายละเอียดโปรเจค</span>
                    </button>
                </div>

                

                <!-- Projects List Grid -->
                <div id="projects-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Loaded dynamically -->
                    <div class="col-span-full py-20 text-center text-slate-400 italic">กำลังโหลดข้อมูลโปรเจค...</div>
                </div>

                <script>
                    $(document).ready(function() {
                        loadProjects();
                    });

                    function loadProjects() {
                        const search = $('#proj-search').val();
                        const status = $('#proj-status-filter').val();
                        
                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'project_list', search: search, status: status },
                            success: function(res) {
                                if (res.status === 'success') {
                                    let html = '';
                                    if (res.data.length === 0) {
                                        html = '<div class="col-span-full py-16 text-center text-slate-400 italic">ไม่พบข้อมูลโปรเจค</div>';
                                    } else {
                                        res.data.forEach(p => {
                                            let statusClass = 'bg-slate-100 text-slate-600 border border-slate-200';
                                            if (p.status === 'กำลังดำเนินการ') {
                                                statusClass = 'bg-emerald-50 text-emerald-600 border border-emerald-100';
                                            } else if (p.status === 'รอเริ่มงาน') {
                                                statusClass = 'bg-amber-50 text-amber-600 border border-amber-100';
                                            } else if (p.status === 'เสร็จสิ้น') {
                                                statusClass = 'bg-blue-50 text-blue-600 border border-blue-100';
                                            } else if (p.status === 'ยกเลิก') {
                                                statusClass = 'bg-rose-50 text-rose-600 border border-rose-100';
                                            }

                                            html += `
                                                <div class="custom-card p-6 flex flex-col justify-between h-full relative">
                                                    <div>
                                                        <!-- Top Row -->
                                                        <div class="flex justify-between items-start mb-3 gap-2">
                                                            <div>
                                                                <span class="text-xs uppercase font-bold text-slate-400 tracking-wider">${p.project_code || 'PROJ'}</span>
                                                                <h4 class="font-bold text-slate-800 text-lg leading-tight mt-0.5 hover:text-emerald-500 transition-colors">
                                                                    <a href="index.php?view=project_detail&id=${p.id}">${p.project_name}</a>
                                                                </h4>
                                                            </div>
                                                            <span class="px-2 py-0.5 text-xs font-bold rounded-full ${statusClass}">${p.status}</span>
                                                        </div>

                                                        <!-- Details list -->
                                                        <div class="space-y-2 mt-4 text-sm text-slate-500">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-slate-400">👤</span>
                                                                <span class="font-semibold text-slate-700">ลูกค้า: ${p.customer_name || '-'}</span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-slate-400">👷</span>
                                                                <span class="font-semibold text-slate-700">ผู้รับเหมา: ${p.contractor_name || '-'} (${p.contractor_team || '-'})</span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-slate-400">📅</span>
                                                                <span>สัญญา: ${p.start_date ? new Date(p.start_date).toLocaleDateString('th-TH') : '-'} - ${p.end_date ? new Date(p.end_date).toLocaleDateString('th-TH') : '-'}</span>
                                                            </div>
                                                        </div>

                                                        <!-- Progress section -->
                                                        <div class="mt-6 space-y-1">
                                                            <div class="flex justify-between text-sm font-semibold">
                                                                <span class="text-slate-400">ความคืบหน้างวดงาน</span>
                                                                <span class="text-slate-700">${p.progress_percent}%</span>
                                                            </div>
                                                            <div class="w-full bg-slate-100 rounded-full h-2">
                                                                <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: ${p.progress_percent}%"></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Financial Footer -->
                                                    <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center">
                                                        <div>
                                                            <span class="text-xs text-slate-400 font-bold block">มูลค่างานรวม</span>
                                                            <span class="font-bold text-slate-700 text-base">${p.contract_value.toLocaleString()} ฿</span>
                                                        </div>
                                                        <div class="text-right">
                                                            <span class="text-xs text-slate-400 font-bold block">จ่ายแล้ว / คงเหลือ</span>
                                                            <span class="text-sm font-semibold">
                                                                <span class="text-emerald-600">${p.paid_installments.toLocaleString()}</span> / <span class="text-rose-500">${p.remaining_installments.toLocaleString()}</span>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Top-right Action Buttons -->
                                                    <div class="absolute top-4 right-4 flex items-center gap-1 opacity-0 hover:opacity-100 transition-opacity bg-white/80 p-1 rounded-lg shadow">
                                                        <button onclick="openProjectModal(${JSON.stringify(p).replace(/"/g, '&quot;')})" class="p-1 text-slate-400 hover:text-emerald-500 rounded" title="แก้ไข">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            `;
                                        });
                                    }
                                    $('#projects-grid').html(html);
                                }
                            }
                        });
                    }

                    function addModalAssignedSubcontractorColumn(job_type = '', sub_id = '', contract_amount = '') {
                        const index = new Date().getTime() + Math.floor(Math.random() * 1000);
                        const jobTypes = ['ทีมโครงสร้าง', 'ทีมไม้', 'ทีมสี/ตกแต่ง', 'ทีมไฟฟ้า', 'ทีมปูน/ก่อฉาบ', 'ทีมกระเบื้อง', 'ทีมหลังคา', 'ทีมงานระบบ', 'ทีมอลูมิเนียม', 'ทีมสแตนเลส'];
                        
                        let jobTypeOptions = '<option value="">-- เลือกประเภทงาน --</option>';
                        jobTypes.forEach(jt => { 
                            const selected = (jt === job_type) ? 'selected' : '';
                            jobTypeOptions += `<option value="${jt}" ${selected}>${jt}</option>`; 
                        });

                        let subOptions = '<option value="">-- เลือกผู้รับเหมา --</option>';
                        <?php foreach ($all_subcontractors as $sub): ?>
                            {
                                const isSelected = (sub_id == <?=$sub['id']?>) ? 'selected' : '';
                                subOptions += `<option value="<?=$sub['id']?>" ${isSelected}><?=$sub['name']?> (<?=$sub['team_type']?>)</option>`;
                            }
                        <?php endforeach; ?>

                        const rowHtml = `
                            <div class="modal-assigned-row flex items-end gap-2 bg-slate-50 p-2 border border-slate-200 rounded-lg relative">
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">ประเภทงาน</label>
                                    <select class="m-job-type w-full text-xs border border-slate-300 rounded p-1.5 outline-none focus:border-blue-500">
                                        ${jobTypeOptions}
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">ชื่อผู้รับเหมา</label>
                                    <select class="m-sub-id w-full text-xs border border-slate-300 rounded p-1.5 outline-none focus:border-blue-500">
                                        ${subOptions}
                                    </select>
                                </div>
                                <div class="w-32">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">มูลค่าสัญญา</label>
                                    <input type="number" class="m-contract-amt w-full text-xs border border-slate-300 rounded p-1.5 outline-none focus:border-blue-500 text-right font-bold" value="${contract_amount}" placeholder="0.00">
                                </div>
                                <button type="button" onclick="$(this).closest('.modal-assigned-row').remove()" class="w-8 h-8 flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded mb-0.5 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        `;
                        $('#modal-assigned-subs').append(rowHtml);
                    }

                    function openProjectModal(data = null) {
                        const title = data ? 'แก้ไขรายละเอียดโปรเจ็ค' : 'เพิ่มรายละเอียดโปรเจ็ค';
                        const id = data ? data.id : 0;
                        const project_name = data ? data.project_name : '';
                        const project_code = data ? data.project_code : '';
                        const project_type = data ? data.project_type : '';
                        const customer_name = data ? data.customer_name : '';
                        const customer_phone = data ? data.customer_phone : '';
                        const customer_email = data ? data.customer_email : '';
                        const address = data ? data.address : '';
                        const start_date = data ? data.start_date : '';
                        const end_date = data ? data.end_date : '';
                        const actual_end_date = data ? data.actual_end_date : '';
                        const status = data ? data.status : 'กำลังดำเนินการ';
                        const project_manager = data ? data.project_manager : '';
                        const budget = data ? data.budget : '';
                        const contract_value = data ? data.contract_value : '';
                        const main_subcontractor_id = data ? data.main_subcontractor_id : '';
                        const note = data ? data.note : '';

                        let subcontractorOptions = '<option value="">-- ไม่ระบุผู้รับเหมาหลัก --</option>';
                        <?php foreach ($all_subcontractors as $sub): ?>
                            subcontractorOptions += `<option value="<?=$sub['id']?>" ${main_subcontractor_id == <?=$sub['id']?> ? 'selected' : ''}><?=$sub['name']?> (<?=$sub['team_type']?>)</option>`;
                        <?php endforeach; ?>

                        Swal.fire({
                            title: title,
                            width: '800px',
                            html: `
                                <div class="text-left space-y-4 p-2 text-base grid grid-cols-2 gap-4">
                                    <div class="col-span-2">
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">ชื่อโครงการ *</label>
                                        <input type="text" id="p-name" class="swal2-input !m-0 !w-full" placeholder="ระบุชื่อโครงการ" value="${project_name}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">รหัสโครงการ</label>
                                        <input type="text" id="p-code" class="swal2-input !m-0 !w-full" placeholder="เช่น PJ-67001" value="${project_code}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">ประเภทโครงการ</label>
                                        <input type="text" id="p-type" class="swal2-input !m-0 !w-full" placeholder="เช่น บ้านเดี่ยว 2 ชั้น, ตกแต่งภายใน" value="${project_type}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">ชื่อลูกค้า</label>
                                        <input type="text" id="p-customer-name" class="swal2-input !m-0 !w-full" placeholder="ระบุชื่อผู้ติดต่อ" value="${customer_name}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">เบอร์โทรศัพท์ลูกค้า</label>
                                        <input type="text" id="p-customer-phone" class="swal2-input !m-0 !w-full" placeholder="08X-XXX-XXXX" value="${customer_phone}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">อีเมลลูกค้า</label>
                                        <input type="email" id="p-customer-email" class="swal2-input !m-0 !w-full" placeholder="wichit@email.com" value="${customer_email}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">ผู้รับเหมาหลัก</label>
                                        <select id="p-subcontractor" class="swal2-input !m-0 !w-full">${subcontractorOptions}</select>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">สถานที่ตั้งโครงการ / ที่อยู่ลูกค้า</label>
                                        <textarea id="p-address" class="swal2-textarea !m-0 !w-full !h-20" placeholder="ระบุที่ตั้ง">${address}</textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">วันที่เริ่มงาน</label>
                                        <input type="date" id="p-start-date" class="swal2-input !m-0 !w-full" value="${start_date}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">กำหนดส่งงาน</label>
                                        <input type="date" id="p-end-date" class="swal2-input !m-0 !w-full" value="${end_date}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">วันที่เสร็จสิ้นจริง (ถ้าเสร็จแล้ว)</label>
                                        <input type="date" id="p-actual-date" class="swal2-input !m-0 !w-full" value="${actual_end_date}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">สถานะโครงการ</label>
                                        <select id="p-status" class="swal2-input !m-0 !w-full">
                                            <option value="กำลังดำเนินการ" ${status==='กำลังดำเนินการ'?'selected':''}>กำลังดำเนินการ</option>
                                            <option value="รอเริ่มงาน" ${status==='รอเริ่มงาน'?'selected':''}>รอเริ่มงาน</option>
                                            <option value="เสร็จสิ้น" ${status==='เสร็จสิ้น'?'selected':''}>เสร็จสิ้น</option>
                                            <option value="ยกเลิก" ${status==='ยกเลิก'?'selected':''}>ยกเลิก</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">งบประมาณภายใน (บาท)</label>
                                        <input type="number" step="0.01" id="p-budget" class="swal2-input !m-0 !w-full" placeholder="0.00" value="${budget}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">มูลค่างานตามสัญญา (บาท)</label>
                                        <input type="number" step="0.01" id="p-contract-value" class="swal2-input !m-0 !w-full" placeholder="0.00" value="${contract_value}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">ผู้จัดการโครงการ</label>
                                        <input type="text" id="p-manager" class="swal2-input !m-0 !w-full" placeholder="ระบุผู้รับผิดชอบ" value="${project_manager}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">หมายเหตุเพิ่มเติม</label>
                                        <input type="text" id="p-note" class="swal2-input !m-0 !w-full" placeholder="รายละเอียดอื่นๆ" value="${note}">
                                    </div>
                                    <div class="col-span-2 border-t border-slate-100 pt-4 mt-2">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="font-bold text-slate-700 text-sm">รายชื่อผู้รับเหมาในโครงการ (ทีมต่างๆ)</h4>
                                            <button type="button" onclick="addModalAssignedSubcontractorColumn()" class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-2 py-1 rounded">+ เพิ่มทีม</button>
                                        </div>
                                        <div id="modal-assigned-subs" class="space-y-3 max-h-48 overflow-y-auto pr-2">
                                        </div>
                                    </div>
                                </div>
                            `,
                            didOpen: () => {
                                if (data && data.assigned_subcontractors && data.assigned_subcontractors.length > 0) {
                                    data.assigned_subcontractors.forEach(sub => {
                                        addModalAssignedSubcontractorColumn(sub.job_type, sub.subcontractor_id, sub.contract_amount);
                                    });
                                } else {
                                    addModalAssignedSubcontractorColumn();
                                }
                            },
                            showCancelButton: true,
                            confirmButtonText: 'บันทึก',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#10b981',
                            preConfirm: () => {
                                const nameVal = $('#p-name').val();
                                const codeVal = $('#p-code').val();
                                const typeVal = $('#p-type').val();
                                const cNameVal = $('#p-customer-name').val();
                                const cPhoneVal = $('#p-customer-phone').val();
                                const cEmailVal = $('#p-customer-email').val();
                                const addrVal = $('#p-address').val();
                                const startVal = $('#p-start-date').val();
                                const endVal = $('#p-end-date').val();
                                const actualVal = $('#p-actual-date').val();
                                const statusVal = $('#p-status').val();
                                const budgetVal = $('#p-budget').val();
                                const contractVal = $('#p-contract-value').val();
                                const managerVal = $('#p-manager').val();
                                const contractorVal = $('#p-subcontractor').val();
                                const noteVal = $('#p-note').val();

                                if (!nameVal) {
                                    Swal.showValidationMessage('กรุณาระบุชื่อโครงการ');
                                    return false;
                                }

                                const assigned_subs = [];
                                $('.modal-assigned-row').each(function() {
                                    const jt = $(this).find('.m-job-type').val();
                                    const sid = $(this).find('.m-sub-id').val();
                                    const camt = $(this).find('.m-contract-amt').val() || 0;
                                    if (sid) {
                                        assigned_subs.push({
                                            job_type: jt,
                                            subcontractor_id: sid,
                                            contract_amount: camt
                                        });
                                    }
                                });

                                return {
                                    project_name: nameVal,
                                    project_code: codeVal,
                                    project_type: typeVal,
                                    customer_name: cNameVal,
                                    customer_phone: cPhoneVal,
                                    customer_email: cEmailVal,
                                    address: addrVal,
                                    start_date: startVal,
                                    end_date: endVal,
                                    actual_end_date: actualVal,
                                    status: statusVal,
                                    budget: budgetVal,
                                    contract_value: contractVal,
                                    project_manager: managerVal,
                                    main_subcontractor_id: contractorVal,
                                    note: noteVal,
                                    assigned_subs: assigned_subs
                                };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: {
                                        action: 'project_save',
                                        id: id,
                                        assigned_subs_json: JSON.stringify(result.value.assigned_subs),
                                        ...result.value
                                    },
                                    success: function(res) {
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
                                            loadProjects();
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error');
                                        }
                                    }
                                });
                            }
                        });
                    }
                </script>
            
            <?php elseif ($view === 'project_detail'): ?>
                <!-- ================== VIEW: PROJECT DETAILS ================== -->
                <?php
                $pid = (int)$_GET['id'];
                ?>
                <!-- Details container, initialized by AJAX -->
                <div id="project-detail-container" class="space-y-6">
                    <div class="custom-card p-10 text-center text-slate-400 italic">กำลังโหลดข้อมูลโครงการ...</div>
                </div>

                <!-- JS Script to fetch project details on load -->
                <script>
                    $(document).ready(function() {
                        loadProjectDetails();
                    });

                    let activeDetailTab = 'tab-info';
                    
                    function loadProjectDetails() {
                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'project_get_details', id: <?=$pid?> },
                            success: function(res) {
                                if (res.status === 'success') {
                                    renderProjectDetails(res);
                                    switchTab(activeDetailTab);
                                } else {
                                    $('#project-detail-container').html(`<div class="custom-card p-10 text-center text-rose-500 font-bold">${res.message}</div>`);
                                }
                            }
                        });
                    }

                    function renderProjectDetails(res) {
                        const p = res.project;
                        const insts = res.installments;
                        const exps = res.expenses;
                        const pays = res.payments;
                        const subs = res.subcontractors;
                        const fins = res.financials;
                        const assigned_subs = res.assigned_subcontractors || [];
                        window.currentAllSubcontractors = res.all_subcontractors || [];
                        window.currentProjectId = p.id;

                        // Build assigned subs HTML
                        let assignedSubsHTML = '';
                        const jobTypes = ['ทีมโครงสร้าง', 'ทีมไม้', 'ทีมสี/ตกแต่ง', 'ทีมไฟฟ้า', 'ทีมปูน/ก่อฉาบ', 'ทีมกระเบื้อง', 'ทีมหลังคา', 'ทีมงานระบบ', 'ทีมอลูมิเนียม', 'ทีมสแตนเลส'];
                        
                        // We always want to show some columns. If empty, show 5 empty columns.
                        const displaySubs = assigned_subs.length > 0 ? assigned_subs : [{}, {}, {}, {}, {}];
                        
                        displaySubs.forEach((sub, index) => {
                            let jobTypeOptions = '<option value="">-- เลือกประเภทงาน --</option>';
                            jobTypes.forEach(jt => {
                                jobTypeOptions += `<option value="${jt}" ${sub.job_type === jt ? 'selected' : ''}>${jt}</option>`;
                            });

                            let subOptions = '<option value="">-- เลือกผู้รับเหมา --</option>';
                            window.currentAllSubcontractors.forEach(s => {
                                subOptions += `<option value="${s.id}" ${sub.subcontractor_id == s.id ? 'selected' : ''}>${s.name} (${s.team_type})</option>`;
                            });

                            const fileLink = sub.attachment ? `<div class="mt-1 text-xs"><a href="../${sub.attachment}" target="_blank" class="text-blue-500 hover:underline">ดูไฟล์แนบปัจจุบัน</a></div>` : '';
                            const existingAttachment = sub.attachment ? `<input type="hidden" name="existing_attachment_${index}" value="${sub.attachment}">` : '';

                            assignedSubsHTML += `
                                <div class="assigned-sub-col w-56 flex flex-col gap-4 border border-slate-200 rounded-lg p-3 bg-slate-50 relative shrink-0">
                                    <button type="button" onclick="$(this).closest('.assigned-sub-col').remove();" class="absolute top-1 right-1 text-slate-400 hover:text-rose-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                    
                                    <div>
                                        <label class="block text-sm font-bold text-rose-500 text-center mb-1">ประเภทงาน เลือก</label>
                                        <select name="job_type_${index}" class="w-full text-sm border border-slate-300 rounded p-1.5 focus:border-emerald-500 outline-none">
                                            ${jobTypeOptions}
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-bold text-rose-500 text-center mb-1">ชื่อผู้รับเหมาที่รับผิดชอบ</label>
                                        <select name="subcontractor_id_${index}" class="w-full text-sm border border-slate-300 rounded p-1.5 focus:border-emerald-500 outline-none">
                                            ${subOptions}
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 text-center mb-1">กรอกค่าแรงตามสัญญา</label>
                                        <input type="number" name="contract_amount_${index}" class="w-full text-center text-sm border border-slate-300 rounded p-1.5 font-bold text-slate-700" value="${sub.contract_amount || ''}" oninput="calcRemaining(this)" placeholder="0">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 text-center mb-1">ชำระแล้ว</label>
                                        <input type="number" class="w-full text-center text-sm border border-slate-200 rounded p-1.5 font-bold text-slate-500 bg-slate-100 sub-paid-amount" value="${sub.paid_amount || 0}" readonly>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-rose-500 text-center mb-1">ยอด ค้างชำระค่าแรง</label>
                                        <input type="number" class="w-full text-center text-sm border border-slate-200 rounded p-1.5 font-bold text-rose-500 bg-slate-100 sub-remaining-amount" value="${sub.remaining_amount || 0}" readonly>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 text-center mb-1">แนบเอกสารสัญญา</label>
                                        <input type="file" name="attachment_${index}" class="w-full text-xs border border-slate-300 rounded p-1 bg-white" accept=".pdf,image/*">
                                        ${fileLink}
                                        ${existingAttachment}
                                    </div>
                                </div>
                            `;
                        });
                        
                        let progressHTML = `
                            <div class="relative w-24 h-24 flex items-center justify-center">
                                <svg class="w-full h-full">
                                    <circle class="text-slate-100" stroke-width="8" stroke="currentColor" fill="transparent" r="38" cx="48" cy="48" />
                                    <circle class="text-emerald-500 progress-ring__circle" stroke-width="8" stroke-dasharray="${2 * Math.PI * 38}" stroke-dashoffset="${2 * Math.PI * 38 * (1 - p.progress_percent / 100)}" stroke-linecap="round" stroke="currentColor" fill="transparent" r="38" cx="48" cy="48" />
                                </svg>
                                <span class="absolute font-bold text-xl text-slate-800">${p.progress_percent}%</span>
                            </div>
                        `;

                        // 1. Build Subcontractors Right Panel list
                        let subsHtml = '';
                        if (subs.length === 0) {
                            subsHtml = '<p class="text-slate-400 text-sm italic">ไม่มีข้อมูลผู้รับเหมาในโครงการ</p>';
                        } else {
                            subs.forEach(s => {
                                subsHtml += `
                                    <div class="flex items-center justify-between border-b border-slate-50 pb-2">
                                        <div>
                                            <p class="font-bold text-slate-700 text-sm">${s.name}</p>
                                            <span class="text-xs text-slate-400">${s.team_type}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs text-slate-400 block">จ่ายสะสม</span>
                                            <span class="font-bold text-emerald-600 text-sm">${s.paid_amount.toLocaleString()} ฿</span>
                                        </div>
                                    </div>
                                `;
                            });
                        }

                        // 2. Build Installments / Payments tab list
                        let instListHtml = '';
                        if (insts.length === 0) {
                            instListHtml = '<tr><td colspan="7" class="py-6 text-center text-slate-400 italic">ไม่มีข้อมูลการกำหนดงวดงาน</td></tr>';
                        } else {
                            insts.forEach((inst, idx) => {
                                let badge = '';
                                if (inst.status === 'จ่ายแล้ว') {
                                    badge = '<span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">จ่ายแล้ว</span>';
                                } else if (inst.status === 'บางส่วน') {
                                    badge = '<span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-600 border border-amber-100">บางส่วน</span>';
                                } else {
                                    badge = '<span class="px-2 py-0.5 rounded text-xs font-bold bg-slate-50 text-slate-500 border border-slate-100">รอจ่าย</span>';
                                }

                                instListHtml += `
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="py-3 px-4 font-bold text-slate-400 text-center">${inst.installment_number}</td>
                                        <td class="py-3 px-4 font-semibold text-slate-700">${inst.name}</td>
                                        <td class="py-3 px-4 text-slate-500 text-center">${inst.due_date ? new Date(inst.due_date).toLocaleDateString('th-TH') : '-'}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-700">${inst.amount.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                                        <td class="py-3 px-4 text-right font-bold text-emerald-600">${inst.paid_amount.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                                        <td class="py-3 px-4 text-right font-bold text-rose-500">${inst.remaining_amount.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                                        <td class="py-3 px-4 text-center">${badge}</td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button onclick="editInstallment(${JSON.stringify(inst).replace(/"/g, '&quot;')})" class="p-1 text-slate-400 hover:text-emerald-500 rounded" title="แก้ไข"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                                <button onclick="deleteInstallment(${inst.id})" class="p-1 text-slate-400 hover:text-red-500 rounded" title="ลบ"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            });
                        }

                        // 3. Build expenses list html
                        let expListHtml = '';
                        if (exps.length === 0) {
                            expListHtml = '<tr><td colspan="7" class="py-6 text-center text-slate-400 italic">ไม่มีข้อมูลค่าใช้จ่ายเพิ่มเติม</td></tr>';
                        } else {
                            exps.forEach((exp, idx) => {
                                let badge = '';
                                if (exp.status === 'อนุมัติแล้ว') {
                                    badge = '<span class="px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">อนุมัติแล้ว</span>';
                                } else if (exp.status === 'รออนุมัติ') {
                                    badge = '<span class="px-2.5 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-600 border border-amber-100">รออนุมัติ</span>';
                                } else {
                                    badge = '<span class="px-2.5 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">ปฏิเสธ</span>';
                                }

                                let fileLink = exp.attachment ? `<a href="../${exp.attachment}" target="_blank" class="text-blue-500 font-semibold hover:underline">แนบไฟล์</a>` : '-';

                                expListHtml += `
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="py-3 px-4 text-slate-500 text-center">${idx + 1}</td>
                                        <td class="py-3 px-4 font-semibold text-slate-700">${exp.item_name}</td>
                                        <td class="py-3 px-4 text-slate-600 text-center">${exp.expense_type}</td>
                                        <td class="py-3 px-4 text-slate-500">${exp.reference_task || '-'}</td>
                                        <td class="py-3 px-4 text-slate-500 text-center">${new Date(exp.expense_date).toLocaleDateString('th-TH')}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-700">${exp.amount.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                                        <td class="py-3 px-4 text-center">${badge}</td>
                                        <td class="py-3 px-4 text-center">${fileLink}</td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button onclick="editExpense(${JSON.stringify(exp).replace(/"/g, '&quot;')})" class="p-1 text-slate-400 hover:text-emerald-500 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                                <button onclick="deleteExpense(${exp.id})" class="p-1 text-slate-400 hover:text-red-500 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            });
                        }

                        // Detailed layout
                        let detailHtml = `
                            <!-- Details Header Card -->
                            <div class="custom-card p-6 flex flex-col md:flex-row justify-between items-center gap-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-4xl shadow shadow-emerald-500/10">
                                        🏡
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm uppercase font-bold text-slate-400 tracking-wider">${p.project_code || 'PJ-67001'}</span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">${p.status}</span>
                                        </div>
                                        <h2 class="font-bold text-xl text-slate-800 mt-1">${p.project_name}</h2>
                                        <p class="text-sm text-slate-400 mt-0.5">${p.project_type || 'โครงการก่อสร้าง'}</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-6">
                                    <div class="text-right">
                                        <span class="text-xs text-slate-400 font-bold block">มูลค่างานตามสัญญา</span>
                                        <h4 class="text-xl font-bold text-slate-700">${fins.contract_value.toLocaleString(undefined, {minimumFractionDigits: 2})} บาท</h4>
                                    </div>
                                    ${progressHTML}
                                </div>
                            </div>

                            <!-- Dashboard Tabs & Info -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                                <!-- Tabs Content Left -->
                                <div class="lg:col-span-2 space-y-6">
                                    <!-- Custom Tab Headers -->
                                    <div class="bg-white border border-slate-100 rounded-xl p-1.5 flex gap-1 shadow-sm">
                                        <button onclick="switchTab('tab-info')" id="btn-tab-info" class="flex-1 py-2 px-4 text-sm font-bold text-slate-500 rounded-lg hover:text-slate-800 transition-all">ข้อมูลโปรเจ็ค</button>
                                        <button onclick="switchTab('tab-milestones')" id="btn-tab-milestones" class="flex-1 py-2 px-4 text-sm font-bold text-slate-500 rounded-lg hover:text-slate-800 transition-all">งวดงาน / การจ่ายเงิน</button>
                                        <button onclick="switchTab('tab-extra-expenses')" id="btn-tab-extra-expenses" class="flex-1 py-2 px-4 text-sm font-bold text-slate-500 rounded-lg hover:text-slate-800 transition-all">ค่าใช้จ่ายเพิ่มเติม</button>
                                    </div>

                                    <!-- TAB CONTENT: INFO -->
                                    <div id="tab-info" class="tab-pane bg-white border border-slate-100 rounded-xl p-6 shadow-sm space-y-6">
                                        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                                            <h3 class="font-bold text-slate-800 text-base">รายละเอียดโปรเจ็ค</h3>
                                            <button onclick="openProjectModal(${JSON.stringify(p).replace(/"/g, '&quot;')})" class="text-sm font-bold text-emerald-600 hover:underline">แก้ไขข้อมูล</button>
                                        </div>
                                        <div class="grid grid-cols-2 gap-y-4 gap-x-6 text-base">
                                            <div>
                                                <span class="text-sm text-slate-400 font-medium">รหัสโครงการ</span>
                                                <p class="font-semibold text-slate-700 mt-0.5">${p.project_code || '-'}</p>
                                            </div>
                                            <div>
                                                <span class="text-sm text-slate-400 font-medium">ประเภทโครงการ</span>
                                                <p class="font-semibold text-slate-700 mt-0.5">${p.project_type || '-'}</p>
                                            </div>
                                            <div>
                                                <span class="text-sm text-slate-400 font-medium">ลูกค้า</span>
                                                <p class="font-semibold text-slate-700 mt-0.5">${p.customer_name || '-'}</p>
                                            </div>
                                            <div>
                                                <span class="text-sm text-slate-400 font-medium">เบอร์โทรศัพท์ลูกค้า</span>
                                                <p class="font-semibold text-slate-700 mt-0.5">${p.customer_phone || '-'}</p>
                                            </div>
                                            <div>
                                                <span class="text-sm text-slate-400 font-medium">อีเมลลูกค้า</span>
                                                <p class="font-semibold text-slate-700 mt-0.5">${p.customer_email || '-'}</p>
                                            </div>
                                            <div>
                                                <span class="text-sm text-slate-400 font-medium">ผู้จัดการโครงการ</span>
                                                <p class="font-semibold text-slate-700 mt-0.5">${p.project_manager || '-'}</p>
                                            </div>
                                            <div>
                                                <span class="text-sm text-slate-400 font-medium">วันที่เริ่มงาน</span>
                                                <p class="font-semibold text-slate-700 mt-0.5">${p.start_date ? new Date(p.start_date).toLocaleDateString('th-TH') : '-'}</p>
                                            </div>
                                            <div>
                                                <span class="text-sm text-slate-400 font-medium">กำหนดเสร็จงาน</span>
                                                <p class="font-semibold text-slate-700 mt-0.5">${p.end_date ? new Date(p.end_date).toLocaleDateString('th-TH') : '-'}</p>
                                            </div>
                                            <div class="col-span-2">
                                                <span class="text-sm text-slate-400 font-medium">ที่อยู่โครงการ</span>
                                                <p class="font-semibold text-slate-700 mt-0.5 leading-relaxed">${p.address || '-'}</p>
                                            </div>
                                            <div class="col-span-2">
                                                <span class="text-sm text-slate-400 font-medium">หมายเหตุเพิ่มเติม</span>
                                                <p class="font-semibold text-slate-700 mt-0.5 leading-relaxed">${p.note || '-'}</p>
                                            </div>
                                        </div>

                                        <!-- Assigned Subcontractors Section -->
                                        <div class="border-t border-slate-100 pt-6 mt-6">
                                            <div class="flex justify-between items-center mb-4">
                                                <h3 class="font-bold text-rose-500 text-lg">รายละเอียดผู้รับเหมาทำงานในโปรเจค</h3>
                                                <button onclick="saveAssignedSubcontractors()" class="text-sm font-bold text-emerald-600 hover:underline bg-emerald-50 px-3 py-1.5 rounded">บันทึกข้อมูลผู้รับเหมาในโปรเจค</button>
                                            </div>
                                            <div class="overflow-x-auto pb-4">
                                                <form id="assigned-subs-form" class="flex gap-4 min-w-max items-start">
                                                    ${assignedSubsHTML}
                                                </form>
                                                <button type="button" onclick="addAssignedSubcontractorColumn()" class="mt-4 text-sm font-bold text-indigo-500 hover:text-indigo-700 flex items-center gap-1">+ เพิ่มประเภทงาน</button>
                                            </div>
                                            
                                        </div>

                                    </div>

                                    <!-- TAB CONTENT: MILESTONES -->
                                    <div id="tab-milestones" class="tab-pane bg-white border border-slate-100 rounded-xl overflow-hidden shadow-sm space-y-4 hidden">
                                        <div class="p-6 pb-0 flex justify-between items-center">
                                            <h3 class="font-bold text-slate-800 text-base">งวดงาน / การจ่ายเงินผู้รับเหมา</h3>
                                            <div class="flex items-center gap-2">
                                                <a href="index.php?view=new_payment&project_id=${p.id}&subcontractor_id=${p.main_subcontractor_id || ''}" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm px-3 py-1.5 rounded-lg">จ่ายเงินงวด</a>
                                                <button onclick="openInstallmentModal()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm px-3 py-1.5 rounded-lg flex items-center gap-1">+ เพิ่มงวด</button>
                                            </div>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-base text-slate-600">
                                                <thead class="bg-slate-50 text-slate-500 uppercase text-sm">
                                                    <tr>
                                                        <th class="py-3 px-4 font-bold text-center w-16">งวดที่</th>
                                                        <th class="py-3 px-4 font-bold">รายการ / รายละเอียด</th>
                                                        <th class="py-3 px-4 font-bold text-center w-28">กำหนดจ่าย</th>
                                                        <th class="py-3 px-4 font-bold text-right w-32">มูลค่างาน</th>
                                                        <th class="py-3 px-4 font-bold text-right w-32">จ่ายแล้ว</th>
                                                        <th class="py-3 px-4 font-bold text-right w-32">คงเหลือ</th>
                                                        <th class="py-3 px-4 font-bold text-center w-24">สถานะ</th>
                                                        <th class="py-3 px-4 font-bold text-center w-20">จัดการ</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    ${instListHtml}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- TAB CONTENT: EXTRA EXPENSES -->
                                    <div id="tab-extra-expenses" class="tab-pane bg-white border border-slate-100 rounded-xl overflow-hidden shadow-sm space-y-4 hidden">
                                        <div class="p-6 pb-0 flex justify-between items-center">
                                            <h3 class="font-bold text-slate-800 text-base">บันทึกค่าใช้จ่ายเพิ่มเติม</h3>
                                            <button onclick="openExpenseModal()" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm px-3 py-1.5 rounded-lg flex items-center gap-1">+ บันทึกค่าใช้จ่าย</button>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-base text-slate-600">
                                                <thead class="bg-slate-50 text-slate-500 uppercase text-sm">
                                                    <tr>
                                                        <th class="py-3 px-4 font-bold w-12 text-center">#</th>
                                                        <th class="py-3 px-4 font-bold">รายการ</th>
                                                        <th class="py-3 px-4 font-bold text-center w-28">ประเภท</th>
                                                        <th class="py-3 px-4 font-bold">งวดงาน/งานที่เกี่ยวข้อง</th>
                                                        <th class="py-3 px-4 font-bold text-center w-28">วันที่</th>
                                                        <th class="py-3 px-4 font-bold text-right w-32">จำนวนเงิน</th>
                                                        <th class="py-3 px-4 font-bold text-center w-24">สถานะ</th>
                                                        <th class="py-3 px-4 font-bold text-center w-16">ไฟล์</th>
                                                        <th class="py-3 px-4 font-bold text-center w-20">จัดการ</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    ${expListHtml}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial summary & sub list Right -->
                                <div class="space-y-6">
                                    <!-- Financial Panel -->
                                    <div class="custom-card p-6 space-y-4">
                                        <h3 class="font-bold text-slate-800 text-base pb-2 border-b border-slate-100">สรุปการเงินของโปรเจ็ค</h3>
                                        <div class="space-y-3 text-base">
                                            <div class="flex justify-between">
                                                <span class="text-slate-400">มูลค่างานรวม</span>
                                                <span class="font-bold text-slate-700">${fins.contract_value.toLocaleString()} บาท</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-slate-400">กำหนดงบค่าแรง</span>
                                                <span class="font-semibold text-slate-700">${fins.total_installments_val.toLocaleString()} บาท</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-slate-400">จ่ายค่าแรงแล้ว</span>
                                                <span class="font-bold text-emerald-600">${fins.paid_installments.toLocaleString()} บาท</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-slate-400">คงเหลือค่าแรงรอจ่าย</span>
                                                <span class="font-bold text-rose-500">${fins.remaining_installments.toLocaleString()} บาท</span>
                                            </div>
                                            <div class="flex justify-between border-t border-slate-50 pt-2">
                                                <span class="text-slate-400">ค่าใช้จ่ายเพิ่มเติมสะสม</span>
                                                <span class="font-bold text-amber-600">${fins.additional_expenses.toLocaleString()} บาท</span>
                                            </div>
                                            <div class="flex justify-between border-t border-slate-100 pt-2 text-lg">
                                                <span class="font-bold text-slate-800">ต้นทุนรวม ณ ปัจจุบัน</span>
                                                <span class="font-extrabold text-slate-800">${fins.total_cost.toLocaleString()} บาท</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="font-bold text-slate-800">กำไรเบื้องต้น</span>
                                                <span class="font-bold ${fins.gross_profit >= 0 ? 'text-emerald-600' : 'text-rose-500'}">
                                                    ${fins.gross_profit.toLocaleString()} บาท (${fins.gross_profit_percent}%)
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Main Subcontractor & Sub list -->
                                    <div class="custom-card p-6 space-y-4">
                                        <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                                            <h3 class="font-bold text-slate-800 text-base">ผู้รับเหมาในโครงการ</h3>
                                        </div>
                                        <div class="space-y-3">
                                            ${subsHtml}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        $('#project-detail-container').html(detailHtml);
                    }

                    function calcRemaining(inputElem) {
                        const col = $(inputElem).closest('.assigned-sub-col');
                        const contractAmt = parseFloat($(inputElem).val()) || 0;
                        const paidAmt = parseFloat(col.find('.sub-paid-amount').val()) || 0;
                        const remaining = Math.max(0, contractAmt - paidAmt);
                        col.find('.sub-remaining-amount').val(remaining);
                    }

                    function addAssignedSubcontractorColumn() {
                        const index = new Date().getTime(); // unique index
                        const jobTypes = ['ทีมโครงสร้าง', 'ทีมไม้', 'ทีมสี/ตกแต่ง', 'ทีมไฟฟ้า', 'ทีมปูน/ก่อฉาบ', 'ทีมกระเบื้อง', 'ทีมหลังคา', 'ทีมงานระบบ', 'ทีมอลูมิเนียม', 'ทีมสแตนเลส'];
                        
                        let jobTypeOptions = '<option value="">-- เลือกประเภทงาน --</option>';
                        jobTypes.forEach(jt => { jobTypeOptions += `<option value="${jt}">${jt}</option>`; });

                        let subOptions = '<option value="">-- เลือกผู้รับเหมา --</option>';
                        if(window.currentAllSubcontractors) {
                            window.currentAllSubcontractors.forEach(s => {
                                subOptions += `<option value="${s.id}">${s.name} (${s.team_type})</option>`;
                            });
                        }

                        const html = `
                            <div class="assigned-sub-col w-56 flex flex-col gap-4 border border-slate-200 rounded-lg p-3 bg-slate-50 relative shrink-0">
                                <button type="button" onclick="$(this).closest('.assigned-sub-col').remove();" class="absolute top-1 right-1 text-slate-400 hover:text-rose-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                
                                <div>
                                    <label class="block text-sm font-bold text-rose-500 text-center mb-1">ประเภทงาน เลือก</label>
                                    <select name="job_type_${index}" class="w-full text-sm border border-slate-300 rounded p-1.5 focus:border-emerald-500 outline-none">
                                        ${jobTypeOptions}
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-rose-500 text-center mb-1">ชื่อผู้รับเหมาที่รับผิดชอบ</label>
                                    <select name="subcontractor_id_${index}" class="w-full text-sm border border-slate-300 rounded p-1.5 focus:border-emerald-500 outline-none">
                                        ${subOptions}
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 text-center mb-1">กรอกค่าแรงตามสัญญา</label>
                                    <input type="number" name="contract_amount_${index}" class="w-full text-center text-sm border border-slate-300 rounded p-1.5 font-bold text-slate-700" value="" oninput="calcRemaining(this)" placeholder="0">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 text-center mb-1">ชำระแล้ว</label>
                                    <input type="number" class="w-full text-center text-sm border border-slate-200 rounded p-1.5 font-bold text-slate-500 bg-slate-100 sub-paid-amount" value="0" readonly>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-rose-500 text-center mb-1">ยอด ค้างชำระค่าแรง</label>
                                    <input type="number" class="w-full text-center text-sm border border-slate-200 rounded p-1.5 font-bold text-rose-500 bg-slate-100 sub-remaining-amount" value="0" readonly>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 text-center mb-1">แนบเอกสารสัญญา</label>
                                    <input type="file" name="attachment_${index}" class="w-full text-xs border border-slate-300 rounded p-1 bg-white" accept=".pdf,image/*">
                                </div>
                            </div>
                        `;
                        $('#assigned-subs-form').append(html);
                    }

                    function saveAssignedSubcontractors() {
                        const form = document.getElementById('assigned-subs-form');
                        const formData = new FormData();
                        
                        let items = [];
                        let hasError = false;
                        
                        $('.assigned-sub-col').each(function() {
                            const jtSelect = $(this).find('select[name^="job_type_"]');
                            const subSelect = $(this).find('select[name^="subcontractor_id_"]');
                            const amtInput = $(this).find('input[name^="contract_amount_"]');
                            const fileInput = $(this).find('input[type="file"]')[0];
                            const existingAttachInput = $(this).find('input[name^="existing_attachment_"]');
                            
                            const jobType = jtSelect.val();
                            const subId = subSelect.val();
                            const amt = amtInput.val();
                            
                            if (jobType || subId || amt) {
                                if (!jobType || !subId) {
                                    hasError = true;
                                    return false;
                                }
                                
                                const idx = items.length;
                                items.push({
                                    job_type: jobType,
                                    subcontractor_id: subId,
                                    contract_amount: amt || 0,
                                    existing_attachment: existingAttachInput.length ? existingAttachInput.val() : ''
                                });
                                
                                if (fileInput.files.length > 0) {
                                    formData.append('attachment_' + idx, fileInput.files[0]);
                                }
                            }
                        });
                        
                        if (hasError) {
                            Swal.fire('ข้อมูลไม่ครบ', 'กรุณาระบุประเภทงานและชื่อผู้รับเหมาให้ครบถ้วนในคอลัมน์ที่มีการกรอกข้อมูล', 'warning');
                            return;
                        }

                        formData.append('action', 'save_assigned_subcontractors');
                        formData.append('project_id', window.currentProjectId);
                        formData.append('data_json', JSON.stringify(items));

                        Swal.fire({title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                        $.ajax({
                            url: 'action.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(res) {
                                Swal.close();
                                if (res.status === 'success') {
                                    Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
                                    loadProjectDetails();
                                } else {
                                    Swal.fire('ผิดพลาด', res.message, 'error');
                                }
                            }
                        });
                    }

                    function switchTab(tabId) {
                        $('.tab-pane').addClass('hidden');
                        $('#' + tabId).removeClass('hidden');

                        // Reset buttons classes
                        $('#btn-tab-info, #btn-tab-milestones, #btn-tab-extra-expenses').removeClass('bg-emerald-500 text-white').addClass('text-slate-500');
                        // Highlight active button
                        $('#btn-' + tabId).addClass('bg-emerald-500 text-white').removeClass('text-slate-500');
                        activeDetailTab = tabId;
                    }

                    // Installment Actions
                    function openInstallmentModal(data = null) {
                        const title = data ? 'แก้ไขข้อมูลงวดงาน' : 'เพิ่มงวดงานใหม่';
                        const id = data ? data.id : 0;
                        const num = data ? data.installment_number : '';
                        const name = data ? data.name : '';
                        const due = data ? data.due_date : '';
                        const amt = data ? data.amount : '';
                        const paid = data ? data.paid_amount : 0;
                        const status = data ? data.status : 'รอจ่าย';

                        Swal.fire({
                            title: title,
                            html: `
                                <div class="text-left space-y-4 p-2">
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">งวดที่ (ตัวเลขเท่านั้น) *</label>
                                        <input type="number" id="inst-num" class="swal2-input !m-0 !w-full" placeholder="เช่น 1, 2" value="${num}">
                                    </div>
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">รายการ / รายละเอียดงาน *</label>
                                        <input type="text" id="inst-name" class="swal2-input !m-0 !w-full" placeholder="เช่น งวดที่ 1 : มัดจำ" value="${name}">
                                    </div>
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">กำหนดจ่าย</label>
                                        <input type="date" id="inst-due" class="swal2-input !m-0 !w-full" value="${due}">
                                    </div>
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">มูลค่างาน (บาท) *</label>
                                        <input type="number" step="0.01" id="inst-amt" class="swal2-input !m-0 !w-full" placeholder="0.00" value="${amt}">
                                    </div>
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">จ่ายแล้ว (บาท)</label>
                                        <input type="number" step="0.01" id="inst-paid" class="swal2-input !m-0 !w-full" placeholder="0.00" value="${paid}">
                                    </div>
                                    <div>
                                        <label class="block text-base font-semibold text-slate-700 mb-1">สถานะ</label>
                                        <select id="inst-status" class="swal2-input !m-0 !w-full">
                                            <option value="รอจ่าย" ${status==='รอจ่าย'?'selected':''}>รอจ่าย</option>
                                            <option value="บางส่วน" ${status==='บางส่วน'?'selected':''}>บางส่วน</option>
                                            <option value="จ่ายแล้ว" ${status==='จ่ายแล้ว'?'selected':''}>จ่ายแล้ว</option>
                                        </select>
                                    </div>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'บันทึก',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#10b981',
                            preConfirm: () => {
                                const numVal = $('#inst-num').val();
                                const nameVal = $('#inst-name').val();
                                const dueVal = $('#inst-due').val();
                                const amtVal = $('#inst-amt').val();
                                const paidVal = $('#inst-paid').val();
                                const statusVal = $('#inst-status').val();

                                if (!numVal || !nameVal || !amtVal) {
                                    Swal.showValidationMessage('กรุณากรอกข้อมูลงวด รายละเอียด และมูลค่างาน');
                                    return false;
                                }

                                return {
                                    installment_number: numVal,
                                    name: nameVal,
                                    due_date: dueVal,
                                    amount: amtVal,
                                    paid_amount: paidVal,
                                    status: statusVal
                                };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: {
                                        action: 'installment_save',
                                        project_id: <?=$pid?>,
                                        id: id,
                                        ...result.value
                                    },
                                    success: function(res) {
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
                                            loadProjectDetails();
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error');
                                        }
                                    }
                                });
                            }
                        });
                    }

                    function editInstallment(data) {
                        openInstallmentModal(data);
                    }

                    function deleteInstallment(id) {
                        Swal.fire({
                            title: 'ยืนยันการลบ?',
                            text: "คุณต้องการลบงวดงานนี้ใช่หรือไม่",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'ใช่, ลบเลย',
                            cancelButtonText: 'ยกเลิก'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: { action: 'installment_delete', id: id },
                                    success: function(res) {
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
                                            loadProjectDetails();
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error');
                                        }
                                    }
                                });
                            }
                        });
                    }

                    // Extra Expense Actions
                    function openExpenseModal(data = null) {
                        const title = data ? 'แก้ไขบันทึกค่าใช้จ่ายเพิ่มเติม' : 'บันทึกค่าใช้จ่ายเพิ่มเติมใหม่';
                        const id = data ? data.id : 0;
                        const name = data ? data.item_name : '';
                        const date = data ? data.expense_date : new Date().toISOString().split('T')[0];
                        const type = data ? data.expense_type : 'วัสดุ';
                        const ref = data ? data.reference_task : '';
                        const amt = data ? data.amount : '';
                        const status = data ? data.status : 'อนุมัติแล้ว';
                        const note = data ? data.note : '';

                        let installmentOptions = '<option value="">-- ไม่เชื่อมโยงงวดงาน --</option>';
                        // We will populate dynamic options from loaded installments
                        $('#tab-milestones table tbody tr').each(function() {
                            const nameCell = $(this).find('td:nth-child(2)').text();
                            if(nameCell && nameCell !== 'ไม่มีข้อมูลการกำหนดงวดงาน') {
                                installmentOptions += `<option value="${nameCell}" ${ref === nameCell ? 'selected' : ''}>${nameCell}</option>`;
                            }
                        });

                        Swal.fire({
                            title: title,
                            html: `
                                <form id="exp-form" class="text-left space-y-4 p-2 text-base" enctype="multipart/form-data">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">รายการค่าใช้จ่าย *</label>
                                        <input type="text" name="item_name" class="swal2-input !m-0 !w-full" placeholder="ระบุรายการ เช่น ค่าขนดิน, ค่าแก้งานฐานราก" value="${name}">
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-500 mb-1">ประเภทค่าใช้จ่าย</label>
                                            <select name="expense_type" class="swal2-input !m-0 !w-full select-style">
                                                <option value="วัสดุ" ${type==='วัสดุ'?'selected':''}>วัสดุ</option>
                                                <option value="ค่าแรงเพิ่ม" ${type==='ค่าแรงเพิ่ม'?'selected':''}>ค่าแรงเพิ่ม</option>
                                                <option value="ค่าเครื่องมือ" ${type==='ค่าเครื่องมือ'?'selected':''}>ค่าเครื่องมือ</option>
                                                <option value="ค่าขนส่ง" ${type==='ค่าขนส่ง'?'selected':''}>ค่าขนส่ง</option>
                                                <option value="ค่าแก้ไขงาน" ${type==='ค่าแก้ไขงาน'?'selected':''}>ค่าแก้ไขงาน</option>
                                                <option value="สาธารณูปโภค" ${type==='สาธารณูปโภค'?'selected':''}>สาธารณูปโภค</option>
                                                <option value="อื่นๆ" ${type==='อื่นๆ'?'selected':''}>อื่นๆ</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-500 mb-1">วันที่เกิดรายการ *</label>
                                            <input type="date" name="expense_date" class="swal2-input !m-0 !w-full" value="${date}">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">งวดงาน / งานที่เกี่ยวข้อง</label>
                                        <select name="reference_task" class="swal2-input !m-0 !w-full">${installmentOptions}</select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-500 mb-1">จำนวนเงิน (บาท) *</label>
                                            <input type="number" step="0.01" name="amount" class="swal2-input !m-0 !w-full" placeholder="0.00" value="${amt}">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-500 mb-1">สถานะ</label>
                                            <select name="status" class="swal2-input !m-0 !w-full">
                                                <option value="อนุมัติแล้ว" ${status==='อนุมัติแล้ว'?'selected':''}>อนุมัติแล้ว</option>
                                                <option value="รออนุมัติ" ${status==='รออนุมัติ'?'selected':''}>รออนุมัติ</option>
                                                <option value="ปฏิเสธ" ${status==='ปฏิเสธ'?'selected':''}>ปฏิเสธ</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">แนบหลักฐาน (รูปภาพ หรือ PDF)</label>
                                        <input type="file" name="attachment" class="swal2-input !m-0 !w-full !p-1.5" accept="image/*,application/pdf">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">บันทึกเพิ่มเติม</label>
                                        <input type="text" name="note" class="swal2-input !m-0 !w-full" placeholder="ระบุรายละเอียดเพิ่มเติม" value="${note}">
                                    </div>
                                </form>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'บันทึก',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#10b981',
                            preConfirm: () => {
                                const form = document.getElementById('exp-form');
                                const formData = new FormData(form);
                                
                                if (!formData.get('item_name') || !formData.get('expense_date') || !formData.get('amount')) {
                                    Swal.showValidationMessage('กรุณากรอกข้อมูลรายการ วันที่ และจำนวนเงิน');
                                    return false;
                                }

                                formData.append('action', 'expense_save');
                                formData.append('project_id', <?=$pid?>);
                                formData.append('id', id);

                                return formData;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: result.value,
                                    processData: false,
                                    contentType: false,
                                    success: function(res) {
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
                                            loadProjectDetails();
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error');
                                        }
                                    }
                                });
                            }
                        });
                    }

                    function editExpense(data) {
                        openExpenseModal(data);
                    }

                    function deleteExpense(id) {
                        Swal.fire({
                            title: 'ยืนยันการลบ?',
                            text: "คุณต้องการลบรายการค่าใช้จ่ายนี้ใช่หรือไม่",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'ใช่, ลบเลย',
                            cancelButtonText: 'ยกเลิก'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: { action: 'expense_delete', id: id },
                                    success: function(res) {
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
                                            loadProjectDetails();
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error');
                                        }
                                    }
                                });
                            }
                        });
                    }
                </script>

            <?php elseif ($view === 'payments'): ?>
                <!-- ================== VIEW: PAYMENTS HISTORIES ================== -->
                <!-- Search & Filters -->
                <div class="custom-card p-5 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                            <input type="text" id="pay-search" class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl outline-none text-lg focus:border-emerald-500" placeholder="ค้นหาเลขที่เอกสาร, ผู้รับเหมา, โปรเจค..." onkeyup="loadPayments()">
                        </div>

                        <select id="pay-sub-filter" class="py-2 px-4 border border-slate-200 rounded-xl outline-none text-lg text-slate-600 focus:border-emerald-500" onchange="loadPayments()">
                            <option value="">ผู้รับเหมาทั้งหมด</option>
                            <?php foreach ($all_subcontractors as $sub): ?>
                                <option value="<?=$sub['id']?>"><?=$sub['name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <a href="index.php?view=new_payment" class="w-full md:w-auto bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-2.5 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>ทำรายการจ่ายเงิน</span>
                    </a>
                </div>

                <!-- Table Card -->
                <div class="custom-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-base text-slate-600 font-medium">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-sm">
                                <tr>
                                    <th class="py-4 px-6 font-bold">เลขที่เอกสาร</th>
                                    <th class="py-4 px-6 font-bold">ผู้รับเหมา</th>
                                    <th class="py-4 px-6 font-bold">โครงการ</th>
                                    <th class="py-4 px-6 font-bold">งวดงาน</th>
                                    <th class="py-4 px-6 font-bold text-center">วันที่จ่าย</th>
                                    <th class="py-4 px-6 font-bold text-right">ยอดจ่ายตามงวด</th>
                                    <th class="py-4 px-6 font-bold text-right">สุทธิ (รวมหักภาษี/ประกัน)</th>
                                    <th class="py-4 px-6 font-bold text-center">หลักฐาน</th>
                                    <th class="py-4 px-6 font-bold text-center w-20">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="payments-table-body" class="divide-y divide-slate-100">
                                <tr>
                                    <td colspan="9" class="py-10 text-center text-slate-400 italic">กำลังโหลดประวัติการจ่ายเงิน...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    $(document).ready(function() {
                        loadPayments();
                    });

                    function loadPayments() {
                        const search = $('#pay-search').val();
                        const subId = $('#pay-sub-filter').val();

                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'payment_list', search: search, subcontractor_id: subId },
                            success: function(res) {
                                if (res.status === 'success') {
                                    let html = '';
                                    if (res.data.length === 0) {
                                        html = '<tr><td colspan="9" class="py-10 text-center text-slate-400 italic">ไม่พบประวัติการจ่ายเงิน</td></tr>';
                                    } else {
                                        res.data.forEach(p => {
                                            const docLink = p.attachment ? `<a href="../${p.attachment}" target="_blank" class="text-blue-500 font-bold hover:underline">คลิกดูหลักฐาน</a>` : '-';
                                            html += `
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="py-4 px-6 font-bold text-slate-700 text-base">${p.payment_number}</td>
                                                    <td class="py-4 px-6">
                                                        <div class="font-bold text-slate-800">${p.contractor_name}</div>
                                                        <span class="text-sm text-slate-400">${p.contractor_team}</span>
                                                    </td>
                                                    <td class="py-4 px-6 text-slate-500 font-semibold">${p.project_name}</td>
                                                    <td class="py-4 px-6 text-slate-500 text-sm max-w-[150px] truncate" title="${p.installment_name || 'จ่ายเงินล่วงหน้า/อื่นๆ'}">
                                                        ${p.installment_name || 'จ่ายเงินล่วงหน้า/อื่นๆ'}
                                                    </td>
                                                    <td class="py-4 px-6 text-center text-slate-500">${p.payment_date}</td>
                                                    <td class="py-4 px-6 text-right font-bold text-slate-600">${p.total_amount.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                                    <td class="py-4 px-6 text-right font-bold text-emerald-600">${p.net_amount.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                                                    <td class="py-4 px-6 text-center">${docLink}</td>
                                                    <td class="py-4 px-6 text-center">
                                                        <button onclick="deletePayment(${p.id})" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all" title="ลบข้อมูล">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            `;
                                        });
                                    }
                                    $('#payments-table-body').html(html);
                                }
                            }
                        });
                    }

                    function deletePayment(id) {
                        Swal.fire({
                            title: 'ยืนยันการลบประวัติ?',
                            text: "ข้อมูลการจ่ายเงินและงวดงานจะถูกย้อนกลับการปรับปรุง ยืนยันการลบ?",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'ใช่, ลบเลย',
                            cancelButtonText: 'ยกเลิก'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: { action: 'payment_delete', id: id },
                                    success: function(res) {
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false});
                                            loadPayments();
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error');
                                        }
                                    }
                                });
                            }
                        });
                    }
                </script>

            <?php elseif ($view === 'new_payment'): ?>
                <!-- ================== VIEW: NEW PAYMENT FORM ================== -->
                <?php
                // Generate a running document code for payment e.g. PAY-6705-0033
                // Format: PAY-YYMM-XXXX
                $yy = date('y') + 43; // Buddhist Era year suffix (e.g. 69 for 2026)
                $mm = date('m');
                $prefix = "PAY-" . $yy . $mm . "-";
                
                // Find latest document number in db
                $latestSQL = "SELECT payment_number FROM subcontractor_payments WHERE payment_number LIKE '$prefix%' ORDER BY id DESC LIMIT 1";
                $latestRes = mysqli_query($conn, $latestSQL);
                if (mysqli_num_rows($latestRes) > 0) {
                    $latest = mysqli_fetch_assoc($latestRes)['payment_number'];
                    $num = (int)substr($latest, -4);
                    $newNum = str_pad($num + 1, 4, '0', STR_PAD_LEFT);
                } else {
                    $newNum = '0001';
                }
                $doc_number = $prefix . $newNum;
                
                // Pre-selected parameters
                $pre_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
                $pre_sub_id = isset($_GET['subcontractor_id']) ? (int)$_GET['subcontractor_id'] : 0;
                ?>
                <form id="payment-form" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start" enctype="multipart/form-data">
                    <!-- Left Section: Form inputs -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Card 1: Document details -->
                        <div class="custom-card p-6 space-y-4">
                            <h3 class="font-bold text-slate-800 text-base border-b border-slate-100 pb-2">ข้อมูลการจ่ายเงิน</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-500 mb-1">เลขที่เอกสาร *</label>
                                    <input type="text" name="payment_number" class="w-full px-4 py-2 border border-slate-200 rounded-xl outline-none text-base font-bold text-slate-700 bg-slate-50 focus:border-emerald-500" value="<?=$doc_number?>" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-500 mb-1">วันที่จ่าย *</label>
                                    <input type="date" name="payment_date" class="w-full px-4 py-2 border border-slate-200 rounded-xl outline-none text-base text-slate-700 focus:border-emerald-500" value="<?=date('Y-m-d')?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-500 mb-1">วิธีการจ่าย *</label>
                                    <select name="payment_method" class="w-full px-4 py-2 border border-slate-200 rounded-xl outline-none text-base text-slate-700 focus:border-emerald-500">
                                        <option value="โอนเงิน">โอนเงิน</option>
                                        <option value="เงินสด">เงินสด</option>
                                        <option value="เช็ค">เช็ค</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-500 mb-1">บัญชีผู้รับเงิน / บัญชีต้นทาง</label>
                                    <input type="text" name="bank_account" class="w-full px-4 py-2 border border-slate-200 rounded-xl outline-none text-base text-slate-700 focus:border-emerald-500" placeholder="เช่น ธนาคารกรุงเทพ 123-4-56789-0 (บัญชีหลัก)">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-500 mb-1">อ้างอิง / หมายเหตุ</label>
                                    <input type="text" name="note" class="w-full px-4 py-2 border border-slate-200 rounded-xl outline-none text-base text-slate-700 focus:border-emerald-500" placeholder="เช่น เลขที่เช็ค, หมายเหตุเพิ่มเติม">
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Subcontractor and Project Selection -->
                        <div class="custom-card p-6 space-y-4">
                            <h3 class="font-bold text-slate-800 text-base border-b border-slate-100 pb-2">เลือกผู้รับเหมาและโปรเจค</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-500 mb-1">ผู้รับเหมา *</label>
                                    <select id="f-subcontractor" name="subcontractor_id" class="w-full px-4 py-2 border border-slate-200 rounded-xl outline-none text-base text-slate-700 focus:border-emerald-500" onchange="fetchFormMilestones()">
                                        <option value="">-- เลือกผู้รับเหมา --</option>
                                        <?php foreach ($all_subcontractors as $sub): ?>
                                            <option value="<?=$sub['id']?>" <?=$pre_sub_id==$sub['id']?'selected':''?>><?=$sub['name']?> (<?=$sub['team_type']?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-500 mb-1">โปรเจค / งาน *</label>
                                    <select id="f-project" name="project_id" class="w-full px-4 py-2 border border-slate-200 rounded-xl outline-none text-base text-slate-700 focus:border-emerald-500" onchange="fetchFormMilestones()">
                                        <option value="">-- เลือกโปรเจค --</option>
                                        <?php foreach ($all_projects as $proj): ?>
                                            <option value="<?=$proj['id']?>" <?=$pre_project_id==$proj['id']?'selected':''?>><?=($proj['project_code'] ? '['.$proj['project_code'].'] ' : '') . $proj['project_name']?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div id="f-installment-container" class="hidden">
                                <label class="block text-sm font-semibold text-slate-500 mb-1">งวดงาน / รายการที่จ่าย *</label>
                                <select id="f-installment" name="installment_id" class="w-full px-4 py-2 border border-slate-200 rounded-xl outline-none text-base text-slate-700 focus:border-emerald-500" onchange="calculateAmounts()">
                                    <!-- Dynamic options -->
                                    <option value="0">-- ไม่เกี่ยวข้องกับงวดงาน (จ่ายค่าแรงล่วงหน้า/ค่าเครื่องมือ) --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Card 3: Payment details table -->
                        <div class="custom-card p-6 space-y-4">
                            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                                <h3 class="font-bold text-slate-800 text-base">รายละเอียดการจ่ายเงิน</h3>
                                <button type="button" onclick="addPaymentItemRow()" class="text-sm font-bold text-emerald-600 flex items-center gap-1 hover:underline">
                                    + เพิ่มรายการจ่ายเพิ่มเติม
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-base text-slate-600">
                                    <thead class="bg-slate-50 text-slate-500 text-sm">
                                        <tr>
                                            <th class="py-2.5 px-4 font-bold w-12 text-center">#</th>
                                            <th class="py-2.5 px-4 font-bold w-48">รายการ</th>
                                            <th class="py-2.5 px-4 font-bold">รายละเอียด</th>
                                            <th class="py-2.5 px-4 font-bold text-right w-44">จำนวนเงิน (บาท)</th>
                                            <th class="py-2.5 px-4 font-bold w-16 text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="payment-items-body" class="divide-y divide-slate-100">
                                        <!-- Will hold first main row and any additional rows -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-slate-50 font-bold">
                                            <td colspan="3" class="py-3 px-4 text-right">รวมจำนวนเงินงวดและงานเพิ่มเติม:</td>
                                            <td class="py-3 px-4 text-right" id="label-grand-total">0.00 บาท</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Card 4: Document Attachment -->
                        <div class="custom-card p-6 space-y-4">
                            <h3 class="font-bold text-slate-800 text-base border-b border-slate-100 pb-2">เอกสารแนบ (สลิปโอนเงิน / ใบสำคัญรับเงิน)</h3>
                            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 flex flex-col items-center justify-center text-center cursor-pointer hover:border-emerald-500 transition-colors" onclick="$('#f-attachment').click()">
                                <svg class="w-10 h-10 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                <p class="text-base font-semibold text-slate-600" id="file-name-label">เลือกไฟล์หลักฐานการชำระเงิน หรือลากมาวางที่นี่</p>
                                <span class="text-sm text-slate-400 mt-1">รองรับไฟล์ภาพ JPG, PNG หรือเอกสาร PDF ขนาดไม่เกิน 10MB</span>
                                <input type="file" id="f-attachment" name="attachment" class="hidden" onchange="updateFileLabel()">
                            </div>
                        </div>
                    </div>

                    <!-- Right Section: Totals & Deduction calculation summary -->
                    <div class="space-y-6">
                        <div class="custom-card p-6 space-y-4 sticky top-6">
                            <h3 class="font-bold text-slate-800 text-base pb-2 border-b border-slate-100">สรุปยอดชำระเงิน</h3>
                            
                            <div class="space-y-3 text-base text-slate-600">
                                <div class="flex justify-between">
                                    <span>ยอดจ่ายงวดหลัก:</span>
                                    <span class="font-bold text-slate-700" id="calc-base-amount">0.00 บาท</span>
                                </div>
                                <div class="flex justify-between text-amber-600">
                                    <span>ยอดงานเพิ่มเติม/อื่นๆ:</span>
                                    <span class="font-bold" id="calc-add-amount">+0.00 บาท</span>
                                </div>
                                <div class="flex justify-between font-bold border-t border-slate-50 pt-2 text-slate-800">
                                    <span>ยอดรวมก่อนหัก:</span>
                                    <span id="calc-gross-amount">0.00 บาท</span>
                                </div>

                                <!-- Deductions controls -->
                                <div class="pt-3 space-y-2 border-t border-slate-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <input type="checkbox" id="f-tax-enabled" class="rounded border-slate-200 text-emerald-500 focus:ring-emerald-500" onchange="calculateAmounts()" checked>
                                            <label for="f-tax-enabled" class="text-sm font-semibold text-slate-600">หักภาษี ณ ที่จ่าย (3%)</label>
                                        </div>
                                        <span class="font-bold text-rose-500" id="calc-tax-deduction">-0.00 บาท</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <input type="checkbox" id="f-ret-enabled" class="rounded border-slate-200 text-emerald-500 focus:ring-emerald-500" onchange="calculateAmounts()">
                                            <label for="f-ret-enabled" class="text-sm font-semibold text-slate-600">หักเงินประกันผลงาน (5%)</label>
                                        </div>
                                        <span class="font-bold text-rose-500" id="calc-ret-deduction">-0.00 บาท</span>
                                    </div>

                                    <!-- Additional Deductions Container -->
                                    <div id="additional-deductions-container" class="space-y-2 pt-2 border-t border-slate-50"></div>
                                    <button type="button" onclick="addAdditionalDeduction()" class="text-sm text-indigo-600 font-bold hover:text-indigo-800 flex items-center gap-1 mt-2">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        เพิ่มรายการหักอื่นๆ
                                    </button>
                                </div>

                                <!-- Net payment -->
                                <div class="border-t border-slate-100 pt-3 flex justify-between items-center text-slate-800">
                                    <span class="font-bold">ยอดเงินโอนสุทธิ:</span>
                                    <span class="text-xl font-extrabold text-emerald-600" id="calc-net-amount">0.00 บาท</span>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="pt-4 space-y-2">
                                <button type="button" onclick="submitPaymentForm()" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-emerald-500/20 transition-all text-center text-base">
                                    บันทึกและยืนยันการจ่ายเงิน
                                </button>
                                <a href="index.php?view=payments" class="block w-full bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold py-3 px-4 rounded-xl text-center text-base transition-all border border-slate-200">
                                    ยกเลิก
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                <script>
                    $(document).ready(function() {
                        $('#f-project').select2({ width: '100%' });
                        $('#f-project').on('change', function() {
                            fetchFormMilestones();
                        });

                        // Check if project and sub parameters are set, fetch milestones
                        if ($('#f-project').val() !== '' && $('#f-subcontractor').val() !== '') {
                            fetchFormMilestones();
                        }
                    });

                    function updateFileLabel() {
                        const file = $('#f-attachment')[0].files[0];
                        if (file) {
                            $('#file-name-label').text('เลือกไฟล์แล้ว: ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)');
                        } else {
                            $('#file-name-label').text('เลือกไฟล์หลักฐานการชำระเงิน หรือลากมาวางที่นี่');
                        }
                    }

                    function fetchFormMilestones() {
                        const pid = $('#f-project').val();
                        const sid = $('#f-subcontractor').val();
                        
                        if (pid === '' || sid === '') {
                            $('#f-installment-container').addClass('hidden');
                            $('#payment-items-body').html('');
                            calculateAmounts();
                            return;
                        }

                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'get_payment_form_details', project_id: pid, subcontractor_id: sid },
                            success: function(res) {
                                if (res.status === 'success') {
                                    // Populate installments dropdown
                                    let html = '<option value="0">-- ไม่เชื่อมโยงงวดงาน (จ่ายค่าแรงล่วงหน้า/ค่าเครื่องมือ) --</option>';
                                    res.installments.forEach(inst => {
                                        html += `<option value="${inst.id}" data-amount="${inst.remaining_amount}">${inst.name} (ค้างจ่าย: ${inst.remaining_amount.toLocaleString()} ฿)</option>`;
                                    });
                                    $('#f-installment').html(html);
                                    $('#f-installment-container').removeClass('hidden');

                                    // Pre-populate parameter installment if set (could be handled)
                                    // Set base row in table body
                                    resetPaymentItemsTable();
                                    calculateAmounts();
                                }
                            }
                        });
                    }

                    function resetPaymentItemsTable() {
                        const instId = $('#f-installment').val();
                        const instText = $('#f-installment option:selected').text();
                        const instAmount = instId > 0 ? parseFloat($('#f-installment option:selected').data('amount')) : 0;
                        
                        let html = '';
                        if (instId > 0) {
                            html = `
                                <tr class="payment-item-row" data-type="installment">
                                    <td class="py-3 px-4 text-center font-bold text-slate-400">1</td>
                                    <td class="py-3 px-4">
                                        <input type="text" class="w-full bg-transparent font-bold border-b border-transparent focus:border-slate-300 py-1 outline-none text-slate-800" value="งวดงานตามสัญญา" readonly>
                                    </td>
                                    <td class="py-3 px-4">
                                        <input type="text" class="w-full bg-transparent border-b border-transparent focus:border-slate-300 py-1 outline-none text-slate-500" value="${instText.split(' (')[0]}" readonly>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <input type="number" step="0.01" class="w-32 bg-transparent text-right font-bold text-slate-700 border-b border-slate-300 py-1 outline-none item-amount" value="${instAmount}" onkeyup="calculateAmounts()" onchange="calculateAmounts()">
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <!-- Cannot delete main installment row, only change amount -->
                                    </td>
                                </tr>
                            `;
                        } else {
                            html = `
                                <tr class="payment-item-row" data-type="advance">
                                    <td class="py-3 px-4 text-center font-bold text-slate-400">1</td>
                                    <td class="py-3 px-4">
                                        <input type="text" class="w-full bg-transparent font-bold border-b border-slate-200 py-1 outline-none text-slate-800 item-title" value="จ่ายเงินค่าแรงล่วงหน้า">
                                    </td>
                                    <td class="py-3 px-4">
                                        <input type="text" class="w-full bg-transparent border-b border-slate-200 py-1 outline-none text-slate-500 item-details" value="ระบุรายละเอียด">
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <input type="number" step="0.01" class="w-32 bg-transparent text-right font-bold text-slate-700 border-b border-slate-300 py-1 outline-none item-amount" value="0" onkeyup="calculateAmounts()" onchange="calculateAmounts()">
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                    </td>
                                </tr>
                            `;
                        }
                        $('#payment-items-body').html(html);
                    }

                    function addPaymentItemRow() {
                        const rowCount = $('#payment-items-body tr').length;
                        const rowIdx = rowCount + 1;
                        const html = `
                            <tr class="payment-item-row" data-type="additional">
                                <td class="py-3 px-4 text-center font-bold text-slate-400">${rowIdx}</td>
                                <td class="py-3 px-4">
                                    <input type="text" class="w-full bg-transparent font-bold border-b border-slate-200 py-1 outline-none text-slate-800 item-title" placeholder="เช่น งานเพิ่ม/ลดนอกสัญญา">
                                </td>
                                <td class="py-3 px-4">
                                    <input type="text" class="w-full bg-transparent border-b border-slate-200 py-1 outline-none text-slate-500 item-details" placeholder="รายละเอียด">
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <input type="number" step="0.01" class="w-32 bg-transparent text-right font-bold text-slate-700 border-b border-slate-300 py-1 outline-none item-amount" value="0" onkeyup="calculateAmounts()" onchange="calculateAmounts()">
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button" onclick="$(this).closest('tr').remove(); calculateAmounts(); resetRowIndices();" class="text-rose-500 hover:text-rose-700 font-bold">L</button>
                                </td>
                            </tr>
                        `;
                        $('#payment-items-body').append(html);
                    }

                    function resetRowIndices() {
                        $('#payment-items-body tr').each(function(idx) {
                            $(this).find('td:first-child').text(idx + 1);
                        });
                    }

                    function addAdditionalDeduction() {
                        const html = `
                            <div class="flex items-center justify-between gap-2 additional-deduction-row">
                                <div class="flex items-center gap-1.5 flex-1">
                                    <input type="checkbox" class="rounded border-slate-200 text-emerald-500 focus:ring-emerald-500 deduction-checkbox" onchange="calculateAmounts()" checked>
                                    <input type="text" class="text-sm font-semibold text-slate-600 border-b border-slate-200 outline-none flex-1 deduction-name" placeholder="รายการหักเพิ่มเติม.....">
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="number" step="0.01" class="text-sm font-bold text-rose-500 text-right border-b border-slate-200 outline-none w-20 deduction-amount" value="0" onkeyup="calculateAmounts()" onchange="calculateAmounts()">
                                    <button type="button" onclick="$(this).closest('.additional-deduction-row').remove(); calculateAmounts();" class="text-slate-400 hover:text-rose-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                        `;
                        $('#additional-deductions-container').append(html);
                    }

                    function calculateAmounts() {
                        // If f-installment changes, we might need to reset the main row
                        const instId = $('#f-installment').val();
                        const mainRow = $('#payment-items-body tr:first-child');
                        
                        // If selected installment, and main row is not of installment type, reset
                        if (instId > 0 && mainRow.data('type') !== 'installment') {
                            resetPaymentItemsTable();
                        } else if (instId == 0 && mainRow.data('type') === 'installment') {
                            resetPaymentItemsTable();
                        }

                        let baseAmount = 0;
                        let additionalAmount = 0;
                        
                        $('#payment-items-body tr').each(function(idx) {
                            const type = $(this).data('type');
                            const amt = parseFloat($(this).find('.item-amount').val()) || 0;
                            if (type === 'installment') {
                                baseAmount = amt;
                            } else {
                                additionalAmount += amt;
                            }
                        });

                        const grossAmount = baseAmount + additionalAmount;
                        
                        // Deductions
                        let taxDeduction = 0;
                        if ($('#f-tax-enabled').is(':checked')) {
                            taxDeduction = grossAmount * 0.03;
                        }

                        let retDeduction = 0;
                        if ($('#f-ret-enabled').is(':checked')) {
                            retDeduction = grossAmount * 0.05;
                        }

                        let otherDeductions = 0;
                        $('.additional-deduction-row').each(function() {
                            if ($(this).find('.deduction-checkbox').is(':checked')) {
                                otherDeductions += parseFloat($(this).find('.deduction-amount').val()) || 0;
                            }
                        });

                        const netAmount = Math.max(0, grossAmount - taxDeduction - retDeduction - otherDeductions);

                        // Update DOM labels
                        $('#label-grand-total').text(grossAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                        $('#calc-base-amount').text(baseAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                        $('#calc-add-amount').text('+' + additionalAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                        $('#calc-gross-amount').text(grossAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                        $('#calc-tax-deduction').text('-' + taxDeduction.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                        $('#calc-ret-deduction').text('-' + retDeduction.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                        $('#calc-net-amount').text(netAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                    }

                    function submitPaymentForm() {
                        const pid = $('#f-project').val();
                        const sid = $('#f-subcontractor').val();
                        const payDate = $('input[name="payment_date"]').val();

                        if (pid === '' || sid === '' || payDate === '') {
                            Swal.fire('กรอกข้อมูลไม่ครบ', 'กรุณาระบุผู้รับเหมา โครงการ และวันที่จ่าย', 'warning');
                            return;
                        }

                        // Collect payment items JSON
                        const items = [];
                        let baseAmount = 0;
                        let additionalAmount = 0;

                        $('#payment-items-body tr').each(function() {
                            const type = $(this).data('type');
                            const amt = parseFloat($(this).find('.item-amount').val()) || 0;
                            let title = '';
                            let details = '';

                            if (type === 'installment') {
                                title = 'จ่ายงวดงานหลัก';
                                details = $('#f-installment option:selected').text().split(' (')[0];
                                baseAmount = amt;
                            } else {
                                title = $(this).find('.item-title').val() || 'งานเพิ่มเติม';
                                details = $(this).find('.item-details').val() || '-';
                                additionalAmount += amt;
                            }

                            items.push({ type: type, title: title, details: details, amount: amt });
                        });

                        let otherDeductionsTotal = 0;
                        $('.additional-deduction-row').each(function() {
                            if ($(this).find('.deduction-checkbox').is(':checked')) {
                                const title = $(this).find('.deduction-name').val() || 'รายการหักเพิ่มเติม';
                                const amt = parseFloat($(this).find('.deduction-amount').val()) || 0;
                                if (amt > 0) {
                                    items.push({ type: 'other_deduction', title: title, details: 'หักจากยอดชำระ', amount: -amt });
                                    otherDeductionsTotal += amt;
                                }
                            }
                        });

                        const grossAmount = baseAmount + additionalAmount;
                        
                        let taxDeduction = 0;
                        if ($('#f-tax-enabled').is(':checked')) {
                            taxDeduction = grossAmount * 0.03;
                        }

                        let retDeduction = 0;
                        if ($('#f-ret-enabled').is(':checked')) {
                            retDeduction = grossAmount * 0.05;
                        }

                        const netAmount = grossAmount - taxDeduction - retDeduction - otherDeductionsTotal;

                        // Create FormData to support file upload
                        const form = document.getElementById('payment-form');
                        const formData = new FormData(form);
                        
                        formData.append('action', 'payment_save');
                        formData.append('total_amount', baseAmount);
                        formData.append('additional_amount', additionalAmount);
                        formData.append('deduct_tax', taxDeduction);
                        formData.append('deduct_retention', retDeduction);
                        formData.append('net_amount', netAmount);
                        formData.append('items_json', JSON.stringify(items));

                        Swal.fire({
                            title: 'กำลังบันทึกข้อมูล...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: 'action.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(res) {
                                Swal.close();
                                if (res.status === 'success') {
                                    Swal.fire({
                                        title: 'สำเร็จ',
                                        text: res.message,
                                        icon: 'success',
                                        showCancelButton: true,
                                        confirmButtonText: 'บันทึกค่าใช้จ่ายโครงการ',
                                        cancelButtonText: 'ไม่บันทึก',
                                        confirmButtonColor: '#10b981',
                                        cancelButtonColor: '#f43f5e'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            openExpenseModalForPayment(pid, grossAmount, 'ค่าแรงผู้รับเหมา ' + $('#f-project option:selected').text());
                                        } else {
                                            window.location.href = 'index.php?view=payments';
                                        }
                                    });
                                } else {
                                    Swal.fire('ผิดพลาด', res.message, 'error');
                                }
                            }
                        });
                    }

                    function openExpenseModalForPayment(pid, defaultAmount, paymentName) {
                        const date = new Date().toISOString().split('T')[0];
                        
                        Swal.fire({
                            title: 'บันทึกค่าใช้จ่ายโครงการ',
                            html: `
                                <form id="exp-form-payment" class="text-left space-y-4 p-2 text-base" enctype="multipart/form-data">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">รายการค่าใช้จ่าย *</label>
                                        <input type="text" name="item_name" class="swal2-input !m-0 !w-full" placeholder="ระบุรายการ" value="${paymentName}">
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-500 mb-1">ประเภทค่าใช้จ่าย</label>
                                            <select name="expense_type" class="swal2-input !m-0 !w-full select-style">
                                                <option value="ค่าแรงเพิ่ม" selected>ค่าแรงเพิ่ม</option>
                                                <option value="วัสดุ">วัสดุ</option>
                                                <option value="ค่าเครื่องมือ">ค่าเครื่องมือ</option>
                                                <option value="ค่าขนส่ง">ค่าขนส่ง</option>
                                                <option value="ค่าแก้ไขงาน">ค่าแก้ไขงาน</option>
                                                <option value="สาธารณูปโภค">สาธารณูปโภค</option>
                                                <option value="อื่นๆ">อื่นๆ</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-500 mb-1">วันที่เกิดรายการ *</label>
                                            <input type="date" name="expense_date" class="swal2-input !m-0 !w-full" value="${date}">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">จำนวนเงิน (บาท) *</label>
                                        <input type="number" step="0.01" name="amount" class="swal2-input !m-0 !w-full" placeholder="0.00" value="${defaultAmount}">
                                    </div>
                                    <input type="hidden" name="status" value="อนุมัติแล้ว">
                                    <input type="hidden" name="reference_task" value="">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">แนบหลักฐาน (รูปภาพ หรือ PDF)</label>
                                        <input type="file" name="attachment" class="swal2-input !m-0 !w-full !p-1.5" accept="image/*,application/pdf">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">บันทึกเพิ่มเติม</label>
                                        <input type="text" name="note" class="swal2-input !m-0 !w-full" placeholder="ระบุรายละเอียดเพิ่มเติม" value="">
                                    </div>
                                </form>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'บันทึกค่าใช้จ่าย',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#10b981',
                            preConfirm: () => {
                                const form = document.getElementById('exp-form-payment');
                                const formData = new FormData(form);
                                
                                if (!formData.get('item_name') || !formData.get('expense_date') || !formData.get('amount')) {
                                    Swal.showValidationMessage('กรุณากรอกข้อมูลรายการ วันที่ และจำนวนเงิน');
                                    return false;
                                }

                                formData.append('action', 'expense_save');
                                formData.append('project_id', pid);
                                formData.append('id', 0); // New expense

                                return formData;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                                $.ajax({
                                    url: 'action.php',
                                    type: 'POST',
                                    data: result.value,
                                    processData: false,
                                    contentType: false,
                                    success: function(res) {
                                        Swal.close();
                                        if (res.status === 'success') {
                                            Swal.fire({icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false})
                                            .then(() => {
                                                window.location.href = 'index.php?view=payments';
                                            });
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error').then(() => {
                                                window.location.href = 'index.php?view=payments';
                                            });
                                        }
                                    }
                                });
                            } else {
                                window.location.href = 'index.php?view=payments';
                            }
                        });
                    }
                </script>

            <?php elseif ($view === 'expenses'): ?>
                <!-- ================== VIEW: GENERAL ADDITIONAL EXPENSES ================== -->
                <!-- Search & Filters -->
                <div class="custom-card p-5 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                        <select id="exp-proj-filter" class="py-2 px-4 border border-slate-200 rounded-xl outline-none text-lg text-slate-600 focus:border-emerald-500" onchange="loadExpensesList()">
                            <option value="">โครงการทั้งหมด</option>
                            <?php foreach ($all_projects as $proj): ?>
                                <option value="<?=$proj['id']?>"><?=$proj['project_name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="custom-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-base text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-sm">
                                <tr>
                                    <th class="py-4 px-6 font-bold w-12 text-center">#</th>
                                    <th class="py-4 px-6 font-bold">โครงการ</th>
                                    <th class="py-4 px-6 font-bold">รายการค่าใช้จ่าย</th>
                                    <th class="py-4 px-6 font-bold text-center w-28">ประเภท</th>
                                    <th class="py-4 px-6 font-bold">งานที่เกี่ยวข้อง</th>
                                    <th class="py-4 px-6 font-bold text-center w-28">วันที่เกิดรายการ</th>
                                    <th class="py-4 px-6 font-bold text-right w-36">จำนวนเงิน</th>
                                    <th class="py-4 px-6 font-bold text-center w-28">สถานะ</th>
                                    <th class="py-4 px-6 font-bold text-center w-16">ไฟล์</th>
                                </tr>
                            </thead>
                            <tbody id="expenses-table-body" class="divide-y divide-slate-100">
                                <tr>
                                    <td colspan="9" class="py-10 text-center text-slate-400 italic">กำลังโหลดค่าใช้จ่ายเพิ่มเติม...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    $(document).ready(function() {
                        loadExpensesList();
                    });

                    function loadExpensesList() {
                        const projId = $('#exp-proj-filter').val();
                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'expense_list', project_id: projId },
                            success: function(res) {
                                if (res.status === 'success') {
                                    let html = '';
                                    if (res.data.length === 0) {
                                        html = '<tr><td colspan="9" class="py-10 text-center text-slate-400 italic">ไม่มีข้อมูลค่าใช้จ่ายเพิ่มเติม</td></tr>';
                                    } else {
                                        res.data.forEach((exp, idx) => {
                                            let badge = '';
                                            if (exp.status === 'อนุมัติแล้ว') {
                                                badge = '<span class="px-2.5 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">อนุมัติแล้ว</span>';
                                            } else if (exp.status === 'รออนุมัติ') {
                                                badge = '<span class="px-2.5 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-600 border border-amber-100">รออนุมัติ</span>';
                                            } else {
                                                badge = '<span class="px-2.5 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">ปฏิเสธ</span>';
                                            }

                                            let fileLink = exp.attachment ? `<a href="../${exp.attachment}" target="_blank" class="text-blue-500 font-bold hover:underline">คลิกดู</a>` : '-';
                                            let code = exp.project_code ? `[${exp.project_code}] ` : '';

                                            html += `
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="py-4 px-6 text-center font-bold text-slate-400">${idx + 1}</td>
                                                    <td class="py-4 px-6">
                                                        <a href="index.php?view=project_detail&id=${exp.project_id}" class="font-bold text-slate-700 hover:text-emerald-500">${code}${exp.project_name}</a>
                                                    </td>
                                                    <td class="py-4 px-6 font-semibold text-slate-700">${exp.item_name}</td>
                                                    <td class="py-4 px-6 text-center font-medium text-slate-500">${exp.expense_type}</td>
                                                    <td class="py-4 px-6 text-slate-500 text-sm">${exp.reference_task || '-'}</td>
                                                    <td class="py-4 px-6 text-center text-slate-500">${new Date(exp.expense_date).toLocaleDateString('th-TH')}</td>
                                                    <td class="py-4 px-6 text-right font-extrabold text-slate-700">${exp.amount.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿</td>
                                                    <td class="py-4 px-6 text-center">${badge}</td>
                                                    <td class="py-4 px-6 text-center">${fileLink}</td>
                                                </tr>
                                            `;
                                        });
                                    }
                                    $('#expenses-table-body').html(html);
                                }
                            }
                        });
                    }
                </script>

            <?php elseif ($view === 'cost_report'): ?>
                <!-- ================== VIEW: COST REPORT ================== -->
                <div class="custom-card p-6 space-y-6">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                        <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                            <span>📋</span> รายงานสรุปต้นทุนค่าแรงรับเหมาโครงการ
                        </h3>
                        <button onclick="window.print()" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm px-4 py-2 rounded-xl flex items-center gap-1.5 shadow shadow-emerald-500/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            <span>พิมพ์รายงาน</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-base text-slate-600 font-medium">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-sm border-b border-slate-100">
                                <tr>
                                    <th class="py-3 px-4 font-bold w-12 text-center hide-checkbox-on-print">
                                        <input type="checkbox" id="cost-report-select-all" class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer" checked onchange="toggleAllCostReports(this)">
                                    </th>
                                    <th class="py-3 px-4 font-bold">รหัส</th>
                                    <th class="py-3 px-4 font-bold">ชื่อโครงการ</th>
                                    <th class="py-3 px-4 font-bold text-center text-rose-500">รายชื่อผู้รับเหมา</th>
                                    <th class="py-3 px-4 font-bold text-right text-rose-500">มูลค่างานรวมเหมา</th>
                                    <th class="py-3 px-4 font-bold text-right text-slate-500">ค่าใช้จ่ายเพิ่มเติม</th>
                                    <th class="py-3 px-4 font-bold text-right text-rose-500">รวมต้นทุน<br><span class="text-xs">ค่าแรงเหมาโปรเจค</span></th>
                                    <th class="py-3 px-4 font-bold text-center">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody id="cost-report-body" class="divide-y divide-slate-100">
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-slate-400 italic">กำลังดึงข้อมูลรายงาน...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    $(document).ready(function() {
                        loadCostReport();
                    });

                    function loadCostReport() {
                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'cost_report' },
                            success: function(res) {
                                if (res.status === 'success') {
                                    let html = '';
                                    if (res.data.length === 0) {
                                        html = '<tr><td colspan="8" class="py-10 text-center text-slate-400 italic">ไม่มีข้อมูลการทำต้นทุนโครงการ</td></tr>';
                                    } else {
                                        res.data.forEach(r => {
                                            const profitClass = r.profit >= 0 ? 'text-emerald-600 font-bold' : 'text-rose-500 font-bold';
                                            
                                            html += `
                                                <tr class="hover:bg-slate-50/50 cost-report-row">
                                                    <td class="py-4 px-4 text-center hide-checkbox-on-print">
                                                        <input type="checkbox" class="cost-report-checkbox w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer" checked onchange="toggleCostReportRow(this)">
                                                    </td>
                                                    <td class="py-4 px-4 font-bold text-slate-500">${r.project_code}</td>
                                                    <td class="py-4 px-4 font-bold text-slate-700">${r.project_name}</td>
                                                    <td class="py-4 px-4 text-rose-600 font-bold text-sm">${r.subcontractor_names}</td>
                                                    <td class="py-4 px-4 text-right font-bold text-slate-700">${r.labor_cost.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                                    <td class="py-4 px-4 text-right font-semibold text-slate-600">${r.additional_expenses.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                                    <td class="py-4 px-4 text-right font-extrabold text-slate-800">${r.total_cost.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                                    <td class="py-4 px-4 text-center">
                                                        <span class="px-2 py-0.5 rounded text-xs font-bold ${r.status==='กำลังดำเนินการ'?'bg-emerald-50 text-emerald-600':(r.status==='เสร็จสิ้น'?'bg-blue-55 text-blue-600':'bg-slate-100 text-slate-600')}">${r.status}</span>
                                                    </td>
                                                </tr>
                                            `;
                                        });
                                    }
                                    $('#cost-report-body').html(html);
                                    updateSelectAllCheckbox();
                                }
                            }
                        });
                    }

                    function toggleCostReportRow(checkbox) {
                        if (checkbox.checked) {
                            $(checkbox).closest('tr').removeClass('hide-on-print');
                        } else {
                            $(checkbox).closest('tr').addClass('hide-on-print');
                        }
                        updateSelectAllCheckbox();
                    }

                    function toggleAllCostReports(selectAllCheckbox) {
                        const isChecked = selectAllCheckbox.checked;
                        $('.cost-report-checkbox').prop('checked', isChecked).each(function() {
                            if (isChecked) {
                                $(this).closest('tr').removeClass('hide-on-print');
                            } else {
                                $(this).closest('tr').addClass('hide-on-print');
                            }
                        });
                    }

                    function updateSelectAllCheckbox() {
                        const total = $('.cost-report-checkbox').length;
                        const checked = $('.cost-report-checkbox:checked').length;
                        if (total > 0) {
                            $('#cost-report-select-all').prop('checked', total === checked);
                        }
                    }
                </script>


            <?php elseif ($view === 'settings'): ?>
                <!-- SETTINGS SECTION -->
                <div class="custom-card p-6 mb-6 border border-blue-200 bg-blue-50/30">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 items-start">
                        <!-- Column 1 -->
                        <div class="settings-col" data-category="project_status">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-base">สถานะโปรเจคทั้งหมด</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-base hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3"></div>
                            <button onclick="saveSettings('project_status', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-base">บันทึก</button>
                        </div>

                        <!-- Column 2 -->
                        <div class="settings-col" data-category="job_type">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-base">ประเภทงาน</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-base hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3"></div>
                            <button onclick="saveSettings('job_type', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-base">บันทึก</button>
                        </div>

                        <!-- Column 3 -->
                        <div class="settings-col" data-category="team_type">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-base">ประเภททีมงาน</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-base hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3"></div>
                            <button onclick="saveSettings('team_type', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-base">บันทึก</button>
                        </div>

                        <!-- Column 4 -->
                        <div class="settings-col" data-category="team_status">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-base">สถานะทีมงาน</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-base hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3"></div>
                            <button onclick="saveSettings('team_status', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-base">บันทึก</button>
                        </div>
                    </div>
                </div>
                
                <script>
                    $(document).ready(function() {
                        if ($('.settings-col').length > 0) {
                            loadSettings();
                        }
                    });

                    function loadSettings() {
                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'settings_list' },
                            success: function(res) {
                                if(res.status === 'success') {
                                    renderSettings(res.data);
                                }
                            }
                        });
                    }

                    function renderSettings(data) {
                        $('.settings-col').each(function() {
                            const cat = $(this).data('category');
                            const list = $(this).find('.settings-list');
                            list.empty();
                            
                            if(data[cat] && data[cat].length > 0) {
                                data[cat].forEach(item => {
                                    list.append(createSettingRow(item.setting_value));
                                });
                            } else {
                                list.append(createSettingRow(''));
                            }
                        });
                    }

                    function createSettingRow(value) {
                        return `
                            <div class="flex items-center gap-2">
                                <input type="text" class="setting-val w-full border border-blue-600 rounded-full px-4 py-1.5 text-base text-center font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-200" value="${value}" placeholder="...">
                                <button onclick="$(this).parent().remove()" class="text-slate-400 hover:text-red-500 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        `;
                    }

                    function addSettingRow(btn) {
                        $(btn).closest('.settings-col').find('.settings-list').append(createSettingRow(''));
                    }

                    function saveSettings(category, btn) {
                        const vals = [];
                        $(btn).closest('.settings-col').find('.setting-val').each(function() {
                            const v = $(this).val().trim();
                            if(v) vals.push(v);
                        });

                        const oldText = $(btn).text();
                        $(btn).text('กำลังบันทึก...').prop('disabled', true);

                        $.ajax({
                            url: 'action.php',
                            type: 'POST',
                            data: { action: 'settings_save', category: category, values: JSON.stringify(vals) },
                            success: function(res) {
                                $(btn).text('บันทึกสำเร็จ!').removeClass('bg-blue-600').addClass('bg-emerald-500');
                                setTimeout(() => {
                                    $(btn).text(oldText).removeClass('bg-emerald-500').addClass('bg-blue-600').prop('disabled', false);
                                }, 2000);
                            }
                        });
                    }
                </script>

            <?php endif; ?>

        </div>
    </main>

    <script>
        // Toggle mobile menu
        $('#mobile-menu-btn').click(function() {
            $('#sidebar').toggleClass('hidden');
        });
    </script>
</body>
</html>

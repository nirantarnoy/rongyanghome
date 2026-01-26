<?php require 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RONGYANG HOME - Main Menu</title>
    <script src="assets/js/tailwindcss.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Prompt', sans-serif;
        }

        .menu-card {
            transition: all 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-8px);
        }

        .menu-icon {
            transition: all 0.3s ease;
        }

        .menu-card:hover .menu-icon {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php include 'navbar.php'; ?>

<div class="container max-w-6xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-2 tracking-tight">
            Welcome Back!
        </h1>
        <div class="w-24 h-1 bg-emerald-500 mx-auto rounded-full"></div>
        <p class="text-gray-500 text-lg mt-4">เลือกเมนูที่ต้องการใช้งาน</p>
    </div>

    <!-- Menu Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php 
        $allowed_modules = isset($_SESSION['allowed_modules']) ? explode(',', $_SESSION['allowed_modules']) : [];
        $user_role = $_SESSION['user_role'] ?? 'user';
        $is_admin = ($user_role === 'admin');
        ?>
        
        <!-- Menu Item 1: Dashboard -->
        <?php if ($is_admin || in_array('admin', $allowed_modules)): ?>
        <a href="dashboard.php" class="menu-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group">
            <div class="flex flex-col items-center">
                <div class="menu-icon bg-emerald-50 text-emerald-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-colors shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-emerald-600 transition-colors">Dashboard</h3>
                <p class="text-gray-500 text-center text-sm">ภาพรวมการทำงานและสถิติ</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- Menu Item: Stock -->
        <?php if ($is_admin || in_array('stock', $allowed_modules)): ?>
        <a href="stock/index.php" class="menu-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group">
            <div class="flex flex-col items-center">
                <div class="menu-icon bg-indigo-50 text-indigo-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-indigo-600 group-hover:text-white transition-colors shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-indigo-600 transition-colors">สต็อกสินค้า</h3>
                <p class="text-gray-500 text-center text-sm">จัดการคลังสินค้าและวัสดุ</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- Menu Item 2: Projects -->
        <?php if ($is_admin || in_array('projects', $allowed_modules)): ?>
        <a href="projects/index.php" class="menu-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group">
            <div class="flex flex-col items-center">
                <div class="menu-icon bg-amber-50 text-amber-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-600 group-hover:text-white transition-colors shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-amber-600 transition-colors">โปรเจคบ้าน & เฟอร์นิเจอร์</h3>
                <p class="text-gray-500 text-center text-sm">จัดการรายรับ-รายจ่ายโครงการ</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- Menu Item 3: Company Transaction -->
        <?php if ($is_admin || in_array('companytransaction', $allowed_modules)): ?>
        <a href="companytransaction/index.php" class="menu-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group">
            <div class="flex flex-col items-center">
                <div class="menu-icon bg-green-50 text-green-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-green-600 group-hover:text-white transition-colors shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-green-600 transition-colors">รายรับรายจ่ายบ้านสักทอง</h3>
                <p class="text-gray-500 text-center text-sm">บันทึกรายรับ-รายจ่ายโรงงาน</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- Menu Item: Documents (จัดการเอกสาร) -->
        <a href="documents/index.php" class="menu-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group">
            <div class="flex flex-col items-center">
                <div class="menu-icon bg-rose-50 text-rose-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-rose-600 group-hover:text-white transition-colors shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-rose-600 transition-colors">จัดการเอกสาร</h3>
                <p class="text-gray-500 text-center text-sm">ใบเสนอราคา ใบแจ้งหนี้ ใบเสร็จ</p>
            </div>
        </a>

        <!-- Menu Item 4: Company -->
        <?php if ($is_admin || in_array('admin', $allowed_modules)): ?>
        <a href="company.php" class="menu-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group">
            <div class="flex flex-col items-center">
                <div class="menu-icon bg-blue-50 text-blue-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors">จัดการบริษัท</h3>
                <p class="text-gray-500 text-center text-sm">ข้อมูลบริษัทและสาขา</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- Menu Item 5: Users -->
        <?php if ($is_admin || in_array('admin', $allowed_modules)): ?>
        <a href="user.php" class="menu-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group">
            <div class="flex flex-col items-center">
                <div class="menu-icon bg-purple-50 text-purple-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 group-hover:text-white transition-colors shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-purple-600 transition-colors">จัดการผู้ใช้งาน</h3>
                <p class="text-gray-500 text-center text-sm">กำหนดสิทธิ์และบัญชีผู้ใช้</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- Menu Item 6: Action Logs -->
        <?php if ($is_admin || in_array('admin', $allowed_modules)): ?>
        <a href="action_logs.php" class="menu-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group">
            <div class="flex flex-col items-center">
                <div class="menu-icon bg-slate-50 text-slate-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-slate-600 group-hover:text-white transition-colors shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-slate-600 transition-colors">ประวัติกิจกรรม</h3>
                <p class="text-gray-500 text-center text-sm">ตรวจสอบประวัติการใช้งานระบบ</p>
            </div>
        </a>
        <?php endif; ?>

        <!-- Menu Item 7: Login Logs -->
        <?php if ($is_admin || in_array('admin', $allowed_modules)): ?>
        <a href="login_logs.php" class="menu-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 group">
            <div class="flex flex-col items-center">
                <div class="menu-icon bg-teal-50 text-teal-600 w-20 h-20 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-teal-600 group-hover:text-white transition-colors shadow-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-teal-600 transition-colors">ประวัติการเข้าสู่ระบบ</h3>
                <p class="text-gray-500 text-center text-sm">ตรวจสอบประวัติการเข้าใช้งานระบบ</p>
            </div>
        </a>
        <?php endif; ?>

    </div>

    <!-- Footer -->
    <div class="text-center mt-12 border-t border-gray-200 pt-8">
        <p class="text-gray-400 text-sm">© 2025 RONGYANG HOME. All rights reserved.</p>
    </div>
</div>
</body>
</html>
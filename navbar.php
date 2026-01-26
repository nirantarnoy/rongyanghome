<?php
// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .glass-effect {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
    }
    .nav-link {
        position: relative;
        color: #64748b; /* slate-500 */
        transition: color 0.3s;
    }
    .nav-link:hover {
        color: #10b981; /* emerald-500 */
    }
    .nav-link::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: -4px;
        left: 0;
        background-color: #10b981;
        transition: width 0.3s;
    }
    .nav-link:hover::after {
        width: 100%;
    }
    /* Active State */
    .nav-link.active {
        color: #059669; /* emerald-600 */
        font-weight: 600;
    }
    .nav-link.active::after {
        width: 100%;
        background-color: #059669;
    }
</style>

<!-- Ensure SweetAlert2 is loaded -->
<script src="assets/js/sweetalert2.js"></script>

<nav class="bg-white shadow-sm sticky top-0 z-50 glass-effect mb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <!-- Logo / Brand -->
            <div class="flex items-center gap-3">
                <a href="index.php" class="flex items-center gap-3 group">
                    <div class="bg-white p-1 rounded-lg shadow-sm border border-gray-100 group-hover:shadow-md transition-all">
                       <img src="assets/logo/logo.png" alt="Logo" class="h-12 w-auto" onerror="this.src='https://via.placeholder.com/150?text=LOGO'">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800 leading-tight group-hover:text-emerald-600 transition-colors">RONGYANG HOME</h1>
                        <p class="text-xs text-slate-500"><?= $_SESSION['company_name'] ?? 'Production Management System' ?></p>
                    </div>
                </a>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-6">
                <?php 
                $allowed_modules = isset($_SESSION['allowed_modules']) ? explode(',', $_SESSION['allowed_modules']) : [];
                $user_role = $_SESSION['user_role'] ?? 'user';
                $is_admin = ($user_role === 'admin');
                ?>
                
                <a href="index.php" class="nav-link text-sm font-medium <?= $current_page == 'index.php' ? 'active' : '' ?>">เมนูหลัก</a>
                
                <?php if ($is_admin || in_array('admin', $allowed_modules)): ?>
                    <a href="dashboard.php" class="nav-link text-sm font-medium <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                    <a href="company.php" class="nav-link text-sm font-medium <?= $current_page == 'company.php' ? 'active' : '' ?>">บริษัท</a>
                    <a href="user.php" class="nav-link text-sm font-medium <?= $current_page == 'user.php' ? 'active' : '' ?>">ผู้ใช้งาน</a>
                    <a href="login_logs.php" class="nav-link text-sm font-medium <?= $current_page == 'login_logs.php' ? 'active' : '' ?>">ประวัติการเข้าสู่ระบบ</a>
                <?php endif; ?>
                <div class="h-6 w-px bg-slate-200"></div>

                <?php if ($is_admin): ?>
                <a href="year_management.php" class="flex items-center gap-2 text-sm font-medium <?= $current_page == 'year_management.php' ? 'text-indigo-600' : 'text-slate-600 hover:text-indigo-600' ?> transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-bold text-indigo-600"><?= $_SESSION['active_year'] ?? date('Y') ?></span>
                </a>
                <div class="h-6 w-px bg-slate-200"></div>
                <?php endif; ?>

                <div class="flex items-center gap-3 text-sm text-slate-600 bg-slate-50 px-4 py-1.5 rounded-full border border-slate-200">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="font-bold"><?= $_SESSION['user_login'] ?></span>
                    </div>
                    <div class="w-px h-4 bg-slate-300"></div>
                    <span id="nav-clock"><?= date('H:i') ?></span>
                </div>
                <!-- Logout Button -->
                <button onclick="confirmLogout()" class="flex items-center gap-2 text-sm font-medium text-red-600 hover:text-red-700 bg-red-100 hover:bg-red-200 px-3 py-1.5 rounded-full border border-red-200 transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    ออกจากระบบ
                </button>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden">
                <button class="text-slate-500 hover:text-indigo-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>

<script>
    function updateNavClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
        const clockEl = document.getElementById('nav-clock');
        if(clockEl) clockEl.textContent = timeString;
    }
    setInterval(updateNavClock, 1000);

    function confirmLogout() {
        // Check if Swal is defined
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'ยืนยันการออกจากระบบ?',
                text: "คุณต้องการออกจากระบบใช่หรือไม่",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'ใช่, ออกจากระบบ',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'font-medium rounded-lg px-4 py-2',
                    cancelButton: 'font-medium rounded-lg px-4 py-2 text-slate-700'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        } else {
            // Fallback if SweetAlert2 is not loaded
            if(confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
                window.location.href = 'logout.php';
            }
        }
    }
</script>

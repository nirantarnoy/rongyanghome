<?php
// Session check for portability
session_start();
if (!isset($_SESSION['user_login'])) {
    if (file_exists('../login.php')) {
        header("Location: ../login.php");
    } else {
        header("Location: login.php");
    }
    exit();
}

$display_year = "2569";
$view = $_GET['view'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกรายรับรายจ่ายโรงงาน - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f0f9f4;
            background-image: linear-gradient(45deg, #e6f4ea 25%, transparent 25%, transparent 50%, #e6f4ea 50%, #e6f4ea 75%, transparent 75%, transparent);
            background-size: 20px 20px;
        }

        .header-box {
            background-color: #10b981; /* emerald-500 */
            border: 1px solid #059669;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.2);
        }

        .nav-tabs {
            background-color: #ffffff;
            border-radius: 2rem;
            padding: 0.25rem;
            display: inline-flex;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .tab-item {
            padding: 0.5rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #64748b;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 2rem;
        }

        .tab-item.active {
            background-color: #f0fdf4;
            color: #059669;
            font-weight: 700;
            box-shadow: inset 0 0 0 1px #d1fae5;
        }

        .btn-add-main {
            background-color: #10b981;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-add-main:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }

        .stat-card {
            border-radius: 1rem;
            padding: 1.5rem;
            color: white;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .card-income { background-color: #10b981; }
        .card-expense { background-color: #ef4444; }
        .card-profit { background-color: #047857; }

        .count-badge {
            background-color: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 1rem;
            padding: 1rem 2rem;
            display: inline-block;
        }

        .content-area {
            background-color: #f7fee7;
            border: 1px solid #d9f99d;
            border-radius: 1.5rem;
            min-height: 300px;
        }

        .category-container {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.5rem;
            height: 100%;
        }

        .category-item {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s;
        }

        .category-item:hover {
            background-color: #f0fdf4;
            transform: translateX(4px);
            border-color: #10b981;
        }

        .btn-mini-add {
            background-color: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .search-input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border-radius: 0.75rem;
            border: 1px solid #d1fae5;
            background-color: white;
            outline: none;
        }

        .transaction-row {
            background-color: white;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid #d1fae5;
            transition: all 0.2s;
        }

        .transaction-row:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
            border-color: #10b981;
        }
    </style>
</head>
<body class="p-4 md:p-10">

    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="header-box p-8 md:p-12 text-center text-white mb-8">
            <div class="flex justify-center mb-4 text-4xl">
                <span>🌳</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold mb-2 tracking-tight">
                บันทึกรายรับรายจ่ายโรงงานบ้านสักทองร้องแหย่ง
            </h1>
            <p class="text-lg opacity-90 font-medium">
                ประจำปี <?php echo $display_year; ?>
            </p>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
            <!-- Tabs -->
            <div class="nav-tabs">
                <a href="index.php?view=dashboard" class="tab-item <?php echo $view == 'dashboard' ? 'active' : ''; ?>">
                    <span class="text-lg">📊</span> Dashboard
                </a>
                <a href="index.php?view=list" class="tab-item <?php echo $view == 'list' ? 'active' : ''; ?>">
                    <span class="text-lg">📝</span> รายการ
                </a>
                <a href="index.php?view=project" class="tab-item <?php echo $view == 'project' ? 'active' : ''; ?>">
                    <span class="text-lg">📁</span> โครงการ
                </a>
                <a href="index.php?view=category" class="tab-item <?php echo $view == 'category' ? 'active' : ''; ?>">
                    <span class="text-lg">🏷️</span> หมวดหมู่
                </a>
            </div>

            <!-- Add Button -->
            <?php if ($view == 'list' || $view == 'project'): ?>
            <button onclick="handleAddNew()" class="btn-add-main">
                เพิ่มรายการใหม่
            </button>
            <?php endif; ?>
        </div>

        <?php if ($view == 'dashboard'): ?>
            <!-- Summary Section -->
            <div class="mb-10">
                <h2 class="text-2xl font-bold text-[#065f46] mb-6">สรุปภาพรวม</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="stat-card card-income shadow-lg shadow-emerald-100">
                        <p class="text-sm opacity-90 mb-1">รายรับทั้งหมด</p>
                        <p id="dashIncome" class="text-3xl font-bold">0.00 ฿</p>
                    </div>
                    <div class="stat-card card-expense shadow-lg shadow-red-100">
                        <p class="text-sm opacity-90 mb-1">รายจ่ายทั้งหมด</p>
                        <p id="dashExpense" class="text-3xl font-bold">0.00 ฿</p>
                    </div>
                    <div class="stat-card card-profit shadow-lg shadow-emerald-200">
                        <p class="text-sm opacity-90 mb-1">กำไร/ขาดทุน</p>
                        <p id="dashProfit" class="text-3xl font-bold">+0.00 ฿</p>
                    </div>
                </div>

                <div class="text-center">
                    <div class="count-badge shadow-sm">
                        <p class="text-sm font-bold text-[#065f46] mb-2 flex items-center justify-center gap-2">
                            📊 จำนวนรายการทั้งหมด
                        </p>
                        <p id="dashCount" class="text-4xl font-black text-[#065f46]">0 / 999</p>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div id="dashContent" class="content-area p-12 flex flex-col items-center justify-center text-center shadow-inner">
                <p class="text-gray-500 italic">กำลังโหลดข้อมูล...</p>
            </div>

            <script>
                $(document).ready(function() {
                    loadDashboardStats();
                });

                function loadDashboardStats() {
                    $.ajax({
                        url: '../projects/transaction_action.php',
                        type: 'GET',
                        data: { action: 'get_dashboard_stats', module_type: 2 },
                        success: function(response) {
                            try {
                                const res = JSON.parse(response);
                                if (res.status === 'success') {
                                    const d = res.data;
                                    $('#dashIncome').text(d.total_income.toLocaleString() + ' ฿');
                                    $('#dashExpense').text(d.total_expense.toLocaleString() + ' ฿');
                                    const profitSign = d.total_profit >= 0 ? '+' : '';
                                    $('#dashProfit').text(profitSign + d.total_profit.toLocaleString() + ' ฿');
                                    $('#dashCount').text(d.total_count + ' / 999');
                                    
                                    if (d.total_count > 0) {
                                        $('#dashContent').html(`
                                            <div class="mb-6">
                                                <svg class="w-16 h-16 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                            </div>
                                            <h3 class="text-xl font-bold text-[#065f46] mb-2">ระบบพร้อมใช้งาน</h3>
                                            <p class="text-gray-500">มีการบันทึกข้อมูลแล้ว ${d.total_count} รายการ</p>
                                        `);
                                    } else {
                                        $('#dashContent').html(`
                                            <div class="mb-6">
                                                <svg class="w-16 h-16 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                            </div>
                                            <h3 class="text-xl font-bold text-[#065f46] mb-2">ยังไม่มีข้อมูล</h3>
                                            <p class="text-gray-500">เริ่มต้นโดยการเพิ่มโครงการและบันทึกรายการ</p>
                                        `);
                                    }
                                }
                            } catch (e) { console.error(e); }
                        }
                    });
                }
            </script>

        <?php elseif ($view == 'list'): ?>
            <!-- List View -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-[#065f46]">รายการทั้งหมด</h2>
                
                <div class="relative w-full md:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-emerald-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text" id="transactionSearch" class="search-input" placeholder="ค้นหารายการ..." onkeyup="loadTransactions()">
                </div>
            </div>

            <div class="content-area shadow-inner !justify-start !items-stretch p-6">
                <div id="transactionList" class="space-y-1">
                    <!-- Transactions loaded via AJAX -->
                </div>
            </div>

            <script>
                $(document).ready(function() {
                    loadTransactions();
                });

                function loadTransactions() {
                    const search = $('#transactionSearch').val();
                    $.ajax({
                        url: '../projects/transaction_action.php',
                        type: 'GET',
                        data: { action: 'list', module_type: 2, search: search },
                        success: function(response) {
                            try {
                                const res = JSON.parse(response);
                                if (res.status === 'success') {
                                    renderTransactionList(res.data);
                                }
                            } catch (e) { console.error(e); }
                        }
                    });
                }

                function renderTransactionList(data) {
                    let html = '';
                    if (data.length === 0) {
                        html = `
                            <div class="text-center py-20 opacity-50">
                                <p class="text-2xl font-bold text-[#065f46]">ไม่พบรายการ</p>
                            </div>
                        `;
                    } else {
                        data.forEach(t => {
                            const amountClass = t.direction === 'income' ? 'text-emerald-600' : 'text-red-600';
                            const amountPrefix = t.direction === 'income' ? '+' : '-';
                            html += `
                                <div class="transaction-row group" onclick="editTransaction(${JSON.stringify(t).replace(/"/g, '&quot;')})">
                                    <div class="flex flex-wrap items-center justify-between gap-4">
                                        <div class="flex items-center gap-4 flex-1 min-w-[200px]">
                                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-2xl shadow-sm">
                                                ${t.category_icon}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-700">${t.category_name}</span>
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">${t.project_name}</span>
                                                </div>
                                                <p class="text-sm text-gray-500">${t.note || '-'}</p>
                                            </div>
                                        </div>
                                        <div class="text-right min-w-[120px]">
                                            <p class="text-xs text-gray-400 mb-1">${t.transaction_date}</p>
                                            <p class="text-lg font-bold ${amountClass}">${amountPrefix}${parseFloat(t.amount).toLocaleString()} ฿</p>
                                        </div>
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="event.stopPropagation(); deleteTransaction(${t.id})" class="p-2 text-red-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    $('#transactionList').html(html);
                }

                function openTransactionModal(id = null, data = null) {
                    $.ajax({
                        url: '../projects/transaction_action.php',
                        type: 'GET',
                        data: { action: 'get_form_data', module_type: 2 },
                        success: function(response) {
                            const res = JSON.parse(response);
                            const title = id ? 'แก้ไขรายการ' : 'เพิ่มรายการใหม่';
                            
                            let projectOptions = '<option value="">เลือกโครงการ</option>';
                            res.projects.forEach(p => {
                                projectOptions += `<option value="${p.id}" ${data && data.project_id == p.id ? 'selected' : ''}>${p.project_name}</option>`;
                            });

                            let categoryOptions = '<option value="">เลือกหมวดหมู่</option>';
                            res.categories.forEach(c => {
                                const dirText = c.direction === 'income' ? '(รายรับ)' : '(รายจ่าย)';
                                categoryOptions += `<option value="${c.id}" ${data && data.category_id == c.id ? 'selected' : ''}>${c.icon} ${c.name} ${dirText}</option>`;
                            });

                            Swal.fire({
                                title: title,
                                html: `
                                    <div class="text-left space-y-4 p-2">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">วันที่</label>
                                                <input type="date" id="tDate" class="swal2-input !m-0 !w-full" value="${data ? data.transaction_date : new Date().toISOString().split('T')[0]}">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนเงิน</label>
                                                <input type="number" step="0.01" id="tAmount" class="swal2-input !m-0 !w-full" placeholder="0.00" value="${data ? data.amount : ''}">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">โครงการ</label>
                                            <select id="tProject" class="swal2-input !m-0 !w-full">${projectOptions}</select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                                            <select id="tCategory" class="swal2-input !m-0 !w-full">${categoryOptions}</select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ</label>
                                            <textarea id="tNote" class="swal2-textarea !m-0 !w-full !h-20" placeholder="ระบุรายละเอียดเพิ่มเติม">${data ? data.note : ''}</textarea>
                                        </div>
                                    </div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: 'บันทึก',
                                cancelButtonText: 'ยกเลิก',
                                confirmButtonColor: '#10b981',
                                preConfirm: () => {
                                    const transaction_date = $('#tDate').val();
                                    const amount = $('#tAmount').val();
                                    const project_id = $('#tProject').val();
                                    const category_id = $('#tCategory').val();
                                    const note = $('#tNote').val();

                                    if (!transaction_date || !amount || !project_id || !category_id) {
                                        Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                                        return false;
                                    }
                                    return { transaction_date, amount, project_id, category_id, note };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: '../projects/transaction_action.php',
                                        type: 'POST',
                                        data: {
                                            action: 'save',
                                            id: id,
                                            ...result.value,
                                            module_type: 2
                                        },
                                        success: function(response) {
                                            const res = JSON.parse(response);
                                            if (res.status === 'success') {
                                                loadTransactions();
                                                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false });
                                            } else {
                                                Swal.fire('ผิดพลาด', res.message, 'error');
                                            }
                                        }
                                    });
                                }
                            });
                        }
                    });
                }

                function editTransaction(data) {
                    openTransactionModal(data.id, data);
                }

                function deleteTransaction(id) {
                    Swal.fire({
                        title: 'ยืนยันการลบ?',
                        text: "คุณต้องการลบรายการนี้ใช่หรือไม่",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'ใช่, ลบเลย',
                        cancelButtonText: 'ยกเลิก'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '../projects/transaction_action.php',
                                type: 'POST',
                                data: { action: 'delete', id: id },
                                success: function(response) {
                                    const res = JSON.parse(response);
                                    if (res.status === 'success') {
                                        loadTransactions();
                                    } else {
                                        Swal.fire('ผิดพลาด', res.message, 'error');
                                    }
                                }
                            });
                        }
                    });
                }
            </script>

        <?php elseif ($view == 'project'): ?>
            <!-- Project View -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-[#065f46]">จัดการโครงการ</h2>
                
                <div class="relative w-full md:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-emerald-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text" id="projectSearch" class="search-input" placeholder="ค้นหาโครงการ..." onkeyup="loadProjects()">
                </div>
            </div>

            <div class="content-area shadow-inner !justify-start !items-stretch p-6">
                <div id="projectList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Projects loaded via AJAX -->
                </div>
            </div>

            <script>
                $(document).ready(function() {
                    loadProjects();
                });

                function loadProjects() {
                    const search = $('#projectSearch').val();
                    $.ajax({
                        url: '../projects/project_action.php',
                        type: 'GET',
                        data: { action: 'list', module_type: 2, search: search },
                        success: function(response) {
                            try {
                                const res = JSON.parse(response);
                                if (res.status === 'success') {
                                    renderProjectList(res.data);
                                }
                            } catch (e) { console.error(e); }
                        }
                    });
                }

                function renderProjectList(projects) {
                    let html = '';
                    if (projects.length === 0) {
                        html = '<div class="col-span-full text-center py-20 text-gray-400 italic">ยังไม่มีข้อมูลโครงการ</div>';
                    } else {
                        projects.forEach(p => {
                            html += `
                                <div class="bg-white p-5 rounded-2xl border border-emerald-100 shadow-sm hover:shadow-md transition-all group relative">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xl">🌳</span>
                                                <h4 class="font-bold text-emerald-800 text-lg">${p.project_name}</h4>
                                            </div>
                                            <p class="text-sm text-gray-500 line-clamp-2">${p.note || 'ไม่มีหมายเหตุ'}</p>
                                        </div>
                                        <button onclick="deleteProject(${p.id})" class="text-red-300 hover:text-red-500 p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="../projects/project_details.php?id=${p.id}&module_type=2" class="flex-1 text-center py-2 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 transition-colors">
                                            📊 สรุปโครงการ
                                        </a>
                                        <button onclick="openProjectModal(${p.id}, '${p.project_name}', '${p.note || ''}')" class="px-3 py-2 border border-emerald-100 text-emerald-600 text-sm font-bold rounded-xl hover:bg-emerald-50 transition-colors">
                                            ✏️ แก้ไข
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    $('#projectList').html(html);
                }

                function openProjectModal(id = null, name = '', note = '') {
                    const title = id ? 'แก้ไขโครงการ' : 'เพิ่มโครงการใหม่';
                    Swal.fire({
                        title: title,
                        html: `
                            <div class="text-left space-y-4 p-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อโครงการ</label>
                                    <input id="projectName" class="swal2-input !m-0 !w-full" placeholder="ระบุชื่อโครงการ" value="${name}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ</label>
                                    <textarea id="projectNote" class="swal2-textarea !m-0 !w-full !h-24" placeholder="ระบุหมายเหตุ (ถ้ามี)">${note}</textarea>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'บันทึก',
                        cancelButtonText: 'ยกเลิก',
                        confirmButtonColor: '#10b981',
                        preConfirm: () => {
                            const project_name = $('#projectName').val();
                            const note = $('#projectNote').val();
                            if (!project_name) {
                                Swal.showValidationMessage('กรุณาระบุชื่อโครงการ');
                                return false;
                            }
                            return { project_name, note };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '../projects/project_action.php',
                                type: 'POST',
                                data: {
                                    action: 'save',
                                    id: id,
                                    project_name: result.value.project_name,
                                    note: result.value.note,
                                    module_type: 2
                                },
                                success: function(response) {
                                    const res = JSON.parse(response);
                                    if (res.status === 'success') {
                                        loadProjects();
                                        Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message, timer: 1500, showConfirmButton: false });
                                    } else {
                                        Swal.fire('ผิดพลาด', res.message, 'error');
                                    }
                                }
                            });
                        }
                    });
                }

                function deleteProject(id) {
                    Swal.fire({
                        title: 'ยืนยันการลบ?',
                        text: "ข้อมูลรายการที่เกี่ยวข้องกับโครงการนี้อาจได้รับผลกระทบ",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'ใช่, ลบเลย',
                        cancelButtonText: 'ยกเลิก'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '../projects/project_action.php',
                                type: 'POST',
                                data: { action: 'delete', id: id },
                                success: function(response) {
                                    const res = JSON.parse(response);
                                    if (res.status === 'success') {
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

        <?php elseif ($view == 'category'): ?>
            <!-- Category View -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-[#065f46] mb-2">จัดการหมวดหมู่</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Expense Categories -->
                <div class="category-container shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-[#065f46]">หมวดหมู่รายจ่าย</h3>
                        <button onclick="openCategoryModal('expense')" class="btn-mini-add">+ เพิ่ม</button>
                    </div>
                    <div id="expenseList" class="space-y-2">
                        <!-- Items loaded via AJAX -->
                    </div>
                </div>

                <!-- Income Categories -->
                <div class="category-container shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-[#065f46]">หมวดหมู่รายรับ</h3>
                        <button onclick="openCategoryModal('income')" class="btn-mini-add">+ เพิ่ม</button>
                    </div>
                    <div id="incomeList" class="space-y-2">
                        <!-- Items loaded via AJAX -->
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function() {
                    loadCategories();
                });

                function loadCategories() {
                    $.ajax({
                        url: '../projects/category_action.php',
                        type: 'GET',
                        data: { action: 'list', module_type: 2 }, // 2 = Company
                        success: function(response) {
                            try {
                                const res = JSON.parse(response);
                                renderList('expenseList', res.expense);
                                renderList('incomeList', res.income);
                            } catch (e) { console.error(e); }
                        }
                    });
                }

                function renderList(targetId, items) {
                    let html = '';
                    if (items.length === 0) {
                        html = '<div class="text-center py-8 text-gray-400 italic">ยังไม่มีข้อมูล</div>';
                    } else {
                        items.forEach(item => {
                            html += `
                                <div class="category-item group">
                                    <div class="flex items-center gap-3 flex-1" onclick="editCategory(${item.id}, '${item.name}', '${item.icon}', '${item.direction}')">
                                        <span class="text-xl">${item.icon}</span>
                                        <span class="font-medium text-slate-700">${item.name}</span>
                                    </div>
                                    <button onclick="deleteCategory(${item.id})" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            `;
                        });
                    }
                    $(`#${targetId}`).html(html);
                }

                function openCategoryModal(direction, id = null, name = '', icon = '📁') {
                    const title = id ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่ใหม่';
                    Swal.fire({
                        title: title,
                        html: `
                            <div class="text-left space-y-4 p-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อหมวดหมู่</label>
                                    <input id="catName" class="swal2-input !m-0 !w-full" placeholder="ระบุชื่อหมวดหมู่" value="${name}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">เลือกไอคอน</label>
                                    <div class="grid grid-cols-6 gap-2 bg-gray-50 p-3 rounded-xl border border-gray-100" id="iconPicker">
                                        ${['🌳', '💰', '💳', '🏦', '🏠', '🏗️', '🛠️', '🪵', '🚚', '🧱', '🎨', '🔌', '🚿', '📁', '📑', '🛒', '🍽️', '⛽', '🔧', '🧹', '💡', '📱', '📦', '🎁'].map(emoji => `
                                            <button type="button" onclick="$('.icon-btn').removeClass('bg-white shadow-sm scale-110 border-emerald-500'); $(this).addClass('bg-white shadow-sm scale-110 border-emerald-500'); $('#catIcon').val('${emoji}')" 
                                                class="icon-btn w-10 h-10 flex items-center justify-center text-xl rounded-lg border border-transparent hover:bg-white hover:shadow-sm transition-all ${emoji === icon ? 'bg-white shadow-sm scale-110 border-emerald-500' : ''}">
                                                ${emoji}
                                            </button>
                                        `).join('')}
                                    </div>
                                    <input type="hidden" id="catIcon" value="${icon}">
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'บันทึก',
                        cancelButtonText: 'ยกเลิก',
                        confirmButtonColor: '#10b981',
                        preConfirm: () => {
                            const name = $('#catName').val();
                            const icon = $('#catIcon').val();
                            if (!name) {
                                Swal.showValidationMessage('กรุณาระบุชื่อหมวดหมู่');
                                return false;
                            }
                            return { name, icon };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '../projects/category_action.php',
                                type: 'POST',
                                data: {
                                    action: 'save',
                                    id: id,
                                    name: result.value.name,
                                    icon: result.value.icon,
                                    direction: direction,
                                    module_type: 2 // 2 = Company
                                },
                                success: function(response) {
                                    try {
                                        const res = JSON.parse(response);
                                        if (res.status === 'success') {
                                            loadCategories();
                                        } else {
                                            Swal.fire('ผิดพลาด', res.message, 'error');
                                        }
                                    } catch (e) {
                                        console.error('JSON Parse Error:', e, response);
                                        Swal.fire('ผิดพลาด', 'การตอบกลับจากเซิร์ฟเวอร์ไม่ถูกต้อง', 'error');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX Error:', status, error, xhr.responseText);
                                    Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
                                }
                            });
                        }
                    });
                }

                function editCategory(id, name, icon, direction) {
                    openCategoryModal(direction, id, name, icon);
                }

                function deleteCategory(id) {
                    Swal.fire({
                        title: 'ยืนยันการลบ?',
                        text: "คุณต้องการลบหมวดหมู่นี้ใช่หรือไม่",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'ใช่, ลบเลย',
                        cancelButtonText: 'ยกเลิก'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '../projects/category_action.php',
                                type: 'POST',
                                data: { action: 'delete', id: id },
                                success: function(response) {
                                    const res = JSON.parse(response);
                                    if (res.status === 'success') {
                                        loadCategories();
                                    } else {
                                        Swal.fire('ผิดพลาด', res.message, 'error');
                                    }
                                }
                            });
                        }
                    });
                }
            </script>
        <?php endif; ?>

        <script>
            function handleAddNew() {
                const currentView = '<?php echo $view; ?>';
                if (currentView === 'project') {
                    openProjectModal();
                } else if (currentView === 'list') {
                    openTransactionModal();
                }
            }
        </script>
    </div>

</body>
</html>

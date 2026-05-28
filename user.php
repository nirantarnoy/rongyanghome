<?php
require 'auth_check.php';
include 'config.php';

$user_role = $_SESSION['user_role'] ?? 'user';

// Only admin can manage users
if ($user_role !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch companies for the dropdown
$companySql = "SELECT id, company_name FROM company ORDER BY company_name ASC";
$companyRes = mysqli_query($conn, $companySql);
$companies = [];
while ($row = mysqli_fetch_assoc($companyRes)) {
    $companies[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - RONGYANG HOME</title>
    <script src="assets/js/tailwindcss.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Prompt', sans-serif; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php include 'navbar.php'; ?>

<div class="container max-w-7xl mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                จัดการผู้ใช้งาน
            </h1>
            <p class="text-gray-500 mt-1">เพิ่ม แก้ไข และลบข้อมูลผู้ใช้งานในระบบ</p>
        </div>
        <button onclick="openModal('add')" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-all duration-200 shadow-lg shadow-indigo-200 group">
            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            เพิ่มผู้ใช้งานใหม่
        </button>
    </div>

    <!-- Filter & Search Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="relative flex-1 md:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text" id="searchInput" placeholder="ค้นหาชื่อผู้ใช้, ชื่อ-นามสกุล..." 
                           class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
            </div>
            
            <div class="flex items-center gap-3 w-full md:w-auto">
                <span class="text-sm text-gray-500 whitespace-nowrap">แสดง:</span>
                <select id="perPage" class="border border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    <option value="20">20 รายการ</option>
                    <option value="50">50 รายการ</option>
                    <option value="100">100 รายการ</option>
                    <option value="all">ทั้งหมด</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">ลำดับ</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">ชื่อผู้ใช้งาน</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">ชื่อ-นามสกุล</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">บริษัท</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">สิทธิ์การใช้งาน</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="userTableBody" class="divide-y divide-gray-50">
                    <!-- Data will be loaded here via AJAX -->
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div id="pagination" class="px-6 py-4 bg-gray-50/30 flex items-center justify-between border-t border-gray-100">
            <!-- Pagination content will be loaded here -->
        </div>
    </div>
</div>

<!-- CRUD Modal -->
<div id="userModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50 backdrop-blur-sm" onclick="closeModal()"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-8 pt-8 pb-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 id="modalTitle" class="text-2xl font-bold text-gray-800">เพิ่มผู้ใช้งานใหม่</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="userForm" class="space-y-5">
                    <input type="hidden" id="user_id" name="id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">ชื่อผู้ใช้งาน <span class="text-red-500">*</span></label>
                            <input type="text" name="username" required
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">รหัสผ่าน <span id="pwdLabel" class="text-red-500">*</span></label>
                            <input type="password" name="password" id="passwordField"
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none"
                                   placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยน">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">บริษัท <span class="text-red-500">*</span></label>
                            <select name="company_id" required
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                                <option value="">-- เลือกบริษัท --</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['company_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">สิทธิ์การใช้งาน</label>
                            <select name="role" required
                                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                                <option value="user">User (ผู้ใช้งานทั่วไป)</option>
                                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">สิทธิ์การเข้าถึงโมดูล</label>
                        <div class="grid grid-cols-2 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="modules[]" value="admin" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">Admin (ระบบหลัก)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="modules[]" value="stock" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">Stock (คลังสินค้า)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="modules[]" value="projects" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">Projects (โครงการ)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="modules[]" value="companytransaction" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">Transaction (บัญชีบริษัท)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" name="modules[]" value="payroll" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors">Payroll (ระบบเงินเดือน)</span>
                            </label>
                        </div>
                        <input type="hidden" name="allowed_modules" id="allowed_modules">
                    </div>
                </form>
            </div>
            
            <div class="bg-gray-50 px-8 py-6 flex flex-row-reverse gap-3">
                <button type="button" onclick="saveUser()" 
                        class="inline-flex justify-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-indigo-100">
                    บันทึกข้อมูล
                </button>
                <button type="button" onclick="closeModal()"
                        class="inline-flex justify-center px-6 py-2.5 bg-white border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;

$(document).ready(function() {
    loadData();

    $('#searchInput').on('keyup', function() {
        currentPage = 1;
        loadData();
    });

    $('#perPage').on('change', function() {
        currentPage = 1;
        loadData();
    });
});

function loadData(page = 1) {
    currentPage = page;
    const search = $('#searchInput').val();
    const perPage = $('#perPage').val();

    $.ajax({
        url: 'user_action.php',
        type: 'GET',
        data: {
            action: 'list',
            search: search,
            perPage: perPage,
            page: currentPage
        },
        success: function(response) {
            const res = JSON.parse(response);
            $('#userTableBody').html(res.html);
            $('#pagination').html(res.pagination);
        }
    });
}

function openModal(type, id = null) {
    $('#userForm')[0].reset();
    $('#user_id').val('');
    
    if (type === 'add') {
        $('#modalTitle').text('เพิ่มผู้ใช้งานใหม่');
        $('#pwdLabel').show();
        $('#passwordField').attr('required', true);
        $('#passwordField').attr('placeholder', 'กำหนดรหัสผ่าน');
        $('#userModal').removeClass('hidden');
    } else {
        $('#modalTitle').text('แก้ไขข้อมูลผู้ใช้งาน');
        $('#pwdLabel').hide();
        $('#passwordField').attr('required', false);
        $('#passwordField').attr('placeholder', 'เว้นว่างไว้หากไม่ต้องการเปลี่ยน');
        $.ajax({
            url: 'user_action.php',
            type: 'GET',
            data: { action: 'get', id: id },
            success: function(response) {
                const data = JSON.parse(response);
                $('#user_id').val(data.id);
                $('input[name="username"]').val(data.username);
                $('input[name="full_name"]').val(data.full_name);
                $('select[name="role"]').val(data.role);
                $('select[name="company_id"]').val(data.company_id);
                
                // Set checkboxes
                $('input[name="modules[]"]').prop('checked', false);
                if (data.allowed_modules) {
                    const modules = data.allowed_modules.split(',');
                    modules.forEach(m => {
                        $(`input[name="modules[]"][value="${m}"]`).prop('checked', true);
                    });
                }
                
                $('#userModal').removeClass('hidden');
            }
        });
    }
}

function closeModal() {
    $('#userModal').addClass('hidden');
}

function saveUser() {
    // Collect checked modules
    const selectedModules = [];
    $('input[name="modules[]"]:checked').each(function() {
        selectedModules.push($(this).val());
    });
    $('#allowed_modules').val(selectedModules.join(','));

    const formData = $('#userForm').serialize();
    const action = $('#user_id').val() ? 'update' : 'create';

    $.ajax({
        url: 'user_action.php',
        type: 'POST',
        data: formData + '&action=' + action,
        success: function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                closeModal();
                loadData(currentPage);
            } else {
                Swal.fire('ผิดพลาด', res.message, 'error');
            }
        }
    });
}

function deleteUser(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบผู้ใช้งานนี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก',
        borderRadius: '1.5rem'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'user_action.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire('ลบแล้ว!', res.message, 'success');
                        loadData(currentPage);
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

function resetPassword(id, username) {
    Swal.fire({
        title: 'ยืนยันการรีเซ็ตรหัสผ่าน?',
        html: `คุณต้องการรีเซ็ตรหัสผ่านของ <b>${username}</b> เป็น <b>123456</b> ใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, รีเซ็ต!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'user_action.php',
                type: 'POST',
                data: { action: 'reset_password', id: id },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'รีเซ็ตเรียบร้อย!',
                            html: `รหัสผ่านของ <b>${username}</b> ถูกรีเซ็ตเป็น <b>123456</b> แล้ว`,
                            timer: 3000,
                            showConfirmButton: true
                        });
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}
</script>

</body>
</html>

<?php
require 'auth_check.php';
include 'config.php';

// Create table if not exists (for demonstration/initial setup)
$createTableSQL = "CREATE TABLE IF NOT EXISTS company (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    address TEXT,
    tax_id VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createTableSQL);
mysqli_query($conn, "ALTER TABLE company CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลบริษัท - RONGYANG HOME</title>
    <script src="assets/js/tailwindcss.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Prompt', sans-serif; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php include 'navbar.php'; ?>

<div class="container max-w-7xl mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                จัดการข้อมูลบริษัท
            </h1>
            <p class="text-gray-500 mt-1">เพิ่ม แก้ไข และลบข้อมูลบริษัทในระบบ</p>
        </div>
        <button onclick="openModal('add')" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-all duration-200 shadow-lg shadow-indigo-200 group">
            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            เพิ่มบริษัทใหม่
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
                    <input type="text" id="searchInput" placeholder="ค้นหาชื่อบริษัท, เลขผู้เสียภาษี..." 
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
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">ชื่อบริษัท</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">เลขผู้เสียภาษี</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">เบอร์โทรศัพท์</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider">อีเมล</th>
                        <th class="px-6 py-4 text-sm font-semibold text-gray-600 uppercase tracking-wider text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="companyTableBody" class="divide-y divide-gray-50">
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
<div id="companyModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-50 backdrop-blur-sm" onclick="closeModal()"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-8 pt-8 pb-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 id="modalTitle" class="text-2xl font-bold text-gray-800">เพิ่มบริษัทใหม่</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="companyForm" class="space-y-5">
                    <input type="hidden" id="company_id" name="id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">ชื่อบริษัท <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">เลขผู้เสียภาษี <span class="text-red-500">*</span></label>
                            <input type="text" name="tax_id" required
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">เบอร์โทรศัพท์</label>
                            <input type="text" name="phone"
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">อีเมล</label>
                        <input type="email" name="email"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">ที่อยู่ <span class="text-red-500">*</span></label>
                        <textarea name="address" rows="3" required
                                  class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none resize-none"></textarea>
                    </div>
                </form>
            </div>
            
            <div class="bg-gray-50 px-8 py-6 flex flex-row-reverse gap-3">
                <button type="button" onclick="saveCompany()" 
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
        url: 'company_action.php',
        type: 'GET',
        data: {
            action: 'list',
            search: search,
            perPage: perPage,
            page: currentPage
        },
        success: function(response) {
            const res = JSON.parse(response);
            $('#companyTableBody').html(res.html);
            $('#pagination').html(res.pagination);
        }
    });
}

function openModal(type, id = null) {
    $('#companyForm')[0].reset();
    $('#company_id').val('');
    
    if (type === 'add') {
        $('#modalTitle').text('เพิ่มบริษัทใหม่');
        $('#companyModal').removeClass('hidden');
    } else {
        $('#modalTitle').text('แก้ไขข้อมูลบริษัท');
        $.ajax({
            url: 'company_action.php',
            type: 'GET',
            data: { action: 'get', id: id },
            success: function(response) {
                const data = JSON.parse(response);
                $('#company_id').val(data.id);
                $('input[name="company_name"]').val(data.company_name);
                $('input[name="tax_id"]').val(data.tax_id);
                $('input[name="phone"]').val(data.phone);
                $('input[name="email"]').val(data.email);
                $('textarea[name="address"]').val(data.address);
                $('#companyModal').removeClass('hidden');
            }
        });
    }
}

function closeModal() {
    $('#companyModal').addClass('hidden');
}

function saveCompany() {
    const formData = $('#companyForm').serialize();
    const action = $('#company_id').val() ? 'update' : 'create';

    $.ajax({
        url: 'company_action.php',
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

function deleteCompany(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบข้อมูลบริษัทนี้ใช่หรือไม่?",
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
                url: 'company_action.php',
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
</script>

</body>
</html>

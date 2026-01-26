<?php
require '../auth_check.php';
require '../config.php';

$company_id = $_SESSION['company_id'];

// Get company info
$company_sql = "SELECT * FROM company WHERE id = ?";
$company_stmt = mysqli_prepare($conn, $company_sql);
mysqli_stmt_bind_param($company_stmt, "i", $company_id);
mysqli_stmt_execute($company_stmt);
$company_res = mysqli_stmt_get_result($company_stmt);
$company = mysqli_fetch_assoc($company_res);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการใบเสนอราคา - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">

<div class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white p-4 shadow-lg">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">📋 จัดการใบเสนอราคา</h1>
        <div class="flex gap-3">
            <a href="quotation_form.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">+ สร้างใหม่</a>
            <a href="index.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">← กลับ</a>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto p-6">
    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
        <input type="text" id="searchInput" placeholder="ค้นหาเลขที่เอกสาร หรือชื่อลูกค้า..." 
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
               onkeyup="loadQuotations()">
    </div>

    <!-- List -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">เลขที่</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">วันที่</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-600">ลูกค้า</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-600">ยอดรวม</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold text-gray-600">จัดการ</th>
                </tr>
            </thead>
            <tbody id="quotationList" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">กำลังโหลด...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    loadQuotations();
});

function loadQuotations() {
    const search = $('#searchInput').val();
    
    $.ajax({
        url: 'quotation_action.php',
        type: 'GET',
        data: { action: 'list', search: search },
        success: function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                renderList(res.data);
            }
        }
    });
}

function renderList(data) {
    let html = '';
    
    if (data.length === 0) {
        html = '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">ไม่พบข้อมูล</td></tr>';
    } else {
        data.forEach(item => {
            const date = new Date(item.doc_date).toLocaleDateString('th-TH');
            html += `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">${item.doc_number}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${date}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">${item.customer_name || '-'}</td>
                    <td class="px-6 py-4 text-sm text-right font-bold text-emerald-600">${parseFloat(item.grand_total).toLocaleString()} ฿</td>
                    <td class="px-6 py-4 text-center space-x-2">
                        <a href="quotation_form.php?id=${item.id}" class="inline-flex items-center px-3 py-1.5 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition-all text-sm">
                            ✏️ แก้ไข
                        </a>
                        <button onclick="deleteQuotation(${item.id})" class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-all text-sm">
                            🗑️ ลบ
                        </button>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#quotationList').html(html);
}

function deleteQuotation(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบใบเสนอราคานี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'quotation_action.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire('ลบแล้ว!', res.message, 'success');
                        loadQuotations();
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

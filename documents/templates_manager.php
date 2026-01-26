<?php
require '../auth_check.php';
require '../config.php';

$company_id = $_SESSION['company_id'];
$type = $_GET['type'] ?? 'payment'; // payment or notes
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเทมเพลต - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">

<div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-4 shadow-lg">
    <div class="max-w-4xl mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">📝 จัดการเทมเพลต<?= $type == 'payment' ? 'เงื่อนไขการชำระ' : 'หมายเหตุ' ?></h1>
        <button onclick="window.close()" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">ปิด</button>
    </div>
</div>

<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">รายการเทมเพลต</h2>
            <button onclick="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-all">
                + เพิ่มเทมเพลต
            </button>
        </div>

        <div id="templateList" class="space-y-3">
            <!-- Templates will be loaded here -->
        </div>
    </div>
</div>

<script>
const templateType = '<?= $type ?>';

$(document).ready(function() {
    loadTemplates();
});

function loadTemplates() {
    $.ajax({
        url: 'template_action.php',
        type: 'GET',
        data: { action: 'list', type: templateType },
        success: function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                renderTemplates(res.data);
            }
        }
    });
}

function renderTemplates(templates) {
    let html = '';
    if (templates.length === 0) {
        html = '<div class="text-center py-8 text-gray-400">ยังไม่มีเทมเพลต</div>';
    } else {
        templates.forEach(t => {
            html += `
                <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition-all">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-800 mb-2">${t.template_name || t.term_name}</h3>
                            <p class="text-sm text-gray-600 whitespace-pre-line">${t.template_content || t.term_content}</p>
                        </div>
                        <div class="flex gap-2 ml-4">
                            <button onclick="editTemplate(${t.id}, '${(t.template_name || t.term_name).replace(/'/g, "\\'")}', \`${(t.template_content || t.term_content).replace(/`/g, '\\`')}\`)" 
                                    class="text-indigo-600 hover:text-indigo-800 p-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="deleteTemplate(${t.id})" class="text-red-600 hover:text-red-800 p-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    $('#templateList').html(html);
}

function openAddModal() {
    const nameLabel = templateType === 'payment' ? 'ชื่อเงื่อนไข' : 'ชื่อเทมเพลต';
    const contentLabel = templateType === 'payment' ? 'รายละเอียดเงื่อนไข' : 'เนื้อหาหมายเหตุ';
    
    Swal.fire({
        title: 'เพิ่มเทมเพลตใหม่',
        html: `
            <div class="text-left space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">${nameLabel}</label>
                    <input id="templateName" class="swal2-input !m-0 !w-full" placeholder="ระบุชื่อ">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">${contentLabel}</label>
                    <textarea id="templateContent" class="swal2-textarea !m-0 !w-full !h-32" placeholder="ระบุรายละเอียด"></textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#4f46e5',
        preConfirm: () => {
            const name = $('#templateName').val();
            const content = $('#templateContent').val();
            if (!name || !content) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }
            return { name, content };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'template_action.php',
                type: 'POST',
                data: {
                    action: 'save',
                    type: templateType,
                    name: result.value.name,
                    content: result.value.content
                },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire('สำเร็จ!', res.message, 'success');
                        loadTemplates();
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

function editTemplate(id, name, content) {
    const nameLabel = templateType === 'payment' ? 'ชื่อเงื่อนไข' : 'ชื่อเทมเพลต';
    const contentLabel = templateType === 'payment' ? 'รายละเอียดเงื่อนไข' : 'เนื้อหาหมายเหตุ';
    
    Swal.fire({
        title: 'แก้ไขเทมเพลต',
        html: `
            <div class="text-left space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">${nameLabel}</label>
                    <input id="templateName" class="swal2-input !m-0 !w-full" value="${name}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">${contentLabel}</label>
                    <textarea id="templateContent" class="swal2-textarea !m-0 !w-full !h-32">${content}</textarea>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#4f46e5',
        preConfirm: () => {
            const name = $('#templateName').val();
            const content = $('#templateContent').val();
            if (!name || !content) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }
            return { name, content };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'template_action.php',
                type: 'POST',
                data: {
                    action: 'save',
                    type: templateType,
                    id: id,
                    name: result.value.name,
                    content: result.value.content
                },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire('สำเร็จ!', res.message, 'success');
                        loadTemplates();
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                }
            });
        }
    });
}

function deleteTemplate(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'คุณต้องการลบเทมเพลตนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'template_action.php',
                type: 'POST',
                data: { action: 'delete', type: templateType, id: id },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        Swal.fire('ลบแล้ว!', res.message, 'success');
                        loadTemplates();
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

<!-- Filter & Actions Bar -->
<div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="relative w-full md:w-80">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
            <i class="fa-solid fa-magnifying-glass text-sm"></i>
        </span>
        <input type="text" id="empSearchInput" onkeyup="loadEmployeesList()" placeholder="ค้นหาชื่อพนักงาน, ตำแหน่ง, รหัส..." 
               class="block w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
    </div>

    <button onclick="openEmployeeModal('add')" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10 gap-1.5 self-start md:self-auto">
        <i class="fa-solid fa-user-plus"></i>
        <span>เพิ่มพนักงานใหม่</span>
    </button>
</div>

<!-- Employee List Card -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">รหัสพนักงาน</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">ชื่อ-นามสกุล</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">แผนก / ตำแหน่ง</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">เงินเดือน</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">วันเริ่มงาน</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">สิทธิ์วันลาสูงสุด (ลากิจ/ป่วย/พักร้อน/อื่น)</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">การจัดการ</th>
                </tr>
            </thead>
            <tbody id="employeesTableBody" class="divide-y divide-slate-100">
                <!-- Loaded dynamically -->
            </tbody>
        </table>
    </div>
</div>

<!-- Employee Modal -->
<div id="employeeModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-slate-900/40 backdrop-blur-sm" onclick="closeEmployeeModal()"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-center justify-between mb-6">
                    <h3 id="employeeModalTitle" class="text-lg font-bold text-slate-800">เพิ่มพนักงานใหม่</h3>
                    <button onclick="closeEmployeeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                
                <form id="employeeForm" class="space-y-4">
                    <input type="hidden" id="emp_id" name="id">
                    
                    <!-- Photo & Basic Info Row -->
                    <div class="flex flex-col md:flex-row gap-6 border-b border-slate-100 pb-4">
                        <!-- Photo Upload Column -->
                        <div class="flex flex-col items-center justify-center space-y-2 md:border-r border-slate-100 pr-0 md:pr-6 self-center md:self-stretch">
                            <div class="relative group">
                                <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-slate-200 bg-slate-50 flex items-center justify-center shadow-inner" id="photo_preview_container">
                                    <i class="fa-solid fa-user-tie text-slate-300 text-4xl" id="photo_preview_icon"></i>
                                    <img src="" id="photo_preview" class="w-full h-full object-cover hidden">
                                </div>
                                <label for="photo_input" class="absolute bottom-0 right-0 w-8 h-8 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center cursor-pointer shadow-md transition-all">
                                    <i class="fa-solid fa-camera text-xs"></i>
                                </label>
                                <input type="file" name="photo" id="photo_input" accept="image/*" class="hidden" onchange="previewEmployeePhoto(this)">
                            </div>
                            <span class="text-[10px] text-slate-400">รูปภาพพนักงาน (สูงสุด 2MB)</span>
                        </div>
                        
                        <!-- Basic Info Column -->
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1.5">รหัสพนักงาน <span class="text-rose-500">*</span></label>
                                <input type="text" name="emp_code" id="emp_code_input" required placeholder="EMP-00001"
                                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1.5">ชื่อจริง <span class="text-rose-500">*</span></label>
                                <input type="text" name="first_name" id="first_name_input" required placeholder="สมชาย"
                                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-1.5">นามสกุล <span class="text-rose-500">*</span></label>
                                <input type="text" name="last_name" id="last_name_input" required placeholder="ใจดี"
                                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
                            </div>
                        </div>
                    </div>

                    <!-- Position & Salary Row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">แผนก <span class="text-rose-500">*</span></label>
                            <input type="text" name="department" id="department_input" required placeholder="เช่น ฝ่ายผลิต, บัญชี"
                                   class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">ตำแหน่ง <span class="text-rose-500">*</span></label>
                            <input type="text" name="position" id="position_input" required placeholder="เช่น ช่างเทคนิค, เสมียน"
                                   class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">เงินเดือนประจำ (บาท) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" name="salary" id="salary_input" required placeholder="15000"
                                   class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
                        </div>
                    </div>

                    <!-- Contact & Start Date -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">เบอร์โทรศัพท์</label>
                            <input type="text" name="phone" id="phone_input" placeholder="08XXXXXXXX"
                                   class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">วันที่เริ่มงาน <span class="text-rose-500">*</span></label>
                            <input type="date" name="start_date" id="start_date_input" required
                                   class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1.5">สถานะพนักงาน <span class="text-rose-500">*</span></label>
                            <select name="status" id="status_input" required
                                    class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700">
                                <option value="active">ปกติ (Active)</option>
                                <option value="inactive">พ้นสภาพ (Inactive)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Leave limits section -->
                    <div class="border-t border-slate-100 pt-4 mt-6">
                        <h4 class="text-xs font-bold text-slate-700 uppercase mb-3"><i class="fa-solid fa-umbrella-beach text-blue-500 mr-1.5"></i> กำหนดโควตาวันลาสูงสุดต่อปี (วัน)</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">ลากิจได้สูงสุด</label>
                                <input type="number" name="max_business_leave" id="max_business_leave_input" min="0" default="7"
                                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700 font-bold text-center">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">ลาป่วยได้สูงสุด</label>
                                <input type="number" name="max_sick_leave" id="max_sick_leave_input" min="0" default="30"
                                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700 font-bold text-center">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">ลาพักร้อนได้สูงสุด</label>
                                <input type="number" name="max_annual_leave" id="max_annual_leave_input" min="0" default="6"
                                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700 font-bold text-center">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">ลาอื่นๆ ได้สูงสุด</label>
                                <input type="number" name="max_other_leave" id="max_other_leave_input" min="0" default="15"
                                       class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white text-xs outline-none transition-all text-slate-700 font-bold text-center">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3">
                <button type="button" onclick="saveEmployee()" 
                        class="inline-flex justify-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-blue-500/10">
                    บันทึกพนักงาน
                </button>
                <button type="button" onclick="closeEmployeeModal()"
                        class="inline-flex justify-center px-5 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 text-xs transition-all">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function loadEmployeesList() {
        const search = $('#empSearchInput').val();
        $.ajax({
            url: 'payroll_action.php',
            type: 'GET',
            data: { action: 'list_employees', search: search },
            success: function(data) {
                let html = '';
                if (data.length > 0) {
                    data.forEach(function(emp) {
                        let statusBadge = emp.status === 'active' 
                            ? `<span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded-full border border-emerald-200"><i class="fa-solid fa-check mr-0.5"></i> ทำงานอยู่</span>`
                            : `<span class="px-2.5 py-1 bg-slate-100 text-slate-500 text-[10px] font-bold rounded-full border border-slate-200">พ้นสภาพ</span>`;
                        
                        let avatarHtml = '';
                        if (emp.photo) {
                            avatarHtml = `<img src="../${emp.photo}" class="w-8 h-8 rounded-full object-cover border border-slate-200 shadow-sm flex-shrink-0">`;
                        } else {
                            let initials = (emp.first_name ? emp.first_name.charAt(0) : '') + (emp.last_name ? emp.last_name.charAt(0) : '');
                            avatarHtml = `<div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 font-bold text-xs flex items-center justify-center border border-blue-100 shadow-sm flex-shrink-0">${initials}</div>`;
                        }

                        html += `
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-bold text-slate-800">${emp.emp_code}</td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-700">
                                <div class="flex items-center gap-3">
                                    ${avatarHtml}
                                    <span>${emp.first_name} ${emp.last_name}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                <div>${emp.position}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">${emp.department}</div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-700 font-bold">${formatCurrency(emp.salary)}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">${formatThaiDate(emp.start_date)}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-bold rounded-md" title="ลากิจ">ก: ${emp.max_business_leave}</span>
                                    <span class="px-1.5 py-0.5 bg-rose-50 text-rose-600 text-[10px] font-bold rounded-md" title="ลาป่วย">ป: ${emp.max_sick_leave}</span>
                                    <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-bold rounded-md" title="ลาพักร้อน">พ: ${emp.max_annual_leave}</span>
                                    <span class="px-1.5 py-0.5 bg-purple-50 text-purple-600 text-[10px] font-bold rounded-md" title="ลาอื่นๆ">อ: ${emp.max_other_leave}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">${statusBadge}</td>
                            <td class="px-6 py-4 text-xs text-right space-x-2">
                                <button onclick="openEmployeeModal('edit', ${emp.id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="แก้ไข">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button onclick="deleteEmployee(${emp.id})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="ลบ">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
                    });
                } else {
                    html = `
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center">
                                <i class="fa-solid fa-user-slash text-3xl mb-2 opacity-30"></i>
                                <div>ไม่พบข้อมูลพนักงานในระบบ</div>
                            </div>
                        </td>
                    </tr>`;
                }
                $('#employeesTableBody').html(html);
            }
        });
    }

    function previewEmployeePhoto(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#photo_preview').attr('src', e.target.result).removeClass('hidden');
                $('#photo_preview_icon').addClass('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Modal Operations
    function openEmployeeModal(mode, id = null) {
        $('#employeeForm')[0].reset();
        $('#emp_id').val('');
        
        // Reset photo preview
        $('#photo_preview').attr('src', '').addClass('hidden');
        $('#photo_preview_icon').removeClass('hidden');
        $('#photo_input').val('');
        
        // Set default values for leaves
        $('#max_business_leave_input').val(7);
        $('#max_sick_leave_input').val(30);
        $('#max_annual_leave_input').val(6);
        $('#max_other_leave_input').val(15);
        $('#status_input').val('active');
        
        if (mode === 'add') {
            $('#employeeModalTitle').text('เพิ่มพนักงานใหม่');
            $('#emp_code_input').prop('readonly', false);
            $('#employeeModal').removeClass('hidden');
        } else {
            $('#employeeModalTitle').text('แก้ไขประวัติพนักงาน');
            $('#emp_code_input').prop('readonly', true);
            
            // Get data by id
            $.ajax({
                url: 'payroll_action.php',
                type: 'GET',
                data: { action: 'get_employee', id: id },
                success: function(emp) {
                    if (emp.status === 'error') {
                        Swal.fire('ผิดพลาด', emp.message, 'error');
                        return;
                    }
                    $('#emp_id').val(emp.id);
                    $('#emp_code_input').val(emp.emp_code);
                    $('#first_name_input').val(emp.first_name);
                    $('#last_name_input').val(emp.last_name);
                    $('#department_input').val(emp.department);
                    $('#position_input').val(emp.position);
                    $('#salary_input').val(emp.salary);
                    $('#phone_input').val(emp.phone);
                    $('#start_date_input').val(emp.start_date);
                    $('#status_input').val(emp.status);
                    
                    $('#max_business_leave_input').val(emp.max_business_leave);
                    $('#max_sick_leave_input').val(emp.max_sick_leave);
                    $('#max_annual_leave_input').val(emp.max_annual_leave);
                    $('#max_other_leave_input').val(emp.max_other_leave);

                    // Load photo preview if exists
                    if (emp.photo) {
                        $('#photo_preview').attr('src', '../' + emp.photo).removeClass('hidden');
                        $('#photo_preview_icon').addClass('hidden');
                    }

                    $('#employeeModal').removeClass('hidden');
                }
            });
        }
    }

    function closeEmployeeModal() {
        $('#employeeModal').addClass('hidden');
    }

    function saveEmployee() {
        const form = $('#employeeForm')[0];
        
        // Basic HTML5 validation trigger
        if (!form.reportValidity()) {
            return;
        }

        const formData = new FormData(form);
        formData.append('action', 'save_employee');

        $.ajax({
            url: 'payroll_action.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    });
                    closeEmployeeModal();
                    loadEmployeesList();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                }
            }
        });
    }

    function deleteEmployee(id) {
        Swal.fire({
            title: 'ยืนยันการลบพนักงาน?',
            html: '<span class="text-sm text-slate-500">การลบพนักงานจะทำให้ประวัติการลงเวลาและคำนวณวันลาของพนักงานรายนี้ถูกลบออกจากระบบทั้งหมดอย่างถาวร</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ใช่, ยืนยันการลบ!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'payroll_action.php',
                    type: 'POST',
                    data: { action: 'delete_employee', id: id },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('ลบสำเร็จ!', res.message, 'success');
                            loadEmployeesList();
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

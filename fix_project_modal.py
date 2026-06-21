import sys
import re

filepath = 'e:/xampp/htdocs/rongyanghome/subcontractors/index.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add the UI to the SWAL HTML
old_html_end = """                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">หมายเหตุเพิ่มเติม</label>
                                        <input type="text" id="p-note" class="swal2-input !m-0 !w-full" placeholder="รายละเอียดอื่นๆ" value="">
                                    </div>
                                </div>
                            ,"""

new_html_end = """                                    <div>
                                        <label class="block text-sm font-semibold text-slate-500 mb-1">หมายเหตุเพิ่มเติม</label>
                                        <input type="text" id="p-note" class="swal2-input !m-0 !w-full" placeholder="รายละเอียดอื่นๆ" value="">
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
                            ,"""

content = content.replace(old_html_end, new_html_end)

# 2. Add didOpen inside Swal.fire
old_swal = """                            showCancelButton: true,
                            confirmButtonText: 'บันทึก',"""

new_swal = """                            didOpen: () => {
                                if (data && data.assigned_subcontractors && data.assigned_subcontractors.length > 0) {
                                    data.assigned_subcontractors.forEach(sub => {
                                        addModalAssignedSubcontractorColumn(sub.job_type, sub.subcontractor_id, sub.contract_amount);
                                    });
                                } else {
                                    addModalAssignedSubcontractorColumn();
                                }
                            },
                            showCancelButton: true,
                            confirmButtonText: 'บันทึก',"""

content = content.replace(old_swal, new_swal)

# 3. Add to preConfirm and AJAX
old_preconfirm_end = """                                if (!nameVal) {
                                    Swal.showValidationMessage('กรุณาระบุชื่อโครงการ');
                                    return false;
                                }

                                return {"""

new_preconfirm_end = """                                if (!nameVal) {
                                    Swal.showValidationMessage('กรุณาระบุชื่อโครงการ');
                                    return false;
                                }
                                
                                const assigned_subs = [];
                                .modal-assigned-row.each(function() {
                                    const jt = .find('.m-job-type').val();
                                    const sid = .find('.m-sub-id').val();
                                    const camt = .find('.m-contract-amt').val() || 0;
                                    if (sid) {
                                        assigned_subs.push({
                                            job_type: jt,
                                            subcontractor_id: sid,
                                            contract_amount: camt
                                        });
                                    }
                                });

                                return {"""

content = content.replace(old_preconfirm_end, new_preconfirm_end)

old_ajax_data = """                                    data: {
                                        action: 'project_save',
                                        id: id,
                                        ...result.value
                                    },"""

new_ajax_data = """                                    data: {
                                        action: 'project_save',
                                        id: id,
                                        assigned_subs_json: JSON.stringify(result.value.assigned_subs),
                                        ...result.value
                                    },"""

# modify return object in preconfirm
old_return = """                                    main_subcontractor_id: contractorVal,
                                    note: noteVal
                                };"""

new_return = """                                    main_subcontractor_id: contractorVal,
                                    note: noteVal,
                                    assigned_subs: assigned_subs
                                };"""

content = content.replace(old_return, new_return)
content = content.replace(old_ajax_data, new_ajax_data)

# 4. Add the function addModalAssignedSubcontractorColumn definition before openProjectModal
old_func_def = """                    function openProjectModal(data = null) {"""

new_func_def = """                    function addModalAssignedSubcontractorColumn(job_type = '', sub_id = '', contract_amount = '') {
                        const index = new Date().getTime() + Math.floor(Math.random() * 1000);
                        const jobTypes = ['ทีมโครงสร้าง', 'ทีมไม้', 'ทีมสี/ตกแต่ง', 'ทีมไฟฟ้า', 'ทีมปูน/ก่อฉาบ', 'ทีมกระเบื้อง', 'ทีมหลังคา', 'ทีมงานระบบ', 'ทีมอลูมิเนียม', 'ทีมสแตนเลส'];
                        
                        let jobTypeOptions = '<option value="">-- เลือกประเภทงาน --</option>';
                        jobTypes.forEach(jt => { 
                            const selected = (jt === job_type) ? 'selected' : '';
                            jobTypeOptions += <option value="" ></option>; 
                        });

                        let subOptions = '<option value="">-- เลือกผู้รับเหมา --</option>';
                        <?php foreach ( as ): ?>
                            const isSelected_<?=['id']?> = (sub_id == <?=['id']?>) ? 'selected' : '';
                            subOptions += <option value="<?=['id']?>" ><?=['name']?> (<?=['team_type']?>)</option>;
                        <?php endforeach; ?>

                        const rowHtml = 
                            <div class="modal-assigned-row flex items-end gap-2 bg-slate-50 p-2 border border-slate-200 rounded-lg relative">
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">ประเภทงาน</label>
                                    <select class="m-job-type w-full text-xs border border-slate-300 rounded p-1.5 outline-none focus:border-blue-500">
                                        
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">ชื่อผู้รับเหมา</label>
                                    <select class="m-sub-id w-full text-xs border border-slate-300 rounded p-1.5 outline-none focus:border-blue-500">
                                        
                                    </select>
                                </div>
                                <div class="w-32">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-0.5">มูลค่าสัญญา</label>
                                    <input type="number" class="m-contract-amt w-full text-xs border border-slate-300 rounded p-1.5 outline-none focus:border-blue-500 text-right font-bold" value="" placeholder="0.00">
                                </div>
                                <button type="button" onclick=".closest('.modal-assigned-row').remove()" class="w-8 h-8 flex items-center justify-center text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded mb-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        ;
                        #modal-assigned-subs.append(rowHtml);
                    }

                    function openProjectModal(data = null) {"""

content = content.replace(old_func_def, new_func_def)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)

print('Updated index.php successfully')

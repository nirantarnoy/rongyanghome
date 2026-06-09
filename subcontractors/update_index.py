import sys

path = r'e:\xampp\htdocs\rongyanghome\subcontractors\index.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update renderProjectDetails function signature and add global vars
old_render_sig = "const fins = res.financials;"
new_render_sig = """const fins = res.financials;
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

                            const fileLink = sub.attachment ? `<div class="mt-1 text-[10px]"><a href="../${sub.attachment}" target="_blank" class="text-blue-500 hover:underline">ดูไฟล์แนบปัจจุบัน</a></div>` : '';
                            const existingAttachment = sub.attachment ? `<input type="hidden" name="existing_attachment_${index}" value="${sub.attachment}">` : '';

                            assignedSubsHTML += `
                                <div class="assigned-sub-col w-56 flex flex-col gap-4 border border-slate-200 rounded-lg p-3 bg-slate-50 relative shrink-0">
                                    <button type="button" onclick="$(this).closest('.assigned-sub-col').remove();" class="absolute top-1 right-1 text-slate-400 hover:text-rose-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-rose-500 text-center mb-1">ประเภทงาน เลือก</label>
                                        <select name="job_type_${index}" class="w-full text-xs border border-slate-300 rounded p-1.5 focus:border-emerald-500 outline-none">
                                            ${jobTypeOptions}
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-rose-500 text-center mb-1">ชื่อผู้รับเหมาที่รับผิดชอบ</label>
                                        <select name="subcontractor_id_${index}" class="w-full text-xs border border-slate-300 rounded p-1.5 focus:border-emerald-500 outline-none">
                                            ${subOptions}
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 text-center mb-1">กรอกค่าแรงตามสัญญา</label>
                                        <input type="number" name="contract_amount_${index}" class="w-full text-center text-xs border border-slate-300 rounded p-1.5 font-bold text-slate-700" value="${sub.contract_amount || ''}" oninput="calcRemaining(this)" placeholder="0">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 text-center mb-1">ชำระแล้ว</label>
                                        <input type="number" class="w-full text-center text-xs border border-slate-200 rounded p-1.5 font-bold text-slate-500 bg-slate-100 sub-paid-amount" value="${sub.paid_amount || 0}" readonly>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-[11px] font-bold text-rose-500 text-center mb-1">ยอด ค้างชำระค่าแรง</label>
                                        <input type="number" class="w-full text-center text-xs border border-slate-200 rounded p-1.5 font-bold text-rose-500 bg-slate-100 sub-remaining-amount" value="${sub.remaining_amount || 0}" readonly>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 text-center mb-1">แนบเอกสารสัญญา</label>
                                        <input type="file" name="attachment_${index}" class="w-full text-[10px] border border-slate-300 rounded p-1 bg-white" accept=".pdf,image/*">
                                        ${fileLink}
                                        ${existingAttachment}
                                    </div>
                                </div>
                            `;
                        });"""
content = content.replace(old_render_sig, new_render_sig)

# 2. Add HTML section to tab-info
old_html_info = """                                            <div class="col-span-2">
                                                <span class="text-xs text-slate-400 font-medium">หมายเหตุเพิ่มเติม</span>
                                                <p class="font-semibold text-slate-700 mt-0.5 leading-relaxed">${p.note || '-'}</p>
                                            </div>
                                        </div>
                                    </div>"""

new_html_info = """                                            <div class="col-span-2">
                                                <span class="text-xs text-slate-400 font-medium">หมายเหตุเพิ่มเติม</span>
                                                <p class="font-semibold text-slate-700 mt-0.5 leading-relaxed">${p.note || '-'}</p>
                                            </div>
                                        </div>

                                        <!-- Assigned Subcontractors Section -->
                                        <div class="border-t border-slate-100 pt-6 mt-6">
                                            <div class="flex justify-between items-center mb-4">
                                                <h3 class="font-bold text-rose-500 text-base">รายละเอียดผู้รับเหมาทำงานในโปรเจค</h3>
                                                <button onclick="saveAssignedSubcontractors()" class="text-xs font-bold text-emerald-600 hover:underline bg-emerald-50 px-3 py-1.5 rounded">บันทึกข้อมูลผู้รับเหมาในโปรเจค</button>
                                            </div>
                                            <div class="overflow-x-auto pb-4">
                                                <form id="assigned-subs-form" class="flex gap-4 min-w-max items-start">
                                                    ${assignedSubsHTML}
                                                </form>
                                                <button type="button" onclick="addAssignedSubcontractorColumn()" class="mt-4 text-xs font-bold text-indigo-500 hover:text-indigo-700 flex items-center gap-1">+ เพิ่มประเภทงาน</button>
                                            </div>
                                            <p class="text-[11px] font-bold text-rose-500 mt-2">ช่องนี้ให้หักลบการจ่ายแต่ละงวดจากยอดรวมค่าแรงช่องแรกนี้มาใส่ในช่อง 2 ช่องนี้เลยค่ะ</p>
                                        </div>

                                    </div>"""
content = content.replace(old_html_info, new_html_info)

# 3. Add JS functions for Assigned Subs
old_js_funcs = """                    function switchTab(tabId) {"""
new_js_funcs = """                    function calcRemaining(inputElem) {
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
                                    <label class="block text-xs font-bold text-rose-500 text-center mb-1">ประเภทงาน เลือก</label>
                                    <select name="job_type_${index}" class="w-full text-xs border border-slate-300 rounded p-1.5 focus:border-emerald-500 outline-none">
                                        ${jobTypeOptions}
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-rose-500 text-center mb-1">ชื่อผู้รับเหมาที่รับผิดชอบ</label>
                                    <select name="subcontractor_id_${index}" class="w-full text-xs border border-slate-300 rounded p-1.5 focus:border-emerald-500 outline-none">
                                        ${subOptions}
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 text-center mb-1">กรอกค่าแรงตามสัญญา</label>
                                    <input type="number" name="contract_amount_${index}" class="w-full text-center text-xs border border-slate-300 rounded p-1.5 font-bold text-slate-700" value="" oninput="calcRemaining(this)" placeholder="0">
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 text-center mb-1">ชำระแล้ว</label>
                                    <input type="number" class="w-full text-center text-xs border border-slate-200 rounded p-1.5 font-bold text-slate-500 bg-slate-100 sub-paid-amount" value="0" readonly>
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-bold text-rose-500 text-center mb-1">ยอด ค้างชำระค่าแรง</label>
                                    <input type="number" class="w-full text-center text-xs border border-slate-200 rounded p-1.5 font-bold text-rose-500 bg-slate-100 sub-remaining-amount" value="0" readonly>
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 text-center mb-1">แนบเอกสารสัญญา</label>
                                    <input type="file" name="attachment_${index}" class="w-full text-[10px] border border-slate-300 rounded p-1 bg-white" accept=".pdf,image/*">
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

                    function switchTab(tabId) {"""

content = content.replace(old_js_funcs, new_js_funcs)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print("SUCCESS!")

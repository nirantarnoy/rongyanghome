import sys
import re

path = r'e:\xampp\htdocs\rongyanghome\subcontractors\index.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

settings_html = """
                <!-- SETTINGS SECTION -->
                <div class="custom-card p-6 mb-6 border border-blue-200 bg-blue-50/30">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 items-start">
                        <!-- Column 1 -->
                        <div class="settings-col" data-category="project_status">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-base">สถานะโปรเจคทั้งหมด</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-base hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3"></div>
                            <button onclick="saveSettings('project_status', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-base">บันทึก</button>
                        </div>

                        <!-- Column 2 -->
                        <div class="settings-col" data-category="job_type">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-base">ประเภทงาน</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-base hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3"></div>
                            <button onclick="saveSettings('job_type', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-base">บันทึก</button>
                        </div>

                        <!-- Column 3 -->
                        <div class="settings-col" data-category="team_type">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-base">ประเภททีมงาน</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-base hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3"></div>
                            <button onclick="saveSettings('team_type', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-base">บันทึก</button>
                        </div>

                        <!-- Column 4 -->
                        <div class="settings-col" data-category="team_status">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-base">สถานะทีมงาน</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-base hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3"></div>
                            <button onclick="saveSettings('team_status', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-base">บันทึก</button>
                        </div>
                    </div>
                </div>
                
                <script>
                    $(document).ready(function() {
                        if ($('.settings-col').length > 0) {
                            loadSettings();
                        }
                    });

                    function loadSettings() {
                        $.ajax({
                            url: 'action.php',
                            type: 'GET',
                            data: { action: 'settings_list' },
                            success: function(res) {
                                if(res.status === 'success') {
                                    renderSettings(res.data);
                                }
                            }
                        });
                    }

                    function renderSettings(data) {
                        $('.settings-col').each(function() {
                            const cat = $(this).data('category');
                            const list = $(this).find('.settings-list');
                            list.empty();
                            
                            if(data[cat] && data[cat].length > 0) {
                                data[cat].forEach(item => {
                                    list.append(createSettingRow(item.setting_value));
                                });
                            } else {
                                list.append(createSettingRow(''));
                            }
                        });
                    }

                    function createSettingRow(value) {
                        return `
                            <div class="flex items-center gap-2">
                                <input type="text" class="setting-val w-full border border-blue-600 rounded-full px-4 py-1.5 text-base text-center font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-200" value="${value}" placeholder="...">
                                <button onclick="$(this).parent().remove()" class="text-slate-400 hover:text-red-500 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        `;
                    }

                    function addSettingRow(btn) {
                        $(btn).closest('.settings-col').find('.settings-list').append(createSettingRow(''));
                    }

                    function saveSettings(category, btn) {
                        const vals = [];
                        $(btn).closest('.settings-col').find('.setting-val').each(function() {
                            const v = $(this).val().trim();
                            if(v) vals.push(v);
                        });

                        const oldText = $(btn).text();
                        $(btn).text('กำลังบันทึก...').prop('disabled', true);

                        $.ajax({
                            url: 'action.php',
                            type: 'POST',
                            data: { action: 'settings_save', category: category, values: JSON.stringify(vals) },
                            success: function(res) {
                                $(btn).text('บันทึกสำเร็จ!').removeClass('bg-blue-600').addClass('bg-emerald-500');
                                setTimeout(() => {
                                    $(btn).text(oldText).removeClass('bg-emerald-500').addClass('bg-blue-600').prop('disabled', false);
                                }, 2000);
                            }
                        });
                    }
                </script>
"""

marker = "<!-- Projects List Grid -->"
if marker in content and "data-category=\"job_type\"" not in content:
    content = content.replace(marker, settings_html + "\n                " + marker)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("SUCCESS: Injected settings into projects view")
else:
    print("Marker not found or already injected.")

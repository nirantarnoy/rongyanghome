import sys

action_path = r'e:\xampp\htdocs\rongyanghome\subcontractors\action.php'
with open(action_path, 'r', encoding='utf-8') as f:
    action_content = f.read()

settings_action = """
if ($action === 'settings_list') {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS subcontractor_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT DEFAULT 1,
        category VARCHAR(50) NOT NULL,
        setting_value VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0
    )");
    
    $check = mysqli_query($conn, "SELECT COUNT(*) as c FROM subcontractor_settings WHERE company_id = $company_id");
    if(mysqli_fetch_assoc($check)['c'] == 0) {
        $defaults = [
            'project_status' => ['กำลังดำเนินการ', 'รอเริ่มงาน', 'เสร็จสิ้น', 'ยกเลิก'],
            'job_type' => ['ทีมโครงสร้าง', 'ทีมไม้', 'ทีมสี/ตกแต่ง', 'ทีมไฟฟ้า', 'ทีมปูน/ก่อฉาบ', 'ทีมกระเบื้อง', 'ทีมหลังคา', 'ทีมงานระบบ', 'ทีมอลูมิเนียม', 'ทีมสแตนเลส'],
            'team_type' => ['ทีมโครงสร้าง', 'ทีมงานระบบ', 'ทีมตกแต่ง', 'อื่นๆ'],
            'team_status' => ['กำลังทำงาน', 'ว่าง', 'พักงาน', 'แบล็คลิสต์']
        ];
        foreach($defaults as $cat => $vals) {
            foreach($vals as $i => $v) {
                mysqli_query($conn, "INSERT INTO subcontractor_settings (company_id, category, setting_value, sort_order) VALUES ($company_id, '$cat', '$v', $i)");
            }
        }
    }
    
    $res = mysqli_query($conn, "SELECT * FROM subcontractor_settings WHERE company_id = $company_id ORDER BY category, sort_order ASC, id ASC");
    $data = [];
    while($row = mysqli_fetch_assoc($res)) {
        $data[$row['category']][] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

if ($action === 'settings_save') {
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $values = json_decode($_POST['values'], true);
    
    mysqli_query($conn, "DELETE FROM subcontractor_settings WHERE company_id = $company_id AND category = '$category'");
    
    if(is_array($values)) {
        foreach($values as $i => $v) {
            $val = mysqli_real_escape_string($conn, $v);
            if(!empty($val)) {
                mysqli_query($conn, "INSERT INTO subcontractor_settings (company_id, category, setting_value, sort_order) VALUES ($company_id, '$category', '$val', $i)");
            }
        }
    }
    echo json_encode(['status' => 'success']);
    exit;
}
"""

if "settings_list" not in action_content:
    action_content += "\n" + settings_action
    with open(action_path, 'w', encoding='utf-8') as f:
        f.write(action_content)


index_path = r'e:\xampp\htdocs\rongyanghome\subcontractors\index.php'
with open(index_path, 'r', encoding='utf-8') as f:
    index_content = f.read()

# 1. Update Menu
menu_old = """                <a href="index.php?view=profit_summary" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all <?= $view === 'profit_summary' ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                    <span>สรุปกำไรโปรเจค</span>
                </a>
            </nav>"""

menu_new = """                <a href="index.php?view=profit_summary" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all <?= $view === 'profit_summary' ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                    <span>สรุปกำไรโปรเจค</span>
                </a>

                <a href="index.php?view=settings" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all <?= $view === 'settings' ? 'active-menu' : 'hover:text-white hover:bg-slate-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>การตั้งค่าสถานะงาน</span>
                </a>
            </nav>"""

if "การตั้งค่าสถานะงาน" not in index_content:
    index_content = index_content.replace(menu_old, menu_new)

# 2. Update Breadcrumbs
bc_old = "case 'profit_summary': echo 'สรุปกำไรโปรเจค'; break;"
bc_new = "case 'profit_summary': echo 'สรุปกำไรโปรเจค'; break;\n                            case 'settings': echo 'การตั้งค่าสถานะงาน'; break;"
index_content = index_content.replace(bc_old, bc_new)

head_old = "case 'profit_summary': echo 'สรุปผลกำไรโครงการ'; break;"
head_new = "case 'profit_summary': echo 'สรุปผลกำไรโครงการ'; break;\n                        case 'settings': echo 'การตั้งค่าสถานะและประเภทงาน'; break;"
index_content = index_content.replace(head_old, head_new)

# 3. Add Settings View HTML and JS
settings_html = """
            <?php elseif ($view === 'settings'): ?>
                <div class="custom-card p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 items-start">
                        
                        <!-- Column 1 -->
                        <div class="settings-col" data-category="project_status">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-sm">สถานะโปรเจคทั้งหมด</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-sm hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3">
                                <!-- dynamic -->
                            </div>
                            <button onclick="saveSettings('project_status', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">บันทึกกลุ่มนี้</button>
                        </div>

                        <!-- Column 2 -->
                        <div class="settings-col" data-category="job_type">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-sm">ประเภทงาน</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-sm hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3">
                                <!-- dynamic -->
                            </div>
                            <button onclick="saveSettings('job_type', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">บันทึกกลุ่มนี้</button>
                        </div>

                        <!-- Column 3 -->
                        <div class="settings-col" data-category="team_type">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-sm">ประเภททีมงาน</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-sm hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3">
                                <!-- dynamic -->
                            </div>
                            <button onclick="saveSettings('team_type', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">บันทึกกลุ่มนี้</button>
                        </div>

                        <!-- Column 4 -->
                        <div class="settings-col" data-category="team_status">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-600 border border-blue-600 rounded-full px-4 py-1.5 text-sm">สถานะทีมงาน</h3>
                                <button onclick="addSettingRow(this)" class="font-bold text-blue-600 border border-blue-600 rounded-lg px-2 py-1 text-sm hover:bg-blue-50">เพิ่ม+</button>
                            </div>
                            <div class="settings-list space-y-3">
                                <!-- dynamic -->
                            </div>
                            <button onclick="saveSettings('team_status', this)" class="mt-4 w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">บันทึกกลุ่มนี้</button>
                        </div>

                    </div>
                </div>
                
                <script>
                    $(document).ready(function() {
                        loadSettings();
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
                                <input type="text" class="setting-val w-full border border-blue-600 rounded-full px-4 py-1.5 text-sm text-center font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-200" value="${value}" placeholder="ระบุข้อมูล...">
                                <button onclick="$(this).parent().remove()" class="text-slate-400 hover:text-red-500">
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

# Insert before <?php endif; ?> at the bottom
if "view === 'settings'" not in index_content:
    index_content = index_content.replace("<?php endif; ?>\n\n        </div>", settings_html + "\n            <?php endif; ?>\n\n        </div>")

with open(index_path, 'w', encoding='utf-8') as f:
    f.write(index_content)
print("SUCCESS!")

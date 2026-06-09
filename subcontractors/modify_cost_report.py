import sys
import re

# --- 1. Modify action.php ---
path_action = r'e:\xampp\htdocs\rongyanghome\subcontractors\action.php'
with open(path_action, 'r', encoding='utf-8') as f:
    content = f.read()

old_cost_report = """if ($action === 'cost_report') {
    $sql = "SELECT p.*, s.name as contractor_name
            FROM projects_list p
            LEFT JOIN subcontractors s ON p.main_subcontractor_id = s.id
            WHERE p.module_type = 1 AND p.company_id = $company_id
            ORDER BY p.id DESC";
    $result = mysqli_query($conn, $sql);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $pid = $row['id'];
        
        // Labor cost (milestones total)
        $instSQL = "SELECT SUM(amount) as labor_total, SUM(paid_amount) as labor_paid FROM project_installments WHERE project_id = $pid";
        $instRes = mysqli_query($conn, $instSQL);
        $instData = mysqli_fetch_assoc($instRes);
        $labor_total = (float)($instData['labor_total'] ?? 0);
        $labor_paid = (float)($instData['labor_paid'] ?? 0);
        
        // Additional expenses
        $expSQL = "SELECT SUM(amount) as exp_total FROM project_additional_expenses WHERE project_id = $pid AND status = 'อนุมัติแล้ว'";
        $expRes = mysqli_query($conn, $expSQL);
        $exp_total = (float)(mysqli_fetch_assoc($expRes)['exp_total'] ?? 0);
        
        $total_cost = $labor_total + $exp_total;
        $paid_cost = $labor_paid + $exp_total; // assuming expenses are already paid
        $contract_val = (float)$row['contract_value'];
        $profit = $contract_val - $total_cost;
        $profit_percent = $contract_val > 0 ? round(($profit / $contract_val) * 100, 2) : 0;
        
        $data[] = [
            'project_code' => $row['project_code'] ?? 'PJ-'.$row['id'],
            'project_name' => $row['project_name'],
            'contractor_name' => $row['contractor_name'] ?? '-',
            'contract_value' => $contract_val,
            'labor_cost' => $labor_total,
            'additional_expenses' => $exp_total,
            'total_cost' => $total_cost,
            'paid_cost' => $paid_cost,
            'remaining_cost' => max(0, $total_cost - $paid_cost),
            'profit' => $profit,
            'profit_percent' => $profit_percent,
            'status' => $row['status']
        ];
    }
    
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}"""

new_cost_report = """if ($action === 'cost_report') {
    $sql = "SELECT p.*, s.name as contractor_name
            FROM projects_list p
            LEFT JOIN subcontractors s ON p.main_subcontractor_id = s.id
            WHERE p.module_type = 1 AND p.company_id = $company_id
            ORDER BY p.id DESC";
    $result = mysqli_query($conn, $sql);
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $pid = $row['id'];
        
        // Get assigned subcontractors
        $subs_sql = "SELECT s.name FROM project_assigned_subcontractors pas JOIN subcontractors s ON pas.subcontractor_id = s.id WHERE pas.project_id = $pid";
        $subs_res = mysqli_query($conn, $subs_sql);
        $sub_names_arr = [];
        while ($sub_row = mysqli_fetch_assoc($subs_res)) {
            $sub_names_arr[] = $sub_row['name'];
        }
        $subcontractor_names = !empty($sub_names_arr) ? implode(',', $sub_names_arr) : '-';
        
        // Labor cost from assigned subs
        $contract_sql = "SELECT SUM(contract_amount) as total_contract, SUM(paid_amount) as labor_paid FROM project_assigned_subcontractors WHERE project_id = $pid";
        $contract_res = mysqli_query($conn, $contract_sql);
        $contract_data = mysqli_fetch_assoc($contract_res);
        $labor_total = (float)($contract_data['total_contract'] ?? 0);
        $labor_paid = (float)($contract_data['labor_paid'] ?? 0);
        
        // Additional expenses
        $expSQL = "SELECT SUM(amount) as exp_total FROM project_additional_expenses WHERE project_id = $pid AND status = 'อนุมัติแล้ว'";
        $expRes = mysqli_query($conn, $expSQL);
        $exp_total = (float)(mysqli_fetch_assoc($expRes)['exp_total'] ?? 0);
        
        $total_cost = $labor_total + $exp_total;
        $paid_cost = $labor_paid + $exp_total; // assuming expenses are already paid
        $contract_val = (float)$row['contract_value'];
        $profit = $contract_val - $total_cost;
        $profit_percent = $contract_val > 0 ? round(($profit / $contract_val) * 100, 2) : 0;
        
        $data[] = [
            'project_code' => $row['project_code'] ?? 'PJ-'.$row['id'],
            'project_name' => $row['project_name'],
            'subcontractor_names' => $subcontractor_names,
            'contract_value' => $contract_val,
            'labor_cost' => $labor_total,
            'additional_expenses' => $exp_total,
            'total_cost' => $total_cost,
            'paid_cost' => $paid_cost,
            'remaining_cost' => max(0, $total_cost - $paid_cost),
            'profit' => $profit,
            'profit_percent' => $profit_percent,
            'status' => $row['status']
        ];
    }
    
    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}"""

content = content.replace(old_cost_report, new_cost_report)

with open(path_action, 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated action.php")


# --- 2. Modify index.php ---
path2 = r'e:\xampp\htdocs\rongyanghome\subcontractors\index.php'
with open(path2, 'r', encoding='utf-8') as f:
    idx_content = f.read()

# Replace Header
idx_content = re.sub(
    r'<h3 class="font-bold text-slate-800 text-[A-Za-z0-9\-]+ flex items-center gap-2">\s*<span>📋</span> รายงานสรุปต้นทุนและการจ่ายงานโครงการ\s*</h3>', 
    '<h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">\n                            <span>📋</span> รายงานสรุปต้นทุนค่าแรงรับเหมาโครงการ\n                        </h3>', 
    idx_content
)

# Replace table columns
old_thead = """<th class="py-3 px-4 font-bold w-12 text-center hide-checkbox-on-print">
                                        <input type="checkbox" id="cost-report-select-all" class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer" checked onchange="toggleAllCostReports(this)">
                                    </th>
                                    <th class="py-3 px-4 font-bold">รหัส</th>
                                    <th class="py-3 px-4 font-bold">ชื่อโครงการ</th>
                                    <th class="py-3 px-4 font-bold">ผู้รับเหมาหลัก</th>
                                    <th class="py-3 px-4 font-bold text-right">มูลค่าโครงการ</th>
                                    <th class="py-3 px-4 font-bold text-right">ค่าแรงงวดงาน</th>
                                    <th class="py-3 px-4 font-bold text-right">ค่าใช้จ่ายเพิ่มเติม</th>
                                    <th class="py-3 px-4 font-bold text-right">รวมต้นทุน</th>
                                    <th class="py-3 px-4 font-bold text-right">กำไรขั้นต้น</th>
                                    <th class="py-3 px-4 font-bold text-center">กำไร %</th>
                                    <th class="py-3 px-4 font-bold text-center">สถานะ</th>"""

new_thead = """<th class="py-3 px-4 font-bold w-12 text-center hide-checkbox-on-print">
                                        <input type="checkbox" id="cost-report-select-all" class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer" checked onchange="toggleAllCostReports(this)">
                                    </th>
                                    <th class="py-3 px-4 font-bold">รหัส</th>
                                    <th class="py-3 px-4 font-bold">ชื่อโครงการ</th>
                                    <th class="py-3 px-4 font-bold text-center text-rose-500">รายชื่อผู้รับเหมา</th>
                                    <th class="py-3 px-4 font-bold text-right text-rose-500">มูลค่างานรวมเหมา</th>
                                    <th class="py-3 px-4 font-bold text-right text-slate-500">ค่าใช้จ่ายเพิ่มเติม</th>
                                    <th class="py-3 px-4 font-bold text-right text-rose-500">รวมต้นทุน<br><span class="text-xs">ค่าแรงเหมาโปรเจค</span></th>
                                    <th class="py-3 px-4 font-bold text-center">สถานะ</th>"""

idx_content = idx_content.replace(old_thead, new_thead)

# Replace table rendering JS
# Finding the template row in index.php
old_js_row_pattern = r'<td class="py-4 px-4 font-bold text-slate-500">\$\{r\.project_code\}</td>[\s\S]*?<td class="py-4 px-4 text-center">\s*<span class="px-2\.5 py-1 rounded-full text-[A-Za-z0-9\-]+ font-bold bg-slate-100 text-slate-600">\$\{r\.status\}</span>\s*</td>'

new_js_row = """<td class="py-4 px-4 font-bold text-slate-500">${r.project_code}</td>
                                                    <td class="py-4 px-4 font-bold text-slate-800">${r.project_name}</td>
                                                    <td class="py-4 px-4 text-center font-bold text-rose-500">${r.subcontractor_names}</td>
                                                    <td class="py-4 px-4 text-right font-bold text-slate-700">${r.labor_cost.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                                    <td class="py-4 px-4 text-right text-slate-600">${r.additional_expenses.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                                    <td class="py-4 px-4 text-right font-bold text-slate-800">${r.total_cost.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                                    <td class="py-4 px-4 text-center">
                                                        <span class="px-2.5 py-1 rounded-full text-sm font-bold bg-slate-100 text-slate-600">${r.status}</span>
                                                    </td>"""
                                                    
idx_content = re.sub(old_js_row_pattern, new_js_row, idx_content)

# The colspan for 'กำลังดึงข้อมูลรายงาน...' is 11, we now have 8 columns
idx_content = idx_content.replace('colspan="11"', 'colspan="8"')

with open(path2, 'w', encoding='utf-8') as f:
    f.write(idx_content)
print("Updated index.php")

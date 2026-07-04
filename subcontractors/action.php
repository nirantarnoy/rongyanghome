<?php
include 'db.php';

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/log_helper.php';
ob_start();
register_shutdown_function(function() {
    global $conn;
    $output = ob_get_clean();
    $json = json_decode($output, true);
    $action = $_REQUEST['action'] ?? '';
    if ($action && strpos($action, 'list') === false && strpos($action, 'get_') !== 0 && strpos($action, 'overview') === false) {
        $action_type = 'update';
        $action_lower = strtolower($action);
        if (strpos($action_lower, 'delete') !== false || strpos($action_lower, 'del') !== false || strpos($action_lower, 'remove') !== false) {
            $action_type = 'delete';
        } elseif (strpos($action_lower, 'add') !== false || strpos($action_lower, 'create') !== false || strpos($action_lower, 'new') !== false) {
            $action_type = 'create';
        }
        
        if ($json && isset($json['status']) && $json['status'] === 'success') {
            $msg = isset($json['message']) ? $json['message'] : "ดำเนินการ $action สำเร็จ";
            logActivity($conn, 'project', $msg, $action_type);
        } elseif (strpos($output, '"status":"success"') !== false || strpos($output, '"status": "success"') !== false) {
            logActivity($conn, 'project', "ดำเนินการ $action สำเร็จ", $action_type);
        }
    }
    echo $output;
});

$action = $_REQUEST['action'] ?? '';
$company_id = $_SESSION['company_id'] ?? 1; // Default to 1 if not set

if ($action === 'subcontractor_list') {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
    $team_type = isset($_GET['team_type']) ? mysqli_real_escape_string($conn, $_GET['team_type']) : '';

    $where = "WHERE company_id = $company_id";
    if ($search !== '') {
        $where .= " AND (name LIKE '%$search%' OR phone LIKE '%$search%')";
    }
    if ($status !== '') {
        $where .= " AND status = '$status'";
    }
    if ($team_type !== '') {
        $where .= " AND team_type = '$team_type'";
    }

    $sql = "SELECT s.*, 
            (SELECT COUNT(DISTINCT project_id) FROM subcontractor_payments WHERE subcontractor_id = s.id) as projects_count,
            (SELECT SUM(net_amount) FROM subcontractor_payments WHERE subcontractor_id = s.id) as paid_amount
            FROM subcontractors s 
            $where 
            ORDER BY s.id DESC";
            
    $result = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sid = $row['id'];
        
        // Calculate contract/work value and paid value for this subcontractor
        // Sum of all installments assigned to projects where this subcontractor is the main subcontractor
        $workValSQL = "SELECT SUM(budget) as total_work, SUM(contract_value) as total_contract 
                       FROM projects_list 
                       WHERE main_subcontractor_id = $sid AND company_id = $company_id AND module_type = 1";
        $workValRes = mysqli_query($conn, $workValSQL);
        $workVal = mysqli_fetch_assoc($workValRes);
        
        $row['total_work'] = (float)($workVal['total_contract'] ?? 0);
        $row['paid_amount'] = (float)($row['paid_amount'] ?? 0);
        $row['remaining_amount'] = max(0, $row['total_work'] - $row['paid_amount']);
        
        // Find current active projects
        $projSQL = "SELECT group_concat(project_name separator ', ') as active_projects 
                    FROM projects_list 
                    WHERE main_subcontractor_id = $sid AND status = 'กำลังดำเนินการ' AND company_id = $company_id";
        $projRes = mysqli_query($conn, $projSQL);
        $projData = mysqli_fetch_assoc($projRes);
        $row['active_projects'] = $projData['active_projects'] ?? '-';
        
        $data[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

if ($action === 'subcontractor_save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $team_type = mysqli_real_escape_string($conn, $_POST['team_type']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อผู้รับเหมา']);
        exit;
    }

    if ($id > 0) {
        $sql = "UPDATE subcontractors SET name = '$name', phone = '$phone', team_type = '$team_type', status = '$status' WHERE id = $id AND company_id = $company_id";
    } else {
        $sql = "INSERT INTO subcontractors (company_id, name, phone, team_type, status) VALUES ($company_id, '$name', '$phone', '$team_type', '$status')";
    }

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลผู้รับเหมาเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'subcontractor_delete') {
    $id = (int)$_POST['id'];

    // Check if contractor has payments
    $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM subcontractor_payments WHERE subcontractor_id = $id");
    if (mysqli_fetch_assoc($check)['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากผู้รับเหมารายนี้มีประวัติการจ่ายเงินแล้ว']);
        exit;
    }

    $sql = "DELETE FROM subcontractors WHERE id = $id AND company_id = $company_id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'project_list') {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

    $where = "WHERE p.module_type = 1 AND p.company_id = $company_id";
    if ($search !== '') {
        $where .= " AND (p.project_name LIKE '%$search%' OR p.project_code LIKE '%$search%' OR p.customer_name LIKE '%$search%')";
    }
    if ($status !== '') {
        $where .= " AND p.status = '$status'";
    }

    $sql = "SELECT p.*, s.name as contractor_name, s.team_type as contractor_team
            FROM projects_list p
            LEFT JOIN subcontractors s ON p.main_subcontractor_id = s.id
            $where 
            ORDER BY p.id DESC";
            
    $result = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $pid = $row['id'];
        
        // Sum installments
        $instSQL = "SELECT SUM(amount) as total_amt, SUM(paid_amount) as total_paid FROM project_installments WHERE project_id = $pid";
        $instRes = mysqli_query($conn, $instSQL);
        $instData = mysqli_fetch_assoc($instRes);
        
        $row['total_installments'] = (float)($instData['total_amt'] ?? 0);
        $row['paid_installments'] = (float)($instData['total_paid'] ?? 0);
        $row['remaining_installments'] = max(0, $row['total_installments'] - $row['paid_installments']);
        
        // Sum additional expenses
        $expSQL = "SELECT SUM(amount) as total_exp FROM project_additional_expenses WHERE project_id = $pid AND status = 'อนุมัติแล้ว'";
        $expRes = mysqli_query($conn, $expSQL);
        $row['additional_expenses'] = (float)(mysqli_fetch_assoc($expRes)['total_exp'] ?? 0);
        
        // Calculate progress based on paid / total installments
        if ($row['total_installments'] > 0) {
            $row['progress_percent'] = round(($row['paid_installments'] / $row['total_installments']) * 100);
        } else {
            $row['progress_percent'] = 0;
        }
        // Fetch assigned subcontractors
        $assignedSQL = "SELECT * FROM project_assigned_subcontractors WHERE project_id = $pid ORDER BY id ASC";
        $assignedRes = mysqli_query($conn, $assignedSQL);
        $assigned_subcontractors = [];
        if($assignedRes) {
            while($ar = mysqli_fetch_assoc($assignedRes)) {
                $assigned_subcontractors[] = $ar;
            }
        }
        $row['assigned_subcontractors'] = $assigned_subcontractors;
        
        $data[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

if ($action === 'project_save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $project_code = mysqli_real_escape_string($conn, $_POST['project_code']);
    $project_type = mysqli_real_escape_string($conn, $_POST['project_type']);
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $customer_phone = mysqli_real_escape_string($conn, $_POST['customer_phone']);
    $customer_email = mysqli_real_escape_string($conn, $_POST['customer_email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $start_date = !empty($_POST['start_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['start_date']) . "'" : "NULL";
    $end_date = !empty($_POST['end_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['end_date']) . "'" : "NULL";
    $actual_end_date = !empty($_POST['actual_end_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['actual_end_date']) . "'" : "NULL";
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $project_manager = mysqli_real_escape_string($conn, $_POST['project_manager']);
    $budget = (float)$_POST['budget'];
    $contract_value = (float)$_POST['contract_value'];
    $main_subcontractor_id = !empty($_POST['main_subcontractor_id']) ? (int)$_POST['main_subcontractor_id'] : "NULL";
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    if (empty($project_name)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อโปรเจ็ค']);
        exit;
    }

    if ($id > 0) {
        $sql = "UPDATE projects_list SET 
                project_name = '$project_name', 
                project_code = '$project_code', 
                project_type = '$project_type', 
                customer_name = '$customer_name', 
                customer_phone = '$customer_phone', 
                customer_email = '$customer_email', 
                address = '$address', 
                start_date = $start_date, 
                end_date = $end_date, 
                actual_end_date = $actual_end_date, 
                status = '$status', 
                project_manager = '$project_manager', 
                budget = $budget, 
                contract_value = $contract_value, 
                main_subcontractor_id = $main_subcontractor_id,
                note = '$note'
                WHERE id = $id AND company_id = $company_id";
    } else {
        $sql = "INSERT INTO projects_list (
                    company_id, project_name, project_code, project_type, customer_name, customer_phone, 
                    customer_email, address, start_date, end_date, actual_end_date, status, 
                    project_manager, budget, contract_value, main_subcontractor_id, note, module_type
                ) VALUES (
                    $company_id, '$project_name', '$project_code', '$project_type', '$customer_name', '$customer_phone', 
                    '$customer_email', '$address', $start_date, $end_date, $actual_end_date, '$status', 
                    '$project_manager', $budget, $contract_value, $main_subcontractor_id, '$note', 1
                )";
    }

    if (mysqli_query($conn, $sql)) {
        $new_id = $id > 0 ? $id : mysqli_insert_id($conn);
        
        // Handle assigned subcontractors
        if (isset($_POST['assigned_subs_json'])) {
            $items = json_decode($_POST['assigned_subs_json'], true);
            mysqli_query($conn, "DELETE FROM project_assigned_subcontractors WHERE project_id = $new_id");
            if (is_array($items)) {
                foreach ($items as $item) {
                    $job_type = mysqli_real_escape_string($conn, $item['job_type']);
                    $subcontractor_id = (int)$item['subcontractor_id'];
                    $contract_amount = (float)$item['contract_amount'];
                    if ($subcontractor_id > 0) {
                        $sub_sql = "INSERT INTO project_assigned_subcontractors (company_id, project_id, job_type, subcontractor_id, contract_amount) 
                                    VALUES ($company_id, $new_id, '$job_type', $subcontractor_id, $contract_amount)";
                        mysqli_query($conn, $sub_sql);
                    }
                }
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลโปรเจ็คเรียบร้อยแล้ว', 'id' => $new_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'project_get_details') {
    $id = (int)$_GET['id'];
    
    $sql = "SELECT p.*, s.name as contractor_name, s.phone as contractor_phone, s.team_type as contractor_team
            FROM projects_list p
            LEFT JOIN subcontractors s ON p.main_subcontractor_id = s.id
            WHERE p.id = $id AND p.company_id = $company_id";
            
    $res = mysqli_query($conn, $sql);
    $project = mysqli_fetch_assoc($res);
    
    if (!$project) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลโปรเจ็ค']);
        exit;
    }
    
    // Installments
    $instSQL = "SELECT * FROM project_installments WHERE project_id = $id ORDER BY installment_number ASC";
    $instRes = mysqli_query($conn, $instSQL);
    $installments = [];
    $total_inst_amount = 0;
    $total_inst_paid = 0;
    while($r = mysqli_fetch_assoc($instRes)) {
        $r['amount'] = (float)$r['amount'];
        $r['paid_amount'] = (float)$r['paid_amount'];
        $r['remaining_amount'] = max(0, $r['amount'] - $r['paid_amount']);
        $total_inst_amount += $r['amount'];
        $total_inst_paid += $r['paid_amount'];
        $installments[] = $r;
    }
    
    // Additional Expenses
    $expSQL = "SELECT * FROM project_additional_expenses WHERE project_id = $id ORDER BY expense_date DESC";
    $expRes = mysqli_query($conn, $expSQL);
    $expenses = [];
    $total_exp_amount = 0;
    while($r = mysqli_fetch_assoc($expRes)) {
        $r['amount'] = (float)$r['amount'];
        if ($r['status'] === 'อนุมัติแล้ว') {
            $total_exp_amount += $r['amount'];
        }
        $expenses[] = $r;
    }
    
    // Payments List
    $paySQL = "SELECT p.*, s.name as subcontractor_name 
               FROM subcontractor_payments p
               LEFT JOIN subcontractors s ON p.subcontractor_id = s.id
               WHERE p.project_id = $id 
               ORDER BY p.payment_date DESC";
    $payRes = mysqli_query($conn, $paySQL);
    $payments = [];
    while($r = mysqli_fetch_assoc($payRes)) {
        $r['total_amount'] = (float)$r['total_amount'];
        $r['additional_amount'] = (float)$r['additional_amount'];
        $r['deduct_tax'] = (float)$r['deduct_tax'];
        $r['deduct_retention'] = (float)$r['deduct_retention'];
        $r['net_amount'] = (float)$r['net_amount'];
        $payments[] = $r;
    }

    // Subcontractors associated with this project (all who got paid on this project)
    $subSQL = "SELECT DISTINCT s.id, s.name, s.team_type, s.phone,
               (SELECT SUM(net_amount) FROM subcontractor_payments WHERE subcontractor_id = s.id AND project_id = $id) as paid_amount
               FROM subcontractors s
               JOIN subcontractor_payments p ON p.subcontractor_id = s.id
               WHERE p.project_id = $id
               UNION
               SELECT s.id, s.name, s.team_type, s.phone, 0 as paid_amount
               FROM subcontractors s
               JOIN projects_list pl ON pl.main_subcontractor_id = s.id
               WHERE pl.id = $id";
    $subRes = mysqli_query($conn, $subSQL);
    $subcontractors = [];
    $sub_ids = [];
    while($r = mysqli_fetch_assoc($subRes)) {
        if (!in_array($r['id'], $sub_ids) && !empty($r['id'])) {
            $sub_ids[] = $r['id'];
            $r['paid_amount'] = (float)$r['paid_amount'];
            $subcontractors[] = $r;
        }
    }

    // Financial totals
    $total_cost = $total_inst_paid + $total_exp_amount;
    $remaining_to_pay = max(0, $project['contract_value'] - $total_inst_paid);
    $profit = $project['contract_value'] - $total_cost;
    $profit_percent = $project['contract_value'] > 0 ? round(($profit / $project['contract_value']) * 100, 2) : 0;
    
    $financials = [
        'contract_value' => (float)$project['contract_value'],
        'paid_installments' => $total_inst_paid,
        'remaining_installments' => max(0, $total_inst_amount - $total_inst_paid),
        'additional_expenses' => $total_exp_amount,
        'total_cost' => $total_cost,
        'gross_profit' => $profit,
        'gross_profit_percent' => $profit_percent,
        'total_installments_val' => $total_inst_amount
    ];
    
    // Assigned Subcontractors
    $assignedSQL = "SELECT a.*, s.name as subcontractor_name 
                    FROM project_assigned_subcontractors a
                    LEFT JOIN subcontractors s ON a.subcontractor_id = s.id
                    WHERE a.project_id = $id ORDER BY a.id ASC";
    $assignedRes = mysqli_query($conn, $assignedSQL);
    $assigned_subcontractors = [];
    if($assignedRes) {
        while($r = mysqli_fetch_assoc($assignedRes)) {
            $r['contract_amount'] = (float)$r['contract_amount'];
            $subId = $r['subcontractor_id'];
            $paidSQL = "SELECT SUM(net_amount) as total_paid FROM subcontractor_payments WHERE project_id = $id AND subcontractor_id = $subId";
            $paidRes = mysqli_query($conn, $paidSQL);
            $r['paid_amount'] = (float)(mysqli_fetch_assoc($paidRes)['total_paid'] ?? 0);
            $r['remaining_amount'] = max(0, $r['contract_amount'] - $r['paid_amount']);
            $assigned_subcontractors[] = $r;
        }
    }

    // All active subcontractors for dropdowns
    $allSubSQL = "SELECT id, name, team_type FROM subcontractors WHERE company_id = $company_id AND status = 'กำลังทำงาน' ORDER BY name ASC";
    $allSubRes = mysqli_query($conn, $allSubSQL);
    $all_subcontractors = [];
    while($r = mysqli_fetch_assoc($allSubRes)) {
        $all_subcontractors[] = $r;
    }
    
    echo json_encode([
        'status' => 'success', 
        'project' => $project, 
        'installments' => $installments, 
        'expenses' => $expenses, 
        'payments' => $payments,
        'subcontractors' => $subcontractors,
        'assigned_subcontractors' => $assigned_subcontractors,
        'all_subcontractors' => $all_subcontractors,
        'financials' => $financials
    ]);
    exit;
}

if ($action === 'installment_save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $project_id = (int)$_POST['project_id'];
    $installment_number = (int)$_POST['installment_number'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $due_date = !empty($_POST['due_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['due_date']) . "'" : "NULL";
    $amount = (float)$_POST['amount'];
    $paid_amount = (float)($_POST['paid_amount'] ?? 0);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($name) || $project_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุข้อมูลให้ครบถ้วน']);
        exit;
    }

    if ($id > 0) {
        $sql = "UPDATE project_installments SET 
                installment_number = $installment_number, 
                name = '$name', 
                due_date = $due_date, 
                amount = $amount, 
                paid_amount = $paid_amount, 
                status = '$status' 
                WHERE id = $id";
    } else {
        $sql = "INSERT INTO project_installments (project_id, installment_number, name, due_date, amount, paid_amount, status) 
                VALUES ($project_id, $installment_number, '$name', $due_date, $amount, $paid_amount, '$status')";
    }

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'บันทึกงวดงานเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'save_assigned_subcontractors') {
    $project_id = (int)$_POST['project_id'];
    $data_json = $_POST['data_json'];
    $items = json_decode($data_json, true);

    // Delete existing records for this project
    mysqli_query($conn, "DELETE FROM project_assigned_subcontractors WHERE project_id = $project_id");

    if (is_array($items)) {
        foreach ($items as $idx => $item) {
            $job_type = mysqli_real_escape_string($conn, $item['job_type']);
            $subcontractor_id = (int)$item['subcontractor_id'];
            $contract_amount = (float)$item['contract_amount'];
            
            // Handle file upload
            $attachment = '';
            $file_key = 'attachment_' . $idx;
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/contracts/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExtension = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
                $newFileName = 'contract_' . $project_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $destPath)) {
                    $attachment = 'uploads/contracts/' . $newFileName;
                }
            } else {
                // Check if existing attachment was passed back
                if (isset($item['existing_attachment']) && !empty($item['existing_attachment'])) {
                    $attachment = mysqli_real_escape_string($conn, $item['existing_attachment']);
                }
            }

            $sql = "INSERT INTO project_assigned_subcontractors (company_id, project_id, job_type, subcontractor_id, contract_amount, attachment) 
                    VALUES ($company_id, $project_id, '$job_type', $subcontractor_id, $contract_amount, '$attachment')";
            mysqli_query($conn, $sql);
        }
    }
    echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลผู้รับเหมาในโปรเจคเรียบร้อยแล้ว']);
    exit;
}

if ($action === 'installment_delete') {
    $id = (int)$_POST['id'];
    $sql = "DELETE FROM project_installments WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบงวดงานเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'expense_list') {
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $where = "WHERE e.company_id = $company_id";
    if ($project_id > 0) {
        $where .= " AND e.project_id = $project_id";
    }

    $sql = "SELECT e.*, p.project_name, p.project_code 
            FROM project_additional_expenses e
            JOIN projects_list p ON e.project_id = p.id
            $where 
            ORDER BY e.expense_date DESC, e.id DESC";
            
    $result = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['amount'] = (float)$row['amount'];
        $data[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

if ($action === 'expense_save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $project_id = (int)$_POST['project_id'];
    $expense_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $expense_type = mysqli_real_escape_string($conn, $_POST['expense_type']);
    $reference_task = mysqli_real_escape_string($conn, $_POST['reference_task']);
    $amount = (float)$_POST['amount'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    if ($project_id <= 0 || empty($expense_date) || empty($item_name) || $amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    // Handle attachment upload if present
    $attachment = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/expenses/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExtension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $newFileName = 'exp_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destPath)) {
            $attachment = 'uploads/expenses/' . $newFileName;
        }
    }

    if ($id > 0) {
        $attach_sql = $attachment !== '' ? ", attachment = '$attachment'" : "";
        $sql = "UPDATE project_additional_expenses SET 
                project_id = $project_id, 
                expense_date = '$expense_date', 
                item_name = '$item_name', 
                expense_type = '$expense_type', 
                reference_task = '$reference_task', 
                amount = $amount, 
                status = '$status', 
                note = '$note'
                $attach_sql
                WHERE id = $id AND company_id = $company_id";
    } else {
        $sql = "INSERT INTO project_additional_expenses (company_id, project_id, expense_date, item_name, expense_type, reference_task, amount, status, note, attachment) 
                VALUES ($company_id, $project_id, '$expense_date', '$item_name', '$expense_type', '$reference_task', $amount, '$status', '$note', '$attachment')";
    }

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'บันทึกค่าใช้จ่ายเพิ่มเติมเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'expense_delete') {
    $id = (int)$_POST['id'];
    $sql = "DELETE FROM project_additional_expenses WHERE id = $id AND company_id = $company_id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบค่าใช้จ่ายเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'payment_list') {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $subcontractor_id = isset($_GET['subcontractor_id']) ? (int)$_GET['subcontractor_id'] : 0;
    
    $where = "WHERE p.company_id = $company_id";
    if ($subcontractor_id > 0) {
        $where .= " AND p.subcontractor_id = $subcontractor_id";
    }
    if ($search !== '') {
        $where .= " AND (p.payment_number LIKE '%$search%' OR s.name LIKE '%$search%' OR pr.project_name LIKE '%$search%')";
    }

    $sql = "SELECT p.*, s.name as contractor_name, s.team_type as contractor_team, pr.project_name, pr.project_code, inst.name as installment_name
            FROM subcontractor_payments p
            LEFT JOIN subcontractors s ON p.subcontractor_id = s.id
            LEFT JOIN projects_list pr ON p.project_id = pr.id
            LEFT JOIN project_installments inst ON p.installment_id = inst.id
            $where 
            ORDER BY p.payment_date DESC, p.id DESC";
            
    $result = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['total_amount'] = (float)$row['total_amount'];
        $row['additional_amount'] = (float)$row['additional_amount'];
        $row['deduct_tax'] = (float)$row['deduct_tax'];
        $row['deduct_retention'] = (float)$row['deduct_retention'];
        $row['net_amount'] = (float)$row['net_amount'];
        $data[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

if ($action === 'payment_save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $payment_number = mysqli_real_escape_string($conn, $_POST['payment_number']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $bank_account = mysqli_real_escape_string($conn, $_POST['bank_account']);
    $subcontractor_id = (int)$_POST['subcontractor_id'];
    $project_id = (int)$_POST['project_id'];
    $installment_id = (int)$_POST['installment_id'];
    $total_amount = (float)$_POST['total_amount'];
    $additional_amount = (float)($_POST['additional_amount'] ?? 0);
    $deduct_tax = (float)($_POST['deduct_tax'] ?? 0);
    $deduct_retention = (float)($_POST['deduct_retention'] ?? 0);
    $net_amount = (float)$_POST['net_amount'];
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    if ($subcontractor_id <= 0 || $project_id <= 0 || empty($payment_date) || empty($payment_number)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุข้อมูลที่จำเป็นให้ครบถ้วน']);
        exit;
    }

    // Handle attachment upload
    $attachment = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/payments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileExtension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $newFileName = 'pay_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $destPath = $uploadDir . $newFileName;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destPath)) {
            $attachment = 'uploads/payments/' . $newFileName;
        }
    }

    if ($id > 0) {
        $attach_sql = $attachment !== '' ? ", attachment = '$attachment'" : "";
        $sql = "UPDATE subcontractor_payments SET 
                payment_number = '$payment_number', 
                payment_date = '$payment_date', 
                payment_method = '$payment_method', 
                bank_account = '$bank_account', 
                subcontractor_id = $subcontractor_id, 
                project_id = $project_id, 
                installment_id = $installment_id, 
                total_amount = $total_amount, 
                additional_amount = $additional_amount, 
                deduct_tax = $deduct_tax, 
                deduct_retention = $deduct_retention, 
                net_amount = $net_amount, 
                note = '$note'
                $attach_sql
                WHERE id = $id AND company_id = $company_id";
    } else {
        $sql = "INSERT INTO subcontractor_payments (
                    company_id, payment_number, payment_date, payment_method, bank_account, 
                    subcontractor_id, project_id, installment_id, total_amount, additional_amount, 
                    deduct_tax, deduct_retention, net_amount, note, attachment
                ) VALUES (
                    $company_id, '$payment_number', '$payment_date', '$payment_method', '$bank_account', 
                    $subcontractor_id, $project_id, $installment_id, $total_amount, $additional_amount, 
                    $deduct_tax, $deduct_retention, $net_amount, '$note', '$attachment'
                )";
    }

    if (mysqli_query($conn, $sql)) {
        $payment_id = $id > 0 ? $id : mysqli_insert_id($conn);
        
        // Handle payment items (if sent as array)
        if (isset($_POST['items_json'])) {
            $items = json_decode($_POST['items_json'], true);
            if (is_array($items)) {
                // Clear old items if updating
                if ($id > 0) {
                    mysqli_query($conn, "DELETE FROM payment_items WHERE payment_id = $id");
                }
                
                foreach ($items as $item) {
                    $item_type = mysqli_real_escape_string($conn, $item['type']);
                    $details = mysqli_real_escape_string($conn, $item['details']);
                    $amt = (float)$item['amount'];
                    mysqli_query($conn, "INSERT INTO payment_items (payment_id, item_type, details, amount) VALUES ($payment_id, '$item_type', '$details', $amt)");
                }
            }
        }

        // Update paid amount in project installment if this payment links to one
        if ($installment_id > 0) {
            // Recalculate total paid on this installment
            $sumSQL = "SELECT SUM(total_amount) as total_paid FROM subcontractor_payments WHERE installment_id = $installment_id";
            $sumRes = mysqli_query($conn, $sumSQL);
            $total_paid = (float)(mysqli_fetch_assoc($sumRes)['total_paid'] ?? 0);
            
            // Get installment amount to update status
            $instInfoSQL = "SELECT amount FROM project_installments WHERE id = $installment_id";
            $instInfoRes = mysqli_query($conn, $instInfoSQL);
            $instInfo = mysqli_fetch_assoc($instInfoRes);
            $inst_amount = (float)($instInfo['amount'] ?? 0);
            
            $status = 'รอจ่าย';
            if ($total_paid >= $inst_amount) {
                $status = 'จ่ายแล้ว';
            } elseif ($total_paid > 0) {
                $status = 'บางส่วน';
            }
            
            mysqli_query($conn, "UPDATE project_installments SET paid_amount = $total_paid, status = '$status' WHERE id = $installment_id");
        }

        echo json_encode(['status' => 'success', 'message' => 'บันทึกการจ่ายเงินสำเร็จ', 'payment_id' => $payment_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'payment_delete') {
    $id = (int)$_POST['id'];
    
    // Get installment link before deleting
    $paySQL = "SELECT installment_id FROM subcontractor_payments WHERE id = $id";
    $payRes = mysqli_query($conn, $paySQL);
    $payData = mysqli_fetch_assoc($payRes);
    $installment_id = (int)($payData['installment_id'] ?? 0);
    
    $sql = "DELETE FROM subcontractor_payments WHERE id = $id AND company_id = $company_id";
    if (mysqli_query($conn, $sql)) {
        mysqli_query($conn, "DELETE FROM payment_items WHERE payment_id = $id");
        
        // Recalculate installment paid amount if deleted payment was linked
        if ($installment_id > 0) {
            $sumSQL = "SELECT SUM(total_amount) as total_paid FROM subcontractor_payments WHERE installment_id = $installment_id";
            $sumRes = mysqli_query($conn, $sumSQL);
            $total_paid = (float)(mysqli_fetch_assoc($sumRes)['total_paid'] ?? 0);
            
            $instInfoSQL = "SELECT amount FROM project_installments WHERE id = $installment_id";
            $instInfoRes = mysqli_query($conn, $instInfoSQL);
            $inst_amount = (float)(mysqli_fetch_assoc($instInfoRes)['amount'] ?? 0);
            
            $status = 'รอจ่าย';
            if ($total_paid >= $inst_amount) {
                $status = 'จ่ายแล้ว';
            } elseif ($total_paid > 0) {
                $status = 'บางส่วน';
            }
            
            mysqli_query($conn, "UPDATE project_installments SET paid_amount = $total_paid, status = '$status' WHERE id = $installment_id");
        }

        echo json_encode(['status' => 'success', 'message' => 'ลบประวัติการจ่ายเงินเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action === 'get_overview_stats') {
    // 1. Total Subcontractors
    $subSQL = "SELECT COUNT(*) as total, SUM(CASE WHEN status='กำลังทำงาน' THEN 1 ELSE 0 END) as working FROM subcontractors WHERE company_id = $company_id";
    $subRes = mysqli_query($conn, $subSQL);
    $subData = mysqli_fetch_assoc($subRes);
    
    // 2. Active Projects
    $projSQL = "SELECT COUNT(*) as total, SUM(contract_value) as total_value FROM projects_list WHERE module_type = 1 AND status = 'กำลังดำเนินการ' AND company_id = $company_id";
    $projRes = mysqli_query($conn, $projSQL);
    $projData = mysqli_fetch_assoc($projRes);

    // 3. Overall Projects
    $allProjSQL = "SELECT COUNT(*) as total, SUM(contract_value) as total_value FROM projects_list WHERE module_type = 1 AND company_id = $company_id";
    $allProjRes = mysqli_query($conn, $allProjSQL);
    $allProjData = mysqli_fetch_assoc($allProjRes);
    
    // 4. Payments
    $paySQL = "SELECT SUM(net_amount) as total_paid FROM subcontractor_payments WHERE company_id = $company_id";
    $payRes = mysqli_query($conn, $paySQL);
    $total_paid = (float)(mysqli_fetch_assoc($payRes)['total_paid'] ?? 0);

    // 5. Total Installment budget (this is the cost allocated to subcontractors)
    $instSQL = "SELECT SUM(i.amount) as total_cost, SUM(i.paid_amount) as paid_cost 
                FROM project_installments i
                JOIN projects_list p ON i.project_id = p.id
                WHERE p.module_type = 1 AND p.company_id = $company_id";
    $instRes = mysqli_query($conn, $instSQL);
    $instData = mysqli_fetch_assoc($instRes);
    
    $total_subcontractor_cost = (float)($instData['total_cost'] ?? 0);
    $paid_subcontractor_cost = (float)($instData['paid_cost'] ?? 0);
    $remaining_subcontractor_cost = max(0, $total_subcontractor_cost - $paid_subcontractor_cost);
    
    // Calculate percents
    $paid_percent = $total_subcontractor_cost > 0 ? round(($paid_subcontractor_cost / $total_subcontractor_cost) * 100, 2) : 0;
    $remaining_percent = $total_subcontractor_cost > 0 ? round(($remaining_subcontractor_cost / $total_subcontractor_cost) * 100, 2) : 0;

    echo json_encode([
        'status' => 'success',
        'stats' => [
            'subcontractors_total' => (int)$subData['total'],
            'subcontractors_working' => (int)$subData['working'],
            'projects_active' => (int)$projData['total'],
            'projects_active_value' => (float)$projData['total_value'],
            'projects_total' => (int)$allProjData['total'],
            'projects_total_value' => (float)$allProjData['total_value'],
            'total_paid' => $paid_subcontractor_cost,
            'total_remaining' => $remaining_subcontractor_cost,
            'paid_percent' => $paid_percent,
            'remaining_percent' => $remaining_percent,
            'cost_total' => $total_subcontractor_cost
        ]
    ]);
    exit;
}

if ($action === 'cost_report') {
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
}

if ($action === 'get_payment_form_details') {
    $project_id = (int)$_GET['project_id'];
    $subcontractor_id = (int)$_GET['subcontractor_id'];
    
    // Get project detail
    $p_sql = "SELECT contract_value, project_name FROM projects_list WHERE id = $project_id";
    $p_res = mysqli_query($conn, $p_sql);
    $proj = mysqli_fetch_assoc($p_res);
    
    // Get subcontractor detail
    $s_sql = "SELECT name, phone, team_type FROM subcontractors WHERE id = $subcontractor_id";
    $s_res = mysqli_query($conn, $s_sql);
    $sub = mysqli_fetch_assoc($s_res);
    
    // Get installments for this project
    $instSQL = "SELECT id, name, amount, paid_amount FROM project_installments WHERE project_id = $project_id AND (status != 'จ่ายแล้ว' OR id IN (SELECT installment_id FROM subcontractor_payments WHERE project_id = $project_id AND subcontractor_id = $subcontractor_id))";
    $instRes = mysqli_query($conn, $instSQL);
    $installments = [];
    while($r = mysqli_fetch_assoc($instRes)) {
        $r['amount'] = (float)$r['amount'];
        $r['paid_amount'] = (float)$r['paid_amount'];
        $r['remaining_amount'] = max(0, $r['amount'] - $r['paid_amount']);
        $installments[] = $r;
    }
    
    // Summary of all payments made to this subcontractor for this project
    $paidSQL = "SELECT SUM(net_amount) as total_paid FROM subcontractor_payments WHERE subcontractor_id = $subcontractor_id AND project_id = $project_id";
    $paidRes = mysqli_query($conn, $paidSQL);
    $total_paid = (float)(mysqli_fetch_assoc($paidRes)['total_paid'] ?? 0);
    
    echo json_encode([
        'status' => 'success',
        'project_name' => $proj['project_name'] ?? '',
        'contract_value' => (float)($proj['contract_value'] ?? 0),
        'subcontractor_name' => $sub['name'] ?? '',
        'installments' => $installments,
        'total_paid' => $total_paid
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
exit;
?>


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

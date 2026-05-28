<?php
require_once '../config.php';
session_start();

// Ensure JSON header for AJAX responses
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_login'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนใช้งาน']);
    exit();
}

$company_id = $_SESSION['company_id'] ?? 1;

// 1. Setup tables if not exist
$createSettingsTable = "CREATE TABLE IF NOT EXISTS payroll_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL UNIQUE,
    pay_day INT DEFAULT 10,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createSettingsTable);

$createHolidaysTable = "CREATE TABLE IF NOT EXISTS payroll_holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    holiday_date DATE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createHolidaysTable);

$createEmployeesTable = "CREATE TABLE IF NOT EXISTS payroll_employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    emp_code VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    salary DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    start_date DATE NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    max_business_leave INT NOT NULL DEFAULT 7,
    max_sick_leave INT NOT NULL DEFAULT 30,
    max_annual_leave INT NOT NULL DEFAULT 6,
    max_other_leave INT NOT NULL DEFAULT 15,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createEmployeesTable);

$createAttendanceTable = "CREATE TABLE IF NOT EXISTS payroll_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    work_date DATE NOT NULL,
    check_in TIME NULL,
    check_out TIME NULL,
    status ENUM('normal', 'late', 'absent', 'leave') NOT NULL DEFAULT 'normal',
    leave_type ENUM('business', 'sick', 'annual', 'other') NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_emp_date (employee_id, work_date),
    FOREIGN KEY (employee_id) REFERENCES payroll_employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createAttendanceTable);

// Insert default setting if not exist
$checkSetting = mysqli_query($conn, "SELECT id FROM payroll_settings WHERE company_id = $company_id");
if (mysqli_num_rows($checkSetting) == 0) {
    mysqli_query($conn, "INSERT IGNORE INTO payroll_settings (company_id, pay_day) VALUES ($company_id, 10)");
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    // -------------------------------------------------------------
    // SETTINGS ACTIONS
    // -------------------------------------------------------------
    case 'get_settings':
        $sql = "SELECT pay_day FROM payroll_settings WHERE company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($res);
        echo json_encode($data ?: ['pay_day' => 10]);
        break;

    case 'save_settings':
        $pay_day = (int)($_POST['pay_day'] ?? 10);
        if ($pay_day < 1 || $pay_day > 31) {
            echo json_encode(['status' => 'error', 'message' => 'วันที่จ่ายเงินต้องอยู่ระหว่าง 1 ถึง 31']);
            exit();
        }
        $sql = "INSERT INTO payroll_settings (company_id, pay_day) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE pay_day = VALUES(pay_day)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $company_id, $pay_day);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกการตั้งค่าระบบเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'list_holidays':
        $sql = "SELECT * FROM payroll_holidays WHERE company_id = ? ORDER BY holiday_date ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $holidays = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $holidays[] = $row;
        }
        echo json_encode($holidays);
        break;

    case 'save_holiday':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $holiday_date = $_POST['holiday_date'] ?? '';
        $name = $_POST['name'] ?? '';

        if (empty($holiday_date) || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
            exit();
        }

        if ($id > 0) {
            $sql = "UPDATE payroll_holidays SET holiday_date = ?, name = ? WHERE id = ? AND company_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssii", $holiday_date, $name, $id, $company_id);
        } else {
            $sql = "INSERT INTO payroll_holidays (company_id, holiday_date, name) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iss", $company_id, $holiday_date, $name);
        }

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกวันหยุดเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'delete_holiday':
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM payroll_holidays WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'ลบวันหยุดเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    // -------------------------------------------------------------
    // EMPLOYEES ACTIONS
    // -------------------------------------------------------------
    case 'list_employees':
        $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
        $where = "WHERE company_id = $company_id";
        if (!empty($search)) {
            $where .= " AND (emp_code LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR department LIKE '%$search%' OR position LIKE '%$search%')";
        }
        $sql = "SELECT * FROM payroll_employees $where ORDER BY emp_code ASC";
        $res = mysqli_query($conn, $sql);
        $employees = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $employees[] = $row;
        }
        echo json_encode($employees);
        break;

    case 'get_employee':
        $id = (int)($_GET['id'] ?? 0);
        $sql = "SELECT * FROM payroll_employees WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($res);
        echo json_encode($data ?: ['status' => 'error', 'message' => 'ไม่พบข้อมูลพนักงาน']);
        break;

    case 'save_employee':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $emp_code = $_POST['emp_code'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $department = $_POST['department'] ?? '';
        $position = $_POST['position'] ?? '';
        $salary = (float)($_POST['salary'] ?? 0.00);
        $start_date = $_POST['start_date'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $max_business_leave = (int)($_POST['max_business_leave'] ?? 7);
        $max_sick_leave = (int)($_POST['max_sick_leave'] ?? 30);
        $max_annual_leave = (int)($_POST['max_annual_leave'] ?? 6);
        $max_other_leave = (int)($_POST['max_other_leave'] ?? 15);
        $status = $_POST['status'] ?? 'active';

        if (empty($emp_code) || empty($first_name) || empty($last_name) || empty($department) || empty($position) || empty($start_date)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน']);
            exit();
        }

        // Check duplicated emp_code
        $dup_sql = $id > 0 
            ? "SELECT id FROM payroll_employees WHERE emp_code = ? AND id != ? AND company_id = ?"
            : "SELECT id FROM payroll_employees WHERE emp_code = ? AND company_id = ?";
        $dup_stmt = mysqli_prepare($conn, $dup_sql);
        if ($id > 0) {
            mysqli_stmt_bind_param($dup_stmt, "sii", $emp_code, $id, $company_id);
        } else {
            mysqli_stmt_bind_param($dup_stmt, "si", $emp_code, $company_id);
        }
        mysqli_stmt_execute($dup_stmt);
        $dup_res = mysqli_stmt_get_result($dup_stmt);
        if (mysqli_num_rows($dup_res) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'รหัสพนักงานนี้มีอยู่ในระบบแล้ว']);
            exit();
        }

        if ($id > 0) {
            $sql = "UPDATE payroll_employees SET 
                    emp_code = ?, first_name = ?, last_name = ?, department = ?, position = ?, 
                    salary = ?, start_date = ?, phone = ?, max_business_leave = ?, max_sick_leave = ?, 
                    max_annual_leave = ?, max_other_leave = ?, status = ?
                    WHERE id = ? AND company_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssdssiiiisii", 
                $emp_code, $first_name, $last_name, $department, $position, 
                $salary, $start_date, $phone, $max_business_leave, $max_sick_leave, 
                $max_annual_leave, $max_other_leave, $status, $id, $company_id
            );
        } else {
            $sql = "INSERT INTO payroll_employees (
                        company_id, emp_code, first_name, last_name, department, position, 
                        salary, start_date, phone, max_business_leave, max_sick_leave, 
                        max_annual_leave, max_other_leave, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isssssdssiiiis", 
                $company_id, $emp_code, $first_name, $last_name, $department, $position, 
                $salary, $start_date, $phone, $max_business_leave, $max_sick_leave, 
                $max_annual_leave, $max_other_leave, $status
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลพนักงานเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'delete_employee':
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM payroll_employees WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลพนักงานเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    // -------------------------------------------------------------
    // ATTENDANCE ACTIONS
    // -------------------------------------------------------------
    case 'list_attendance':
        $work_date = $_GET['work_date'] ?? date('Y-m-d');
        
        $sql = "SELECT e.id as employee_id, e.emp_code, e.first_name, e.last_name, e.department, e.position,
                       a.id as attendance_id, a.work_date, a.check_in, a.check_out, a.status, a.leave_type, a.note
                FROM payroll_employees e
                LEFT JOIN payroll_attendance a ON e.id = a.employee_id AND a.work_date = ?
                WHERE e.company_id = ? AND e.status = 'active'
                ORDER BY e.emp_code ASC";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $work_date, $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        $attendance = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $attendance[] = $row;
        }
        echo json_encode($attendance);
        break;

    case 'save_attendance':
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        $work_date = $_POST['work_date'] ?? '';
        $status = $_POST['status'] ?? 'normal';
        $check_in = (!empty($_POST['check_in']) && $status !== 'absent' && $status !== 'leave') ? $_POST['check_in'] : null;
        $check_out = (!empty($_POST['check_out']) && $status !== 'absent' && $status !== 'leave') ? $_POST['check_out'] : null;
        $leave_type = ($status === 'leave') ? ($_POST['leave_type'] ?? 'business') : null;
        $note = $_POST['note'] ?? '';

        if ($employee_id <= 0 || empty($work_date)) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลผู้ใช้หรือวันที่ไม่ถูกต้อง']);
            exit();
        }

        $sql = "INSERT INTO payroll_attendance (company_id, employee_id, work_date, check_in, check_out, status, leave_type, note)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                check_in = VALUES(check_in),
                check_out = VALUES(check_out),
                status = VALUES(status),
                leave_type = VALUES(leave_type),
                note = VALUES(note)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissssss", $company_id, $employee_id, $work_date, $check_in, $check_out, $status, $leave_type, $note);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกเวลาทำงานเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'save_attendance_batch':
        $work_date = $_POST['work_date'] ?? '';
        $data_json = $_POST['attendance_data'] ?? '[]';
        $attendance_list = json_decode($data_json, true);

        if (empty($work_date) || !is_array($attendance_list)) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลการส่งไม่ถูกต้อง']);
            exit();
        }

        mysqli_begin_transaction($conn);
        $success = true;
        $error_msg = '';

        foreach ($attendance_list as $item) {
            $employee_id = (int)($item['employee_id'] ?? 0);
            $status = $item['status'] ?? 'normal';
            $check_in = (!empty($item['check_in']) && $status !== 'absent' && $status !== 'leave') ? $item['check_in'] : null;
            $check_out = (!empty($item['check_out']) && $status !== 'absent' && $status !== 'leave') ? $item['check_out'] : null;
            $leave_type = ($status === 'leave') ? ($item['leave_type'] ?? 'business') : null;
            $note = $item['note'] ?? '';

            if ($employee_id > 0) {
                $sql = "INSERT INTO payroll_attendance (company_id, employee_id, work_date, check_in, check_out, status, leave_type, note)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        check_in = VALUES(check_in),
                        check_out = VALUES(check_out),
                        status = VALUES(status),
                        leave_type = VALUES(leave_type),
                        note = VALUES(note)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iissssss", $company_id, $employee_id, $work_date, $check_in, $check_out, $status, $leave_type, $note);
                if (!mysqli_stmt_execute($stmt)) {
                    $success = false;
                    $error_msg = mysqli_error($conn);
                    break;
                }
            }
        }

        if ($success) {
            mysqli_commit($conn);
            echo json_encode(['status' => 'success', 'message' => 'บันทึกเวลาทำงานทั้งหมดเรียบร้อยแล้ว']);
        } else {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $error_msg]);
        }
        break;

    // -------------------------------------------------------------
    // LEAVE CALCULATIONS & DASHBOARD SUMMARY ACTIONS
    // -------------------------------------------------------------
    case 'calculate_leaves':
        // Get leave summary grouped by employee and leave_type
        $leave_sql = "SELECT employee_id, leave_type, COUNT(*) as count 
                      FROM payroll_attendance 
                      WHERE company_id = ? AND status = 'leave' AND leave_type IS NOT NULL
                      GROUP BY employee_id, leave_type";
        $stmt = mysqli_prepare($conn, $leave_sql);
        mysqli_stmt_bind_param($stmt, "i", $company_id);
        mysqli_stmt_execute($stmt);
        $leave_res = mysqli_stmt_get_result($stmt);
        
        $leave_map = [];
        while ($row = mysqli_fetch_assoc($leave_res)) {
            $emp_id = $row['employee_id'];
            $type = $row['leave_type'];
            $count = (int)$row['count'];
            if (!isset($leave_map[$emp_id])) {
                $leave_map[$emp_id] = [
                    'business' => 0,
                    'sick' => 0,
                    'annual' => 0,
                    'other' => 0
                ];
            }
            $leave_map[$emp_id][$type] = $count;
        }

        // Fetch all active employees
        $emp_sql = "SELECT id, emp_code, first_name, last_name, department, position, 
                           max_business_leave, max_sick_leave, max_annual_leave, max_other_leave
                    FROM payroll_employees 
                    WHERE company_id = ? AND status = 'active'
                    ORDER BY emp_code ASC";
        $emp_stmt = mysqli_prepare($conn, $emp_sql);
        mysqli_stmt_bind_param($emp_stmt, "i", $company_id);
        mysqli_stmt_execute($emp_stmt);
        $emp_res = mysqli_stmt_get_result($emp_stmt);

        $results = [];
        while ($emp = mysqli_fetch_assoc($emp_res)) {
            $emp_id = $emp['id'];
            $leaves_used = $leave_map[$emp_id] ?? [
                'business' => 0,
                'sick' => 0,
                'annual' => 0,
                'other' => 0
            ];
            
            $results[] = [
                'employee_id' => $emp['id'],
                'emp_code' => $emp['emp_code'],
                'name' => $emp['first_name'] . ' ' . $emp['last_name'],
                'department' => $emp['department'],
                'position' => $emp['position'],
                
                'business_used' => $leaves_used['business'],
                'business_max' => (int)$emp['max_business_leave'],
                
                'sick_used' => $leaves_used['sick'],
                'sick_max' => (int)$emp['max_sick_leave'],
                
                'annual_used' => $leaves_used['annual'],
                'annual_max' => (int)$emp['max_annual_leave'],
                
                'other_used' => $leaves_used['other'],
                'other_max' => (int)$emp['max_other_leave'],
            ];
        }
        echo json_encode($results);
        break;

    case 'get_dashboard_summary':
        // Calculate basic stats for the payroll dashboard
        // Total Employees
        $emp_sql = "SELECT COUNT(*) as total FROM payroll_employees WHERE company_id = ? AND status = 'active'";
        $emp_stmt = mysqli_prepare($conn, $emp_sql);
        mysqli_stmt_bind_param($emp_stmt, "i", $company_id);
        mysqli_stmt_execute($emp_stmt);
        $total_emp = mysqli_fetch_assoc(mysqli_stmt_get_result($emp_stmt))['total'] ?? 0;

        // Total Salary
        $sal_sql = "SELECT SUM(salary) as total_salary FROM payroll_employees WHERE company_id = ? AND status = 'active'";
        $sal_stmt = mysqli_prepare($conn, $sal_sql);
        mysqli_stmt_bind_param($sal_stmt, "i", $company_id);
        mysqli_stmt_execute($sal_stmt);
        $total_salary = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($sal_stmt))['total_salary'] ?? 0.00);

        // Daily Attendance stats (Today)
        $today = date('Y-m-d');
        $att_sql = "SELECT status, COUNT(*) as count FROM payroll_attendance 
                    WHERE company_id = ? AND work_date = ?
                    GROUP BY status";
        $att_stmt = mysqli_prepare($conn, $att_sql);
        mysqli_stmt_bind_param($att_stmt, "is", $company_id, $today);
        mysqli_stmt_execute($att_stmt);
        $att_res = mysqli_stmt_get_result($att_stmt);
        
        $att_stats = [
            'normal' => 0,
            'late' => 0,
            'absent' => 0,
            'leave' => 0
        ];
        while ($row = mysqli_fetch_assoc($att_res)) {
            $att_stats[$row['status']] = (int)$row['count'];
        }

        // Return summary
        echo json_encode([
            'total_employees' => $total_emp,
            'total_salary' => $total_salary,
            'attendance_today' => $att_stats,
            'today_date' => $today
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'การดำเนินการไม่ถูกต้อง']);
        break;
}
?>

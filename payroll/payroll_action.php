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
    pay_day VARCHAR(100) DEFAULT '10',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createSettingsTable);

// Ensure column type is VARCHAR if it was created as INT earlier
$checkTypeSQL = "SHOW COLUMNS FROM payroll_settings LIKE 'pay_day'";
$typeRes = mysqli_query($conn, $checkTypeSQL);
if ($typeRow = mysqli_fetch_assoc($typeRes)) {
    if (strpos(strtolower($typeRow['Type']), 'int') !== false) {
        mysqli_query($conn, "ALTER TABLE payroll_settings MODIFY COLUMN pay_day VARCHAR(100) DEFAULT '10'");
    }
}

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
    photo VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createEmployeesTable);

// Ensure column exists
$checkPhotoSQL = "SHOW COLUMNS FROM payroll_employees LIKE 'photo'";
$photoRes = mysqli_query($conn, $checkPhotoSQL);
if (mysqli_num_rows($photoRes) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll_employees ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER status");
}

// Ensure wage_type column exists
$checkWageTypeSQL = "SHOW COLUMNS FROM payroll_employees LIKE 'wage_type'";
$wtRes = mysqli_query($conn, $checkWageTypeSQL);
if (mysqli_num_rows($wtRes) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll_employees ADD COLUMN wage_type ENUM('monthly', 'daily') NOT NULL DEFAULT 'monthly' AFTER salary");
}

// Ensure description column exists
$checkDescSQL = "SHOW COLUMNS FROM payroll_employees LIKE 'description'";
$descRes = mysqli_query($conn, $checkDescSQL);
if (mysqli_num_rows($descRes) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll_employees ADD COLUMN description TEXT DEFAULT NULL AFTER photo");
}

// Ensure multiple positions table exists
$createPositionsTable = "CREATE TABLE IF NOT EXISTS payroll_employee_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    position VARCHAR(100) NOT NULL,
    wage_type ENUM('monthly', 'daily') NOT NULL DEFAULT 'daily',
    salary DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES payroll_employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createPositionsTable);

// Populate payroll_employee_positions from existing employees if it's empty
$checkEmptyPositions = mysqli_query($conn, "SELECT id FROM payroll_employee_positions LIMIT 1");
if ($checkEmptyPositions && mysqli_num_rows($checkEmptyPositions) == 0) {
    $existingEmp = mysqli_query($conn, "SELECT id, position, wage_type, salary FROM payroll_employees");
    if ($existingEmp) {
        while ($emp_row = mysqli_fetch_assoc($existingEmp)) {
            if (!empty($emp_row['position'])) {
                $ins_mig = "INSERT INTO payroll_employee_positions (employee_id, position, wage_type, salary) VALUES (?, ?, ?, ?)";
                $stmt_mig = mysqli_prepare($conn, $ins_mig);
                mysqli_stmt_bind_param($stmt_mig, "issd", $emp_row['id'], $emp_row['position'], $emp_row['wage_type'], $emp_row['salary']);
                mysqli_stmt_execute($stmt_mig);
            }
        }
    }
}


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
    position_id INT NULL DEFAULT NULL,
    allowance_fuel DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    allowance_travel DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    allowance_food DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    allowance_other DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    allowance_other_note VARCHAR(255) NULL DEFAULT NULL,
    deduction_damage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deduction_other DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deduction_other_note VARCHAR(255) NULL DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_emp_date (employee_id, work_date),
    FOREIGN KEY (employee_id) REFERENCES payroll_employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createAttendanceTable);

// Ensure the new columns exist (in case the table was created before)
$cols_to_add = [
    'position_id' => "INT NULL DEFAULT NULL",
    'allowance_fuel' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'allowance_travel' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'allowance_food' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'allowance_other' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'allowance_other_note' => "VARCHAR(255) NULL DEFAULT NULL",
    'deduction_damage' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'deduction_other' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    'deduction_other_note' => "VARCHAR(255) NULL DEFAULT NULL"
];
foreach ($cols_to_add as $col_name => $col_definition) {
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM payroll_attendance LIKE '$col_name'");
    if ($check_col && mysqli_num_rows($check_col) == 0) {
        mysqli_query($conn, "ALTER TABLE payroll_attendance ADD COLUMN $col_name $col_definition");
    }
}


$createPayrollRunsTable = "CREATE TABLE IF NOT EXISTS payroll_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    month_period VARCHAR(7) NOT NULL, -- Format YYYY-MM
    start_date DATE NULL,
    end_date DATE NULL,
    status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_company_month_range (company_id, month_period, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createPayrollRunsTable);

// Ensure columns exist and unique key is upgraded
$checkRunStartCol = mysqli_query($conn, "SHOW COLUMNS FROM payroll_runs LIKE 'start_date'");
if ($checkRunStartCol && mysqli_num_rows($checkRunStartCol) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll_runs ADD COLUMN start_date DATE NULL AFTER month_period");
}
$checkRunEndCol = mysqli_query($conn, "SHOW COLUMNS FROM payroll_runs LIKE 'end_date'");
if ($checkRunEndCol && mysqli_num_rows($checkRunEndCol) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll_runs ADD COLUMN end_date DATE NULL AFTER start_date");
}

$checkUniqueKey = mysqli_query($conn, "SHOW INDEX FROM payroll_runs WHERE Key_name = 'uniq_company_month'");
if ($checkUniqueKey && mysqli_num_rows($checkUniqueKey) > 0) {
    mysqli_query($conn, "ALTER TABLE payroll_runs DROP KEY uniq_company_month");
}
$checkNewUniqueKey = mysqli_query($conn, "SHOW INDEX FROM payroll_runs WHERE Key_name = 'uniq_company_month_range'");
if ($checkNewUniqueKey && mysqli_num_rows($checkNewUniqueKey) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll_runs ADD UNIQUE KEY uniq_company_month_range (company_id, month_period, start_date, end_date)");
}

$createPayrollRunDetailsTable = "CREATE TABLE IF NOT EXISTS payroll_run_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_run_id INT NOT NULL,
    employee_id INT NOT NULL,
    wage_type ENUM('monthly', 'daily') NOT NULL,
    rate DECIMAL(10, 2) NOT NULL,
    base_earnings DECIMAL(10, 2) NOT NULL,
    allowance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    present_days INT NOT NULL DEFAULT 0,
    absent_days INT NOT NULL DEFAULT 0,
    leave_days INT NOT NULL DEFAULT 0,
    deductions DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    net_pay DECIMAL(10, 2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_run_emp (payroll_run_id, employee_id),
    FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES payroll_employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createPayrollRunDetailsTable);

// Ensure the allowance column exists in payroll_run_details
$check_detail_allowance = mysqli_query($conn, "SHOW COLUMNS FROM payroll_run_details LIKE 'allowance'");
if ($check_detail_allowance && mysqli_num_rows($check_detail_allowance) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll_run_details ADD COLUMN allowance DECIMAL(10, 2) NOT NULL DEFAULT 0.00");
}

// Ensure the loan_deduction column exists in payroll_run_details
$check_detail_loan_deduction = mysqli_query($conn, "SHOW COLUMNS FROM payroll_run_details LIKE 'loan_deduction'");
if ($check_detail_loan_deduction && mysqli_num_rows($check_detail_loan_deduction) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll_run_details ADD COLUMN loan_deduction DECIMAL(10, 2) NOT NULL DEFAULT 0.00");
}

// Create loans and loan payments tables
$createLoansTable = "CREATE TABLE IF NOT EXISTS payroll_loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    type ENUM('loan', 'borrow') NOT NULL,
    contract_no VARCHAR(100) NOT NULL,
    loan_date DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    remaining_balance DECIMAL(10, 2) NOT NULL,
    total_installments INT NULL,
    monthly_deduction DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    due_date DATE NULL,
    status ENUM('active', 'paid_off') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES payroll_employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createLoansTable);

$createLoanPaymentsTable = "CREATE TABLE IF NOT EXISTS payroll_loan_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    payroll_run_id INT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES payroll_loans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createLoanPaymentsTable);


// Create master adjustments and daily adjustments tables
$createMasterAdjustmentsTable = "CREATE TABLE IF NOT EXISTS payroll_adjustment_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('allowance', 'deduction') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createMasterAdjustmentsTable);

$createAttendanceAdjustmentsTable = "CREATE TABLE IF NOT EXISTS payroll_attendance_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    work_date DATE NOT NULL,
    adjustment_item_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_emp_date_item (employee_id, work_date, adjustment_item_id),
    FOREIGN KEY (employee_id) REFERENCES payroll_employees(id) ON DELETE CASCADE,
    FOREIGN KEY (adjustment_item_id) REFERENCES payroll_adjustment_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createAttendanceAdjustmentsTable);

// Insert default adjustment items if none exist
$checkAdjustmentItems = mysqli_query($conn, "SELECT id FROM payroll_adjustment_items WHERE company_id = $company_id");
if ($checkAdjustmentItems && mysqli_num_rows($checkAdjustmentItems) == 0) {
    mysqli_query($conn, "INSERT IGNORE INTO payroll_adjustment_items (company_id, name, type) VALUES 
        ($company_id, 'ค่าน้ำมัน', 'allowance'),
        ($company_id, 'ค่าเดินทาง', 'allowance'),
        ($company_id, 'ค่าอาหาร', 'allowance'),
        ($company_id, 'เงินเพิ่มอื่นๆ', 'allowance'),
        ($company_id, 'ค่าของเสียหาย', 'deduction'),
        ($company_id, 'เงินหักอื่นๆ', 'deduction')");
}

// Insert default setting if not exist
$checkSetting = mysqli_query($conn, "SELECT id FROM payroll_settings WHERE company_id = $company_id");
if (mysqli_num_rows($checkSetting) == 0) {
    mysqli_query($conn, "INSERT IGNORE INTO payroll_settings (company_id, pay_day) VALUES ($company_id, '10')");
}

// Create commission tables
$createCommSettingsTable = "CREATE TABLE IF NOT EXISTS payroll_commission_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL UNIQUE,
    admin_rate DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    sales_rate DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    helper_rate DECIMAL(5,2) NOT NULL DEFAULT 0.50,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createCommSettingsTable);

// Ensure admin_rate column exists in payroll_commission_settings
$checkAdminRateCol = mysqli_query($conn, "SHOW COLUMNS FROM payroll_commission_settings LIKE 'admin_rate'");
if ($checkAdminRateCol && mysqli_num_rows($checkAdminRateCol) == 0) {
    mysqli_query($conn, "ALTER TABLE payroll_commission_settings ADD COLUMN admin_rate DECIMAL(5,2) NOT NULL DEFAULT 1.00 AFTER company_id");
}

// Ensure default settings exist for company
$checkCommSettings = mysqli_query($conn, "SELECT id FROM payroll_commission_settings WHERE company_id = $company_id");
if ($checkCommSettings && mysqli_num_rows($checkCommSettings) == 0) {
    mysqli_query($conn, "INSERT IGNORE INTO payroll_commission_settings (company_id, admin_rate, sales_rate, helper_rate) VALUES ($company_id, 1.00, 2.00, 0.50)");
}

$createCommissionsTable = "CREATE TABLE IF NOT EXISTS payroll_commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft', 'approved') NOT NULL DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createCommissionsTable);

$createCommissionItemsTable = "CREATE TABLE IF NOT EXISTS payroll_commission_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commission_id INT NOT NULL,
    product_code VARCHAR(100) NULL,
    product_name VARCHAR(255) NOT NULL,
    unit VARCHAR(50) NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    admin_employee_id INT NULL,
    admin_rate DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    admin_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sales_employee_id INT NOT NULL,
    sales_rate DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    sales_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    helper1_employee_id INT NULL,
    helper1_rate DECIMAL(5,2) NOT NULL DEFAULT 0.25,
    helper1_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    helper2_employee_id INT NULL,
    helper2_rate DECIMAL(5,2) NOT NULL DEFAULT 0.25,
    helper2_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    item_total_commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (commission_id) REFERENCES payroll_commissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
mysqli_query($conn, $createCommissionItemsTable);

// Ensure new commission columns exist in payroll_commission_items
$admin_cols_to_add = [
    'admin_employee_id' => "INT NULL DEFAULT NULL AFTER total_price",
    'admin_rate' => "DECIMAL(5,2) NOT NULL DEFAULT 1.00 AFTER admin_employee_id",
    'admin_commission' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER admin_rate",
    'helper1_employee_id' => "INT NULL AFTER sales_commission",
    'helper1_rate' => "DECIMAL(5,2) NOT NULL DEFAULT 0.25 AFTER helper1_employee_id",
    'helper1_commission' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER helper1_rate",
    'helper2_employee_id' => "INT NULL AFTER helper1_commission",
    'helper2_rate' => "DECIMAL(5,2) NOT NULL DEFAULT 0.25 AFTER helper2_employee_id",
    'helper2_commission' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER helper2_rate",
    'item_total_commission' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER helper2_commission"
];
foreach ($admin_cols_to_add as $col_name => $col_definition) {
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM payroll_commission_items LIKE '$col_name'");
    if ($check_col && mysqli_num_rows($check_col) == 0) {
        mysqli_query($conn, "ALTER TABLE payroll_commission_items ADD COLUMN $col_name $col_definition");
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

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
        echo json_encode($data ?: ['pay_day' => '10']);
        break;

    case 'save_settings':
        $pay_day = mysqli_real_escape_string($conn, $_POST['pay_day'] ?? '10');
        // Validate tokens
        $tokens = explode(',', $pay_day);
        $valid_tokens = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === 'L' || ((int)$token >= 1 && (int)$token <= 31)) {
                $valid_tokens[] = $token;
            }
        }
        $pay_day_saved = implode(',', $valid_tokens);
        if (empty($pay_day_saved)) {
            $pay_day_saved = '10';
        }

        $sql = "INSERT INTO payroll_settings (company_id, pay_day) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE pay_day = VALUES(pay_day)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $company_id, $pay_day_saved);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกการตั้งค่าระบบเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    // -------------------------------------------------------------
    // MASTER ADJUSTMENT ACTIONS
    // -------------------------------------------------------------
    case 'list_adjustment_items':
        $sql = "SELECT * FROM payroll_adjustment_items WHERE company_id = ? ORDER BY id ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode($items);
        break;

    case 'save_adjustment_item':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'allowance';

        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อรายการ']);
            exit();
        }

        if ($id > 0) {
            $sql = "UPDATE payroll_adjustment_items SET name = ?, type = ? WHERE id = ? AND company_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssii", $name, $type, $id, $company_id);
        } else {
            $sql = "INSERT INTO payroll_adjustment_items (company_id, name, type) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iss", $company_id, $name, $type);
        }

        header('Content-Type: application/json');
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลรายการเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'delete_adjustment_item':
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM payroll_adjustment_items WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
        header('Content-Type: application/json');
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลรายการเรียบร้อยแล้ว']);
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
            // Fetch multiple positions
            $emp_id = $row['id'];
            $pos_sql = "SELECT position, wage_type, salary FROM payroll_employee_positions WHERE employee_id = $emp_id ORDER BY id ASC";
            $pos_res = mysqli_query($conn, $pos_sql);
            $positions = [];
            while ($pos_row = mysqli_fetch_assoc($pos_res)) {
                $positions[] = $pos_row;
            }
            $row['positions'] = $positions;
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
        if ($data) {
            // Fetch multiple positions
            $pos_sql = "SELECT position, wage_type, salary FROM payroll_employee_positions WHERE employee_id = ? ORDER BY id ASC";
            $pos_stmt = mysqli_prepare($conn, $pos_sql);
            mysqli_stmt_bind_param($pos_stmt, "i", $id);
            mysqli_stmt_execute($pos_stmt);
            $pos_res = mysqli_stmt_get_result($pos_stmt);
            $positions = [];
            while ($pos_row = mysqli_fetch_assoc($pos_res)) {
                $positions[] = $pos_row;
            }
            $data['positions'] = $positions;
            echo json_encode($data);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลพนักงาน']);
        }
        break;

    case 'save_employee':
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $emp_code = $_POST['emp_code'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $department = $_POST['department'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $max_business_leave = (int)($_POST['max_business_leave'] ?? 7);
        $max_sick_leave = (int)($_POST['max_sick_leave'] ?? 30);
        $max_annual_leave = (int)($_POST['max_annual_leave'] ?? 6);
        $max_other_leave = (int)($_POST['max_other_leave'] ?? 15);
        $status = $_POST['status'] ?? 'active';
        $description = $_POST['description'] ?? '';

        // Extract first position for main payroll_employees fields (fallback compatibility)
        $positions_post = $_POST['positions'] ?? [];
        $first_pos_item = $positions_post[0] ?? [];
        $position = trim($first_pos_item['position'] ?? $_POST['position'] ?? '');
        $wage_type = $first_pos_item['wage_type'] ?? $_POST['wage_type'] ?? 'daily';
        $salary = (float)($first_pos_item['salary'] ?? $_POST['salary'] ?? 0.00);

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

        // Handling file upload
        $photo_path = null;
        if ($id > 0) {
            $old_sql = "SELECT photo FROM payroll_employees WHERE id = ? AND company_id = ?";
            $old_stmt = mysqli_prepare($conn, $old_sql);
            mysqli_stmt_bind_param($old_stmt, "ii", $id, $company_id);
            mysqli_stmt_execute($old_stmt);
            $old_res = mysqli_stmt_get_result($old_stmt);
            if ($old_row = mysqli_fetch_assoc($old_res)) {
                $photo_path = $old_row['photo'];
            }
        }

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = 'emp_' . uniqid() . '.' . $fileExtension;
                $uploadFileDir = '../uploads/employees/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                $dest_path = $uploadFileDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    if ($photo_path && file_exists('../' . $photo_path)) {
                        @unlink('../' . $photo_path);
                    }
                    $photo_path = 'uploads/employees/' . $newFileName;
                }
            }
        }

        if ($id > 0) {
            $sql = "UPDATE payroll_employees SET 
                    emp_code = ?, first_name = ?, last_name = ?, department = ?, position = ?, 
                    salary = ?, wage_type = ?, start_date = ?, phone = ?, max_business_leave = ?, max_sick_leave = ?, 
                    max_annual_leave = ?, max_other_leave = ?, status = ?, photo = ?, description = ?
                    WHERE id = ? AND company_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssdsssiiiisssii", 
                $emp_code, $first_name, $last_name, $department, $position, 
                $salary, $wage_type, $start_date, $phone, $max_business_leave, $max_sick_leave, 
                $max_annual_leave, $max_other_leave, $status, $photo_path, $description, $id, $company_id
            );
        } else {
            $sql = "INSERT INTO payroll_employees (
                        company_id, emp_code, first_name, last_name, department, position, 
                        salary, wage_type, start_date, phone, max_business_leave, max_sick_leave, 
                        max_annual_leave, max_other_leave, status, photo, description
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isssssdsssiiiisss", 
                $company_id, $emp_code, $first_name, $last_name, $department, $position, 
                $salary, $wage_type, $start_date, $phone, $max_business_leave, $max_sick_leave, 
                $max_annual_leave, $max_other_leave, $status, $photo_path, $description
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            $employee_id = ($id > 0) ? $id : mysqli_insert_id($conn);
            
            // Delete old positions and insert new ones
            mysqli_query($conn, "DELETE FROM payroll_employee_positions WHERE employee_id = $employee_id");
            
            foreach ($positions_post as $p) {
                $p_pos = trim($p['position'] ?? '');
                $p_wage = $p['wage_type'] ?? 'daily';
                $p_sal = (float)($p['salary'] ?? 0.00);
                
                if (empty($p_pos)) {
                    continue;
                }
                
                $pos_ins = "INSERT INTO payroll_employee_positions (employee_id, position, wage_type, salary) VALUES (?, ?, ?, ?)";
                $pos_stmt = mysqli_prepare($conn, $pos_ins);
                mysqli_stmt_bind_param($pos_stmt, "issd", $employee_id, $p_pos, $p_wage, $p_sal);
                mysqli_stmt_execute($pos_stmt);
            }
            
            echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลพนักงานเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'delete_employee':
        $id = (int)($_POST['id'] ?? 0);
        
        // Find photo path to delete
        $photo_sql = "SELECT photo FROM payroll_employees WHERE id = ? AND company_id = ?";
        $photo_stmt = mysqli_prepare($conn, $photo_sql);
        mysqli_stmt_bind_param($photo_stmt, "ii", $id, $company_id);
        mysqli_stmt_execute($photo_stmt);
        $photo_res = mysqli_stmt_get_result($photo_stmt);
        if ($photo_row = mysqli_fetch_assoc($photo_res)) {
            $photo_path = $photo_row['photo'];
            if ($photo_path && file_exists('../' . $photo_path)) {
                @unlink('../' . $photo_path);
            }
        }

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
        
        $sql = "SELECT e.id as employee_id, e.emp_code, e.first_name, e.last_name, e.department, e.position, e.photo, e.salary, e.wage_type,
                       a.id as attendance_id, a.work_date, a.check_in, a.check_out, a.status, a.leave_type, a.note,
                       a.position_id, a.allowance_fuel, a.allowance_travel, a.allowance_food, a.allowance_other, a.allowance_other_note,
                       a.deduction_damage, a.deduction_other, a.deduction_other_note
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
            // Fetch all available positions for this employee
            $emp_id = $row['employee_id'];
            $pos_sql = "SELECT id, position, wage_type, salary FROM payroll_employee_positions WHERE employee_id = ? ORDER BY id ASC";
            $pos_stmt = mysqli_prepare($conn, $pos_sql);
            mysqli_stmt_bind_param($pos_stmt, "i", $emp_id);
            mysqli_stmt_execute($pos_stmt);
            $pos_res = mysqli_stmt_get_result($pos_stmt);
            
            $positions = [];
            while ($pos_row = mysqli_fetch_assoc($pos_res)) {
                $positions[] = $pos_row;
            }
            $row['positions'] = $positions;

            // Fetch daily adjustments for this employee on this date
            $adj_sql = "SELECT adj.id, adj.adjustment_item_id, adj.amount, adj.note, item.name, item.type 
                        FROM payroll_attendance_adjustments adj
                        JOIN payroll_adjustment_items item ON adj.adjustment_item_id = item.id
                        WHERE adj.employee_id = ? AND adj.work_date = ?
                        ORDER BY adj.id ASC";
            $adj_stmt = mysqli_prepare($conn, $adj_sql);
            mysqli_stmt_bind_param($adj_stmt, "is", $emp_id, $work_date);
            mysqli_stmt_execute($adj_stmt);
            $adj_res = mysqli_stmt_get_result($adj_stmt);
            
            $adjustments = [];
            while ($adj_row = mysqli_fetch_assoc($adj_res)) {
                $adjustments[] = $adj_row;
            }
            $row['adjustments'] = $adjustments;

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
        $position_id = !empty($_POST['position_id']) ? (int)$_POST['position_id'] : null;
        $allowance_fuel = (float)($_POST['allowance_fuel'] ?? 0.00);
        $allowance_travel = (float)($_POST['allowance_travel'] ?? 0.00);
        $allowance_food = (float)($_POST['allowance_food'] ?? 0.00);
        $allowance_other = (float)($_POST['allowance_other'] ?? 0.00);
        $allowance_other_note = !empty($_POST['allowance_other_note']) ? $_POST['allowance_other_note'] : null;
        $deduction_damage = (float)($_POST['deduction_damage'] ?? 0.00);
        $deduction_other = (float)($_POST['deduction_other'] ?? 0.00);
        $deduction_other_note = !empty($_POST['deduction_other_note']) ? $_POST['deduction_other_note'] : null;

        if ($employee_id <= 0 || empty($work_date)) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลผู้ใช้หรือวันที่ไม่ถูกต้อง']);
            exit();
        }

        $sql = "INSERT INTO payroll_attendance (company_id, employee_id, work_date, check_in, check_out, status, leave_type, note,
                                                position_id, allowance_fuel, allowance_travel, allowance_food, allowance_other, allowance_other_note,
                                                deduction_damage, deduction_other, deduction_other_note)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                check_in = VALUES(check_in),
                check_out = VALUES(check_out),
                status = VALUES(status),
                leave_type = VALUES(leave_type),
                note = VALUES(note),
                position_id = VALUES(position_id),
                allowance_fuel = VALUES(allowance_fuel),
                allowance_travel = VALUES(allowance_travel),
                allowance_food = VALUES(allowance_food),
                allowance_other = VALUES(allowance_other),
                allowance_other_note = VALUES(allowance_other_note),
                deduction_damage = VALUES(deduction_damage),
                deduction_other = VALUES(deduction_other),
                deduction_other_note = VALUES(deduction_other_note)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissssssiddddsdds", $company_id, $employee_id, $work_date, $check_in, $check_out, $status, $leave_type, $note,
                               $position_id, $allowance_fuel, $allowance_travel, $allowance_food, $allowance_other, $allowance_other_note,
                               $deduction_damage, $deduction_other, $deduction_other_note);
        
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
            $position_id = !empty($item['position_id']) ? (int)$item['position_id'] : null;
            $allowance_fuel = (float)($item['allowance_fuel'] ?? 0.00);
            $allowance_travel = (float)($item['allowance_travel'] ?? 0.00);
            $allowance_food = (float)($item['allowance_food'] ?? 0.00);
            $allowance_other = (float)($item['allowance_other'] ?? 0.00);
            $allowance_other_note = !empty($item['allowance_other_note']) ? $item['allowance_other_note'] : null;
            $deduction_damage = (float)($item['deduction_damage'] ?? 0.00);
            $deduction_other = (float)($item['deduction_other'] ?? 0.00);
            $deduction_other_note = !empty($item['deduction_other_note']) ? $item['deduction_other_note'] : null;

            if ($employee_id > 0) {
                $sql = "INSERT INTO payroll_attendance (company_id, employee_id, work_date, check_in, check_out, status, leave_type, note,
                                                        position_id, allowance_fuel, allowance_travel, allowance_food, allowance_other, allowance_other_note,
                                                        deduction_damage, deduction_other, deduction_other_note)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        check_in = VALUES(check_in),
                        check_out = VALUES(check_out),
                        status = VALUES(status),
                        leave_type = VALUES(leave_type),
                        note = VALUES(note),
                        position_id = VALUES(position_id),
                        allowance_fuel = VALUES(allowance_fuel),
                        allowance_travel = VALUES(allowance_travel),
                        allowance_food = VALUES(allowance_food),
                        allowance_other = VALUES(allowance_other),
                        allowance_other_note = VALUES(allowance_other_note),
                        deduction_damage = VALUES(deduction_damage),
                        deduction_other = VALUES(deduction_other),
                        deduction_other_note = VALUES(deduction_other_note)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iissssssiddddsdds", $company_id, $employee_id, $work_date, $check_in, $check_out, $status, $leave_type, $note,
                                       $position_id, $allowance_fuel, $allowance_travel, $allowance_food, $allowance_other, $allowance_other_note,
                                       $deduction_damage, $deduction_other, $deduction_other_note);
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
    // DAILY ADJUSTMENT ACTIONS
    // -------------------------------------------------------------
    case 'list_daily_adjustments':
        $employee_id = (int)($_GET['employee_id'] ?? 0);
        $work_date = $_GET['work_date'] ?? '';

        $sql = "SELECT adj.id, adj.adjustment_item_id, adj.amount, adj.note, item.name, item.type 
                FROM payroll_attendance_adjustments adj
                JOIN payroll_adjustment_items item ON adj.adjustment_item_id = item.id
                WHERE adj.employee_id = ? AND adj.work_date = ?
                ORDER BY adj.id ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $employee_id, $work_date);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $adjustments = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $adjustments[] = $row;
        }
        header('Content-Type: application/json');
        echo json_encode($adjustments);
        break;

    case 'add_daily_adjustment':
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        $work_date = $_POST['work_date'] ?? '';
        $adjustment_item_id = (int)($_POST['adjustment_item_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0.00);
        $note = trim($_POST['note'] ?? '');

        if ($employee_id <= 0 || empty($work_date) || $adjustment_item_id <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง']);
            exit();
        }

        $sql = "INSERT INTO payroll_attendance_adjustments (employee_id, work_date, adjustment_item_id, amount, note)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE amount = VALUES(amount), note = VALUES(note)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isids", $employee_id, $work_date, $adjustment_item_id, $amount, $note);
        
        header('Content-Type: application/json');
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกรายการสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'delete_daily_adjustment':
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM payroll_attendance_adjustments WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        header('Content-Type: application/json');
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'ลบรายการสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
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
        $emp_sql = "SELECT id, emp_code, first_name, last_name, department, position, photo, 
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
                'photo' => $emp['photo'],
                
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

    // -------------------------------------------------------------
    // PAYROLL CALCULATIONS & SUMMARY ACTIONS
    // -------------------------------------------------------------
    case 'list_payroll_runs':
        $sql = "SELECT r.*, 
                       COUNT(d.id) as total_employees, 
                       SUM(d.net_pay) as total_net_pay 
                FROM payroll_runs r
                LEFT JOIN payroll_run_details d ON r.id = d.payroll_run_id
                WHERE r.company_id = ?
                GROUP BY r.id
                ORDER BY r.month_period DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $runs = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $runs[] = $row;
        }
        echo json_encode($runs);
        break;

    case 'get_payroll_run':
        $month = mysqli_real_escape_string($conn, $_GET['month_period'] ?? date('Y-m'));
        $recalculate = isset($_GET['recalculate']) && $_GET['recalculate'] === 'true';
        
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;
        
        // If not provided, calculate first and last days of the month
        if (empty($start_date) || empty($end_date)) {
            $start_date = $month . '-01';
            $end_date = date('Y-m-t', strtotime($start_date));
        } else {
            $start_date = mysqli_real_escape_string($conn, $start_date);
            $end_date = mysqli_real_escape_string($conn, $end_date);
        }
        
        // Check if run already exists in DB
        $run_sql = "SELECT * FROM payroll_runs 
                    WHERE company_id = ? 
                      AND (
                           (month_period = ? AND start_date = ? AND end_date = ?) OR
                           (month_period = ? AND start_date IS NULL AND end_date IS NULL)
                          )";
        $run_stmt = mysqli_prepare($conn, $run_sql);
        $run = null;
        if ($run_stmt) {
            mysqli_stmt_bind_param($run_stmt, "issss", $company_id, $month, $start_date, $end_date, $month);
            mysqli_stmt_execute($run_stmt);
            $run_res = mysqli_stmt_get_result($run_stmt);
            $run = mysqli_fetch_assoc($run_res);
        }
        
        if ($run && !$recalculate) {
            // Retrieve saved details
            $details_sql = "SELECT d.*, e.emp_code, e.first_name, e.last_name, e.department, e.position, e.photo 
                            FROM payroll_run_details d
                            JOIN payroll_employees e ON d.employee_id = e.id
                            WHERE d.payroll_run_id = ?
                            ORDER BY e.emp_code ASC";
            $details_stmt = mysqli_prepare($conn, $details_sql);
            $details = [];
            if ($details_stmt) {
                mysqli_stmt_bind_param($details_stmt, "i", $run['id']);
                mysqli_stmt_execute($details_stmt);
                $res = mysqli_stmt_get_result($details_stmt);
                while ($row = mysqli_fetch_assoc($res)) {
                    $row['name'] = $row['first_name'] . ' ' . $row['last_name'];
                    $details[] = $row;
                }
            }
            echo json_encode([
                'status' => 'saved',
                'run_id' => $run['id'],
                'month_period' => $run['month_period'],
                'start_date' => $run['start_date'] ?: $start_date,
                'end_date' => $run['end_date'] ?: $end_date,
                'run_status' => $run['status'],
                'details' => $details
            ]);
        } else {
            // Run doesn't exist, calculate dynamically
            // Fetch all active employees
            $emp_sql = "SELECT id, emp_code, first_name, last_name, department, position, salary, wage_type, photo 
                        FROM payroll_employees 
                        WHERE company_id = ? AND status = 'active'
                        ORDER BY emp_code ASC";
            $emp_stmt = mysqli_prepare($conn, $emp_sql);
            if ($emp_stmt) {
                mysqli_stmt_bind_param($emp_stmt, "i", $company_id);
                mysqli_stmt_execute($emp_stmt);
                $emp_res = mysqli_stmt_get_result($emp_stmt);
            } else {
                $emp_res = null;
            }
            
            // Get attendance details for this range
            $att_details_sql = "SELECT a.employee_id, a.work_date, a.status, a.position_id,
                                       p.position as pos_name, p.wage_type as pos_wage_type, p.salary as pos_salary
                                FROM payroll_attendance a
                                LEFT JOIN payroll_employee_positions p ON a.position_id = p.id
                                WHERE a.company_id = ? AND a.work_date BETWEEN ? AND ?";
            $att_details_stmt = mysqli_prepare($conn, $att_details_sql);
            $emp_att_map = [];
            if ($att_details_stmt) {
                mysqli_stmt_bind_param($att_details_stmt, "iss", $company_id, $start_date, $end_date);
                mysqli_stmt_execute($att_details_stmt);
                $att_details_res = mysqli_stmt_get_result($att_details_stmt);
                
                while ($att_row = mysqli_fetch_assoc($att_details_res)) {
                    $emp_id = $att_row['employee_id'];
                    if (!isset($emp_att_map[$emp_id])) {
                        $emp_att_map[$emp_id] = [];
                    }
                    $emp_att_map[$emp_id][] = $att_row;
                }
            }
            
            // Get adjustments for this range
            $adj_month_sql = "SELECT adj.employee_id, adj.amount, item.type
                              FROM payroll_attendance_adjustments adj
                              JOIN payroll_adjustment_items item ON adj.adjustment_item_id = item.id
                              WHERE adj.work_date BETWEEN ? AND ?";
            $adj_month_stmt = mysqli_prepare($conn, $adj_month_sql);
            $adj_map = [];
            if ($adj_month_stmt) {
                mysqli_stmt_bind_param($adj_month_stmt, "ss", $start_date, $end_date);
                mysqli_stmt_execute($adj_month_stmt);
                $adj_month_res = mysqli_stmt_get_result($adj_month_stmt);
                while ($adj_row = mysqli_fetch_assoc($adj_month_res)) {
                    $emp_id = $adj_row['employee_id'];
                    if (!isset($adj_map[$emp_id])) {
                        $adj_map[$emp_id] = [
                            'allowance' => 0.00,
                            'deductions' => 0.00
                        ];
                    }
                    if ($adj_row['type'] === 'allowance') {
                        $adj_map[$emp_id]['allowance'] += (float)$adj_row['amount'];
                    } else {
                        $adj_map[$emp_id]['deductions'] += (float)$adj_row['amount'];
                    }
                }
            }
            
            // Get approved commission earnings for this range
            $comm_month_sql = "SELECT 
                                    item.admin_employee_id as emp_id, 
                                    SUM(item.admin_commission) as total_comm
                               FROM payroll_commission_items item
                               JOIN payroll_commissions comm ON item.commission_id = comm.id
                               WHERE comm.company_id = ? AND comm.status = 'approved' AND item.admin_employee_id IS NOT NULL AND comm.transaction_date BETWEEN ? AND ?
                               GROUP BY item.admin_employee_id

                               UNION ALL

                               SELECT 
                                    item.sales_employee_id as emp_id, 
                                    SUM(item.sales_commission) as total_comm
                               FROM payroll_commission_items item
                               JOIN payroll_commissions comm ON item.commission_id = comm.id
                               WHERE comm.company_id = ? AND comm.status = 'approved' AND comm.transaction_date BETWEEN ? AND ?
                               GROUP BY item.sales_employee_id
                               
                               UNION ALL
                               
                               SELECT 
                                    item.helper1_employee_id as emp_id, 
                                    SUM(item.helper1_commission) as total_comm
                               FROM payroll_commission_items item
                               JOIN payroll_commissions comm ON item.commission_id = comm.id
                               WHERE comm.company_id = ? AND comm.status = 'approved' AND item.helper1_employee_id IS NOT NULL AND comm.transaction_date BETWEEN ? AND ?
                               GROUP BY item.helper1_employee_id
                               
                               UNION ALL
                               
                               SELECT 
                                    item.helper2_employee_id as emp_id, 
                                    SUM(item.helper2_commission) as total_comm
                               FROM payroll_commission_items item
                               JOIN payroll_commissions comm ON item.commission_id = comm.id
                               WHERE comm.company_id = ? AND comm.status = 'approved' AND item.helper2_employee_id IS NOT NULL AND comm.transaction_date BETWEEN ? AND ?
                               GROUP BY item.helper2_employee_id";

            $comm_stmt = mysqli_prepare($conn, $comm_month_sql);
            if ($comm_stmt) {
                mysqli_stmt_bind_param($comm_stmt, "ississississ", $company_id, $start_date, $end_date, $company_id, $start_date, $end_date, $company_id, $start_date, $end_date, $company_id, $start_date, $end_date);
                if (mysqli_stmt_execute($comm_stmt)) {
                    $comm_res = mysqli_stmt_get_result($comm_stmt);
                    while ($comm_row = mysqli_fetch_assoc($comm_res)) {
                        $emp_id = $comm_row['emp_id'];
                        if (!empty($emp_id)) {
                            if (!isset($adj_map[$emp_id])) {
                                $adj_map[$emp_id] = [
                                    'allowance' => 0.00,
                                    'deductions' => 0.00
                                ];
                            }
                            $adj_map[$emp_id]['allowance'] += (float)$comm_row['total_comm'];
                        }
                    }
                }
            }
            

            // Get active loans/borrows for this company
            $loans_sql = "SELECT id, employee_id, type, remaining_balance, monthly_deduction, contract_no
                          FROM payroll_loans 
                          WHERE company_id = ? AND status = 'active'";
            $loans_stmt = mysqli_prepare($conn, $loans_sql);
            $emp_loans_map = [];
            if ($loans_stmt) {
                mysqli_stmt_bind_param($loans_stmt, "i", $company_id);
                mysqli_stmt_execute($loans_stmt);
                $loans_res = mysqli_stmt_get_result($loans_stmt);
                while ($loan_row = mysqli_fetch_assoc($loans_res)) {
                    $l_emp_id = $loan_row['employee_id'];
                    if (!isset($emp_loans_map[$l_emp_id])) {
                        $emp_loans_map[$l_emp_id] = [];
                    }
                    $emp_loans_map[$l_emp_id][] = $loan_row;
                }
            }
            
            $details = [];
            if ($emp_res) {
                while ($emp = mysqli_fetch_assoc($emp_res)) {
                $emp_id = $emp['id'];
                $att_records = $emp_att_map[$emp_id] ?? [];
                
                $present_days = 0;
                $absent_days = 0;
                $leave_days = 0;
                
                $base_earnings = 0.00;
                $total_allowance = (float)($adj_map[$emp_id]['allowance'] ?? 0.00);
                $total_deductions = (float)($adj_map[$emp_id]['deductions'] ?? 0.00);
                
                $is_monthly = ($emp['wage_type'] === 'monthly');
                
                foreach ($att_records as $record) {
                    $status = $record['status'];
                    
                    if ($status === 'normal' || $status === 'late') {
                        $present_days++;
                        
                        // Calculate base wage for daily employees based on today's position
                        if (!$is_monthly) {
                            if (!empty($record['position_id']) && $record['pos_salary'] !== null) {
                                        $base_earnings += (float)$record['pos_salary'];
                                    } else {
                                        $base_earnings += (float)$emp['salary'];
                                    }
                        }
                    } else if ($status === 'absent') {
                        $absent_days++;
                    } else if ($status === 'leave') {
                        $leave_days++;
                    }
                }
                
                // For monthly employees, base earnings is flat monthly salary or proportional to the range
                if ($is_monthly) {
                    $is_full_month = (date('d', strtotime($start_date)) === '01' && date('d', strtotime($end_date)) === date('t', strtotime($start_date)));
                    if ($is_full_month) {
                        $base_earnings = (float)$emp['salary'];
                        // Deduct for absences: (salary / 30) * absent_days
                        $absence_deduction = round(($emp['salary'] / 30) * $absent_days, 2);
                        $total_deductions += $absence_deduction;
                    } else {
                        // Custom date range: proportional base earnings
                        $days_in_range = round((strtotime($end_date) - strtotime($start_date)) / 86400) + 1;
                        $base_earnings = round(($emp['salary'] / 30) * $days_in_range, 2);
                        // Deduct for absences within this custom range
                        $absence_deduction = round(($emp['salary'] / 30) * $absent_days, 2);
                        $total_deductions += $absence_deduction;
                    }
                }
                
                $loan_deduction = 0.00;
                $emp_loans = $emp_loans_map[$emp_id] ?? [];
                foreach ($emp_loans as $loan) {
                    $deduct = min((float)$loan['monthly_deduction'], (float)$loan['remaining_balance']);
                    $loan_deduction += $deduct;
                }

                $net_pay = $base_earnings + $total_allowance - $total_deductions - $loan_deduction;
                
                $details[] = [
                    'employee_id' => $emp_id,
                    'emp_code' => $emp['emp_code'],
                    'first_name' => $emp['first_name'],
                    'last_name' => $emp['last_name'],
                    'name' => $emp['first_name'] . ' ' . $emp['last_name'],
                    'department' => $emp['department'],
                    'position' => $emp['position'],
                    'photo' => $emp['photo'],
                    'wage_type' => $emp['wage_type'],
                    'rate' => (float)$emp['salary'],
                    'base_earnings' => $base_earnings,
                    'allowance' => $total_allowance,
                    'present_days' => $present_days,
                    'absent_days' => $absent_days,
                    'leave_days' => $leave_days,
                    'deductions' => $total_deductions,
                    'loan_deduction' => $loan_deduction,
                    'net_pay' => $net_pay
                ];
                }
            }
            
            echo json_encode([
                'status' => 'calculated',
                'month_period' => $month,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'run_status' => 'pending',
                'details' => $details
            ]);
        }
        break;
 
    case 'save_payroll_run':
        $month = mysqli_real_escape_string($conn, $_POST['month_period'] ?? '');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'pending');
        $details_json = $_POST['details'] ?? '[]';
        $details_list = json_decode($details_json, true);
        
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if (empty($month) || !is_array($details_list)) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลที่ส่งมาไม่ถูกต้อง']);
            exit();
        }
        
        if (empty($start_date) || empty($end_date)) {
            $start_date = $month . '-01';
            $end_date = date('Y-m-t', strtotime($start_date));
        } else {
            $start_date = mysqli_real_escape_string($conn, $start_date);
            $end_date = mysqli_real_escape_string($conn, $end_date);
        }
        
        mysqli_begin_transaction($conn);
        
        // Insert or update run
        $run_sql = "INSERT INTO payroll_runs (company_id, month_period, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status)";
        $run_stmt = mysqli_prepare($conn, $run_sql);
        if ($run_stmt) {
            mysqli_stmt_bind_param($run_stmt, "issss", $company_id, $month, $start_date, $end_date, $status);
            if (!mysqli_stmt_execute($run_stmt)) {
                mysqli_rollback($conn);
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
                exit();
            }
        } else {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare run_sql']);
            exit();
        }
        
        // Get run ID
        $get_id_sql = "SELECT id FROM payroll_runs WHERE company_id = ? AND month_period = ? AND start_date = ? AND end_date = ?";
        $get_id_stmt = mysqli_prepare($conn, $get_id_sql);
        if ($get_id_stmt) {
            mysqli_stmt_bind_param($get_id_stmt, "isss", $company_id, $month, $start_date, $end_date);
            mysqli_stmt_execute($get_id_stmt);
            $run_id = mysqli_fetch_assoc(mysqli_stmt_get_result($get_id_stmt))['id'];
        } else {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare get_id_sql']);
            exit();
        }
        
        // Delete existing details first (to rebuild)
        mysqli_query($conn, "DELETE FROM payroll_run_details WHERE payroll_run_id = $run_id");
        
        // Insert details
        $success = true;
        $error_msg = '';
        foreach ($details_list as $item) {
            $employee_id = (int)$item['employee_id'];
            $wage_type = $item['wage_type'];
            $rate = (float)$item['rate'];
            $base_earnings = (float)$item['base_earnings'];
            $allowance = (float)($item['allowance'] ?? 0.00);
            $present_days = (int)$item['present_days'];
            $absent_days = (int)$item['absent_days'];
            $leave_days = (int)$item['leave_days'];
            $deductions = (float)$item['deductions'];
            $loan_deduction = (float)($item['loan_deduction'] ?? 0.00);
            $net_pay = (float)$item['net_pay'];
            
            $detail_sql = "INSERT INTO payroll_run_details (
                                payroll_run_id, employee_id, wage_type, rate, base_earnings, allowance,
                                present_days, absent_days, leave_days, deductions, loan_deduction, net_pay
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $detail_stmt = mysqli_prepare($conn, $detail_sql);
            if ($detail_stmt) {
                mysqli_stmt_bind_param($detail_stmt, "iissddiiiddd", 
                    $run_id, $employee_id, $wage_type, $rate, $base_earnings, $allowance,
                    $present_days, $absent_days, $leave_days, $deductions, $loan_deduction, $net_pay
                );
                if (!mysqli_stmt_execute($detail_stmt)) {
                    $success = false;
                    $error_msg = mysqli_error($conn);
                    break;
                }
            } else {
                $success = false;
                $error_msg = "Failed to prepare detail_sql";
                break;
            }
        }
        
        if ($success && $status === 'approved') {
            // Process loan payments
            foreach ($details_list as $item) {
                $employee_id = (int)$item['employee_id'];
                $loan_ded_amt = (float)($item['loan_deduction'] ?? 0.00);
                if ($loan_ded_amt <= 0) continue;
                
                // Fetch active loans/borrows for this employee ordered by type, then date
                $l_query = mysqli_query($conn, "SELECT * FROM payroll_loans WHERE employee_id = $employee_id AND status = 'active' ORDER BY type ASC, loan_date ASC");
                if ($l_query) {
                    $remaining_to_deduct = $loan_ded_amt;
                    while ($loan = mysqli_fetch_assoc($l_query)) {
                        if ($remaining_to_deduct <= 0) break;
                        
                        $deduct = min((float)$loan['monthly_deduction'], (float)$loan['remaining_balance']);
                        $deduct = min($deduct, $remaining_to_deduct);
                        
                        if ($deduct > 0) {
                            $new_bal = (float)$loan['remaining_balance'] - $deduct;
                            $new_status = ($new_bal <= 0.005) ? 'paid_off' : 'active';
                            $new_bal = max(0.00, $new_bal);
                            
                            // Update loan
                            mysqli_query($conn, "UPDATE payroll_loans SET remaining_balance = $new_bal, status = '$new_status' WHERE id = {$loan['id']}");
                            
                            // Insert payment log
                            $note = "หักชำระผ่านเงินเดือนงวด $month";
                            mysqli_query($conn, "INSERT INTO payroll_loan_payments (loan_id, payroll_run_id, payment_date, amount, note) VALUES ({$loan['id']}, $run_id, CURDATE(), $deduct, '$note')");
                            
                            $remaining_to_deduct -= $deduct;
                        }
                    }
                }
            }
        }
        
        if ($success) {
            mysqli_commit($conn);
            echo json_encode(['status' => 'success', 'message' => 'บันทึกการคำนวณเงินเดือนเรียบร้อยแล้ว']);
        } else {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $error_msg]);
        }
        break;

    case 'get_commission_settings':
        $sql = "SELECT admin_rate, sales_rate, helper_rate FROM payroll_commission_settings WHERE company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $settings = mysqli_fetch_assoc($res);
        if (!$settings) {
            $settings = ['admin_rate' => 1.00, 'sales_rate' => 2.00, 'helper_rate' => 0.50];
        }
        echo json_encode($settings);
        break;

    case 'save_commission_settings':
        $admin_rate = (float)($_POST['admin_rate'] ?? 1.00);
        $sales_rate = (float)($_POST['sales_rate'] ?? 2.00);
        $helper_rate = (float)($_POST['helper_rate'] ?? 0.50);
        $sql = "INSERT INTO payroll_commission_settings (company_id, admin_rate, sales_rate, helper_rate) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE admin_rate = VALUES(admin_rate), sales_rate = VALUES(sales_rate), helper_rate = VALUES(helper_rate)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iddd", $company_id, $admin_rate, $sales_rate, $helper_rate);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'บันทึกอัตราค่าคอมมิชชั่นเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'get_commission_employees':
        $sql = "SELECT id, emp_code, first_name, last_name, position FROM payroll_employees WHERE company_id = ? AND status = 'active' ORDER BY emp_code ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $emps = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['name'] = $row['emp_code'] . ' | ' . $row['first_name'] . ' ' . $row['last_name'];
            $emps[] = $row;
        }
        echo json_encode($emps);
        break;

    case 'save_commission_transaction':
        $comm_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $transaction_date = mysqli_real_escape_string($conn, $_POST['transaction_date'] ?? date('Y-m-d'));
        $total_amount = (float)($_POST['total_amount'] ?? 0.00);
        $total_commission = (float)($_POST['total_commission'] ?? 0.00);
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'draft');
        $items_json = $_POST['items'] ?? '[]';
        $items = json_decode($items_json, true);

        if (!is_array($items)) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลรายการสินค้าไม่ถูกต้อง']);
            exit();
        }

        mysqli_begin_transaction($conn);
        try {
            if ($comm_id > 0) {
                // Update
                $sql = "UPDATE payroll_commissions SET transaction_date = ?, total_amount = ?, total_commission = ?, status = ? WHERE id = ? AND company_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sdddii", $transaction_date, $total_amount, $total_commission, $status, $comm_id, $company_id);
                mysqli_stmt_execute($stmt);
                
                // Delete old items
                mysqli_query($conn, "DELETE FROM payroll_commission_items WHERE commission_id = $comm_id");
            } else {
                // Insert
                $sql = "INSERT INTO payroll_commissions (company_id, transaction_date, total_amount, total_commission, status) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "issds", $company_id, $transaction_date, $total_amount, $total_commission, $status);
                mysqli_stmt_execute($stmt);
                $comm_id = mysqli_insert_id($conn);
            }

            // Insert items
            $item_sql = "INSERT INTO payroll_commission_items (
                commission_id, product_code, product_name, unit, quantity, unit_price, total_price,
                admin_employee_id, admin_rate, admin_commission,
                sales_employee_id, sales_rate, sales_commission,
                helper1_employee_id, helper1_rate, helper1_commission,
                helper2_employee_id, helper2_rate, helper2_commission,
                item_total_commission
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $item_stmt = mysqli_prepare($conn, $item_sql);

            foreach ($items as $item) {
                $p_code = !empty($item['product_code']) ? $item['product_code'] : null;
                $p_name = $item['product_name'] ?? '';
                $unit = !empty($item['unit']) ? $item['unit'] : null;
                $qty = (int)($item['quantity'] ?? 1);
                $u_price = (float)($item['unit_price'] ?? 0.00);
                $t_price = (float)($item['total_price'] ?? 0.00);
                
                $admin_emp = !empty($item['admin_employee_id']) ? (int)$item['admin_employee_id'] : null;
                $admin_rate = (float)($item['admin_rate'] ?? 1.00);
                $admin_comm = (float)($item['admin_commission'] ?? 0.00);

                $sales_emp = (int)($item['sales_employee_id'] ?? 0);
                $sales_rate = (float)($item['sales_rate'] ?? 2.00);
                $sales_comm = (float)($item['sales_commission'] ?? 0.00);
                
                $h1_emp = !empty($item['helper1_employee_id']) ? (int)$item['helper1_employee_id'] : null;
                $h1_rate = (float)($item['helper1_rate'] ?? 0.25);
                $h1_comm = (float)($item['helper1_commission'] ?? 0.00);
                
                $h2_emp = !empty($item['helper2_employee_id']) ? (int)$item['helper2_employee_id'] : null;
                $h2_rate = (float)($item['helper2_rate'] ?? 0.25);
                $h2_comm = (float)($item['helper2_commission'] ?? 0.00);
                
                $item_total_comm = (float)($item['item_total_commission'] ?? 0.00);

                mysqli_stmt_bind_param($item_stmt, "isssiddiidddiddidddd",
                    $comm_id, $p_code, $p_name, $unit, $qty, $u_price, $t_price,
                    $admin_emp, $admin_rate, $admin_comm,
                    $sales_emp, $sales_rate, $sales_comm,
                    $h1_emp, $h1_rate, $h1_comm,
                    $h2_emp, $h2_rate, $h2_comm,
                    $item_total_comm
                );
                mysqli_stmt_execute($item_stmt);
            }

            mysqli_commit($conn);
            echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลค่าคอมมิชชั่นเรียบร้อยแล้ว']);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
        break;

    case 'list_commission_transactions':
        $sql = "SELECT c.*, 
                      (SELECT COUNT(*) FROM payroll_commission_items WHERE commission_id = c.id) as total_items
               FROM payroll_commissions c
               WHERE c.company_id = ?
               ORDER BY c.transaction_date DESC, c.id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $list = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $list[] = $row;
        }
        echo json_encode($list);
        break;

    case 'get_commission_transaction':
        $comm_id = (int)($_GET['id'] ?? 0);
        // Get parent
        $sql = "SELECT * FROM payroll_commissions WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $comm_id, $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $comm = mysqli_fetch_assoc($res);
        
        if (!$comm) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล']);
            exit();
        }
        
        // Get items
        $item_sql = "SELECT * FROM payroll_commission_items WHERE commission_id = ? ORDER BY id ASC";
        $item_stmt = mysqli_prepare($conn, $item_sql);
        mysqli_stmt_bind_param($item_stmt, "i", $comm_id);
        mysqli_stmt_execute($item_stmt);
        $item_res = mysqli_stmt_get_result($item_stmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($item_res)) {
            $items[] = $row;
        }
        
        echo json_encode([
            'status' => 'success',
            'commission' => $comm,
            'items' => $items
        ]);
        break;

    case 'delete_commission_transaction':
        $comm_id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM payroll_commissions WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $comm_id, $company_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'ลบประวัติค่าคอมมิชชั่นเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'get_commission_dashboard':
        $month = mysqli_real_escape_string($conn, $_GET['month'] ?? date('Y-m'));
        $month_like = $month . '-%';
        
        $sql = "SELECT c.id as commission_id, c.transaction_date, c.status, i.*,
                e_admin.first_name as admin_name,
                e_sales.first_name as sales_name,
                e_h1.first_name as h1_name,
                e_h2.first_name as h2_name
                FROM payroll_commissions c
                JOIN payroll_commission_items i ON c.id = i.commission_id
                LEFT JOIN payroll_employees e_admin ON i.admin_employee_id = e_admin.id
                LEFT JOIN payroll_employees e_sales ON i.sales_employee_id = e_sales.id
                LEFT JOIN payroll_employees e_h1 ON i.helper1_employee_id = e_h1.id
                LEFT JOIN payroll_employees e_h2 ON i.helper2_employee_id = e_h2.id
                WHERE c.company_id = ? AND DATE_FORMAT(c.transaction_date, '%Y-%m') = ?
                ORDER BY c.transaction_date ASC, i.id ASC";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $company_id, $month);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        $data = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    // -------------------------------------------------------------
    // LOAN & BORROW MANAGEMENT ACTIONS
    // -------------------------------------------------------------
    case 'list_loans':
        $sql = "SELECT l.*, e.first_name, e.last_name, e.emp_code, e.position, e.department, e.photo
                FROM payroll_loans l
                JOIN payroll_employees e ON l.employee_id = e.id
                WHERE l.company_id = ?
                ORDER BY l.loan_date DESC, l.id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $company_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $loans = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['name'] = $row['first_name'] . ' ' . $row['last_name'];
            $loans[] = $row;
        }
        echo json_encode($loans);
        break;

    case 'get_loan':
        $loan_id = (int)($_GET['id'] ?? 0);
        $sql = "SELECT l.*, e.first_name, e.last_name, e.emp_code, e.position, e.department
                FROM payroll_loans l
                JOIN payroll_employees e ON l.employee_id = e.id
                WHERE l.id = ? AND l.company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $loan_id, $company_id);
        mysqli_stmt_execute($stmt);
        $loan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($loan) {
            $loan['name'] = $loan['first_name'] . ' ' . $loan['last_name'];
            echo json_encode($loan);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลสัญญา']);
        }
        break;

    case 'save_loan':
        $id = (int)($_POST['id'] ?? 0);
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        $type = mysqli_real_escape_string($conn, $_POST['type'] ?? 'loan');
        $contract_no = mysqli_real_escape_string($conn, $_POST['contract_no'] ?? '');
        $loan_date = mysqli_real_escape_string($conn, $_POST['loan_date'] ?? date('Y-m-d'));
        $amount = (float)($_POST['amount'] ?? 0.00);
        $monthly_deduction = (float)($_POST['monthly_deduction'] ?? 0.00);
        $total_installments = !empty($_POST['total_installments']) ? (int)$_POST['total_installments'] : null;
        $due_date = !empty($_POST['due_date']) ? mysqli_real_escape_string($conn, $_POST['due_date']) : null;
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'active');

        if ($employee_id <= 0 || empty($contract_no) || $amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน']);
            exit();
        }

        if ($id > 0) {
            // Edit existing
            // Fetch old amount/remaining to adjust balance
            $old_sql = mysqli_query($conn, "SELECT amount, remaining_balance FROM payroll_loans WHERE id = $id AND company_id = $company_id");
            $old = mysqli_fetch_assoc($old_sql);
            if ($old) {
                $diff = $amount - (float)$old['amount'];
                $new_rem = (float)$old['remaining_balance'] + $diff;
                $new_rem = max(0.00, $new_rem);
                
                $sql = "UPDATE payroll_loans SET 
                            employee_id = ?, type = ?, contract_no = ?, loan_date = ?, 
                            amount = ?, remaining_balance = ?, total_installments = ?, 
                            monthly_deduction = ?, due_date = ?, status = ?
                        WHERE id = ? AND company_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "isssddidssii", 
                    $employee_id, $type, $contract_no, $loan_date, 
                    $amount, $new_rem, $total_installments, 
                    $monthly_deduction, $due_date, $status, $id, $company_id
                );
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลสัญญาเรียบร้อยแล้ว']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลสัญญา']);
            }
        } else {
            // Create new
            $remaining_balance = $amount;
            $sql = "INSERT INTO payroll_loans (
                        company_id, employee_id, type, contract_no, loan_date, 
                        amount, remaining_balance, total_installments, 
                        monthly_deduction, due_date, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iisssddidss", 
                $company_id, $employee_id, $type, $contract_no, $loan_date, 
                $amount, $remaining_balance, $total_installments, 
                $monthly_deduction, $due_date, $status
            );
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['status' => 'success', 'message' => 'สร้างสัญญาเงินกู้/ยืมเรียบร้อยแล้ว']);
            } else {
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            }
        }
        break;

    case 'delete_loan':
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM payroll_loans WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลสัญญาเงินกู้/ยืมเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    case 'list_loan_payments':
        $loan_id = (int)($_GET['loan_id'] ?? 0);
        $sql = "SELECT p.*, r.month_period
                FROM payroll_loan_payments p
                LEFT JOIN payroll_runs r ON p.payroll_run_id = r.id
                WHERE p.loan_id = ?
                ORDER BY p.payment_date DESC, p.id DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $loan_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $payments = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $payments[] = $row;
        }
        echo json_encode($payments);
        break;

    case 'save_loan_payment':
        $loan_id = (int)($_POST['loan_id'] ?? 0);
        $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date'] ?? date('Y-m-d'));
        $amount = (float)($_POST['amount'] ?? 0.00);
        $note = mysqli_real_escape_string($conn, $_POST['note'] ?? 'ชำระเป็นเงินสด');

        if ($loan_id <= 0 || $amount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุจำนวนเงินกู้ยืมที่ถูกต้อง']);
            exit();
        }

        mysqli_begin_transaction($conn);

        // Fetch loan to update remaining balance
        $l_query = mysqli_query($conn, "SELECT remaining_balance, status FROM payroll_loans WHERE id = $loan_id AND company_id = $company_id");
        $loan = mysqli_fetch_assoc($l_query);
        if ($loan) {
            $new_rem = (float)$loan['remaining_balance'] - $amount;
            $new_status = ($new_rem <= 0.005) ? 'paid_off' : 'active';
            $new_rem = max(0.00, $new_rem);

            // Update loan remaining balance and status
            $up_sql = "UPDATE payroll_loans SET remaining_balance = ?, status = ? WHERE id = ?";
            $up_stmt = mysqli_prepare($conn, $up_sql);
            mysqli_stmt_bind_param($up_stmt, "dsi", $new_rem, $new_status, $loan_id);
            
            // Insert payment log
            $pay_sql = "INSERT INTO payroll_loan_payments (loan_id, payment_date, amount, note) VALUES (?, ?, ?, ?)";
            $pay_stmt = mysqli_prepare($conn, $pay_sql);
            mysqli_stmt_bind_param($pay_stmt, "isds", $loan_id, $payment_date, $amount, $note);

            if (mysqli_stmt_execute($up_stmt) && mysqli_stmt_execute($pay_stmt)) {
                mysqli_commit($conn);
                echo json_encode(['status' => 'success', 'message' => 'บันทึกการชำระเงินเรียบร้อยแล้ว']);
            } else {
                mysqli_rollback($conn);
                echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            }
        } else {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลสัญญา']);
        }
        break;

    case 'delete_loan_payment':
        $id = (int)($_POST['id'] ?? 0);
        
        mysqli_begin_transaction($conn);
        
        // Fetch payment details first
        $p_query = mysqli_query($conn, "SELECT loan_id, amount FROM payroll_loan_payments WHERE id = $id");
        $payment = mysqli_fetch_assoc($p_query);
        if ($payment) {
            $loan_id = $payment['loan_id'];
            $amount = (float)$payment['amount'];
            
            // Revert remaining balance on loan
            $l_query = mysqli_query($conn, "SELECT remaining_balance, status FROM payroll_loans WHERE id = $loan_id");
            $loan = mysqli_fetch_assoc($l_query);
            if ($loan) {
                $new_rem = (float)$loan['remaining_balance'] + $amount;
                $new_status = 'active'; // Since we reverted, it's active again
                
                $up_sql = "UPDATE payroll_loans SET remaining_balance = ?, status = ? WHERE id = ?";
                $up_stmt = mysqli_prepare($conn, $up_sql);
                mysqli_stmt_bind_param($up_stmt, "dsi", $new_rem, $new_status, $loan_id);
                
                // Delete payment record
                $del_sql = "DELETE FROM payroll_loan_payments WHERE id = ?";
                $del_stmt = mysqli_prepare($conn, $del_sql);
                mysqli_stmt_bind_param($del_stmt, "i", $id);
                
                if (mysqli_stmt_execute($up_stmt) && mysqli_stmt_execute($del_stmt)) {
                    mysqli_commit($conn);
                    echo json_encode(['status' => 'success', 'message' => 'ยกเลิกรายการชำระเงินเรียบร้อยแล้ว']);
                } else {
                    mysqli_rollback($conn);
                    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
                }
            } else {
                mysqli_rollback($conn);
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลสัญญาที่เกี่ยวข้อง']);
            }
        } else {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลการชำระเงิน']);
        }
        break;

    case 'delete_payroll_run':
        $id = (int)($_POST['id'] ?? 0);
        $sql = "DELETE FROM payroll_runs WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลรอบบัญชีเรียบร้อยแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'การดำเนินการไม่ถูกต้อง']);
        break;
}
?>

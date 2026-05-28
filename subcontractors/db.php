<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$configPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
if (file_exists($configPath)) {
    include $configPath;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Config file not found']);
    exit;
}

if (!isset($conn) || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Self-Migration: Check if subcontractors table exists, if not, create all required tables
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'subcontractors'");
if (mysqli_num_rows($checkTable) == 0) {
    // Create subcontractors table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS subcontractors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        team_type VARCHAR(100),
        status VARCHAR(50) DEFAULT 'กำลังทำงาน',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Add detail columns to projects_list if they don't exist
    $columns_to_add = [
        'project_code' => "VARCHAR(50) DEFAULT NULL",
        'project_type' => "VARCHAR(255) DEFAULT NULL",
        'customer_name' => "VARCHAR(255) DEFAULT NULL",
        'customer_phone' => "VARCHAR(50) DEFAULT NULL",
        'customer_email' => "VARCHAR(100) DEFAULT NULL",
        'address' => "TEXT DEFAULT NULL",
        'start_date' => "DATE DEFAULT NULL",
        'end_date' => "DATE DEFAULT NULL",
        'actual_end_date' => "DATE DEFAULT NULL",
        'status' => "VARCHAR(50) DEFAULT 'กำลังดำเนินการ'",
        'project_manager' => "VARCHAR(100) DEFAULT NULL",
        'budget' => "DECIMAL(15, 2) DEFAULT 0.00",
        'contract_value' => "DECIMAL(15, 2) DEFAULT 0.00",
        'main_subcontractor_id' => "INT DEFAULT NULL"
    ];

    foreach ($columns_to_add as $colName => $colType) {
        $check_col = mysqli_query($conn, "SHOW COLUMNS FROM projects_list LIKE '$colName'");
        if (mysqli_num_rows($check_col) == 0) {
            mysqli_query($conn, "ALTER TABLE projects_list ADD COLUMN $colName $colType");
        }
    }

    // Create project_installments table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS project_installments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        installment_number INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        due_date DATE DEFAULT NULL,
        amount DECIMAL(15, 2) DEFAULT 0.00,
        paid_amount DECIMAL(15, 2) DEFAULT 0.00,
        status VARCHAR(50) DEFAULT 'รอจ่าย',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create subcontractor_payments table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS subcontractor_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        payment_number VARCHAR(50) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method VARCHAR(50) DEFAULT NULL,
        bank_account VARCHAR(100) DEFAULT NULL,
        subcontractor_id INT NOT NULL,
        project_id INT NOT NULL,
        installment_id INT DEFAULT 0,
        total_amount DECIMAL(15, 2) DEFAULT 0.00,
        additional_amount DECIMAL(15, 2) DEFAULT 0.00,
        deduct_tax DECIMAL(15, 2) DEFAULT 0.00,
        deduct_retention DECIMAL(15, 2) DEFAULT 0.00,
        net_amount DECIMAL(15, 2) DEFAULT 0.00,
        note TEXT DEFAULT NULL,
        attachment VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (company_id),
        INDEX (subcontractor_id),
        INDEX (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create project_additional_expenses table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS project_additional_expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        project_id INT NOT NULL,
        expense_date DATE NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        expense_type VARCHAR(100) NOT NULL,
        reference_task VARCHAR(255) DEFAULT NULL,
        amount DECIMAL(15, 2) DEFAULT 0.00,
        status VARCHAR(50) DEFAULT 'อนุมัติแล้ว',
        note TEXT DEFAULT NULL,
        attachment VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (company_id),
        INDEX (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create payment_items table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payment_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payment_id INT NOT NULL,
        item_type VARCHAR(100) NOT NULL,
        details TEXT DEFAULT NULL,
        amount DECIMAL(15, 2) DEFAULT 0.00,
        INDEX (payment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Seed sample subcontractors
    $subcontractors = [
        ['ช่างแดง', '081-234-5678', 'ทีมโครงสร้าง', 'กำลังทำงาน'],
        ['ช่างเอก', '062-345-6789', 'ทีมไม้', 'กำลังทำงาน'],
        ['ช่างบอย', '089-111-2233', 'ทีมสี/ตกแต่ง', 'กำลังทำงาน'],
        ['ช่างตั้ม', '090-222-3333', 'ทีมไฟฟ้า', 'กำลังทำงาน'],
        ['ช่างหนึ่ง', '091-333-4444', 'ทีมปูน/ก่อฉาบ', 'กำลังทำงาน'],
        ['ช่างโอ๋', '093-444-5555', 'ทีมกระเบื้อง', 'รอเริ่มงาน'],
        ['ช่างสมชาย', '094-555-6666', 'ทีมหลังคา', 'หยุดงานชั่วคราว'],
        ['ช่างวุฒิ', '095-666-7777', 'ทีมงานระบบ', 'เสร็จสิ้น'],
        ['ช่างอาร์ม', '096-777-8888', 'ทีมอลูมิเนียม', 'รอเริ่มงาน'],
        ['ช่างชาติ', '097-888-9999', 'ทีมสแตนเลส', 'เสร็จสิ้น']
    ];

    foreach ($subcontractors as $sub) {
        $name = mysqli_real_escape_string($conn, $sub[0]);
        $phone = mysqli_real_escape_string($conn, $sub[1]);
        $team = mysqli_real_escape_string($conn, $sub[2]);
        $status = mysqli_real_escape_string($conn, $sub[3]);
        mysqli_query($conn, "INSERT INTO subcontractors (company_id, name, phone, team_type, status) VALUES (1, '$name', '$phone', '$team', '$status')");
    }

    // Seed sample project installments if there are projects in projects_list
    $check_proj = mysqli_query($conn, "SELECT id FROM projects_list WHERE module_type = 1 LIMIT 1");
    if (mysqli_num_rows($check_proj) > 0) {
        $proj = mysqli_fetch_assoc($check_proj);
        $pid = $proj['id'];
        
        // Update this project to have some sample detail values
        mysqli_query($conn, "UPDATE projects_list SET 
            project_code = 'PJ-67001',
            project_type = 'โครงการบ้านเดี่ยว 2 ชั้น',
            customer_name = 'คุณวิชิต พงษ์สวัสดิ์',
            customer_phone = '081-234-5678',
            customer_email = 'wichit@email.com',
            address = '99/9 หมู่ 3 ต.บางรักน้อย อ.เมืองนนทบุรี จ.นนทบุรี 11000',
            start_date = '2026-04-01',
            end_date = '2026-09-30',
            status = 'กำลังดำเนินการ',
            project_manager = 'แอดมิน',
            budget = 750000.00,
            contract_value = 750000.00,
            main_subcontractor_id = 1
            WHERE id = $pid");
        
        $installments = [
            [1, 'งวดที่ 1 : มัดจำ เซ็นสัญญา / เปิดตัวห้องเป่า', '2026-04-01', 100000.00, 100000.00, 'จ่ายแล้ว'],
            [2, 'งวดที่ 2 : งานโครงสร้าง งานฐานราก - เสา - คาน - พื้น', '2026-04-20', 200000.00, 200000.00, 'จ่ายแล้ว'],
            [3, 'งวดที่ 3 : งานก่อผนัง ก่ออิฐ - ฉาบปูน', '2026-05-15', 150000.00, 100000.00, 'บางส่วน'],
            [4, 'งวดที่ 4 : งานหลังคา โครงหลังคา - มุงหลังคา', '2026-06-15', 100000.00, 70000.00, 'บางส่วน'],
            [5, 'งวดที่ 5 : งานระบบ ระบบไฟฟ้า - ประปา', '2026-07-15', 100000.00, 50000.00, 'รอจ่าย'],
            [6, 'งวดที่ 6 : งานเก็บรายละเอียด เก็บงาน - ทำความสะอาด', '2026-08-15', 80000.00, 0.00, 'รอจ่าย'],
            [7, 'งวดที่ 7 : ส่งมอบงาน ส่งมอบงานทั้งหมด', '2026-09-30', 20000.00, 0.00, 'รอจ่าย']
        ];
        
        foreach ($installments as $inst) {
            $num = $inst[0];
            $name = mysqli_real_escape_string($conn, $inst[1]);
            $due = $inst[2];
            $amt = $inst[3];
            $paid = $inst[4];
            $status = mysqli_real_escape_string($conn, $inst[5]);
            
            mysqli_query($conn, "INSERT INTO project_installments (project_id, installment_number, name, due_date, amount, paid_amount, status)
                        VALUES ($pid, $num, '$name', '$due', $amt, $paid, '$status')");
        }
    }
}
?>

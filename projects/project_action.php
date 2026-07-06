<?php
session_start();
ob_start();
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

$action = $_REQUEST['action'] ?? '';
$company_id = $_SESSION['company_id'] ?? 0;

// Create projects_list table if not exists
$createTableSQL = "CREATE TABLE IF NOT EXISTS projects_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    note TEXT,
    project_value DECIMAL(15,2) DEFAULT 0,
    module_type INT NOT NULL COMMENT '1=Project Module, 2=Company Module',
    company_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if (!mysqli_query($conn, $createTableSQL)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Table creation failed: ' . mysqli_error($conn)]);
    exit;
}

if ($action == 'list') {
    $module_type = (int)$_GET['module_type'];
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    
    $where = "WHERE module_type = $module_type AND company_id = $company_id";
    if ($search != '') {
        $where .= " AND (project_name LIKE '%$search%' OR note LIKE '%$search%')";
    }
    
    $sql = "SELECT * FROM projects_list $where ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    
    $projects = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $projects[] = $row;
    }
    
    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $projects]);
    exit;
}

if ($action == 'save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $project_value = isset($_POST['project_value']) ? (float)$_POST['project_value'] : 0;
    $module_type = (int)$_POST['module_type'];

    if (empty($project_name)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อโครงการ']);
        exit;
    }

    if ($id > 0) {
        $sql = "UPDATE projects_list SET project_name = '$project_name', note = '$note', project_value = $project_value WHERE id = $id AND company_id = $company_id";
    } else {
        $sql = "INSERT INTO projects_list (project_name, note, project_value, module_type, company_id) VALUES ('$project_name', '$note', $project_value, $module_type, $company_id)";
    }

    ob_clean();
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'delete') {
    $id = (int)$_POST['id'];
    
    // Check if project is used in transactions
    $checkSQL = "SELECT COUNT(*) as count FROM transactions WHERE project_id = $id";
    $checkRes = mysqli_query($conn, $checkSQL);
    $checkData = mysqli_fetch_assoc($checkRes);
    
    if ($checkData['count'] > 0) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากโครงการนี้ถูกใช้งานอยู่ในรายการบันทึก']);
        exit;
    }

    $sql = "DELETE FROM projects_list WHERE id = $id AND company_id = $company_id";
    ob_clean();
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}
?>

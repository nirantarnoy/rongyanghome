<?php
ob_start();
$configPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
if (file_exists($configPath)) {
    include $configPath;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Config file not found at ' . $configPath]);
    exit;
}

if (!isset($conn) || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// Create categories table if not exists
$createTableSQL = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    direction ENUM('income', 'expense') NOT NULL,
    module_type INT NOT NULL COMMENT '1=Project, 2=Company',
    icon VARCHAR(50) DEFAULT '📁',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($conn, $createTableSQL)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Table creation failed: ' . mysqli_error($conn)]);
    exit;
}

// Ensure table uses utf8mb4
mysqli_query($conn, "ALTER TABLE categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if ($action == 'list') {
    $module_type = (int)$_GET['module_type'];
    
    $sql = "SELECT * FROM categories WHERE module_type = $module_type ORDER BY id ASC";
    $result = mysqli_query($conn, $sql);
    
    $income = [];
    $expense = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['direction'] == 'income') {
            $income[] = $row;
        } else {
            $expense[] = $row;
        }
    }
    
    ob_clean();
    echo json_encode(['income' => $income, 'expense' => $expense]);
    exit;
}

if ($action == 'save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $direction = isset($_POST['direction']) ? mysqli_real_escape_string($conn, $_POST['direction']) : '';
    $module_type = isset($_POST['module_type']) ? (int)$_POST['module_type'] : 0;
    $icon = isset($_POST['icon']) && $_POST['icon'] != '' ? mysqli_real_escape_string($conn, $_POST['icon']) : '📁';

    if (empty($name) || empty($direction) || $module_type == 0) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
        exit;
    }

    if ($id > 0) {
        $sql = "UPDATE categories SET name = '$name', direction = '$direction', icon = '$icon' WHERE id = $id";
    } else {
        $sql = "INSERT INTO categories (name, direction, module_type, icon) VALUES ('$name', '$direction', $module_type, '$icon')";
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
    
    // Check if category is used in transactions
    $checkSQL = "SELECT COUNT(*) as count FROM transactions WHERE category_id = $id";
    $checkRes = mysqli_query($conn, $checkSQL);
    $checkData = mysqli_fetch_assoc($checkRes);
    
    if ($checkData['count'] > 0) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากหมวดหมู่นี้ถูกใช้งานอยู่ในรายการบันทึก']);
        exit;
    }

    $sql = "DELETE FROM categories WHERE id = $id";
    ob_clean();
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}
?>

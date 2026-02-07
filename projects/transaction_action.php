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

// Create transactions table if not exists
$createTableSQL = "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    category_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    note TEXT,
    module_type INT NOT NULL COMMENT '1=Project Module, 2=Company Module',
    company_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($conn, $createTableSQL)) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Table creation failed: ' . mysqli_error($conn)]);
    exit;
}
mysqli_query($conn, "ALTER TABLE transactions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if ($action == 'list') {
    $module_type = (int)$_GET['module_type'];
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    
    $where = "WHERE t.module_type = $module_type AND t.company_id = $company_id";
    if ($search != '') {
        $where .= " AND (p.project_name LIKE '%$search%' OR c.name LIKE '%$search%' OR t.note LIKE '%$search%')";
    }
    
    $sql = "SELECT t.*, p.project_name, c.name as category_name, c.direction, c.icon as category_icon 
            FROM transactions t
            LEFT JOIN projects_list p ON t.project_id = p.id
            LEFT JOIN categories c ON t.category_id = c.id
            $where 
            ORDER BY t.transaction_date DESC, t.id DESC";
            
    $result = mysqli_query($conn, $sql);
    
    $transactions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    
    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $transactions]);
    exit;
}

if ($action == 'save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $project_id = (int)$_POST['project_id'];
    $category_id = (int)$_POST['category_id'];
    $transaction_date = mysqli_real_escape_string($conn, $_POST['transaction_date']);
    $amount = (float)$_POST['amount'];
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $module_type = (int)$_POST['module_type'];

    if ($project_id == 0 || $category_id == 0 || empty($transaction_date) || $amount <= 0) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    if ($id > 0) {
        $sql = "UPDATE transactions SET 
                project_id = $project_id, 
                category_id = $category_id, 
                transaction_date = '$transaction_date', 
                amount = $amount, 
                note = '$note' 
                WHERE id = $id AND company_id = $company_id";
    } else {
        $sql = "INSERT INTO transactions (project_id, category_id, transaction_date, amount, note, module_type, company_id) 
                VALUES ($project_id, $category_id, '$transaction_date', $amount, '$note', $module_type, $company_id)";
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
    $sql = "DELETE FROM transactions WHERE id = $id AND company_id = $company_id";
    ob_clean();
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'get_form_data') {
    $module_type = (int)$_GET['module_type'];
    
    // Get projects
    $p_sql = "SELECT id, project_name FROM projects_list WHERE module_type = $module_type AND company_id = $company_id ORDER BY project_name ASC";
    $p_res = mysqli_query($conn, $p_sql);
    $projects = [];
    while($row = mysqli_fetch_assoc($p_res)) $projects[] = $row;
    
    // Get categories
    $c_sql = "SELECT id, name, direction, icon FROM categories WHERE module_type = $module_type ORDER BY direction DESC, sort_order ASC, name ASC";
    $c_res = mysqli_query($conn, $c_sql);
    $categories = [];
    while($row = mysqli_fetch_assoc($c_res)) $categories[] = $row;
    
    ob_clean();
    echo json_encode(['projects' => $projects, 'categories' => $categories]);
    exit;
}

if ($action == 'get_dashboard_stats') {
    $module_type = (int)$_GET['module_type'];
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    
    $where = "WHERE t.module_type = $module_type AND t.company_id = $company_id";
    if ($project_id > 0) {
        $where .= " AND t.project_id = $project_id";
    }
    
    $sql = "SELECT 
                SUM(CASE WHEN c.direction = 'income' THEN t.amount ELSE 0 END) as total_income,
                SUM(CASE WHEN c.direction = 'expense' THEN t.amount ELSE 0 END) as total_expense,
                COUNT(t.id) as total_count
            FROM transactions t
            LEFT JOIN categories c ON t.category_id = c.id
            $where";
            
    $result = mysqli_query($conn, $sql);
    $stats = mysqli_fetch_assoc($result);
    
    $stats['total_income'] = (float)($stats['total_income'] ?? 0);
    $stats['total_expense'] = (float)($stats['total_expense'] ?? 0);
    $stats['total_profit'] = $stats['total_income'] - $stats['total_expense'];
    $stats['total_count'] = (int)$stats['total_count'];
    
    // Get summary by category
    $cat_sql = "SELECT c.name, c.direction, c.icon, SUM(t.amount) as total
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                $where
                GROUP BY t.category_id
                ORDER BY c.sort_order ASC, c.name ASC";
    $cat_res = mysqli_query($conn, $cat_sql);
    $categories_summary = ['income' => [], 'expense' => []];
    while($row = mysqli_fetch_assoc($cat_res)) {
        $row['total'] = (float)$row['total'];
        $categories_summary[$row['direction']][] = $row;
    }
    $stats['categories_summary'] = $categories_summary;

    // Get stats by monthly
    $month_sql = "SELECT DATE_FORMAT(t.transaction_date, '%Y-%m') as month,
                         SUM(CASE WHEN c.direction = 'income' THEN t.amount ELSE 0 END) as income,
                         SUM(CASE WHEN c.direction = 'expense' THEN t.amount ELSE 0 END) as expense
                  FROM transactions t
                  JOIN categories c ON t.category_id = c.id
                  $where
                  GROUP BY month
                  ORDER BY month ASC";
    $month_res = mysqli_query($conn, $month_sql);
    $monthly_stats = [];
    while($row = mysqli_fetch_assoc($month_res)) {
        $row['income'] = (float)$row['income'];
        $row['expense'] = (float)$row['expense'];
        $monthly_stats[] = $row;
    }
    $stats['monthly_stats'] = $monthly_stats;

    // Get stats by project comparison
    $proj_sql = "SELECT p.project_name, 
                        SUM(CASE WHEN c.direction = 'income' THEN t.amount ELSE 0 END) as income,
                        SUM(CASE WHEN c.direction = 'expense' THEN t.amount ELSE 0 END) as expense
                 FROM transactions t
                 JOIN projects_list p ON t.project_id = p.id
                 JOIN categories c ON t.category_id = c.id
                 $where
                 GROUP BY t.project_id
                 ORDER BY income DESC";
    $proj_res = mysqli_query($conn, $proj_sql);
    $project_comparison = [];
    while($row = mysqli_fetch_assoc($proj_res)) {
        $row['income'] = (float)$row['income'];
        $row['expense'] = (float)$row['expense'];
        $row['profit'] = $row['income'] - $row['expense'];
        $project_comparison[] = $row;
    }
    $stats['project_comparison'] = $project_comparison;

    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $stats]);
    exit;
}
?>

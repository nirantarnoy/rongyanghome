<?php
include 'config.php';

$action = $_REQUEST['action'] ?? '';

// Create table if not exists
$createUserTableSQL = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'user') DEFAULT 'user',
    company_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createUserTableSQL);
mysqli_query($conn, "ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// Add allowed_modules column if not exists
$checkColumnSQL = "SHOW COLUMNS FROM users LIKE 'allowed_modules'";
$columnExists = mysqli_query($conn, $checkColumnSQL);
if (mysqli_num_rows($columnExists) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN allowed_modules TEXT");
}


if ($action == 'list') {
    $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
    $perPage = $_GET['perPage'] ?? 20;
    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) $page = 1;

    $where = "WHERE 1=1";
    if ($search != '') {
        $where .= " AND (u.username LIKE '%$search%' OR u.full_name LIKE '%$search%' OR c.company_name LIKE '%$search%')";
    }

    // Count total rows
    $countSql = "SELECT COUNT(*) as total FROM users u LEFT JOIN company c ON u.company_id = c.id $where";
    $countRes = mysqli_query($conn, $countSql);
    $totalRows = mysqli_fetch_assoc($countRes)['total'];

    if ($perPage == 'all') {
        $limit = "";
        $totalPages = 1;
    } else {
        $perPage = (int)$perPage;
        $totalPages = ceil($totalRows / $perPage);
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
        $offset = ($page - 1) * $perPage;
        $limit = "LIMIT $offset, $perPage";
    }

    $sql = "SELECT u.*, c.company_name 
            FROM users u 
            LEFT JOIN company c ON u.company_id = c.id 
            $where ORDER BY u.id DESC $limit";
    $result = mysqli_query($conn, $sql);

    $html = '';
    $i = ($perPage == 'all') ? 1 : ($page - 1) * $perPage + 1;
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $roleBadge = $row['role'] == 'admin' 
                ? "<span class='px-2 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full'>Admin</span>"
                : "<span class='px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full'>User</span>";

            $modules = $row['allowed_modules'] ? explode(',', $row['allowed_modules']) : [];
            $moduleBadges = '';
            foreach ($modules as $mod) {
                $color = 'bg-gray-100 text-gray-600';
                if ($mod == 'admin') $color = 'bg-purple-100 text-purple-700';
                if ($mod == 'stock') $color = 'bg-indigo-100 text-indigo-700';
                if ($mod == 'projects') $color = 'bg-amber-100 text-amber-700';
                if ($mod == 'companytransaction') $color = 'bg-green-100 text-green-700';
                if ($mod == 'payroll') $color = 'bg-sky-100 text-sky-700';
                $moduleBadges .= "<span class='px-2 py-0.5 $color text-[10px] font-bold rounded-md mr-1 uppercase'>$mod</span>";
            }

            $html .= "
            <tr class='hover:bg-gray-50/80 transition-colors group'>
                <td class='px-6 py-4 text-sm text-gray-500'>$i</td>
                <td class='px-6 py-4'>
                    <div class='text-sm font-bold text-gray-900'>{$row['username']}</div>
                    <div class='text-xs text-gray-400 mt-0.5'>ID: #{$row['id']}</div>
                </td>
                <td class='px-6 py-4 text-sm text-gray-600 font-medium'>".($row['full_name'] ?: '-')."</td>
                <td class='px-6 py-4 text-sm text-gray-600'>".($row['company_name'] ?: "<span class='text-gray-300'>ไม่ได้ระบุ</span>")."</td>
                <td class='px-6 py-4'>
                    <div class='mb-1'>$roleBadge</div>
                    <div class='flex flex-wrap gap-y-1'>$moduleBadges</div>
                </td>
                <td class='px-6 py-4 text-right space-x-2'>
                    <button onclick='openModal(\"edit\", {$row['id']})' 
                            class='inline-flex items-center p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all' title='แก้ไข'>
                        <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'></path>
                        </svg>
                    </button>
                    <button onclick='resetPassword({$row['id']}, \"{$row['username']}\")' 
                            class='inline-flex items-center p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-all' title='รีเซ็ตรหัสผ่าน'>
                        <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'></path>
                        </svg>
                    </button>
                    <button onclick='deleteUser({$row['id']})' 
                            class='inline-flex items-center p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all' title='ลบ'>
                        <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path>
                        </svg>
                    </button>
                </td>
            </tr>";
            $i++;
        }
    } else {
        $html = "<tr><td colspan='6' class='px-6 py-12 text-center text-gray-400'>
            <div class='flex flex-col items-center'>
                <svg class='w-12 h-12 mb-3 opacity-20' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'></path>
                </svg>
                ไม่พบข้อมูลผู้ใช้งาน
            </div>
        </td></tr>";
    }

    // Pagination HTML
    $pagination = "<div class='text-sm text-gray-500'>แสดงทั้งหมด <b>$totalRows</b> รายการ</div>";
    if ($perPage != 'all' && $totalPages > 1) {
        $pagination .= "<div class='flex gap-1'>";
        for ($p = 1; $p <= $totalPages; $p++) {
            $activeClass = ($p == $page) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200';
            $pagination .= "<button onclick='loadData($p)' class='w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-all $activeClass'>$p</button>";
        }
        $pagination .= "</div>";
    }

    echo json_encode(['html' => $html, 'pagination' => $pagination]);
}

if ($action == 'get') {
    $id = (int)$_GET['id'];
    $sql = "SELECT id, username, full_name, role, company_id, allowed_modules FROM users WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    echo json_encode(mysqli_fetch_assoc($result));
}

if ($action == 'create') {
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name'] ?? '');
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
    $company_id_raw = $_POST['company_id'] ?? '';
    $allowed_modules = mysqli_real_escape_string($conn, $_POST['allowed_modules'] ?? '');

    if (empty($username) || empty($password_raw) || empty($full_name) || empty($role) || empty($company_id_raw)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน']);
        exit;
    }

    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $company_id = (int)$company_id_raw;

    // Check for duplicate username
    $checkSql = "SELECT id FROM users WHERE username = '$username'";
    $checkRes = mysqli_query($conn, $checkSql);
    if (mysqli_num_rows($checkRes) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว']);
        exit;
    }

    $sql = "INSERT INTO users (username, password, full_name, role, company_id, allowed_modules) 
            VALUES ('$username', '$password', '$full_name', '$role', $company_id, '$allowed_modules')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}

if ($action == 'update') {
    $id = (int)$_POST['id'];
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name'] ?? '');
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
    $company_id_raw = $_POST['company_id'] ?? '';
    $allowed_modules = mysqli_real_escape_string($conn, $_POST['allowed_modules'] ?? '');

    if (empty($username) || empty($full_name) || empty($role) || empty($company_id_raw)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน']);
        exit;
    }

    $company_id = (int)$company_id_raw;

    // Check for duplicate username (excluding current record)
    $checkSql = "SELECT id FROM users WHERE username = '$username' AND id != $id";
    $checkRes = mysqli_query($conn, $checkSql);
    if (mysqli_num_rows($checkRes) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว']);
        exit;
    }

    $passwordUpdate = "";
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $passwordUpdate = ", password = '$password'";
    }

    $sql = "UPDATE users SET 
            username = '$username', 
            full_name = '$full_name', 
            role = '$role', 
            company_id = $company_id,
            allowed_modules = '$allowed_modules'
            $passwordUpdate
            WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลผู้ใช้งานเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}

if ($action == 'delete') {
    $id = (int)$_POST['id'];
    $sql = "DELETE FROM users WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบผู้ใช้งานเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}

if ($action == 'reset_password') {
    $id = (int)$_POST['id'];
    $default_password = '123456';
    $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
    
    $sql = "UPDATE users SET password = '$password_hash' WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'รีเซ็ตรหัสผ่านเป็น 123456 เรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}
?>

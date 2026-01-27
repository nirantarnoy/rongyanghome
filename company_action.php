<?php
include 'config.php';

$action = $_REQUEST['action'] ?? '';

if ($action == 'list') {
    $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
    $perPage = $_GET['perPage'] ?? 20;
    $page = (int)($_GET['page'] ?? 1);
    if ($page < 1) $page = 1;

    $where = "WHERE 1=1";
    if ($search != '') {
        $where .= " AND (company_name LIKE '%$search%' OR tax_id LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%')";
    }

    // Count total rows
    $countSql = "SELECT COUNT(*) as total FROM company $where";
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

    $sql = "SELECT * FROM company $where ORDER BY id DESC $limit";
    $result = mysqli_query($conn, $sql);

    $html = '';
    $i = ($perPage == 'all') ? 1 : ($page - 1) * $perPage + 1;
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $html .= "
            <tr class='hover:bg-gray-50/80 transition-colors group'>
                <td class='px-6 py-4'>
                    <div class='w-10 h-10 rounded-lg bg-gray-100 overflow-hidden border border-gray-200'>
                        ".($row['logo'] ? "<img src='{$row['logo']}' class='w-full h-full object-contain'>" : "<div class='w-full h-full flex items-center justify-center text-gray-300'><svg class='w-6 h-6' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'></path></svg></div>")."
                    </div>
                </td>
                <td class='px-6 py-4'>
                    <div class='text-sm font-bold text-gray-900'>{$row['company_name']}</div>
                    <div class='text-xs text-gray-400 mt-0.5'>ID: #{$row['id']}</div>
                </td>
                <td class='px-6 py-4 text-sm text-gray-600 font-medium'>".($row['tax_id'] ?: '-')."</td>
                <td class='px-6 py-4 text-sm text-gray-600'>".($row['phone'] ?: '-')."</td>
                <td class='px-6 py-4 text-sm text-gray-600'>".($row['email'] ?: '-')."</td>
                <td class='px-6 py-4 text-right space-x-2'>
                    <button onclick='openModal(\"edit\", {$row['id']})' 
                            class='inline-flex items-center p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all' title='แก้ไข'>
                        <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'></path>
                        </svg>
                    </button>
                    <button onclick='deleteCompany({$row['id']})' 
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
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'></path>
                </svg>
                ไม่พบข้อมูลที่ค้นหา
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
    $sql = "SELECT * FROM company WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    echo json_encode(mysqli_fetch_assoc($result));
}

if ($action == 'create') {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $tax_id = mysqli_real_escape_string($conn, $_POST['tax_id'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');

    if (empty($company_name) || empty($tax_id) || empty($address)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (ชื่อบริษัท, เลขผู้เสียภาษี, ที่อยู่)']);
        exit;
    }

    // Check for duplicate name
    $checkSql = "SELECT id FROM company WHERE company_name = '$company_name'";
    $checkRes = mysqli_query($conn, $checkSql);
    if (mysqli_num_rows($checkRes) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ชื่อบริษัทนี้มีอยู่ในระบบแล้ว']);
        exit;
    }

    $logo_path = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $ext;
        $target = 'uploads/logos/' . $filename;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
            $logo_path = $target;
        }
    }

    $sql = "INSERT INTO company (company_name, address, tax_id, phone, email, logo) 
            VALUES ('$company_name', '$address', '$tax_id', '$phone', '$email', '$logo_path')";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มข้อมูลเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}

if ($action == 'update') {
    $id = (int)$_POST['id'];
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $tax_id = mysqli_real_escape_string($conn, $_POST['tax_id'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');

    if (empty($company_name) || empty($tax_id) || empty($address)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (ชื่อบริษัท, เลขผู้เสียภาษี, ที่อยู่)']);
        exit;
    }

    // Check for duplicate name (excluding current record)
    $checkSql = "SELECT id FROM company WHERE company_name = '$company_name' AND id != $id";
    $checkRes = mysqli_query($conn, $checkSql);
    if (mysqli_num_rows($checkRes) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ชื่อบริษัทนี้มีอยู่ในระบบแล้ว']);
        exit;
    }

    $logo_update = "";
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $ext;
        $target = 'uploads/logos/' . $filename;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
            $logo_update = ", logo = '$target'";
        }
    }

    $sql = "UPDATE company SET 
            company_name = '$company_name', 
            address = '$address', 
            tax_id = '$tax_id', 
            phone = '$phone', 
            email = '$email' 
            $logo_update
            WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}

if ($action == 'delete') {
    $id = (int)$_POST['id'];
    $sql = "DELETE FROM company WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}
?>

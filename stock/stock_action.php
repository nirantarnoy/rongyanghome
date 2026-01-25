<?php
require '../auth_check.php';
require '../config.php';

$action = $_GET['action'] ?? '';
$company_id = $_SESSION['company_id'];

function logStockAction($conn, $company_id, $activity, $action_type) {
    $user_login = $_SESSION['user_login'] ?? 'system';
    // Try to get user_id from users table if not in session
    $user_id = 0;
    $u_sql = "SELECT id FROM users WHERE username = ?";
    $u_stmt = mysqli_prepare($conn, $u_sql);
    mysqli_stmt_bind_param($u_stmt, "s", $user_login);
    mysqli_stmt_execute($u_stmt);
    $u_res = mysqli_stmt_get_result($u_stmt);
    if ($u_row = mysqli_fetch_assoc($u_res)) {
        $user_id = $u_row['id'];
    }

    $sql = "INSERT INTO stock_action_logs (company_id, user_id, activity, action_type) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiss", $company_id, $user_id, $activity, $action_type);
    mysqli_stmt_execute($stmt);
}


if ($action == 'add_category') {
    header('Content-Type: application/json');
    $name = $_POST['name'] ?? '';
    $sql = "INSERT INTO stock_categories (company_id, name) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $company_id, $name);
    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "เพิ่มหมวดหมู่: $name", 'create');
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มหมวดหมู่เรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'get_categories') {
    header('Content-Type: application/json');
    $sql = "SELECT * FROM stock_categories WHERE company_id = ? ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $categories = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $categories[] = $row;
    }
    echo json_encode($categories);
    exit;
}

if ($action == 'delete_category') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $sql = "DELETE FROM stock_categories WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "ลบหมวดหมู่ ID: $id", 'delete');
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

if ($action == 'add_product') {
    header('Content-Type: application/json');
    $name = $_POST['name'] ?? '';
    $sku = $_POST['sku'] ?? '';
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $unit = $_POST['unit'] ?? '';
    $price = $_POST['price'] ?? 0;
    $min_stock = $_POST['min_stock'] ?? 0;
    $description = $_POST['description'] ?? '';
    
    $image_url = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $image_url = 'uploads/' . $file_name;
        }
    }

    $sql = "INSERT INTO stock_products (company_id, category_id, name, sku, unit, price, min_stock, image_url, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisssdiss", $company_id, $category_id, $name, $sku, $unit, $price, $min_stock, $image_url, $description);

    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "เพิ่มสินค้า: $name (SKU: $sku)", 'create');
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มสินค้าเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'get_product') {
    header('Content-Type: application/json');
    $id = $_GET['id'] ?? 0;
    $sql = "SELECT * FROM stock_products WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($res);
    if ($product) {
        logStockAction($conn, $company_id, "ดูรายละเอียดสินค้า: {$product['name']}", 'view');
    }
    echo json_encode($product);
    exit;
}

if ($action == 'update_product') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $sku = $_POST['sku'] ?? '';
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $unit = $_POST['unit'] ?? '';
    $price = $_POST['price'] ?? 0;
    $min_stock = $_POST['min_stock'] ?? 0;
    $description = $_POST['description'] ?? '';
    
    // Handle image upload
    $image_sql = "";
    $image_url = "";
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $image_url = 'uploads/' . $file_name;
            $image_sql = ", image_url = ?";
        }
    }

    $sql = "UPDATE stock_products SET name = ?, sku = ?, category_id = ?, unit = ?, price = ?, min_stock = ?, description = ? $image_sql WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($image_sql) {
        mysqli_stmt_bind_param($stmt, "ssisdissii", $name, $sku, $category_id, $unit, $price, $min_stock, $description, $image_url, $id, $company_id);
    } else {
        mysqli_stmt_bind_param($stmt, "ssisdisii", $name, $sku, $category_id, $unit, $price, $min_stock, $description, $id, $company_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "แก้ไขสินค้า: $name (SKU: $sku)", 'update');
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตสินค้าเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'get_products') {
    header('Content-Type: text/html');
    $sql = "SELECT p.*, c.name as category_name, 
            (SELECT SUM(CASE WHEN type='in' THEN qty ELSE -qty END) FROM stock_transactions WHERE product_id = p.id) as current_stock
            FROM stock_products p 
            LEFT JOIN stock_categories c ON p.category_id = c.id 
            WHERE p.company_id = ? 
            ORDER BY p.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) == 0) {
        echo '<div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">ไม่พบข้อมูลสินค้า</div>';
        exit;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $stock = $row['current_stock'] ?? 0;
        $img = !empty($row['image_url']) ? $row['image_url'] : 'https://via.placeholder.com/300x180?text=No+Image';
        $low_stock_class = ($stock <= $row['min_stock']) ? 'style="background: #FEE2E2; color: #991B1B;"' : '';
        
        echo '
        <div class="product-card">
            <img src="'.$img.'" class="product-img" alt="'.$row['name'].'">
            <div class="product-info">
                <div class="product-name">'.htmlspecialchars($row['name']).'</div>
                <div class="product-sku">SKU: '.htmlspecialchars($row['sku']).' | '.htmlspecialchars($row['category_name'] ?? 'ทั่วไป').'</div>
                <div class="product-meta">
                    <div class="product-price">฿'.number_format($row['price'], 2).'</div>
                    <div class="product-stock" '.$low_stock_class.'>คงเหลือ: '.$stock.' '.$row['unit'].'</div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                    <button onclick="editProduct('.$row['id'].')" class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #6366F1;">
                        <i class="fas fa-edit"></i> แก้ไข
                    </button>
                    <button onclick="deleteProduct('.$row['id'].')" class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #EF4444;">
                        <i class="fas fa-trash"></i> ลบ
                    </button>
                </div>
            </div>
        </div>';
    }
    exit;
}

if ($action == 'delete_product') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    
    // Check dependencies
    $check_sql = "SELECT 
        (SELECT COUNT(*) FROM stock_transactions WHERE product_id = ?) as trans_count,
        (SELECT COUNT(*) FROM stock_production_orders WHERE product_id = ?) as prod_count,
        (SELECT COUNT(*) FROM stock_production_bom WHERE product_id = ?) as bom_count,
        (SELECT COUNT(*) FROM stock_requisition_items WHERE product_id = ?) as req_count";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "iiii", $id, $id, $id, $id);
    mysqli_stmt_execute($check_stmt);
    $check_res = mysqli_stmt_get_result($check_stmt);
    $counts = mysqli_fetch_assoc($check_res);
    
    if ($counts['trans_count'] > 0 || $counts['prod_count'] > 0 || $counts['bom_count'] > 0 || $counts['req_count'] > 0) {
        $msg = "ไม่สามารถลบสินค้าได้เนื่องจากมีการใช้งานอยู่ใน: ";
        $reasons = [];
        if ($counts['trans_count'] > 0) $reasons[] = "รายการเคลื่อนไหวสต็อก";
        if ($counts['prod_count'] > 0) $reasons[] = "ใบสั่งผลิต (สินค้าหลัก)";
        if ($counts['bom_count'] > 0) $reasons[] = "รายการวัสดุ (BOM)";
        if ($counts['req_count'] > 0) $reasons[] = "ใบเบิกสินค้า";
        echo json_encode(['status' => 'error', 'message' => $msg . implode(', ', $reasons)]);
        exit;
    }

    // Get product name for log before deleting
    $name_sql = "SELECT name FROM stock_products WHERE id = ? AND company_id = ?";
    $name_stmt = mysqli_prepare($conn, $name_sql);
    mysqli_stmt_bind_param($name_stmt, "ii", $id, $company_id);
    mysqli_stmt_execute($name_stmt);
    $name_res = mysqli_stmt_get_result($name_stmt);
    $product_name = mysqli_fetch_assoc($name_res)['name'] ?? "Unknown";

    $sql = "DELETE FROM stock_products WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);

    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "ลบสินค้า: $product_name (ID: $id)", 'delete');
        echo json_encode(['status' => 'success', 'message' => 'ลบสินค้าเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'add_transaction') {
    header('Content-Type: application/json');
    $product_id = $_POST['product_id'] ?? 0;
    $type = $_POST['type'] ?? 'in';
    $qty = $_POST['qty'] ?? 0;
    $note = $_POST['note'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');

    $sql = "INSERT INTO stock_transactions (company_id, product_id, type, qty, note, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisiss", $company_id, $product_id, $type, $qty, $note, $transaction_date);

    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "บันทึกรายการ $type: สินค้า ID $product_id จำนวน $qty", 'create');
        echo json_encode(['status' => 'success', 'message' => 'บันทึกรายการเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'get_transactions') {
    header('Content-Type: text/html');
    $sql = "SELECT t.*, p.name as product_name, p.unit 
            FROM stock_transactions t 
            JOIN stock_products p ON t.product_id = p.id 
            WHERE t.company_id = ? 
            ORDER BY t.transaction_date DESC, t.id DESC 
            LIMIT 50";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) == 0) {
        echo '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">ไม่พบประวัติรายการ</td></tr>';
        exit;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $type_label = ($row['type'] == 'in') ? '<span style="color: #059669; font-weight: 600;"><i class="fas fa-arrow-down"></i> รับเข้า</span>' : '<span style="color: #DC2626; font-weight: 600;"><i class="fas fa-arrow-up"></i> เบิกออก</span>';
        $qty_prefix = ($row['type'] == 'in') ? '+' : '-';
        $qty_color = ($row['type'] == 'in') ? '#059669' : '#DC2626';

        echo '
        <tr style="border-bottom: 1px solid var(--border-color);">
            <td style="padding: 1rem;">'.date('d/m/Y', strtotime($row['transaction_date'])).'</td>
            <td style="padding: 1rem;">'.$type_label.'</td>
            <td style="padding: 1rem; font-weight: 500;">'.htmlspecialchars($row['product_name']).'</td>
            <td style="padding: 1rem; text-align: right; font-weight: 700; color: '.$qty_color.';">'.$qty_prefix.number_format($row['qty']).' '.$row['unit'].'</td>
            <td style="padding: 1rem; color: var(--text-muted);">'.htmlspecialchars($row['note']).'</td>
            <td style="padding: 1rem; text-align: center;">
                <button onclick="deleteTransaction('.$row['id'].')" style="background: none; border: none; color: #EF4444; cursor: pointer;">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>';
    }
    exit;
}

if ($action == 'delete_transaction') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $sql = "DELETE FROM stock_transactions WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);

    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "ลบรายการเคลื่อนไหว ID: $id", 'delete');
        echo json_encode(['status' => 'success', 'message' => 'ลบรายการเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'add_production') {
    header('Content-Type: application/json');
    $order_no = $_POST['order_no'] ?? '';
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $due_date = $_POST['due_date'] ?? null;
    $customer_name = $_POST['customer_name'] ?? '';
    $project_name = $_POST['project_name'] ?? '';
    $product_id = $_POST['product_id'] ?? 0;
    $sku = $_POST['sku'] ?? '';
    $qty = $_POST['qty'] ?? 0;
    $unit = $_POST['unit'] ?? '';
    $dimensions = $_POST['dimensions'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    $qc_standards = $_POST['qc_standards'] ?? '';
    $ordered_by = $_POST['ordered_by'] ?? '';
    $foreman = $_POST['foreman'] ?? '';
    $status = 'pending';
    $bom = $_POST['bom'] ?? [];

    mysqli_begin_transaction($conn);
    try {
        $sql = "INSERT INTO stock_production_orders (company_id, order_no, order_date, due_date, customer_name, project_name, product_id, sku, qty, unit, dimensions, instructions, qc_standards, ordered_by, foreman, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssssisdsssssss", $company_id, $order_no, $order_date, $due_date, $customer_name, $project_name, $product_id, $sku, $qty, $unit, $dimensions, $instructions, $qc_standards, $ordered_by, $foreman, $status);
        mysqli_stmt_execute($stmt);
        $production_order_id = mysqli_insert_id($conn);

        foreach ($bom as $item) {
            $sql_bom = "INSERT INTO stock_production_bom (production_order_id, product_id, qty) VALUES (?, ?, ?)";
            $stmt_bom = mysqli_prepare($conn, $sql_bom);
            mysqli_stmt_bind_param($stmt_bom, "iid", $production_order_id, $item['product_id'], $item['qty']);
            mysqli_stmt_execute($stmt_bom);
        }

        mysqli_commit($conn);
        logStockAction($conn, $company_id, "สร้างใบสั่งผลิต: $order_no", 'create');
        echo json_encode(['status' => 'success', 'message' => 'สร้างใบสั่งผลิตเรียบร้อยแล้ว']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_productions') {
    header('Content-Type: text/html');
    $sql = "SELECT po.*, p.name as product_name 
            FROM stock_production_orders po 
            LEFT JOIN stock_products p ON po.product_id = p.id 
            WHERE po.company_id = ? 
            ORDER BY po.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) == 0) {
        echo '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">ไม่พบรายการสั่งผลิต</td></tr>';
        exit;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $status_colors = [
            'pending' => ['bg' => '#FEF3C7', 'text' => '#92400E', 'label' => 'รอดำเนินการ'],
            'in_progress' => ['bg' => '#DBEAFE', 'text' => '#1E40AF', 'label' => 'กำลังผลิต'],
            'completed' => ['bg' => '#D1FAE5', 'text' => '#065F46', 'label' => 'เสร็จสิ้น'],
            'cancelled' => ['bg' => '#F3F4F6', 'text' => '#374151', 'label' => 'ยกเลิก']
        ];
        $s = $status_colors[$row['status']];
        $status_badge = '<span style="background: '.$s['bg'].'; color: '.$s['text'].'; padding: 0.3rem 0.8rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 600;">'.$s['label'].'</span>';

        echo '
        <tr style="border-bottom: 1px solid var(--border-color);">
            <td style="padding: 1rem; font-weight: 600;">'.htmlspecialchars($row['order_no']).'</td>
            <td style="padding: 1rem;">'.date('d/m/Y', strtotime($row['order_date'] ?? $row['created_at'])).'</td>
            <td style="padding: 1rem;">
                <div style="font-weight: 500;">'.htmlspecialchars($row['customer_name']).'</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">'.htmlspecialchars($row['project_name']).'</div>
            </td>
            <td style="padding: 1rem;">'.htmlspecialchars($row['product_name']).'</td>
            <td style="padding: 1rem; text-align: right; font-weight: 700;">'.number_format($row['qty']).' '.$row['unit'].'</td>
            <td style="padding: 1rem; text-align: center;">'.$status_badge.'</td>
            <td style="padding: 1rem; text-align: center;">
                <select onchange="updateProductionStatus('.$row['id'].', this.value)" style="padding: 0.3rem; border-radius: 0.5rem; border: 1px solid var(--border-color); font-size: 0.8rem;">
                    <option value="pending" '.($row['status'] == 'pending' ? 'selected' : '').'>รอดำเนินการ</option>
                    <option value="in_progress" '.($row['status'] == 'in_progress' ? 'selected' : '').'>กำลังผลิต</option>
                    <option value="completed" '.($row['status'] == 'completed' ? 'selected' : '').'>เสร็จสิ้น</option>
                    <option value="cancelled" '.($row['status'] == 'cancelled' ? 'selected' : '').'>ยกเลิก</option>
                </select>
            </td>
        </tr>';
    }
    exit;
}

if ($action == 'update_production_status') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? 'pending';

    $sql = "UPDATE stock_production_orders SET status = ? WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sii", $status, $id, $company_id);

    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "อัปเดตสถานะใบสั่งผลิต ID $id เป็น $status", 'update');
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตสถานะเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'add_requisition') {
    header('Content-Type: application/json');
    $req_no = $_POST['req_no'] ?? '';
    $po_no = $_POST['po_no'] ?? '';
    $so_no = $_POST['so_no'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $shipping_address = $_POST['shipping_address'] ?? '';
    $shipping_method = $_POST['shipping_method'] ?? '';
    $requisition_date = $_POST['requisition_date'] ?? date('Y-m-d');
    $items = $_POST['items'] ?? [];

    mysqli_begin_transaction($conn);
    try {
        $sql = "INSERT INTO stock_requisitions (company_id, req_no, po_no, so_no, customer_name, phone, shipping_address, shipping_method, requisition_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssssss", $company_id, $req_no, $po_no, $so_no, $customer_name, $phone, $shipping_address, $shipping_method, $requisition_date);
        mysqli_stmt_execute($stmt);
        $requisition_id = mysqli_insert_id($conn);

        foreach ($items as $item) {
            $sql_item = "INSERT INTO stock_requisition_items (requisition_id, product_id, qty) VALUES (?, ?, ?)";
            $stmt_item = mysqli_prepare($conn, $sql_item);
            mysqli_stmt_bind_param($stmt_item, "iii", $requisition_id, $item['product_id'], $item['qty']);
            mysqli_stmt_execute($stmt_item);
        }

        mysqli_commit($conn);
        logStockAction($conn, $company_id, "สร้างใบเบิกสินค้า: $req_no", 'create');
        echo json_encode(['status' => 'success', 'message' => 'สร้างใบเบิกเรียบร้อยแล้ว']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_requisitions') {
    header('Content-Type: text/html');
    $sql = "SELECT * FROM stock_requisitions WHERE company_id = ? ORDER BY id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) == 0) {
        echo '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">ไม่พบใบเบิก</td></tr>';
        exit;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $status_colors = [
            'pending' => ['bg' => '#FEF3C7', 'text' => '#92400E', 'label' => 'รออนุมัติ'],
            'approved' => ['bg' => '#D1FAE5', 'text' => '#065F46', 'label' => 'อนุมัติแล้ว'],
            'rejected' => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'label' => 'ปฏิเสธ']
        ];
        $s = $status_colors[$row['status']];
        $status_badge = '<span style="background: '.$s['bg'].'; color: '.$s['text'].'; padding: 0.3rem 0.8rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 600;">'.$s['label'].'</span>';

        echo '
        <tr style="border-bottom: 1px solid var(--border-color);">
            <td style="padding: 1rem;">'.date('d/m/Y', strtotime($row['requisition_date'] ?? $row['created_at'])).'</td>
            <td style="padding: 1rem; font-weight: 600;">'.htmlspecialchars($row['req_no']).'</td>
            <td style="padding: 1rem;">'.htmlspecialchars($row['customer_name']).'</td>
            <td style="padding: 1rem; text-align: center;">'.$status_badge.'</td>
            <td style="padding: 1rem; text-align: center;">
                <select onchange="updateRequisitionStatus('.$row['id'].', this.value)" style="padding: 0.3rem; border-radius: 0.5rem; border: 1px solid var(--border-color); font-size: 0.8rem;">
                    <option value="pending" '.($row['status'] == 'pending' ? 'selected' : '').'>รออนุมัติ</option>
                    <option value="approved" '.($row['status'] == 'approved' ? 'selected' : '').'>อนุมัติ</option>
                    <option value="rejected" '.($row['status'] == 'rejected' ? 'selected' : '').'>ปฏิเสธ</option>
                </select>
            </td>
        </tr>';
    }
    exit;
}

if ($action == 'update_requisition_status') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? 'pending';

    $sql = "UPDATE stock_requisitions SET status = ? WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sii", $status, $id, $company_id);

    if (mysqli_stmt_execute($stmt)) {
        // If approved, we should automatically create stock 'out' transactions
        if ($status == 'approved') {
            $sql_items = "SELECT * FROM stock_requisition_items WHERE requisition_id = ?";
            $stmt_items = mysqli_prepare($conn, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "i", $id);
            mysqli_stmt_execute($stmt_items);
            $res_items = mysqli_stmt_get_result($stmt_items);
            
            while ($item = mysqli_fetch_assoc($res_items)) {
                $sql_trans = "INSERT INTO stock_transactions (company_id, product_id, type, qty, note, transaction_date) VALUES (?, ?, 'out', ?, ?, ?)";
                $note = "เบิกตามใบเบิกเลขที่ " . $id;
                $date = date('Y-m-d');
                $stmt_trans = mysqli_prepare($conn, $sql_trans);
                mysqli_stmt_bind_param($stmt_trans, "iiiss", $company_id, $item['product_id'], $item['qty'], $note, $date);
                mysqli_stmt_execute($stmt_trans);
            }
        }
        logStockAction($conn, $company_id, "อัปเดตสถานะใบเบิก ID $id เป็น $status", 'update');
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตสถานะเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . mysqli_error($conn)]);
    }
    exit;
}
?>

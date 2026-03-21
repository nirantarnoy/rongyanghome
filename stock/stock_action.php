<?php
require '../auth_check.php';
require '../config.php';

$action = $_GET['action'] ?? '';
$company_id = $_SESSION['company_id'];
$active_year = $_SESSION['active_year'] ?? (int)date('Y');

// Auto-migrate byproducts table
$bq = "CREATE TABLE IF NOT EXISTS stock_production_byproducts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT NOT NULL,
    name VARCHAR(255),
    qty DECIMAL(15,4),
    unit VARCHAR(50),
    price DECIMAL(15,4),
    total DECIMAL(15,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_prod_id (production_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $bq);


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


if ($action == 'add_warehouse') {
    header('Content-Type: application/json');
    $name = $_POST['name'] ?? '';
    $location = $_POST['location'] ?? '';
    $sql = "INSERT INTO stock_warehouses (company_id, name, location) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $company_id, $name, $location);
    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "เพิ่มคลังสินค้า: $name", 'create');
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มคลังสินค้าเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'update_warehouse') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $location = $_POST['location'] ?? '';
    $sql = "UPDATE stock_warehouses SET name = ?, location = ? WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssii", $name, $location, $id, $company_id);
    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "แก้ไขคลังสินค้า: $name", 'update');
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตคลังสินค้าเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'get_warehouses') {
    header('Content-Type: text/html');
    $sql = "SELECT * FROM stock_warehouses WHERE company_id = ? ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($res) == 0) {
        echo '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">ยังไม่มีข้อมูลคลังสินค้า</p>';
        exit;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        echo '
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color); background: white; margin-bottom: 0.5rem; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: all 0.2s ease;" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 6px rgba(0,0,0,0.1)\';" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 4px rgba(0,0,0,0.05)\';">
            <div style="cursor: pointer; flex-grow: 1;" onclick="viewWarehouseDetails('.$row['id'].', \''.addslashes($row['name']).'\')">
                <div style="font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-warehouse text-muted"></i> '.htmlspecialchars($row['name']).'
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">'.htmlspecialchars($row['location']).'</div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="editWarehouse('.$row['id'].', \''.addslashes($row['name']).'\', \''.addslashes($row['location']).'\')" style="background: #6366F1; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 0.4rem; cursor: pointer; font-size: 0.8rem;">
                    <i class="fas fa-edit"></i> แก้ไข
                </button>
                <button onclick="deleteWarehouse('.$row['id'].')" style="background: #EF4444; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 0.4rem; cursor: pointer; font-size: 0.8rem;">
                    <i class="fas fa-trash"></i> ลบ
                </button>
            </div>
        </div>';
    }
    exit;
}

if ($action == 'get_warehouses_json') {
    header('Content-Type: application/json');
    $sql = "SELECT * FROM stock_warehouses WHERE company_id = ? ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $warehouses = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $warehouses[] = $row;
    }
    echo json_encode($warehouses);
    exit;
}

if ($action == 'delete_warehouse') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    
    // Check if there are transactions in this warehouse
    $check_sql = "SELECT COUNT(*) as count FROM stock_transactions WHERE warehouse_id = ? AND company_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $id, $company_id);
    mysqli_stmt_execute($check_stmt);
    $count = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt))['count'];
    
    if ($count > 0) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบคลังสินค้าได้เนื่องจากมีรายการเคลื่อนไหวในคลังนี้แล้ว']);
        exit;
    }

    $sql = "DELETE FROM stock_warehouses WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "ลบคลังสินค้า ID: $id", 'delete');
        echo json_encode(['status' => 'success', 'message' => 'ลบคลังสินค้าเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if ($action == 'get_warehouse_details_html') {
    header('Content-Type: text/html');
    $warehouse_id = $_GET['id'] ?? 0;
    $search = $_GET['search'] ?? '';

    $search_cond = "";
    if ($search) {
        $search_cond = " AND (p.name LIKE ? OR p.sku LIKE ?) ";
    }
    
    $sql = "SELECT p.*, c.name as cat_name,
            (SELECT SUM(CASE WHEN t.type='in' THEN t.qty ELSE -t.qty END) 
             FROM stock_transactions t 
             WHERE t.product_id = p.id AND t.warehouse_id = ? AND t.company_id = ?) as balance
            FROM stock_products p
            LEFT JOIN stock_categories c ON p.category_id = c.id
            WHERE p.company_id = ? $search_cond
            HAVING balance > 0
            ORDER BY c.name ASC, p.name ASC";
            
    $stmt = mysqli_prepare($conn, $sql);
    if ($search) {
        $search_term = "%$search%";
        mysqli_stmt_bind_param($stmt, "iiiss", $warehouse_id, $company_id, $company_id, $search_term, $search_term);
    } else {
        mysqli_stmt_bind_param($stmt, "iii", $warehouse_id, $company_id, $company_id);
    }
    
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $grouped_products = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $cat_key = $row['cat_name'] ? $row['cat_name'] : 'ไม่มีหมวดหมู่';
        if (!isset($grouped_products[$cat_key])) {
            $grouped_products[$cat_key] = [];
        }
        $grouped_products[$cat_key][] = $row;
    }

    if (empty($grouped_products)) {
        echo '<div style="text-align: center; padding: 3rem; color: var(--text-muted); background: #F9FAFB; border-radius: 0.5rem; border: 1px dashed #D1D5DB;">ไม่พบสินค้าในคลังนี้</div>';
        exit;
    }

    echo '<table id="whProductsTable" style="width: 100%; border-collapse: collapse; font-size: 0.9rem; margin-top: 1rem;">
            <thead>
                <tr style="background: #F3F4F6; color: var(--text-muted); text-align: left; border-bottom: 2px solid #E5E7EB;">
                    <th style="padding: 1rem; width: 50px; text-align: center;"><input type="checkbox" id="selectAllWarehouseProducts" checked></th>
                    <th style="padding: 1rem;">ชื่อสินค้า</th>
                    <th style="padding: 1rem;">รหัสอ้างอิงภายใน</th>
                    <th style="padding: 1rem; text-align: right;">ราคารวม</th>
                    <th style="padding: 1rem; text-align: right;">ต้นทุน</th>
                    <th style="padding: 1rem; text-align: right;">ที่มีอยู่</th>
                    <th style="padding: 1rem; text-align: center;">หน่วย</th>
                </tr>
            </thead>
            <tbody>';
            
    foreach ($grouped_products as $cat_name => $products) {
        $cat_count = count($products);
        echo '  <tr class="category-row" style="background: #E5E7EB; font-weight: 600;">
                    <td colspan="7" style="padding: 0.8rem 1rem; color: #374151;">
                        <i class="fas fa-caret-down" style="width: 20px;"></i> '.htmlspecialchars($cat_name).' ('.$cat_count.')
                    </td>
                </tr>';
                
        foreach ($products as $p) {
            $balance = $p['balance'];
            $cost = $p['price'] ?? 0;
            $total = $balance * $cost;
            
            echo '<tr style="border-bottom: 1px solid #E5E7EB; transition: background 0.15s;" onmouseover="this.style.background=\'#F9FAFB\'" onmouseout="this.style.background=\'transparent\'">
                    <td style="padding: 1rem; text-align: center;">
                        <input type="checkbox" class="export-checkbox" checked>
                    </td>
                    <td style="padding: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="far fa-star" style="color: #D1D5DB;"></i> '.htmlspecialchars($p['name']).'
                    </td>
                    <td style="padding: 1rem; color: var(--text-muted);">'.htmlspecialchars($p['sku']).'</td>
                    <td style="padding: 1rem; text-align: right;">'.number_format($total, 2).'</td>
                    <td style="padding: 1rem; text-align: right;">'.number_format($cost, 2).'</td>
                    <td style="padding: 1rem; text-align: right; font-weight: bold; color: #059669;">'.number_format($balance, 2).'</td>
                    <td style="padding: 1rem; text-align: center; color: var(--text-muted);">'.htmlspecialchars($p['unit']).'</td>
                  </tr>';
        }
    }
    
    echo '  </tbody>
          </table>';
    exit;
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

if ($action == 'get_warehouse_products') {
    header('Content-Type: application/json');
    $warehouse_id = $_GET['warehouse_id'] ?? 0;
    
    // Get products and their current balance in this warehouse
    $sql = "SELECT p.*, 
            (SELECT SUM(CASE WHEN t.type='in' THEN t.qty ELSE -t.qty END) 
             FROM stock_transactions t 
             WHERE t.product_id = p.id AND t.warehouse_id = ? AND t.company_id = ?) as balance
            FROM stock_products p
            WHERE p.company_id = ?
            HAVING balance > 0
            ORDER BY p.name ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $warehouse_id, $company_id, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $products = [];
    while ($row = mysqli_fetch_assoc($res)) {
        // Return raw image_url
        $products[] = $row;
    }
    echo json_encode($products);
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

if ($action == 'get_stock_balance') {
    header('Content-Type: application/json');
    $product_id = $_GET['product_id'] ?? 0;
    $warehouse_id = $_GET['warehouse_id'] ?? 0;
    
    $sql = "SELECT SUM(CASE WHEN type='in' THEN qty ELSE -qty END) as balance 
            FROM stock_transactions 
            WHERE product_id = ? AND warehouse_id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $product_id, $warehouse_id, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $balance = mysqli_fetch_assoc($res)['balance'] ?? 0;
    
    echo json_encode(['balance' => (int)$balance]);
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
    $sql = "SELECT p.*, c.name as category_name
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

    // Get warehouses for this company
    $w_sql = "SELECT * FROM stock_warehouses WHERE company_id = ? ORDER BY name ASC";
    $w_stmt = mysqli_prepare($conn, $w_sql);
    mysqli_stmt_bind_param($w_stmt, "i", $company_id);
    mysqli_stmt_execute($w_stmt);
    $w_res = mysqli_stmt_get_result($w_stmt);
    $warehouses = [];
    while ($w_row = mysqli_fetch_assoc($w_res)) {
        $warehouses[] = $w_row;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        // Calculate stock per warehouse
        $stock_by_warehouse = [];
        $total_stock = 0;
        foreach ($warehouses as $w) {
            $st_sql = "SELECT SUM(CASE WHEN type='in' THEN qty ELSE -qty END) as qty 
                       FROM stock_transactions 
                       WHERE product_id = ? AND warehouse_id = ?";
            $st_stmt = mysqli_prepare($conn, $st_sql);
            mysqli_stmt_bind_param($st_stmt, "ii", $row['id'], $w['id']);
            mysqli_stmt_execute($st_stmt);
            $st_qty = mysqli_fetch_assoc(mysqli_stmt_get_result($st_stmt))['qty'] ?? 0;
            $stock_by_warehouse[$w['name']] = $st_qty;
            $total_stock += $st_qty;
        }

        $img = !empty($row['image_url']) ? $row['image_url'] : 'https://via.placeholder.com/300x180?text=No+Image';
        $low_stock_class = ($total_stock <= $row['min_stock']) ? 'style="background: #FEE2E2; color: #991B1B;"' : '';
        
        $warehouse_details = "";
        if (!empty($stock_by_warehouse)) {
            $warehouse_details = '<div style="margin-top: 0.5rem; font-size: 0.8rem; border-top: 1px dashed #DDD; padding-top: 0.5rem;">';
            foreach ($stock_by_warehouse as $w_name => $qty) {
                $warehouse_details .= '<div style="display: flex; justify-content: space-between;"><span>'.htmlspecialchars($w_name).':</span> <b>'.number_format($qty).'</b></div>';
            }
            $warehouse_details .= '</div>';
        }

        echo '
        <div class="product-card">
            <img src="'.$img.'" class="product-img" alt="'.$row['name'].'">
            <div class="product-info">
                <div class="product-name">'.htmlspecialchars($row['name']).'</div>
                <div class="product-sku">SKU: '.htmlspecialchars($row['sku']).' | '.htmlspecialchars($row['category_name'] ?? 'ทั่วไป').'</div>
                <div class="product-meta">
                    <div class="product-price">฿'.number_format($row['price'], 2).'</div>
                    <div class="product-stock" '.$low_stock_class.'>รวม: '.$total_stock.' '.$row['unit'].'</div>
                </div>
                '.$warehouse_details.'
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
    $warehouse_id = $_POST['warehouse_id'] ?? 0;
    $type = $_POST['type'] ?? 'in';
    $qty = $_POST['qty'] ?? 0;
    $note = $_POST['note'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');

    $sql = "INSERT INTO stock_transactions (company_id, product_id, warehouse_id, type, qty, note, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiisiss", $company_id, $product_id, $warehouse_id, $type, $qty, $note, $transaction_date);

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
    $search = $_GET['search'] ?? '';
    $where = "WHERE t.company_id = ?";
    if ($search) {
        $search_term = "%$search%";
        $where .= " AND (p.name LIKE ? OR p.sku LIKE ? OR t.note LIKE ? OR w.name LIKE ?)";
    }

    $sql = "SELECT t.*, p.name as product_name, p.unit, w.name as warehouse_name 
            FROM stock_transactions t 
            JOIN stock_products p ON t.product_id = p.id 
            LEFT JOIN stock_warehouses w ON t.warehouse_id = w.id
            $where 
            ORDER BY t.transaction_date DESC, t.id DESC 
            LIMIT 100";
    $stmt = mysqli_prepare($conn, $sql);
    if ($search) {
        mysqli_stmt_bind_param($stmt, "issss", $company_id, $search_term, $search_term, $search_term, $search_term);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $company_id);
    }
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
            <td style="padding: 1rem;">
                <div style="font-weight: 500;">'.htmlspecialchars($row['product_name']).'</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-warehouse"></i> '.htmlspecialchars($row['warehouse_name'] ?? 'ไม่ระบุ').'</div>
            </td>
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
    $byproducts = $_POST['byproducts'] ?? [];


    mysqli_begin_transaction($conn);
    try {
        // Insert production order
        $sql = "INSERT INTO stock_production_orders (company_id, order_no, order_date, due_date, customer_name, project_name, product_id, sku, qty, unit, dimensions, instructions, qc_standards, ordered_by, foreman, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssssisdsssssss", $company_id, $order_no, $order_date, $due_date, $customer_name, $project_name, $product_id, $sku, $qty, $unit, $dimensions, $instructions, $qc_standards, $ordered_by, $foreman, $status);
        mysqli_stmt_execute($stmt);
        $production_order_id = mysqli_insert_id($conn);

        // Insert BOM items
        foreach ($bom as $item) {
            $sql_bom = "INSERT INTO stock_production_bom (production_order_id, product_id, qty) VALUES (?, ?, ?)";
            $stmt_bom = mysqli_prepare($conn, $sql_bom);
            mysqli_stmt_bind_param($stmt_bom, "iid", $production_order_id, $item['product_id'], $item['qty']);
            mysqli_stmt_execute($stmt_bom);
        }

        // Insert By-products
        foreach ($byproducts as $bp) {
            $sql_bp = "INSERT INTO stock_production_byproducts (production_order_id, name, qty, unit, price, total) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_bp = mysqli_prepare($conn, $sql_bp);
            $bp_name = $bp['name'] ?? '';
            $bp_qty = (float)($bp['qty'] ?? 0);
            $bp_unit = $bp['unit'] ?? '';
            $bp_price = (float)($bp['price'] ?? 0);
            $bp_total = (float)($bp['total'] ?? 0);
            mysqli_stmt_bind_param($stmt_bp, "isdsdd", $production_order_id, $bp_name, $bp_qty, $bp_unit, $bp_price, $bp_total);
            mysqli_stmt_execute($stmt_bp);
        }

        // Auto-create material requisition if BOM exists
        if (!empty($bom)) {
            $ty = date('Y') + 543;
            $requisition_no = 'WH' . substr($ty, -2) . date('md') . '-' . str_pad($production_order_id, 4, '0', STR_PAD_LEFT);
            $purpose = "เบิกวัสดุสำหรับใบสั่งผลิต: $order_no";
            
            $sql_req = "INSERT INTO material_requisitions (company_id, requisition_no, production_order_id, requisition_date, requested_by, department, purpose, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt_req = mysqli_prepare($conn, $sql_req);
            $department = 'ฝ่ายผลิต';
            mysqli_stmt_bind_param($stmt_req, "isissss", $company_id, $requisition_no, $production_order_id, $order_date, $ordered_by, $department, $purpose);
            mysqli_stmt_execute($stmt_req);
            $requisition_id = mysqli_insert_id($conn);

            // Insert requisition items from BOM
            foreach ($bom as $item) {
                // Get product unit
                $unit_sql = "SELECT unit FROM stock_products WHERE id = ?";
                $unit_stmt = mysqli_prepare($conn, $unit_sql);
                mysqli_stmt_bind_param($unit_stmt, "i", $item['product_id']);
                mysqli_stmt_execute($unit_stmt);
                $product_unit = mysqli_fetch_assoc(mysqli_stmt_get_result($unit_stmt))['unit'] ?? '';
                
                $sql_req_item = "INSERT INTO material_requisition_items (requisition_id, product_id, qty_requested, unit) 
                                VALUES (?, ?, ?, ?)";
                $stmt_req_item = mysqli_prepare($conn, $sql_req_item);
                mysqli_stmt_bind_param($stmt_req_item, "iids", $requisition_id, $item['product_id'], $item['qty'], $product_unit);
                mysqli_stmt_execute($stmt_req_item);
            }
            
            logStockAction($conn, $company_id, "สร้างใบเบิกจ่ายวัสดุอัตโนมัติ: $requisition_no จากใบสั่งผลิต $order_no", 'create');
        }

        // Save byproducts as products if requested
        $warnings = [];
        if (($_POST['save_byproducts_as_products'] ?? 0) == 1 && !empty($byproducts)) {
            foreach ($byproducts as $bp) {
                $bp_name = $bp['name'] ?? '';
                if (empty($bp_name)) continue;
                $check_p = "SELECT id FROM stock_products WHERE name = ? AND company_id = ? LIMIT 1";
                $st_p = mysqli_prepare($conn, $check_p);
                mysqli_stmt_bind_param($st_p, "si", $bp_name, $company_id);
                mysqli_stmt_execute($st_p);
                $res_p = mysqli_stmt_get_result($st_p);
                if (mysqli_num_rows($res_p) > 0) {
                    $warnings[] = "สินค้าชื่อ '$bp_name' มีอยู่ในระบบแล้ว (ข้ามการบันทึก)";
                } else {
                    $bp_unit = $bp['unit'] ?? '';
                    $bp_price = (float)($bp['price'] ?? 0);
                    $ins_p = "INSERT INTO stock_products (company_id, year, name, unit, price) VALUES (?, ?, ?, ?, ?)";
                    $st_ins = mysqli_prepare($conn, $ins_p);
                    mysqli_stmt_bind_param($st_ins, "iissd", $company_id, $active_year, $bp_name, $bp_unit, $bp_price);
                    mysqli_stmt_execute($st_ins);
                }
            }
        }

        mysqli_commit($conn);
        logStockAction($conn, $company_id, "สร้างใบสั่งผลิต: $order_no", 'create');
        
        $message = 'สร้างใบสั่งผลิตเรียบร้อยแล้ว';
        if (!empty($bom)) {
            $message .= ' และสร้างใบเบิกจ่ายวัสดุอัตโนมัติแล้ว';
        }
        
        echo json_encode(['status' => 'success', 'message' => $message, 'production_id' => $production_order_id, 'warnings' => $warnings]);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'update_production') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
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
    $bom = $_POST['bom'] ?? [];
    $byproducts = $_POST['byproducts'] ?? [];


    mysqli_begin_transaction($conn);
    try {
        $sql = "UPDATE stock_production_orders SET order_no = ?, order_date = ?, due_date = ?, customer_name = ?, project_name = ?, product_id = ?, sku = ?, qty = ?, unit = ?, dimensions = ?, instructions = ?, qc_standards = ?, ordered_by = ?, foreman = ? WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssisdssssssii", $order_no, $order_date, $due_date, $customer_name, $project_name, $product_id, $sku, $qty, $unit, $dimensions, $instructions, $qc_standards, $ordered_by, $foreman, $id, $company_id);
        mysqli_stmt_execute($stmt);

        // Update BOM: Delete old and insert new
        $sql_del_bom = "DELETE FROM stock_production_bom WHERE production_order_id = ?";
        $stmt_del = mysqli_prepare($conn, $sql_del_bom);
        mysqli_stmt_bind_param($stmt_del, "i", $id);
        mysqli_stmt_execute($stmt_del);

        if (!empty($bom)) {
            foreach ($bom as $item) {
                $sql_bom = "INSERT INTO stock_production_bom (production_order_id, product_id, qty) VALUES (?, ?, ?)";
                $stmt_bom = mysqli_prepare($conn, $sql_bom);
                mysqli_stmt_bind_param($stmt_bom, "iid", $id, $item['product_id'], $item['qty']);
                mysqli_stmt_execute($stmt_bom);
            }
        }

        // Update By-products: Delete old and insert new
        $sql_del_bp = "DELETE FROM stock_production_byproducts WHERE production_order_id = ?";
        $stmt_del_bp = mysqli_prepare($conn, $sql_del_bp);
        mysqli_stmt_bind_param($stmt_del_bp, "i", $id);
        mysqli_stmt_execute($stmt_del_bp);

        foreach ($byproducts as $bp) {
            $sql_bp = "INSERT INTO stock_production_byproducts (production_order_id, name, qty, unit, price, total) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_bp = mysqli_prepare($conn, $sql_bp);
            $bp_name = $bp['name'] ?? '';
            $bp_qty = (float)($bp['qty'] ?? 0);
            $bp_unit = $bp['unit'] ?? '';
            $bp_price = (float)($bp['price'] ?? 0);
            $bp_total = (float)($bp['total'] ?? 0);
            mysqli_stmt_bind_param($stmt_bp, "isdsdd", $id, $bp_name, $bp_qty, $bp_unit, $bp_price, $bp_total);
            mysqli_stmt_execute($stmt_bp);
        }
        // Save byproducts as products if requested
        $warnings = [];
        if (($_POST['save_byproducts_as_products'] ?? 0) == 1 && !empty($byproducts)) {
            foreach ($byproducts as $bp) {
                $bp_name = $bp['name'] ?? '';
                if (empty($bp_name)) continue;
                $check_p = "SELECT id FROM stock_products WHERE name = ? AND company_id = ? LIMIT 1";
                $st_p = mysqli_prepare($conn, $check_p);
                mysqli_stmt_bind_param($st_p, "si", $bp_name, $company_id);
                mysqli_stmt_execute($st_p);
                $res_p = mysqli_stmt_get_result($st_p);
                if (mysqli_num_rows($res_p) > 0) {
                    $warnings[] = "สินค้าชื่อ '$bp_name' มีอยู่ในระบบแล้ว (ข้ามการบันทึก)";
                } else {
                    $bp_unit = $bp['unit'] ?? '';
                    $bp_price = (float)($bp['price'] ?? 0);
                    $ins_p = "INSERT INTO stock_products (company_id, year, name, unit, price) VALUES (?, ?, ?, ?, ?)";
                    $st_ins = mysqli_prepare($conn, $ins_p);
                    mysqli_stmt_bind_param($st_ins, "iissd", $company_id, $active_year, $bp_name, $bp_unit, $bp_price);
                    mysqli_stmt_execute($st_ins);
                }
            }
        }
        // Update pending material requisition if exists
        $sql_check_req = "SELECT id FROM material_requisitions WHERE production_order_id = ? AND status = 'pending' LIMIT 1";
        $stmt_check_req = mysqli_prepare($conn, $sql_check_req);
        mysqli_stmt_bind_param($stmt_check_req, "i", $id);
        mysqli_stmt_execute($stmt_check_req);
        $res_check_req = mysqli_stmt_get_result($stmt_check_req);
            
        if ($row_req = mysqli_fetch_assoc($res_check_req)) {
            $requisition_id = $row_req['id'];
            
            // Delete old items
            $sql_del_req_items = "DELETE FROM material_requisition_items WHERE requisition_id = ?";
            $stmt_del_req_items = mysqli_prepare($conn, $sql_del_req_items);
            mysqli_stmt_bind_param($stmt_del_req_items, "i", $requisition_id);
            mysqli_stmt_execute($stmt_del_req_items);
            
            // Insert new items from updated BOM
            foreach ($bom as $item) {
                $unit_sql = "SELECT unit FROM stock_products WHERE id = ?";
                $unit_stmt = mysqli_prepare($conn, $unit_sql);
                mysqli_stmt_bind_param($unit_stmt, "i", $item['product_id']);
                mysqli_stmt_execute($unit_stmt);
                $product_unit = mysqli_fetch_assoc(mysqli_stmt_get_result($unit_stmt))['unit'] ?? '';
                
                $sql_req_item = "INSERT INTO material_requisition_items (requisition_id, product_id, qty_requested, unit) 
                                VALUES (?, ?, ?, ?)";
                $stmt_req_item = mysqli_prepare($conn, $sql_req_item);
                mysqli_stmt_bind_param($stmt_req_item, "iids", $requisition_id, $item['product_id'], $item['qty'], $product_unit);
                mysqli_stmt_execute($stmt_req_item);
            }
            logStockAction($conn, $company_id, "อัปเดตรายการในใบเบิกวัสดุ ID: $requisition_id ตามการแก้ไข BOM", 'update');
        }

        mysqli_commit($conn);
        logStockAction($conn, $company_id, "แก้ไขใบสั่งผลิต: $order_no (ID: $id)", 'update');
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตใบสั่งผลิตเรียบร้อยแล้ว', 'warnings' => $warnings]);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_production') {
    header('Content-Type: application/json');
    $id = $_GET['id'] ?? 0;
    $sql = "SELECT * FROM stock_production_orders WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($res);
    
    if ($order) {
        $sql_bom = "SELECT b.*, p.name as product_name, p.sku FROM stock_production_bom b JOIN stock_products p ON b.product_id = p.id WHERE b.production_order_id = ?";
        $stmt_bom = mysqli_prepare($conn, $sql_bom);
        mysqli_stmt_bind_param($stmt_bom, "i", $id);
        mysqli_stmt_execute($stmt_bom);
        $res_bom = mysqli_stmt_get_result($stmt_bom);
        $bom_list = [];
        while ($row_bom = mysqli_fetch_assoc($res_bom)) {
            $bom_list[] = $row_bom;
        }

        // Get byproducts
        $bp_list = [];
        $sql_bp = "SELECT * FROM stock_production_byproducts WHERE production_order_id = ?";
        $stmt_bp = mysqli_prepare($conn, $sql_bp);
        mysqli_stmt_bind_param($stmt_bp, "i", $id);
        mysqli_stmt_execute($stmt_bp);
        $res_bp = mysqli_stmt_get_result($stmt_bp);
        while ($row_bp = mysqli_fetch_assoc($res_bp)) {
            $bp_list[] = $row_bp;
        }
        
        echo json_encode(['status' => 'success', 'data' => $order, 'bom' => $bom_list, 'byproducts' => $bp_list]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบใบสั่งผลิต']);
    }
    exit;
}

if ($action == 'delete_production') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    
    mysqli_begin_transaction($conn);
    try {
        // Delete BOM first
        $sql_bom = "DELETE FROM stock_production_bom WHERE production_order_id = ?";
        $stmt_bom = mysqli_prepare($conn, $sql_bom);
        mysqli_stmt_bind_param($stmt_bom, "i", $id);
        mysqli_stmt_execute($stmt_bom);

        // Delete order
        $sql = "DELETE FROM stock_production_orders WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
        mysqli_stmt_execute($stmt);

        mysqli_commit($conn);
        logStockAction($conn, $company_id, "ลบใบสั่งผลิต ID: $id", 'delete');
        echo json_encode(['status' => 'success', 'message' => 'ลบใบสั่งผลิตเรียบร้อยแล้ว']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}


if ($action == 'get_productions') {
    header('Content-Type: text/html');
    $search = $_GET['search'] ?? '';
    $where = "WHERE po.company_id = ?";
    if ($search) {
        $search_term = "%$search%";
        $where .= " AND (po.order_no LIKE ? OR po.customer_name LIKE ? OR po.project_name LIKE ? OR p.name LIKE ?)";
    }

    $sql = "SELECT po.*, p.name as product_name 
            FROM stock_production_orders po 
            LEFT JOIN stock_products p ON po.product_id = p.id 
            $where 
            ORDER BY po.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if ($search) {
        mysqli_stmt_bind_param($stmt, "issss", $company_id, $search_term, $search_term, $search_term, $search_term);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $company_id);
    }
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
                <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: center;">
                    <select onchange="updateProductionStatus('.$row['id'].', this.value)" style="padding: 0.3rem; border-radius: 0.5rem; border: 1px solid var(--border-color); font-size: 0.8rem; width: 100%;">
                        <option value="pending" '.($row['status'] == 'pending' ? 'selected' : '').'>รอดำเนินการ</option>
                        <option value="in_progress" '.($row['status'] == 'in_progress' ? 'selected' : '').'>กำลังผลิต</option>
                        <option value="completed" '.($row['status'] == 'completed' ? 'selected' : '').'>เสร็จสิ้น</option>
                        <option value="cancelled" '.($row['status'] == 'cancelled' ? 'selected' : '').'>ยกเลิก</option>
                    </select>
                    <div style="display: flex; gap: 0.25rem;">
                        <button onclick="viewProduction('.$row['id'].')" class="btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #6366F1;" title="ดูรายละเอียด">
                            <i class="fas fa-eye"></i>
                        </button>
                        '.($row['status'] != 'completed' ? '
                        <button onclick="finishProduction('.$row['id'].')" class="btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #8B5CF6;" title="ผลิตสำเร็จแล้ว">
                            <i class="fas fa-check-circle"></i> ผลิตสำเร็จ
                        </button>' : '').'
                        <a href="print_production.php?id='.$row['id'].'" target="_blank" class="btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #10B981;" title="พิมพ์">
                            <i class="fas fa-print"></i>
                        </a>
                        <button onclick="editProduction('.$row['id'].')" class="btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #6366F1;" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteProduction('.$row['id'].')" class="btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #EF4444;" title="ลบ">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
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

if ($action == 'complete_production') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $warehouse_id = $_POST['warehouse_id'] ?? 0;
    $prod_qty = $_POST['prod_qty'] ?? 0; // Actual qty produced
    
    if (!$warehouse_id) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุคลังสินค้า']);
        exit;
    }

    mysqli_begin_transaction($conn);
    try {
        // 1. Get Production Order
        $sql = "SELECT * FROM stock_production_orders WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
        mysqli_stmt_execute($stmt);
        $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        
        if (!$order) throw new Exception("ไม่พบใบสั่งผลิต");
        if ($order['status'] == 'completed') throw new Exception("ใบสั่งผลิตนี้สำเร็จไปแล้ว");

        $actual_qty = ($prod_qty > 0) ? $prod_qty : $order['qty'];

        // 2. Update status to completed
        $sql_up = "UPDATE stock_production_orders SET status = 'completed' WHERE id = ?";
        $stmt_up = mysqli_prepare($conn, $sql_up);
        mysqli_stmt_bind_param($stmt_up, "i", $id);
        mysqli_stmt_execute($stmt_up);

        // 3. Stock IN for finished product
        $sql_in = "INSERT INTO stock_transactions (company_id, product_id, warehouse_id, type, qty, note, transaction_date) 
                   VALUES (?, ?, ?, 'in', ?, ?, NOW())";
        $note_in = "ผลิตสำเร็จตามใบสั่งผลิต: " . $order['order_no'];
        $stmt_in = mysqli_prepare($conn, $sql_in);
        mysqli_stmt_bind_param($stmt_in, "iiids", $company_id, $order['product_id'], $warehouse_id, $actual_qty, $note_in);
        mysqli_stmt_execute($stmt_in);

        // 4. Stock OUT for BOM items (if not already completed via material requisition)
        // Check associated material requisition
        $sql_mr = "SELECT id, status FROM material_requisitions WHERE production_order_id = ? AND company_id = ? AND status != 'completed' LIMIT 1";
        $stmt_mr = mysqli_prepare($conn, $sql_mr);
        mysqli_stmt_bind_param($stmt_mr, "ii", $id, $company_id);
        mysqli_stmt_execute($stmt_mr);
        $res_mr = mysqli_stmt_get_result($stmt_mr);
        
        // If material requisition exists and not completed, deduct stock
        if ($row_mr = mysqli_fetch_assoc($res_mr)) {
            $mr_id = $row_mr['id'];
            
            // Get BOM items from the production order directly to ensure we deduct what was planned
            $sql_bom = "SELECT * FROM stock_production_bom WHERE production_order_id = ?";
            $stmt_bom = mysqli_prepare($conn, $sql_bom);
            mysqli_stmt_bind_param($stmt_bom, "i", $id);
            mysqli_stmt_execute($stmt_bom);
            $res_bom = mysqli_stmt_get_result($stmt_bom);
            
            while ($bom_item = mysqli_fetch_assoc($res_bom)) {
                $p_id = $bom_item['product_id'];
                $b_qty = $bom_item['qty'];
                
                // Deduct from warehouses (Auto-find stock if no warehouse specified for BOM)
                // Use the same logic as approve_material_req
                $wh_sql = "SELECT w.id, SUM(CASE WHEN t.type='in' THEN t.qty ELSE -t.qty END) as balance
                           FROM stock_warehouses w
                           LEFT JOIN stock_transactions t ON w.id = t.warehouse_id AND t.product_id = ?
                           WHERE w.company_id = ?
                           GROUP BY w.id HAVING balance > 0 ORDER BY balance DESC";
                $wh_stmt = mysqli_prepare($conn, $wh_sql);
                mysqli_stmt_bind_param($wh_stmt, "ii", $p_id, $company_id);
                mysqli_stmt_execute($wh_stmt);
                $wh_res = mysqli_stmt_get_result($wh_stmt);
                
                $rem = $b_qty;
                while ($wh = mysqli_fetch_assoc($wh_res)) {
                    if ($rem <= 0) break;
                    $deduct = min($rem, $wh['balance']);
                    
                    $out_sql = "INSERT INTO stock_transactions (company_id, product_id, warehouse_id, type, qty, note, transaction_date) 
                                VALUES (?, ?, ?, 'out', ?, ?, NOW())";
                    $note_out = "ตัดวัสดุตามการผลิตสำเร็จ: " . $order['order_no'];
                    $out_stmt = mysqli_prepare($conn, $out_sql);
                    mysqli_stmt_bind_param($out_stmt, "iiids", $company_id, $p_id, $wh['id'], $deduct, $note_out);
                    mysqli_stmt_execute($out_stmt);
                    
                    $rem -= $deduct;
                }
            }
            
            // Mark requisition as completed
            $sql_mr_up = "UPDATE material_requisitions SET status = 'completed' WHERE id = ?";
            $stmt_mr_up = mysqli_prepare($conn, $sql_mr_up);
            mysqli_stmt_bind_param($stmt_mr_up, "i", $mr_id);
            mysqli_stmt_execute($stmt_mr_up);
        }

        mysqli_commit($conn);
        logStockAction($conn, $company_id, "ยืนยันการผลิตสำเร็จ: $order[order_no], สินค้าเพิ่มสต็อก $actual_qty", 'update');
        echo json_encode(['status' => 'success', 'message' => 'ยืนยันการผลิตสำเร็จและอัปเดตสต็อกเรียบร้อยแล้ว']);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'add_requisition') {
    header('Content-Type: application/json');
    $req_no = $_POST['req_no'] ?? '';
    $po_no = $_POST['po_no'] ?? '';
    $so_no = $_POST['so_no'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    $requester_name = $_POST['requester_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $shipping_address = $_POST['shipping_address'] ?? '';
    $shipping_method = $_POST['shipping_method'] ?? '';
    $requisition_date = $_POST['requisition_date'] ?? date('Y-m-d');
    $items = $_POST['items'] ?? [];

    mysqli_begin_transaction($conn);
    try {
        $sql = "INSERT INTO stock_requisitions (company_id, req_no, po_no, so_no, customer_name, requester_name, phone, shipping_address, shipping_method, requisition_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssssssss", $company_id, $req_no, $po_no, $so_no, $customer_name, $requester_name, $phone, $shipping_address, $shipping_method, $requisition_date);
        mysqli_stmt_execute($stmt);
        $requisition_id = mysqli_insert_id($conn);

        foreach ($items as $item) {
            $sql_item = "INSERT INTO stock_requisition_items (requisition_id, product_id, warehouse_id, qty) VALUES (?, ?, ?, ?)";
            $stmt_item = mysqli_prepare($conn, $sql_item);
            mysqli_stmt_bind_param($stmt_item, "iiii", $requisition_id, $item['product_id'], $item['warehouse_id'], $item['qty']);
            mysqli_stmt_execute($stmt_item);
        }

        mysqli_commit($conn);
        logStockAction($conn, $company_id, "สร้างใบเบิกสินค้า: $req_no", 'create');
        echo json_encode(['status' => 'success', 'message' => 'สร้างใบเบิกเรียบร้อยแล้ว']);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'update_requisition') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $req_no = $_POST['req_no'] ?? '';
    $po_no = $_POST['po_no'] ?? '';
    $so_no = $_POST['so_no'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    $requester_name = $_POST['requester_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $shipping_address = $_POST['shipping_address'] ?? '';
    $shipping_method = $_POST['shipping_method'] ?? '';
    $requisition_date = $_POST['requisition_date'] ?? date('Y-m-d');
    $items = $_POST['items'] ?? [];

    mysqli_begin_transaction($conn);
    try {
        $sql_old = "SELECT status, req_no FROM stock_requisitions WHERE id = ? AND company_id = ?";
        $stmt_old = mysqli_prepare($conn, $sql_old);
        mysqli_stmt_bind_param($stmt_old, "ii", $id, $company_id);
        mysqli_stmt_execute($stmt_old);
        $old_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_old));
        
        if (!$old_data) throw new Exception("ไม่พบใบเบิกนี้");
        
        $old_status = $old_data['status'];
        $old_req_no = $old_data['req_no'];

        // If it was already approved, reverse old stock movement
        if ($old_status == 'approved') {
            $note_match = "เบิกตามใบเบิกเลขที่ " . $old_req_no;
            $sql_del_trans = "DELETE FROM stock_transactions WHERE company_id = ? AND note = ?";
            $stmt_del_trans = mysqli_prepare($conn, $sql_del_trans);
            mysqli_stmt_bind_param($stmt_del_trans, "is", $company_id, $note_match);
            mysqli_stmt_execute($stmt_del_trans);
            
            // Log the reversal
            logStockAction($conn, $company_id, "คืนสต็อกชั่วคราวเพื่อแก้ไขใบเบิก: $old_req_no", 'update');
        }

        $sql = "UPDATE stock_requisitions SET req_no=?, po_no=?, so_no=?, customer_name=?, requester_name=?, phone=?, shipping_address=?, shipping_method=?, requisition_date=? WHERE id=? AND company_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssssii", $req_no, $po_no, $so_no, $customer_name, $requester_name, $phone, $shipping_address, $shipping_method, $requisition_date, $id, $company_id);
        mysqli_stmt_execute($stmt);

        // Delete old items
        $sql_del = "DELETE FROM stock_requisition_items WHERE requisition_id = ?";
        $stmt_del = mysqli_prepare($conn, $sql_del);
        mysqli_stmt_bind_param($stmt_del, "i", $id);
        mysqli_stmt_execute($stmt_del);

        // Insert new items
        foreach ($items as $item) {
            $sql_item = "INSERT INTO stock_requisition_items (requisition_id, product_id, warehouse_id, qty) VALUES (?, ?, ?, ?)";
            $stmt_item = mysqli_prepare($conn, $sql_item);
            mysqli_stmt_bind_param($stmt_item, "iiii", $id, $item['product_id'], $item['warehouse_id'], $item['qty']);
            mysqli_stmt_execute($stmt_item);
        }

        // If it was already approved, re-apply stock movement for new items
        if ($old_status == 'approved') {
            foreach ($items as $item) {
                $sql_trans = "INSERT INTO stock_transactions (company_id, product_id, warehouse_id, type, qty, note, transaction_date) VALUES (?, ?, ?, 'out', ?, ?, ?)";
                $note = "เบิกตามใบเบิกเลขที่ " . $req_no;
                $date = date('Y-m-d');
                $stmt_trans = mysqli_prepare($conn, $sql_trans);
                mysqli_stmt_bind_param($stmt_trans, "iiiiss", $company_id, $item['product_id'], $item['warehouse_id'], $item['qty'], $note, $date);
                mysqli_stmt_execute($stmt_trans);
            }
            logStockAction($conn, $company_id, "ตัดสต็อกใหม่อีกครั้งหลังแก้ไขใบเบิก: $req_no", 'update');
        }

        mysqli_commit($conn);
        logStockAction($conn, $company_id, "แก้ไขใบเบิกสินค้า: $req_no", 'update');
        echo json_encode(['status' => 'success', 'message' => 'แก้ไขใบเบิกเรียบร้อยแล้ว']);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_requisitions') {
    header('Content-Type: text/html');
    $search = $_GET['search'] ?? '';
    $where = "WHERE company_id = ?";
    if ($search) {
        $search_term = "%$search%";
        $where .= " AND (req_no LIKE ? OR customer_name LIKE ? OR requester_name LIKE ?)";
    }
    
    $sql = "SELECT * FROM stock_requisitions $where ORDER BY id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if ($search) {
        mysqli_stmt_bind_param($stmt, "isss", $company_id, $search_term, $search_term, $search_term);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $company_id);
    }
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
            <td style="padding: 1rem;">
                <div style="font-weight: 600;">'.htmlspecialchars($row['customer_name']).'</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">ผู้เบิก: '.htmlspecialchars($row['requester_name'] ?? '-').'</div>
            </td>
            <td style="padding: 1rem; text-align: center;">'.$status_badge.'</td>
            <td style="padding: 1rem; text-align: center;">
                <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">
                    <select onchange="updateRequisitionStatus('.$row['id'].', this.value)" style="padding: 0.3rem; border-radius: 0.5rem; border: 1px solid var(--border-color); font-size: 0.8rem;">
                        <option value="pending" '.($row['status'] == 'pending' ? 'selected' : '').'>รออนุมัติ</option>
                        <option value="approved" '.($row['status'] == 'approved' ? 'selected' : '').'>อนุมัติ</option>
                        <option value="rejected" '.($row['status'] == 'rejected' ? 'selected' : '').'>ปฏิเสธ</option>
                    </select>
                    <button onclick="editRequisition('.$row['id'].')" class="btn-primary" style="padding: 0.4rem; background: #6366F1;" title="แก้ไข">
                        <i class="fas fa-edit"></i>
                    </button>
                    <a href="print_delivery_note.php?id='.$row['id'].'" target="_blank" class="btn-primary" style="padding: 0.4rem; background: #E91E63;" title="ใบส่งสินค้า">
                        <i class="fas fa-truck"></i>
                    </a>
                    <button onclick="viewRequisition('.$row['id'].')" class="btn-primary" style="padding: 0.4rem; background: #6366F1;" title="ดูรายละเอียด">
                        <i class="fas fa-eye"></i>
                    </button>
                    <a href="print_requisition.php?id='.$row['id'].'" target="_blank" class="btn-primary" style="padding: 0.4rem; background: #10B981;" title="พิมพ์">
                        <i class="fas fa-print"></i>
                    </a>
                    <button onclick="openProjectExpenseModal('.$row['id'].')" class="btn-primary" style="padding: 0.4rem 0.8rem; background: #22C55E; color: white; font-weight: bold; font-size: 0.75rem;" title="บันทึกรายจ่าย">
                        กดเพื่อบันทึก
                    </button>
                    <button onclick="deleteRequisition('.$row['id'].')" class="btn-primary" style="padding: 0.4rem; background: #EF4444;" title="ลบ">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>';
    }
    exit;
}

if ($action == 'update_requisition_status') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? 'pending';

    // Get old status and req_no first
    $sql_old = "SELECT status, req_no FROM stock_requisitions WHERE id = ? AND company_id = ?";
    $stmt_old = mysqli_prepare($conn, $sql_old);
    mysqli_stmt_bind_param($stmt_old, "ii", $id, $company_id);
    mysqli_stmt_execute($stmt_old);
    $req_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_old));
    
    if (!$req_data) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบใบเบิกนี้']);
        exit;
    }
    
    $old_status = $req_data['status'];
    $req_no = $req_data['req_no'];

    mysqli_begin_transaction($conn);
    try {
        $sql = "UPDATE stock_requisitions SET status = ? WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $status, $id, $company_id);
        mysqli_stmt_execute($stmt);

        // If changing to 'approved', deduct stock
        if ($old_status != 'approved' && $status == 'approved') {
            $sql_items = "SELECT * FROM stock_requisition_items WHERE requisition_id = ?";
            $stmt_items = mysqli_prepare($conn, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "i", $id);
            mysqli_stmt_execute($stmt_items);
            $res_items = mysqli_stmt_get_result($stmt_items);
            
            while ($item = mysqli_fetch_assoc($res_items)) {
                $sql_trans = "INSERT INTO stock_transactions (company_id, product_id, warehouse_id, type, qty, note, transaction_date) VALUES (?, ?, ?, 'out', ?, ?, ?)";
                $note = "เบิกตามใบเบิกเลขที่ " . $req_no;
                $date = date('Y-m-d');
                $stmt_trans = mysqli_prepare($conn, $sql_trans);
                mysqli_stmt_bind_param($stmt_trans, "iiiiss", $company_id, $item['product_id'], $item['warehouse_id'], $item['qty'], $note, $date);
                mysqli_stmt_execute($stmt_trans);
            }
        } 
        // If changing from 'approved' to something else, restore stock (delete past deduct transactions)
        else if ($old_status == 'approved' && $status != 'approved') {
            $note_match = "เบิกตามใบเบิกเลขที่ " . $req_no;
            $sql_del_trans = "DELETE FROM stock_transactions WHERE company_id = ? AND note = ?";
            $stmt_del_trans = mysqli_prepare($conn, $sql_del_trans);
            mysqli_stmt_bind_param($stmt_del_trans, "is", $company_id, $note_match);
            mysqli_stmt_execute($stmt_del_trans);
        }

        mysqli_commit($conn);
        logStockAction($conn, $company_id, "อัปเดตสถานะใบเบิก ID $id เป็น $status", 'update');
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตสถานะและสต็อกเรียบร้อยแล้ว']);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_requisition_details') {
    header('Content-Type: text/html');
    $id = $_GET['id'] ?? 0;
    
    $sql = "SELECT * FROM stock_requisitions WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $req = mysqli_fetch_assoc($res);
    
    if (!$req) {
        echo '<p>ไม่พบข้อมูลใบเบิก</p>';
        exit;
    }
    
    $sql_items = "SELECT ri.*, p.name as product_name, p.sku, p.unit, w.name as warehouse_name 
                  FROM stock_requisition_items ri 
                  JOIN stock_products p ON ri.product_id = p.id 
                  LEFT JOIN stock_warehouses w ON ri.warehouse_id = w.id
                  WHERE ri.requisition_id = ?";
    $stmt_items = mysqli_prepare($conn, $sql_items);
    mysqli_stmt_bind_param($stmt_items, "i", $id);
    mysqli_stmt_execute($stmt_items);
    $res_items = mysqli_stmt_get_result($stmt_items);
    
    echo '
    <div style="margin-bottom: 2rem; border-bottom: 2px solid #F3F4F6; padding-bottom: 1rem;">
        <h2 style="margin: 0; color: var(--text-dark); font-size: 1.5rem;">รายละเอียดใบเบิกสินค้า</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
            <div>
                <p><strong>เลขที่ใบเบิก:</strong> '.htmlspecialchars($req['req_no']).'</p>
                <p><strong>วันที่เบิก:</strong> '.date('d/m/Y', strtotime($req['requisition_date'])).'</p>
                <p><strong>ชื่อลูกค้า:</strong> '.htmlspecialchars($req['customer_name']).'</p>
                <p><strong>ผู้เบิก:</strong> '.htmlspecialchars($req['requester_name'] ?? '-').'</p>
            </div>
            <div>
                <p><strong>เลขที่ PO:</strong> '.htmlspecialchars($req['po_no'] ?? '-').'</p>
                <p><strong>เลขที่ SO:</strong> '.htmlspecialchars($req['so_no'] ?? '-').'</p>
                <p><strong>เบอร์โทรศัพท์:</strong> '.htmlspecialchars($req['phone'] ?? '-').'</p>
            </div>
        </div>
        <div style="margin-top: 1rem;">
            <p><strong>ที่อยู่จัดส่ง:</strong> '.nl2br(htmlspecialchars($req['shipping_address'] ?? '-')).'</p>
            <p><strong>ช่องทางการจัดส่ง:</strong> '.htmlspecialchars($req['shipping_method'] ?? '-').'</p>
        </div>
    </div>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #F9FAFB; border-bottom: 2px solid #E5E7EB;">
                <th style="padding: 0.75rem; text-align: left;">สินค้า</th>
                <th style="padding: 0.75rem; text-align: left;">คลังสินค้า</th>
                <th style="padding: 0.75rem; text-align: right;">จำนวน</th>
            </tr>
        </thead>
        <tbody>';
    
    while ($item = mysqli_fetch_assoc($res_items)) {
        echo '
            <tr style="border-bottom: 1px solid #F3F4F6;">
                <td style="padding: 0.75rem;">
                    <div style="font-weight: 500;">'.htmlspecialchars($item['product_name']).'</div>
                    <div style="font-size: 0.8rem; color: #6B7280;">SKU: '.htmlspecialchars($item['sku']).'</div>
                </td>
                <td style="padding: 0.75rem;">'.htmlspecialchars($item['warehouse_name'] ?? 'ไม่ระบุ').'</td>
                <td style="padding: 0.75rem; text-align: right; font-weight: 600;">'.number_format($item['qty']).' '.htmlspecialchars($item['unit']).'</td>
            </tr>';
    }
    
    echo '
        </tbody>
    </table>
    
    <div style="margin-top: 2rem; text-align: right;">
        <button onclick="closeModal()" class="btn-primary" style="background: #9CA3AF;">ปิดหน้าต่าง</button>
        <a href="print_requisition.php?id='.$id.'" target="_blank" class="btn-primary" style="background: #10B981; margin-left: 0.5rem;">
            <i class="fas fa-print"></i> พิมพ์ใบเบิก
        </a>
    </div>';
    exit;
}

if ($action == 'delete_requisition') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    
    // Check status
    $sql_check = "SELECT status FROM stock_requisitions WHERE id = ? AND company_id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "ii", $id, $company_id);
    mysqli_stmt_execute($stmt_check);
    $status = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check))['status'] ?? '';
    
    if ($status == 'approved') {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบใบเบิกที่อนุมัติแล้วได้']);
        exit;
    }
    
    mysqli_begin_transaction($conn);
    try {
        // Delete items
        $sql_del_items = "DELETE FROM stock_requisition_items WHERE requisition_id = ?";
        $stmt_del_items = mysqli_prepare($conn, $sql_del_items);
        mysqli_stmt_bind_param($stmt_del_items, "i", $id);
        mysqli_stmt_execute($stmt_del_items);
        
        // Delete requisition
        $sql_del = "DELETE FROM stock_requisitions WHERE id = ? AND company_id = ?";
        $stmt_del = mysqli_prepare($conn, $sql_del);
        mysqli_stmt_bind_param($stmt_del, "ii", $id, $company_id);
        mysqli_stmt_execute($stmt_del);
        
        mysqli_commit($conn);
        logStockAction($conn, $company_id, "ลบใบเบิกสินค้า ID: $id", 'delete');
        echo json_encode(['status' => 'success', 'message' => 'ลบใบเบิกเรียบร้อยแล้ว']);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
    exit;
}


if ($action == 'get_requisition_json') {
    header('Content-Type: application/json');
    $id = $_GET['id'] ?? 0;
    $sql = "SELECT * FROM stock_requisitions WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $req = mysqli_fetch_assoc($res);
    
    if ($req) {
        $sql_items = "SELECT ri.*, p.name as product_name, p.sku, p.unit, p.price, w.name as warehouse_name 
                      FROM stock_requisition_items ri 
                      JOIN stock_products p ON ri.product_id = p.id 
                      LEFT JOIN stock_warehouses w ON ri.warehouse_id = w.id
                      WHERE ri.requisition_id = ?";
        $stmt_items = mysqli_prepare($conn, $sql_items);
        mysqli_stmt_bind_param($stmt_items, "i", $id);
        mysqli_stmt_execute($stmt_items);
        $res_items = mysqli_stmt_get_result($stmt_items);
        $req['items'] = [];
        $grand_total = 0;
        while ($item = mysqli_fetch_assoc($res_items)) {
            $item['subtotal'] = $item['qty'] * $item['price'];
            $grand_total += $item['subtotal'];
            $req['items'][] = $item;
        }
        $req['grand_total'] = $grand_total;
        echo json_encode(['status' => 'success', 'data' => $req]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล']);
    }
    exit;
}

if ($action == 'get_production_details') {
    header('Content-Type: text/html');
    $id = $_GET['id'] ?? 0;
    
    $sql = "SELECT po.*, p.name as product_name, p.sku as product_sku, p.unit as product_unit 
            FROM stock_production_orders po 
            LEFT JOIN stock_products p ON po.product_id = p.id 
            WHERE po.id = ? AND po.company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($res);
    
    if (!$order) {
        echo '<p>ไม่พบข้อมูลใบสั่งผลิต</p>';
        exit;
    }
        $sql_bom = "SELECT b.*, p.name as product_name, p.sku, p.unit 
                FROM stock_production_bom b 
                JOIN stock_products p ON b.product_id = p.id 
                WHERE b.production_order_id = ?";
    $stmt_bom = mysqli_prepare($conn, $sql_bom);
    mysqli_stmt_bind_param($stmt_bom, "i", $id);
    mysqli_stmt_execute($stmt_bom);
    $res_bom = mysqli_stmt_get_result($stmt_bom);
    
    // Get byproducts
    $sql_bp = "SELECT * FROM stock_production_byproducts WHERE production_order_id = ? ORDER BY id";
    $stmt_bp = mysqli_prepare($conn, $sql_bp);
    mysqli_stmt_bind_param($stmt_bp, "i", $id);
    mysqli_stmt_execute($stmt_bp);
    $res_bp = mysqli_stmt_get_result($stmt_bp);

    
    echo '
    <div style="margin-bottom: 2rem; border-bottom: 2px solid #F3F4F6; padding-bottom: 1rem;">
        <h2 style="margin: 0; color: var(--text-dark); font-size: 1.5rem;">รายละเอียดใบสั่งผลิต</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
            <div>
                <p><strong>เลขที่ใบสั่งผลิต:</strong> '.htmlspecialchars($order['order_no']).'</p>
                <p><strong>วันที่สั่งผลิต:</strong> '.date('d/m/Y', strtotime($order['order_date'])).'</p>
                <p><strong>กำหนดเสร็จ:</strong> '.($order['due_date'] ? date('d/m/Y', strtotime($order['due_date'])) : '-').'</p>
                <p><strong>ลูกค้า:</strong> '.htmlspecialchars($order['customer_name']).'</p>
                <p><strong>โครงการ:</strong> '.htmlspecialchars($order['project_name'] ?? '-').'</p>
            </div>
            <div>
                <p><strong>สินค้าที่ผลิต:</strong> '.htmlspecialchars($order['product_name']).'</p>
                <p><strong>SKU:</strong> '.htmlspecialchars($order['sku']).'</p>
                <p><strong>จำนวน:</strong> '.number_format($order['qty']).' '.htmlspecialchars($order['unit']).'</p>
                <p><strong>ขนาด/มิติ:</strong> '.htmlspecialchars($order['dimensions'] ?? '-').'</p>
            </div>
        </div>
        <div style="margin-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <p><strong>ผู้สั่งผลิต:</strong> '.htmlspecialchars($order['ordered_by'] ?? '-').'</p>
            </div>
            <div>
                <p><strong>หัวหน้าช่าง:</strong> '.htmlspecialchars($order['foreman'] ?? '-').'</p>
            </div>
        </div>
        <div style="margin-top: 1rem; border-top: 1px dashed #E5E7EB; padding-top: 1rem;">
            <p><strong>ขั้นตอนการทำงาน/คำแนะนำ (ปฏิบัติการ):</strong><br>'.nl2br(htmlspecialchars($order['instructions'] ?? '-')).'</p>
            <p style="margin-top: 1rem;"><strong>มาตรฐานการตรวจสอบ (QC):</strong><br>'.nl2br(htmlspecialchars($order['qc_standards'] ?? '-')).'</p>
        </div>
    </div>';
    
    $mr_html = "";
    $sql_mr = "SELECT * FROM material_requisitions WHERE production_order_id = ? AND company_id = ? ORDER BY id DESC";
    $stmt_mr = mysqli_prepare($conn, $sql_mr);
    mysqli_stmt_bind_param($stmt_mr, "ii", $id, $company_id);
    mysqli_stmt_execute($stmt_mr);
    $res_mr = mysqli_stmt_get_result($stmt_mr);
    
    if (mysqli_num_rows($res_mr) == 0) {
        $mr_html = '<p style="color: #9CA3AF; text-align: center; padding: 1rem; background: #F9FAFB; border-radius: 0.5rem;">ยังไม่มีใบเบิกวัสดุ</p>';
    } else {
        $mr_html = '<table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #F9FAFB; border-bottom: 2px solid #E5E7EB;">
                        <th style="padding: 0.75rem; text-align: left;">เลขที่ใบเบิก</th>
                        <th style="padding: 0.75rem; text-align: left;">วันที่</th>
                        <th style="padding: 0.75rem; text-align: center;">สถานะ</th>
                        <th style="padding: 0.75rem; text-align: center;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>';
        while ($mr = mysqli_fetch_assoc($res_mr)) {
            $status_labels = [
                'pending' => ['bg' => '#FEF3C7', 'text' => '#92400E', 'label' => 'รออนุมัติ'],
                'approved' => ['bg' => '#DBEAFE', 'text' => '#1E40AF', 'label' => 'อนุมัติแล้ว'],
                'completed' => ['bg' => '#D1FAE5', 'text' => '#065F46', 'label' => 'ตัดสต็อกแล้ว'],
                'rejected' => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'label' => 'ปฏิเสธ']
            ];
            $s = $status_labels[$mr['status']] ?? ['bg' => '#F3F4F6', 'text' => '#374151', 'label' => $mr['status']];
            
            $mr_html .= '<tr style="border-bottom: 1px solid #F3F4F6;">
                    <td style="padding: 0.75rem; font-weight: 500;">'.htmlspecialchars($mr['requisition_no']).'</td>
                    <td style="padding: 0.75rem;">'.date('d/m/Y', strtotime($mr['requisition_date'])).'</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <span style="background: '.$s['bg'].'; color: '.$s['text'].'; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 600;">'.$s['label'].'</span>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <div style="display: flex; gap: 0.25rem; justify-content: center;">
                            <a href="print_material_requisition.php?id='.$mr['id'].'" target="_blank" class="btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.75rem; background: #10B981;" title="พิมพ์">
                                <i class="fas fa-print"></i>
                            </a>';
            
            if ($mr['status'] == 'pending') {
                $mr_html .= '<button onclick="approveMaterialReq('.$mr['id'].')" class="btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.75rem; background: #6366F1;" title="อนุมัติและตัดสต็อก">
                        <i class="fas fa-check"></i>
                      </button>
                      <button onclick="rejectMaterialReq('.$mr['id'].')" class="btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.75rem; background: #EF4444;" title="ปฏิเสธ">
                        <i class="fas fa-times"></i>
                      </button>';
            }
            
            $mr_html .= '      </div>
                    </td>
                  </tr>';
        }
        $mr_html .= '</tbody></table>';
    }

    echo '
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <span>ใบเบิกวัสดุ (Material Requisitions)</span>
        </h3>
        <div id="materialRequisitionsList">
            '.$mr_html.'
        </div>
    </div>

    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">รายการวัสดุ (BOM)</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #F9FAFB; border-bottom: 2px solid #E5E7EB;">
                    <th style="padding: 0.75rem; text-align: left;">วัสดุ</th>
                    <th style="padding: 0.75rem; text-align: right;">จำนวนที่ใช้</th>
                </tr>
            </thead>
            <tbody>';
    
    if (mysqli_num_rows($res_bom) == 0) {
        echo '<tr><td colspan="2" style="padding: 1rem; text-align: center; color: #9CA3AF;">ไม่มีรายการวัสดุ</td></tr>';
    } else {
        while ($bom = mysqli_fetch_assoc($res_bom)) {
            echo '
                <tr style="border-bottom: 1px solid #F3F4F6;">
                    <td style="padding: 0.75rem;">
                        <div style="font-weight: 500;">'.htmlspecialchars($bom['product_name']).'</div>
                        <div style="font-size: 0.8rem; color: #6B7280;">SKU: '.htmlspecialchars($bom['sku']).'</div>
                    </td>
                    <td style="padding: 0.75rem; text-align: right; font-weight: 600;">'.number_format($bom['qty'], 2).' '.htmlspecialchars($bom['unit']).'</td>
                </tr>';
        }
    }
    
    echo '
            </tbody>
        </table>
    </div>

    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">ผลิตภัณฑ์พลอยได้หรือเศษผลผลิตคงเหลือ</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #F0FDF4; border-bottom: 2px solid #BBF7D0;">
                    <th style="padding: 0.75rem; text-align: left;">รายการ</th>
                    <th style="padding: 0.75rem; text-align: right;">จำนวน</th>
                    <th style="padding: 0.75rem; text-align: right;">ราคา/หน่วย</th>
                    <th style="padding: 0.75rem; text-align: right;">รวมราคา</th>
                </tr>
            </thead>
            <tbody>';
    
    if (mysqli_num_rows($res_bp) == 0) {
        echo '<tr><td colspan="4" style="padding: 1rem; text-align: center; color: #9CA3AF;">ไม่มีรายการผลิตภัณฑ์พลอยได้</td></tr>';
    } else {
        while ($bp = mysqli_fetch_assoc($res_bp)) {
            echo '
                <tr style="border-bottom: 1px solid #F0FDF4;">
                    <td style="padding: 0.75rem;">'.htmlspecialchars($bp['name']).'</td>
                    <td style="padding: 0.75rem; text-align: right;">'.number_format($bp['qty'], 2).' '.htmlspecialchars($bp['unit']).'</td>
                    <td style="padding: 0.75rem; text-align: right;">'.number_format($bp['price'], 2).'</td>
                    <td style="padding: 0.75rem; text-align: right; font-weight: 600;">'.number_format($bp['total'], 2).'</td>
                </tr>';
        }
    }
    
    echo '
            </tbody>
        </table>
    </div>

    
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">ขั้นตอนการทำงาน / คำแนะนำ</h3>
        <div style="background: #F9FAFB; padding: 1rem; border-radius: 0.5rem; border: 1px solid #E5E7EB;">
            '.nl2br(htmlspecialchars($order['instructions'] ?? 'ไม่มีข้อมูล')).'
        </div>
    </div>
    
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">มาตรฐานการตรวจสอบ (QC)</h3>
        <div style="background: #F9FAFB; padding: 1rem; border-radius: 0.5rem; border: 1px solid #E5E7EB;">
            '.nl2br(htmlspecialchars($order['qc_standards'] ?? 'ไม่มีข้อมูล')).'
        </div>
    </div>
    
    <div style="margin-top: 2rem; text-align: right;">
        <button onclick="closeProductionModal()" class="btn-primary" style="background: #9CA3AF;">ปิดหน้าต่าง</button>
        <a href="print_production.php?id='.$id.'" target="_blank" class="btn-primary" style="background: #10B981; margin-left: 0.5rem;">
            <i class="fas fa-print"></i> พิมพ์ใบสั่งผลิต
        </a>
    </div>';
    exit;
}

// Material Requisition Actions
if ($action == 'get_material_requisitions') {
    header('Content-Type: application/json');
    $production_order_id = $_GET['production_order_id'] ?? 0;
    
    $sql = "SELECT mr.*, po.order_no as production_order_no
            FROM material_requisitions mr
            LEFT JOIN stock_production_orders po ON mr.production_order_id = po.id
            WHERE mr.production_order_id = ? AND mr.company_id = ?
            ORDER BY mr.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $production_order_id, $company_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    $requisitions = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $requisitions[] = $row;
    }
    
    echo json_encode($requisitions);
    exit;
}

if ($action == 'approve_material_requisition') {
    header('Content-Type: application/json');
    $requisition_id = $_POST['requisition_id'] ?? 0;
    $approved_by = $_POST['approved_by'] ?? $_SESSION['user_login'];
    
    mysqli_begin_transaction($conn);
    try {
        // Update requisition status
        $sql = "UPDATE material_requisitions 
                SET status = 'approved', approved_by = ?, approved_date = NOW() 
                WHERE id = ? AND company_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $approved_by, $requisition_id, $company_id);
        mysqli_stmt_execute($stmt);
        
        // Get requisition items
        $items_sql = "SELECT * FROM material_requisition_items WHERE requisition_id = ?";
        $items_stmt = mysqli_prepare($conn, $items_sql);
        mysqli_stmt_bind_param($items_stmt, "i", $requisition_id);
        mysqli_stmt_execute($items_stmt);
        $items_res = mysqli_stmt_get_result($items_stmt);
        
        // Deduct stock from warehouses
        while ($item = mysqli_fetch_assoc($items_res)) {
            $product_id = $item['product_id'];
            $qty_needed = $item['qty_requested'];
            
            // Find warehouses with sufficient stock
            $warehouse_sql = "SELECT w.id, w.name, 
                              SUM(CASE WHEN t.type='in' THEN t.qty ELSE -t.qty END) as balance
                              FROM stock_warehouses w
                              LEFT JOIN stock_transactions t ON w.id = t.warehouse_id AND t.product_id = ?
                              WHERE w.company_id = ?
                              GROUP BY w.id
                              HAVING balance > 0
                              ORDER BY balance DESC";
            $warehouse_stmt = mysqli_prepare($conn, $warehouse_sql);
            mysqli_stmt_bind_param($warehouse_stmt, "ii", $product_id, $company_id);
            mysqli_stmt_execute($warehouse_stmt);
            $warehouse_res = mysqli_stmt_get_result($warehouse_stmt);
            
            $remaining_qty = $qty_needed;
            while ($warehouse = mysqli_fetch_assoc($warehouse_res)) {
                if ($remaining_qty <= 0) break;
                
                $available = $warehouse['balance'];
                $to_deduct = min($remaining_qty, $available);
                
                // Create stock transaction (out)
                $trans_sql = "INSERT INTO stock_transactions 
                              (company_id, product_id, warehouse_id, type, qty, note, transaction_date) 
                              VALUES (?, ?, ?, 'out', ?, ?, NOW())";
                $trans_stmt = mysqli_prepare($conn, $trans_sql);
                $note = "เบิกจ่ายตามใบเบิก: " . $requisition_id;
                mysqli_stmt_bind_param($trans_stmt, "iiids", $company_id, $product_id, $warehouse['id'], $to_deduct, $note);
                mysqli_stmt_execute($trans_stmt);
                
                // Update requisition item
                $update_item_sql = "UPDATE material_requisition_items 
                                    SET qty_issued = qty_issued + ?, warehouse_id = ?
                                    WHERE id = ?";
                $update_item_stmt = mysqli_prepare($conn, $update_item_sql);
                mysqli_stmt_bind_param($update_item_stmt, "dii", $to_deduct, $warehouse['id'], $item['id']);
                mysqli_stmt_execute($update_item_stmt);
                
                $remaining_qty -= $to_deduct;
            }
            
            if ($remaining_qty > 0) {
                throw new Exception("สินค้า ID $product_id มีสต็อกไม่เพียงพอ (ขาด $remaining_qty)");
            }
        }
        
        // Update requisition to completed
        $complete_sql = "UPDATE material_requisitions SET status = 'completed' WHERE id = ?";
        $complete_stmt = mysqli_prepare($conn, $complete_sql);
        mysqli_stmt_bind_param($complete_stmt, "i", $requisition_id);
        mysqli_stmt_execute($complete_stmt);
        
        mysqli_commit($conn);
        logStockAction($conn, $company_id, "อนุมัติและตัดสต็อกใบเบิกจ่าย ID: $requisition_id", 'update');
        
        echo json_encode(['status' => 'success', 'message' => 'อนุมัติและตัดสต็อกเรียบร้อยแล้ว']);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action == 'reject_material_requisition') {
    header('Content-Type: application/json');
    $requisition_id = $_POST['requisition_id'] ?? 0;
    
    $sql = "UPDATE material_requisitions SET status = 'rejected' WHERE id = ? AND company_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $requisition_id, $company_id);
    
    if (mysqli_stmt_execute($stmt)) {
        logStockAction($conn, $company_id, "ปฏิเสธใบเบิกจ่าย ID: $requisition_id", 'update');
        echo json_encode(['status' => 'success', 'message' => 'ปฏิเสธใบเบิกจ่ายเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}
?>

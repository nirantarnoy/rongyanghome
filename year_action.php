<?php
require 'auth_check.php';
include 'config.php';

$user_role = $_SESSION['user_role'] ?? 'user';
$company_id = $_SESSION['company_id'];

// Only admin can change year
if ($user_role !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ในการเปลี่ยนปี']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action == 'change_year') {
    $year = (int)$_POST['year'];
    
    // Validate year
    if ($year < 2020 || $year > 2050) {
        echo json_encode(['status' => 'error', 'message' => 'ปีที่เลือกไม่ถูกต้อง']);
        exit();
    }
    
    // Check if year_settings exists for this company
    $check_sql = "SELECT id FROM year_settings WHERE company_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $company_id);
    mysqli_stmt_execute($check_stmt);
    $check_res = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_res) > 0) {
        // Update existing
        $update_sql = "UPDATE year_settings SET active_year = ? WHERE company_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ii", $year, $company_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Update session
            $_SESSION['active_year'] = $year;
            echo json_encode(['status' => 'success', 'message' => "เปลี่ยนปีทำงานเป็น $year เรียบร้อยแล้ว"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    } else {
        // Insert new
        $insert_sql = "INSERT INTO year_settings (company_id, active_year) VALUES (?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "ii", $company_id, $year);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            // Update session
            $_SESSION['active_year'] = $year;
            echo json_encode(['status' => 'success', 'message' => "เปลี่ยนปีทำงานเป็น $year เรียบร้อยแล้ว"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
}

if ($action == 'get_current_year') {
    $year_sql = "SELECT active_year FROM year_settings WHERE company_id = ?";
    $year_stmt = mysqli_prepare($conn, $year_sql);
    mysqli_stmt_bind_param($year_stmt, "i", $company_id);
    mysqli_stmt_execute($year_stmt);
    $year_res = mysqli_stmt_get_result($year_stmt);
    $year_data = mysqli_fetch_assoc($year_res);
    
    $active_year = $year_data['active_year'] ?? date('Y');
    echo json_encode(['status' => 'success', 'year' => $active_year]);
}
?>

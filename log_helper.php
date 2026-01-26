<?php
/**
 * Log Activity Helper Functions
 * ใช้สำหรับบันทึกกิจกรรมทุก module
 */

/**
 * บันทึก action log
 * 
 * @param mysqli $conn Database connection
 * @param string $module ชื่อ module (stock, quotation, project, etc.)
 * @param string $activity รายละเอียดกิจกรรม
 * @param string $action_type ประเภท (create, update, delete, view)
 * @param int|null $reference_id ID ของข้อมูลที่เกี่ยวข้อง
 * @return bool
 */
function logActivity($conn, $module, $activity, $action_type = 'create', $reference_id = null) {
    // ตรวจสอบว่ามี session หรือไม่
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $company_id = $_SESSION['company_id'] ?? 0;
    $user_id = $_SESSION['user_id'] ?? 0;
    $year = $_SESSION['active_year'] ?? (int)date('Y');
    
    // ถ้าไม่มี company_id หรือ user_id ให้ข้าม
    if (!$company_id || !$user_id) {
        return false;
    }
    
    $sql = "INSERT INTO action_logs (company_id, user_id, module, activity, action_type, reference_id, year) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "iisssii", $company_id, $user_id, $module, $activity, $action_type, $reference_id, $year);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * บันทึก log สำหรับ Stock
 */
function logStock($conn, $activity, $action_type = 'create', $reference_id = null) {
    return logActivity($conn, 'stock', $activity, $action_type, $reference_id);
}

/**
 * บันทึก log สำหรับ Quotation
 */
function logQuotation($conn, $activity, $action_type = 'create', $reference_id = null) {
    return logActivity($conn, 'quotation', $activity, $action_type, $reference_id);
}

/**
 * บันทึก log สำหรับ Project
 */
function logProject($conn, $activity, $action_type = 'create', $reference_id = null) {
    return logActivity($conn, 'project', $activity, $action_type, $reference_id);
}

/**
 * บันทึก log สำหรับ Transaction
 */
function logTransaction($conn, $activity, $action_type = 'create', $reference_id = null) {
    return logActivity($conn, 'transaction', $activity, $action_type, $reference_id);
}

/**
 * ดึง logs ตาม module และ company
 * 
 * @param mysqli $conn Database connection
 * @param string|null $module ชื่อ module (null = ทั้งหมด)
 * @param int $limit จำนวนที่ต้องการดึง
 * @return array
 */
function getActivityLogs($conn, $module = null, $limit = 100) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $company_id = $_SESSION['company_id'] ?? 0;
    $year = $_SESSION['active_year'] ?? (int)date('Y');
    
    if (!$company_id) {
        return [];
    }
    
    $sql = "SELECT l.*, u.full_name, u.username 
            FROM action_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            WHERE l.company_id = ? AND l.year = ?";
    
    if ($module) {
        $sql .= " AND l.module = ?";
    }
    
    $sql .= " ORDER BY l.created_at DESC LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }
    
    if ($module) {
        mysqli_stmt_bind_param($stmt, "iisi", $company_id, $year, $module, $limit);
    } else {
        mysqli_stmt_bind_param($stmt, "iii", $company_id, $year, $limit);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    return $logs;
}
?>

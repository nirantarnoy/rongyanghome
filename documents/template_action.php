<?php
require '../auth_check.php';
include '../config.php';

$action = $_REQUEST['action'] ?? '';
$company_id = $_SESSION['company_id'] ?? 0;

// Get templates
if ($action == 'get_templates') {
    $type = $_GET['type'] ?? 'all';
    
    if ($type == 'all') {
        $sql = "SELECT * FROM quotation_templates WHERE company_id = ? ORDER BY template_type, template_name";
    } else {
        $sql = "SELECT * FROM quotation_templates WHERE company_id = ? AND template_type = ? ORDER BY template_name";
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($type == 'all') {
        mysqli_stmt_bind_param($stmt, "i", $company_id);
    } else {
        mysqli_stmt_bind_param($stmt, "is", $company_id, $type);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $templates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $templates[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'data' => $templates]);
    exit;
}

// Save template
if ($action == 'save_template') {
    $id = $_POST['id'] ?? null;
    $template_type = mysqli_real_escape_string($conn, $_POST['template_type']);
    $template_name = mysqli_real_escape_string($conn, $_POST['template_name']);
    $template_content = mysqli_real_escape_string($conn, $_POST['template_content']);
    
    if ($id) {
        // Update
        $sql = "UPDATE quotation_templates SET 
                template_name = '$template_name',
                template_content = '$template_content'
                WHERE id = $id AND company_id = $company_id";
    } else {
        // Insert
        $sql = "INSERT INTO quotation_templates (company_id, template_type, template_name, template_content)
                VALUES ($company_id, '$template_type', '$template_name', '$template_content')";
    }
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'บันทึกเทมเพลตเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

// Delete template
if ($action == 'delete_template') {
    $id = (int)$_POST['id'];
    $sql = "DELETE FROM quotation_templates WHERE id = $id AND company_id = $company_id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'ลบเทมเพลตเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}
?>

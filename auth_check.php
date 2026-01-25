<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_login'])) {
    header("Location: /login.php");
    exit();
}

// Module Access Control
$current_path = $_SERVER['PHP_SELF'];
$allowed_modules = isset($_SESSION['allowed_modules']) ? explode(',', $_SESSION['allowed_modules']) : [];
$user_role = $_SESSION['user_role'] ?? 'user';

// Admin role has access to everything
if ($user_role === 'admin') {
    // Access granted
} else {
    $is_allowed = false;
    
    // Check if accessing a module directory
    if (strpos($current_path, '/stock/') !== false) {
        if (in_array('stock', $allowed_modules)) $is_allowed = true;
    } elseif (strpos($current_path, '/projects/') !== false) {
        if (in_array('projects', $allowed_modules)) $is_allowed = true;
    } elseif (strpos($current_path, '/companytransaction/') !== false) {
        if (in_array('companytransaction', $allowed_modules)) $is_allowed = true;
    } elseif (strpos($current_path, '/user.php') !== false || strpos($current_path, '/company.php') !== false || strpos($current_path, '/dashboard.php') !== false) {
        // These are usually admin-only or main system files
        if (in_array('admin', $allowed_modules)) $is_allowed = true;
    } else {
        // Root files or other files
        $is_allowed = true; // Default allow for now, or refine as needed
    }

    if (!$is_allowed) {
        // Redirect to their first allowed module or logout if none
        if (count($allowed_modules) > 0) {
            $first = trim($allowed_modules[0]);
            if ($first == 'stock') header("Location: /stock/index.php");
            elseif ($first == 'projects') header("Location: /projects/index.php");
            elseif ($first == 'companytransaction') header("Location: /companytransaction/index.php");
            else header("Location: /dashboard.php");
        } else {
            // No modules allowed, maybe they only have admin access but role is user? 
            // Or just send to dashboard which might show "Access Denied"
            header("Location: /dashboard.php?error=access_denied");
        }
        exit();
    }
}
?>

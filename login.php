<?php
session_start();
include 'config.php';

// Ensure allowed_modules column exists
$checkColumnSQL = "SHOW COLUMNS FROM users LIKE 'allowed_modules'";
$columnExists = mysqli_query($conn, $checkColumnSQL);
if (mysqli_num_rows($columnExists) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN allowed_modules TEXT");
}

// Fetch companies for selection
$companySql = "SELECT id, company_name FROM company ORDER BY company_name ASC";
$companyResult = mysqli_query($conn, $companySql);
$companies = [];
if ($companyResult) {
    while ($row = mysqli_fetch_assoc($companyResult)) {
        $companies[] = $row;
    }
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $company_id = (int)($_POST['company_id'] ?? 0);
    
    // Fetch user from database
    $userSql = "SELECT * FROM users WHERE username = '$username' AND company_id = $company_id";
    $userResult = mysqli_query($conn, $userSql);
    
    if ($userResult && mysqli_num_rows($userResult) > 0) {
        $user = mysqli_fetch_assoc($userResult);
        
        // Verify password
        $is_valid = false;
        if (password_verify($password, $user['password'])) {
            $is_valid = true;
        } elseif ($password === $user['password']) {
            // Fallback for plain text passwords (if any exist)
            $is_valid = true;
            // Optionally update to hash now
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password = '$newHash' WHERE id = {$user['id']}");
        }

        if ($is_valid) {
            // Find company name for session
            $selected_company_name = '';
            foreach ($companies as $c) {
                if ($c['id'] == $company_id) {
                    $selected_company_name = $c['company_name'];
                    break;
                }
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['company_id'] = $company_id;
            $_SESSION['company_name'] = $selected_company_name;
            $_SESSION['allowed_modules'] = $user['allowed_modules'] ?: 'admin,stock,projects,companytransaction'; // Default for old users
            
            // Log Login Activity
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $log_sql = "INSERT INTO login_logs (user_id, company_id, username, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "iisss", $user['id'], $company_id, $user['username'], $ip_address, $user_agent);
            mysqli_stmt_execute($log_stmt);
            
            // Redirect based on allowed modules
            $allowed_str = $_SESSION['allowed_modules'];
            $allowed = explode(',', $allowed_str);
            $allowed = array_filter($allowed); // Remove empty values

            if ($user['role'] == 'admin' || in_array('admin', $allowed)) {
                header("Location: dashboard.php");
            } elseif (count($allowed) > 0) {
                $firstModule = trim($allowed[0]);
                if ($firstModule == 'stock') {
                    header("Location: stock/index.php");
                } elseif ($firstModule == 'projects') {
                    header("Location: projects/index.php");
                } elseif ($firstModule == 'companytransaction') {
                    header("Location: companytransaction/index.php");
                } else {
                    header("Location: dashboard.php");
                }
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบผู้ใช้งานในบริษัทที่เลือก";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - RONGYANG HOME</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-sm w-full bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        
        <!-- Header -->
        <div class="pt-8 pb-6 px-8 text-center">
            <div class="inline-flex items-center justify-center mb-4">
                <img src="assets/logo/logo.png" alt="Logo" class="h-16 w-auto" onerror="this.src='https://via.placeholder.com/150?text=LOGO'">
            </div>
            <h2 class="text-xl font-bold text-gray-800">ยินดีต้อนรับ</h2>
            <p class="text-gray-500 text-sm mt-1">กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
        </div>

        <!-- Form -->
        <div class="px-8 pb-8">
            <?php if (isset($error)): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-100 text-red-600 text-sm rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            <form action="" method="POST" onsubmit="return handleLogin(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">บริษัท <span class="text-red-500">*</span></label>
                        <select name="company_id" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all text-gray-700 text-sm">
                            <option value="">-- เลือกบริษัท --</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">ชื่อผู้ใช้งาน</label>
                        <input type="text" name="username" value="admin" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all text-gray-700 text-sm placeholder-gray-400"
                                placeholder="Username">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wide">รหัสผ่าน</label>
                        <input type="password" name="password" id="password" value="123456" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all text-gray-700 text-sm placeholder-gray-400"
                                placeholder="Password">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="remember_me" id="remember_me" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                        <label for="remember_me" class="ml-2 text-sm text-gray-600 cursor-pointer select-none">จดจำการเข้าระบบ</label>
                    </div>
                </div>

                <button type="submit" 
                        class="w-full mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg shadow-sm hover:shadow transition-all duration-200 flex items-center justify-center gap-2 text-sm">
                    <span>เข้าสู่ระบบ</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400">Rongyang Home Management System</p>
        </div>
    </div>

    <script>
        // Load saved credentials on page load
        window.addEventListener('DOMContentLoaded', function() {
            const savedCompany = getCookie('saved_company');
            const savedUsername = getCookie('saved_username');
            
            if (savedCompany && savedUsername) {
                document.querySelector('select[name="company_id"]').value = savedCompany;
                document.querySelector('input[name="username"]').value = savedUsername;
                document.getElementById('remember_me').checked = true;
            }
        });

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        function setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
            document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
        }

        function deleteCookie(name) {
            document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/`;
        }

        function handleLogin(e) {
            const form = e.target;
            const rememberMe = document.getElementById('remember_me').checked;
            const companyId = form.querySelector('select[name="company_id"]').value;
            const username = form.querySelector('input[name="username"]').value;

            if (rememberMe) {
                setCookie('saved_company', companyId, 30);
                setCookie('saved_username', username, 30);
            } else {
                deleteCookie('saved_company');
                deleteCookie('saved_username');
            }

            const btn = form.querySelector('button');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังตรวจสอบ...';
            btn.classList.add('opacity-75');
            return true; // Let the form submit normally
        }
    </script>
</body>
</html>

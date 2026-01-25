<?php
session_start();
include 'config.php';

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
    $username = $_POST['username'] ?? '';
    $company_id = $_POST['company_id'] ?? '';
    
    // Find company name for session
    $selected_company_name = '';
    foreach ($companies as $c) {
        if ($c['id'] == $company_id) {
            $selected_company_name = $c['company_name'];
            break;
        }
    }

    // Simply set session and redirect
    $_SESSION['user_login'] = $username;
    $_SESSION['company_id'] = $company_id;
    $_SESSION['company_name'] = $selected_company_name;
    
    header("Location: dashboard.php");
    exit();
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
                        <input type="password" name="password" value="123456" required
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all text-gray-700 text-sm placeholder-gray-400"
                               placeholder="Password">
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
        function handleLogin(e) {
            const btn = e.target.querySelector('button');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> กำลังตรวจสอบ...';
            btn.classList.add('opacity-75');
            return true; // Let the form submit normally
        }
    </script>
</body>
</html>

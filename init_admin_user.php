<?php
/**
 * Init Admin User Script
 * สคริปต์สำหรับสร้าง admin user เริ่มต้น
 * 
 * Username: admin
 * Password: 123456
 * Company ID: 1
 * Role: admin
 */

include 'config.php';

function initAdminUser($conn) {
    // ตรวจสอบว่าตาราง users มีอยู่หรือไม่ ถ้าไม่มีให้สร้าง
    $createUserTableSQL = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        role ENUM('admin', 'user') DEFAULT 'user',
        company_id INT,
        allowed_modules TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!mysqli_query($conn, $createUserTableSQL)) {
        echo "⚠️ Warning: " . mysqli_error($conn) . "\n";
    }
    
    // แปลง charset
    mysqli_query($conn, "ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // ตรวจสอบว่ามี column allowed_modules หรือไม่
    $checkColumnSQL = "SHOW COLUMNS FROM users LIKE 'allowed_modules'";
    $columnExists = mysqli_query($conn, $checkColumnSQL);
    if (mysqli_num_rows($columnExists) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN allowed_modules TEXT");
    }
    
    // ข้อมูล admin user
    $username = 'admin';
    $password_raw = '123456';
    $full_name = 'System Administrator';
    $role = 'admin';
    $company_id = 1;
    $allowed_modules = 'admin,stock,projects,companytransaction'; // อนุญาตทุก module
    
    // เข้ารหัสรหัสผ่าน
    $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
    
    // ตรวจสอบว่ามี username 'admin' อยู่แล้วหรือไม่
    $checkSql = "SELECT id, username FROM users WHERE username = '$username'";
    $checkRes = mysqli_query($conn, $checkSql);
    
    if (mysqli_num_rows($checkRes) > 0) {
        $existingUser = mysqli_fetch_assoc($checkRes);
        echo "❌ ผู้ใช้งาน 'admin' มีอยู่ในระบบแล้ว (ID: {$existingUser['id']})\n";
        echo "ℹ️  หากต้องการรีเซ็ตรหัสผ่าน กรุณาใช้ฟังก์ชัน resetAdminPassword()\n";
        return false;
    }
    
    // สร้าง admin user
    $sql = "INSERT INTO users (username, password, full_name, role, company_id, allowed_modules) 
            VALUES ('$username', '$password_hash', '$full_name', '$role', $company_id, '$allowed_modules')";
    
    if (mysqli_query($conn, $sql)) {
        $new_id = mysqli_insert_id($conn);
        echo "✅ สร้างผู้ใช้งาน Admin เรียบร้อยแล้ว!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 รายละเอียด:\n";
        echo "   • ID: $new_id\n";
        echo "   • Username: $username\n";
        echo "   • Password: $password_raw\n";
        echo "   • Full Name: $full_name\n";
        echo "   • Role: $role\n";
        echo "   • Company ID: $company_id\n";
        echo "   • Allowed Modules: $allowed_modules\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "⚠️  กรุณาเปลี่ยนรหัสผ่านหลังจากเข้าสู่ระบบครั้งแรก\n";
        return true;
    } else {
        echo "❌ เกิดข้อผิดพลาด: " . mysqli_error($conn) . "\n";
        return false;
    }
}

function resetAdminPassword($conn) {
    $username = 'admin';
    $password_raw = '123456';
    $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);
    
    // ตรวจสอบว่ามี username 'admin' หรือไม่
    $checkSql = "SELECT id FROM users WHERE username = '$username'";
    $checkRes = mysqli_query($conn, $checkSql);
    
    if (mysqli_num_rows($checkRes) == 0) {
        echo "❌ ไม่พบผู้ใช้งาน 'admin' ในระบบ\n";
        echo "ℹ️  กรุณาใช้ฟังก์ชัน initAdminUser() เพื่อสร้างผู้ใช้งานใหม่\n";
        return false;
    }
    
    $user = mysqli_fetch_assoc($checkRes);
    $user_id = $user['id'];
    
    // รีเซ็ตรหัสผ่าน
    $sql = "UPDATE users SET password = '$password_hash' WHERE username = '$username'";
    
    if (mysqli_query($conn, $sql)) {
        echo "✅ รีเซ็ตรหัสผ่านเรียบร้อยแล้ว!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 รายละเอียด:\n";
        echo "   • User ID: $user_id\n";
        echo "   • Username: $username\n";
        echo "   • New Password: $password_raw\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        return true;
    } else {
        echo "❌ เกิดข้อผิดพลาด: " . mysqli_error($conn) . "\n";
        return false;
    }
}

// ตรวจสอบว่าเรียกใช้งานผ่าน command line หรือ browser
if (php_sapi_name() === 'cli') {
    // Command Line Interface
    echo "\n";
    echo "╔════════════════════════════════════════╗\n";
    echo "║   Init Admin User Script               ║\n";
    echo "╚════════════════════════════════════════╝\n";
    echo "\n";
    
    // ถ้ามี argument
    if (isset($argv[1])) {
        if ($argv[1] === 'reset') {
            resetAdminPassword($conn);
        } else {
            echo "❌ คำสั่งไม่ถูกต้อง\n";
            echo "ℹ️  ใช้งาน: php init_admin_user.php [reset]\n";
        }
    } else {
        initAdminUser($conn);
    }
    
    echo "\n";
} else {
    // Web Browser
    echo "<!DOCTYPE html>";
    echo "<html lang='th'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Init Admin User</title>";
    echo "<style>";
    echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }";
    echo ".container { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 600px; width: 90%; }";
    echo "h1 { color: #667eea; margin-top: 0; font-size: 28px; border-bottom: 3px solid #667eea; padding-bottom: 15px; }";
    echo "pre { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; overflow-x: auto; line-height: 1.6; }";
    echo ".btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; transition: all 0.3s; border: none; cursor: pointer; font-size: 16px; }";
    echo ".btn:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }";
    echo ".btn-secondary { background: #6c757d; }";
    echo ".btn-secondary:hover { background: #5a6268; }";
    echo ".actions { margin-top: 30px; padding-top: 20px; border-top: 2px solid #e9ecef; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='container'>";
    echo "<h1>🔐 Init Admin User</h1>";
    echo "<pre>";
    
    // ตรวจสอบ action
    $action = $_GET['action'] ?? 'init';
    
    if ($action === 'reset') {
        resetAdminPassword($conn);
    } else {
        initAdminUser($conn);
    }
    
    echo "</pre>";
    echo "<div class='actions'>";
    echo "<a href='init_admin_user.php' class='btn'>🔄 สร้าง Admin User</a>";
    echo "<a href='init_admin_user.php?action=reset' class='btn btn-secondary'>🔑 รีเซ็ตรหัสผ่าน</a>";
    echo "<a href='login.php' class='btn btn-secondary'>🏠 กลับหน้า Login</a>";
    echo "</div>";
    echo "</div>";
    echo "</body>";
    echo "</html>";
}

mysqli_close($conn);
?>

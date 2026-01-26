# คู่มือการใช้งาน Action Logs System

## ภาพรวม
ระบบบันทึกกิจกรรม (Action Logs) จะบันทึกทุกการกระทำในระบบ เช่น สร้าง แก้ไข ลบข้อมูล ในทุก module

## การติดตั้ง

### 1. สร้างตาราง action_logs
```bash
php create_unified_action_logs.php
```

หรือรัน SQL:
```sql
CREATE TABLE IF NOT EXISTS action_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NOT NULL,
    module VARCHAR(50) NOT NULL COMMENT 'stock, quotation, project, etc.',
    activity TEXT NOT NULL,
    action_type ENUM('create', 'update', 'delete', 'view') NOT NULL,
    reference_id INT DEFAULT NULL COMMENT 'ID of the affected record',
    year INT NOT NULL DEFAULT 2026,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_module (module),
    INDEX idx_year (year),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. ใช้งาน Log Helper ในแต่ละ Module

#### ตัวอย่าง: Quotation Module
```php
require_once '../log_helper.php';

// เมื่อสร้างใบเสนอราคา
logQuotation($conn, "สร้างใบเสนอราคา: $doc_number", 'create', $quotation_id);

// เมื่อแก้ไข
logQuotation($conn, "แก้ไขใบเสนอราคา: $doc_number", 'update', $quotation_id);

// เมื่อลบ
logQuotation($conn, "ลบใบเสนอราคา: $doc_number", 'delete', $quotation_id);
```

#### ตัวอย่าง: Stock Module
```php
require_once '../log_helper.php';

logStock($conn, "เพิ่มสินค้า: $product_name", 'create', $product_id);
logStock($conn, "แก้ไขสินค้า: $product_name", 'update', $product_id);
logStock($conn, "ลบสินค้า: $product_name", 'delete', $product_id);
```

#### ตัวอย่าง: Project Module
```php
require_once '../log_helper.php';

logProject($conn, "สร้างโครงการ: $project_name", 'create', $project_id);
logProject($conn, "อัพเดทโครงการ: $project_name", 'update', $project_id);
```

## Functions ที่มีให้ใช้งาน

### 1. logActivity()
```php
logActivity($conn, $module, $activity, $action_type, $reference_id);
```
- `$module`: ชื่อ module (stock, quotation, project, transaction)
- `$activity`: รายละเอียดกิจกรรม
- `$action_type`: create, update, delete, view
- `$reference_id`: ID ของข้อมูลที่เกี่ยวข้อง (optional)

### 2. logStock(), logQuotation(), logProject(), logTransaction()
```php
logStock($conn, $activity, $action_type, $reference_id);
```
Shortcut functions สำหรับแต่ละ module

### 3. getActivityLogs()
```php
$logs = getActivityLogs($conn, 'quotation', 100); // ดึง 100 รายการล่าสุดของ quotation
$all_logs = getActivityLogs($conn, null, 200); // ดึงทุก module
```

## การดู Logs

### ในหน้า Admin
1. ไปที่เมนู "ประวัติกิจกรรมในระบบ" (`action_logs.php`)
2. จะแสดงกิจกรรมทั้งหมดของทุก module
3. แสดงข้อมูล:
   - วันที่-เวลา
   - ผู้ใช้งาน
   - Module (Stock, Quotation, Project, etc.)
   - ประเภท (CREATE, UPDATE, DELETE)
   - รายละเอียดกิจกรรม

## Module ที่รองรับ

- ✅ **Quotation** - บันทึกครบแล้ว
- ⚠️ **Stock** - ใช้ตารางเก่า `stock_action_logs` (จะ migrate อัตโนมัติ)
- ❌ **Project** - ยังไม่มีการบันทึก (ต้องเพิ่ม)
- ❌ **Transaction** - ยังไม่มีการบันทึก (ต้องเพิ่ม)

## TODO: เพิ่ม Logging ใน Module อื่นๆ

### Projects Module
แก้ไขไฟล์ `projects/project_action.php`:
```php
require_once '../log_helper.php';

// ใน action create
logProject($conn, "สร้างโครงการ: $project_name", 'create', $project_id);

// ใน action update  
logProject($conn, "แก้ไขโครงการ: $project_name", 'update', $project_id);

// ใน action delete
logProject($conn, "ลบโครงการ: $project_name", 'delete', $project_id);
```

### Stock Module
แก้ไขไฟล์ `stock/stock_action.php`:
```php
// เปลี่ยนจาก
$sql = "INSERT INTO stock_action_logs ...";

// เป็น
require_once '../log_helper.php';
logStock($conn, $activity, $action_type, $reference_id);
```

## ข้อดีของระบบใหม่

1. ✅ รวมศูนย์ในตารางเดียว
2. ✅ แยกตาม module ชัดเจน
3. ✅ รองรับ year management
4. ✅ มี reference_id เชื่อมโยงกับข้อมูลหลัก
5. ✅ ใช้งานง่าย ผ่าน helper functions
6. ✅ Query เร็วขึ้น (มี indexes)

## Troubleshooting

### ปัญหา: ไม่มีข้อมูล log แสดง
**แก้ไข**: ตรวจสอบว่ารันไฟล์ `create_unified_action_logs.php` แล้ว

### ปัญหา: Log ไม่บันทึก
**แก้ไข**: ตรวจสอบว่า:
1. มี `require_once '../log_helper.php';` ในไฟล์ action
2. เรียกใช้ function `logXXX()` หลังจาก query สำเร็จ
3. มี session `user_id` และ `company_id`

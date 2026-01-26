# คู่มือการใช้งานระบบจัดการปี (Year Management System)

## ภาพรวม
ระบบจัดการปีช่วยให้คุณสามารถแยกข้อมูลการทำงานตามปีได้ เหมาะสำหรับการจัดเก็บข้อมูลประจำปีและสามารถย้อนกลับไปดูข้อมูลปีก่อนหน้าได้

## การติดตั้ง

### 1. สร้างตารางและเพิ่มคอลัมน์

#### ขั้นตอนที่ 1: สร้างตาราง year_settings
```bash
php create_year_settings_table.php
```

หรือรัน SQL ใน phpMyAdmin:
```sql
CREATE TABLE IF NOT EXISTS year_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    active_year INT NOT NULL DEFAULT 2026,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_company (company_id),
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### ขั้นตอนที่ 2: เพิ่มคอลัมน์ year ในทุกตาราง
รัน SQL script: `add_year_columns.sql` ใน phpMyAdmin

### 2. อัพเดทโค้ดในแต่ละ Module

สำหรับทุก module (stock, projects, companytransaction) ให้เพิ่มการกรองข้อมูลตาม year:

#### ตัวอย่าง: Stock Module
```php
// ดึงปีปัจจุบันจาก session
$active_year = $_SESSION['active_year'] ?? date('Y');

// เพิ่ม WHERE clause ในทุก query
$sql = "SELECT * FROM stock_products 
        WHERE company_id = ? AND year = ? 
        ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $company_id, $active_year);
```

#### ตัวอย่าง: การบันทึกข้อมูลใหม่
```php
// เมื่อสร้างข้อมูลใหม่ ให้บันทึก year ด้วย
$active_year = $_SESSION['active_year'] ?? date('Y');

$sql = "INSERT INTO stock_products (company_id, year, product_name, ...) 
        VALUES (?, ?, ?, ...)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iis...", $company_id, $active_year, $product_name, ...);
```

## การใช้งาน

### สำหรับ Admin

1. **เข้าสู่หน้าจัดการปี**
   - คลิกที่ปีปัจจุบันใน navbar (เช่น "2026")
   - หรือไปที่ `year_management.php`

2. **เปลี่ยนปีทำงาน**
   - เลือกปีที่ต้องการจากตัวเลือก
   - ยืนยันการเปลี่ยนปี
   - ระบบจะรีโหลดและแสดงข้อมูลของปีที่เลือก

3. **ดูสถิติข้อมูล**
   - ในหน้าจัดการปีจะแสดงจำนวนข้อมูลแต่ละปี
   - แยกตาม module: Stock, Quotations, Projects

### สำหรับ User ทั่วไป

- ผู้ใช้ทั่วไปจะเห็นเฉพาะข้อมูลของปีที่ admin กำหนด
- ไม่สามารถเปลี่ยนปีได้เอง

## ข้อควรระวัง

1. **การเปลี่ยนปี**
   - เมื่อเปลี่ยนปี ข้อมูลทั้งหมดจะแสดงเฉพาะปีที่เลือก
   - ข้อมูลปีอื่นยังคงอยู่ในฐานข้อมูล แต่ไม่แสดง

2. **การสร้างข้อมูลใหม่**
   - ข้อมูลใหม่จะถูกบันทึกในปีที่กำลังใช้งานอยู่
   - ตรวจสอบให้แน่ใจว่าเลือกปีที่ถูกต้องก่อนสร้างข้อมูล

3. **การย้ายข้อมูลระหว่างปี**
   - ไม่สามารถย้ายข้อมูลระหว่างปีได้โดยตรง
   - ต้องแก้ไขในฐานข้อมูลโดยตรง

## Troubleshooting

### ปัญหา: ไม่เห็นข้อมูลหลังเปลี่ยนปี
**แก้ไข**: ตรวจสอบว่า query ในแต่ละ module มีการกรอง `year` แล้ว

### ปัญหา: ข้อมูลเก่าหายหมด
**แก้ไข**: ข้อมูลไม่ได้หาย เพียงแต่ถูกกรองตามปี ให้เปลี่ยนกลับไปปีเดิม

### ปัญหา: Session active_year ไม่อัพเดท
**แก้ไข**: ลอง logout แล้ว login ใหม่

## ไฟล์ที่เกี่ยวข้อง

- `create_year_settings_table.php` - สร้างตาราง year_settings
- `add_year_columns.sql` - เพิ่มคอลัมน์ year ในทุกตาราง
- `year_management.php` - หน้าจัดการปี (admin only)
- `year_action.php` - API สำหรับเปลี่ยนปี
- `auth_check.php` - โหลดปีปัจจุบันเข้า session
- `navbar.php` - แสดงปีปัจจุบันและลิงก์จัดการปี

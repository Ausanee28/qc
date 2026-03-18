# Home Machine Handover (2026-03-18)

เอกสารนี้ไว้ส่งต่อให้ Codex/ทีมบนเครื่องบ้าน เพื่อให้ใช้งานได้เหมือนเครื่องที่ทำงาน โดยใช้ DB ของเครื่องบ้านเอง

## สรุปสิ่งที่ทำวันนี้

- รองรับกรณี DB มีหรือไม่มี `deleted_at` ได้ดีขึ้น
- แก้ปัญหา query `deleted_at` ambiguous บน Dashboard
- เพิ่มความปลอดภัยไฟล์ export (`report` filename sanitize)
- เพิ่ม baseline/performance tooling:
  - คำสั่งใหม่ `php artisan qc:baseline --pretty`
  - schedule รายสัปดาห์ใน `routes/console.php`
- เพิ่ม migration index สำหรับข้อมูลโต:
  - `2026_03_18_000004_add_scaling_indexes_to_workflow_tables.php`

## บนเครื่องบ้านต้องทำอะไรบ้าง (หลัง pull โค้ดล่าสุด)

## 1) เตรียม dependency

```powershell
cd C:\qc
composer install
npm install
```

## 2) ตั้งค่า .env ของเครื่องบ้าน

ตรวจว่า `.env` เป็นค่า DB ของบ้าน แล้วสลับ profile ให้ถูก

```powershell
php switch_db.php home
php switch_db.php status
```

ควรเห็น host/port/database เป็นของบ้าน

## 3) migrate schema ให้ครบ

```powershell
php artisan migrate
php artisan migrate:status
```

ต้องเห็น migration ล่าสุดเป็น `Ran` โดยเฉพาะ:

- `2026_03_18_000002_add_soft_deletes_to_workflow_tables`
- `2026_03_18_000004_add_scaling_indexes_to_workflow_tables`

## 4) เคลียร์ cache หลังย้ายเครื่อง/อัปเดตโค้ด

```powershell
php artisan optimize:clear
```

## 5) ทดสอบระบบอัตโนมัติ

```powershell
php artisan test
```

## 6) ทดสอบ baseline command

```powershell
php artisan qc:baseline --pretty
Get-ChildItem C:\qc\storage\app\performance | Sort-Object LastWriteTime -Descending | Select-Object -First 3
```

ต้องมีไฟล์ `baseline-*.json` ถูกสร้าง

## 7) ตั้ง scheduler ให้ทำงานจริงบนเครื่องบ้าน

ถ้ายังไม่มี task:

```powershell
schtasks /Create /TN "Laravel Scheduler" /SC MINUTE /MO 1 /TR "cmd /c cd /d C:\qc && php artisan schedule:run >> storage\logs\scheduler.log 2>&1" /F
```

สั่งรันทดสอบ:

```powershell
schtasks /Run /TN "Laravel Scheduler"
schtasks /Query /TN "Laravel Scheduler" /V /FO LIST
Get-Content C:\qc\storage\logs\scheduler.log -Tail 20
```

ค่าที่ควรเห็น:

- `Last Result: 0`
- `Repeat: Every: 0 Hour(s), 1 Minute(s)`

## ปัญหาที่อาจเจอและวิธีแก้

## A) หา `Deleted records` ไม่เจอ

ตรวจก่อนว่า migration soft delete รันแล้วหรือยัง (`000002`)

## B) Dashboard พังเรื่อง `deleted_at` ambiguous

ต้อง pull โค้ดล่าสุดให้มี patch ใน `DashboardMetricsService` แล้ว clear cache

## C) เทสต์บางตัวพังเพราะ permission ที่ `storage/framework/views`

เป็นปัญหา environment/ACL ของเครื่องนั้น ให้เช็กสิทธิ์โฟลเดอร์ storage และลองรัน terminal แบบสิทธิ์สูงขึ้น

## หมายเหตุสำหรับ Codex บนเครื่องบ้าน

- ใช้ DB ของบ้านเท่านั้น (`switch_db.php home`)
- ห้ามแก้ `.env` ของเครื่องอื่นจากค่าบ้าน
- ก่อน push ให้รันอย่างน้อย:
  - `php artisan test`
  - `php artisan qc:baseline --pretty`


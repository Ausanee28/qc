# Codex Home Execution Runbook (DB Update Included)

เอกสารนี้ให้ Codex ที่บ้านทำตามได้ทันที เพื่อให้โค้ดรันเหมือนเครื่องที่ทำงาน โดยเน้นเรื่องฐานข้อมูลเป็นพิเศษ

## เป้าหมาย

- ใช้ DB profile = `home`
- Schema บ้านต้องเท่ากับที่ branch ล่าสุดต้องการ
- ระบบรันได้ครบ: Laravel + Queue + Vite + Reverb
- Dashboard real-time ใช้งานได้

## ขั้นตอนหลักหลัง git pull

1. เข้าโปรเจกต์และดึงโค้ด

```powershell
cd C:\qc
git pull
```

2. ติดตั้ง dependencies

```powershell
composer install
npm install
```

3. สลับ DB เป็นบ้าน

```powershell
php switch_db.php home
php switch_db.php status
```

ต้องเห็น:
- `Current Profile : home`
- `Host : 127.0.0.1`
- `Database : dbqc` (หรือค่าที่บ้านใช้งานจริง)

4. เคลียร์ cache

```powershell
php artisan optimize:clear
```

5. อัปเดต schema

```powershell
php artisan migrate
php artisan migrate:status
```

## สิ่งที่ DB บ้านต้องมี (สำคัญ)

Codex ที่บ้านต้องตรวจให้ครบว่า DB บ้านมีสิ่งเหล่านี้:

1. ตาราง workflow รองรับ soft delete
- `Transaction_Header.deleted_at`
- `Transaction_Detail.deleted_at`

2. migration สำคัญต้องเป็น `Ran`
- `2026_03_18_000002_add_soft_deletes_to_workflow_tables`
- `2026_03_18_000004_add_scaling_indexes_to_workflow_tables`

3. ถ้าใช้ queue แบบ database (`QUEUE_CONNECTION=database`) ต้องมีตาราง
- `jobs`
- `failed_jobs`
- `job_batches`

ถ้าขาด queue tables ให้รัน:

```powershell
php artisan queue:table
php artisan queue:failed-table
php artisan queue:batches-table
php artisan migrate
```

## ค่า .env ที่เกี่ยวกับ DB และ real-time

ขั้นต่ำต้องมี:

```env
DB_PROFILE=home
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dbqc
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## รันระบบ dev

```powershell
composer run dev
```

ต้องมี process หลัก:
- `php artisan serve`
- `php artisan queue:listen --tries=1 --timeout=0`
- `npm run dev`
- `php artisan reverb:start --host=127.0.0.1 --port=8080`

## วิธีตรวจว่า DB พร้อมจริง

1. เข้าเว็บได้และล็อกอินได้
2. หน้า Receive Job เปิดได้
3. หน้า Execute Test เปิดได้ และ submit ได้ไม่ 429
4. Dashboard แสดงข้อมูลไม่ error SQL
5. ทดสอบเพิ่มข้อมูลจาก Receive/Execute แล้ว Dashboard อีกแท็บอัปเดตเอง

## Troubleshooting ด่วน

### A) ต่อ DB บ้านไม่ได้ (`SQLSTATE[HY000] [2002] ... refused`)

ให้ Codex ที่บ้าน:
1. เปิด MySQL ในเครื่องบ้านก่อน (XAMPP/WAMP/Service)
2. ตรวจพอร์ต 3306 ว่าฟังอยู่
3. รันอีกครั้ง:

```powershell
php switch_db.php status
php artisan migrate:status
```

### B) Submit แล้ว 429

```powershell
php artisan optimize:clear
```

แล้วลองใหม่

### C) Dashboard ไม่ real-time

ตรวจตามลำดับ:
- `reverb` process ต้องรัน
- `.env` ต้องเป็น `BROADCAST_CONNECTION=reverb`
- ปิด/เปิด browser ใหม่หลังแก้ `.env`

## ก่อนเริ่มงานจริง (quality gate)

```powershell
php artisan test
npm run build
```

ผ่านทั้งสองคำสั่งค่อยเริ่มทำงาน/commit

# Codex Home Execution Runbook

เอกสารนี้สำหรับให้ Codex บนเครื่องที่บ้านทำตามแบบอัตโนมัติ เพื่อให้รันโค้ดได้เหมือนเครื่องที่ทำงาน

## เป้าหมาย

- ใช้ฐานข้อมูล `home` ของเครื่องบ้าน
- รันระบบได้ครบ: `Laravel + Queue + Vite + Reverb (WebSocket)`
- Dashboard real-time ทำงาน

## ลำดับงานที่ Codex ต้องทำ (หลัง git pull)

1. เข้าโฟลเดอร์โปรเจกต์

```powershell
cd C:\qc
```

2. อัปเดตโค้ด

```powershell
git pull
```

3. ติดตั้ง dependency

```powershell
composer install
npm install
```

4. สลับฐานข้อมูลเป็นเครื่องบ้าน

```powershell
php switch_db.php home
php switch_db.php status
```

เงื่อนไขที่ต้องผ่าน:
- `DB_PROFILE=home`
- host / database เป็นของเครื่องบ้าน

5. ตรวจ `.env` ให้รองรับ WebSocket

ค่าที่ต้องมีอย่างน้อย:

```env
BROADCAST_CONNECTION=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

6. เคลียร์แคชแอป

```powershell
php artisan optimize:clear
```

7. อัปเดต schema (ถ้ามี migration ใหม่)

```powershell
php artisan migrate
```

8. รันระบบ dev แบบครบ

```powershell
composer run dev
```

โดยสคริปต์นี้ต้องรัน 4 โปรเซส:
- `php artisan serve`
- `php artisan queue:listen --tries=1 --timeout=0`
- `npm run dev`
- `php artisan reverb:start --host=127.0.0.1 --port=8080`

## การยืนยันผลหลังรัน

1. เปิดเว็บ `http://127.0.0.1:8000`
2. ล็อกอินสำเร็จ
3. เปิดหน้า Dashboard ค้างไว้ 2 แท็บ
4. ไปหน้า `Receive Job` หรือ `Execute Test` แล้วบันทึกข้อมูล
5. Dashboard อีกแท็บต้องอัปเดตเอง (ไม่ต้องรีเฟรช)

## Troubleshooting ที่ Codex ต้องทำก่อนถามกลับ

### A) เจอ `429 Too Many Requests` ตอน submit

```powershell
php artisan optimize:clear
```

แล้วลองใหม่

### B) `composer run dev` ล้ม

- ตรวจว่าใช้สคริปต์ล่าสุด (ไม่มี `php artisan pail`)
- ถ้ายังล้ม ให้รันแยกทีละคำสั่ง:

```powershell
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
php artisan reverb:start --host=127.0.0.1 --port=8080
```

### C) Dashboard ไม่ real-time

ตรวจตามลำดับ:
- มีโปรเซส `reverb` รันอยู่
- `.env` เป็น `BROADCAST_CONNECTION=reverb`
- เปิด browser ใหม่หลังแก้ `.env`
- ทดสอบสร้างข้อมูลใหม่อีกครั้ง

## คำสั่งตรวจสุขภาพก่อนเริ่มงานจริง

```powershell
php artisan test
npm run build
```

ถ้าผ่านทั้งคู่ถือว่าเครื่องบ้านพร้อมใช้งาน

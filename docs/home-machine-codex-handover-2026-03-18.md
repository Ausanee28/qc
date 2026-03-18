# Home Machine Handover (Updated: 2026-03-18)

เอกสารนี้สรุปสิ่งที่ต้องทำบนเครื่องที่บ้านหลัง `git pull` เพื่อให้ระบบทำงานเหมือนเครื่องที่ทำงาน โดยใช้ DB ของเครื่องบ้านเอง

## สิ่งใหม่ที่เพิ่มในรอบนี้

- Dashboard real-time ผ่าน WebSocket (`Laravel Reverb` + `Laravel Echo`)
- Broadcast event เมื่อข้อมูลเปลี่ยนจากหน้า `Receive Job` / `Execute Test`
- ปรับ `composer run dev` ให้รองรับ Windows (ตัด `pail` ที่ใช้ `pcntl` ออก)
- ปรับ throttle ให้เหมาะกับงานหน้างาน ลดโอกาสเจอ `429 Too Many Requests`

## Checklist หลัง git pull (เครื่องบ้าน)

1. ดึงโค้ดล่าสุด

```powershell
git pull
```

2. อัปเดต dependency (รอบนี้จำเป็น)

```powershell
composer install
npm install
```

3. ตั้ง `.env` ให้เป็น DB เครื่องบ้าน

```powershell
php switch_db.php home
php switch_db.php status
```

ต้องเห็นว่า profile เป็น `home` และ host/database เป็นของเครื่องบ้าน

4. ตรวจค่า Reverb ใน `.env`

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=xxxxxx
REVERB_APP_KEY=xxxxxx
REVERB_APP_SECRET=xxxxxx
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

หมายเหตุ: ถ้า pull จาก branch ล่าสุดที่มีไฟล์ `.env.example` ใหม่แล้ว ให้ copy ค่าจากไฟล์นั้นได้

5. เคลียร์แคชแอป

```powershell
php artisan optimize:clear
```

6. รัน migration (ถ้ามีใหม่)

```powershell
php artisan migrate
```

7. รันระบบ dev แบบครบ (server + queue + vite + reverb)

```powershell
composer run dev
```

## วิธีเช็กว่า Real-time ทำงาน

1. เปิด Dashboard 2 แท็บ (หรือ 2 เครื่อง)
2. ไปที่ `Receive Job` หรือ `Execute Test` แล้วบันทึกข้อมูลใหม่
3. Dashboard อีกแท็บต้องอัปเดตเองทันทีโดยไม่ต้องรีเฟรช

## ถ้าเจอปัญหา

### 1) เจอ 429 ตอน submit

- ตอนนี้ route ถูกปรับ throttle สูงขึ้นแล้ว
- ถ้ายังเจอ ให้รัน:

```powershell
php artisan optimize:clear
```

แล้วลองใหม่

### 2) `composer run dev` ล้มเพราะ pail/pcntl

- สคริปต์ใหม่ตัด `pail` ออกแล้ว
- ถ้ายังเป็นสคริปต์เก่า ให้ pull ล่าสุดอีกครั้ง

### 3) Dashboard ไม่ real-time

เช็กตามลำดับ:

- `composer run dev` ต้องมี process `reverb` ขึ้น
- `.env` ต้องเป็น `BROADCAST_CONNECTION=reverb`
- เปิด browser ใหม่หลังแก้ `.env`
- ทดสอบด้วยการบันทึกข้อมูลใหม่อีกครั้ง

## คำสั่ง fallback แยกรันทีละตัว (กรณี debug)

```powershell
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
php artisan reverb:start --host=127.0.0.1 --port=8080
```

## ก่อน push จากเครื่องบ้าน

```powershell
php artisan test
npm run build
```

ถ้าผ่านทั้งคู่ค่อย push

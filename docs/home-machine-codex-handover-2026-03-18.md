# Home Machine Handover (Updated: 2026-03-20)

เอกสารนี้คือ checklist ล่าสุดสำหรับเครื่องที่บ้าน เพื่อให้ตามเครื่องที่ทำงานได้ทันทั้งฟีเจอร์และ performance

## สิ่งที่เพิ่มจากรอบก่อน

- Dashboard ปรับให้เบาขึ้น (lazy-load chart/realtime และ cache ฝั่ง server)
- ตัด external font chain เพื่อลด network dependency ตอนหน้าแรกโหลด
- Execute Test เปลี่ยนเป็น adaptive polling (active/hidden tab)
- เพิ่มสคริปต์ benchmark: `npm run bench:prodlike`
- มีโฟลเดอร์ `tools/redis` ใน repo สำหรับใช้ Redis บน Windows (optional)

## ขั้นตอนบนเครื่องบ้านหลัง pull

1. เข้าโฟลเดอร์โปรเจกต์และดึงโค้ดล่าสุดจาก branch ทำงาน

```powershell
cd C:\qc
git checkout dev-work
git pull --ff-only origin dev-work
```

2. ติดตั้ง dependency ให้ตรง lock ล่าสุด

```powershell
composer install
npm install
```

3. ตั้งค่า `.env` ให้เป็นเครื่องบ้าน

```powershell
php switch_db.php home
php switch_db.php status
```

ต้องเห็นว่า `Current Profile` เป็น `home` และ DB host/database เป็นค่าของเครื่องบ้าน

4. ตรวจค่าสำคัญใน `.env`

```env
APP_URL=http://localhost
DB_PROFILE=home
BROADCAST_CONNECTION=reverb
VITE_PREFETCH=false

REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

5. เคลียร์แคชและอัปเดต schema

```powershell
php artisan optimize:clear
php artisan migrate
```

6. Build asset หนึ่งรอบเพื่อซิงก์ของใหม่

```powershell
npm run build
```

7. รันระบบ dev

```powershell
composer run dev
```

## Optional: ใช้ Redis บนเครื่องบ้าน

ถ้าต้องการเปิด Redis ในเครื่องบ้าน สามารถใช้ไฟล์ใน `tools/redis/bin` ได้ทันที

```powershell
C:\qc\tools\redis\bin\redis-server.exe C:\qc\tools\redis\bin\redis.windows.conf
```

จากนั้นค่อยเปลี่ยน `CACHE_STORE` หรือ `QUEUE_CONNECTION` เป็น redis ตามที่ต้องการ

## Smoke Test ให้มั่นใจว่าเท่าเครื่องที่ทำงาน

1. เข้า Dashboard, Receive Job, Execute Test ได้ปกติ
2. ทดสอบบันทึกข้อมูลจาก Receive/Execute แล้ว Dashboard อีกแท็บอัปเดตเอง
3. เช็กว่าการเปลี่ยนเมนูลื่นและ sidebar ไม่เด้งกลับบนตำแหน่ง scroll เดิม
4. รัน benchmark คร่าว ๆ

```powershell
php artisan qc:profile --runs=5
npm run bench:prodlike
```

## Troubleshooting สั้น ๆ

- ถ้าเจอ 429 หรือค่าแปลกหลัง pull: `php artisan optimize:clear`
- ถ้า Dashboard ไม่ realtime: ตรวจว่า `reverb` process รันอยู่ และค่า `BROADCAST_CONNECTION=reverb` ถูกต้อง
- ถ้าหน้าเว็บยังใช้ไฟล์เก่า: hard refresh (`Ctrl+F5`) หรือปิดเปิด browser ใหม่

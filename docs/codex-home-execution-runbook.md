# Codex Home Execution Runbook (Updated: 2026-03-20)

เอกสารนี้ให้ Codex/ผู้พัฒนาบนเครื่องบ้านทำตามเพื่อให้สภาพแวดล้อมตามเครื่องที่ทำงานทัน

## Quick Sync Steps

1. Sync code

```powershell
cd C:\qc
git checkout dev-work
git pull --ff-only origin dev-work
```

2. Install deps

```powershell
composer install
npm install
```

3. Set home DB profile

```powershell
php switch_db.php home
php switch_db.php status
```

4. Clear cache + migrate

```powershell
php artisan optimize:clear
php artisan migrate
```

5. Build once

```powershell
npm run build
```

6. Start full local stack

```powershell
composer run dev
```

## Required Env Check

```env
DB_PROFILE=home
BROADCAST_CONNECTION=reverb
VITE_PREFETCH=false
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
```

## Optional Redis on Windows

มีไฟล์พร้อมใน repo แล้วที่ `tools/redis/bin`

```powershell
C:\qc\tools\redis\bin\redis-server.exe C:\qc\tools\redis\bin\redis.windows.conf
```

## Parity Verification

```powershell
php artisan qc:profile --runs=5
npm run bench:prodlike
```

ถ้า command ผ่านและหน้า Dashboard/Receive Job/Execute Test ใช้งานได้ลื่น ถือว่าพร้อมเทียบเครื่องที่ทำงาน

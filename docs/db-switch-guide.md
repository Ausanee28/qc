# คู่มือการสลับฐานข้อมูล

โปรเจกต์นี้รองรับการสลับฐานข้อมูล 2 โปรไฟล์ เพื่อให้คุณไม่ต้องเปิดแก้ไฟล์ `.env` เองทุกครั้งเวลาย้ายเครื่องระหว่างที่บ้านกับที่ทำงาน

## โปรไฟล์ที่มีอยู่

`home`

- Driver: `mysql`
- Host: `127.0.0.1`
- Port: `3306`
- Database: `dbqc`
- Username: `root`

`work`

- Driver: `mariadb`
- Host: `10.22.0.101`
- Port: `3307`
- Database: `qc3`
- Username: `std01`

## หลักการทำงาน

การสลับฐานข้อมูลถูกควบคุมด้วย [switch_db.php](C:/xampp/htdocs/project/qc/switch_db.php)

สคริปต์จะอ่านค่า profile จาก [\.env](C:/xampp/htdocs/project/qc/.env) แล้วอัปเดตค่าที่ใช้จริงของระบบดังนี้

- `DB_PROFILE`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

หลังจากสลับแล้ว สคริปต์จะล้าง cache ของ Laravel ในส่วน config, route, view และ event เพื่อให้โปรเจกต์ใช้ค่าฐานข้อมูลใหม่ได้ทันที

## วิธีใช้ผ่าน Command Line

รันคำสั่งต่อไปนี้จากโฟลเดอร์ root ของโปรเจกต์

```bash
php switch_db.php status
php switch_db.php home
php switch_db.php work
```

ความหมายของแต่ละคำสั่ง

- `status` ใช้ดูว่าโปรเจกต์กำลังเชื่อมกับฐานข้อมูลโปรไฟล์ไหนอยู่
- `home` ใช้สลับไปฐานข้อมูล MySQL ที่เครื่องบ้าน
- `work` ใช้สลับไปฐานข้อมูล MariaDB ที่เครื่องที่ทำงาน

## วิธีใช้ผ่านไฟล์ลัดบน Windows

มีไฟล์ `.bat` เตรียมไว้ให้ที่ root ของโปรเจกต์ดังนี้

- [switch-db-home.bat](C:/xampp/htdocs/project/qc/switch-db-home.bat)
- [switch-db-work.bat](C:/xampp/htdocs/project/qc/switch-db-work.bat)
- [switch-db-status.bat](C:/xampp/htdocs/project/qc/switch-db-status.bat)

คุณสามารถดับเบิลคลิกจาก Windows Explorer หรือรันจาก Command Prompt ได้เลย

## ลำดับการใช้งานปกติ

ตอนใช้งานที่บ้าน

```bash
php switch_db.php home
php artisan serve
npm run dev
```

ตอนใช้งานที่ทำงาน

```bash
php switch_db.php work
php artisan serve
npm run dev
```

## การตั้งค่าครั้งแรกบนเครื่องใหม่

ถ้าฐานข้อมูลปลายทางยังว่างอยู่ ให้รันคำสั่งนี้

```bash
php artisan migrate --seed
```

ถ้าฐานข้อมูลเดิมมีตารางเก่าหรือ schema เพี้ยน และคุณยอมรับการล้างข้อมูลในฐานนั้นได้ ให้ใช้

```bash
php artisan migrate:fresh --seed
```

ข้อควรระวัง

- `migrate:fresh --seed` จะลบทุกตารางในฐานข้อมูลที่กำลังเลือกอยู่ก่อนสร้างใหม่
- ห้ามรันคำสั่งนี้กับฐานข้อมูลที่ทำงาน ถ้ายังไม่แน่ใจว่าปลอดภัย

## จุดที่ใช้แก้ค่าโปรไฟล์

ค่าของแต่ละ profile ถูกเก็บไว้ใน [\.env](C:/xampp/htdocs/project/qc/.env) และค่า template อยู่ใน [\.env.example](C:/xampp/htdocs/project/qc/.env.example)

ตัวแปรที่เกี่ยวข้องมีดังนี้

```env
WORK_DB_CONNECTION=mariadb
WORK_DB_HOST=10.22.0.101
WORK_DB_PORT=3307
WORK_DB_DATABASE=qc3
WORK_DB_USERNAME=std01
WORK_DB_PASSWORD=...

HOME_DB_CONNECTION=mysql
HOME_DB_HOST=127.0.0.1
HOME_DB_PORT=3306
HOME_DB_DATABASE=dbqc
HOME_DB_USERNAME=root
HOME_DB_PASSWORD=
```

ควรแก้ค่าเหล่านี้เฉพาะตอนที่รายละเอียดของ database server เปลี่ยนจริงเท่านั้น

## การแก้ปัญหาเบื้องต้น

ถ้า Laravel ยังชี้ไปฐานข้อมูลผิด ให้ตรวจสอบด้วยคำสั่งนี้

```bash
php switch_db.php status
```

ถ้า migration ขึ้น error ว่าตารางมีอยู่แล้ว

- แปลว่าฐานข้อมูลอาจมี schema เก่าค้างอยู่จากการตั้งค่าครั้งก่อน
- สำหรับเครื่อง local ของตัวเอง วิธีที่สะอาดที่สุดมักเป็น `php artisan migrate:fresh --seed`

ถ้าที่ทำงานล็อกอินได้ แต่ที่บ้านล็อกอินไม่ได้

- ตรวจสอบว่าฐาน `home` ได้รัน `migrate` และ `seed` แล้ว
- บัญชีเริ่มต้นที่ seed ไว้คือ `admin` / `password` หากยังไม่ได้แก้ภายหลัง

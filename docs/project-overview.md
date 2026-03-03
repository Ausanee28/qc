# QC Lab Tracking System — ภาพรวมโปรเจกต์

ระบบติดตามงานห้องปฏิบัติการควบคุมคุณภาพ (QC Lab Tracking)  
พัฒนาด้วย PHP + MySQL/MariaDB, UI แบบ Dark Mode ด้วย TailwindCSS + Chart.js

---

## ระบบผู้ใช้ (Authentication)

| หน้า                 | ฟีเจอร์                                                        |
| -------------------- | --------------------------------------------------------------- |
| `login.php`          | ล็อกอินเข้าสู่ระบบ พร้อม typewriter effect ตอนล็อกอินสำเร็จ    |
| `register.php`       | สมัครสมาชิกใหม่ (username, password, employee ID, ชื่อ, role)   |
| `forgot_password.php`| รีเซ็ตรหัสผ่าน                                                 |
| `logout.php`         | ออกจากระบบ                                                      |

---

## Dashboard (`index.php`)

- แสดงตัวเลขสำคัญ (Metrics): จำนวนงานวันนี้, งานเดือนนี้, จำนวน OK, NG, งาน Pending
- กราฟ Weekly Trend (แนวโน้มรายสัปดาห์)
- กราฟ Monthly Overview (ภาพรวมรายเดือน)
- Equipment Ranking (อุปกรณ์ที่ใช้บ่อย)
- ใช้ Chart.js สำหรับกราฟ

---

## รับงาน (`receive_job.php`)

- บันทึกงานตรวจสอบใหม่ที่เข้ามาในห้องแล็บ
- เลือก ผู้ส่ง (External User), ผู้รับ (Internal User), อุปกรณ์, DMC Code, Line
- สร้าง Transaction Header ใหม่พร้อมสถานะ "Pending"
- สามารถพิมพ์ Tag (`print_tag.php`) ได้หลังรับงานสำเร็จ

---

## ทำการทดสอบ (`execute_test.php`)

- เลือกงานที่ยังมีสถานะ Pending มาบันทึกผลการทดสอบ
- กำหนด: วิธีทดสอบ, ผู้ตรวจ (Inspector), เวลาเริ่ม/สิ้นสุด, ผลตัดสิน (OK/NG), หมายเหตุ
- เมื่อบันทึกแล้ว สถานะงานจะเปลี่ยนเป็น Completed

---

## รายงาน (`report.php`)

- ดูผลการทดสอบทั้งหมดแบบตาราง พร้อมกรองตามช่วงวันที่
- แสดงรายละเอียด: Line, Equipment, DMC, Sender, Method, Inspector, เวลาเริ่ม/สิ้นสุด, ผลตัดสิน, หมายเหตุ
- สามารถ Export ข้อมูลได้ (`export.php`)
- สามารถดาวน์โหลด PDF รายงานแต่ละรายการได้

---

## ใบรับรอง (`certificates.php`)

- แสดงงานทั้งหมดเป็น Card พร้อมสถานะ (OK / NG / Pending)
- กดดาวน์โหลด PDF Certificate (ใบรับรองผลการตรวจ QC) สำหรับแต่ละงาน

---

## สร้าง PDF (`generate_pdf.php`)

- ใช้ Dompdf สร้างเอกสาร PDF "QC LAB TEST CERTIFICATE" อย่างเป็นทางการ
- มีข้อมูลงาน, ผลการทดสอบทั้งหมด, ผลตัดสินรวม (Overall Judgement), ช่องลงนาม

---

## Performance (`performance.php`)

- วิเคราะห์ระยะเวลาทดสอบของแต่ละ Inspector
- แสดง: ค่าเฉลี่ย, เร็วที่สุด, ช้าที่สุด, อัตรา OK/NG
- กราฟ Bar Chart เปรียบเทียบเวลาเฉลี่ยระหว่าง Inspector
- ตาราง Test Duration History (50 รายการล่าสุด)

---

## API (`api/search_dmc.php`)

- ค้นหา DMC Code สำหรับใช้เป็น API ภายใน

---

## โครงสร้างฐานข้อมูล

| ตาราง                | หน้าที่                                    |
| -------------------- | ------------------------------------------ |
| `Departments`        | แผนกต่าง ๆ                                |
| `Internal_Users`     | ผู้ใช้ภายใน (Inspector/Admin)              |
| `External_Users`     | ผู้ส่งตัวอย่างจากแผนกอื่น                 |
| `Equipments`         | อุปกรณ์ที่ทดสอบ (Caliper, CMM, ฯลฯ)       |
| `Test_Methods`       | วิธีการทดสอบ                               |
| `Transaction_Header` | หัวข้องาน (รับงาน)                        |
| `Transaction_Detail` | รายละเอียดผลทดสอบ                          |

---

## Flow การใช้งาน

รับตัวอย่างเข้า → ทำการทดสอบ → บันทึกผล → ออกใบรับรอง PDF → ดูรายงานและวิเคราะห์ Performance

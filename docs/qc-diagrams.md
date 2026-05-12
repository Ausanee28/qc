# เอกสาร Diagram สำหรับ QC Lab Tracking System

เอกสารนี้รวบรวมไดอะแกรมของระบบ QC Lab Tracking System สำหรับใช้ประกอบเล่มจบ และสามารถนำ Mermaid code ไปวางต่อใน draw.io ได้ผ่านเมนู `Insert > Advanced > Mermaid`

ขอบเขตของไดอะแกรมในเอกสารนี้ครอบคลุมฟีเจอร์หลักของระบบ ได้แก่ Dashboard, Receive Job, Execute Test, Master Data และ Audit Log

## วิธีนำ Mermaid ไปใช้ใน draw.io

1. เปิด draw.io หรือ diagrams.net
2. เลือกเมนู `Insert > Advanced > Mermaid`
3. คัดลอก code ใน block `mermaid` ของไดอะแกรมที่ต้องการ
4. วางลงในหน้าต่าง Mermaid แล้วกด Insert
5. ปรับตำแหน่ง สี ขนาด และข้อความเพิ่มเติมตามรูปแบบเล่มจบ

## รายการไดอะแกรมในเอกสาร

- System Context Diagram / แผนภาพบริบทของระบบ
- Use Case Diagram / แผนภาพกรณีการใช้งาน
- Activity Diagram / แผนภาพกิจกรรม
- Entity Relationship Diagram / แผนภาพความสัมพันธ์ของฐานข้อมูล
- Data Flow Diagram Level 0 / แผนภาพกระแสข้อมูลระดับ 0
- Data Flow Diagram Level 1 / แผนภาพกระแสข้อมูลระดับ 1
- System Architecture Diagram / แผนภาพสถาปัตยกรรมระบบ
- Sequence Diagram: Receive Job / แผนภาพลำดับการทำงานของการรับงาน
- Sequence Diagram: Execute Test / แผนภาพลำดับการทำงานของการบันทึกผลตรวจ
- State Diagram: Job Status / แผนภาพสถานะของงานตรวจ
- Class / Model Relationship Diagram / แผนภาพความสัมพันธ์ของ Laravel Models

---

## 1. System Context Diagram / แผนภาพบริบทของระบบ

### จุดประสงค์ของไดอะแกรม

System Context Diagram ใช้อธิบายภาพรวมระดับบนสุดของระบบ ว่าระบบ QC Lab Tracking System ติดต่อกับผู้ใช้งานและองค์ประกอบภายนอกใดบ้าง ไดอะแกรมนี้เหมาะสำหรับใช้เปิดบทวิเคราะห์ระบบ เพราะช่วยให้ผู้อ่านเข้าใจขอบเขตของระบบก่อนลงรายละเอียดในไดอะแกรมอื่น

### องค์ประกอบในภาพ

- `Admin` คือผู้ดูแลระบบ มีหน้าที่จัดการข้อมูลพื้นฐาน ดู Dashboard รับงาน และบันทึกผลตรวจ
- `Inspector / QC Lab` คือผู้ใช้งานหลักของห้อง QC ใช้รับงานตรวจและบันทึกผลการตรวจ
- `External User / Sender` คือผู้ส่งงานหรือผู้ที่ส่งชิ้นงานเข้ามาให้ห้อง QC ตรวจ
- `QC Lab Tracking System` คือระบบหลักที่พัฒนาด้วย Laravel, Vue และ Inertia
- `MariaDB Database` คือฐานข้อมูลที่เก็บข้อมูลงาน ผลตรวจ ผู้ใช้ ข้อมูลพื้นฐาน และ Audit Log
- `Cache / Redis` คือพื้นที่เก็บข้อมูลชั่วคราวเพื่อเพิ่มความเร็ว เช่น Dashboard และรายการงานที่รอตรวจ
- `Realtime Dashboard Refresh` คือกลไกที่ช่วยให้ข้อมูล Dashboard ถูกอัปเดตหลังเกิดการเปลี่ยนแปลงใน workflow

### ความสัมพันธ์และเส้นเชื่อม

- Admin และ Inspector เข้าสู่ระบบและใช้งาน module ต่าง ๆ ภายในระบบ
- External User ไม่ได้เป็นผู้ใช้งานระบบโดยตรง แต่เป็นแหล่งที่มาของข้อมูลงานตรวจ
- ระบบบันทึกและอ่านข้อมูลจาก MariaDB
- ระบบใช้ Cache / Redis เพื่อลดภาระการ query ข้อมูลซ้ำ
- เมื่อมีการรับงานหรือบันทึกผลตรวจ ระบบจะล้าง cache และกระตุ้นให้ Dashboard แสดงข้อมูลล่าสุด

### Mermaid Diagram

```mermaid
flowchart LR
  Admin["Admin<br/>ผู้ดูแลระบบ"]
  Inspector["Inspector / QC Lab<br/>เจ้าหน้าที่ตรวจ QC"]
  Sender["External User / Sender<br/>ผู้ส่งงานตรวจ"]

  subgraph System["QC Lab Tracking System"]
    Auth["Authentication<br/>เข้าสู่ระบบ"]
    Dashboard["Dashboard<br/>สรุปผลและตัวชี้วัด"]
    Receive["Receive Job<br/>รับงานตรวจ"]
    Execute["Execute Test<br/>บันทึกผลตรวจ"]
    Master["Master Data<br/>จัดการข้อมูลพื้นฐาน"]
    Audit["Audit Logging<br/>บันทึกประวัติการทำงาน"]
  end

  DB[("MariaDB Database")]
  Cache[("Cache / Redis")]
  Realtime["Realtime Dashboard Refresh"]

  Sender -->|"ส่งข้อมูลงานหรือชิ้นงานให้ตรวจ"| Inspector

  Admin --> Auth
  Inspector --> Auth
  Admin --> Dashboard
  Admin --> Receive
  Admin --> Execute
  Admin --> Master
  Inspector --> Dashboard
  Inspector --> Receive
  Inspector --> Execute

  Receive --> DB
  Execute --> DB
  Master --> DB
  Dashboard --> DB
  Audit --> DB

  Receive --> Audit
  Execute --> Audit
  Master --> Audit

  Dashboard --> Cache
  Receive --> Cache
  Execute --> Cache
  Receive --> Realtime
  Execute --> Realtime
  Realtime --> Dashboard
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพบริบทของระบบแสดงให้เห็นว่า QC Lab Tracking System เป็นศูนย์กลางในการรับงาน ตรวจงาน จัดการข้อมูลพื้นฐาน และแสดงผล Dashboard โดยมี Admin และ Inspector เป็นผู้ใช้งานหลัก ระบบเชื่อมต่อกับฐานข้อมูล MariaDB สำหรับจัดเก็บข้อมูลถาวร และใช้ Cache / Redis เพื่อเพิ่มประสิทธิภาพในการแสดงข้อมูล

---

## 2. Use Case Diagram / แผนภาพกรณีการใช้งาน

### จุดประสงค์ของไดอะแกรม

Use Case Diagram ใช้อธิบายว่าผู้ใช้แต่ละประเภทสามารถทำอะไรได้บ้างในระบบ โดยเน้นขอบเขตการใช้งานของ Admin และ Inspector ไดอะแกรมนี้ช่วยให้เห็นฟีเจอร์หลักและสิทธิ์การเข้าถึงของแต่ละบทบาท

### องค์ประกอบในภาพ

- `Admin` มีสิทธิ์ใช้งานทุกส่วนของระบบ รวมถึงจัดการ Master Data และผู้ใช้
- `Inspector` ใช้งานส่วนปฏิบัติงานหลัก ได้แก่ Dashboard, Receive Job และ Execute Test
- Use case กลุ่ม workflow ได้แก่ รับงาน แก้ไขงาน ปิดงาน เปิดงานอีกครั้ง และบันทึกผลตรวจ
- Use case กลุ่ม Master Data ได้แก่ จัดการแผนก อุปกรณ์ วิธีตรวจ ผู้ใช้ภายใน และผู้ส่งงาน
- `System records Audit Log` เป็นกระบวนการที่ระบบทำอัตโนมัติเมื่อเกิดการเพิ่ม แก้ไข ลบ หรือ restore ข้อมูลสำคัญ

### ความสัมพันธ์และเส้นเชื่อม

- Admin เชื่อมกับ use case ทุกตัว เพราะมีสิทธิ์ควบคุมระบบทั้งหมด
- Inspector เชื่อมกับ use case ที่เกี่ยวกับการทำงานประจำวันของห้อง QC
- เส้นประไปยัง Audit Log หมายถึงระบบบันทึกประวัติให้อัตโนมัติ ไม่ใช่ action ที่ผู้ใช้กดโดยตรง

### Mermaid Diagram

```mermaid
flowchart TB
  Admin(("Admin"))
  Inspector(("Inspector"))

  Login["Login / Logout"]
  ViewDashboard["View Dashboard"]
  CreateJob["Create Receive Job"]
  ManageJob["Edit / Delete / Restore Job"]
  CloseJob["Close / Reopen Job"]
  RecordResult["Record Test Result"]
  ManageResult["Edit / Delete / Restore Test Result"]
  ManageDept["Manage Departments"]
  ManageEquip["Manage Equipments"]
  ManageMethod["Manage Test Methods"]
  ManageInternal["Manage Internal Users"]
  ManageExternal["Manage External Users"]
  AuditTrack["System records Audit Log"]

  Admin --> Login
  Admin --> ViewDashboard
  Admin --> CreateJob
  Admin --> ManageJob
  Admin --> CloseJob
  Admin --> RecordResult
  Admin --> ManageResult
  Admin --> ManageDept
  Admin --> ManageEquip
  Admin --> ManageMethod
  Admin --> ManageInternal
  Admin --> ManageExternal

  Inspector --> Login
  Inspector --> ViewDashboard
  Inspector --> CreateJob
  Inspector --> ManageJob
  Inspector --> CloseJob
  Inspector --> RecordResult
  Inspector --> ManageResult

  CreateJob -.-> AuditTrack
  ManageJob -.-> AuditTrack
  CloseJob -.-> AuditTrack
  RecordResult -.-> AuditTrack
  ManageResult -.-> AuditTrack
  ManageDept -.-> AuditTrack
  ManageEquip -.-> AuditTrack
  ManageMethod -.-> AuditTrack
  ManageInternal -.-> AuditTrack
  ManageExternal -.-> AuditTrack
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพกรณีการใช้งานแสดงความสามารถของผู้ใช้แต่ละบทบาทในระบบ โดย Admin สามารถจัดการระบบได้ครบทุกส่วน ส่วน Inspector สามารถใช้งานฟังก์ชันหลักที่เกี่ยวข้องกับการรับงานและบันทึกผลตรวจ ระบบจะบันทึก Audit Log อัตโนมัติเมื่อมีการเปลี่ยนแปลงข้อมูลสำคัญ

---

## 3. Activity Diagram / แผนภาพกิจกรรม

### จุดประสงค์ของไดอะแกรม

Activity Diagram ใช้อธิบายลำดับขั้นตอนการทำงานหลักของระบบ ตั้งแต่การเข้าสู่ระบบ การรับงาน การบันทึกผลตรวจ จนถึงการปิดงาน เหมาะสำหรับแสดง workflow จริงของห้อง QC

### องค์ประกอบในภาพ

- ขั้นตอน Login ตรวจสอบว่าผู้ใช้มีสิทธิ์เข้าใช้งานระบบหรือไม่
- ขั้นตอน Receive Job ใช้สร้างงานตรวจใหม่ลงใน `Transaction_Header`
- ขั้นตอน Execute Test ใช้บันทึกผลตรวจลงใน `Transaction_Detail`
- จุดตัดสินใจ validate ใช้ตรวจสอบความถูกต้องของข้อมูลก่อนบันทึก
- ขั้นตอน Audit Log และ Cache Refresh เป็นกระบวนการหลังจากบันทึกข้อมูลสำเร็จ
- ขั้นตอน Close Job ใช้กำหนด `return_date` เพื่อระบุว่างานจบแล้ว

### ความสัมพันธ์และเส้นเชื่อม

- ถ้า Login ไม่สำเร็จ ระบบจะวนกลับไปให้เข้าสู่ระบบใหม่
- ถ้าข้อมูลรับงานไม่ถูกต้อง ระบบจะให้แก้ไขข้อมูลรับงาน
- ถ้าข้อมูลผลตรวจไม่ถูกต้อง ระบบจะให้แก้ไขข้อมูลผลตรวจ
- หลังบันทึกผลตรวจ ผู้ใช้สามารถบันทึกผลเพิ่มได้หากงานนั้นยังมีรายการตรวจเพิ่มเติม
- เมื่อไม่มีผลตรวจเพิ่มเติม ผู้ใช้สามารถปิดงานได้

### Mermaid Diagram

```mermaid
flowchart TD
  Start([Start])
  Login["User Login"]
  RoleCheck{"Role valid?"}
  OpenReceive["Open Receive Job Page"]
  SelectSender["Select External User / Sender"]
  FillJob["Fill DMC, Cell, Line, Shift, Model, Detail"]
  ValidateJob{"Validate receive data"}
  SaveHeader["Create Transaction_Header"]
  AuditJob["Write Audit_Logs for receive job"]
  JobOpen["Job status = Open / Pending<br/>return_date is null"]
  OpenExecute["Open Execute Test Page"]
  SelectPending["Select Pending/Open Job"]
  SelectMethod["Select Test Method and Inspector"]
  FillResult["Fill Start Time, End Time, Min/Max, OK/NG, Remark"]
  ValidateResult{"Validate test result"}
  SaveDetail["Create Transaction_Detail"]
  AuditResult["Write Audit_Logs for test result"]
  Refresh["Clear cache and update dashboard data"]
  MoreResult{"Need more test results?"}
  CloseJob["Close Job<br/>set return_date"]
  End([End])

  Start --> Login --> RoleCheck
  RoleCheck -- "No" --> Login
  RoleCheck -- "Yes" --> OpenReceive
  OpenReceive --> SelectSender --> FillJob --> ValidateJob
  ValidateJob -- "Invalid" --> FillJob
  ValidateJob -- "Valid" --> SaveHeader --> AuditJob --> JobOpen
  JobOpen --> OpenExecute --> SelectPending --> SelectMethod --> FillResult --> ValidateResult
  ValidateResult -- "Invalid" --> FillResult
  ValidateResult -- "Valid" --> SaveDetail --> AuditResult --> Refresh --> MoreResult
  MoreResult -- "Yes" --> OpenExecute
  MoreResult -- "No" --> CloseJob --> Refresh --> End
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพกิจกรรมแสดงขั้นตอนหลักของระบบ ตั้งแต่ผู้ใช้เข้าสู่ระบบ รับงานตรวจ บันทึกข้อมูลงาน เลือกงานที่ยังเปิดอยู่ บันทึกผลการตรวจ และปิดงานเมื่อดำเนินการเสร็จ ระบบมีการตรวจสอบข้อมูลก่อนบันทึก และมีการอัปเดต cache กับ Dashboard หลังข้อมูลเปลี่ยนแปลง

---

## 4. Entity Relationship Diagram / แผนภาพความสัมพันธ์ของฐานข้อมูล

### จุดประสงค์ของไดอะแกรม

ER Diagram ใช้อธิบายโครงสร้างฐานข้อมูลของระบบว่ามี entity หรือตารางใดบ้าง แต่ละตารางเก็บข้อมูลอะไร และมีความสัมพันธ์กันอย่างไร ไดอะแกรมนี้เป็นส่วนสำคัญของเล่มจบ เพราะแสดงโครงสร้างข้อมูลที่ระบบใช้จริง

### องค์ประกอบในภาพ

- `Departments` เก็บข้อมูลแผนก
- `External_Users` เก็บข้อมูลผู้ส่งงาน และเชื่อมกับแผนก
- `Internal_Users` เก็บข้อมูลผู้ใช้ภายใน เช่น Admin และ Inspector
- `Equipments` เก็บข้อมูลอุปกรณ์ที่ใช้ในการตรวจ
- `Test_Methods` เก็บวิธีการตรวจ และเชื่อมกับอุปกรณ์
- `Transaction_Header` เก็บข้อมูลงานรับเข้า เช่น DMC, Cell, Line, Shift, Model, วันรับงาน และวันปิดงาน
- `Transaction_Detail` เก็บผลตรวจ เช่น วิธีตรวจ ผู้ตรวจ เวลาเริ่ม เวลาเสร็จ ค่า Min/Max ผล OK/NG และหมายเหตุ
- `Audit_Logs` เก็บประวัติการกระทำของผู้ใช้ เช่น create, update, delete และ restore

### ความสัมพันธ์และเส้นเชื่อม

- 1 Department มี External User ได้หลายคน
- 1 External User สามารถส่งงานได้หลายงาน
- 1 Internal User สามารถรับงานและตรวจงานได้หลายรายการ
- 1 Transaction Header มี Transaction Detail ได้หลายรายการ
- 1 Equipment มี Test Method ได้หลายวิธี
- 1 Test Method ถูกใช้ใน Transaction Detail ได้หลายรายการ
- 1 Internal User สามารถสร้าง Audit Log ได้หลายรายการ

### Mermaid Diagram

```mermaid
erDiagram
  Departments ||--o{ External_Users : has
  External_Users ||--o{ Transaction_Header : sends
  Internal_Users ||--o{ Transaction_Header : receives
  Transaction_Header ||--o{ Transaction_Detail : has_results
  Internal_Users ||--o{ Transaction_Detail : inspects
  Equipments ||--o{ Test_Methods : supports
  Test_Methods ||--o{ Transaction_Detail : used_in
  Internal_Users ||--o{ Audit_Logs : performs

  Departments {
    int department_id PK
    varchar department_name
    varchar internal_phone
    boolean is_active
  }

  External_Users {
    int external_id PK
    varchar external_name
    int department_id FK
    boolean is_active
  }

  Internal_Users {
    int user_id PK
    varchar user_name
    varchar user_password
    varchar employee_id
    varchar name
    varchar role
    boolean is_active
  }

  Equipments {
    int equipment_id PK
    varchar equipment_name
    boolean is_active
  }

  Test_Methods {
    int method_id PK
    varchar method_name
    int equipment_id FK
    boolean is_active
  }

  Transaction_Header {
    int transaction_id PK
    int external_id FK
    int internal_id FK
    varchar sender_leader
    text detail
    varchar dmc
    varchar cell
    varchar line
    varchar shift
    varchar model
    datetime receive_date
    datetime return_date
    timestamp deleted_at
  }

  Transaction_Detail {
    int detail_id PK
    int transaction_id FK
    int method_id FK
    int internal_id FK
    datetime start_time
    datetime end_time
    int duration_sec
    varchar max_value
    varchar min_value
    varchar judgement
    varchar remark
    timestamp deleted_at
  }

  Audit_Logs {
    bigint audit_id PK
    varchar module
    varchar action
    varchar record_type
    int record_id
    int performed_by FK
    varchar performed_by_name
    longtext before_data
    longtext after_data
    timestamp created_at
  }
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพ ER แสดงโครงสร้างฐานข้อมูลของระบบ โดยมี `Transaction_Header` เป็นตารางหลักสำหรับเก็บข้อมูลงานรับเข้า และ `Transaction_Detail` เป็นตารางสำหรับเก็บผลตรวจแต่ละรายการ ข้อมูลทั้งสองส่วนเชื่อมกับผู้ส่งงาน ผู้ตรวจ วิธีตรวจ และอุปกรณ์ นอกจากนี้ระบบยังมี `Audit_Logs` สำหรับบันทึกประวัติการเปลี่ยนแปลงข้อมูล

---

## 5. Data Flow Diagram Level 0 / แผนภาพกระแสข้อมูลระดับ 0

### จุดประสงค์ของไดอะแกรม

DFD Level 0 ใช้อธิบายการไหลของข้อมูลในภาพรวม โดยมองระบบเป็น process หลักเพียง process เดียว และแสดงว่าใครส่งข้อมูลเข้า ใครรับข้อมูลออก และข้อมูลถูกจัดเก็บไว้ที่ใด

### องค์ประกอบในภาพ

- `Admin / Inspector` เป็น external entity ที่ส่งข้อมูลเข้าสู่ระบบและรับผลลัพธ์จากระบบ
- `QC Lab Tracking System` เป็น process หลักที่ประมวลผลข้อมูลทั้งหมด
- `Master Data` เป็น data store ของข้อมูลพื้นฐาน
- `Transaction_Header` เป็น data store ของข้อมูลงานรับเข้า
- `Transaction_Detail` เป็น data store ของผลตรวจ
- `Audit_Logs` เป็น data store ของประวัติการกระทำ
- `Cache` เป็น data store ชั่วคราวสำหรับข้อมูลที่ถูกเรียกใช้บ่อย

### ความสัมพันธ์และเส้นเชื่อม

- ผู้ใช้ส่งข้อมูล login, รับงาน, ผลตรวจ และข้อมูลพื้นฐานเข้าสู่ระบบ
- ระบบส่งข้อมูล Dashboard, รายการงาน และผลการทำงานกลับไปยังผู้ใช้
- ระบบอ่านและเขียนข้อมูลลง data store ตามประเภทข้อมูล
- ระบบใช้ cache เพื่อเพิ่มความเร็วในการแสดงข้อมูล

### Mermaid Diagram

```mermaid
flowchart LR
  User["Admin / Inspector"]
  P0(("0<br/>QC Lab Tracking System"))

  D1[("D1 Master Data")]
  D2[("D2 Transaction_Header")]
  D3[("D3 Transaction_Detail")]
  D4[("D4 Audit_Logs")]
  D5[("D5 Cache")]

  User -->|"Login request, job data, test result, master data input"| P0
  P0 -->|"Dashboard metrics, job list, validation messages"| User

  P0 -->|"Read/write departments, users, equipments, methods"| D1
  P0 -->|"Create/update/delete/restore job"| D2
  P0 -->|"Create/update/delete/restore test result"| D3
  P0 -->|"Record user action history"| D4
  P0 -->|"Read/write cached dashboard and pending jobs"| D5
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพกระแสข้อมูลระดับ 0 แสดงภาพรวมการไหลของข้อมูลระหว่างผู้ใช้ ระบบ และแหล่งเก็บข้อมูล โดยระบบรับข้อมูลจาก Admin และ Inspector จากนั้นประมวลผลและจัดเก็บลงฐานข้อมูลตามประเภทของข้อมูล เช่น ข้อมูลงานรับเข้า ผลตรวจ ข้อมูลพื้นฐาน และประวัติการทำงาน

---

## 6. Data Flow Diagram Level 1 / แผนภาพกระแสข้อมูลระดับ 1

### จุดประสงค์ของไดอะแกรม

DFD Level 1 ใช้ขยายรายละเอียดจาก DFD Level 0 โดยแยก process ภายในระบบออกเป็นงานย่อย เช่น Authentication, Receive Job, Execute Test, Dashboard และ Master Data ทำให้เห็นว่าข้อมูลไหลผ่านแต่ละ module อย่างไร

### องค์ประกอบในภาพ

- `1.0 Authentication` ตรวจสอบข้อมูลผู้ใช้จาก `Internal_Users`
- `2.0 Receive Job` รับข้อมูลงานและบันทึกลง `Transaction_Header`
- `3.0 Execute Test` เลือกงานที่เปิดอยู่และบันทึกผลลง `Transaction_Detail`
- `4.0 Dashboard` อ่านข้อมูลงานและผลตรวจเพื่อคำนวณตัวชี้วัด
- `5.0 Master Data Management` จัดการข้อมูลพื้นฐานของระบบ
- `6.0 Audit Logging` บันทึกประวัติการกระทำของผู้ใช้

### ความสัมพันธ์และเส้นเชื่อม

- Authentication อ่านข้อมูลผู้ใช้เพื่อตรวจสอบสิทธิ์
- Receive Job อ่านข้อมูลผู้ส่งงานและผู้รับงาน แล้วสร้างรายการใน `Transaction_Header`
- Execute Test อ่านงานที่ยังเปิดอยู่จาก `Transaction_Header` และบันทึกผลใน `Transaction_Detail`
- Dashboard อ่านข้อมูลจาก `Transaction_Header` และ `Transaction_Detail` เพื่อสรุปตัวเลข
- Master Data อ่านและเขียนข้อมูลพื้นฐาน
- ทุก process ที่เปลี่ยนข้อมูลสำคัญส่งข้อมูลไปยัง Audit Logging

### Mermaid Diagram

```mermaid
flowchart TB
  User["Admin / Inspector"]

  P1(("1.0<br/>Authentication"))
  P2(("2.0<br/>Receive Job"))
  P3(("3.0<br/>Execute Test"))
  P4(("4.0<br/>Dashboard"))
  P5(("5.0<br/>Master Data Management"))
  P6(("6.0<br/>Audit Logging"))

  D1[("D1 Internal_Users")]
  D2[("D2 External_Users")]
  D3[("D3 Departments")]
  D4[("D4 Equipments")]
  D5[("D5 Test_Methods")]
  D6[("D6 Transaction_Header")]
  D7[("D7 Transaction_Detail")]
  D8[("D8 Audit_Logs")]
  D9[("D9 Cache")]

  User -->|"username/password"| P1
  P1 -->|"read user credential and role"| D1
  P1 -->|"login result"| User

  User -->|"job intake data"| P2
  P2 -->|"read sender list"| D2
  P2 -->|"read receiver list"| D1
  P2 -->|"insert/update job"| D6
  P2 -->|"job action detail"| P6

  User -->|"test result data"| P3
  P3 -->|"read open jobs"| D6
  P3 -->|"read active methods"| D5
  P3 -->|"read inspectors"| D1
  P3 -->|"insert/update test result"| D7
  P3 -->|"result action detail"| P6

  User -->|"dashboard request"| P4
  P4 -->|"read job records"| D6
  P4 -->|"read result records"| D7
  P4 -->|"read/write metrics cache"| D9
  P4 -->|"dashboard data"| User

  User -->|"master data changes"| P5
  P5 -->|"read/write internal users"| D1
  P5 -->|"read/write external users"| D2
  P5 -->|"read/write departments"| D3
  P5 -->|"read/write equipments"| D4
  P5 -->|"read/write test methods"| D5
  P5 -->|"master data action detail"| P6

  P6 -->|"insert audit record"| D8
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพกระแสข้อมูลระดับ 1 แสดงรายละเอียดภายในระบบว่าแต่ละ module มีการอ่านและเขียนข้อมูลกับ data store ใดบ้าง โดย workflow หลักเริ่มจากการตรวจสอบสิทธิ์ รับงาน บันทึกผลตรวจ และนำข้อมูลไปใช้แสดง Dashboard ขณะเดียวกันระบบจะบันทึก Audit Log เมื่อมีการเปลี่ยนแปลงข้อมูลสำคัญ

---

## 7. System Architecture Diagram / แผนภาพสถาปัตยกรรมระบบ

### จุดประสงค์ของไดอะแกรม

System Architecture Diagram ใช้อธิบายโครงสร้างทางเทคนิคของระบบ ว่าระบบประกอบด้วย layer ใดบ้าง และแต่ละ layer เชื่อมต่อกันอย่างไร ไดอะแกรมนี้เหมาะสำหรับอธิบายเทคโนโลยีที่ใช้พัฒนาระบบ

### องค์ประกอบในภาพ

- `User Browser` คือเครื่องของผู้ใช้งาน
- `Vue 3 Pages` คือหน้า frontend ของระบบ
- `Inertia.js` ทำหน้าที่เชื่อม Vue frontend กับ Laravel backend
- `Tailwind UI` ใช้จัดรูปแบบหน้าจอ
- `Chart.js Dashboard` ใช้แสดงกราฟและข้อมูลสรุปบน Dashboard
- `Laravel Routes` รับ request จาก frontend
- `Auth Middleware` ตรวจสอบการเข้าสู่ระบบและสิทธิ์
- `Controllers` ประมวลผล request ของแต่ละ module
- `DashboardMetricsService` คำนวณข้อมูลสรุปสำหรับ Dashboard
- `Eloquent Models` ติดต่อกับฐานข้อมูล
- `AuditLogger` บันทึกประวัติการทำงาน
- `MariaDB` เก็บข้อมูลถาวรของระบบ
- `Redis / Cache` เก็บข้อมูลชั่วคราวเพื่อเพิ่มความเร็ว

### ความสัมพันธ์และเส้นเชื่อม

- ผู้ใช้เปิดระบบผ่าน Browser แล้วใช้งาน Vue Page
- Vue ส่ง request ผ่าน Inertia ไปยัง Laravel Routes
- Routes ผ่าน Auth Middleware เพื่อคุมสิทธิ์
- Controllers ประมวลผลและเรียก Models, Services และ AuditLogger
- Models และ Services อ่านเขียนข้อมูลจาก MariaDB
- Controllers และ Services ใช้ Redis / Cache ในงานที่ต้องเรียกข้อมูลซ้ำ

### Mermaid Diagram

```mermaid
flowchart TB
  Browser["User Browser"]

  subgraph Frontend["Frontend Layer"]
    Vue["Vue 3 Pages"]
    Inertia["Inertia.js"]
    Tailwind["Tailwind CSS UI"]
    Chart["Chart.js Dashboard"]
  end

  subgraph Backend["Laravel Backend Layer"]
    Routes["Laravel Web Routes"]
    Auth["Auth / Admin Middleware"]
    Controllers["Controllers<br/>Dashboard, Receive Job, Execute Test, Master Data"]
    Services["DashboardMetricsService"]
    Models["Eloquent Models"]
    Audit["AuditLogger"]
  end

  subgraph Data["Data Layer"]
    MariaDB[("MariaDB")]
    Redis[("Redis / Cache")]
  end

  Browser --> Vue
  Vue --> Tailwind
  Vue --> Chart
  Vue --> Inertia
  Inertia --> Routes
  Routes --> Auth
  Auth --> Controllers
  Controllers --> Models
  Controllers --> Services
  Controllers --> Audit
  Models --> MariaDB
  Services --> MariaDB
  Audit --> MariaDB
  Controllers --> Redis
  Services --> Redis
  Redis --> Controllers
  MariaDB --> Models
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพสถาปัตยกรรมระบบแสดงโครงสร้างของ QC Lab Tracking System โดยแบ่งเป็น Frontend Layer, Backend Layer และ Data Layer ผู้ใช้ทำงานผ่าน Browser และหน้า Vue 3 ส่วน backend ใช้ Laravel ในการตรวจสอบสิทธิ์ ประมวลผลข้อมูล และติดต่อฐานข้อมูล MariaDB พร้อมใช้ Redis / Cache เพื่อเพิ่มประสิทธิภาพในการโหลดข้อมูล

---

## 8. Sequence Diagram: Receive Job / แผนภาพลำดับการทำงานของการรับงาน

### จุดประสงค์ของไดอะแกรม

Sequence Diagram: Receive Job ใช้อธิบายลำดับการสื่อสารระหว่างผู้ใช้ หน้า frontend, route, controller, database, audit logger และ cache ในกรณีที่ผู้ใช้สร้างงานตรวจใหม่

### องค์ประกอบในภาพ

- `Inspector` คือผู้รับงานตรวจ
- `ReceiveJob Vue Page` คือหน้า frontend ที่กรอกข้อมูลงาน
- `Laravel Route` คือ route `POST /receive-job`
- `ReceiveJobController` คือ controller ที่ validate และบันทึกงาน
- `MariaDB` คือฐานข้อมูลที่เก็บ `Transaction_Header`
- `AuditLogger` คือ class สำหรับบันทึกประวัติ
- `Cache/Dashboard` คือ cache และข้อมูล Dashboard ที่ต้องอัปเดตหลังรับงานใหม่

### ลำดับการทำงาน

1. Inspector กรอกข้อมูลงานในหน้า Receive Job
2. Vue ส่ง request ไปยัง `POST /receive-job`
3. Laravel Route ส่งต่อไปยัง `ReceiveJobController`
4. Controller ตรวจสอบข้อมูล เช่น external user, internal user, DMC, shift และข้อมูลอื่น
5. Controller บันทึกข้อมูลลง `Transaction_Header`
6. ระบบบันทึก Audit Log
7. ระบบล้าง cache ที่เกี่ยวข้องกับรายการงานและ Dashboard
8. ระบบ redirect กลับไปยังหน้า Receive Job พร้อมข้อความสำเร็จ

### Mermaid Diagram

```mermaid
sequenceDiagram
  actor Inspector
  participant Page as ReceiveJob Vue Page
  participant Route as Laravel Route
  participant Controller as ReceiveJobController
  participant DB as MariaDB
  participant Audit as AuditLogger
  participant Cache as Cache/Dashboard

  Inspector->>Page: Fill job intake form
  Page->>Route: POST /receive-job
  Route->>Controller: Forward request
  Controller->>Controller: Validate external_id, internal_id, DMC, cell, line, shift, model
  Controller->>DB: Insert Transaction_Header
  DB-->>Controller: Return created transaction_id
  Controller->>Audit: Log receive_job create action
  Audit->>DB: Insert Audit_Logs
  Controller->>Cache: Forget receive job, pending job, dashboard caches
  Controller-->>Page: Redirect with success message
  Page-->>Inspector: Show created job status
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพลำดับการทำงานของการรับงานแสดงขั้นตอนตั้งแต่ Inspector กรอกข้อมูลงานตรวจ ระบบตรวจสอบความถูกต้อง บันทึกลง `Transaction_Header` และสร้าง Audit Log จากนั้นระบบจะล้าง cache ที่เกี่ยวข้องเพื่อให้รายการงานและ Dashboard แสดงข้อมูลล่าสุด

---

## 9. Sequence Diagram: Execute Test / แผนภาพลำดับการทำงานของการบันทึกผลตรวจ

### จุดประสงค์ของไดอะแกรม

Sequence Diagram: Execute Test ใช้อธิบาย flow สำคัญของระบบ คือการบันทึกผลตรวจ OK/NG ลงใน `Transaction_Detail` โดยแสดงการสื่อสารระหว่าง frontend, backend, database, audit logger และ cache

### องค์ประกอบในภาพ

- `Inspector` คือผู้ตรวจหรือผู้บันทึกผลตรวจ
- `ExecuteTest Vue Page` คือหน้าบันทึกผลตรวจ
- `Laravel Route` คือ route `POST /execute-test`
- `ExecuteTestController` คือ controller ที่ตรวจสอบข้อมูลและบันทึกผล
- `MariaDB` คือฐานข้อมูลที่เก็บ `Transaction_Header`, `Transaction_Detail` และ `Audit_Logs`
- `AuditLogger` คือ class สำหรับบันทึกประวัติการเพิ่มผลตรวจ
- `Cache/Dashboard` คือ cache และข้อมูล Dashboard ที่ต้อง refresh หลังผลตรวจเปลี่ยน

### ลำดับการทำงาน

1. Inspector เลือกงานที่ยังเปิดอยู่
2. Inspector เลือกวิธีตรวจ ผู้ตรวจ เวลา และผล OK/NG
3. Vue ส่ง request ไปยัง `POST /execute-test`
4. Controller validate ข้อมูลและตรวจว่า job ยังไม่ถูกปิด
5. Controller คำนวณ `duration_sec` จาก start time และ end time
6. Controller บันทึกผลลง `Transaction_Detail`
7. AuditLogger บันทึก action ลง `Audit_Logs`
8. Controller ล้าง cache และอัปเดตข้อมูล Dashboard
9. ระบบส่งข้อความสำเร็จกลับไปยังหน้า Execute Test

### Mermaid Diagram

```mermaid
sequenceDiagram
  actor Inspector
  participant Page as ExecuteTest Vue Page
  participant Route as Laravel Route
  participant Controller as ExecuteTestController
  participant DB as MariaDB
  participant Audit as AuditLogger
  participant Cache as Cache/Dashboard

  Inspector->>Page: Select pending job and fill test result
  Page->>Route: POST /execute-test
  Route->>Controller: Forward request
  Controller->>Controller: Validate transaction_id, method_id, inspector, judgement, time
  Controller->>DB: Check Transaction_Header is open
  DB-->>Controller: Job found and return_date is null
  Controller->>Controller: Normalize start_time, end_time, duration_sec
  Controller->>DB: Insert Transaction_Detail
  DB-->>Controller: Return created detail_id
  Controller->>Audit: Log execute_test create action
  Audit->>DB: Insert Audit_Logs
  Controller->>Cache: Forget pending job, result, dashboard caches
  Controller-->>Page: Redirect with success message
  Page-->>Inspector: Show saved result
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพลำดับการทำงานของการบันทึกผลตรวจแสดงขั้นตอนตั้งแต่ Inspector เลือกงานที่ยังเปิดอยู่ กรอกผลตรวจ OK/NG ระบบตรวจสอบข้อมูล บันทึกผลลง `Transaction_Detail` และบันทึก Audit Log จากนั้นระบบล้าง cache เพื่อให้ Dashboard แสดงข้อมูลล่าสุด

---

## 10. State Diagram: Job Status / แผนภาพสถานะของงานตรวจ

### จุดประสงค์ของไดอะแกรม

State Diagram ใช้อธิบายสถานะของงานตรวจตั้งแต่เริ่มสร้างงานจนถึงปิดงาน รวมถึงกรณีแก้ไข ลบ และ restore ข้อมูล ไดอะแกรมนี้ช่วยให้เห็น lifecycle ของ `Transaction_Header`

### องค์ประกอบในภาพ

- `New` คือสถานะก่อนบันทึกงาน
- `Open / Pending` คือสถานะหลังสร้าง `Transaction_Header` และ `return_date` ยังเป็น `null`
- `Has Test Result` คือสถานะที่งานมีผลตรวจใน `Transaction_Detail` อย่างน้อยหนึ่งรายการ
- `Closed` คือสถานะที่งานถูกปิดแล้ว โดยกำหนด `return_date`
- `Deleted` คือสถานะที่งานถูกลบแบบ soft delete เมื่อมี column `deleted_at`
- `Restored` คือสถานะหลังนำงานที่ถูกลบกลับมาใช้งาน

### ความสัมพันธ์และเส้นเชื่อม

- เมื่อสร้างงานใหม่สำเร็จ งานเข้าสู่สถานะ Open / Pending
- เมื่องานมีผลตรวจ งานยังเปิดอยู่แต่มีข้อมูลผลตรวจแล้ว
- เมื่องานเสร็จสิ้น ผู้ใช้ปิดงานและระบบกำหนด `return_date`
- งานที่ปิดแล้วสามารถ reopen ได้โดยตั้ง `return_date` กลับเป็น `null`
- งานสามารถถูก soft delete และ restore ได้ตามสิทธิ์ของผู้ใช้

### Mermaid Diagram

```mermaid
stateDiagram-v2
  state "New" as New
  state "Open / Pending\nreturn_date is null" as OpenPending
  state "Has Test Result\nTransaction_Detail exists" as HasTestResult
  state "Closed\nreturn_date is set" as Closed
  state "Deleted\nsoft delete" as Deleted

  [*] --> New
  New --> OpenPending: Create Transaction_Header
  OpenPending --> OpenPending: Edit job data
  OpenPending --> HasTestResult: Add Transaction_Detail
  HasTestResult --> HasTestResult: Add/Edit/Delete/Restore result
  HasTestResult --> Closed: Close job / set return_date
  Closed --> OpenPending: Reopen job / clear return_date
  OpenPending --> Deleted: Soft delete job
  Closed --> Deleted: Soft delete closed job
  Deleted --> OpenPending: Restore job
  Closed --> [*]
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพสถานะของงานตรวจแสดง lifecycle ของ `Transaction_Header` โดยงานเริ่มจากการสร้างใหม่เป็นสถานะ Open / Pending จากนั้นสามารถเพิ่มผลตรวจได้ เมื่อดำเนินการเสร็จจึงปิดงานด้วยการกำหนด `return_date` ระบบยังรองรับการลบแบบ soft delete และการ restore เพื่อเรียกคืนข้อมูล

---

## 11. Class / Model Relationship Diagram / แผนภาพความสัมพันธ์ของ Laravel Models

### จุดประสงค์ของไดอะแกรม

Class / Model Relationship Diagram ใช้อธิบายความสัมพันธ์ของ Eloquent Models ใน Laravel แบบย่อ เพื่อให้เห็นว่าฝั่ง code เชื่อมกับฐานข้อมูลอย่างไร ไดอะแกรมนี้ช่วยอธิบายระบบในมุมมองของ developer

### องค์ประกอบในภาพ

- `TransactionHeader` map กับตาราง `Transaction_Header`
- `TransactionDetail` map กับตาราง `Transaction_Detail`
- `User` map กับตาราง `Internal_Users`
- `ExternalUser` map กับตาราง `External_Users`
- `Department` map กับตาราง `Departments`
- `Equipment` map กับตาราง `Equipments`
- `TestMethod` map กับตาราง `Test_Methods`
- `AuditLog` map กับตาราง `Audit_Logs`

### ความสัมพันธ์และเส้นเชื่อม

- `Department` has many `ExternalUser`
- `ExternalUser` belongs to `Department`
- `ExternalUser` has many `TransactionHeader`
- `User` has many `TransactionHeader` ในฐานะผู้รับงาน
- `TransactionHeader` has many `TransactionDetail`
- `TransactionDetail` belongs to `TransactionHeader`
- `TestMethod` belongs to `Equipment`
- `TransactionDetail` belongs to `TestMethod`
- `AuditLog` belongs to `User` ในฐานะ actor ของการกระทำ

### Mermaid Diagram

```mermaid
classDiagram
  class Department {
    +department_id
    +department_name
    +internal_phone
    +is_active
    +externalUsers()
  }

  class ExternalUser {
    +external_id
    +external_name
    +department_id
    +is_active
    +department()
    +transactionHeaders()
  }

  class User {
    +user_id
    +user_name
    +employee_id
    +name
    +role
    +is_active
    +getAuthPassword()
  }

  class Equipment {
    +equipment_id
    +equipment_name
    +is_active
  }

  class TestMethod {
    +method_id
    +method_name
    +equipment_id
    +is_active
    +equipment()
    +transactionDetails()
  }

  class TransactionHeader {
    +transaction_id
    +external_id
    +internal_id
    +dmc
    +cell
    +line
    +shift
    +model
    +receive_date
    +return_date
    +externalUser()
    +internalUser()
    +details()
  }

  class TransactionDetail {
    +detail_id
    +transaction_id
    +method_id
    +internal_id
    +start_time
    +end_time
    +duration_sec
    +judgement
    +transactionHeader()
    +testMethod()
    +inspector()
  }

  class AuditLog {
    +audit_id
    +module
    +action
    +record_type
    +record_id
    +performed_by
    +actor()
  }

  Department "1" --> "*" ExternalUser : has
  ExternalUser "1" --> "*" TransactionHeader : sends
  User "1" --> "*" TransactionHeader : receives
  TransactionHeader "1" --> "*" TransactionDetail : details
  Equipment "1" --> "*" TestMethod : supports
  TestMethod "1" --> "*" TransactionDetail : used by
  User "1" --> "*" TransactionDetail : inspects
  User "1" --> "*" AuditLog : performs
```

### คำอธิบายใต้ภาพสำหรับใส่ในเล่ม

แผนภาพความสัมพันธ์ของ Laravel Models แสดงโครงสร้าง model หลักของระบบและความสัมพันธ์ที่ใช้ใน code โดย `TransactionHeader` เป็น model หลักของงานรับเข้า และ `TransactionDetail` เป็น model ของผลตรวจ ส่วน model อื่นใช้เป็นข้อมูลอ้างอิง เช่น ผู้ใช้ ผู้ส่งงาน วิธีตรวจ อุปกรณ์ และ Audit Log

---

## สรุปการใช้งาน Diagram ในเล่มจบ

ลำดับการนำเสนอที่แนะนำคือเริ่มจากภาพรวมไปสู่รายละเอียด ได้แก่ System Context Diagram, Use Case Diagram, Activity Diagram, ER Diagram, DFD Level 0, DFD Level 1, System Architecture Diagram, Sequence Diagram, State Diagram และ Class / Model Relationship Diagram

ไดอะแกรมทั้งหมดในเอกสารนี้เน้นขอบเขตของระบบ QC Lab Tracking System ตาม workflow หลัก คือการเข้าสู่ระบบ การรับงานตรวจ การบันทึกผลตรวจ การแสดง Dashboard การจัดการ Master Data และการบันทึก Audit Log

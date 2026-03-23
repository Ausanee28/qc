# Performance Bottleneck Checklist

Checklist สำหรับติดตามงานกำจัดคอขวดของเว็บนี้แบบ end-to-end

## Baseline

- [x] เก็บ baseline ของ route หลักด้วย `qc:profile`
- [x] เทียบก่อน-หลังในงานที่เป็น quick win สำคัญ
- [ ] สรุป baseline ปิดงานรอบสุดท้ายอีกครั้งหลังเก็บทุก step

## Backend And Query

- [x] ลด query ซ้ำใน dashboard metrics
- [x] เปลี่ยน date filtering ให้ใช้ index-friendly range query
- [x] ลดภาระ route `/execute-test/pending-jobs-version` ด้วย cache-backed version token
- [x] เก็บ report export ให้ filter ใน SQL และ stream ผลลัพธ์
- [x] ใส่ cache ให้ analytics hotspot บางจุด เช่น performance
- [ ] ตรวจ query ฝั่ง certificate/report/performance รอบปิดงานอีกครั้งบน dataset ใหญ่

## Workflow Pages

- [x] ลด query และ relation overhead ของ `Receive Job`
- [x] ลด query และ relation overhead ของ `Execute Test`
- [x] ลด render work ใน table row ของ workflow pages
- [x] แยก filter state ของ workflow ออกจาก `useForm` ที่ไม่จำเป็น
- [ ] ลด option/data payload ของ `Receive Job` และ `Execute Test` เพิ่มเติม
- [ ] ตรวจว่ามี modal/form state ใดใน workflow ที่ยังโหลดหนักเกินจำเป็น

## Dashboard And Analytics

- [x] แยก chart runtime ให้โหลดเฉพาะที่ใช้
- [x] split chart bundle ออกจาก main app path
- [x] ทำ dashboard first viewport ให้เบากว่าเดิม
- [x] เลื่อน realtime boot ของ dashboard ออกจาก critical path
- [ ] ตรวจ dashboard hydration รอบสุดท้ายบนหน้าจอ mobile และ desktop
- [ ] พิจารณาลด section หรือ deferred data เพิ่ม ถ้ายังรู้สึกหน่วงตอน first load

## Shared App Shell

- [x] ลดขนาด `AuthenticatedLayout`
- [x] ยุบ nav config และลด branch ซ้ำใน shared layout
- [x] ตัด sidebar/mobile nav hover prefetch ที่ทำให้เกิด network noise
- [ ] ตรวจ shared component ที่ยังแบก logic หรือ state เกินจำเป็น

## Master Data

- [x] ลด client-side search overhead ด้วย normalized search path
- [x] ย้าย master data list ไปเป็น server-side search + pagination
- [x] ทำ partial reload หลัง create/update/delete ของ master data
- [x] defer option list ที่ใช้เฉพาะ modal ใน `Test Methods` และ `External Users`
- [ ] ลด modal/form payload ของหน้า master data ที่เหลือถ้ายังมี
- [ ] พิจารณาแยก modal/form เป็น shared component ถ้ามี duplication สูง

## Bundle And Frontend Delivery

- [x] แยก `framework`, `charts`, `realtime`, `http` ออกเป็น vendor chunk
- [x] ลด `app.js` main entry ให้เหลือเฉพาะของจำเป็นจริง
- [ ] ตรวจ `framework-vendor` ว่ายังมี dependency ไหนที่แยกได้อีก
- [ ] ตรวจ shared CSS/utility ที่ทำให้ payload หน้าแรกโตเกินจำเป็น

## Final Verification

- [ ] รัน `npm run build`
- [ ] รัน `php artisan qc:profile` สำหรับ route หลักอีกครั้ง
- [ ] สรุปผลก่อน-หลังเป็นตัวเลข
- [ ] แยก backlog ที่ยังเหลือเป็น quick wins กับ long-term work

## Suggested Next Steps

1. ลด option/data payload ของ `Receive Job` และ `Execute Test`
2. เก็บ modal/form duplication และ shared component path
3. ปิดงานด้วย benchmark before/after รอบสุดท้าย

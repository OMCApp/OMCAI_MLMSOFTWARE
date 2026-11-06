# สถานะโปรเจค
> อัปเดตล่าสุด: 2026-11-04

- **ช่วง:** C — ลงมือทีละ phase
- **โหมด:** เร็ว (ผู้ใช้สั่ง "ทำให้เสร็จเลย ไม่ต้องถามจุกจิก")
- **Phase:** 1 🔨 · 2 🔨 · 3 🔨 · 4 🔨 · 5 🔨 · 6 🔨 (ปิดงาน pre-launch 2026-11-05 — `QA-REPORT.md` แบ่ง PASS / BLOCKED / PRODUCTION ONLY)
- **ขั้นใน phase:** งานที่ทำได้โดยไม่มีข้อมูล/สิทธิ์ของ OMC เสร็จหมด · รอ OMC ตาม `OMC-CHECKLIST.md` (อนุมัติ · document root · SMTP · บัญชีทดสอบ) · ห้ามแตะ production/DNS จนได้คำสั่ง
- **ประตูที่ผ่านแล้ว:** A ✅ (2026-11-03 — brief 3 ฉบับแทนการสัมภาษณ์) · B ✅ (2026-11-03 — ผู้ใช้อนุมัติเต็มรูปแบบล่วงหน้า + ส่งแบบอ้างอิงหน้าแรก)

## ค้างอยู่ที่
Pre-launch ปิดแล้ว (2026-11-05) — อีเมลแจ้งฝ่ายขาย (SMTP + สถานะ + เตือน + ส่งซ้ำ) · LCP มือถือ ≤ 2.5 วิ ทุกหน้าสำคัญเมื่อเป็น HTTPS/HTTP2 (staging HTTP/1.1 ยังเกิน — ข้อจำกัด staging) · SEO 95 URL ผ่านบน staging · เครื่องมือ postcheck/staging-smoke
ค้าง: ทั้งหมดอยู่ใน `OMC-CHECKLIST.md` (🔴 A1–A5 · B1–B4) · หลังขึ้นจริง: `LAUNCH.md` ข้อ 12–13

## ตัดสินใจอะไรไปแล้วที่ไม่ได้อยู่ในไฟล์อื่น
- [2026-11-03] Phase 2: หลังบ้านย้ายไป `/admin` · หน้าสาธารณะทั้งหมดเป็น draft จน Phase 4 · หลัง merge Phase 1 ให้เปลี่ยน `PublicController::LAYOUT` เป็น `site` · รายละเอียดใน NOTES.md หัวข้อ Phase 2
- [2026-11-03] ประตู A/B ไม่ได้หยุดถาม — brief ระบุชัดว่าอนุมัติเต็มรูปแบบและห้ามถามจุกจิก (คำสั่งผู้ใช้ > CLAUDE.md)
- [2026-11-03] merge เข้า `main` + push เองได้ (ผู้ใช้สั่ง) · push main = deploy staging เท่านั้น
- [2026-11-03] เส้นทางดีไซน์ = ค (ผู้ใช้มีภาพแบบหน้าแรก) · หน้าอื่นสืบทอดจากหน้าแรก
- [2026-11-03] Phase 1: ช่อง SEO head ของ `layouts/site.php` = include `partials/seo-head.php` (Phase 2 สร้าง) ส่ง `$seo` + `$jsonLd` · ไม่ใช่ string HTML (ชุดตรวจ output-escape)
- [2026-11-03] Phase 1: preview หน้าแรกที่ `/_preview/home` เฉพาะ env ที่ไม่ใช่ production
- [2026-11-04] Phase 3 (CMS admin) 🔨 สร้างเสร็จบน branch ของ Phase 3 — migration 012–015 · secret ใหม่ `APP_KEY` (ผู้ใช้ต้องเพิ่ม) · คำแปลจีนของ UI หลังบ้านรวมเข้า `lang/zh.php` แล้ว · รายละเอียด NOTES.md หัวข้อ Phase 3
- [2026-11-04] Phase 3 ขึ้น staging แล้ว (APP_KEY พร้อม) · ปุ่ม Demo ชี้ demomlm.omc.co.th · หน้าแรกปรับตาม Mockup ชุดที่ 2 (NOTES.md หัวข้อเดียวกัน)
- [2026-11-04] ผู้ใช้สั่งเว็บสาธารณะ 3 ภาษา (ไทย root · /en/ · /zh/) + เกณฑ์รับงานชุดใหม่ — รายละเอียดใน NOTES.md หัวข้อ Phase 4

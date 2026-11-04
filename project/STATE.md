# สถานะโปรเจค
> อัปเดตล่าสุด: 2026-11-04

- **ช่วง:** C — ลงมือทีละ phase
- **โหมด:** เร็ว (ผู้ใช้สั่ง "ทำให้เสร็จเลย ไม่ต้องถามจุกจิก")
- **Phase:** 1 🔨 · 2 🔨 · 3 🔨 (CMS — merge กับ Phase 4 แล้วบน branch ของ Phase 3) · 4 🔨 · 5 🔨
- **ขั้นใน phase:** Phase 4 สร้างครบ รอตรวจ · Phase 3 (CMS) ทำคู่ขนาน — ต้องมีช่อง locale/translation_group ในหน้าแก้ไข
- **ประตูที่ผ่านแล้ว:** A ✅ (2026-11-03 — brief 3 ฉบับแทนการสัมภาษณ์) · B ✅ (2026-11-03 — ผู้ใช้อนุมัติเต็มรูปแบบล่วงหน้า + ส่งแบบอ้างอิงหน้าแรก)

## ค้างอยู่ที่
หน้าแรกตาม Mockup ชุดที่ 2 ขึ้น staging แล้ว — รอภาพทีมจริง / ตัวเลขที่พิสูจน์ได้ / รีวิวที่ได้รับอนุญาต (QUESTIONS.md) · Phase 6 QA ต่อ · merge Phase 4 · native speaker ตรวจ EN/ZH · ตั้ง MAIL_ENABLED/MAIL_FROM ของ staging

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

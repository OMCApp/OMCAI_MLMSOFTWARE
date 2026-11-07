# สถานะโปรเจค
> อัปเดตล่าสุด: 2026-11-07

- **ช่วง:** C — ลงมือทีละ phase
- **โหมด:** เร็ว (ผู้ใช้สั่ง "ทำให้เสร็จเลย ไม่ต้องถามจุกจิก")
- **Phase:** 1 🔨 · 2 🔨 · 3 🔨 · 4 🔨 · 5 🔨 · 6 🔨 · Production Preparation ✅ (2026-11-06 — `GO-NO-GO.md`: NO-GO วันนี้ รอ OMC 7 ข้อ)
- **ขั้นใน phase:** ผู้ใช้อนุมัติ Production Preparation (2026-11-06) แต่ **ยังไม่อนุมัติ deploy ทับ production / เปลี่ยน DNS / ลบ WordPress** · ขั้นตอนจริงใน `LAUNCH.md` · รอ OMC ตาม `OMC-CHECKLIST.md`
- **ประตูที่ผ่านแล้ว:** A ✅ (2026-11-03 — brief 3 ฉบับแทนการสัมภาษณ์) · B ✅ (2026-11-03 — ผู้ใช้อนุมัติเต็มรูปแบบล่วงหน้า + ส่งแบบอ้างอิงหน้าแรก)

## ค้างอยู่ที่
[2026-11-07] Blog redesign ✅ บน staging · GEO / AI Search Visibility ✅ ส่วนที่ทำได้ในระบบ (llms.txt · Organization schema · sitemap WordPress 301 · IndexNow · เอกสาร `docs/geo/` 01–05 · รายงาน `project/GEO-REPORT.md`) — เนื้อหาใหม่ยังไม่เผยแพร่ รอ OMC ยืนยันข้อเท็จจริง
Production Preparation เสร็จ (2026-11-06) — `/_preflight` · release builder · verify-wp-backup · ซ้อมติดตั้งจากแพ็กเกจลงฐานเปล่า (migrated 28) · runbook แบบโฟลเดอร์ใหม่ (ไม่ทับ WordPress) · ตรวจเซิร์ฟเวอร์ production + อีเมล DNS จากภายนอก · repo GitHub เป็นสาธารณะ (ไม่มีความลับหลุด)
ค้าง: 🔴 7 ข้อใน `GO-NO-GO.md` (อนุมัติ · backup WordPress · ฐานใหม่ · .env · `/_preflight` บนเครื่องจริง · SMTP · commit) — รายละเอียด `OMC-CHECKLIST.md`

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

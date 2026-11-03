# สถานะโปรเจค
> อัปเดตล่าสุด: 2026-11-03

- **ช่วง:** C — ลงมือทีละ phase
- **โหมด:** เร็ว (ผู้ใช้สั่ง "ทำให้เสร็จเลย ไม่ต้องถามจุกจิก")
- **Phase:** 1 — Design system + public layout 🔨 (รอตรวจช่วง D) · Phase 2 — Content engine 🔄 (ทำขนานใน worktree)
- **ขั้นใน phase:** Phase 1 สร้างเสร็จบน branch worktree — รอ merge เข้า `rebuild-cms`
- **ประตูที่ผ่านแล้ว:** A ✅ (2026-11-03 — brief 3 ฉบับแทนการสัมภาษณ์) · B ✅ (2026-11-03 — ผู้ใช้อนุมัติเต็มรูปแบบล่วงหน้า + ส่งแบบอ้างอิงหน้าแรก)

## ค้างอยู่ที่
แตก Phase 1 (ดีไซน์ + layout) และ Phase 2 (DB + router + SEO infra) ให้ agent ทำขนานกันบน branch แยก แล้ว merge เข้า `rebuild-cms`

## ตัดสินใจอะไรไปแล้วที่ไม่ได้อยู่ในไฟล์อื่น
- [2026-11-03] ประตู A/B ไม่ได้หยุดถาม — brief ระบุชัดว่าอนุมัติเต็มรูปแบบและห้ามถามจุกจิก (คำสั่งผู้ใช้ > CLAUDE.md)
- [2026-11-03] merge เข้า `main` + push เองได้ (ผู้ใช้สั่ง) · push main = deploy staging เท่านั้น
- [2026-11-03] เส้นทางดีไซน์ = ค (ผู้ใช้มีภาพแบบหน้าแรก) · หน้าอื่นสืบทอดจากหน้าแรก
- [2026-11-03] Phase 1: ช่อง SEO head ของ `layouts/site.php` = include `partials/seo-head.php` (Phase 2 สร้าง) ส่ง `$seo` + `$jsonLd` · ไม่ใช่ string HTML (ชุดตรวจ output-escape)
- [2026-11-03] Phase 1: preview หน้าแรกที่ `/_preview/home` เฉพาะ env ที่ไม่ใช่ production

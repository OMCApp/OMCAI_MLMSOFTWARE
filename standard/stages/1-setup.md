# วางโครงโปรเจค

> **ทำครั้งเดียวต่อโปรเจค** ในช่วง B ก่อนเริ่ม phase แรก
> การเก็บความต้องการอยู่ที่ [`../interview.md`](../interview.md) · ภาพรวมอยู่ที่ [`../flow.md`](../flow.md)

---

## ‼️ ถ้า clone มาจากมาตรฐานนี้ ของมีอยู่แล้วทั้งหมด

**หน้าที่ในขั้นนี้คือตรวจว่าของที่มีทำงานถูก ไม่ใช่สร้างใหม่**

ข้ามไปที่ [ตั้งค่าต่อโปรเจค](#ตั้งค่าต่อโปรเจค) แล้วต่อด้วย [เกณฑ์ผ่านด่าน](#เกณฑ์ผ่านด่าน) ได้เลย

รายการข้างล่างเก็บไว้เป็น**สเปกว่าโครงต้องมีอะไร** และใช้กรณีต้องวางโครงเองในระบบที่มีอยู่ก่อนแล้ว

---

## ลำดับการวางโครง

ทำตามลำดับนี้ เพราะแต่ละข้อพึ่งข้อก่อนหน้า

| # | ทำอะไร | ดูที่ |
|---|---|---|
| 1 | โครงโฟลเดอร์ · `core/autoload.php` (PSR-4 เขียนเอง ไม่ใช้ composer) · `.env` + `.env.example` + `.gitignore` | [`../rule.md`](../rule.md) ข้อ 2 |
| 2 | `core/Path.php` · `core/Router.php` · `core/Database.php` · `core/Controller.php` · `core/Model.php` | [`../rule.md`](../rule.md) ข้อ 4-5 |
| 3 | `config/config.php` — อ่าน `.env` · timezone · `APP_ENV` default `production` · **รายการภาษาที่เปิดใช้** | [`../rule.md`](../rule.md) ข้อ 9 |
| 4 | `core/Logger.php` + รหัสอ้างอิง error + หน้า 403/404/500/maintenance ที่ใช้ layout กลาง | [`../rule.md`](../rule.md) ข้อ 8 |
| 5 | **ระบบภาษา** — `core/Lang.php` + `__()` + `lang/th.php` + `lang/en.php` + helper วันที่/ตัวเลขตามภาษา | [`../features/i18n.md`](../features/i18n.md) |
| 6 | **Auth + Middleware** — `Auth::id()` / `Auth::role()` / `Auth::check()` + rate limit | [`../security.md`](../security.md) ข้อ 1-2 |
| 7 | **audit log ใน `core/Model.php`** | [`../features/audit-log.md`](../features/audit-log.md) |
| 8 | `core/Security.php` (CSRF · escape · security headers) · `core/Request.php` · `core/Uploader.php` | [`../security.md`](../security.md) |
| 9 | `project/DESIGN.md` → `php scripts/design-sync.php` → `app.css` · layout กลาง + nav + **footer** + ปุ่มสลับภาษา + ปุ่มสลับโหมด | [`../design.md`](../design.md) |
| 10 | component กลางชุดพื้นฐานใน `partials/` — ปุ่ม ฟอร์ม ตาราง modal toast confirm upload icons | [`../components.md`](../components.md) |
| 11 | ระบบแจ้งปัญหา (ปุ่มใน layout กลาง) | [`../features/support-system.md`](../features/support-system.md) |
| 12 | หน้าคู่มือ — route `/manual` + ทะเบียน `config/manuals.php` + บทความของหน้าแรกและหน้า login | [`../features/manual.md`](../features/manual.md) |
| 13 | migration `001_create_users_table` (มี `locale`, `theme`, `deleted_at`) · `002_create_audit_logs_table` · `003_create_support_requests_table` · `004_create_login_attempts_table` · seeder admin | [`../rule.md`](../rule.md) ข้อ 5 |
| 14 | `scripts/` + `.github/workflows/deploy.yml` + `git config core.hooksPath scripts/hooks` | — |
| 15 | README ครบทุกหัวข้อ | [`../rule.md`](../rule.md) ข้อ 10 |

### ‼️ 2 ข้อที่ห้ามเลื่อนไปทำทีหลังเด็ดขาด

> **ข้อ 7 (audit log) ต้องเสร็จก่อนเขียน CRUD ตัวแรกของระบบ**
> เขียนฟีเจอร์ไปก่อนแล้วค่อยใส่ทีหลัง จะต้องไล่แทรกทุกจุดที่แก้ข้อมูลและจะลืมบางจุดแน่นอน ทำให้ log ไม่ครบและเชื่อถือไม่ได้ทั้งระบบ

> **ข้อ 5 (ระบบภาษา) ต้องเสร็จก่อนเขียน view ตัวแรกของระบบ**
> ด้วยเหตุผลเดียวกัน — เขียนหน้าไปก่อนแล้วค่อยทำระบบภาษา ต้องไล่แก้ทุกข้อความในทุกหน้าย้อนหลังและจะเหลือค้างแน่นอน

---

## ตั้งค่าต่อโปรเจค

สิ่งที่ต้องทำใหม่ทุกโปรเจคแม้จะ clone มาจากมาตรฐานแล้ว

- [ ] `.env` — ข้อมูลฐานข้อมูล · `APP_URL` · `MIGRATE_TOKEN` (สุ่มใหม่ทุกโปรเจค อย่าใช้ซ้ำ)
- [ ] สร้างฐานข้อมูลด้วย charset `utf8mb4` collation `utf8mb4_unicode_ci`
- [ ] `php scripts/migrate.php` แล้วรัน seeder
- [ ] `project/DESIGN.md` มีค่าจริงแล้ว (ไม่ใช่ค่าตัวอย่างของมาตรฐาน) แล้วรัน `design-sync`
- [ ] `php scripts/make-favicon.php` ← **หลัง `DESIGN.md` มีค่าจริงแล้ว** ไม่งั้นแท็บจะยังเป็นสีเทาของโครงเปล่า
- [ ] `lang/` มีไฟล์ครบทุกภาษาที่โปรเจคนี้เปิดใช้
- [ ] `git config core.hooksPath scripts/hooks` ← **ทุกคนในทีมต้องสั่งเองบนเครื่องตัวเอง** เพราะ `.git/hooks/` ไม่ติดมากับการ clone
- [ ] ตั้ง GitHub Secrets: `FTP_HOST` · `FTP_USER` · `FTP_PASS` · `MIGRATE_TOKEN` · `APP_URL`
- [ ] ตั้ง cron บนเครื่องจริงสำหรับ `scripts/backup.php`
- [ ] ลบสิ่งที่ไม่เกี่ยวกับโปรเจคนี้ออกจาก `project/` และเขียน `project/NOTES.md` ถ้ามีจุดที่ต่างจากมาตรฐาน

---

## เกณฑ์ผ่านด่าน

- [ ] ตรวจ skill ตาม [`../skills.md`](../skills.md) แล้ว
- [ ] `project/BRIEF.md` · `BRIEF-DESIGN.md` · `SCOPE.md` · `PLAN.md` · `DESIGN.md` ครบและผู้ใช้ยืนยันแล้ว
- [ ] โครงโปรเจครันขึ้นได้จริง เปิดหน้าแรกและหน้า login ได้ไม่มี error
- [ ] **login/logout ทำงานได้ และ Middleware กันผู้ใช้ที่ไม่มีสิทธิ์ได้จริง — ทดสอบด้วยการเรียก URL ตรง**
- [ ] **audit log บันทึกอัตโนมัติแล้ว** — ทดสอบด้วยการแก้ข้อมูล 1 รายการแล้วเปิดตารางดูว่ามีแถวเกิดขึ้นพร้อมค่าก่อน-หลังถูกต้อง
- [ ] ปุ่มแจ้งปัญหาแสดงทุกหน้า ส่งเรื่องเข้าฐานข้อมูลได้จริง
- [ ] **หน้า `/manual` เปิดได้ และกดปุ่มซัพพอร์ต → วิธีใช้งาน แล้วไปบทความของหน้านั้นได้ตรง**
- [ ] `app.css` generate จาก `DESIGN.md` แล้ว และไม่มีค่าสี/ระยะ hardcode ที่ไหน
- [ ] **สลับภาษาได้จริงทุกภาษาที่เปิดใช้** และค่าคงอยู่หลัง logout/login (บันทึกลง `users.locale`)
- [ ] **สลับโหมดสว่าง/มืดได้ ไม่มี flash ตอนโหลดหน้า** และค่าคงอยู่ข้าม session
- [ ] ไฟล์ภาษาทุกไฟล์มี key ครบเท่ากัน
- [ ] ทุกหน้ามี footer
- [ ] **pre-commit hook ทำงานจริง — ทดสอบด้วยการลอง commit ไฟล์ที่ผิดกฎ 1 ครั้งแล้วต้องถูกบล็อก** ไม่ใช่แค่สั่ง config แล้วเชื่อว่าได้
- [ ] `php scripts/check.php` ผ่านทั้งหมด

# สิ่งที่มาตรฐานยังไม่ครอบคลุม

## [ ] 2026-11-03 · ก่อนช่วง A — AI ใช้ `git add -A` ตอนที่ไฟล์มาตรฐานเพิ่งถูกคัดลอกเข้ามา แล้ว commit + push ลง `main`

**เกิดอะไร:** ระหว่างทำ SEO baseline ไฟล์มาตรฐาน 3.20.0 ทั้งชุด (185 ไฟล์) ถูกคัดลอกเข้าโฟลเดอร์ AI ไม่ได้สังเกตเพราะเช็ค `git status` ไว้ตั้งแต่ตอนเริ่ม session แล้วรัน `git add -A` → commit `9f8cfa8` ลง `main` → push → GitHub Actions deploy ทั้งชุดขึ้น FTP_SERVER_DIR
และ `.gitignore` ถูกเขียนทับด้วยเวอร์ชันสั้นที่ไม่ครอบคลุม `vendor/` · `storage/*` (ตอนนั้นยังไม่มี `.env` จึงไม่มี secret หลุด)
**กฎข้อไหนเกี่ยวข้อง:** `CLAUDE.md` ข้อ 5 Git — "ทำงานใน branch เท่านั้น ห้าม commit ลง `main`" · `rule.md` — `.gitignore` ขั้นต่ำ
**ทำไมกฎไม่ช่วย:** ตอนนั้น `CLAUDE.md` ยังไม่อยู่ในโฟลเดอร์ AI จึงยังไม่ได้โหลดกฎ · pre-commit hook ยังไม่ถูกเปิดเพราะยังไม่ได้รัน `setup.php` · มาตรฐานไม่มีจุดตรวจตอน "คัดลอกมาตรฐานเข้าโปรเจคที่มี repo และ CI อยู่แล้ว"
**หลักฐาน:** `git show --stat 9f8cfa8`
**ราคาที่จ่าย:** โค้ดโครงระบบขึ้น server ทดสอบโดยยังไม่ได้ตรวจ · ประวัติ `main` มี commit ที่ไม่ผ่าน branch 1 ตัว (ไม่ได้ reset เพราะต้องขออนุญาตก่อน)
**ข้อเสนอ:** (1) README มาตรฐานควรมีหัวข้อ "นำมาตรฐานเข้าโปรเจคที่มี repo/CI อยู่แล้ว" ให้รัน `setup.php` ก่อน commit ใดๆ (2) ส่ง `.gitignore` มากับมาตรฐานด้วย หรือให้ `setup.php` สร้าง/เติมให้ครบตามข้อกำหนด
**ตรวจด้วยเครื่องได้ไหม:** ได้ — `setup.php --check` ตรวจว่า `.gitignore` มีบรรทัดบังคับครบ

## [ ] 2026-11-03 · ก่อนช่วง A — `setup.php` เตือนว่า "ยังผูกกับ repo มาตรฐาน" ทั้งที่ origin คือ repo ของโปรเจคเอง

**เกิดอะไร:** origin ชี้ `OMCApp/OMCAI_MLMSOFTWARE` ซึ่งเป็น repo ของโปรเจคนี้ (ผู้ใช้สร้างเอง ใส่ secret FTP ไว้แล้ว) แต่ `setup.php` ข้อ 3ก แนะนำให้รัน `init-project.php --yes` ซึ่งจะตัด origin ทิ้ง
**กฎข้อไหนเกี่ยวข้อง:** `scripts/setup.php` ข้อ 3ก
**ทำไมกฎไม่ช่วย:** เงื่อนไขดูแค่ "มี origin + ไม่มี STATE.md + ไม่มี commit ที่ยังไม่ push" ซึ่งเป็นจริงทั้งกับ clone สดของมาตรฐาน และกับโปรเจคที่ push ไปแล้ว
**หลักฐาน:** ผล `php scripts/setup.php` วันที่ 2026-11-03
**ราคาที่จ่าย:** ถ้าทำตามโดยไม่ดู จะต้องตั้ง remote ใหม่และ CI/secret ที่ผูกไว้หลุด
**ข้อเสนอ:** เทียบ origin กับ URL ของ repo มาตรฐาน (เก็บไว้ใน `standard/.manifest`) แทนการเดาจากสถานะ
**ตรวจด้วยเครื่องได้ไหม:** ได้

## [ ] 2026-11-03 · ก่อนช่วง A — `core/` ของมาตรฐาน 3.20.0 มีช่องโหว่ 5 จุด (security review อัตโนมัติจับได้ ยืนยันในโค้ดแล้ว)

**เกิดอะไร:**
1. **HIGH** `core/Auth.php:199` — `clearFailures()` ลบ `login_attempts` แบบ `username = :u OR ip_address = :ip` → login สำเร็จด้วยบัญชีตัวเองจาก IP เดียวกัน ล้างประวัติเดาเลขของบัญชีอื่นได้ = ข้ามระบบกัน brute-force
2. **MEDIUM** `core/Auth.php:164` — นับ lockout แบบ `username OR ip` → คนนอกยิงรหัสผิดใส่ username ของแอดมินจนบัญชีจริงถูกล็อก (DoS)
3. **MEDIUM** `core/Auth.php` — `check()` ดูแค่ session · ปิดใช้งาน/ลบ/เปลี่ยน role ผู้ใช้แล้ว session เดิมยังใช้ได้
4. **MEDIUM** `core/Auth.php:75` — ไม่เรียก `password_verify()` เมื่อไม่พบผู้ใช้ → เดาจากเวลาตอบได้ว่ามี username นี้ไหม
5. **MEDIUM** `core/Controller.php:76` — `back()` เช็ค referer ด้วย `str_contains($referer, $host)` → `https://evil.com/?x=<host>` ผ่าน = open redirect
**กฎข้อไหนเกี่ยวข้อง:** `standard/security.md` (rate limit / session / redirect)
**ทำไมกฎไม่ช่วย:** โค้ดกลางเองผิด และ `check.php` ไม่มีข้อตรวจสำหรับรูปแบบเหล่านี้
**ราคาที่จ่าย:** ต้องแก้ในโปรเจคนี้ (จะแก้ตอนสร้างระบบแอดมินบน branch) และทุกโปรเจคที่ใช้ 3.20.0 มีช่องโหว่เดียวกัน
**ข้อเสนอ:** แก้ที่ repo มาตรฐาน: (1)(2) ใช้คู่ username+IP · (3) โหลด `is_active/role/deleted_at` ใหม่ใน AuthMiddleware ทุก request · (4) dummy hash · (5) `parse_url` เทียบ scheme+host ตรงตัว หรือ redirect เฉพาะ path
**ตรวจด้วยเครื่องได้ไหม:** ได้บางข้อ — grep หา `OR ip_address` และ `str_contains($referer`

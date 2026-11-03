# จุดที่โปรเจคนี้ต่างจากมาตรฐาน

## Deploy ใช้ FTP ธรรมดา (ไม่ใช่ FTPS/SFTP)
- **ต่างตรงไหน:** `.github/workflows/deploy.yml` ส่งไฟล์ผ่าน FTP ไม่เข้ารหัส ทั้งที่ brief ข้อ 6 กำหนดให้ใช้ FTPS/SFTP
- **ใครตัดสิน:** ผู้ใช้ — 2026-11-03 ตอบว่า "ไม่เปลี่ยน"
- **ผลที่ต้องรู้:** รหัส FTP วิ่งบนเครือข่ายแบบอ่านได้ · ถ้าจะเปลี่ยน เพิ่ม `protocol: ftps` ใน step FTP deploy บรรทัดเดียว

## เครื่องมือ SEO migration อยู่นอกโครงมาตรฐาน
- `tools/seo-audit/` (crawler สำหรับ baseline/เทียบผล) และ `docs/seo/` (ผล baseline) ไม่ถูก deploy ขึ้น server (exclude ใน workflow)

## Staging ตั้ง document root ที่ `public/` ไม่ได้ → มี `.htaccess` ที่ root ของโปรเจค
- **ต่างตรงไหน:** มาตรฐานข้อ 8 ให้ document root ชี้ `public/` · staging อยู่ที่ `http://IP/~omcai/mlmsoftware/` (userdir) ตั้งไม่ได้
- **ทำอะไรแทน:** `.htaccess` ที่ root ส่งทุก request เข้า `public/` ภายใน · `Core\Path::base()` ตัด `/public` ออกให้ลิงก์ถูก · CI ใส่ `RewriteBase` จาก `APP_URL` · ขั้นสุดท้ายของ CI ยิงตรวจว่า `.env` / `app/` / `core/` / `config/` เปิดไม่ได้
- **production ต้องตั้ง document root ที่ `public/` ตามมาตรฐาน**

## `APP_ENV=staging` + `Core\Staging`
- ทุก environment ที่ไม่ใช่ production ส่ง `X-Robots-Tag: noindex` · ตั้ง `STAGING_PASS` แล้วต้องใส่รหัส (Basic Auth) ยกเว้น `/_health` และ `/_migrate`
- เพิ่ม `/_health` (ตอบ ok + ต่อ DB ได้) ให้ CI ใช้ตรวจ เพราะหน้าแรกของ staging ตอบ 401

## `.env` ของ staging สร้างใน CI จาก GitHub Secrets
- มาตรฐานไม่ได้บอกว่า `.env` บน server มาจากไหน · โปรเจคนี้ให้ CI เขียนจาก secret แล้วอัปด้วย lftp ทุกครั้ง — ไม่มีใครต้องแก้ไฟล์บน server ด้วยมือ
- รายละเอียด deploy / rollback: [`DEPLOY.md`](DEPLOY.md)

## `deploy.yml` ปรับจากของมาตรฐาน
- ชื่อ secret ตามที่ผู้ใช้ตั้ง (`FTP_SERVER` …) · มี `FTP_SERVER_DIR` · ไม่ส่ง `project/` `docs/` `tools/` `CLAUDE.md` `AGENTS.md` ขึ้น server
- โหมดปรับปรุง: วาง flag บน server ก่อนอัป และ `/_migrate` เป็นคนลบเมื่อสำเร็จ (ของมาตรฐานลบแค่บน runner — ดู FEEDBACK)

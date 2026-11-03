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

## ข้อแลกเปลี่ยนด้านความปลอดภัยที่รู้ตัวและยอมรับไว้
- **ล็อกต่อ username รวมทุก IP ที่ 50 ครั้ง/15 นาที** — กันเดารหัสแบบกระจาย IP แต่คนนอกที่ยิงครบ 50 ครั้งล็อกเจ้าของบัญชีได้ 15 นาที · ล็อกแข็งจริงอยู่ที่คู่ username+IP (5 ครั้ง) · ตอนทำระบบแอดมินจะเพิ่ม "อุปกรณ์ที่เคยเข้าสำเร็จไม่โดนเพดานนี้"
- **staging เป็น HTTP + FTP ธรรมดา** — Basic Auth และ token วิ่งแบบไม่เข้ารหัส (ผู้ใช้เลือก FTP · staging เปิดผ่าน IP จึงไม่มี HTTPS) · ชดเชยด้วย STAGING_PASS ≥ 20 ตัว และ token ส่งทาง header ไม่ใช่ URL
- **IP ที่เคยเข้าบัญชีสำเร็จใน 90 วัน ไม่โดนเพดาน 50 ครั้งต่อ username** — คนที่อยู่ IP เดียวกัน (NAT ออฟฟิศ / CGNAT) ได้ข้อยกเว้นนี้ด้วย แต่ยังติดล็อกคู่ username+IP 5 ครั้ง และต่อ IP 20 ครั้ง/15 นาที · **ต้องทำตอนสร้างระบบแอดมิน:** เปลี่ยนจากผูกกับ IP เป็น cookie อุปกรณ์ที่ลงลายเซ็น (ออกให้ตอนล็อกอินสำเร็จ)

## Phase 2 — content engine + SEO (จุดที่ต่างจากมาตรฐาน / ข้อตกลงที่ phase อื่นต้องรู้)

- **หลังบ้านทั้งหมดอยู่ใต้ `/admin`** — `/admin/login` · `/admin` (dashboard) · `/admin/manual` · `/admin/support` · `/admin/theme` · `/admin/language` · `/admin/files/{id}` · `/admin/options/{key}` · URL อื่นทั้งหมดเป็นของเว็บสาธารณะ (มาจากฐานข้อมูล)
- **Router มี `fallback()`** — GET/HEAD ที่ไม่ตรง route ไหนไป `SiteController@resolve` · ลำดับการจับ URL ทั้งหมดอยู่ใน `App\Services\SiteResolver` ที่เดียว · HEAD ตอบเหมือน GET · ส่วนตัวอักษรของ pattern ถูก `preg_quote` แล้ว (เดิม `.` ใน `/sitemap.xml` ตรงกับทุกตัวอักษร)
- **`Core\Path::requested()`** คืน path ที่ decode แล้วและคง `/` ท้าย (`current()` ตัดทิ้ง) · `Path::encode()` เข้ารหัสทีละช่วงสำหรับ Location/sitemap/canonical
- **path / slug เก็บแบบ decode และใช้ collation `utf8mb4_bin`** — `unicode_ci` พับตัวพิมพ์และมองเครื่องหมายไทยบางตัวเป็นตัวที่ข้ามได้ ทำให้ slug ไทยชนกันใน unique key · unique key รวมคอลัมน์ generated `alive` (1/NULL) เพื่อให้แถวที่ soft delete ไม่กันการสร้าง path เดิมซ้ำ
- **ข้อความไทยใน seed (.sql) เขียนเป็น hex** (`CONVERT(X'..' USING utf8mb4)`) โดยมีข้อความจริงในคอมเมนต์เหนือแถว — `check.php` (no-thai-values) ห้ามภาษาไทยในบรรทัด INSERT/VALUES ซึ่งตั้งใจกันค่า status ไม่ใช่เนื้อหา · seed ที่มีเนื้อหาไทยเยอะ (Phase 5) ควรนำเข้าผ่านสคริปต์ PHP แทน
- **ตัวนับ `redirects.hits` และ `not_found_log` ไม่ลง audit log** — เป็นตัวนับ traffic ที่เขียนทุก request (เหตุผลเดียวกับ `login_attempts`) · การสร้าง/แก้/ลบ redirect และการซ่อนแถว 404 ยังผ่าน base Model ตามปกติ
- **canonical / og:url / sitemap `<loc>` ใช้ `config('seo.canonical_url')`** (env `CANONICAL_URL`, ค่าเริ่ม `https://mlmsoftware.omc.co.th`) ไม่ใช่ `APP_URL` — บน staging APP_URL คือ `http://IP/~omcai/...` ซึ่งห้ามหลุดเป็น canonical · บรรทัด `Sitemap:` ใน robots.txt ใช้ APP_URL ตามแผน (production ค่าเดียวกัน)
- **noindex ที่ระบบใส่เองมีแค่สามที่**: `Core\Staging` (ทุก non-production) · หน้า 404/410 (meta) · การดูตัวอย่าง draft (`?preview=1` ต้องล็อกอินเป็น admin/editor — meta + `X-Robots-Tag` + `no-store`) · `SeoService` ห้ามดู environment
- **สัญญาของ layout เว็บสาธารณะ** (`layouts/site-fallback.php` ตอนนี้ → `layouts/site.php` ของ Phase 1): `$seoHead` (string), `$jsonLd` (string), `$content`, `$breadcrumbs` (`[['label','url'|null]]`), `$title` (H1 → `partials/page-head.php`), `$siteBrand` · สลับ layout ที่ `App\Controllers\PublicController::LAYOUT` บรรทัดเดียว · layout พิมพ์ `$seoHead`/`$jsonLd` ด้วย `echo` ไม่ใช่ `<?= e() ?>` เพราะเป็น HTML ที่ partial escape ครบแล้ว (เหตุผลเดียวกับ `$content`)
- **บล็อกของหน้า**: `pages.blocks` = JSON list ของ `{"type": "<key>", "data": {...}}` · partial ที่ `partials/blocks/<type>.php` (รับทั้ง kebab และ snake) ได้ตัวแปร `$block = ['type','data']` · ชนิดที่ยังไม่มี partial ถูกข้าม · บล็อก `faq` ใส่ `data.items[{question, answer}]` หรือ `data.group` (ดึงจากตาราง faqs ที่ published) — FAQPage schema ใช้ข้อมูลชุดเดียวกับที่แสดง · `rich_text.data.html` ผ่าน `HtmlSanitizer`
- **HTML ของบทความผ่าน `App\Services\HtmlSanitizer` (allowlist) ก่อนแสดงทุกครั้ง** · `<h1>` ในเนื้อหาถูกลดเป็น `<h2>` (H1 มาจาก template ตัวเดียว)
- **`server.php` ตั้ง `SCRIPT_NAME=/index.php`** — built-in server ตั้งเป็น path ที่ขอ ทำให้ `Path::base()` ตัด `/admin` ทิ้งและ route ซ้อนชั้นหาไม่เจอ
- **CI ตรวจ `/admin/login` = 200 (ด้วยรหัส staging) แทน `/`** — หน้าสาธารณะเป็น draft จน Phase 4 เผยแพร่ `/` จึงเป็น 404 โดยตั้งใจ · เปลี่ยนกลับเป็น `/` เมื่อหน้าแรกเผยแพร่แล้ว
- **ชุดทดสอบ**: `php tests/run.php` — เปิด built-in server ชี้ฐานข้อมูลจาก config (.env หรือ env vars) ใส่ข้อมูล `zz-test-*` แล้วลบทิ้งตอนจบ · ไม่ deploy ขึ้น server (exclude ใน workflow) · ปฏิเสธการรันเมื่อ APP_ENV=production

## เว็บสาธารณะเป็นโหมดสว่างอย่างเดียว (Phase 1)
- **ต่างตรงไหน:** กฎเหล็กข้อ 19 ให้ทุกระบบสลับสว่าง/มืดได้ · `layouts/site.php` บังคับ `data-theme="light"` ไม่มีปุ่มสลับ
- **ใครตัดสิน:** ผู้ใช้ — brief ฉบับ 3 "พื้นขาว/สว่างเป็นหลัก · ไม่เอาเว็บมืด" (บันทึกใน SCOPE ด้วย)
- **ผลที่ต้องรู้:** token ทุกตัวใน DESIGN.md ยังมีค่าโหมดมืดครบ (หลังบ้าน `layouts/app.php` ใช้) · CSS ของเว็บสาธารณะใช้ token เสมอ เปิดโหมดมืดวันหลังได้โดยไม่รื้อ

## เว็บสาธารณะใช้ layout แยก (`layouts/site.php`) ไม่อ่าน `layout.nav`
- `layout.nav` ใน DESIGN.md = `sidebar` เป็นของหลังบ้าน · เว็บสาธารณะเป็นแถบบนเสมอ (design.md 5.15) ผ่าน `partials/site-header.php`
- view-model ของ header/footer มาจาก `App\Services\SiteService::layout()` · ค่าตั้งต้นที่ `config/site.php` (ข้อมูลติดต่อดึงจากหน้า /contact/ ของเว็บเดิม 2026-11-03)
- บล็อกของหน้าผ่าน `App\Services\BlockService::prepare()` → `partials/blocks.php` → `partials/blocks/<ชนิด>.php` · ตัวเลือกฟอร์ม lead อยู่ที่ `BlockService::LEAD_OPTIONS` ที่เดียว (Phase 4 validate กับชุดนี้)
- หน้าแรกเบี่ยงจากแบบตรงไหน ดูตาราง "ความต่างจากแบบ (C4ก)" ใน DESIGN.md — อย่า "แก้กลับ" ให้ตรงภาพโดยไม่อ่านเหตุผล
- `/_preview/home` (+ `?all=1` ดูบล็อกที่เหลือ) มีเฉพาะ env ที่ไม่ใช่ production — ลบได้เมื่อ CMS แสดงหน้าแรกจาก DB แล้ว

## ฟอนต์ self-host (vendor asset)
- Prompt 600 + IBM Plex Sans Thai 400/500/600 (ชุดย่อย thai + latin) ดาวน์โหลดจาก npm `@fontsource/prompt@5.3.0` · `@fontsource/ibm-plex-sans-thai@5.3.0` เก็บที่ `public/assets/vendor/fonts/` พร้อมไฟล์ OFL · ไม่ใช่ dependency ของโค้ด ไม่โหลดจาก CDN ตอนรัน
- `.gitignore` เดิมมี `vendor/` ซึ่งจับ `public/assets/vendor/` ไปด้วย — เพิ่ม `!public/assets/vendor/` ให้ commit ได้

## โลโก้วาดใหม่เป็น SVG
- `public/assets/images/brand/` — วาดจากพิกเซลของ `project/design/refs/logo-omc.png` (สี่เหลี่ยม 3 ช่อง · O M C · บรรทัดชื่อบริษัท) ไม่ได้ออกแบบใหม่ · มี mark / stacked / horizontal / รุ่นพื้นเข้ม + PNG ที่ render ด้วย Chrome headless
- บรรทัดชื่อบริษัทใช้ `<text>` ฟอนต์ระบบ (Arial Narrow → Arial) — ได้ไฟล์เวกเตอร์ต้นฉบับเมื่อไหร่ให้แทนทั้งชุด แล้วรัน `php scripts/make-favicon.php --source public/assets/images/brand/omc-mark.png`

## ไม่มีเครื่องมือสร้างภาพในโปรเจคนี้ (design.md 7ก.3ค)
- ส่วนเปิดใช้ mockup แดชบอร์ด HTML/CSS (มี caption "ข้อมูลสมมติ") · industry ใช้ไอคอนบนพื้นไล่สี · about ใช้แผงโลโก้ — ทุกบล็อกมีช่อง `image` ให้ CMS ใส่ภาพจริงทีหลัง · ไม่ตัดภาพจากภาพแบบ (ภาพในแบบเป็นภาพสต็อก/AI ที่ไม่ใช่ของ OMC)

## `server.php` ตั้ง SCRIPT_NAME เอง
- built-in server ให้ SCRIPT_NAME เป็น path ที่ขอ → `Path::base()` ผิดกับ URL หลายชั้น (`/_preview/home` ได้ base `/_preview`) · server.php ตั้ง `/index.php` ก่อน require (เครื่อง dev เท่านั้น)

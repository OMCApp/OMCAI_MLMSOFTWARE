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
- ~~CI ตรวจ `/admin/login` แทน `/`~~ — Phase 4 เผยแพร่หน้าแรกแล้ว CI ตรวจ `/` `/en/` `/zh/` = 200 (ดูหัวข้อ Phase 4)
- **ชุดทดสอบ**: `php tests/run.php` — เปิด built-in server ชี้ฐานข้อมูลจาก config (.env หรือ env vars) ใส่ข้อมูล `zz-test-*` แล้วลบทิ้งตอนจบ · ไม่ deploy ขึ้น server (exclude ใน workflow) · ปฏิเสธการรันเมื่อ APP_ENV=production

## Phase 5 — นำเข้าบทความเดิม
- **migration `030_import_old_posts.sql` สร้างด้วยสคริปต์** `php tools/migration/build-import.php` จาก `docs/migration/content.json` — ห้ามแก้ไฟล์ SQL ด้วยมือ · ผลลัพธ์ deterministic (ไม่มีเวลาในไฟล์) · **ถ้า 030 ถูก apply ที่ไหนแล้ว ห้าม generate ใหม่ด้วยเนื้อหาต่าง** — แก้ข้อมูลด้วย migration 031–039 หรือแก้ใน CMS
- ข้อความไทยทั้งหมดใน 030 เป็น hex (ตามแบบ 010/011) ข้อความจริงอยู่ในคอมเมนต์ · ไฟล์ ~1.7 MB, คำสั่งยาวสุด ~120 KB (ต้องการ `max_allowed_packet` ≥ 1 MB — ค่าเริ่ม XAMPP/โฮสต์ทั่วไปพอ)
- idempotent: media ตาม path · posts ตาม slug + วันที่ · SEO ของหมวดเติมเฉพาะช่องที่ยังว่าง (ไม่ทับที่แก้ใน CMS)
- การนำเข้าผ่าน SQL ไม่ผ่าน base Model → **ไม่มีแถว audit log** ของการนำเข้า (เหมือน seed 010/011)
- `title` (H1) = ชื่อบทความเดิมที่เว็บเดิมแสดงเป็น H1 · `seo_title` = ส่วน intent ของ `<title>` เดิม — สองค่านี้ต่างกันใน 15 เรื่องเพราะเว็บเดิมต่างกันอยู่แล้ว (ตารางใน `docs/migration/post-titles.md`)
- `author_name` เก็บตามเว็บเดิม (`tee` 2 เรื่อง · `Ongart Atipornpanich` 34 เรื่อง) — ใช้เป็น `author` ใน BlogPosting
- `tools/migration/url-mapping.php` เขียน `docs/migration/url-mapping.md` ใหม่ทุกครั้งที่รัน (ยิงจริงผ่าน `server.php`) — รันอีกครั้งหลัง Phase 4 เผยแพร่หน้า แถว "pending Phase 4" ต้องกลายเป็น 200 หมด

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

## Staging ไม่มีรหัสเข้าแล้ว (2026-11-04)
- **ผู้ใช้สั่ง:** "ไม่ต้องมี login ขึ้นหน้าเว็บไปเลย" — ขัดกับ brief ฉบับ 2 ข้อ 3 (staging ต้องมี Authentication) · คำสั่งล่าสุดชนะ
- CI ไม่เขียน `STAGING_PASS` ลง `.env` แล้ว → `Core\Staging` ข้าม Basic Auth (โค้ดยังอยู่ เปิดกลับได้โดยใส่บรรทัดนั้นคืน)
- **ยังกันไม่ให้ถูก index เหมือนเดิม:** `X-Robots-Tag: noindex` ทุก response + robots.txt `Disallow: /` + canonical ชี้ production · CI ยังตรวจ noindex ทุกครั้ง
- ความเสี่ยงที่เหลือ: ใครก็เปิดดูเนื้อหาร่างได้ถ้ารู้ URL · ซอร์สโค้ดยังปิดอยู่ (root `.htaccess`)

## Phase 4 — หน้าสาธารณะ 3 ภาษา + lead
- **ภาษา:** `config/locales.php` คือรายการเดียว (prefix · html lang/hreflang · og:locale · ป้ายตัวสลับ · font token · ฟอนต์ที่ preload) — `App\Services\LocaleService` อ่านที่นี่ · Router (`/{locale}/feed`) · SEO · sitemap · ตัวสลับภาษาใช้ร่วม · เพิ่มภาษา = เพิ่มรายการ + `lang/<code>.php` + เนื้อหา · `APP_LOCALES` ค่าเริ่มต้นมาจากไฟล์นี้
- **ภาษามาจาก URL เท่านั้น** (`/` ไทย · `/en/` · `/zh/`) — หน้าสาธารณะเรียก `Lang::useForRequest()` ไม่จำลง session · ไม่ดู Accept-Language/IP · ไม่ redirect ตามภาษา (test ตรวจ) · หลังบ้านใช้ลำดับเดิมของ `Core\Lang`
- **ตาราง** `pages` `posts` `categories` มี `locale` + `translation_group` (สัญญากับ Phase 3 — ห้ามเปลี่ยนชื่อ) · คำแปลคือแถวแยก path ของตัวเอง · parent ของคำแปล = คำแปลของ parent ไทย
- **hreflang** ออกเฉพาะแถวที่มีจริง เผยแพร่แล้ว index ได้ และ canonical เป็นตัวเอง (`PageService::hreflangSet` ชุดเดียวกันทั้ง `<head>` และ sitemap) · x-default = ไทย · canonical ไม่ข้ามภาษา
- **sitemap แยกภาษา:** `sitemap-pages.xml` `sitemap-posts.xml` `sitemap-categories.xml` (ไทย ประกาศเสมอ) + `sitemap-pages-en.xml` `sitemap-pages-zh.xml` (posts/categories ของภาษาอื่นเมื่อมีแถว)
- **ไม่มี URL แปลที่ว่าง:** `/en/blog/` `/en/category/*` `/en/feed/` = 404 noindex จนมีบทความภาษานั้น · ลิงก์ในเมนู/footer ของภาษาอื่นไปหน้าที่ไม่มีคำแปล → ลิงก์หน้าไทย (`SiteService::localizePath`)
- **ฟอนต์จีน:** ฟอนต์ self-host ไม่มี CJK → ฟอนต์ระบบ token `--font-zh` (DESIGN.md) ผ่าน `:lang(zh)` · ภาษาจีนไม่ preload ฟอนต์ · อังกฤษ preload ชุด latin
- **เนื้อหาหน้า** มาจาก `tools/content/<locale>/*.php` → `php tools/content/build-pages.php` → `021_publish_site_content.sql` (hex ตามแบบ 010/011 · deterministic · ตรวจความยาว title/description, H1 เดียว, คำต้องห้าม) · **ถ้า 021 ถูก apply ที่ไหนแล้ว ห้าม generate ใหม่ด้วยเนื้อหาต่าง** — แก้ใน CMS หรือ migration 022–029
- **บล็อก:** hero `variant: page` = ส่วนเปิดหน้าใน (ไม่มี mockup · breadcrumb อยู่ในตัวเพราะ page-head ไม่วาดเมื่อมี hero) · `{years}` ในทุกบล็อก = ปีที่ดำเนินธุรกิจ คำนวณจาก `config/site.php` brand.founded (2009-04-03) · stats `format: plain` = ไม่ใส่จุลภาค/ไม่วิ่ง (ปี 2552) · faq ใช้ `question`/`answer` (รับ q/a เดิมด้วย) ชุดเดียวกับ FAQPage schema · lead_form section id คงที่ `#lead-form`
- **lead:** `POST /lead/` → `LeadController` → `LeadService::submit()` · rate limit: ทุกการส่ง ≤ 10 ครั้ง/15 นาที/IP (`lead_attempts`, IPv6 ยุบ /64) + lead ที่บันทึก ≤ 5/ชม. · honeypot ได้หน้าขอบคุณแต่ไม่บันทึก · UTM first-touch ใน cookie `omc_utm` (HttpOnly, 30 วัน, เฉพาะ utm_* ที่กรองแล้ว) + UTM ของหน้าที่ส่ง → `leads.utm` `{first,current}` · `landing_page` · `referrer` (ตัด query) · `locale` · อีเมลภาษาไทยถึงทีมขาย (`config/site.php` lead_email หรือ settings `lead.notify_email`) ผ่าน `MailService` (`mail()` · ไม่ตั้ง `MAIL_ENABLED`/`MAIL_FROM` = log แทน)
- **CSRF ล้มเหลว** (`public/index.php`) กลับหน้าที่ส่งฟอร์ม (referer host เดียวกัน, 303) แทน path ของ POST — `/lead/` ไม่มีหน้า GET
- **แก้ของกลาง:** `field.php` ล้าง `$fieldValue` ท้ายไฟล์ (เดิมค่า old() ของช่องแรกค้างไปทุกช่อง) · error ผูก `aria-describedby` + `aria-invalid` · `lang/*.php` เคยมี key `site` ซ้ำสองก้อน (ก้อนหลังทับก้อนแรก — ป้าย header เป็น key ดิบ) รวมเป็นก้อนเดียวแล้ว
- **cache:** `public/.htaccess` CSS/JS/ฟอนต์ 1 ปี immutable (asset() ต่อ ?v=เวลาแก้ไฟล์) · รูป 30 วัน
- **ดูหน้าบนเครื่อง:** built-in server ต้องใช้ `-t public` (`php -S 127.0.0.1:8000 -t public server.php`) — ไม่ใส่ `-t` ไฟล์ CSS/JS เป็น 404 (tests ไม่กระทบเพราะตรวจแค่ HTML) · headless Chrome `--window-size` แคบกว่า ~500px ไม่ได้ ใช้ DevTools emulation (`Emulation.setDeviceMetricsOverride`) สำหรับภาพ 375px
- **CI** ตรวจ `/` `/en/` `/zh/` = 200 แล้ว (หน้าแรกเผยแพร่แล้ว · staging ไม่มีรหัส) + `/admin/login` = 200
- **Lighthouse mobile (2026-11-04, built-in PHP server ไม่มี gzip, noindex เพราะ dev):** th perf 86 · a11y 100 · BP 100 · SEO 69 (หักเฉพาะ is-crawlable = noindex ของ dev/staging) — LCP 3.5 s · en 88/100/100/69 LCP 3.3 s · zh 91/100/100/69 LCP 2.9 s · CLS ≤ 0.022 · TBT ≤ 10 ms — วัดซ้ำบน Apache (gzip) ที่ Phase 6

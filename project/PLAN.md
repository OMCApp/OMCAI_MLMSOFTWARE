# แผนงาน — OMC MLM Software (rebuild mlmsoftware.omc.co.th)
> อัปเดตล่าสุด: 2026-11-04 | ⬜ ยังไม่เริ่ม · 🔄 กำลังทำ · 🔨 สร้างเสร็จ รอตรวจ (โหมดเร็ว) · ✅ เสร็จแล้ว

อ่านคู่กับ `BRIEF.md` · `SCOPE.md` · `DESIGN.md` · `NOTES.md` · `QUESTIONS.md` · baseline ที่ `docs/seo/baseline-2026-11-03/`

---

## สถาปัตยกรรม (ตัดสินแล้ว — ทุก phase ทำตามนี้)

### สองฝั่งในแอปเดียว
- **Public site** — render ฝั่ง server ทั้งหมด เนื้อหาอยู่ใน DB (CMS) · layout ใหม่ `app/Views/layouts/site.php` (header + mega nav + footer) แยกจาก layout หลังบ้าน
- **CMS admin** — อยู่ใต้ `/admin/...` ทั้งหมด (ย้าย `/login` `/dashboard` ของโครงตั้งต้นไป `/admin/login` `/admin`) เพื่อไม่ให้ชน URL สาธารณะ
- ข้อความ UI (ปุ่ม, label, error) ผ่าน `__()` · **เนื้อหาเว็บ (หัวข้อ, ย่อหน้า, บล็อก) อยู่ใน DB ไม่ใช่ lang file** — เพราะ CMS ต้องแก้ได้
- Public รองรับภาษาไทยเป็นหลัก (เนื้อหา EN ยังไม่อยู่ในขอบเขต — ดู SCOPE)

### Routing สาธารณะ — ลำดับการจับ URL (ห้ามเปลี่ยนลำดับ)
1. ไฟล์จริงใน `public/` (asset, `wp-content/uploads/...`)
2. route ระบบ: `/_health` `/_migrate` `/admin/*` `/sitemap.xml` (+ sitemap ย่อย) `/robots.txt` `/feed/` `/lead` (POST)
3. **trailing slash** — path สาธารณะที่ไม่ลงท้าย `/` (และไม่ใช่ไฟล์/route ระบบ) → 301 ไปตัวที่มี `/` · query string คงไว้
4. **redirects table** (CMS) — ตรงตัว path → 301/302/410
5. **บทความ** `/{yyyy}/{mm}/{dd}/{slug}/` — slug ภาษาไทยเก็บแบบ decode แล้ว เทียบแบบ decode · วันที่ต้องตรงกับ `published_at` ไม่ตรง → 301 ไป URL ที่ถูก
6. **หมวดหมู่** `/category/{slug}/` + `/category/{slug}/page/{n}/`
7. **บล็อก** `/blog/` + `/blog/page/{n}/`
8. **หน้า** (pages.path ตรงตัว รวมหน้าลูก เช่น `/compensation-plans/binary/`)
9. ไม่เจอ → 404 (noindex) + บันทึกลง `not_found_log` (path, referrer, hits, last_seen) ให้แอดมินทำ redirect ได้จากหน้าเดียว

### SEO ที่ทุกหน้า public ต้องมี (partial กลาง `partials/seo-head.php`)
- `<title>` จาก `seo_title` ไม่มีก็ใช้ title + ` | OMC MLM Software` · ไม่เกิน ~60 ตัวอักษร (เตือนใน CMS ไม่บังคับ)
- `meta description` (เตือนถ้าว่าง/เกิน 160) · `canonical` เต็ม URL บน production host (`APP_URL`) ลงท้าย `/`
- `meta robots` จากหน้า (index/noindex) · **บน non-production `X-Robots-Tag: noindex` เสมอ (มีแล้วใน `Core\Staging`)** · production ต้องไม่มี noindex หลุด (CI ตรวจ)
- OG/Twitter: title, description, image, url, type
- H1 หนึ่งตัวต่อหน้า (template เป็นคนวาง ไม่ให้ editor ใส่ H1 ใน body)
- JSON-LD: `Organization` + `WebSite` ทุกหน้า · `BreadcrumbList` ทุกหน้าที่ไม่ใช่หน้าแรก · `BlogPosting` บทความ · `FAQPage` เมื่อหน้ามีบล็อก FAQ · `SoftwareApplication`/`Service` ที่หน้า MLM Software / landing · **schema ต้องตรงเนื้อหาจริง ห้ามใส่ rating ปลอม**
- breadcrumbs แสดงบนหน้า (ไม่ใช่หน้าแรก)
- รูป: `alt` บังคับใน CMS · `width/height` เสมอ · `loading="lazy"` ยกเว้นรูป LCP · WebP + `srcset`
- sitemap: index + pages / posts / categories · `lastmod` จาก `updated_at` · ไม่รวม noindex / draft / redirect
- robots.txt: production = Allow + Sitemap · non-production = `Disallow: /`
- RSS `/feed/` (เว็บเดิมมี)

### ตารางข้อมูล (migration ใหม่ เริ่ม 006)
`pages` (path unique, parent_id, title, template, blocks JSON, seo_title, meta_description, canonical_override, robots, og_image_id, status draft|published, published_at, sort, timestamps, deleted_at)
`posts` (slug, title, excerpt, content HTML, featured_image_id, category_id, author_name, status, published_at, seo_*, legacy_url, timestamps, deleted_at)
`categories` (slug, name, description, seo_*) · `media` (path, mime, width, height, alt, title, sizes JSON, bytes) · `menus` + `menu_items` (location header|footer, parent, label, url/page_id, sort)
`redirects` (from_path unique, to_path, code 301|302|410, note, hits, last_hit_at, enabled) · `not_found_log` · `faqs` (question, answer, group, sort, status) · `testimonials` + `case_studies` (status draft|published — **published ได้เฉพาะของจริง**)
`leads` (form_key, name, company, business_type, phone, email, line_id, has_system, need, member_estimate, message, source_url, utm JSON, ip, status new|contacted|qualified|won|lost, assigned_to, notes) · `settings` (key, value JSON) · `revisions` (entity, entity_id, data JSON, user_id)
ทุกตารางที่แก้ได้ผ่าน base Model → audit log อัตโนมัติ (มาตรฐานข้อ 10)

### บล็อกของหน้า (page builder แบบมีชนิด — ไม่ใช่ HTML อิสระ)
`hero` · `trust_bar` (ซ่อนเองถ้าไม่มีข้อมูลจริง) · `stats` (ซ่อนถ้าไม่ยืนยัน) · `feature_grid` · `split` (ภาพ+ข้อความ) · `plan_cards` · `industry_grid` · `dashboard_showcase` · `about_teaser` · `testimonials` (ซ่อนถ้าไม่มี published) · `faq` · `cta_band` · `rich_text` · `steps` · `comparison_table` · `lead_form` (form_key)
แต่ละบล็อกมี partial ใน `app/Views/partials/blocks/` และ form ใน CMS

### URL map — หน้าเดิม (คงไว้) และหน้าใหม่
| URL | หน้า | หมายเหตุ |
|---|---|---|
| `/` | Home | คง · template home ตามแบบอ้างอิง |
| `/mlm-software-omc/` | **MLM Software** (หน้าหลักของสินค้า) | คง URL เดิม · intent "MLM Software by OMC" |
| `/services/` | Solutions / บริการ | คง URL เดิม · hub ไปหน้า industry |
| `/ocpb-mlm/` · `/ocpb-mlm2/` | OCPB / ขอใบอนุญาต สคบ. | คงทั้งสอง URL · เนื้อหาเดิม + ปรับโครง · ตัวที่ 2 canonical ไปตัวแรกเฉพาะถ้าซ้ำจริง (ตัดสินหลังเห็นเนื้อหา) |
| `/contact/` | Contact | คง |
| `/blog/` | Blog / Knowledge Center | คง (+ `/blog/page/N/`) |
| `/portfolio/` | Case Studies | คง · แสดงเฉพาะของจริง ไม่มีก็สถานะว่าง+CTA |
| `/category/ข่าวขายตรง/` · `/category/ประกัน/` | หมวด | คง |
| `/นโยบายความเป็นส่วนตัวข/` · `/นโยบายการใช้คุกกี้-cookies-policy/` | Policy | คง |
| `/YYYY/MM/DD/slug/` (36 เรื่อง) | บทความ | **คงทุก URL ห้ามแตะ** (รวมประกัน 9 เรื่อง) |
| `/mlm-software/` | — | redirect เดิม 301 → `/2025/05/20/mlm-software-คำตอบการจัดการสต็อก/` **คงไว้ตามเดิม** |
| `/features/` | Features | ใหม่ |
| `/compensation-plans/` + `/binary/` `/unilevel/` `/matrix/` `/board/` `/stair-step/` `/hybrid/` | แผนรายได้ | ใหม่ (Board/Stair Step รอยืนยันว่ารองรับจริง — QUESTIONS) |
| `/solutions/{supplements,cosmetics,health-wellness,distributor,franchise,international,custom}/` | Industries | ใหม่ · ลูกของ `/services/` ทาง internal link |
| `/pricing/` | Pricing / ขอใบเสนอราคา | ใหม่ ไม่มีตัวเลขราคา |
| `/demo/` | ขอ Demo | ใหม่ |
| `/about/` | About OMC | ใหม่ |
| `/faq/` · `/support/` | FAQ · Support | ใหม่ |
| `/mlm-program/` โปรแกรม MLM · `/mlm-system/` ระบบ MLM · `/direct-sales-software/` โปรแกรมขายตรง · `/direct-sales-system/` ระบบขายตรง · `/mlm-development/` รับทำระบบ MLM · `/mlm-programming/` รับเขียนโปรแกรม MLM · `/mlm-software-thailand/` · `/mlm-migration/` ย้ายระบบเดิม · `/commission-system/` ระบบคำนวณคอมมิชชัน · `/member-system/` ระบบสมาชิกขายตรง | Landing | ใหม่ · `/mlm-system/` เคยมีอยู่ (ตอนนี้ 404) — ใช้ซ้ำได้ดี |

### Redirect map ของ URL ที่ไม่มีในเว็บใหม่ (ยืนยันกับ GSC ที่ Gate)
`/sample-page/` `/sample-page-2/` → `/` · `/home-startup/` → `/` · `/about-us-3/` → `/about/` · `/newsletter/` `/newsletter-2/` → `/blog/` · `/portfolio/<demo 9 ตัว>/` → `/portfolio/` · `/portfolio_category/*` `/portfolio_tag/*` → `/portfolio/` · `/author/*` → `/about/` · `/mlmsoftware/` → `/mlm-software-omc/` · `/contact-us/` `/contact-us-2/` → `/contact/` · `/homepage-business-solutions/` `/pages/` → `/` · `/comments/feed/` → `/feed/` · `/index.php/` → `/` (เดิมทำแล้ว) · `/wp-login.php` `/xmlrpc.php` `/wp-admin/` → 410

### รูปเดิม
รูปที่หน้า/บทความเดิมใช้ ย้ายไป `public/wp-content/uploads/<path เดิม>` **path เดิมทุกตัว** → URL รูปเดิมใช้ได้ต่อ (Google Images + backlink) · ย้ายเฉพาะที่ถูกอ้างถึง (ไม่ใช่ 1.1 GB ทั้งก้อน) · ต้นทาง: `C:\Users\Surface\Desktop\mlmss\wp-content\uploads` + ดาวน์โหลดจากเว็บจริงถ้าไม่มีในไฟล์สำรอง

---

## Phase 1 — Design system + public layout  🔨
### ครบลูป
- [-] ไม่มี resource (เป็นฐานของหน้า) — phase นี้ไม่มีข้อมูลให้ ดู/เพิ่ม/แก้/ลบ
### หน้า
- `/` (โครงหน้าแรกตามแบบ ใช้ข้อมูลจาก DB เมื่อ Phase 2 เสร็จ)
### ขั้นตอน
- [x] `DESIGN.md` จากแบบอ้างอิง — สีวัดด้วย `design-refs.php --palette/--pick` · ตาราง `## แบบของแต่ละหน้า` · รายการจุดที่ขัดกับกฎ · `design-sync.php` · `check-contrast.php`
- [x] โลโก้: header (horizontal), footer, favicon/app icon (`make-favicon.php`), compact · ยึดโลโก้ที่แนบ
- [x] `layouts/site.php` + header (mega nav ตามแบบ, CTA "ขอ Demo ฟรี", เบอร์โทร) + footer + mobile nav
- [x] partial บล็อกทั้งหมด (`partials/blocks/*`) ตามรายการบล็อกข้างบน · animation เบา (reveal, counter, hover) เคารพ `prefers-reduced-motion`
- [x] C4ก เทียบหน้าแรกกับแบบอ้างอิงทีละบล็อก (โหมดเร็วไม่เลื่อน) — ตารางใน DESIGN.md · ภาพ `project/design/built/home-reference.png` · ดูได้ที่ `/_preview/home` (+ `?all=1`)

## Phase 2 — Content engine + SEO infrastructure  🔨
### ครบลูป
- [x] resource ทุกตัวมี Model/Service + audit log (หน้าจัดการ ดู/เพิ่ม/แก้/ลบ อยู่ใน Phase 3)
### ขั้นตอน
- [x] migrations 006–011 ตามตารางข้างบน + seed redirect map, page shell (draft) ทุก URL ใน URL map, หมวด 2 ตัว, settings
- [x] public router ตามลำดับการจับ URL (`Router::fallback` → `App\Services\SiteResolver`) · trailing slash · redirects (exact/prefix · 301/302/410 · นับ hits) · บทความวันที่ผิด 301 · 404 log
- [x] `seo-head` partial · JSON-LD · breadcrumbs · sitemap · robots · RSS · ดูตัวอย่าง draft `?preview=1`
- [x] ย้าย `/login` `/dashboard` ไป `/admin/...`
- [x] tests: `php tests/run.php` — router (ทุกแถวใน URL map + redirect map), sitemap, canonical, noindex ตาม env (397 ผ่าน)
- [ ] เปลี่ยน `PublicController::LAYOUT` เป็น `site` เมื่อ Phase 1 ส่ง `layouts/site.php` (บรรทัดเดียว)

## Phase 3 — CMS admin  🔨
### ครบลูป (ทุกตัว ดู/เพิ่ม/แก้/ลบ + สิทธิ์ที่ middleware)
- [x] บทบาท admin · editor · sales — `ContentRoleMiddleware` (admin+editor) · `LeadRoleMiddleware` (admin+sales) · `admin` · users: รายการ/เพิ่ม/แก้บทบาท/ปิดบัญชี/ตั้งรหัสใหม่/ลบ/ออกจากทุกอุปกรณ์ · เปลี่ยนรหัสของตัวเอง · **บังคับเปลี่ยนรหัสครั้งแรก** (admin ที่ seed ด้วยรหัสตั้งต้น + ผู้ใช้ที่แอดมินสร้าง/ตั้งรหัสให้)
- [x] หน้า: รายการ (กรองสถานะ/ภาษา · ค้นหา · ธงขาดคำแปล) · block builder ทุกชนิดใน `BlockService::TYPES` (คีย์ตรงกับ partial 1:1 · แถวซ้ำได้ · รูปจากคลัง · ใช้ได้โดยไม่มี JS) · SEO panel · draft/publish + วันเผยแพร่ · ดูตัวอย่าง `?preview=1` · revisions (ดู/ย้อน) · soft delete · เปลี่ยน path ของหน้าที่เผยแพร่ → 301 อัตโนมัติ · คำแปล (สร้าง draft ใต้ /en/ /zh/ กลุ่มเดียวกัน)
- [x] บทความ: SEO panel · slug ไทย · ตัวแก้ HTML (ไม่มี dependency ใหม่) + แทรกรูปจากคลัง · ผ่าน HtmlSanitizer ตอนบันทึก (ไม่มี H1) · เปลี่ยน slug/วันที่ → 301 · revisions · คำแปล
- [x] หมวด: CRUD + SEO + คำแปล (ลบได้เฉพาะหมวดว่าง)
- [x] คลังสื่อ: อัปโหลด jpg/png/gif/webp (ไม่รับ SVG · ตรวจ mime) · alt บังคับ · WebP + 480/768/1200/1600 ด้วย GD ใน `public/uploads/YYYY/MM/` · width/height/sizes · ค้นหา · แก้ alt/title · ลบไม่ได้ถ้าถูกใช้ (แสดงว่าใช้ที่ไหน) · ลงทะเบียนรูปเดิมใน `/wp-content/uploads/` โดยไม่ย้ายไฟล์
- [x] เมนู header/footer แยกภาษา · ซ้อน 2 ชั้น · เลื่อนขึ้น/ลง · คัดลอกเมนูตั้งต้นจาก config · `SiteService` อ่านเมนู/ข้อมูลบริษัทจาก DB แล้วตกไป config (view-model รูปเดิม + `contact.emails`/`phones`)
- [x] redirects: CRUD · 301/302/410 · exact/prefix · เปิด/ปิด · hits · ห้ามวน · ห้าม chain (เสนอปลายทางสุดท้าย) · 404 log เรียงตาม hits + สร้าง redirect คลิกเดียว + ซ่อน
- [x] FAQ (กลุ่ม/ลำดับ/สถานะ/ภาษา) · testimonials · case studies — **เผยแพร่ต้องติ๊ก "ได้รับอนุญาตเผยแพร่จากลูกค้าแล้ว"** บันทึกใคร/เมื่อไหร่ · เว็บแสดงเฉพาะที่ยืนยันแล้ว (query)
- [x] leads (admin+sales): กรองสถานะ/ฟอร์ม/วันที่/ภาษา/landing page/referrer/UTM · รายละเอียดทุกช่อง · สถานะ · มอบหมาย · โน้ตเพิ่มอย่างเดียว · CSV UTF-8+BOM (กันสูตร Excel) · widget lead ใหม่ที่ dashboard
- [x] settings (admin): บริษัท (ก่อตั้ง 2009-04-03 · 3 อีเมล · เบอร์ · LINE · Facebook · ที่อยู่/แนะนำแยกภาษา) · ตัวเลขพร้อม "ยืนยันแล้ว" · โลโก้ลูกค้าที่ได้รับอนุญาต · อีเมลรับ lead · GA4/GTM (ส่งออกเฉพาะ production) · OG เริ่มต้น
- [x] dashboard `/admin`: หน้า/บทความ draft vs published · lead ใหม่ · 404 ใน 7 วัน · redirect hits · รูปขาด alt · ทางลัด
### ขั้นตอน
- [x] SEO panel ทุกหน้า/บทความ/หมวด: title/description + ตัวนับ + preview แบบ Google · canonical · robots · OG image (ต่อแถว = ต่อภาษา)
- [x] backup: สร้าง/ดาวน์โหลด/ลบที่ `/admin/backups` (audit) · **restore = CLI เท่านั้น** (DEPLOY.md + คู่มือ)
- [x] device cookie ลงลายเซ็น (HMAC `APP_KEY` + ความลับต่อผู้ใช้: password_hash · role · is_active · device_token_version) แทนข้อยกเว้นผูก IP · อายุ ≤ 90 วันตรวจที่ server · HttpOnly · SameSite=Strict · Secure ตาม `config('https')` · ข้อยกเว้นมีเพดาน 200 ครั้ง/15 นาที · **secret ใหม่ `APP_KEY`**
- [x] คู่มือ th + en 10 บทความ (`cms-*`) พร้อมรูป · ข้อความ UI ผ่าน `__()` th/en (zh อยู่ที่ `tools/i18n/zh-cms.php` รอรวมกับ `lang/zh.php` ของ Phase 4)
- [x] tests: `tests/cms.php` (ต่อจาก `tests/run.php`) — ทุก route ใต้ /admin จากตาราง route (ต้องล็อกอิน · เขียนต้องมี CSRF) · สิทธิ์ 3 บทบาททุกโมดูล · บังคับเปลี่ยนรหัส · ล็อก pair/IP/user cap · device cookie · session fixation/revoke · CRUD + audit ทุกโมดูล — **ชุดรวมกับ Phase 4: 4972+ ผ่าน**
- [x] ดูหน้าจริงด้วย headless Chrome: desktop + 375px + โหมดมืด (`project/design/built/cms-*.png`)
- [x] merge กับ Phase 4: ภาษาอ่านจาก config/locales.php (LocaleService) · unique key รวม locale (040) · ตัวเลขจาก settings ที่ยืนยันแล้วเท่านั้น (041) · บล็อก case_studies ใน /portfolio/ 3 ภาษา (042) · CSP nonce + strict-dynamic สำหรับรหัสติดตาม
- [ ] ตรวจรวมช่วง D (โหมดเร็ว: ข้อ 4/6/8 ของนิยามเสร็จ — ทำแล้วด้วยชุดทดสอบ แต่รอตรวจรวม)

## Phase 4 — หน้า public + copy + lead + 3 ภาษา  🔨
### ครบลูป
- [x] lead: เพิ่ม (ฟอร์มสาธารณะ) · ดู/แก้/ลบ/สถานะ อยู่ใน Phase 3 (หน้าจัดการ) — service มี update/changeStatus/delete แล้ว
### ขั้นตอน
- [x] เนื้อหาทุกหน้าใน URL map เผยแพร่แล้ว (migration `021_publish_site_content.sql` สร้างจาก `tools/content/<locale>/*.php` ด้วย `php tools/content/build-pages.php`) — ไทย 41 · อังกฤษ 39 · จีน 39 หน้า · Home ตามแบบ · MLM Software · Features · แผนรายได้ 7 · Services + 7 solutions · Pricing · Demo · About · Contact · FAQ · Support · Portfolio (สถานะว่างจริง) · OCPB · นโยบาย 2 · Landing 10 · thank-you (noindex)
- [x] copy ใหม่ทั้งหมด ไทย/อังกฤษ/จีน (ห้ามตัวเลข/โลโก้/รีวิวปลอม — builder + tests ตรวจ) · internal link: hub ↔ ลูก ↔ landing ↔ บทความเดิม · breadcrumb ตาม parent ทุกภาษา
- [x] SEO: seo_title คีย์เวิร์ดนำ + `| OMC` · description 120–160 (จีน 50–100) · ไม่ซ้ำในภาษาเดียวกัน · H1 เดียว · OG image ค่าเริ่มต้น `og-default.png` · Organization JSON-LD มีโทร/อีเมล/Facebook จริง
- [x] หลายภาษา: ไทยที่ root (URL เดิมไม่เปลี่ยน) · `/en/` · `/zh/` · `config/locales.php` ที่เดียว · ภาษาจาก URL เท่านั้น · hreflang reciprocal + x-default (ไทย) ทั้ง `<head>` และ sitemap แยกภาษา · `/en/blog/` `/en/feed/` 404 จนกว่าจะมีบทความแปล · ตัวสลับภาษา header/drawer/footer · `lang/zh.php`
- [x] lead: `POST /lead/` CSRF · honeypot · rate limit ต่อ IP (`lead_attempts`) · validate กับ `BlockService::LEAD_OPTIONS` · locale/landing_page/referrer/UTM (first-touch cookie 30 วัน) · อีเมลแจ้ง sale@omc.co.th ผ่าน `mail()` (ไม่ตั้ง MAIL_* = ข้าม + log) · thank-you ต่อภาษา (noindex) · tel:/LINE
- [x] template บทความ: breadcrumb · วันที่ไทย (พ.ศ.) · หมวด · ผู้เขียน · รูปเด่น LCP · บทความที่เกี่ยวข้อง · CTA ท้ายบทความ · การ์ดรายการมีรูปเด่น
- [x] tests: ส่วน "Phase 4" ใน `php tests/run.php` (4548 ผ่าน) · check.php 0 ต้องแก้ · ภาพจริง headless Chrome 1440/375 (th/en/zh) · Lighthouse mobile (ดู NOTES)
- [ ] ภาพ WebP + srcset ของรูปบทความเดิม (PNG 1.5 MB) — Phase 6 performance
- [ ] native speaker ตรวจ EN/ZH ก่อนขึ้น production (QUESTIONS)

## Phase 5 — Content migration  🔨
- [x] ดึงบทความ 36 เรื่องจาก HTML เว็บจริง (`docs/migration/content.json`) → `posts` ด้วย migration `030_import_old_posts.sql` (สร้างจาก `tools/migration/build-import.php` · idempotent) · ข่าวขายตรง 27 + ประกัน 9 · **URL เดิมตรงทุกตัว** (ทดสอบด้วย URL เดิมแบบ encode ตัวเล็กของ WordPress)
- [-] หน้าเดิม (policy 2 หน้า, /ocpb-mlm/ ฯลฯ) — เป็นงานของ Phase 4 (เจ้าของเนื้อหาหน้า) · `content.json` มีเนื้อหาที่ทำความสะอาดแล้วครบ จึงไม่ต้องทำ `policy-pages.json`
- [x] รูปที่ถูกอ้างถึง → `public/wp-content/uploads/` path เดิม (คัดลอกตอนดึงเนื้อหา) · แถว `media` 149 แถว · เติม alt ที่หาย 75 รูป (จาก alt ของผู้เขียนในบทความอื่น → `alt_suggested` → ชื่อบทความ) · width/height ครบ · `loading="lazy"` ในเนื้อหา
- [x] title: `<title>` = ส่วน intent ของ title เดิม (ตัด keyword list ของ WordPress) · H1 = ชื่อบทความเดิม (แก้ 1 เรื่องที่ H1 ถูกตัดท้าย) · meta description เขียนใหม่ 4 เรื่อง (ยาว/สั้น/เสีย) · H1 ตัวเดียว · ตาราง `docs/migration/post-titles.md` · หมวด 2 ตัวมี description/meta จริง
- [x] redirect map — นำเข้าแล้วใน `010_seed_redirects.sql` (Phase 2) · ตรวจซ้ำผ่าน url-mapping
- [x] รายงาน mapping เก่า→ใหม่ ทุก URL ใน baseline: `docs/migration/url-mapping.md` (`tools/migration/url-mapping.php` ยิงจริงผ่านแอป) — 95 URL: 200 = 44 · 301→200 = 4 · 410 = 2 · รอ Phase 4 เผยแพร่หน้า = 45 · **404 จริง = 0**
- [x] tests: ส่วน "Phase 5" ใน `php tests/run.php` (ทุกบทความ: 200 · H1 เดียว = title · canonical · title/description · BlogPosting datePublished · รูป alt/ขนาด/ไฟล์มีจริง · หมวด · /blog/ แบ่งหน้า · sitemap-posts · feed) — 1401 ผ่าน

## Phase 6 — QA + launch readiness  ⬜
- [ ] crawl staging ด้วย `tools/seo-audit/crawl.php` (Basic Auth) เทียบ baseline 95 URL — ทุก URL เดิมได้ 200 หรือ 301 ไปที่ถูก · ไม่มี 404 · ไม่มี redirect loop/chain
- [ ] SEO regression: title/description/H1/canonical/schema ทุกหน้า · production build ไม่มี noindex
- [ ] performance: Lighthouse mobile ทุก template · ขนาดภาพ · JS/CSS
- [ ] security: `security.md` checklist · ฟอร์ม · อัปโหลด · สิทธิ์ · headers
- [ ] `QA-REPORT.md` · SEO Migration Gate checklist · rollback plan · post-launch checklist (`project/LAUNCH.md`)
- [ ] **ไม่สลับ production ใน phase นี้** — ต้องผ่าน Gate + ผู้ใช้ตั้ง document root/โดเมน

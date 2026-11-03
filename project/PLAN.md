# แผนงาน — OMC MLM Software (rebuild mlmsoftware.omc.co.th)
> อัปเดตล่าสุด: 2026-11-03 | ⬜ ยังไม่เริ่ม · 🔄 กำลังทำ · 🔨 สร้างเสร็จ รอตรวจ (โหมดเร็ว) · ✅ เสร็จแล้ว

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
| `/YYYY/MM/DD/slug/` (37 เรื่อง) | บทความ | **คงทุก URL ห้ามแตะ** (รวมประกัน 11 เรื่อง) |
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

## Phase 1 — Design system + public layout  🔄
### ครบลูป
- [ ] ไม่มี resource (เป็นฐานของหน้า)
### หน้า
- `/` (โครงหน้าแรกตามแบบ ใช้ข้อมูลจาก DB เมื่อ Phase 2 เสร็จ)
### ขั้นตอน
- [ ] `DESIGN.md` จากแบบอ้างอิง — สีวัดด้วย `design-refs.php --palette/--pick` · ตาราง `## แบบของแต่ละหน้า` · รายการจุดที่ขัดกับกฎ · `design-sync.php` · `check-contrast.php`
- [ ] โลโก้: header (horizontal), footer, favicon/app icon (`make-favicon.php`), compact · ยึดโลโก้ที่แนบ
- [ ] `layouts/site.php` + header (mega nav ตามแบบ, CTA "ขอ Demo ฟรี", เบอร์โทร) + footer + mobile nav
- [ ] partial บล็อกทั้งหมด (`partials/blocks/*`) ตามรายการบล็อกข้างบน · animation เบา (reveal, counter, hover) เคารพ `prefers-reduced-motion`
- [ ] C4ก เทียบหน้าแรกกับแบบอ้างอิงทีละบล็อก (โหมดเร็วไม่เลื่อน)

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

## Phase 3 — CMS admin  ⬜
### ครบลูป (ทุกตัว ดู/เพิ่ม/แก้/ลบ + สิทธิ์)
- [ ] หน้า (block builder, draft/preview/publish, revisions) · บทความ · หมวด · media library (อัปโหลด → WebP + ขนาดย่อย, alt บังคับ) · เมนู · redirects (+ จาก 404 log) · FAQ · testimonials · case studies · leads (สถานะ, มอบหมาย, notes, export CSV) · settings (ติดต่อ, social, ตัวเลขที่ยืนยันแล้ว, tracking code) · users/roles (admin, editor, sales)
- [ ] SEO panel ทุกหน้า/บทความ: title/description พร้อมตัวนับ + preview แบบ Google · slug · canonical · robots · OG image
- [ ] backup/restore (ใช้ `scripts/backup.php`) · audit log viewer (มีแล้ว)
- [ ] device cookie ลงลายเซ็นแทนข้อยกเว้นผูก IP (NOTES)

## Phase 4 — หน้า public + copy + lead  ⬜
- [ ] Home ตามแบบ · MLM Software · Features · Compensation Plans + 6 แผน · Solutions + 7 industry · Pricing · Demo · About · Contact · FAQ · Support · Blog/หมวด/บทความ template · Landing 10 หน้า
- [ ] copy ภาษาไทยใหม่ทั้งหมด (tone ตาม BRIEF · ห้ามตัวเลข/โลโก้/รีวิวปลอม) · internal linking ตาม topic cluster
- [ ] lead form: ขอ Demo / ใบเสนอราคา / ปรึกษา / ติดต่อ / ย้ายระบบ · CSRF · honeypot + rate limit · บันทึก lead + แจ้งอีเมล (ถ้าตั้ง SMTP) · หน้า thank-you (noindex) · ปุ่ม LINE/โทร

## Phase 5 — Content migration  ⬜
- [ ] ดึงบทความ 37 เรื่อง + หน้าเดิมจาก HTML เว็บจริง (title, meta description, H1, เนื้อหา, วันที่, หมวด, รูปเด่น, alt) → posts/pages · **URL เดิมตรงทุกตัว**
- [ ] รูปที่ถูกอ้างถึง → `public/wp-content/uploads/` path เดิม · เติม alt ที่หาย (จากบริบท) · ใส่ width/height
- [ ] แก้ title ที่ยัด keyword (ตัด suffix ยาวของ WordPress) โดย**รักษาส่วนต้นที่เป็น intent** · เติม meta description ที่หาย · H1 ตัวเดียว
- [ ] นำเข้า redirect map
- [ ] รายงาน mapping เก่า→ใหม่ ทุก URL ใน baseline

## Phase 6 — QA + launch readiness  ⬜
- [ ] crawl staging ด้วย `tools/seo-audit/crawl.php` (Basic Auth) เทียบ baseline 95 URL — ทุก URL เดิมได้ 200 หรือ 301 ไปที่ถูก · ไม่มี 404 · ไม่มี redirect loop/chain
- [ ] SEO regression: title/description/H1/canonical/schema ทุกหน้า · production build ไม่มี noindex
- [ ] performance: Lighthouse mobile ทุก template · ขนาดภาพ · JS/CSS
- [ ] security: `security.md` checklist · ฟอร์ม · อัปโหลด · สิทธิ์ · headers
- [ ] `QA-REPORT.md` · SEO Migration Gate checklist · rollback plan · post-launch checklist (`project/LAUNCH.md`)
- [ ] **ไม่สลับ production ใน phase นี้** — ต้องผ่าน Gate + ผู้ใช้ตั้ง document root/โดเมน

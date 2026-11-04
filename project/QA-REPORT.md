# รายงาน Final QA — Phase 6 (2026-11-04)

> ฐาน: commit `539776e` → งานรอบนี้ถึง `a1c030d` (main) · staging: http://203.146.127.221/~omcai/mlmsoftware/
> **ยังไม่ได้ deploy production และไม่ได้แตะ DNS** · ภาพหน้าจอทั้งหมดอยู่ที่ `docs/qa/2026-11-04/`

## สรุป: **NOT READY** สำหรับ production — ฝั่งโค้ดและ SEO ผ่านเกณฑ์แล้ว เหลือเงื่อนไขของ production

| | สถานะ |
|---|---|
| SEO regression 95 URL | ✅ 0 ปัญหาที่กั้น (ทั้งในเครื่องและบน staging) |
| Sitemap 153 URL (TH 77 · EN 38 · ZH 38) + hreflang 27 คู่ | ✅ ครบ |
| ชุดทดสอบอัตโนมัติ | ✅ 4,987 ผ่าน · 0 ไม่ผ่าน · `check.php` 0 ต้องแก้ |
| Performance ≥ 90 | ✅ desktop ทุกหน้า (97–100) · ⚠️ มือถือบน staging 88–96 |
| LCP ≤ 2.5 วินาที (มือถือ) | ⚠️ **ลดอาการแล้ว ยังไม่ถึงเป้า** — staging 1.9–3.4 วินาที (ดูข้อ 4) |
| หน้าแรกตรง Mockup | ✅ (ต่างจากแบบ 2 จุด ดูข้อ 2) |

**เหตุผลที่ยัง NOT READY**
1. **LCP มือถือ** เกินเป้าใน 5 จาก 6 หน้าที่วัดบน staging — staging เป็น HTTP/1.1 ไม่มี HTTP/2 จึงโหลด CSS/ฟอนต์/รูปทีละไม่กี่ไฟล์ · production ที่เป็น HTTPS น่าจะได้ HTTP/2 แต่**ต้องวัดซ้ำหลังขึ้นจริงก่อนจะบอกว่าถึงเป้า**
2. **เงื่อนไขของ production ยังไม่ได้ทำ** — document root ที่ `public/` · ใบรับรอง SSL ที่ครอบ `www` · `.env` production (APP_KEY ใหม่) — รายการเต็มใน `LAUNCH.md`
3. **อีเมลแจ้ง lead ยังปิดอยู่** (`MAIL_ENABLED`/`MAIL_FROM` ไม่ได้ตั้ง) — lead ถูกบันทึกใน CMS ครบ แต่ฝ่ายขายจะไม่ได้อีเมล
4. **เนื้อหา EN/ZH ยังไม่มีเจ้าของภาษาตรวจ** — แสดงผลถูกต้องทุกหน้า (ตรวจแล้ว) แต่ศัพท์ธุรกิจควรมีคนตรวจก่อนเปิดจริง
5. **ยังไม่ได้รับอนุมัติ deploy production** จากเจ้าของ

---

## 1. ภาพหน้าจอหน้าสำคัญ 3 ภาษา (staging)

`docs/qa/2026-11-04/` — desktop 1536×1024 และมือถือ 390×844 (DPR 2) · ทุกหน้า HTTP 200 · ไม่มีการล้นแนวนอนเลยแม้แต่หน้าเดียว

| หน้า | TH | EN | ZH |
|---|---|---|---|
| หน้าแรก | `th-home-desktop` · `th-home-mobile` | `en-home-…` | `zh-home-…` |
| MLM Software by OMC | `th-software-…` | `en-software-…` | `zh-software-…` |
| ติดต่อ | `th-contact-…` | `en-contact-…` | `zh-contact-…` |
| บทความ | `th-blog-desktop` · `th-article-mobile` | — (บทความมีภาษาไทยเท่านั้น) | — |

## 2. ตารางเทียบ Mockup (`project/design/refs/home-mockup-3.png` ↔ `compare-mockup.png`)

| จุด | Mockup | หน้าจริง | ผล |
|---|---|---|---|
| โลโก้ OMC สีส้ม · เมนู · TH/EN/中文 · ปุ่ม "ทดลองใช้ Demo" | ✔ | ✔ ตำแหน่งเดียวกัน | ตรง |
| พื้นหลังขาวไล่ครีม + แสงส้มด้านหลังบุคคล | ✔ | ✔ | ตรง |
| ป้าย "ระบบบริหารธุรกิจเครือข่าย (MLM)" | ✔ | ✔ | ตรง |
| พาดหัว "MLM Software / ที่ออกแบบตามแผนธุรกิจ / ของคุณ" บรรทัด 2–3 สีส้ม | ✔ | ✔ ตัดบรรทัดตรงกันที่ 1280–1920px | ตรง |
| ย่อหน้านำ + จุดขาย 3 ข้อ (เครื่องหมายถูกสีส้ม) | ✔ | ✔ ข้อความเดียวกันทุกคำ | ตรง |
| ปุ่ม "ทดลอง Demo ฟรี" ส้ม · "ปรึกษาผู้เชี่ยวชาญ" ขาว | ✔ | ✔ | ตรง |
| ผู้บริหารชายชาวตะวันตก 45–55 ผมเทา สูทกรมท่า กอดอก อยู่ขวา | ✔ | ✔ (ภาพ stock ตัดพื้นหลัง) | **ต่าง 1 จุด: ไม่มีเนกไท** — ภาพฟรีที่ใช้เชิงพาณิชย์ได้และตรงทุกข้อไม่มี |
| แล็ปท็อป + มือถือแดชบอร์ดซ้อนหน้าบุคคล | ✔ | ✔ ตัวเลขเดียวกัน (฿3,256,000 · 128,560 · ฿24,860) | ตรง |
| ข้อความ "ภาพบุคคลเป็นภาพประกอบ · ตัวเลขเป็นข้อมูลสมมติ" | ✔ | ✔ 3 ภาษา | ตรง |
| การ์ดสถิติ 2552 / 17 ปี ลอยทับขอบล่าง | ✔ | ✔ | ตรง (ยืนยัน: ก่อตั้ง 3 เม.ย. 2552 → 17 ปี ณ 2569) |
| ใบหน้าบุคคล | คนใน Mockup | คนละคน (Unsplash F_CnS-8NlZQ) | **ต่างโดยจงใจ** — ภาพใน Mockup ไม่มีสิทธิใช้งาน |
| Animation | — | fade-up ปุ่ม/รายการ/อุปกรณ์ · แล็ปท็อปลอยช้า · hover การ์ด · ปิดเมื่อผู้ใช้ตั้งลดการเคลื่อนไหว | h1 / ย่อหน้านำ / ภาพบุคคลไม่ fade (เป็น LCP) |

แท็บเล็ต (820px): ข้อความบน ภาพบุคคล + อุปกรณ์ด้านล่าง · มือถือ: พาดหัวแบ่งบรรทัดสมดุล ไม่มีคำเดี่ยวค้างบรรทัด · แถบ "โทรหาเรา / ทดลองใช้ Demo" ติดล่างจอ

## 3. SEO Regression — 95 URL (`docs/seo/regression.md` · สร้างซ้ำได้ด้วย `php tools/seo-audit/regression.php`)

**"94 รายการ" ในรายงานก่อนหน้า:** ไม่พบตัวเลข 94 ในเอกสารของโปรเจค — ตาราง `docs/migration/url-mapping.md` มี 95 แถว และสรุปรวมได้ 95 ทั้ง baseline (84 × 200 + 4 × 301 + 7 × 4xx) และผลใหม่ (57 + 36 + 2) · ไม่มี URL ใดหายจากตาราง · รายงานใหม่ (`docs/seo/regression.md`) นับทีละแถวครบ 95 พร้อม Old → New → HTTP → Canonical → Indexability · ถ้าตัวเลข 94 มาจากรายงานอื่น ส่งมาได้ จะไล่ให้ว่าแถวไหน

| ผลบน staging (ยิงจริง) | URL |
|---|---|
| 200 · index · canonical = ตัวเอง | 54 |
| 200 · canonical ไปหน้าหลัก (`/ocpb-mlm2/` → `/ocpb-mlm/` · `/?s=mlm` → `/`) | 2 |
| 200 · RSS feed | 1 |
| 301 (ครั้งเดียว) → 200 | 36 |
| 410 (`/wp-login.php`, `/xmlrpc.php`) | 2 |
| **รวม** | **95** · 404 = 0 · redirect chain/loop = 0 |

- **keyword:** ทุก URL เดิมที่ยังอยู่ มีคำหลักเดิม (H1 เดิม / ชื่อใน title เดิม) ในหน้าใหม่ · หน้าแรกคง "MLM Software · โปรแกรมขายตรง · ระบบ MLM · ระบบขายตรง" — รอบนี้แก้ 2 จุด: "ระบบขายตรง" กลับเข้าเนื้อหาหน้าแรก · H1 นโยบายความเป็นส่วนตัวกลับเป็นถ้อยคำเดิม (migration 047)
- **title / description / H1 / canonical / JSON-LD:** ทุก URL ใน sitemap 3 ภาษา (153) มี 200 · H1 เดียว · title · description · canonical ตัวเอง · JSON-LD อ่านได้ · ไม่มี noindex
- **hreflang:** th / en / zh-Hans / x-default ไปกลับครบ 27 คู่ · บทความไทยไม่มี hreflang (ยังไม่มีฉบับแปล — ถูกต้อง)
- **robots / noindex:** production = `Allow: /` + `Disallow: /admin/` + Sitemap · ไม่มี noindex (ทดสอบใน `tests/run.php`) · staging = noindex + `Disallow: /` (ตรวจแล้ว)
- **Structured data:** Organization · WebSite · SoftwareApplication · FAQPage · BreadcrumbList · BlogPosting (บทความ)
- **www → non-www / http → https:** เพิ่มใน `public/.htaccess` — 301 ครั้งเดียว คง path ภาษาไทยแบบ encode เดิม + query · ทดสอบบน Apache แล้ว · ทำงานเฉพาะชื่อโดเมน production

## 4. Lighthouse (Lab — Lighthouse 11, จำลองมือถือ Moto G Power + 4G ช้า)

**บน staging จริง (HTTP/1.1, gzip):**

| หน้า | มือถือ | Desktop | LCP มือถือ | CLS | TBT มือถือ |
|---|---|---|---|---|---|
| หน้าแรก TH | 88 | 100 | 3.4 s | 0 | 0 ms |
| Services | 96 | 99 | 2.6 s | 0 | 0 ms |
| บทความ (รวม) | 93 | 88 | 3.3 s | 0 | 0 ms |
| บทความ | 92 | 97 | 2.8 s | 0 | 170 ms |
| หน้าแรก EN | 90 | 100 | 2.8 s | 0 | 280 ms |
| หน้าแรก ZH | 93 | 100 | 1.9 s | 0 | 300 ms |

Accessibility 98–100 · SEO 91–92 (หักเพราะ staging noindex — production 100) · Best Practices 81 (หักข้อเดียว: staging ไม่ใช่ HTTPS)

**ก่อน → หลังแก้ (ในเครื่อง, gzip แบบเดียวกับ staging):** บทความรวม มือถือ 63 → 93 (8.6 MB → 0.55 MB) · บทความ 62 → 95 · หน้าแรก 81 → 95 (มัธยฐาน 3 รอบ)

**แก้อะไรไป:** รูปบทความเดิม 149 รูป → WebP + srcset (81 MB → 7.9 MB, ต้นฉบับยังเปิดได้) · รูปในเนื้อหาบทความได้ srcset ตอนแสดง · รูปเด่น/รูปแรก = ไม่ lazy + fetchpriority · ภาพ hero 182 KB → 43 KB (768px) · เลิก fade ย่อหน้านำ (คือ LCP บนมือถือ)
ตรวจทุกหน้าใน sitemap: 872 รูป — มี alt ครบ · มี width/height ครบ · ไม่มี PNG/JPEG เหลือ

**INP:** วัดใน lab ไม่ได้ (ต้องมีผู้ใช้จริง) — ใช้ TBT แทน 0–300 ms · **Field data (CrUX): ยังไม่มี** เพราะเว็บใหม่ยังไม่เปิด — ดูใน Search Console → Core Web Vitals 28 วันหลังเปิด

## 5. CMS Functional Test

ทดสอบด้วยชุดทดสอบอัตโนมัติ (`tests/cms.php` ผ่าน `tests/run.php`) กับโค้ดชุดเดียวกับ staging บนฐานข้อมูลในเครื่อง — ใช้บัญชีทดสอบ `zz-test-*` ที่สร้างแล้วลบทิ้งเอง **ไม่ได้ใช้บัญชีจริงหรือข้อมูลจริง**

| รายการ | ผล |
|---|---|
| Login · ล็อกเมื่อเดาผิด · cookie อุปกรณ์ · session | ✅ |
| เปลี่ยนรหัสผ่านครั้งแรก (บังคับ) | ✅ |
| เพิ่ม / แก้ / เผยแพร่ / ลบ บทความ · sanitize HTML · 301 อัตโนมัติเมื่อเปลี่ยน slug | ✅ |
| หน้า TH / EN / ZH · translation group · path ต้องตรงภาษา | ✅ |
| Preview draft (`?preview=1` เฉพาะแอดมิน/editor · noindex) | ✅ |
| Revision ทุกครั้งที่แก้ · Restore (rollback) ได้ title เดิมคืน | ✅ |
| คลังสื่อ: อัปโหลด → WebP + ขนาดย่อย · alt บังคับ · ลบได้เมื่อไม่ถูกใช้ | ✅ |
| **เปลี่ยนภาพ Hero + แก้พาดหัวจาก /admin แล้วหน้าเว็บเปลี่ยนจริง** (เพิ่มรอบนี้) | ✅ |
| สิทธิ์ Admin / Editor / Sales แยกตามโมดูล (sales แก้หน้าไม่ได้ = 403) | ✅ |
| Lead: ฟอร์ม (CSRF · honeypot · validation · rate limit) → บันทึก + UTM/landing/referrer · กรอง · มอบหมาย · โน้ต | ✅ |
| Export CSV: UTF-8 BOM · เฉพาะแถวที่กรอง · กันสูตร Excel (`=` …) | ✅ |
| Audit Log: สร้าง/แก้/ลบ/login ล้มเหลว/backup ถูกบันทึก · ไม่มีรหัสผ่านใน log | ✅ |
| Backup → Restore (ฐานข้อมูลชั่วคราว) | ✅ 21 ตาราง 5,087 แถว ตรงทุกตาราง · ภาษาไทยไม่เพี้ยน |
| **Login จริงบน staging ด้วยบัญชีทดสอบ** | ⬜ **ไม่ได้ทำ** — ไม่มีบัญชีทดสอบบน staging ที่ได้รับอนุญาต · หน้า `/admin/login` บน staging เปิดได้ (200) |

## 6. Security Test

| รายการ | ผล |
|---|---|
| ไฟล์ลับจากเว็บ: `.env` `.git` `app/` `core/` `config/` `database/` `tools/` `docs/` `project/` `storage/` `composer.json` | ✅ 403/404 ทั้งหมด (ยิงจริงบน staging) |
| Header: CSP `script-src 'self'` · nosniff · X-Frame SAMEORIGIN · Referrer-Policy · Permissions-Policy · HSTS (production) | ✅ |
| Cookie session: HttpOnly · SameSite · Secure บน HTTPS | ✅ |
| ทุก route ใน /admin ต้อง login · CSRF ทุกคำสั่งเขียน (`hash_equals`) | ✅ (test) |
| SQL: prepared statement ทุก query · HTML บทความผ่าน allowlist · output ผ่าน `e()` | ✅ (`check.php`) |
| XSS ผ่าน query (`?s=<script>`) | ✅ ไม่สะท้อนกลับ |
| WordPress endpoint เดิม (`/wp-login.php`, `/xmlrpc.php`) | ✅ 410 |
| Analytics ไม่ส่งข้อมูลส่วนบุคคล | ✅ ส่งแค่ชื่อเหตุการณ์ · ลิงก์ปลายทาง · path · ชนิดฟอร์ม |
| ⚠️ staging เปิดสาธารณะ (ไม่มีรหัส) | ผู้ใช้ตัดสินแล้ว 2026-11-04 · ยังกัน index ด้วย noindex + robots |
| ⚠️ staging เป็น HTTP + FTP ไม่เข้ารหัส | ผู้ใช้ตัดสินแล้ว (NOTES.md) · production ต้อง HTTPS |

## 7. Production Deployment Checklist → [`LAUNCH.md`](LAUNCH.md)

Backup + restore · `.env` / APP_KEY · HTTPS/SSL · DNS/www · migration · สิทธิ์ไฟล์ · cache/CDN · security headers · log/monitoring · Search Console/sitemap · rollback

## 8. ที่ยังต้องใช้ข้อมูลจริงจาก OMC (ไม่กั้นการเปิด แต่ทำให้น่าเชื่อถือขึ้น)

- ภาพจริง: ผู้บริหาร/ทีม/ออฟฟิศ · ภาพหน้าจอระบบจริง (แทน stock + mockup) — เปลี่ยนเองได้ใน CMS → คลังสื่อ (ภาพ hero ต้องเป็นภาพตัดพื้นหลัง PNG/WebP โปร่งใส)
- ตัวเลขที่พิสูจน์ได้ (จำนวนลูกค้า/ระบบที่ดูแล) · รีวิวลูกค้าที่**ได้รับอนุญาต**เป็นลายลักษณ์อักษร · กรณีศึกษา/โลโก้ลูกค้าที่อนุญาต
- ยืนยันฟีเจอร์ที่ระบบรองรับจริง: แผน Board / Stair Step · E-Commerce/สต็อก · Wallet · API · AI
- ยืนยันข้อมูลติดต่อ (099-109-5544 · sale@omc.co.th · LINE omc54 · ที่อยู่) · เลขทะเบียนนิติบุคคล · หน้าติดต่อแสดง `projectyak@hotmail.com` ด้วย — ที่อยู่ Hotmail บนเว็บบริษัทลดความน่าเชื่อถือ ควรยืนยันว่าจะคงไว้ไหม (แก้ได้ใน CMS → ตั้งค่า)
- ตัวเลขกฎหมาย สคบ. ที่ตรวจแล้ว (ตอนนี้เขียนแบบทั่วไป) · OMC ยังรับยื่นเอกสาร สคบ. ไหม
- อีเมลผู้ส่งสำหรับแจ้ง lead (`MAIL_FROM` โดเมน omc.co.th) · GA4 / GTM ID · สิทธิ์ Search Console
- เจ้าของภาษา EN / ZH ตรวจเนื้อหา · ไฟล์โลโก้ต้นฉบับ (SVG/AI)
- รายการเต็ม: `QUESTIONS.md`

## 9. Commit และสถานะ Staging

| Commit | งาน |
|---|---|
| `5d60d05` | ภาพผู้บริหาร + จัด hero ตาม Mockup · animation · /zh/ ข้อความ UI ถูกภาษา |
| `1874a7a` | SEO regression 95 + 153 URL · คำหลักเดิม (047) · www/http → https |
| `0603eda` | รูปบทความเดิม → WebP + srcset (048) · LCP |
| `7a35e7f` | test เปลี่ยนภาพ/พาดหัว hero จาก CMS |
| `a1c030d` | merge ขึ้น main — พาดหัวตัดบรรทัดตรง Mockup · LAUNCH.md |

Staging: deploy อัตโนมัติจาก main · `/_health` 200 · migration ถึง `048_legacy_webp_sizes.sql` · noindex ✔ · **production: ไม่ได้แตะ**

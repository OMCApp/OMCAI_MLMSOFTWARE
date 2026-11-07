# GEO 1 — Technical AI Audit (2026-11-07)

ตรวจว่า search engine และระบบ AI **เข้าถึงและอ่านเว็บได้จริง** โดยไม่เปิด admin หรือข้อมูลส่วนบุคคล

## 1.1 ผลตรวจ crawler (ยิงจริงด้วย User-Agent ของแต่ละบอท)

| บอท | ใช้ทำอะไร | Production ปัจจุบัน (WordPress) | Staging (ระบบใหม่) |
|---|---|---|---|
| Googlebot | Google Search / AI Overviews | 200 | 200 + noindex (ตั้งใจ) |
| bingbot | Bing / Copilot / ChatGPT Search (ใช้ index ของ Bing บางส่วน) | 200 | 200 + noindex |
| OAI-SearchBot | ChatGPT Search | 200 | 200 + noindex |
| ChatGPT-User | ChatGPT เปิดลิงก์ตามคำสั่งผู้ใช้ | 200 | 200 + noindex |
| GPTBot | เก็บข้อมูลฝึกโมเดล OpenAI | 200 | 200 + noindex |
| ClaudeBot | Anthropic | 200 | 200 + noindex |
| PerplexityBot | Perplexity | 200 | 200 + noindex |
| Applebot | Siri / Apple Intelligence | 200 | — |
| CCBot | Common Crawl | 200 | — |

- ไม่มี WAF / CDN / firewall ที่บล็อกบอท AI ทั้งสอง server
- Staging ส่ง `X-Robots-Tag: noindex, nofollow, noarchive` ทุกหน้า — **ถูกต้อง** ห้าม index staging

## 1.2 robots.txt

| | Production ปัจจุบัน | ระบบใหม่ |
|---|---|---|
| Allow | ทั้งหมด | ทั้งหมด |
| Disallow | `/wp-admin/` | `/admin/` (CMS) |
| Sitemap | `/wp-sitemap.xml` | `/sitemap.xml` |

- ไม่แยกนโยบายบอท AI — **ทุกบอทเข้าได้เท่ากัน** (ค่าเดิมของเว็บ ไม่เปลี่ยนโดยไม่อนุมัติ)
- ‼️ เรื่องที่ OMC ต้องตัดสิน: จะอนุญาตบอท **ฝึกโมเดล** (GPTBot, ClaudeBot, Google-Extended, CCBot) หรือไม่ — บล็อกได้โดย **ไม่กระทบ** การถูกอ้างอิงใน ChatGPT Search (OAI-SearchBot), Google Search หรือ Bing · ค่าแนะนำ: **อนุญาตต่อ** เพราะเป้าหมายคือให้ AI รู้จักแบรนด์ และเว็บไม่มีเนื้อหาที่ต้องหวง

## 1.3 Server-side rendering

- ทุกหน้า public render ฝั่ง server (PHP) — เนื้อหา หัวข้อ FAQ ราคา/ขั้นตอน อยู่ใน HTML ตั้งแต่ไบต์แรก ไม่ต้องรัน JavaScript
- JavaScript ใช้แค่ animation / เมนู / ฟอร์ม — บอทที่ไม่รัน JS (GPTBot, ClaudeBot, PerplexityBot ไม่รัน JS) เห็นเนื้อหาครบ
- ตรวจด้วย `curl` ดิบ: H1, เนื้อหา, FAQ, JSON-LD อยู่ใน response แรกทุกหน้าที่สุ่ม

## 1.4 Status code และ URL

- SEO regression 95 URL ของเว็บเดิม: **0 ปัญหา** (ดู `docs/seo/regression.md`)
- 410 สองตัวที่ตั้งใจ (หน้าทดสอบเก่า) ยังเป็น 410
- **เพิ่มใหม่ (migration 052):** sitemap ของ WordPress ที่ Google เคยรู้จัก 301 ไป sitemap ใหม่
  `/wp-sitemap.xml` · `/wp-sitemap-posts-post-1.xml` · `/wp-sitemap-posts-page-1.xml` · `/wp-sitemap-taxonomies-category-1.xml` · `/wp-sitemap-posts-portfolio-1.xml` · `/wp-sitemap-users-1.xml` · `/sitemap_index.xml`
  — วัน cutover Search Console จะไม่เจอ sitemap 404

## 1.5 ของใหม่สำหรับ AI

| สิ่งที่เพิ่ม | ที่ | หมายเหตุ |
|---|---|---|
| `/llms.txt` | `LlmsService` | Markdown สรุปบริษัท + หน้าทั้งหมดที่ index ได้ (3 ภาษา) + บทความ 50 เรื่อง · สร้างจากข้อมูลจริงทุกครั้ง · ไม่มี `/admin/`, `/thank-you/`, หน้า noindex |
| Organization schema | `SchemaService::organization()` | ชื่อทางการ (ไทย/อังกฤษ), วันจดทะเบียน 3 เม.ย. 2552, ที่อยู่, เบอร์ E.164, อีเมลขายสาธารณะ (sale@), Facebook · **ไม่มี** rating / review / award / จำนวนลูกค้า |
| IndexNow | `IndexNowService` | แจ้ง Bing/Yandex/Seznam ทุกครั้งที่เผยแพร่/แก้หน้า · ทำงานเฉพาะ production ที่ตั้ง `INDEXNOW_KEY` · `/<key>.txt` ตอบเฉพาะคีย์ที่ถูก ที่เหลือ 404 |

## 1.6 ไม่เปิดข้อมูลที่ไม่ควรเปิด

- `/admin/*` อยู่หลังล็อกอิน + Disallow ใน robots + noindex
- llms.txt และ schema ใช้ **อีเมลสาธารณะท้ายเว็บ** ไม่ใช่กล่องรับแจ้งเตือน lead (แก้หลัง security review อัตโนมัติ — กล่องแจ้งเตือนตั้งเป็นอีเมลภายในได้จาก CMS)
- ไม่มีชื่อพนักงาน / เบอร์มือถือส่วนตัว / ข้อมูล lead ในหน้า public, sitemap, llms.txt
- `/_preflight`, `/_migrate` ตอบ 404 ถ้าไม่มี token header

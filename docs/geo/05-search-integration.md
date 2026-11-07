# GEO 5 — Search Console · Bing Webmaster · IndexNow (2026-11-07)

ทำ **หลัง cutover production** เท่านั้น — ก่อนหน้านั้น sitemap ใหม่ยังไม่อยู่บนโดเมนจริง
ทุกขั้นไม่ต้องแก้ DNS (ใช้วิธียืนยันด้วยไฟล์ หรือขอสิทธิ์ property เดิม) — ถ้าเลือกยืนยันด้วย DNS TXT ต้องอนุมัติแยก

## 5.1 Google Search Console

1. ตรวจว่ามี property อยู่แล้วไหม — โดเมน `omc.co.th` มี TXT `google-site-verification=...` อยู่แล้ว แปลว่ามีคนยืนยัน **Domain property** ไว้ → ขอสิทธิ์จากเจ้าของ (อย่าสร้างใหม่ซ้ำ)
2. ถ้าไม่มี: เพิ่ม **URL-prefix property** `https://mlmsoftware.omc.co.th/` ยืนยันด้วย **ไฟล์ HTML** ที่ Google ให้ (`google<รหัส>.html`) — ส่งไฟล์ให้ dev วางใน `public/` แล้ว deploy · ไม่ต้องแตะ DNS (CMS ยังไม่มีช่องใส่ meta verification)
3. Sitemaps → ส่ง `https://mlmsoftware.omc.co.th/sitemap.xml` · `/wp-sitemap.xml` เดิม 301 มาที่นี่เอง ไม่ต้องลบ
4. URL Inspection → ขอ index หน้า `/`, `/mlm-software-omc/`, `/pricing/`, `/faq/`, `/about/`
5. สัปดาห์ 1–4 หลัง cutover: ดู Pages → "Not found (404)" และ "Redirect error" ทุกวัน — เทียบกับรายการ 95 URL ใน `docs/seo/regression.md`
6. Rich Results Test: หน้า `/` (Organization) · `/faq/` (FAQPage) · บทความ (Article + Breadcrumb)

## 5.2 Bing Webmaster Tools

1. https://www.bing.com/webmasters → **Import from Google Search Console** (เร็วสุด ยืนยันอัตโนมัติ) หรือยืนยันด้วยไฟล์ `BingSiteAuth.xml` ใน `public/` แบบเดียวกัน
2. ส่ง sitemap `/sitemap.xml`
3. Bing index ใช้กับ **Copilot** และเป็นแหล่งหนึ่งของ **ChatGPT Search** — สำคัญกว่าที่ traffic ของ Bing บอก

## 5.3 IndexNow (ระบบทำให้แล้ว รอแค่คีย์)

1. สุ่มคีย์บน server production: `php -r "echo bin2hex(random_bytes(16));"`
2. ใส่ใน `.env` production: `INDEXNOW_KEY=<คีย์>` (คีย์ **ไม่ใช่ความลับ** — เปิดอ่านได้ที่ `/<คีย์>.txt` ตามสเปค แต่ก็ไม่ต้องใส่ใน git)
3. ตรวจ: `https://mlmsoftware.omc.co.th/<คีย์>.txt` ต้องได้คีย์เดียวกันกลับมา · คีย์อื่นต้อง 404
4. จากนั้นทุกครั้งที่ **เผยแพร่ / แก้ / เปลี่ยน URL** หน้าหรือบทความใน CMS ระบบแจ้ง api.indexnow.org เอง (Bing, Yandex, Seznam, Naver รับต่อกัน) — ดูผลใน `storage/logs/app.log` คำว่า `indexnow`
5. ทำงานเฉพาะ `APP_ENV=production` — staging ไม่ส่งเด็ดขาด

## 5.4 สิ่งที่ Google ไม่มี

Google **ไม่รับ IndexNow** และไม่ใช้ llms.txt — ใช้ sitemap + ลิงก์ภายใน + Search Console ตามปกติ
AI Overviews ใช้ index เดียวกับ Google Search → ทุกอย่างที่ทำให้ SEO ดี ช่วย AI Overviews โดยตรง

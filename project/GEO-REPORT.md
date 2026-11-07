# GEO / AI Search Visibility — Implementation Report (2026-11-07)

ขอบเขต: ทำเฉพาะสิ่งที่ระบบและสิทธิ์ปัจจุบันทำได้ · **ไม่ deploy production · ไม่แก้ DNS · ไม่แก้เนื้อหาเดิมที่มีอันดับ · ไม่สร้างคำกล่าวอ้างที่ไม่มีหลักฐาน**

## ทำแล้ว (ในโค้ด — ขึ้น staging)

| งาน | ผล | ไฟล์ |
|---|---|---|
| ตรวจ crawler 9 บอท (Google, Bing, OpenAI ×3, Anthropic, Perplexity, Apple, Common Crawl) | production เดิม 200 ทุกตัว ไม่มี WAF บล็อก · staging 200 + noindex | `docs/geo/01-technical-audit.md` |
| `/llms.txt` | สร้างจากข้อมูลจริง: ข้อมูลบริษัท + หน้า index ได้ 3 ภาษา + บทความ 50 เรื่อง · ไม่มี admin / thank-you / หน้า noindex | `app/Services/LlmsService.php` |
| Organization schema ที่ตรวจสอบได้ | ชื่อทางการไทย/อังกฤษ, วันจดทะเบียน 2009-04-03, ที่อยู่, เบอร์ E.164, อีเมลขายสาธารณะ, Facebook · ไม่มี rating/review/award | `app/Services/SchemaService.php` |
| sitemap WordPress เดิม → 301 | 7 URL (`/wp-sitemap*.xml`, `/sitemap_index.xml`) | migration `052` |
| IndexNow | แจ้ง URL อัตโนมัติเมื่อเผยแพร่/แก้ · เฉพาะ production + มีคีย์ · `/<key>.txt` ตอบเฉพาะคีย์ถูก | `app/Services/IndexNowService.php` |
| แก้จาก security review อัตโนมัติ | schema + llms.txt เลิกใช้อีเมลผู้รับแจ้งเตือน lead (ตั้งเป็นอีเมลภายในได้) → ใช้อีเมลสาธารณะท้ายเว็บ | — |

## เอกสารที่ส่งมอบ

| # | เอกสาร | เนื้อหา |
|---|---|---|
| 01 | `docs/geo/01-technical-audit.md` | robots, SSR, status code, crawler, ข้อมูลที่ไม่เปิด |
| 02 | `docs/geo/02-questions-gap.md` | 50 คำถามลูกค้า × หน้าที่ตอบ · ✅ 28 · 🟡 15 · ❌ 7 · เนื้อหาใหม่ 8 ชิ้นเรียงตามผล |
| 03 | `docs/geo/03-baseline-monitoring.md` | baseline 0/6 · KPI · วิธีวัดที่ได้รับอนุญาต · ชุด prompt 30 ข้อ · สเปค dashboard |
| 04 | `docs/geo/04-roadmap-authority.md` | Roadmap 90 วัน (นับจากวัน cutover) · แผน authority ไม่มีรีวิวปลอม/ลิงก์สแปม |
| 05 | `docs/geo/05-search-integration.md` | ขั้นตอน GSC · Bing Webmaster · IndexNow |

## QA

- tests: **5052 passed · 0 failed** (รวมชุด GEO: llms.txt, Organization facts, ไม่มี rating/award, sitemap 301, `.txt` สุ่ม → 404)
- `scripts/check.php`: 0 ต้องแก้ · 19 ควรดู (เท่า baseline)
- SEO regression 95 URL: **0 ปัญหา** (410 สองตัวตามตั้งใจ)

## ยังไม่ทำ (ตั้งใจ — ต้องมีข้อมูลหรืออนุมัติ)

- เนื้อหาใหม่บนเว็บ: ทุกชิ้นมีข้อกฎหมายหรือคำกล่าวอ้างเกี่ยวกับ OMC ที่ต้องมีคนตรวจ
- Dashboard: ยังไม่มีข้อมูลรอบแรกและไม่มีบัญชี/API key สำหรับวัด
- GSC / Bing: ต้องรอ cutover + สิทธิ์
- บทความประกันภัย 9 เรื่องที่ทำให้ entity เบลอ: ต้องดู traffic ใน GSC ก่อนตัดสิน

## รออนุมัติ / รอข้อมูลจาก OMC

1. **นโยบายบอทฝึกโมเดล** (GPTBot, ClaudeBot, Google-Extended, CCBot): อนุญาตต่อ (ค่าแนะนำ) หรือบล็อก
2. **สิทธิ์ Google Search Console** (มี Domain property ของ omc.co.th อยู่แล้ว) + Bing Webmaster
3. **INDEXNOW_KEY** บน production (ตั้งตอน cutover)
4. ตรวจข้อกฎหมายบทความ "MLM vs แชร์ลูกโซ่"
5. ยืนยันรูปแบบบริการ (สำเร็จรูป/เช่า/พัฒนาเฉพาะ), มาตรการความปลอดภัย, ระบบที่เคยเชื่อมต่อจริง
6. ความยินยอมลูกค้าสำหรับกรณีศึกษา + ข้อมูลทีมงาน
7. บัญชีทดสอบ AI (ChatGPT/Perplexity/Gemini) หรือ API key สำหรับวัด visibility
8. บัญชี Google Business Profile / LinkedIn / ไดเรกทอรีซอฟต์แวร์
9. (ต่อจากรอบก่อน) DMARC record ของ omc.co.th · repo GitHub เป็น private · รายการ 7 ข้อใน `GO-NO-GO.md`

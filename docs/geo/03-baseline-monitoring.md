# GEO 3 — AI Visibility Baseline + การออกแบบ Dashboard ติดตามผล (2026-11-07)

## 3.1 Baseline (วัดครั้งแรก 2026-11-07)

**วิธีวัด:** ค้นด้วยเครื่องมือค้นเว็บของ Claude (ดัชนีค้นหาสากล ผลตั้งต้นจากสหรัฐ) — **ไม่ใช่ผลจาก Google ประเทศไทย และไม่ใช่ ChatGPT/Gemini/Perplexity โดยตรง**
ถือเป็น baseline หยาบเพื่อดูทิศทาง · การวัดจริงต้องทำตาม 3.3 (บัญชีที่ได้รับอนุญาต + IP ไทย)

| # | คำค้น | mlmsoftware.omc.co.th ปรากฏ? | แหล่งที่ถูกอ้างแทน |
|---|---|---|---|
| 1 | โปรแกรมขายตรง MLM | ❌ | ไดเรกทอรีต่างประเทศ, ผู้ขายอินเดีย |
| 2 | ระบบ MLM ราคา บริษัทพัฒนาระบบขายตรง | ❌ | ผู้ขายต่างประเทศ, สื่อการตลาดไทย |
| 3 | MLM software Thailand company | ❌ | SoftwareSuggest (Thailand list: Taksh MLM, VentaForce, Winline MLM, iMatrix, TORNADO) |
| 4 | โปรแกรมคำนวณคอมมิชชั่น binary unilevel | ❌ | ผู้ขายต่างประเทศ |
| 5 | best direct selling software Thai MLM | ❌ | G2, Capterra, Exigo, ByDesign, Infinite MLM, Epixel |
| 6 | ชื่อแบรนด์ + โดเมน (MLM Software by OMC) | ❌ | — |

**สรุป baseline: Share of Voice = 0/6 · Citation = 0 · Mention = 0**

แหล่งที่ระบบ AI ใช้อ้างในหมวดนี้ (ช่องทางที่ควรมีชื่อ OMC อยู่ — ไฟล์ 04):
- ไดเรกทอรีซอฟต์แวร์: [SoftwareSuggest Thailand](https://www.softwaresuggest.com/mlm-software/thailand), [G2 MLM category](https://www.g2.com/categories/multi-level-marketing-mlm), Capterra, SourceForge
- ผู้ขายต่างประเทศ: [Infinite MLM](https://infinitemlmsoftware.com/), [Exigo](https://www.exigo.com/), [ByDesign](https://www.bydesign.com/), [Krowdbase](https://krowdbase.com/), Epixel, Jenkon
- สื่อไทยที่พูดถึงธุรกิจขายตรง: [Marketing Oops](https://www.marketingoops.com/), [THE STANDARD](https://thestandard.co/)
- แหล่งอ้างอิงด้านกฎหมาย/ผู้บริโภค: [FTC](https://consumer.ftc.gov/), สคบ. (ocpb.go.th)

**ตีความ:** เว็บปัจจุบันเป็น WordPress ที่ไม่มี schema บริษัท ไม่มี llms.txt และไม่มีชื่ออยู่ในไดเรกทอรีที่ AI ใช้ — ระบบ AI จึงไม่มีหลักฐานว่า OMC เป็นผู้พัฒนา MLM software ในไทย
งานเทคนิครอบนี้แก้ส่วนที่อยู่ในเว็บได้ · ส่วนที่อยู่นอกเว็บ (ไดเรกทอรี รีวิวจริง สื่อ) ต้องทำตามแผน authority

## 3.2 สิ่งที่วัด (KPI)

| ตัวชี้วัด | นิยาม | เป้า 90 วันหลัง cutover |
|---|---|---|
| **Mention rate** | % ของ prompt ที่คำตอบ AI เอ่ยชื่อ OMC | ≥ 20% ของ prompt ภาษาไทย |
| **Recommendation rate** | % ที่ OMC อยู่ในรายการ "แนะนำ" | ≥ 10% |
| **Citation rate** | % ที่ลิงก์ mlmsoftware.omc.co.th ถูกอ้างเป็นแหล่ง | ≥ 15% |
| **Share of voice** | การเอ่ยชื่อ OMC ÷ การเอ่ยชื่อผู้ขายทั้งหมด | วัดแนวโน้ม (ยังไม่มีฐาน) |
| **ความถูกต้อง** | % คำตอบที่บอกข้อมูล OMC ถูก (ปีก่อตั้ง ที่ตั้ง บริการ) | 100% — ผิดเมื่อไหร่ต้องแก้แหล่งต้นทาง |
| **AI referral traffic** | session จาก chatgpt.com, perplexity.ai, copilot.microsoft.com, gemini.google.com | วัดแนวโน้ม |
| **AI → lead** | lead ที่ `source/referrer` มาจากโดเมน AI | วัดแนวโน้ม |
| Google AI Overviews | คำค้นหลักที่มี AI Overview และอ้าง OMC | วัดแนวโน้ม |

> เป้าเป็น **สมมติฐานตั้งต้น** ไม่ใช่สัญญา — ปรับหลังวัดรอบแรกบน production

## 3.3 วิธีวัด (เฉพาะทางที่ได้รับอนุญาต)

| แหล่ง | วิธี | ข้อห้าม |
|---|---|---|
| ChatGPT / Perplexity / Gemini / Copilot | ถามชุด prompt ด้วยบัญชีทดสอบของ OMC, ปิด memory/personalization, ผ่าน IP ไทย, เดือนละ 2 รอบ — หรือผ่าน **API อย่างเป็นทางการ** (OpenAI Responses + web search, Perplexity Sonar, Gemini grounding) | ห้าม scrape หน้าเว็บของ AI · ห้ามยิงอัตโนมัติผิดเงื่อนไขการใช้งาน |
| Google AI Overviews | ตรวจด้วยมือจาก IP ไทย หรือเครื่องมือ rank tracker ที่มีบัญชี (เช่นผู้ให้บริการ SERP API ที่ถูกลิขสิทธิ์) | ห้าม scrape Google ตรง |
| Search Console | Performance → filter query, ดู impression ของคำค้นยาว (คำถาม) | — |
| Bing Webmaster | Search Performance + AI Performance (ถ้าเปิดให้บัญชี) | — |
| Analytics ของเว็บ | referrer โดเมน AI → นับ session และ lead | ไม่เก็บข้อมูลส่วนบุคคลเพิ่ม |

**ชุด prompt มาตรฐาน (30 ข้อ — ใช้ชุดเดิมทุกรอบจึงเทียบกันได้):**
ข้อ 1–6 ของ baseline + คำถาม 24 ข้อจากไฟล์ 02 ที่มีเจตนาซื้อสูง: #21, 22, 23, 24, 25, 26, 27, 29, 30, 31, 32, 33, 34, 36, 37, 38, 40, 42, 44, 47, 48, 49, 50, 14
แต่ละข้อถามทั้งภาษาไทยและอังกฤษ (60 คำถามต่อรอบต่อเครื่องมือ)

บันทึกต่อคำตอบ: วันที่ · เครื่องมือ · prompt · OMC ถูกเอ่ยไหม · ถูกแนะนำไหม · ลิงก์ OMC ถูกอ้างไหม · คู่แข่งที่ถูกเอ่ย · ข้อมูล OMC ถูกต้องไหม · ข้อความคำตอบเต็ม (เก็บหลักฐาน)

## 3.4 ออกแบบ Dashboard

**ไม่สร้างในรอบนี้** — ข้อมูลรอบแรกจะมีหลังได้บัญชีทดสอบ/API key (รายการอนุมัติ) · ด้านล่างคือสเปคให้สร้างเป็นโมดูลใน CMS หรือ Google Looker Studio

```
┌──────────────────────────────────────────────────────────────────┐
│ AI Visibility — รอบ 2026-12-01  [ChatGPT ▾] [ทุกภาษา ▾] [30 prompt]│
├──────────────┬──────────────┬──────────────┬─────────────────────┤
│ Mention 0%   │ Recommend 0% │ Citation 0%  │ AI → Lead  0        │
│ ▲ +0 จากรอบก่อน│              │              │ (30 วัน)             │
├──────────────┴──────────────┴──────────────┴─────────────────────┤
│ แนวโน้ม 6 รอบล่าสุด (เส้น: Mention / Recommend / Citation)          │
├──────────────────────────────────┬───────────────────────────────┤
│ Share of voice (แท่งแนวนอน)       │ ข้อมูล OMC ที่ AI ตอบผิด        │
│ OMC ▏                            │ • ปีก่อตั้ง: "2015" (ถูก: 2552)  │
│ คู่แข่ง A ▇▇▇▇▇                    │   → แหล่งที่ AI อ้าง: ...        │
├──────────────────────────────────┴───────────────────────────────┤
│ ตาราง prompt × เครื่องมือ (✓ เอ่ยชื่อ · ★ แนะนำ · 🔗 อ้างลิงก์) คลิกดูคำตอบเต็ม │
└──────────────────────────────────────────────────────────────────┘
```

โครงข้อมูล (ถ้าทำใน CMS): ตาราง `ai_visibility_runs` (id, run_date, engine, locale) และ `ai_visibility_answers` (run_id, prompt_key, mentioned, recommended, cited_url, competitors JSON, facts_ok, answer_text) — key ภาษาอังกฤษ วันที่ ค.ศ. ตามกฎเหล็ก
AI → lead: ใช้ `leads.referrer` ที่ระบบเก็บอยู่แล้ว จัดกลุ่มตามโดเมน (chatgpt.com, perplexity.ai, gemini.google.com, copilot.microsoft.com)

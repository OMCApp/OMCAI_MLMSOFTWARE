# Brief — OMC MLM Software website rebuild

ผู้ใช้ส่ง brief 3 ฉบับ (2026-11-03) — ฉบับล่าสุดคือฉบับหลัก เมื่อขัดกันให้ยึดฉบับล่าสุด

## ฉบับที่ 3 (หลัก) — "ทำเว็บไซต์ OMC MLM Software ให้เสร็จทั้งระบบ"
อนุมัติเต็มรูปแบบ ทำจนจบ ไม่ต้องถามจุกจิก · หยุดถามได้เฉพาะ: เสี่ยง SEO เดิมเสียหายหนัก · เปลี่ยนโดเมน/DNS/infra · ต้องใช้ข้อมูลจริงที่ยังไม่มี · ทิศทางธุรกิจที่กระทบสาระสำคัญ

### สำคัญที่สุด
1. SEO เดิมห้ามพัง 2. รักษา keyword ที่ติดอันดับ 3. น่าเชื่อถือกว่าเดิม ปิดการขายได้จริง 4. เว็บเก่ามีตัวตน คนสงสัยว่า OMC คือใคร — ต้องตอบให้ได้ 5. ใช้โลโก้ใหม่ (`project/design/refs/logo-omc.png`)

### ดีไซน์
modern · clean · premium · corporate · trustworthy · technology-driven · มีความเป็นบริษัทจริง
- พื้นขาว/สว่างเป็นหลัก · ส้มเป็นสีแบรนด์ · สีเข้มเป็น accent ได้แต่ไม่มืดทั้งหน้า · ตัวอักษรชัด
- ภาพคนจริง / ทีม / office / dashboard จริง
- animation: reveal on scroll, hover, dashboard mockup animate เบาๆ, counter, tab/accordion/slider, parallax เบาๆ, micro-interaction
- ไม่เอา: เว็บมืด, tech แข็งกระด้าง, animation รก, futuristic/neon/คริปโต, 3D หนัก, generic template, อะไรที่ทำให้ช้า
- แบบอ้างอิงหน้าแรก: `project/design/refs/home-reference.png`

### กลุ่มเป้าหมาย
เจ้าของธุรกิจที่จะเริ่ม MLM · บริษัทขายตรงที่จะเปลี่ยนระบบ · แบรนด์อาหารเสริม · สกินแคร์/เครื่องสำอาง · สุขภาพ/ความงาม · distributor · affiliate/referral · franchise · ต้องการ custom · ลูกค้าต่างประเทศ
อยากรู้: OMC คือใคร · เคยทำอะไร · น่าเชื่อถือไหม · ระบบทำอะไรได้ · แผนรายได้อะไร · ปรับแต่งได้ไหม · ซัพพอร์ต · ราคา/เริ่มยังไง · ย้ายระบบเดิมได้ไหม

### โครงสร้าง
- Public: Home · MLM Software · Features · Compensation Plans · Solutions/Industries · Pricing/Request Quote · Demo · About OMC · Contact · FAQ · Blog · Support/Help
- Landing (SEO): โปรแกรม MLM · ระบบ MLM · โปรแกรมขายตรง · ระบบขายตรง · รับทำระบบ MLM · รับเขียนโปรแกรม MLM · MLM Software Thailand · MLM สำหรับอาหารเสริม/เครื่องสำอาง/ธุรกิจสุขภาพ/ตัวแทนจำหน่าย · MLM แบบ custom · ย้ายระบบ MLM เดิม · ระบบคำนวณคอมมิชชัน · ระบบสมาชิกขายตรง
- แผนรายได้: Binary · Unilevel · Matrix · Board · Stair Step · Hybrid
- Home sections: Hero · Why OMC/เราคือใคร · Trust · Features · Compensation preview · Industries · Real system/dashboard · About OMC · CTA

### Copy
เขียนใหม่ทั้งเว็บ ภาษาไทยชัด มืออาชีพ ไม่เว่อร์ · **ห้ามตัวเลขมั่ว · ห้าม client logo / testimonial ปลอม · ห้ามอ้างเกินจริง** — ไม่มีข้อมูลยืนยันใช้ข้อความกลางๆ

### CMS
หน้าเว็บ · บทความ · SEO title/meta/slug · redirect · รูปภาพ · เมนู · FAQ · testimonials/case studies · CTA/forms · lead · draft/preview/publish · roles/permissions · audit log · backup/restore

### Lead
ฟอร์ม: ขอ Demo · ขอใบเสนอราคา · ปรึกษาเริ่มธุรกิจ · ติดต่อทีม · เปลี่ยนจากระบบเดิม · LINE/โทร/อีเมล
ช่อง: ชื่อ · บริษัท · ประเภทธุรกิจ · เบอร์/อีเมล/LINE · มีระบบอยู่แล้วไหม · ต้องการแบบไหน · จำนวนสมาชิกโดยประมาณ · รายละเอียด

### Technical
เร็ว · mobile/responsive · SEO-friendly · HTML crawlable · image optimized · security ครบ · staging/production flow · rollout/rollback plan

## ฉบับที่ 1–2 (2026-11-03) — ยังมีผล
- เลิก WordPress ทำด้วย PHP + DB ที่ OMC ดูแลเองได้ · render ฝั่ง server
- Core Web Vitals: LCP ≤ 2.5s · CLS ≤ 0.1 · INP ≤ 200ms (field p75)
- Git + CI/CD (build · test · deploy · rollback) · branch `rebuild-cms` → merge `main` (ผู้ใช้ให้ merge+push เองได้)
- staging แยก มี auth + noindex · ห้าม deploy ทับ production
- เก็บบทความเดิม 37 เรื่อง + ประกัน 11 เรื่องไว้ก่อน ห้ามลบหรือ redirect จนกว่าดู SEO performance/backlinks
- ห้ามสลับ production จนผ่าน SEO Migration Gate + Rollback Readiness
- Deploy: ผู้ใช้เลือก FTP ธรรมดา (ไม่ใช่ FTPS)

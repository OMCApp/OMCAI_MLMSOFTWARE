---
name: "OMC MLM Software"
version: 1
status: decided
mode:
  default: light
color:
  brand:
    primary:      { light: "#C24E00", dark: "#FF8A3D" }
    onPrimary:    { light: "#FFFFFF", dark: "#1A0E05" }
    primaryHover: { light: "#A84300", dark: "#FFA366" }
    accent:       { light: "#FD6B08", dark: "#FF8A3D" }
    accentEnd:    { light: "#FE9609", dark: "#FFA94D" }
    accentSoft:   { light: "#FDE9DD", dark: "#3A2416" }
    logo:         { light: "#FAA61A", dark: "#FAA61A" }
    link:         { light: "#B34700", dark: "#FFA366" }
    ink:          { light: "#1A243D", dark: "#0B0F1A" }
    onInk:        { light: "#FFFFFF", dark: "#F4F5F8" }
  surface:
    page:         { light: "#FCFCFD", dark: "#0E1014" }
    card:         { light: "#FFFFFF", dark: "#171A20" }
    raised:       { light: "#F5F6FA", dark: "#1F232B" }
    tint:         { light: "#FDF9F6", dark: "#1C1712" }
  text:
    strong:       { light: "#070A18", dark: "#F4F5F8" }
    normal:       { light: "#3C424A", dark: "#C6CAD4" }
    muted:        { light: "#5A606B", dark: "#9AA1AE" }
  border:
    normal:       { light: "#E4E5EB", dark: "#2A2F38" }
    strong:       { light: "#868B96", dark: "#737B8A" }
  state:
    success:      { light: "#15803D", dark: "#4ED08A" }
    warning:      { light: "#A16207", dark: "#F0B44A" }
    danger:       { light: "#C0262D", dark: "#FF7B72" }
    info:         { light: "#1D5FD0", dark: "#7DA0FF" }
  focus:          { light: "#C24E00", dark: "#FF8A3D" }
  overlay:        { light: "rgb(7 10 24 / .48)", dark: "rgb(0 0 0 / .65)" }
typography:
  family:
    heading: "Prompt"
    body:    "IBM Plex Sans Thai"
    mono:    "ui-monospace"
    zh:      '"PingFang SC", "Microsoft YaHei", "Noto Sans CJK SC", "Noto Sans SC", sans-serif'
  scale:      { xs: 0.75, sm: 0.875, base: 1, lg: 1.125, xl: 1.375, 2xl: 1.75, 3xl: 2.25, 4xl: 3, display: 4 }
  weight:     { normal: 400, medium: 500, bold: 600 }
  lineHeight: { tight: 1.3, snug: 1.45, normal: 1.65, loose: 1.85 }
space:        { 1: 4px, 2: 8px, 3: 12px, 4: 16px, 5: 20px, 6: 24px, 8: 32px, 10: 40px, 12: 48px, 16: 64px, 20: 80px, 24: 96px }
radius:       { sm: 6px, md: 10px, lg: 16px, xl: 24px, full: 999px }
icon:         { sm: 16px, base: 20px, lg: 32px, xl: 48px }
shadow:
  sm: "0 1px 2px rgb(16 24 40 / .06)"
  md: "0 6px 20px rgb(16 24 40 / .07)"
  lg: "0 24px 48px rgb(16 24 40 / .12)"
  header: "0 6px 24px rgb(16 24 40 / .08)"
motion:
  duration: { fast: 140ms, normal: 220ms, slow: 320ms, reveal: 560ms }
  easing:   { standard: "cubic-bezier(.2,0,0,1)", enter: "cubic-bezier(0,0,0,1)", soft: "cubic-bezier(.22,.61,.36,1)" }
layout:
  density: comfortable
  nav: sidebar
  maxWidth: 1200px
  formWidth: 760px
  fieldMinWidth: 220px
  tileMinWidth: 260px
  asideWidth: 320px
  pageMediaWidth: 360px
  pageMediaHeight: 200px
  sidebarWidth: 240px
  sidebarCollapsed: 60px
  breakpoint: 768px
component:
  table:
    rowHeight:      44px
    cellPadding:    "0 12px"
    headBackground: transparent
    headPadding:    "8px 12px"
    fontSize:       0.875rem
  choice:
    size:      20px
    mark:      10px
    arrowWide: 10px
    arrowTall: 6px
  badge:
    radius:            6px
    typeBorderStyle:   dashed
    statusBorderStyle: solid
  hero:
    minHeight:       600px
    minHeightMobile: 480px
    gap:             48px
    imageRatio:      "4 / 3"
    scrim:           "linear-gradient(to top, rgb(0 0 0 / .72) 0%, rgb(0 0 0 / .45) 42%, rgb(0 0 0 / .08) 100%)"
    onImage:         "#FFFFFF"
  productCard:
    imageRatio:  "3 / 4"
    gap:         16px
    gridGap:     32px
    nameSize:    1.0625rem
    metaSize:    0.9375rem
  prose:
    maxWidth:     70ch
    paragraphGap: 16px
    headingGap:   36px
  newsItem:
    imageRatio:   "16 / 9"
    imageWidth:   320px
    gap:          24px
    dividerStyle: "1px solid var(--color-border-normal)"
  site:
    headerHeight:       76px
    headerHeightMobile: 64px
    logoHeight:         36px
    logoHeightMobile:   30px
    footerLogoHeight:   56px
    sectionPad:         80px
    sectionPadMobile:   56px
    gutter:             24px
    gutterMobile:       16px
    megaWidth:          640px
    drawerWidth:        360px
    revealShift:        24px
    headMaxWidth:       720px
  showcase:
    laptopWidth:  600px
    phoneWidth:   168px
    chartHeight:  120px
    bezel:        12px
    barRatio:     "4 / 3"
  industry:
    mediaRatio:   "16 / 10"
---

# เหตุผลของดีไซน์นี้

## ทิศทาง

บริษัทซอฟต์แวร์ไทยที่จริงจังและเข้าถึงได้ — **พื้นขาวสะอาด ส้มแบรนด์จากโลโก้เป็นตัวนำสายตา ตัวอักษรหมึกเข้มอมน้ำเงิน** ตามแบบอ้างอิงหน้าแรก (`refs/home-reference.png`) และ brief ฉบับที่ 3 ("modern · clean · premium · corporate · trustworthy · technology-driven") · ความ "เทคโนโลยี" มาจาก mockup แดชบอร์ดที่สร้างด้วย HTML/CSS ไม่ใช่จากสีมืดหรือ neon ซึ่ง brief ห้ามไว้

ฟอนต์: หัวข้อ **Prompt** (600) — ทรงเรขาคณิตไม่มีหัว หนักแน่นแบบพาดหัวในแบบอ้างอิง · เนื้อความ **IBM Plex Sans Thai** (400/500/600) — อ่านง่าย ทางการ ตัวเลขชัด · ทั้งสองตัว self-host ที่ `public/assets/vendor/fonts/` (SIL OFL 1.1 — ใช้เชิงพาณิชย์ได้) · **ภาษาจีน** (`/zh/`) ใช้ฟอนต์ระบบ `font.zh` (PingFang SC / Microsoft YaHei / Noto Sans CJK SC) ผ่าน `:lang(zh)` — ฟอนต์จีนแบบ webfont หนักหลาย MB จึงไม่โหลด

## ลายเซ็นของระบบนี้

1. **สี่เหลี่ยมมนสามช่องของโลโก้** — ตัว mark "OMC" เป็นสัญลักษณ์ที่ใช้ซ้ำ: กรอบไอคอนของการ์ดเป็นสี่เหลี่ยมมนพื้นส้มอ่อน (`accentSoft`) แบบเดียวกับช่องของโลโก้
2. **ปุ่มหลักทรงแคปซูลส้มเข้ม + ลูกศร →** ทุก CTA · แถบปิดท้าย (CTA band) ไล่สีจากน้ำเงินหมึก (`ink`) ไปส้มเข้ม (`primary`) ตามแบบ
3. **แสงส้มจางๆ ด้านหลังส่วนเปิด** — ทำด้วย CSS gradient ไม่ใช่ไฟล์ภาพ

## สิ่งที่จงใจไม่ทำในโปรเจคนี้

- **ไม่ใช้ส้มสดของแบบ (`#FD6B08`) เป็นพื้นปุ่มที่มีตัวหนังสือขาว** — วัดได้ 2.88:1 ไม่ผ่าน 4.5:1 · ปุ่มใช้ `primary` `#C24E00` (เฉดเดียวกัน ลดความสว่างจนผ่าน 4.79:1) · ส้มสดยังอยู่ใน gradient ตกแต่ง แสงพื้นหลัง และกราฟ ซึ่งไม่มีตัวหนังสือวางทับ
- **ไม่ใช้ส้มอ่อนของโลโก้ (`#FAA61A`) เป็นสีไอคอนหรือข้อความ** — บนพื้นขาวได้ ~2:1 · ไอคอนของการ์ดใช้ `primary` บนกรอบ `accentSoft` (4.08:1)
- **ไม่ทำเว็บสาธารณะโหมดมืด** — ผู้ใช้สั่ง "พื้นขาว/สว่างเป็นหลัก · ไม่เอาเว็บมืด" · token ทุกตัวยังมีค่าโหมดมืดครบตามกฎเหล็กข้อ 19 เพราะหลังบ้านใช้ · `layouts/site.php` บังคับ `data-theme="light"` (บันทึกใน `NOTES.md`)
- **ไม่ใช้ภาพบุคคลสต็อก** แทนทีมหรือผู้บริหาร — ส่วนเปิดใช้ mockup แดชบอร์ด (แล็ปท็อป + มือถือ) ที่สร้างด้วย HTML/CSS แทนผู้หญิงในแบบ และเว้นช่องภาพให้ CMS ใส่ภาพจริงทีหลัง
- **ไม่แสดงตัวเลข โลโก้ลูกค้า และรีวิวที่ยังไม่มีหลักฐาน** — บล็อก `trust_bar` `stats` `testimonials` ซ่อนตัวเองเมื่อไม่มีข้อมูล (brief: ห้ามตัวเลขมั่ว · ห้าม client logo/testimonial ปลอม)
- **ไม่ใช้ animation หนัก / 3D / parallax แรง** — reveal เลื่อนขึ้น 24px + จางเข้า · parallax ≤ 6% ของระยะ scroll · ปิดทั้งหมดเมื่อ `prefers-reduced-motion`
- **ไม่ย่อพื้นที่กดต่ำกว่า 44px** แม้ในแบบเมนูบน เม็ดเบอร์โทร และจุดของสไลด์จะเล็กกว่านั้น

## แบบของแต่ละหน้า

| หน้า | ภาพแบบ | ระดับ | ต่างจากแบบ |
|---|---|---|---|
| `/` `/en/` `/zh/` (hero หน้าแรก — แบบล่าสุด 2026-11-04 ชนะ home-reference) | `refs/home-mockup-3.png` | ตรงแบบ | ภาพบุคคลเป็นภาพ stock ฟรี (ไม่มีเนกไท) · ตัวเลขแดชบอร์ดมีป้ายข้อมูลสมมติ · สถิติแสดงเฉพาะที่ยืนยันแล้ว (NOTES.md "Mockup ชุดที่ 3") |
| `/` (หน้าแรก — ส่วนอื่นนอก hero) | `refs/home-reference.png` | ตรงแบบ | ดู "ความต่างจากแบบ (C4ก)" ข้างล่าง — ทุกจุดมีเหตุผลจาก brief หรือกฎแข็ง |
| โลโก้ (header · footer · favicon) | `refs/logo-omc.png` | ตรงแบบ | วาดใหม่เป็น SVG จากพิกเซลของไฟล์นี้ (ช่องสี่เหลี่ยม · ตัวอักษร O M C · บรรทัดชื่อบริษัท) — แบบ header ในภาพหน้าแรกเป็นโลโก้คนละตัว ใช้ตัวนี้ตาม brief ข้อ 5 |
| หน้าสาธารณะอื่นทั้งหมด (`/mlm-software-omc/` `/features/` `/compensation-plans/…` `/solutions/…` `/about/` `/contact/` `/blog/` landing ฯลฯ) | `refs/home-reference.png` | แนวทาง | สืบทอด header/footer/สี/การ์ด/ปุ่ม/CTA band จากหน้าแรก · เปิดด้วยบล็อก `hero` หรือหัวเพจ + breadcrumb |

## สีที่วัดได้ (`php scripts/design-refs.php --palette` / `--pick`)

| บทบาท | วัดจาก | ค่า | ใช้เป็น |
|---|---|---|---|
| พื้นหน้า | palette อันดับ 1 (52.1%) | `#FCFCFD` | `surface.page` |
| พื้นรอง | palette (3.4%) | `#F5F6FA` | `surface.raised` |
| พื้นอุ่นของส่วนเปิด | palette (1.2%) | `#FDF9F6` | `surface.tint` |
| เส้นขอบการ์ด | palette (0.5%) | `#E4E5EB` | `border.normal` |
| ปุ่มส้ม — ต้น gradient | pick 70,409 | `#FD6B08` | `brand.accent` (+ ฐานของ `primary` ที่ลดความสว่าง) |
| ปุ่มส้ม — ปลาย gradient | pick 200,409 | `#FE9609` | `brand.accentEnd` |
| ส้มของโลโก้ | `logo-omc.png` palette (66.8%) | `#FAA61A` | `brand.logo` |
| เม็ด "OMC MLM Software" | pick 56,85 | `#FDE9DD` | `brand.accentSoft` |
| หมึกพาดหัว | pick 400,675 | `#070A18` | `text.strong` |
| หมึกเนื้อความ | pick 400,708 | `#3C424A` | `text.normal` |
| น้ำเงินเข้มของ CTA band | pick 30,1450 | `#1A243D` | `brand.ink` |

ค่าที่**ไม่ได้วัด แต่คำนวณจากค่าที่วัด**เพื่อให้ผ่าน contrast: `primary` `#C24E00` (จาก `#FD6B08` เฉดเดิม) · `primaryHover` · `link` · `text.muted` `#5A606B` · `border.strong` `#868B96` · สี state · ค่าโหมดมืดทั้งหมด (หลังบ้านเท่านั้น)

ตรวจ contrast ด้วย `php scripts/check-contrast.php` (check.php เรียกให้ทุกครั้ง)

## จุดที่แบบขัดกับกฎ (design.md 5.0) และวิธีจัดการ

**(1) กฎแข็ง — กฎชนะ ปรับให้ใกล้แบบที่สุด**

| # | จุดในแบบ | กฎ | ทำยังไง |
|---|---|---|---|
| 1 | ปุ่มส้มสดตัวหนังสือขาว 2.88:1 | contrast 4.5:1 | พื้นปุ่ม `#C24E00` ขาวบนส้มเข้ม 4.79:1 · ส้มสดย้ายไปอยู่ใน gradient ตกแต่ง |
| 2 | ไอคอนเส้นสีส้มอ่อนบนการ์ดขาว (~2:1) | ไอคอน 3:1 | ไอคอนสี `primary` บนกรอบ `accentSoft` 4.08:1 |
| 3 | เมนูบน เม็ดเบอร์โทร จุดสไลด์ สูง ~30px | พื้นที่กด 44px | ทุกตัวสูงอย่างน้อย 44px (พื้นที่กด) โดยหน้าตาภายนอกยังบาง |
| 4 | หัวเมนู "MLM Software ▾" กางเมื่อชี้เมาส์ | คีย์บอร์ด / a11y | เป็น `<button aria-expanded>` กดหรือ Enter เปิด · Esc ปิด · ลูกศรเดินในเมนู · มือถือเป็น accordion ใน drawer |
| 5 | ไม่มีโหมดมืด | ทุกระบบต้องมีสองโหมด | token มีค่ามืดครบ (หลังบ้านใช้) · เว็บสาธารณะบังคับสว่างตามคำสั่งผู้ใช้ — บันทึกใน `NOTES.md` |
| 6 | ไม่มี footer ในภาพ | ทุกหน้าต้องมี footer | footer พื้น `ink` ใต้ CTA band — โลโก้ (เวอร์ชันพื้นเข้ม) · เกี่ยวกับ · คอลัมน์ลิงก์ · ติดต่อ · นโยบาย · ลิขสิทธิ์ |

**(2) กฎเริ่มต้น — แบบชนะ (ทำตามแบบ)**

| # | จุดในแบบ | กฎเริ่มต้นที่ถูกยกเว้น |
|---|---|---|
| 7 | ส่วนเปิดสองคอลัมน์ ข้อความซ้าย ภาพขวา + แสงพื้นหลังเต็มจอ | 5.4 คอลัมน์เดียว / ความกว้างเดียว (5.16 อนุญาตอยู่แล้ว) |
| 8 | การ์ดฟีเจอร์ 8 ช่องแถวเดียว · industry 6 ช่อง | ขนาดการ์ดขั้นต่ำ `tileMinWidth` — ใช้ grid ของบล็อกเอง |
| 9 | แถบ CTA ไล่สีเต็มความกว้าง | 5.4 (ใช้ `.bleed`) |
| 10 | เมนูแบบแถบบน (`header`) | `layout.nav` = `sidebar` ของหลังบ้าน — เว็บสาธารณะใช้ layout แยก `layouts/site.php` ที่เป็นแถบบนเสมอ (5.15: เว็บสาธารณะ = header) |

**(3) เนื้อหาในแบบที่ brief ห้าม — ซ่อนจนกว่าจะมีของจริง**

| # | จุดในแบบ | ทำยังไง |
|---|---|---|
| 11 | โลโก้ลูกค้า J&C · NUTRIPLUS · BEAUTÉ ฯลฯ | `trust_bar` ไม่มีข้อมูล = ไม่แสดง (QUESTIONS) |
| 12 | 10+ ปี · 200+ · 100,000+ · 99.9% · 24/7 | `stats` ไม่มีข้อมูลยืนยัน = ไม่แสดง |
| 13 | รีวิว "คุณสมชาย วัฒนกุล" | `testimonials` ไม่มีของจริง = ไม่แสดง |
| 14 | ภาพผู้หญิงในส่วนเปิด · ภาพอาคาร OMC | ใช้ mockup แดชบอร์ด HTML/CSS · about ใช้แผงโลโก้แทนภาพอาคารจนกว่าจะได้ภาพจริง |
| 15 | แถบ "AI & Data" | ยังไม่ยืนยันว่าระบบมี AI → ใช้แถบเดียวกันพูดเรื่อง "รายงานและข้อมูล" ที่ไม่อ้างเกินจริง (QUESTIONS) |
| 16 | ภาพถ่ายของ industry 6 ใบ | ยังไม่มีภาพ → การ์ดไอคอนบนพื้นไล่สีอ่อน · ช่อง `image` รอ CMS |

## ความต่างจากแบบ (C4ก — หน้าแรก `/_preview/home` เทียบ `refs/home-reference.png`)

เทียบเมื่อ 2026-11-03 ที่ความกว้าง 1024px (เท่าภาพแบบ) · ภาพหน้าจริง: `project/design/built/home-reference.png` · ตรวจเพิ่มที่ 1440px และ 375px

| # | บล็อกในแบบ | หน้าจริง | ต่างตรงไหน · เพราะอะไร |
|---|---|---|---|
| 1 | header | `partials/site-header.php` | โลโก้ใหม่ (สี่เหลี่ยม O M C) แทน wordmark ในภาพ — brief ข้อ 5 · **1024–1199px เมนูยุบเป็นปุ่ม ☰ (drawer)** แบบแสดงเมนูเต็มที่ 1024 แต่ 7 เมนูไทย + เบอร์ + ปุ่ม ที่พื้นที่กด 44px ไม่พอ — เมนูเต็มตั้งแต่ 1200px · เม็ดเบอร์โทรแสดงตั้งแต่ 1360px |
| 2 | hero | `blocks/hero` | ไม่มีภาพผู้หญิง → mockup แล็ปท็อป + มือถือ HTML/CSS + caption "ข้อมูลสมมติ" · พาดหัวเขียนใหม่ (ตัด "ธุรกิจชั้นนำเลือกใช้" / "ไม่จำกัด") · ปุ่มส้มเข้มทึบแทน gradient ส้มสด (contrast) · จอ < 640px ซ่อนมือถือจำลอง |
| 3 | แถบโลโก้ลูกค้า | `blocks/trust_bar` | ไม่แสดง — ไม่มีโลโก้ที่ยืนยันได้ |
| 4 | แถบตัวเลข 10+ ปี … 24/7 | `blocks/stats` | ไม่แสดง — ไม่มีหลักฐาน |
| 5 | โซลูชันครบ 8 การ์ด | `blocks/feature_grid` (columns 8) | ตรงแบบที่ ≥1100px · 1024px เป็น 4×2 · ไอคอนส้มเข้มบนกรอบส้มอ่อน (contrast) · "ความปลอดภัยระดับองค์กร" → "ความปลอดภัยของข้อมูล" |
| 6 | แถบ AI & Data | `blocks/split` (band, bars) | โครงเดียวกัน (กราฟิก · หัวข้อ · รายการ) · ภาพ "AI" 3D → กราฟแท่ง CSS · เนื้อหาพูดเรื่องข้อมูลและรายงาน ไม่อ้าง AI (รอยืนยัน) |
| 7 | — | `blocks/plan_cards` | **เพิ่ม** — brief ฉบับ 3 ระบุ "Compensation preview" ในหน้าแรก แต่แบบไม่มี |
| 8 | ธุรกิจหลากหลายประเภท 6 การ์ด | `blocks/industry_grid` | ไอคอนบนพื้นไล่สีแทนภาพถ่าย (ยังไม่มีภาพจริง) · 6 คอลัมน์ที่ ≥1100px, 1024px เป็น 3×2 |
| 9 | — | `blocks/dashboard_showcase` | **เพิ่ม** — brief ระบุ "Real system/dashboard" · แท็บ ภาพรวม/สายงาน/คอมมิชชัน |
| 10 | รีวิว + เกี่ยวกับ OMC (คู่กัน) | `blocks/testimonials` + `blocks/about_teaser` | รีวิวไม่แสดง (ไม่มีของจริง) → about ใช้สองคอลัมน์เต็มแถว · แผงโลโก้แทนภาพอาคาร · ตัด "มากกว่า 10 ปี" |
| 11 | แถบ CTA | `blocks/cta_band` | ตรงแบบ · ปลายขวาเป็นส้มเข้มแทนส้มสด (ตัวหนังสือขาวต้องผ่าน 4.5:1) |
| 12 | — (ไม่มีในแบบ) | `partials/site-footer.php` | **เพิ่ม** footer พื้นน้ำเงินหมึก — กฎเหล็กข้อ 20 |

## ลำดับชั้นของสีแบรนด์ (design.md 7.3)

| ที่ | ใช้อะไร |
|---|---|
| ปุ่มหลัก (ขอ Demo ฟรี →) | พื้นทึบ `primary` ตัวหนังสือ `onPrimary` |
| ปุ่มรอง | ขอบ `border.strong` พื้นขาว · ปุ่มรองแบบส้ม (รู้จัก OMC →) ขอบและตัวหนังสือ `primary` |
| เมนูที่อยู่หน้านั้น | ขีดใต้สี `accent` + ตัวหนังสือ `text.strong` |
| เม็ดหัวข้อเล็ก (eyebrow) | พื้น `accentSoft` ตัวหนังสือ `primary` |
| กรอบไอคอนการ์ด | พื้น `accentSoft` ไอคอน `primary` · ชี้แล้วพื้น `primary` ไอคอนขาว |
| เครื่องหมายถูกในรายการ | วง `primary` |
| แถบ CTA ท้ายหน้า | gradient `ink` → `primary` (ทั้งสองปลายผ่าน 4.5:1 กับตัวหนังสือขาว) + แสง `accent` ตกแต่ง |
| ลิงก์ในเนื้อความ | `link` |
| วงแหวน focus | `focus` |

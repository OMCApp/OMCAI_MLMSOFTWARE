---
name: "[ยังไม่ได้ตัดสินใจ]"
version: 0
status: placeholder
mode:
  default: light
color:
  brand:
    primary:    { light: "#3F3F46", dark: "#A1A1AA" }
    onPrimary:  { light: "#FFFFFF", dark: "#18181B" }
  surface:
    page:       { light: "#FFFFFF", dark: "#18181B" }
    card:       { light: "#FAFAFA", dark: "#212126" }
    raised:     { light: "#F4F4F5", dark: "#27272E" }
  text:
    strong:     { light: "#18181B", dark: "#FAFAFA" }
    normal:     { light: "#3F3F46", dark: "#D4D4D8" }
    muted:      { light: "#6B6B74", dark: "#A1A1AA" }
  border:
    normal:     { light: "#E4E4E7", dark: "#3F3F46" }
    strong:     { light: "#8B8B92", dark: "#75757E" }
  state:
    success:    { light: "#3F3F46", dark: "#A1A1AA" }
    warning:    { light: "#3F3F46", dark: "#A1A1AA" }
    danger:     { light: "#3F3F46", dark: "#A1A1AA" }
    info:       { light: "#3F3F46", dark: "#A1A1AA" }
  focus:        { light: "#18181B", dark: "#FAFAFA" }
  overlay:      { light: "rgb(0 0 0 / .45)", dark: "rgb(0 0 0 / .65)" }
typography:
  family:
    heading: "system-ui"
    body:    "system-ui"
    mono:    "ui-monospace"
  scale:      { xs: 0.75, sm: 0.875, base: 1, lg: 1.125, xl: 1.375, 2xl: 1.75, 3xl: 2.25, display: 4 }
  weight:     { normal: 400, medium: 500, bold: 600 }
  lineHeight: { tight: 1.3, normal: 1.6, loose: 1.8 }
space:        { 1: 4px, 2: 8px, 3: 12px, 4: 16px, 5: 20px, 6: 24px, 8: 32px, 10: 40px, 12: 48px, 16: 64px }
radius:       { sm: 4px, md: 6px, lg: 8px, full: 999px }
icon:         { sm: 16px, base: 20px, lg: 32px, xl: 48px }
shadow:
  sm: "0 1px 2px rgba(0,0,0,.05)"
  md: "0 2px 6px rgba(0,0,0,.06)"
  lg: "0 8px 20px rgba(0,0,0,.08)"
motion:
  duration: { fast: 120ms, normal: 200ms, slow: 320ms }
  easing:   { standard: "cubic-bezier(.2,0,0,1)", enter: "cubic-bezier(0,0,0,1)" }
layout:
  density: comfortable
  nav: sidebar
  maxWidth: 1280px
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
    radius:            4px
    typeBorderStyle:   dashed
    statusBorderStyle: solid
  hero:
    minHeight:       560px
    minHeightMobile: 420px
    gap:             48px
    imageRatio:      "3 / 4"
    scrim:           "linear-gradient(to top, rgb(0 0 0 / .72) 0%, rgb(0 0 0 / .45) 42%, rgb(0 0 0 / .08) 100%)"
    onImage:         "#FFFFFF"
  productCard:
    imageRatio:  "3 / 4"
    gap:         16px
    gridGap:     32px
    nameSize:    1.0625rem
    metaSize:    0.9375rem
  prose:
    maxWidth:     68ch
    paragraphGap: 16px
    headingGap:   36px
  newsItem:
    imageRatio:   "16 / 9"
    imageWidth:   320px
    gap:          24px
    dividerStyle: "1px solid var(--color-border-normal)"
---

# ⚠️ ไฟล์นี้ยังไม่ได้ตัดสินใจ — เป็นค่าตั้งต้นของโครงเปล่า

**ค่าทั้งหมดข้างบนเป็นขาวดำและใช้ฟอนต์ของระบบโดยตั้งใจ** เพื่อให้เห็นชัดด้วยตาว่ายังไม่ผ่านเฟสออกแบบ

**พอเริ่ม `/wf` แล้ว (มี `project/STATE.md`) `scripts/check.php` จะ fail ตราบใดที่ `status: placeholder` ยังอยู่ — และนั่นถูกต้องแล้ว**

โครงเปล่าที่ยังไม่เริ่ม `/wf` ไม่ถูกบังคับข้อนี้ จึงผ่านได้ทั้งที่ยังเป็น placeholder

---

## ต้องทำอะไรต่อ

พิมพ์ `/wf` แล้วทำ **ช่วง B** ให้จบ — AI จะถามชุดคำถามดีไซน์ แล้วแปลงคำตอบเป็น token จริง

หรือแก้ไฟล์นี้เองตามขั้นตอนใน [`standard/design.md`](../standard/design.md) แล้วรัน

```
php scripts/design-sync.php
```

**เมื่อตัดสินใจแล้วต้องแก้ 3 อย่างนี้เป็นอย่างน้อย**

1. `name` — ชื่อระบบจริง
2. `status` — เปลี่ยนจาก `placeholder` เป็น `decided`
3. เนื้อหาข้างล่างนี้ — ทิศทาง ลายเซ็น และสิ่งที่จงใจไม่ทำ

---

## ทิศทาง

[2-3 ประโยค — ระบบนี้ควรให้ความรู้สึกยังไง มาจากคำตอบข้อไหนใน BRIEF-DESIGN.md]

## ลายเซ็นของระบบนี้

[1-2 อย่างที่ทำให้ระบบนี้จำได้ — เช่น หัวตารางเป็นแถบสีเข้ม การ์ดมีเส้นซ้ายหนา ปุ่มหลักมุมมนเต็ม]

## สิ่งที่จงใจไม่ทำในโปรเจคนี้

[เช่น ไม่ใช้เงาลึก เพราะระบบใช้บนจอโปรเจกเตอร์ที่ contrast ต่ำ]

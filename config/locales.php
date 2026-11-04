<?php
/**
 * ภาษาของเว็บสาธารณะ — ที่เดียวในระบบ (Router · SEO/hreflang · sitemap · ตัวสลับภาษา · CMS อ่านจากที่นี่)
 *
 * เพิ่มภาษาใหม่ = เพิ่มรายการที่นี่ + lang/<code>.php + เนื้อหา (pages.locale = <code>) — ไม่ต้องแก้โค้ด
 *   ถ้าภาษานั้นใช้ตัวอักษรที่ฟอนต์ self-host ไม่มี (CJK ฯลฯ) ให้เพิ่ม token ฟอนต์ใน project/DESIGN.md
 *   แล้วรัน php scripts/design-sync.php · ชื่อ token ใส่ใน 'font_token' (CSS ใช้ผ่าน :lang())
 *
 * ‼️ ภาษาแรกคือภาษาหลัก — อยู่ที่ root ไม่มี prefix (URL เดิมของเว็บเก่าทุกตัว) และเป็น x-default ของ hreflang
 * ‼️ code คือค่าที่เก็บในฐานข้อมูล (pages.locale) — ห้ามเปลี่ยนเมื่อมีข้อมูลแล้ว
 *
 *   prefix        ส่วนหน้าของ URL ('' = root)
 *   html_lang     <html lang> และ hreflang (BCP 47)
 *   og_locale     og:locale
 *   label         ป้ายบนตัวสลับภาษา (เขียนในภาษานั้นเอง)
 *   name          ชื่อภาษาเต็ม (aria-label ของลิงก์สลับภาษา)
 *   font_token    token ฟอนต์ใน DESIGN.md ที่ :lang() ใช้ — null = ฟอนต์หลัก
 *   preload_fonts ไฟล์ฟอนต์ที่จอแรกใช้จริง (preload) — [] = ไม่ preload (ฟอนต์ระบบ)
 */

declare(strict_types=1);

return [
    'th' => [
        'prefix'        => '',
        'html_lang'     => 'th',
        'og_locale'     => 'th_TH',
        'label'         => 'TH',
        'name'          => 'ภาษาไทย',
        'font_token'    => null,
        /* preload ชุดละตินเพิ่ม (พาดหัว "MLM Software") ลองแล้ว 2026-11-04 — LCP มือถือไม่ดีขึ้น (แย่งแบนด์วิดท์กับ CSS) จึงไม่ใส่ */
        'preload_fonts' => [
            '/assets/vendor/fonts/prompt-thai-600-normal.woff2',
            '/assets/vendor/fonts/ibm-plex-sans-thai-thai-400-normal.woff2',
        ],
    ],
    'en' => [
        'prefix'        => '/en',
        'html_lang'     => 'en',
        'og_locale'     => 'en_US',
        'label'         => 'EN',
        'name'          => 'English',
        'font_token'    => null,
        'preload_fonts' => [
            '/assets/vendor/fonts/prompt-latin-600-normal.woff2',
            '/assets/vendor/fonts/ibm-plex-sans-thai-latin-400-normal.woff2',
        ],
    ],
    'zh' => [
        'prefix'        => '/zh',
        'html_lang'     => 'zh-Hans',
        'og_locale'     => 'zh_CN',
        'label'         => '中文',
        'name'          => '简体中文',
        'font_token'    => 'zh',
        'preload_fonts' => [],
    ],
];

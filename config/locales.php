<?php
/**
 * ภาษาของเนื้อหาเว็บ — ทะเบียนเดียวทั้งระบบ (เว็บสาธารณะ + CMS)
 *
 * ‼️ ภาษาแรกคือภาษาหลัก (อยู่ที่ราก URL ไม่มี prefix)
 * ‼️ ห้ามประกาศรายการภาษาซ้ำที่อื่น — CMS อ่านผ่าน App\Services\TranslationService
 *
 * (Phase 3 สร้างไฟล์ขั้นต่ำนี้ระหว่างรอ Phase 4 — ตอน merge ใช้ของ Phase 4 เป็นหลัก รูปแบบเดียวกัน)
 */

declare(strict_types=1);

return [
    'th' => ['prefix' => '', 'label' => 'ไทย', 'hreflang' => 'th'],
    'en' => ['prefix' => '/en', 'label' => 'English', 'hreflang' => 'en'],
    'zh' => ['prefix' => '/zh', 'label' => '中文', 'hreflang' => 'zh-Hans'],
];

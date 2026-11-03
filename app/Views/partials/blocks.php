<?php
/**
 * วาดรายการบล็อกของหน้าตามลำดับ — ตัวเดียวที่ include ไฟล์ใน partials/blocks/
 *
 * ‼️ $blocks ต้องผ่าน App\Services\BlockService::prepare() มาก่อน (ตัดชนิดที่ไม่รู้จักทิ้งแล้ว)
 *    ที่นี่ยังกรองชื่อไฟล์ซ้ำอีกชั้น เพราะชื่อชนิดมาจาก DB — ห้ามเอาค่าจาก DB ไปประกอบ path ตรงๆ
 *
 * ตัวแปรที่รับ: $blocks — list ของ ['type' => ชนิด, ...ค่าของบล็อกนั้น]
 */

declare(strict_types=1);

foreach ((array) ($blocks ?? []) as $blocksIndex => $block) {
    $blocksType = (string) ($block['type'] ?? '');

    if (preg_match('/^[a-z_]+$/', $blocksType) !== 1 || !is_file(__DIR__ . '/blocks/' . $blocksType . '.php')) {
        continue;
    }

    // id ของ section ไม่ซ้ำกันแม้ชนิดเดียวกันอยู่หลายที่ในหน้า
    $block['_uid'] = $blocksType . '-' . $blocksIndex;

    include __DIR__ . '/blocks/' . $blocksType . '.php';
}

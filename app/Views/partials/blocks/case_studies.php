<?php
/**
 * บล็อก case_studies — ผลงานลูกค้าที่เผยแพร่และได้รับอนุญาตแล้ว (หน้า /portfolio/)
 *
 * $block = [
 *   'eyebrow', 'title' (h2), 'lead'
 *   'items' => BlockService::prepare() เติมจากตาราง case_studies (published + consent) — ห้ามส่งเอง
 * ]
 *
 * ‼️ ไม่มีผลงานที่ได้รับอนุญาต = ไม่วาดอะไร — หน้า /portfolio/ มีข้อความ "กำลังรวบรวม" อยู่แล้ว (ไม่มีของปลอมแทน)
 * ‼️ ใช้การ์ดของ industry_grid (ภาพ/ไอคอน + ชื่อ + บรรทัดรอง) — ไม่สร้างการ์ดชุดใหม่ (กฎเหล็กข้อ 18)
 */

declare(strict_types=1);

$caseBlock = (array) ($block ?? []);

if (array_filter((array) ($caseBlock['items'] ?? []), 'is_array') === []) {
    return;
}

$block = $caseBlock;
include __DIR__ . '/industry_grid.php';

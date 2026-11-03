<?php
/**
 * บล็อก stats — ตัวเลขยืนยันได้ของบริษัท (ตัวนับวิ่งเมื่อเลื่อนมาถึง)
 *
 * $block = [
 *   'items' => list<[
 *     'value'  => int|float   ตัวเลข (ตัวนับวิ่งจาก 0 ถึงค่านี้)
 *     'prefix' => string      เช่น "฿" — ไม่บังคับ
 *     'suffix' => string      เช่น "+" "%" " ปี" — ไม่บังคับ
 *     'label'  => string      คำอธิบายใต้ตัวเลข
 *     'icon'   => string      ชื่อไอคอนจากชุดกลาง — ไม่บังคับ
 *   ]>
 * ]
 *
 * ‼️ ไม่มีรายการ = ไม่วาดอะไรเลย — brief ห้ามตัวเลขที่ยังไม่มีหลักฐาน
 *    CMS ต้องให้ใส่เฉพาะตัวเลขที่ยืนยันแล้ว (settings) — ห้ามใส่ตัวเลขในแบบอ้างอิงจนกว่าผู้ใช้ยืนยัน
 * ‼️ ค่าจริงอยู่ใน HTML เสมอ — ไม่มี JS ก็เห็นตัวเลขครบ (JS แค่ทำให้วิ่ง)
 */

declare(strict_types=1);

$statsBlock = (array) ($block ?? []);
$statsCells = array_values(array_filter(
    (array) ($statsBlock['items'] ?? []),
    static fn ($cell): bool => is_array($cell) && is_numeric($cell['value'] ?? null) && (string) ($cell['label'] ?? '') !== ''
));

if ($statsCells === []) {
    return;
}
?>
<section class="stats-band" aria-label="<?= e((string) ($statsBlock['title'] ?? '')) ?>">
    <div class="container">
        <ul class="stats-grid reveal">
            <?php foreach ($statsCells as $statsCell): ?>
                <li class="stat">
                    <?php if ((string) ($statsCell['icon'] ?? '') !== ''): ?>
                        <span class="stat-icon" aria-hidden="true"><?= icon((string) $statsCell['icon']) ?></span>
                    <?php endif; ?>
                    <span class="stat-body">
                        <span class="stat-value">
                            <?= e((string) ($statsCell['prefix'] ?? '')) ?><span data-count="<?= e((string) $statsCell['value']) ?>"><?= e(format_money((float) $statsCell['value'], fmod((float) $statsCell['value'], 1.0) === 0.0 ? 0 : 1)) ?></span><?= e((string) ($statsCell['suffix'] ?? '')) ?>
                        </span>
                        <span class="stat-label"><?= e((string) $statsCell['label']) ?></span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

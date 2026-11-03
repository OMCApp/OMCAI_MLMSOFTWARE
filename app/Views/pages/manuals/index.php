<?php
/**
 * หน้ารวมคู่มือ — จัดกลุ่มตามโมดูล ไม่ใช่เรียงตามที่นึกออก
 *
 * ‼️ หน้านี้คือ "เลือกทางเข้า" ไม่ใช่ "อ่านรายการ" จึงเป็นการ์ดทางเข้า ไม่ใช่ bullet ลิงก์
 *    รายการลิงก์เปล่าไม่มีลำดับสายตา และไม่บอกว่าบทความไหนเกี่ยวกับอะไร
 *
 * ‼️ ใช้ .tile-grid / .tile ซึ่งเป็น component กลาง ไม่ใช่สไตล์เฉพาะหน้าคู่มือ
 *    manual.md ข้อ 6 ห้ามทำสไตล์แยกเฉพาะหน้าคู่มือ และหน้าสรุปจะใช้ pattern เดียวกันนี้
 */

declare(strict_types=1);
?>
<div class="stack">
    <?php if ($groups === []): ?>
        <div class="card">
            <div class="empty-state">
                <?= icon('book', 'icon-lg') ?>
                <h3><?= e(__('manual.not_found')) ?></h3>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($groups as $groupName => $items): ?>
        <section class="stack">
            <h2><?= e(__('manual.group.' . $groupName)) ?></h2>

            <div class="tile-grid">
                <?php foreach ($items as $item): ?>
                    <a class="tile" href="<?= e(url('/manual/' . $item['slug'])) ?>">
                        <span class="tile-icon"><?= icon((string) ($item['icon'] ?? 'book')) ?></span>
                        <span class="tile-title"><?= e((string) $item['title']) ?></span>
                        <?php if (($item['summary'] ?? '') !== ''): ?>
                            <span class="tile-summary"><?= e((string) $item['summary']) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

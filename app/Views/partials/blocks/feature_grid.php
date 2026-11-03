<?php
/**
 * บล็อก feature_grid — การ์ดไอคอน + หัวข้อ (+ คำอธิบาย) เรียงเป็นตะแกรง
 *
 * $block = [
 *   'eyebrow' => string   — ไม่บังคับ
 *   'title'   => string   หัวข้อของ section (h2)
 *   'lead'    => string   — ไม่บังคับ
 *   'columns' => 3 | 4 | 8   จำนวนคอลัมน์บนจอใหญ่ (ค่าเริ่มต้น 4 · แบบหน้าแรกใช้ 8 = การ์ดเล็กไอคอนกลาง)
 *   'items'   => list<['icon','title','text' => ไม่บังคับ,'url' => ไม่บังคับ]>
 * ]
 *
 * ‼️ การ์ดที่มี url ทั้งใบเป็นลิงก์ (พื้นที่กดใหญ่) · ไม่มี url เป็นการ์ดเฉยๆ
 */

declare(strict_types=1);

$featureBlock   = (array) ($block ?? []);
$featureCards   = array_values(array_filter((array) ($featureBlock['items'] ?? []), 'is_array'));
$featureColumns = in_array((int) ($featureBlock['columns'] ?? 4), [3, 4, 8], true) ? (int) $featureBlock['columns'] : 4;

if ($featureCards === []) {
    return;
}
?>
<section class="section" aria-labelledby="<?= e($featureBlock['_uid'] ?? 'features') ?>-title">
    <div class="container">
        <?php
        $sectionHeadEyebrow = (string) ($featureBlock['eyebrow'] ?? '');
        $sectionHeadTitle   = (string) ($featureBlock['title'] ?? '');
        $sectionHeadLead    = (string) ($featureBlock['lead'] ?? '');
        $sectionHeadId      = ($featureBlock['_uid'] ?? 'features') . '-title';
        include __DIR__ . '/../section-head.php';
        ?>

        <ul class="feature-grid" data-columns="<?= e((string) $featureColumns) ?>">
            <?php foreach ($featureCards as $featureIndex => $featureCard): ?>
                <li class="reveal" data-reveal-delay="<?= e((string) ($featureIndex % 4)) ?>">
                    <?php $featureHasLink = (string) ($featureCard['url'] ?? '') !== ''; ?>
                    <?php if ($featureHasLink): ?>
                        <a class="feature-card" href="<?= e(url((string) $featureCard['url'])) ?>">
                    <?php else: ?>
                        <div class="feature-card">
                    <?php endif; ?>
                        <span class="icon-chip" aria-hidden="true"><?= icon((string) ($featureCard['icon'] ?? 'sparkle')) ?></span>
                        <span class="feature-card-title"><?= e((string) ($featureCard['title'] ?? '')) ?></span>
                        <?php if ((string) ($featureCard['text'] ?? '') !== ''): ?>
                            <span class="feature-card-text"><?= e((string) $featureCard['text']) ?></span>
                        <?php endif; ?>
                    <?php if ($featureHasLink): ?></a><?php else: ?></div><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

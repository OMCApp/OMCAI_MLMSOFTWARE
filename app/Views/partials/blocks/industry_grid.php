<?php
/**
 * บล็อก industry_grid — การ์ดประเภทธุรกิจ (ภาพ + ชื่อไทย + ชื่ออังกฤษ)
 *
 * $block = [
 *   'eyebrow', 'title' (h2), 'lead'
 *   'items' => list<[
 *     'title'    => string  ชื่อประเภทธุรกิจ
 *     'subtitle' => string  บรรทัดรอง เช่น "(Franchise)" — ไม่บังคับ
 *     'url'      => string  หน้า solution — ไม่บังคับ
 *     'icon'     => string  ไอคอนบนพื้นไล่สี ใช้เมื่อไม่มีภาพ
 *     'image'    => ['src','alt','width','height'] — ไม่บังคับ · lazy
 *   ]>
 * ]
 *
 * ‼️ แบบอ้างอิงใช้ภาพถ่ายทุกใบ — ยังไม่มีภาพของจริง (QUESTIONS.md) จึงใช้ไอคอนบนพื้นไล่สีอ่อน
 *    ไม่ใช้กล่องเทา placeholder (design.md 7ก) · ใส่ภาพใน CMS แล้วการ์ดเปลี่ยนเป็นภาพเอง
 */

declare(strict_types=1);

$industryBlock = (array) ($block ?? []);
$industryCards = array_values(array_filter((array) ($industryBlock['items'] ?? []), 'is_array'));

if ($industryCards === []) {
    return;
}
?>
<section class="section" aria-labelledby="<?= e($industryBlock['_uid'] ?? 'industries') ?>-title">
    <div class="container">
        <?php
        $sectionHeadEyebrow = (string) ($industryBlock['eyebrow'] ?? '');
        $sectionHeadTitle   = (string) ($industryBlock['title'] ?? '');
        $sectionHeadLead    = (string) ($industryBlock['lead'] ?? '');
        $sectionHeadId      = ($industryBlock['_uid'] ?? 'industries') . '-title';
        include __DIR__ . '/../section-head.php';
        ?>

        <ul class="industry-grid">
            <?php foreach ($industryCards as $industryIndex => $industryCard): ?>
                <?php
                $industryImage = is_array($industryCard['image'] ?? null) && (string) ($industryCard['image']['src'] ?? '') !== ''
                    ? $industryCard['image'] : null;
                $industryTag = (string) ($industryCard['url'] ?? '') !== '' ? 'a' : 'div';
                ?>
                <li class="reveal" data-reveal-delay="<?= e((string) ($industryIndex % 4)) ?>">
                    <<?= e($industryTag) ?> class="industry-card"<?php if ($industryTag === 'a'): ?> href="<?= e(url((string) $industryCard['url'])) ?>"<?php endif; ?>>
                        <span class="industry-media" data-tone="<?= e((string) ($industryIndex % 6)) ?>">
                            <?php if ($industryImage !== null): ?>
                                <img src="<?= e(asset((string) $industryImage['src'])) ?>" alt="<?= e((string) ($industryImage['alt'] ?? '')) ?>"
                                     width="<?= e((string) (int) ($industryImage['width'] ?? 480)) ?>" height="<?= e((string) (int) ($industryImage['height'] ?? 300)) ?>"
                                     loading="lazy" decoding="async">
                            <?php else: ?>
                                <span class="industry-icon" aria-hidden="true"><?= icon((string) ($industryCard['icon'] ?? 'grid'), 'icon-xl') ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="industry-body">
                            <span class="industry-title"><?= e((string) ($industryCard['title'] ?? '')) ?></span>
                            <?php if ((string) ($industryCard['subtitle'] ?? '') !== ''): ?>
                                <span class="industry-subtitle"><?= e((string) $industryCard['subtitle']) ?></span>
                            <?php endif; ?>
                        </span>
                    </<?= e($industryTag) ?>>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

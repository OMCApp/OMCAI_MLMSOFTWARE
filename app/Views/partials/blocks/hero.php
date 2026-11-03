<?php
/**
 * บล็อก hero — ส่วนเปิดของหน้าสาธารณะ (พาดหัว h1 + รายการข้อดี + ปุ่ม + ภาพ/mockup)
 *
 * $block = [
 *   'eyebrow' => string        เม็ดข้อความเล็กเหนือพาดหัว — ไม่บังคับ
 *   'title'   => string        พาดหัว (h1) — "\n" = ขึ้นบรรทัดใหม่ · บังคับ
 *   'lead'    => string        ย่อหน้านำ — ไม่บังคับ
 *   'bullets' => list<string>  รายการข้อดีพร้อมเครื่องหมายถูก — ไม่บังคับ
 *   'actions' => list<['label','url','style' => primary|secondary|outline]>
 *   'image'   => ['src','alt','width','height'] | null
 *                ‣ มีภาพ = ใช้ภาพแทน mockup (ภาพทีม/ระบบจริงจาก CMS) · ‼️ ภาพนี้คือ LCP — ห้าม lazy
 *                ‣ ไม่มี = mockup แล็ปท็อป + มือถือ (partials/dashboard-mock.php) พร้อม caption ข้อมูลสมมติ
 * ]
 *
 * ‼️ มี h1 ได้ตัวเดียวต่อหน้า — เรียก hero_rendered(true) ให้ page-head.php ไม่วาดซ้ำ
 *    และ page_public(true) ทำเครื่องหมายหน้าสาธารณะ (design.md 5.14 / 5.15ก)
 */

declare(strict_types=1);

hero_rendered(true);
page_public(true);

$heroBlock   = (array) ($block ?? []);
$heroLines   = preg_split('/\R/', (string) ($heroBlock['title'] ?? '')) ?: [];
$heroImage   = is_array($heroBlock['image'] ?? null) && (string) ($heroBlock['image']['src'] ?? '') !== ''
    ? $heroBlock['image'] : null;
?>
<section class="hero-site" aria-labelledby="<?= e($heroBlock['_uid'] ?? 'hero') ?>-title">
    <div class="hero-site-glow" aria-hidden="true" data-parallax="0.06"></div>
    <div class="container hero-site-inner">
        <div class="hero-site-text">
            <?php if ((string) ($heroBlock['eyebrow'] ?? '') !== ''): ?>
                <p class="eyebrow-pill"><?= e((string) $heroBlock['eyebrow']) ?></p>
            <?php endif; ?>

            <h1 class="hero-site-title" id="<?= e($heroBlock['_uid'] ?? 'hero') ?>-title">
                <?php foreach ($heroLines as $heroLineIndex => $heroLine): ?>
                    <?php if ($heroLineIndex > 0): ?><br><?php endif; ?><?= e($heroLine) ?>
                <?php endforeach; ?>
            </h1>

            <?php if ((string) ($heroBlock['lead'] ?? '') !== ''): ?>
                <p class="hero-site-lead"><?= e((string) $heroBlock['lead']) ?></p>
            <?php endif; ?>

            <?php $checkListItems = (array) ($heroBlock['bullets'] ?? []); include __DIR__ . '/../check-list.php'; ?>

            <?php if (!empty($heroBlock['actions'])): ?>
                <div class="hero-site-actions">
                    <?php foreach ((array) $heroBlock['actions'] as $heroAction): ?>
                        <?php $siteButton = (array) $heroAction + ['size' => 'lg']; include __DIR__ . '/../site-button.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <figure class="hero-site-visual">
            <?php if ($heroImage !== null): ?>
                <img class="hero-site-image"
                     src="<?= e(asset((string) $heroImage['src'])) ?>"
                     alt="<?= e((string) ($heroImage['alt'] ?? '')) ?>"
                     width="<?= e((string) (int) ($heroImage['width'] ?? 1200)) ?>"
                     height="<?= e((string) (int) ($heroImage['height'] ?? 900)) ?>"
                     fetchpriority="high" decoding="async">
            <?php else: ?>
                <?php $dashboardMockFrame = 'laptop'; $dashboardMockView = 'overview'; include __DIR__ . '/../dashboard-mock.php'; ?>
                <figcaption class="mock-caption"><?= e(__('showcase.caption')) ?></figcaption>
            <?php endif; ?>
        </figure>
    </div>
</section>

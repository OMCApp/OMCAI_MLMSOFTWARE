<?php
/**
 * บล็อก dashboard_showcase — mockup แดชบอร์ด MLM (KPI · กราฟ · ผังสายงาน · คอมมิชชัน) พร้อมแท็บ
 *
 * $block = [
 *   'eyebrow', 'title' (h2), 'lead'
 *   'bullets' => list<string>   — ไม่บังคับ
 *   'action'  => ['label','url'] — ไม่บังคับ
 * ]
 *
 * ‼️ ไม่ต้องมีภาพหน้าจอ — mockup มาจาก partials/dashboard-mock.php (HTML/CSS) พร้อม caption "ข้อมูลสมมติ"
 * ‼️ แท็บทำงานได้เพราะ JS จึงถูกซ่อนไว้ (hidden) จนกว่า app.js จะเปิด — ไม่มี JS เห็นทั้งสามหน้าจอเรียงกัน
 *    (design.md 5.11 ของที่ทำงานได้เพราะ JS ต้องถูกสร้าง/เปิดโดย JS)
 */

declare(strict_types=1);

$showcaseBlock = (array) ($block ?? []);
$showcaseUid   = (string) ($showcaseBlock['_uid'] ?? 'showcase');
$showcaseViews = [
    'overview'   => __('showcase.tab_overview'),
    'team'       => __('showcase.tab_team'),
    'commission' => __('showcase.tab_commission'),
];
?>
<section class="section showcase" data-tone="tint" aria-labelledby="<?= e($showcaseUid) ?>-title">
    <div class="container showcase-inner">
        <div class="showcase-text reveal">
            <?php if ((string) ($showcaseBlock['eyebrow'] ?? '') !== ''): ?>
                <p class="eyebrow-pill"><?= e((string) $showcaseBlock['eyebrow']) ?></p>
            <?php endif; ?>
            <h2 class="section-title" id="<?= e($showcaseUid) ?>-title"><?= e((string) ($showcaseBlock['title'] ?? '')) ?></h2>
            <?php if ((string) ($showcaseBlock['lead'] ?? '') !== ''): ?>
                <p class="section-lead"><?= e((string) $showcaseBlock['lead']) ?></p>
            <?php endif; ?>
            <?php $checkListItems = (array) ($showcaseBlock['bullets'] ?? []); include __DIR__ . '/../check-list.php'; ?>
            <?php if (is_array($showcaseBlock['action'] ?? null)): ?>
                <div class="block-actions">
                    <?php $siteButton = (array) $showcaseBlock['action'] + ['style' => 'outline']; include __DIR__ . '/../site-button.php'; ?>
                </div>
            <?php endif; ?>
        </div>

        <figure class="showcase-stage reveal" data-tabs>
            <div class="tabs-list" role="tablist" aria-label="<?= e((string) ($showcaseBlock['title'] ?? '')) ?>" hidden>
                <?php foreach ($showcaseViews as $showcaseKey => $showcaseLabel): ?>
                    <button type="button" class="tab" role="tab" id="<?= e($showcaseUid . '-tab-' . $showcaseKey) ?>"
                            aria-controls="<?= e($showcaseUid . '-panel-' . $showcaseKey) ?>"
                            aria-selected="<?= e($showcaseKey === 'overview' ? 'true' : 'false') ?>"
                            tabindex="<?= e($showcaseKey === 'overview' ? '0' : '-1') ?>"><?= e($showcaseLabel) ?></button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($showcaseViews as $showcaseKey => $showcaseLabel): ?>
                <div class="tab-panel" role="tabpanel" id="<?= e($showcaseUid . '-panel-' . $showcaseKey) ?>"
                     aria-labelledby="<?= e($showcaseUid . '-tab-' . $showcaseKey) ?>">
                    <?php $dashboardMockFrame = 'panel'; $dashboardMockView = $showcaseKey; include __DIR__ . '/../dashboard-mock.php'; ?>
                </div>
            <?php endforeach; ?>

            <figcaption class="mock-caption"><?= e(__('showcase.caption')) ?></figcaption>
        </figure>
    </div>
</section>

<?php
/**
 * บล็อก plan_cards — การ์ดแผนรายได้ (Binary · Unilevel · Matrix · Board · Stair Step · Hybrid)
 *
 * $block = [
 *   'eyebrow', 'title' (h2), 'lead'
 *   'plans'  => list<[
 *     'key'  => 'binary'|'unilevel'|'matrix'|'board'|'stair'|'stair_step'|'hybrid'   เลือกแผนภาพของแผน
 *     'name' => string   ชื่อแผน
 *     'text' => string   อธิบายสั้น
 *     'url'  => string   หน้าของแผน — ไม่บังคับ
 *   ]>
 *   'action' => ['label','url'] ลิงก์ไปหน้ารวม — ไม่บังคับ
 * ]
 *
 * ‼️ แสดงเฉพาะแผนที่ระบบรองรับจริง — Board / Stair Step ยังรอยืนยัน (QUESTIONS.md)
 */

declare(strict_types=1);

$planBlock = (array) ($block ?? []);
$planCards = array_values(array_filter((array) ($planBlock['plans'] ?? []), 'is_array'));
$planIcons = [
    'binary' => 'plan-binary', 'unilevel' => 'plan-unilevel', 'matrix' => 'plan-matrix',
    'board' => 'plan-board', 'stair' => 'plan-stair', 'stair_step' => 'plan-stair', 'hybrid' => 'plan-hybrid',
];

if ($planCards === []) {
    return;
}
?>
<section class="section" data-tone="raised" aria-labelledby="<?= e($planBlock['_uid'] ?? 'plans') ?>-title">
    <div class="container">
        <?php
        $sectionHeadEyebrow = (string) ($planBlock['eyebrow'] ?? '');
        $sectionHeadTitle   = (string) ($planBlock['title'] ?? '');
        $sectionHeadLead    = (string) ($planBlock['lead'] ?? '');
        $sectionHeadId      = ($planBlock['_uid'] ?? 'plans') . '-title';
        include __DIR__ . '/../section-head.php';
        ?>

        <ul class="plan-grid">
            <?php foreach ($planCards as $planIndex => $planCard): ?>
                <li class="plan-card reveal" data-reveal-delay="<?= e((string) ($planIndex % 4)) ?>">
                    <span class="plan-card-art" aria-hidden="true">
                        <?= icon($planIcons[(string) ($planCard['key'] ?? '')] ?? 'network', 'icon-xl') ?>
                    </span>
                    <h3 class="plan-card-name"><?= e((string) ($planCard['name'] ?? '')) ?></h3>
                    <p class="plan-card-text"><?= e((string) ($planCard['text'] ?? '')) ?></p>
                    <?php if ((string) ($planCard['url'] ?? '') !== ''): ?>
                        <a class="text-link stretched" href="<?= e(url((string) $planCard['url'])) ?>">
                            <?= e(__('site.view_plan', ['name' => (string) ($planCard['name'] ?? '')])) ?>
                            <?= icon('arrow-right', 'icon-sm') ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (is_array($planBlock['action'] ?? null)): ?>
            <div class="block-actions is-center">
                <?php $siteButton = (array) $planBlock['action'] + ['style' => 'outline']; include __DIR__ . '/../site-button.php'; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

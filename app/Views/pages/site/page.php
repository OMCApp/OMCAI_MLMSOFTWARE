<?php
/**
 * หน้าจากตาราง pages — วาดบล็อกตามลำดับ
 *
 * ‼️ DB เก็บบล็อกเป็น {type, data} แต่ partial ของบล็อก (Phase 1) อ่านค่าแบบแบน ['type' => ..., ...ค่าของบล็อก]
 *    แปลงที่นี่จุดเดียว แล้วส่งให้ partials/blocks.php ซึ่งเป็นตัวเดียวที่ include ไฟล์บล็อก
 *    BlockService::prepare() ตัดชนิดที่ไม่รู้จักและเติมตัวเลือกของฟอร์ม lead
 * ‼️ H1 มาจาก layout (page-head) หรือจากบล็อก hero — บล็อกอื่นห้ามวาด H1
 */

declare(strict_types=1);

$siteBlocks  = (array) ($siteBlocks ?? []);
$sitePreview = (bool) ($sitePreview ?? false);

$blocks = \App\Services\BlockService::prepare(array_map(
    static fn (array $siteBlock): array => ['type' => (string) $siteBlock['type']] + (array) $siteBlock['data'],
    $siteBlocks
));
?>
<?php if ($sitePreview): ?>
    <p class="card small" role="status"><?= e(__('site.preview_notice')) ?></p>
<?php endif; ?>

<?php include __DIR__ . '/../../partials/blocks.php'; ?>

<?php if ($blocks === [] && $sitePreview): ?>
    <p class="muted"><?= e(__('site.preview_empty')) ?></p>
<?php endif; ?>

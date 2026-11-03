<?php
/**
 * หน้าจากตาราง pages — วาดบล็อกตามลำดับ
 *
 * ‼️ บล็อกแต่ละชนิดมี partial ของตัวเองใน partials/blocks/ (Phase 1)
 *    ชนิดที่ยังไม่มี partial ถูกข้ามเงียบๆ — SiteViewService หาไฟล์ให้แล้วใน $siteBlock['partial']
 *    partial ของบล็อกอ่านข้อมูลจาก $block = ['type' => string, 'data' => array]
 * ‼️ H1 มาจาก layout (page-head) หรือจากบล็อก hero — บล็อกอื่นห้ามวาด H1
 */

declare(strict_types=1);

$siteBlocks  = (array) ($siteBlocks ?? []);
$sitePreview = (bool) ($sitePreview ?? false);
?>
<?php if ($sitePreview): ?>
    <p class="card small" role="status"><?= e(__('site.preview_notice')) ?></p>
<?php endif; ?>

<?php foreach ($siteBlocks as $siteBlock): ?>
    <?php
    if ($siteBlock['partial'] === null) {
        continue;
    }

    $block = ['type' => $siteBlock['type'], 'data' => $siteBlock['data']];
    include $siteBlock['partial'];
    ?>
<?php endforeach; ?>

<?php if ($siteBlocks === [] && $sitePreview): ?>
    <p class="muted"><?= e(__('site.preview_empty')) ?></p>
<?php endif; ?>

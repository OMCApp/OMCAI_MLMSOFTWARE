<?php
/**
 * ค่าตั้งของเว็บ (admin)
 *
 * ‼️ ตัวเลข (stats) และโลโก้ลูกค้า (trust_logos) แสดงบนเว็บเฉพาะแถวที่ติ๊ก "ยืนยันแล้ว"
 *    ใส่ตัวเลขเก็บไว้ก่อนได้ — ไม่ติ๊กก็ไม่ขึ้นเว็บ (BRIEF ห้ามตัวเลข/โลโก้ที่ไม่มีหลักฐาน)
 * ‼️ แถวว่างเผื่อเพิ่มหนึ่งแถวเสมอ (ไม่มี JS ก็เพิ่มได้) · cms.js เพิ่มแถวจาก <template> ได้
 */

declare(strict_types=1);

$settingsStats   = array_values((array) ($settingsStats ?? []));
$settingsLogos   = array_values((array) ($settingsLogos ?? []));
$settingsStats[] = [];
$settingsLogos[] = [];

$settingsStatRow = static function (string $index, array $row): void {
    foreach ([['value', 'cms.settings.stat_value', 'short'], ['prefix', 'cms.settings.stat_prefix', 'short'],
              ['suffix', 'cms.settings.stat_suffix', 'short'], ['label', 'cms.settings.stat_label', 'medium'],
              ['icon', 'cms.menus.icon', 'short']] as [$key, $label, $width]) {
        $fieldName = 'stats[' . $index . '][' . $key . ']'; $fieldLabel = __($label); $fieldType = 'text';
        $fieldValue = (string) ($row[$key] ?? ''); $fieldWidth = $width;
        include __DIR__ . '/../../partials/field.php';
    }
    $fieldName = 'stats[' . $index . '][verified]'; $fieldLabel = __('cms.settings.verified'); $fieldType = 'checkbox';
    $fieldValue = !empty($row['verified']); $fieldHint = __('cms.settings.verified_hint');
    include __DIR__ . '/../../partials/field.php';
};

$settingsLogoRow = static function (string $index, array $row, array $media): void {
    $fieldName = 'trust_logos[' . $index . '][media_id]'; $fieldLabel = __('cms.settings.logo'); $fieldType = 'select';
    $fieldOptions = ['' => __('cms.media.none')] + $media; $fieldValue = (string) ($row['media_id'] ?? '');
    include __DIR__ . '/../../partials/field.php';
    $fieldName = 'trust_logos[' . $index . '][url]'; $fieldLabel = __('cms.block_field.url'); $fieldType = 'text';
    $fieldValue = (string) ($row['url'] ?? '');
    include __DIR__ . '/../../partials/field.php';
    $fieldName = 'trust_logos[' . $index . '][verified]'; $fieldLabel = __('cms.settings.logo_verified'); $fieldType = 'checkbox';
    $fieldValue = !empty($row['verified']);
    include __DIR__ . '/../../partials/field.php';
};
?>
<form class="stack" method="POST" action="<?= e(url('/admin/settings')) ?>">
    <?= csrf_field() ?>
    <?= method_field('PATCH') ?>

    <fieldset class="card group stack">
        <legend><?= e(__('cms.settings.company')) ?></legend>
        <div class="field-grid">
            <?php foreach ((array) $settingsCompany as $cmsField): ?>
                <?php include __DIR__ . '/../../partials/cms/form-field.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php foreach ((array) $settingsLocalized as $cmsField): ?>
            <?php include __DIR__ . '/../../partials/cms/form-field.php'; ?>
        <?php endforeach; ?>
    </fieldset>

    <fieldset class="card group stack cms-rows" data-rows>
        <legend><?= e(__('cms.settings.stats')) ?></legend>
        <p class="hint"><?= e(__('cms.settings.stats_hint')) ?></p>
        <?php if (field_error('stats') !== null): ?><p class="error"><?= e((string) field_error('stats')) ?></p><?php endif; ?>
        <?php foreach ($settingsStats as $settingsIndex => $settingsRow): ?>
            <div class="cms-row field-grid" data-row><?php $settingsStatRow((string) $settingsIndex, (array) $settingsRow); ?></div>
        <?php endforeach; ?>
        <template data-row-template><div class="cms-row field-grid" data-row><?php $settingsStatRow('__ROW__', []); ?></div></template>
    </fieldset>

    <fieldset class="card group stack cms-rows" data-rows>
        <legend><?= e(__('cms.settings.logos')) ?></legend>
        <p class="hint"><?= e(__('cms.settings.logos_hint')) ?></p>
        <?php foreach ($settingsLogos as $settingsIndex => $settingsRow): ?>
            <div class="cms-row field-grid" data-row><?php $settingsLogoRow((string) $settingsIndex, (array) $settingsRow, (array) $cmsMediaOptions); ?></div>
        <?php endforeach; ?>
        <template data-row-template><div class="cms-row field-grid" data-row><?php $settingsLogoRow('__ROW__', [], (array) $cmsMediaOptions); ?></div></template>
    </fieldset>

    <fieldset class="card group stack">
        <legend><?= e(__('cms.settings.other')) ?></legend>
        <?php foreach ((array) $settingsOther as $cmsField): ?>
            <?php include __DIR__ . '/../../partials/cms/form-field.php'; ?>
        <?php endforeach; ?>
    </fieldset>

    <div class="row">
        <button type="submit" class="btn btn-primary" data-submit-once><?= icon('check') ?><?= e(__('common.save')) ?></button>
    </div>
</form>

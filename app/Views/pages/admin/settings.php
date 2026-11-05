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

$settingsLangs   = (array) ($settingsLocales ?? []);
$settingsStatRow = static function (string $index, array $row) use ($settingsLangs): void {
    foreach ([['value', 'cms.settings.stat_value', 'short', 'cms.settings.stat_value_hint'], ['prefix', 'cms.settings.stat_prefix', 'short', ''],
              ['icon', 'cms.menus.icon', 'short', '']] as [$key, $label, $width, $hint]) {
        $fieldName = 'stats[' . $index . '][' . $key . ']'; $fieldLabel = __($label); $fieldType = 'text';
        $fieldValue = (string) ($row[$key] ?? ''); $fieldWidth = $width; $fieldHint = $hint === '' ? '' : __($hint);
        include __DIR__ . '/../../partials/field.php';
    }
    // ป้ายและหน่วยแยกตามภาษา (ค่าเดิมแบบเดี่ยว label/suffix = ภาษาหลัก)
    foreach ($settingsLangs as $settingsCode => $settingsLangName) {
        $fieldName = 'stats[' . $index . '][labels][' . $settingsCode . ']'; $fieldLabel = __('cms.settings.stat_label') . ' · ' . $settingsLangName;
        $fieldType = 'text'; $fieldWidth = 'medium';
        $fieldValue = (string) ($row['labels'][$settingsCode] ?? ($settingsCode === array_key_first($settingsLangs) ? ($row['label'] ?? '') : ''));
        include __DIR__ . '/../../partials/field.php';
        $fieldName = 'stats[' . $index . '][suffixes][' . $settingsCode . ']'; $fieldLabel = __('cms.settings.stat_suffix') . ' · ' . $settingsLangName;
        $fieldType = 'text'; $fieldWidth = 'short';
        $fieldValue = (string) ($row['suffixes'][$settingsCode] ?? ($settingsCode === array_key_first($settingsLangs) ? ($row['suffix'] ?? '') : ''));
        include __DIR__ . '/../../partials/field.php';
    }
    $fieldName = 'stats[' . $index . '][plain]'; $fieldLabel = __('cms.settings.stat_plain'); $fieldType = 'checkbox';
    $fieldValue = ($row['format'] ?? '') === 'plain';
    include __DIR__ . '/../../partials/field.php';
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

<?php
/* อีเมลแจ้ง lead — ค่าเซิร์ฟเวอร์อีเมลอยู่ใน .env (secret ห้ามเก็บใน CMS) · ที่นี่แสดงสถานะ + ส่งอีเมลทดสอบ
   ‼️ ฟอร์มแยกจากฟอร์มบันทึกด้านบน (ฟอร์มซ้อนกันไม่ได้) */
$settingsMail = (array) ($settingsMail ?? []);
?>
<form id="mail" class="card group stack" method="POST" action="<?= e(url('/admin/settings/test-mail')) ?>">
    <?= csrf_field() ?>
    <h2><?= e(__('cms.settings.mail_title')) ?></h2>
    <dl class="cms-dl">
        <dt><?= e(__('cms.settings.mail_status')) ?></dt>
        <dd><span class="badge"<?php if (empty($settingsMail['configured'])): ?> data-type="warning"<?php endif; ?>><?= e(__(!empty($settingsMail['configured']) ? 'cms.settings.mail_on' : 'cms.settings.mail_off')) ?></span></dd>
        <dt><?= e(__('cms.settings.mail_driver')) ?></dt>
        <dd><?= e(strtoupper((string) ($settingsMail['driver'] ?? ''))) ?><?php if ((string) ($settingsMail['host'] ?? '') !== ''): ?> · <?= e((string) $settingsMail['host']) ?><?php endif; ?></dd>
        <dt><?= e(__('cms.settings.mail_from')) ?></dt>
        <dd><?= e((string) ($settingsMail['from'] ?? '') !== '' ? (string) $settingsMail['from'] : '—') ?></dd>
        <dt><?= e(__('cms.settings.lead_email')) ?></dt>
        <dd><?= e((string) ($settingsMail['recipient'] ?? '')) ?></dd>
    </dl>
    <p class="hint"><?= e(__('cms.settings.mail_hint')) ?></p>
    <?php
    $fieldName = 'to'; $fieldLabel = __('cms.settings.mail_test_to'); $fieldType = 'email'; $fieldValue = '';
    $fieldHint = __('cms.settings.mail_test_to_hint'); $fieldWidth = 'medium';
    include __DIR__ . '/../../partials/field.php';
    ?>
    <div class="row">
        <button type="submit" class="btn btn-secondary" data-submit-once><?= icon('mail') ?><?= e(__('cms.settings.mail_test')) ?></button>
    </div>
</form>

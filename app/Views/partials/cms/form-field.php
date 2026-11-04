<?php
/**
 * ช่องของฟอร์ม CMS จากนิยามข้อมูล — ส่งต่อให้ partials/field.php (ของกลาง) ทุกชนิดที่มันรองรับ
 *
 * ตัวแปรที่รับ: $cmsField = [
 *   'name', 'label', 'type' (text|textarea|select|checkbox|number|date|datetime-local|email|url|password|media|html),
 *   'value', 'options', 'required', 'hint', 'width', 'rows',
 *   'counter' => int  ตัวนับตัวอักษร (เตือน ไม่บังคับ) — ช่อง SEO title/description
 * ]
 *   $cmsMediaOptions  id => ป้าย ของคลังสื่อ (สำหรับชนิด media)
 *
 * ‼️ validate ไม่ผ่าน → ใช้ค่าที่กรอกไว้ (old input) แทนค่าจากฐานข้อมูล · ชื่อแบบ a[b][c] ไล่หาในก้อน old ให้
 * ‼️ ชื่อตัวแปรขึ้นต้นด้วย cms เสมอ (components.md 1ก — include ใช้ขอบเขตร่วมกัน)
 */

declare(strict_types=1);

$cmsField = (array) ($cmsField ?? []);
$cmsName  = (string) ($cmsField['name'] ?? '');
$cmsType  = (string) ($cmsField['type'] ?? 'text');
$cmsValue = $cmsField['value'] ?? '';

if (has_errors() && $cmsType !== 'password') {
    $cmsParts = preg_split('/[\[\]]+/', $cmsName, -1, PREG_SPLIT_NO_EMPTY) ?: [$cmsName];
    $cmsOld   = old((string) array_shift($cmsParts), null);

    foreach ($cmsParts as $cmsPart) {
        $cmsOld = is_array($cmsOld) ? ($cmsOld[$cmsPart] ?? null) : null;
    }

    $cmsValue = $cmsOld ?? ($cmsType === 'checkbox' ? '' : $cmsValue);
}

$cmsHint = (string) ($cmsField['hint'] ?? '');
?>
<?php if ($cmsType === 'html'): ?>
    <?php
    $cmsEditorName  = $cmsName;
    $cmsEditorLabel = (string) ($cmsField['label'] ?? '');
    $cmsEditorValue = (string) $cmsValue;
    $cmsEditorHint  = $cmsHint;
    include __DIR__ . '/html-editor.php';
    ?>
<?php elseif (isset($cmsField['counter'])): ?>
    <?php $cmsCounterId = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $cmsName); ?>
    <div class="field" data-width="full"<?php if (field_error($cmsName) !== null): ?> data-invalid="true"<?php endif; ?>>
        <label for="<?= e($cmsCounterId) ?>"><?= e((string) $cmsField['label']) ?></label>
        <?php if ($cmsHint !== ''): ?><span class="hint" id="<?= e($cmsCounterId) ?>-hint"><?= e($cmsHint) ?></span><?php endif; ?>
        <?php if ($cmsType === 'textarea'): ?>
            <textarea id="<?= e($cmsCounterId) ?>" name="<?= e($cmsName) ?>" rows="3" data-counter="<?= e((string) $cmsField['counter']) ?>"
                      <?php if (isset($cmsField['preview'])): ?>data-snippet="<?= e((string) $cmsField['preview']) ?>"<?php endif; ?>
                      aria-describedby="<?= e($cmsCounterId) ?>-count"><?= e((string) $cmsValue) ?></textarea>
        <?php else: ?>
            <input id="<?= e($cmsCounterId) ?>" name="<?= e($cmsName) ?>" type="text" value="<?= e((string) $cmsValue) ?>"
                   data-counter="<?= e((string) $cmsField['counter']) ?>"
                   <?php if (isset($cmsField['preview'])): ?>data-snippet="<?= e((string) $cmsField['preview']) ?>"<?php endif; ?>
                   aria-describedby="<?= e($cmsCounterId) ?>-count">
        <?php endif; ?>
        <span class="hint counter" id="<?= e($cmsCounterId) ?>-count" aria-live="polite">
            <?= e(__('cms.seo.chars', ['n' => (string) mb_strlen((string) $cmsValue), 'max' => (string) $cmsField['counter']])) ?>
        </span>
        <?php if (field_error($cmsName) !== null): ?><span class="error"><?= e((string) field_error($cmsName)) ?></span><?php endif; ?>
    </div>
<?php else: ?>
    <?php
    $fieldName     = $cmsName;
    $fieldLabel    = (string) ($cmsField['label'] ?? $cmsName);
    $fieldType     = $cmsType === 'media' ? 'select' : $cmsType;
    $fieldRequired = (bool) ($cmsField['required'] ?? false);
    $fieldHint     = $cmsHint;
    $fieldWidth    = (string) ($cmsField['width'] ?? 'full');
    $fieldRows     = (int) ($cmsField['rows'] ?? 4);
    $fieldOptions  = $cmsType === 'media'
        ? ['' => __('cms.media.none')] + (array) ($cmsMediaOptions ?? [])
        : (array) ($cmsField['options'] ?? []);
    $fieldValue    = $cmsType === 'checkbox' ? !empty($cmsValue) : (is_array($cmsValue) ? '' : (string) $cmsValue);
    include __DIR__ . '/../field.php';
    $fieldRequired = false;
    $fieldRows     = 4;
    ?>
<?php endif; ?>
<?php
$cmsField = [];

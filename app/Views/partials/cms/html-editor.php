<?php
/**
 * ตัวแก้ HTML ของบทความ — textarea + แถบเครื่องมือเล็ก (h2 h3 p ul ol strong em ลิงก์ ตาราง รูปจากคลัง)
 *
 * ‼️ ไม่มี dependency ใหม่ (กฎเหล็กข้อ 2) — แถบเครื่องมือสร้างโดย cms.js จาก data-html-editor
 *    ไม่มี JS = textarea ธรรมดาที่พิมพ์ HTML ได้ (design.md 5.11)
 * ‼️ ไม่มีปุ่ม H1 — H1 มาจาก template ตัวเดียว · HtmlSanitizer ลด h1 เป็น h2 ตอนบันทึกอีกชั้น
 * ‼️ ปุ่มรูปใช้รายการจาก $cmsMediaOptions (id => ป้าย) + $cmsMediaData (id => src/alt/width/height) ที่ Controller ส่งมา
 *
 * ตัวแปรที่รับ: $cmsEditorName $cmsEditorLabel $cmsEditorValue $cmsEditorHint
 */

declare(strict_types=1);

$cmsEditorId    = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', (string) $cmsEditorName);
$cmsEditorImages = [];

foreach ((array) ($cmsMediaData ?? []) as $cmsEditorMediaId => $cmsEditorMedia) {
    $cmsEditorImages[] = ['id' => $cmsEditorMediaId] + (array) $cmsEditorMedia;
}
?>
<div class="field" data-width="full"<?php if (field_error((string) $cmsEditorName) !== null): ?> data-invalid="true"<?php endif; ?>>
    <label for="<?= e($cmsEditorId) ?>"><?= e((string) $cmsEditorLabel) ?></label>
    <?php if ((string) ($cmsEditorHint ?? '') !== ''): ?>
        <span class="hint"><?= e((string) $cmsEditorHint) ?></span>
    <?php endif; ?>
    <textarea id="<?= e($cmsEditorId) ?>" name="<?= e((string) $cmsEditorName) ?>" rows="18" class="code-input"
              data-html-editor
              data-images="<?= e((string) json_encode($cmsEditorImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
              data-base="<?= e(url('/')) ?>"><?= e((string) $cmsEditorValue) ?></textarea>
    <?php if (field_error((string) $cmsEditorName) !== null): ?>
        <span class="error"><?= e((string) field_error((string) $cmsEditorName)) ?></span>
    <?php endif; ?>
</div>

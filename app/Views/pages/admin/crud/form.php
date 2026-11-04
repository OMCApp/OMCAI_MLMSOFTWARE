<?php
/**
 * ฟอร์มเพิ่ม/แก้ไขของ CMS — ตัวเดียวทุก resource
 *
 * ตัวแปร (Controller ส่งมา):
 *   $formAction    URL ปลายทาง (path ภายใน)
 *   $formMethod    POST | PATCH
 *   $formSections  [['title' => ?, 'hint' => ?, 'fields' => [นิยามของ partials/cms/form-field.php], 'grid' => bool]]
 *   $formPanels    ไฟล์ partial ใน partials/cms/ ที่วาดในฟอร์มต่อจากส่วนช่อง (block-builder, seo-panel, html-editor)
 *   $formAside     ไฟล์ partial ใน partials/cms/ ที่วาดนอกฟอร์ม (translations, revisions) — ฟอร์มซ้อนฟอร์มไม่ได้
 *   $formMultipart true = อัปโหลดไฟล์
 *   $formSubmit    ป้ายปุ่มบันทึก · $formCancel URL ยกเลิก
 *   $formDelete    ['url','name'] = มีปุ่มลบ (ต้องยืนยัน) — ไม่บังคับ
 *   $formLinks     ลิงก์รอง [['label','url','icon','external' => bool]] — ไม่บังคับ (ดูตัวอย่าง ฯลฯ)
 */

declare(strict_types=1);

$formSections = (array) ($formSections ?? []);
$formPanels   = (array) ($formPanels ?? []);
$formAside    = (array) ($formAside ?? []);
$formLinks    = (array) ($formLinks ?? []);
$formAllowed  = ['block-builder', 'seo-panel', 'translations', 'revisions', 'upload-field', 'menu-tree', 'settings-lists', 'lead-detail', 'media-usage'];
?>
<div class="stack">
    <?php if ($formLinks !== []): ?>
        <div class="row">
            <?php foreach ($formLinks as $formLink): ?>
                <a class="btn btn-secondary" href="<?= e((string) $formLink['url']) ?>"
                   <?php if (!empty($formLink['external'])): ?>target="_blank" rel="noopener"<?php endif; ?>>
                    <?php if (isset($formLink['icon'])): ?><?= icon((string) $formLink['icon']) ?><?php endif; ?>
                    <?= e((string) $formLink['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="card stack" method="POST" action="<?= e(url((string) $formAction)) ?>"
          <?php if (!empty($formMultipart)): ?>enctype="multipart/form-data"<?php endif; ?>>
        <?= csrf_field() ?>
        <?php if (($formMethod ?? 'POST') !== 'POST'): ?><?= method_field((string) $formMethod) ?><?php endif; ?>

        <?php foreach ($formSections as $formSection): ?>
            <fieldset class="group stack">
                <?php if (($formSection['title'] ?? '') !== ''): ?>
                    <legend><?= e((string) $formSection['title']) ?></legend>
                <?php endif; ?>
                <?php if (($formSection['hint'] ?? '') !== ''): ?>
                    <p class="hint"><?= e((string) $formSection['hint']) ?></p>
                <?php endif; ?>
                <div class="<?= e(!empty($formSection['grid']) ? 'field-grid' : 'stack') ?>">
                    <?php foreach ((array) $formSection['fields'] as $cmsField): ?>
                        <?php include __DIR__ . '/../../../partials/cms/form-field.php'; ?>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        <?php endforeach; ?>

        <?php foreach ($formPanels as $formPanel): ?>
            <?php if (in_array($formPanel, $formAllowed, true)): ?>
                <?php include __DIR__ . '/../../../partials/cms/' . $formPanel . '.php'; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="row">
            <button type="submit" class="btn btn-primary" data-submit-once><?= icon('check') ?><?= e((string) ($formSubmit ?? __('common.save'))) ?></button>
            <a class="btn" href="<?= e(url((string) ($formCancel ?? '/admin'))) ?>"><?= e(__('common.cancel')) ?></a>
        </div>
    </form>

    <?php foreach ($formAside as $formPanel): ?>
        <?php if (in_array($formPanel, $formAllowed, true)): ?>
            <?php include __DIR__ . '/../../../partials/cms/' . $formPanel . '.php'; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (is_array($formDelete ?? null)): ?>
        <section class="card row" aria-label="<?= e(__('common.delete')) ?>">
            <p class="muted"><?= e(__('cms.common.delete_hint')) ?></p>
            <span class="spacer">
                <?php
                $confirmUrl     = (string) $formDelete['url'];
                $confirmTitle   = __('confirm.delete_title', ['name' => (string) $formDelete['name']]);
                $confirmBody    = __('confirm.delete_body');
                $confirmTrigger = __('common.delete');
                include __DIR__ . '/../../../partials/confirm.php';
                ?>
            </span>
        </section>
    <?php endif; ?>
</div>

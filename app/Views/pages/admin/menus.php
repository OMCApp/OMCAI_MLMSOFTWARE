<?php
/**
 * จัดการเมนู — เลือกตำแหน่ง (header/footer) + ภาษา · รายการเรียงตามต้นไม้ · เพิ่ม/แก้/ลบ/เลื่อน
 *
 * ‼️ เมนูยังว่าง = เว็บใช้เมนูใน config/site.php — ปุ่ม "คัดลอกเมนูตั้งต้น" ทำสำเนาลงฐานข้อมูลให้แก้ต่อ
 * ‼️ footer: รายการชั้นบนที่มีลูก = หัวคอลัมน์ · ชั้นบนที่ไม่มีลูก = ลิงก์นโยบายแถวล่าง
 */

declare(strict_types=1);

$menuRows  = (array) ($menuRows ?? []);
$menuScope = http_build_query(['location' => $menuLocation, 'locale' => $menuLocale]);
?>
<div class="stack">
    <form class="card toolbar" method="GET" action="<?= e(url('/admin/menus')) ?>">
        <?php
        $fieldName    = 'location';
        $fieldLabel   = __('cms.menus.location');
        $fieldType    = 'select';
        $fieldValue   = (string) $menuLocation;
        $fieldOptions = ['header' => __('cms.menus.header'), 'footer' => __('cms.menus.footer')];
        $fieldWidth   = 'medium';
        include __DIR__ . '/../../partials/field.php';

        $fieldName    = 'locale';
        $fieldLabel   = __('cms.field.locale');
        $fieldType    = 'select';
        $fieldValue   = (string) $menuLocale;
        $fieldOptions = (array) $menuLocales;
        $fieldWidth   = 'medium';
        include __DIR__ . '/../../partials/field.php';
        ?>
        <button type="submit" class="btn btn-primary"><?= e(__('common.view')) ?></button>
    </form>

    <section class="card stack" aria-labelledby="menu-items-title">
        <h2 id="menu-items-title"><?= e(__('cms.menus.items')) ?></h2>
        <?php if ($menuRows === []): ?>
            <p class="muted"><?= e(__('cms.menus.empty_db')) ?></p>
            <form method="POST" action="<?= e(url('/admin/menus/import?' . $menuScope)) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary" data-submit-once><?= icon('download') ?><?= e(__('cms.menus.import')) ?></button>
            </form>
        <?php else: ?>
            <ul class="cms-list cms-menu">
                <?php foreach ($menuRows as $menuRow): ?>
                    <li class="row" data-depth="<?= e((string) $menuRow['depth']) ?>">
                        <span class="stack">
                            <a href="<?= e(url('/admin/menus/items/' . (int) $menuRow['id'])) ?>"><?= e((string) $menuRow['label']) ?></a>
                            <span class="muted small">
                                <?= e((string) ($menuRow['page_path'] ?? $menuRow['url'] ?? __('cms.menus.heading_only'))) ?>
                                <?php if ($menuRow['page_id'] !== null && ($menuRow['page_status'] ?? '') !== 'published'): ?>
                                    · <?= e(__('cms.menus.hidden_draft')) ?>
                                <?php endif; ?>
                            </span>
                        </span>
                        <span class="actions spacer">
                            <?php foreach (['up' => 'arrow-up', 'down' => 'arrow-down'] as $menuDir => $menuIcon): ?>
                                <form method="POST" action="<?= e(url('/admin/menus/items/' . (int) $menuRow['id'] . '/move')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="direction" value="<?= e($menuDir) ?>">
                                    <button type="submit" class="btn btn-icon" aria-label="<?= e(__('cms.menus.move_' . $menuDir)) ?>" title="<?= e(__('cms.menus.move_' . $menuDir)) ?>">
                                        <?= icon($menuIcon) ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                            <a class="btn btn-icon" href="<?= e(url('/admin/menus/items/' . (int) $menuRow['id'])) ?>"
                               aria-label="<?= e(__('common.edit')) ?>" title="<?= e(__('common.edit')) ?>"><?= icon('edit') ?></a>
                            <?php
                            $confirmUrl     = '/admin/menus/items/' . (int) $menuRow['id'];
                            $confirmTitle   = __('confirm.delete_title', ['name' => (string) $menuRow['label']]);
                            $confirmBody    = __('cms.menus.delete_body');
                            $confirmTrigger = __('common.delete');
                            include __DIR__ . '/../../partials/confirm.php';
                            ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <form class="card stack" method="POST" action="<?= e(url('/admin/menus/items?' . $menuScope)) ?>">
        <?= csrf_field() ?>
        <h2><?= e(__('cms.menus.add')) ?></h2>
        <div class="field-grid">
            <?php
            $fieldName = 'label'; $fieldLabel = __('cms.menus.label'); $fieldType = 'text'; $fieldValue = (string) old('label'); $fieldRequired = true;
            include __DIR__ . '/../../partials/field.php';
            $fieldRequired = false;

            $fieldName = 'page_id'; $fieldLabel = __('cms.menus.page'); $fieldType = 'select'; $fieldValue = (string) old('page_id'); $fieldOptions = (array) $menuPages;
            include __DIR__ . '/../../partials/field.php';

            $fieldName = 'url'; $fieldLabel = __('cms.menus.url'); $fieldType = 'text'; $fieldValue = (string) old('url'); $fieldHint = __('cms.menus.url_hint');
            include __DIR__ . '/../../partials/field.php';

            $fieldName = 'parent_id'; $fieldLabel = __('cms.menus.parent'); $fieldType = 'select'; $fieldValue = (string) old('parent_id'); $fieldOptions = (array) $menuParents;
            include __DIR__ . '/../../partials/field.php';

            $fieldName = 'description'; $fieldLabel = __('cms.menus.description'); $fieldType = 'text'; $fieldValue = (string) old('description');
            include __DIR__ . '/../../partials/field.php';

            $fieldName = 'icon'; $fieldLabel = __('cms.menus.icon'); $fieldType = 'text'; $fieldValue = (string) old('icon'); $fieldWidth = 'medium';
            include __DIR__ . '/../../partials/field.php';
            ?>
        </div>
        <div class="row">
            <button type="submit" class="btn btn-primary" data-submit-once><?= icon('plus') ?><?= e(__('cms.menus.add')) ?></button>
        </div>
    </form>
</div>

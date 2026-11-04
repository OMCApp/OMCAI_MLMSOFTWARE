<?php
/**
 * คลังสื่อ — อัปโหลด + ค้นหา + ตะแกรงรูป
 *
 * ‼️ ตะแกรงรูปไม่ใช่ตาราง (components.md: ของที่เลือกด้วยภาพ ใช้ตะแกรง) — การ์ดเป็นลิงก์ไปหน้าแก้ alt/title
 * ‼️ alt บังคับตอนอัปโหลด — ช่อง required ฝั่งหน้า + MediaService ตรวจซ้ำฝั่ง server
 */

declare(strict_types=1);

$mediaRows = (array) ($mediaRows ?? []);
?>
<div class="stack">
    <form class="card stack" method="POST" action="<?= e(url('/admin/media')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <h2><?= e(__('cms.media.upload')) ?></h2>
        <div class="field-grid">
            <?php
            $uploadName     = 'file';
            $uploadLabel    = __('cms.media.file');
            $uploadAccept   = (array) $mediaTypes;
            $uploadMaxBytes = (int) $mediaMax;
            $uploadPreview  = true;
            $uploadHint     = __('cms.media.upload_hint');
            include __DIR__ . '/../../partials/upload.php';
            ?>
            <div class="stack">
                <?php
                $fieldName     = 'alt';
                $fieldLabel    = __('cms.media.alt');
                $fieldType     = 'text';
                $fieldRequired = true;
                $fieldHint     = __('cms.media.alt_hint');
                include __DIR__ . '/../../partials/field.php';

                $fieldName     = 'title';
                $fieldLabel    = __('cms.field.title');
                $fieldRequired = false;
                include __DIR__ . '/../../partials/field.php';
                ?>
            </div>
        </div>
        <?php if (field_error('file') !== null): ?>
            <p class="error" role="alert"><?= e((string) field_error('file')) ?></p>
        <?php endif; ?>
        <div class="row">
            <button type="submit" class="btn btn-primary" data-submit-once><?= icon('upload') ?><?= e(__('cms.media.upload')) ?></button>
        </div>
    </form>

    <div class="card stack">
        <form class="toolbar" method="GET" action="<?= e(url('/admin/media')) ?>">
            <?php
            $fieldName  = 'q';
            $fieldLabel = __('common.search');
            $fieldType  = 'search';
            $fieldValue = (string) $mediaQ;
            $fieldWidth = 'medium';
            include __DIR__ . '/../../partials/field.php';
            ?>
            <button type="submit" class="btn btn-primary"><?= e(__('common.search')) ?></button>
            <a class="btn" href="<?= e(url('/admin/media')) ?>"><?= e(__('common.clear')) ?></a>
        </form>

        <div class="row">
            <?php if ((int) $mediaNoAlt > 0): ?>
                <p class="badge" data-type="warning"><?= icon('warning') ?><?= e(__('cms.media.no_alt_count', ['count' => (string) $mediaNoAlt])) ?></p>
            <?php endif; ?>
            <form method="POST" action="<?= e(url('/admin/media/import')) ?>" class="spacer">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary" data-submit-once title="<?= e(__('cms.media.import_hint')) ?>">
                    <?= icon('download') ?><?= e(__('cms.media.import')) ?>
                </button>
            </form>
        </div>

        <?php if ($mediaRows === []): ?>
            <?php $filtered = (string) $mediaQ !== ''; include __DIR__ . '/../../partials/empty-state.php'; ?>
        <?php else: ?>
            <ul class="media-grid">
                <?php foreach ($mediaRows as $mediaRow): ?>
                    <?php
                    // รูปย่อ: WebP 480 ถ้ามี (ไม่โหลดต้นฉบับหลาย MB มาแสดงเป็นรูปเล็ก)
                    $mediaSizes = json_decode((string) ($mediaRow['sizes'] ?? ''), true);
                    $mediaThumb = is_array($mediaSizes) && is_string($mediaSizes['480'] ?? null) ? $mediaSizes['480'] : (string) $mediaRow['path'];
                    ?>
                    <li class="media-card">
                        <a href="<?= e(url('/admin/media/' . (int) $mediaRow['id'])) ?>" class="stretched">
                            <img src="<?= e(url($mediaThumb)) ?>" alt="<?= e((string) $mediaRow['alt']) ?>"
                                 loading="lazy" width="<?= e((string) ($mediaRow['width'] ?? 160)) ?>" height="<?= e((string) ($mediaRow['height'] ?? 120)) ?>">
                            <span class="media-card-name"><?= e((string) ($mediaRow['title'] ?: basename((string) $mediaRow['path']))) ?></span>
                        </a>
                        <?php if ((string) $mediaRow['alt'] === ''): ?>
                            <span class="badge" data-type="warning"><?= e(__('cms.media.no_alt')) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
            $paginationTotal   = (int) $mediaTotal;
            $paginationPage    = (int) $mediaPage;
            $paginationPerPage = 48;
            include __DIR__ . '/../../partials/pagination.php';
            ?>
        <?php endif; ?>
    </div>
</div>

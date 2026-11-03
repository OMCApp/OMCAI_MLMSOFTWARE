<?php
/**
 * เนื้อหาข้างในกล่องแจ้งปัญหา — เปลือกอยู่ที่ dialog.php
 *
 * ‼️ ประเภท "วิธีใช้งาน" ต้องให้คู่มือตอบก่อนเสมอ ห้ามพาเข้าฟอร์มถามแอดมินทันที
 *    ผู้ใช้ 100 คนสงสัยเรื่องเดียวกัน แอดมินจะต้องตอบ 100 ครั้งด้วยคำตอบเดียวกัน
 *
 * ‼️ ระบบเก็บบริบทให้เอง ผู้ใช้ไม่ต้องกรอก URL หรือรหัส error เอง
 *    URL เก็บจากฝั่งเซิร์ฟเวอร์ รวม query string ด้วย
 *
 * อ่าน $manualForPage ที่ support.php คำนวณไว้ให้
 */

declare(strict_types=1);
?>
<div class="dialog-body" data-support-step="choose">
    <button type="button" class="btn btn-secondary" data-support-type="bug">
        <?= icon('bug') ?><?= e(__('support.type_bug')) ?>
    </button>
    <button type="button" class="btn btn-secondary" data-support-type="feature_request">
        <?= icon('sparkle') ?><?= e(__('support.type_feature')) ?>
    </button>

    <?php if ($manualForPage !== null): ?>
        <a class="btn btn-secondary" href="<?= e(url('/admin/manual/' . $manualForPage)) ?>">
            <?= icon('help') ?><?= e(__('support.type_how_to')) ?>
        </a>
    <?php else: ?>
        <button type="button" class="btn btn-secondary" data-support-type="how_to">
            <?= icon('help') ?><?= e(__('support.type_how_to')) ?>
        </button>
    <?php endif; ?>

    <a class="btn" href="<?= e(url('/admin/manual')) ?>">
        <?= icon('book') ?><?= e(__('support.open_manual')) ?>
    </a>
</div>

<form class="dialog-body" method="POST" action="<?= e(url('/admin/support')) ?>"
      enctype="multipart/form-data" data-support-step="form" hidden>
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="bug" data-support-type-input>
    <input type="hidden" name="page_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
    <?php if ($manualForPage !== null): ?>
        <input type="hidden" name="manual_slug" value="<?= e($manualForPage) ?>">
    <?php endif; ?>

    <?php
    $fieldName     = 'message';
    $fieldLabel    = __('support.message');
    $fieldType     = 'textarea';
    $fieldRequired = true;
    $fieldHint     = '';
    $fieldValue    = '';
    include __DIR__ . '/field.php';
    ?>

    <?php
    /* เงื่อนไขตรงกับ SupportService::storeAttachment() ที่สร้าง Uploader ด้วยค่าชุดนี้ */
    $uploadName     = 'attachment';
    $uploadLabel    = __('support.attachment');
    $uploadAccept   = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $uploadMaxBytes = (int) config('upload.max_bytes', 5_242_880);
    $uploadPreview  = true;
    include __DIR__ . '/upload.php';
    ?>

    <div class="row">
        <button type="button" class="btn" data-support-back><?= e(__('common.back')) ?></button>
        <button type="submit" class="btn btn-primary spacer" data-submit-once>
            <?= e(__('support.submit')) ?>
        </button>
    </div>
</form>

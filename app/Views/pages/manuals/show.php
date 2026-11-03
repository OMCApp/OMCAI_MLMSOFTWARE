<?php
/**
 * บทความคู่มือหนึ่งชิ้น
 *
 * ‼️ เนื้อความของบทความเป็นข้อยกเว้นเดียวที่เขียนข้อความตรงในไฟล์ได้
 *    แต่ต้องมีไฟล์ครบทุกภาษาที่เปิดใช้
 * ‼️ ยังไม่มีคำแปล → แสดงภาษาหลักพร้อมหมายเหตุ ห้าม 404 และห้ามหน้าว่าง
 */

declare(strict_types=1);
?>
<article class="card prose">
    <?php if (!$translated): ?>
        <p class="muted small"><?= e(__('manual.no_translate')) ?></p>
    <?php endif; ?>

    <?php include $article; ?>

    <hr>

    <form method="POST" action="<?= e(url('/support')) ?>" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="how_to">
        <input type="hidden" name="manual_slug" value="<?= e($slug) ?>">
        <input type="hidden" name="page_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">

        <?php
        $fieldName     = 'message';
        $fieldLabel    = __('support.still_stuck');
        $fieldType     = 'textarea';
        $fieldRequired = true;
        $fieldHint     = '';
        $fieldValue    = '';
        include __DIR__ . '/../../partials/field.php';
        ?>

        <button type="submit" class="btn btn-secondary" data-submit-once>
            <?= e(__('support.submit')) ?>
        </button>
    </form>
</article>

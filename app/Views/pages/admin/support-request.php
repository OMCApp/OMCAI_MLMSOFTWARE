<?php
/**
 * รายละเอียดเรื่องที่แจ้งเข้ามา
 *
 * ‼️ ข้อความจากผู้ใช้ต้องผ่าน e() ก่อนแสดงเสมอ แม้จะเป็นหน้าแอดมิน
 *    ถ้าลืม สคริปต์จะรันในเบราว์เซอร์ของคนที่มีสิทธิ์สูงสุด (stored XSS)
 */

declare(strict_types=1);

$statusOptions = [];
foreach (\App\Models\SupportRequest::STATUSES as $fieldValue) {
    $statusOptions[$fieldValue] = __('support.status_' . $fieldValue);
}
?>
<div class="stack">
    <article class="card stack">
        <div class="row">
            <span class="badge"><?= e(__('support.status_' . $record['status'])) ?></span>
            <span class="muted small"><?= e(format_date($record['created_at'], true)) ?></span>
        </div>

        <p><?= e((string) $record['message']) ?></p>

        <dl class="stack small muted">
            <div class="row">
                <dt><?= e(__('state.reference')) ?></dt>
                <dd><?= e((string) ($record['error_reference'] ?? '—')) ?></dd>
            </div>
            <div class="row">
                <dt><?= e(__('ui.language')) ?></dt>
                <dd><?= e((string) ($record['locale'] ?? '—')) ?> / <?= e((string) ($record['theme'] ?? '—')) ?></dd>
            </div>
            <div class="row">
                <dt>URL</dt>
                <dd><?= e((string) ($record['page_url'] ?? '—')) ?></dd>
            </div>
        </dl>

        <?php if (($record['attachment_path'] ?? null) !== null): ?>
            <a class="btn btn-secondary" href="<?= e(url('/admin/files/' . $record['id'])) ?>">
                <?= icon('download') ?><?= e((string) $record['attachment_name']) ?>
            </a>
        <?php endif; ?>
    </article>

    <form class="card stack" method="POST" action="<?= e(url('/admin/support-requests/' . $record['id'])) ?>">
        <?= csrf_field() ?>
        <?= method_field('PATCH') ?>

        <?php
        $fieldName    = 'status';
        $fieldLabel   = __('support.title');
        $fieldType    = 'select';
        $fieldValue   = (string) $record['status'];
        $fieldOptions = $statusOptions;
        include __DIR__ . '/../../partials/field.php';
        ?>

        <?php
        $fieldName     = 'admin_note';
        $fieldLabel    = __('common.edit');
        $fieldType     = 'textarea';
        $fieldRequired = false;
        $fieldValue    = (string) ($record['admin_note'] ?? '');
        $fieldOptions  = [];
        include __DIR__ . '/../../partials/field.php';
        ?>

        <button type="submit" class="btn btn-primary" data-submit-once><?= e(__('common.save')) ?></button>
    </form>
</div>

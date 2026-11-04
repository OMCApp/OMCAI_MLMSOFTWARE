<?php
/**
 * แก้ผู้ใช้ — ข้อมูล/บทบาท · ตั้งรหัสผ่านใหม่ · ออกจากทุกอุปกรณ์ · ลบ
 *
 * ‼️ สามฟอร์มแยกกัน (ฟอร์มซ้อนฟอร์มไม่ได้) — ทุกฟอร์มมี CSRF
 */

declare(strict_types=1);
?>
<div class="stack">
    <form class="card stack" method="POST" action="<?= e(url('/admin/users/' . (int) $userRow['id'])) ?>">
        <?= csrf_field() ?>
        <?= method_field('PATCH') ?>
        <p class="hint"><?= e(__('auth.username')) ?>: <strong><?= e((string) $userRow['username']) ?></strong></p>
        <?php foreach ((array) $userProfile as $cmsField): ?>
            <?php include __DIR__ . '/../../partials/cms/form-field.php'; ?>
        <?php endforeach; ?>
        <div class="row">
            <button type="submit" class="btn btn-primary" data-submit-once><?= icon('check') ?><?= e(__('common.save')) ?></button>
            <a class="btn" href="<?= e(url('/admin/users')) ?>"><?= e(__('common.cancel')) ?></a>
        </div>
    </form>

    <form class="card stack" method="POST" action="<?= e(url('/admin/users/' . (int) $userRow['id'] . '/password')) ?>">
        <?= csrf_field() ?>
        <h2><?= e(__('cms.users.reset_password')) ?></h2>
        <p class="hint"><?= e(__('cms.users.reset_hint')) ?></p>
        <div class="field-grid">
            <?php foreach ((array) $userPassword as $cmsField): ?>
                <?php include __DIR__ . '/../../partials/cms/form-field.php'; ?>
            <?php endforeach; ?>
        </div>
        <div class="row">
            <button type="submit" class="btn btn-secondary" data-submit-once><?= icon('lock') ?><?= e(__('cms.users.reset_password')) ?></button>
        </div>
    </form>

    <section class="card row">
        <p class="hint"><?= e(__('cms.users.devices_hint')) ?></p>
        <span class="spacer">
            <?php
            $confirmUrl     = '/admin/users/' . (int) $userRow['id'] . '/devices';
            $confirmTitle   = __('cms.users.devices_title', ['name' => (string) $userRow['username']]);
            $confirmBody    = __('cms.users.devices_body');
            $confirmLabel   = __('cms.users.devices');
            $confirmTrigger = __('cms.users.devices');
            $confirmIcon    = 'logout';
            $confirmMethod  = 'POST';
            include __DIR__ . '/../../partials/confirm.php';
            ?>
        </span>
    </section>

    <section class="card row">
        <p class="muted"><?= e(__('cms.common.delete_hint')) ?></p>
        <span class="spacer">
            <?php
            $confirmUrl     = '/admin/users/' . (int) $userRow['id'];
            $confirmTitle   = __('confirm.delete_title', ['name' => (string) $userRow['username']]);
            $confirmBody    = __('confirm.delete_body');
            $confirmLabel   = __('common.delete');
            $confirmTrigger = __('common.delete');
            $confirmIcon    = 'trash';
            $confirmMethod  = 'DELETE';
            include __DIR__ . '/../../partials/confirm.php';
            ?>
        </span>
    </section>
</div>

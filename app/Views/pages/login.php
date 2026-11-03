<?php
/**
 * หน้าเข้าสู่ระบบ
 * ‼️ validate ไม่ผ่านต้องคืนค่าที่กรอกไว้ — จัดการโดย partials/field.php
 */

declare(strict_types=1);
?>
<form class="card stack" method="POST" action="<?= e(url('/admin/login')) ?>">
    <?= csrf_field() ?>

    <h2><?= e(__('auth.login')) ?></h2>

    <?php
    $fieldName     = 'username';
    $fieldLabel    = __('auth.username');
    $fieldType     = 'text';
    $fieldRequired = true;
    $fieldHint     = '';
    include __DIR__ . '/../partials/field.php';
    ?>

    <?php
    $fieldName     = 'password';
    $fieldLabel    = __('auth.password');
    $fieldType     = 'password';
    $fieldRequired = true;
    $fieldValue    = '';
    include __DIR__ . '/../partials/field.php';
    ?>

    <button type="submit" class="btn btn-primary" data-submit-once>
        <?= e(__('auth.login')) ?>
    </button>
</form>

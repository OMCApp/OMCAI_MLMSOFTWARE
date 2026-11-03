<?php
/**
 * หน้า error 500
 * ‼️ ทุกหน้า error ต้องใช้ layout กลางและมีทางกลับเสมอ
 */

declare(strict_types=1);
?>
<div class="error-state">
    <?= icon('warning') ?>
    <h2><?= e(__('error.500_title')) ?></h2>
    <p><?= e(__('error.500_body')) ?></p>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>"><?= e(__('error.go_home')) ?></a>
</div>

<?php
/**
 * 404 / 410 ของเว็บสาธารณะ — มีทางกลับเสมอ (หน้าแรก + บทความ)
 * ชื่อหน้า (H1) มาจาก layout · noindex มาจาก SeoService
 */

declare(strict_types=1);

$siteStatus = (int) ($siteStatus ?? 404);
?>
<div class="error-state">
    <?= icon('warning') ?>
    <p><?= e($siteStatus === 410 ? __('site.gone_body') : __('error.404_body')) ?></p>
    <div class="row">
        <a class="btn btn-primary" href="<?= e(url('/')) ?>"><?= e(__('error.go_home')) ?></a>
        <a class="btn btn-secondary" href="<?= e(url('/blog/')) ?>"><?= e(__('site.blog')) ?></a>
    </div>
</div>

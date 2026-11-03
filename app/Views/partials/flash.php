<?php
/**
 * ข้อความแจ้งผล — ลอยมุมจอ ห้ามแทรกในหน้า
 * เพราะแบบแทรกจะดัน component อื่นเลื่อนตำแหน่ง ทำให้ผู้ใช้กดผิดที่
 *
 * ‼️ ต้องมี aria-live ไม่งั้น screen reader ไม่รู้ว่ามีอะไรเกิดขึ้น
 *    เพราะ toast ลอยอยู่นอกลำดับการอ่านของหน้า
 */

declare(strict_types=1);

$flashItems = flashes();

if ($flashItems === []) {
    return;
}

$flashIconOf = [
    'success' => 'check',
    'warning' => 'warning',
    'danger'  => 'danger',
    'info'    => 'info',
];
?>
<div class="toasts" role="status" aria-live="polite">
    <?php foreach ($flashItems as $flashItem): ?>
        <?php $flashType = isset($flashIconOf[$flashItem['type']]) ? $flashItem['type'] : 'info'; ?>
        <div class="toast" data-type="<?= e($flashType) ?>" data-toast>
            <span class="toast-icon"><?= icon($flashIconOf[$flashType]) ?></span>
            <div class="stack">
                <span><?= e($flashItem['message']) ?></span>
                <?php if ($flashItem['reference'] !== ''): ?>
                    <span class="ref"><?= e(__('state.reference')) ?>: <?= e($flashItem['reference']) ?></span>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-icon" data-toast-close
                    aria-label="<?= e(__('common.close')) ?>" title="<?= e(__('common.close')) ?>">
                <?= icon('close') ?>
            </button>
        </div>
    <?php endforeach; ?>
</div>

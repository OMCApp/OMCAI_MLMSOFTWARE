<?php
/**
 * สถานะว่าง
 *
 * ‼️ ต้องบอก 3 อย่าง: ว่าง · ทำไม (ถ้าเกี่ยวกับตัวกรอง) · ทำอะไรต่อได้
 *    ไม่ใช่แค่ "ไม่มีข้อมูล" แล้วจบ
 *
 * ‼️ แยกให้ออกระหว่าง "ไม่มีข้อมูลเลย" กับ "มีข้อมูลแต่ตัวกรองไม่เจอ"
 *
 * ตัวแปรที่รับ: $filtered (bool)
 */

declare(strict_types=1);

$filtered = (bool) ($filtered ?? false);
?>
<div class="empty-state">
    <?= icon('inbox-empty', 'icon-lg') ?>
    <h3><?= e($filtered ? __('state.no_result') : __('state.empty_title')) ?></h3>
    <p><?= e($filtered ? __('state.no_result_hint') : __('state.empty_hint')) ?></p>

    <?php if ($filtered): ?>
        <a class="btn btn-secondary" href="<?= e(url(current_path())) ?>"><?= e(__('common.clear')) ?></a>
    <?php endif; ?>
</div>

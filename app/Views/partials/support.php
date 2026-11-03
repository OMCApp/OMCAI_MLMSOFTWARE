<?php
/**
 * ปุ่มแจ้งปัญหา — อยู่ใน layout กลาง แสดงทุกหน้ารวมถึงหน้าที่ยังไม่ล็อกอิน
 *
 * เปลือกของกล่องมาจาก dialog.php · เนื้อหาอยู่ที่ support-body.php
 * ไฟล์นี้ทำหน้าที่เดียวคือหาว่าหน้าที่ผู้ใช้ยืนอยู่มีบทความคู่มือไหม แล้วประกอบทั้งสองเข้าด้วยกัน
 */

declare(strict_types=1);

$manualForPage = null;
$registry      = (array) require dirname(__DIR__, 3) . '/config/manuals.php';

foreach ($registry as $slug => $entry) {
    if (in_array(current_path(), (array) ($entry['routes'] ?? []), true)) {
        $manualForPage = $slug;
        break;
    }
}
?>
<button type="button" class="btn btn-icon support-fab" data-support-open
        aria-label="<?= e(__('ui.support')) ?>" title="<?= e(__('ui.support')) ?>">
    <?= icon('help') ?>
</button>

<?php
$dialogId    = 'support-dialog';
$dialogTitle = __('support.title');
$dialogBody  = __DIR__ . '/support-body.php';
include __DIR__ . '/dialog.php';

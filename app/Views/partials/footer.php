<?php
/**
 * ‼️ ทุกหน้าต้องมี footer และต้องมาจาก layout กลาง
 *    ข้อนี้ถูกข้ามบ่อยที่สุดถ้าไม่มีใครสั่ง
 */

declare(strict_types=1);
?>
<footer class="site-footer">
    <span><?= e((string) config('name')) ?></span>
    <span class="muted">v<?= e(trim((string) @file_get_contents(dirname(__DIR__, 3) . '/standard/VERSION'))) ?></span>
    <a href="<?= e(url('/admin/manual')) ?>"><?= e(__('ui.manual')) ?></a>
    <button type="button" class="btn" data-support-open><?= e(__('ui.support')) ?></button>
    <span class="spacer muted">&copy; <?= e(date('Y')) ?></span>

    <?php
    /* ‼️ ตัวนับ query โผล่เฉพาะโหมด development
       เห็นตัวเลขทุกหน้าตอนเขียน จึงรู้ทันทีที่เผลอ query ในลูป
       ไม่ต้องรอไปนับตอนช่วง D ซึ่งเป็นตอนที่แก้แล้วต้องรื้อ
       ห้ามโผล่บน production เพราะบอกโครงสร้างภายในให้คนนอกเห็น */
    if ((bool) config('debug')): ?>
        <span class="muted small" data-query-meter><?= e(query_meter()) ?></span>
    <?php endif; ?>
</footer>

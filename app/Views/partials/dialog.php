<?php
/**
 * กล่อง modal ของกลาง — เปลือกอย่างเดียว เนื้อหาส่งมาเป็นไฟล์
 *
 * ‼️ เปลือกต้องเป็นของกลาง ไม่งั้นกล่องที่สองในระบบจะถูก copy markup ไปวาง
 *    ซึ่งผิดกฎเหล็กข้อ 18 และทำให้ modal ในโปรเจคเดียวกันหน้าตาไม่ตรงกัน
 *    เดิม markup ของ <dialog> ฝังอยู่ใน support.php ตัวเดียว ใช้ซ้ำไม่ได้เลย
 *
 * ‼️ รับ "path ของไฟล์เนื้อหา" ไม่ใช่ HTML
 *    components.md 1ก กฎ 3 ห้าม partial รับ HTML เพราะแปลว่ามีจุดที่ output ไม่ผ่าน e()
 *    การส่งชื่อไฟล์ทำให้เนื้อหายังเป็นเทมเพลตปกติที่ทุกค่าผ่าน e() ตามเดิม
 *
 * ‼️ ปิดกล่องด้วย data-dialog-close — app.js ผูกให้ทุกกล่องในระบบพร้อมกัน
 *    ไม่ต้องเขียน handler ใหม่ต่อกล่อง
 *
 * ตัวแปรที่รับ:
 *   $dialogId     id ของ <dialog> (บังคับ) — ใช้ประกอบ id ของหัวข้อด้วย
 *   $dialogTitle  หัวข้อที่แสดงบนหัวกล่อง (บังคับ)
 *   $dialogBody   path เต็มของไฟล์เนื้อหา (บังคับ)
 *   $dialogSmall  true = กล่องเล็กที่ไม่เต็มจอบนมือถือ · ค่าเริ่มต้น false
 */

declare(strict_types=1);

$dialogId    = $dialogId    ?? '';
$dialogTitle = $dialogTitle ?? '';
$dialogBody  = $dialogBody  ?? '';
$dialogSmall = $dialogSmall ?? false;
?>
<dialog id="<?= e($dialogId) ?>"<?php if ($dialogSmall): ?> class="dialog-sm"<?php endif; ?>
        aria-labelledby="<?= e($dialogId) ?>-title">
    <div class="dialog-head">
        <h2 id="<?= e($dialogId) ?>-title"><?= e($dialogTitle) ?></h2>
        <button type="button" class="btn btn-icon spacer" data-dialog-close
                aria-label="<?= e(__('common.close')) ?>" title="<?= e(__('common.close')) ?>">
            <?= icon('close') ?>
        </button>
    </div>

    <?php include $dialogBody; ?>
</dialog>
<?php
/* ล้างค่าที่ไม่ควรค้าง — หน้าที่มีสองกล่องจะได้ไม่หยิบเนื้อหาของกล่องก่อนหน้ามาใช้ */
$dialogId    = '';
$dialogTitle = '';
$dialogBody  = '';
$dialogSmall = false;

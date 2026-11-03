<?php
/**
 * ปุ่มที่ต้องยืนยันก่อนทำ — ใช้กับทุกการกระทำที่ย้อนกลับยาก
 *
 * ‼️ กฎที่สำคัญที่สุดของ component นี้: ต้องบอกว่ากำลังทำอะไร "กับอะไร"
 *    ❌ ยืนยันการลบ?                [ยกเลิก] [ตกลง]
 *    ✅ ลบ "ใบสั่งซื้อ #1042" ?      [ยกเลิก] [ลบ]
 *    คนอ่านปุ่ม ไม่อ่านข้อความ ปุ่มยืนยันจึงต้องเป็นคำกริยาที่จะเกิดขึ้นจริง
 *
 * ‼️ กล่องยืนยันถูกสร้างโดย JS ไม่ได้เขียนไว้ในหน้า
 *    ตามกฎ "ของที่ทำงานได้เพราะ JS ต้องถูกสร้างโดย JS" (design.md 5.11)
 *    JS พังเมื่อไหร่ ฟอร์มนี้ยัง submit ได้ตามปกติ ซึ่งดีกว่าปุ่มที่กดแล้วไม่เกิดอะไร
 *
 * ตัวแปรที่รับ:
 *   $confirmUrl      ปลายทางของฟอร์ม (บังคับ)
 *   $confirmTitle    หัวข้อในกล่อง — ต้องมีชื่อของสิ่งที่จะถูกกระทำอยู่ในนั้น (บังคับ)
 *   $confirmBody     บอกว่าผลลัพธ์ย้อนกลับได้หรือไม่
 *   $confirmLabel    คำกริยาบนปุ่มยืนยัน — ค่าเริ่มต้นคือ "ลบ"
 *   $confirmTrigger  ข้อความของปุ่มที่กดเรียก (aria-label + tooltip) — ค่าเริ่มต้นใช้ $confirmTitle
 *   $confirmIcon     ไอคอนบนปุ่มที่กดเรียก — ค่าเริ่มต้น trash
 *   $confirmMethod   ค่าเริ่มต้น DELETE
 */

declare(strict_types=1);

$confirmUrl     = $confirmUrl     ?? '';
$confirmTitle   = $confirmTitle   ?? '';
$confirmBody    = $confirmBody    ?? '';
$confirmLabel   = $confirmLabel   ?? __('common.delete');
$confirmTrigger = $confirmTrigger ?? $confirmTitle;
$confirmIcon    = $confirmIcon    ?? 'trash';
$confirmMethod  = $confirmMethod  ?? 'DELETE';
?>
<form class="confirm-form" method="POST" action="<?= e(url($confirmUrl)) ?>"
      data-confirm
      data-confirm-title="<?= e($confirmTitle) ?>"
      data-confirm-body="<?= e($confirmBody) ?>"
      data-confirm-label="<?= e($confirmLabel) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="<?= e($confirmMethod) ?>">

    <button type="submit" class="btn btn-icon btn-danger" data-submit-once
            aria-label="<?= e($confirmTrigger) ?>" title="<?= e($confirmTrigger) ?>">
        <?= icon($confirmIcon) ?>
    </button>
</form>
<?php
/* ‼️ ล้างค่าที่ไม่ควรค้างข้ามการ include ครั้งถัดไป
   ในตารางจะมีปุ่มนี้แถวละอัน ถ้าค่าค้าง แถวถัดไปจะถามยืนยันด้วยชื่อของแถวก่อนหน้า
   ซึ่งเป็นความผิดพลาดที่อันตรายที่สุดของ component นี้ — ผู้ใช้กดยืนยันลบผิดรายการ */
$confirmUrl     = '';
$confirmTitle   = '';
$confirmBody    = '';
$confirmTrigger = null;

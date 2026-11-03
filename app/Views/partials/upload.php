<?php
/**
 * ช่องแนบไฟล์ — ลากวางได้ เลือกหลายไฟล์ได้ ลบทีละไฟล์ก่อนส่งได้
 *
 * ‼️ ตัวลากวางถูกสร้างโดย app.js ไม่ได้เขียนไว้ในหน้า (design.md 5.11)
 *    ไฟล์นี้ render แค่ <input type="file"> ปกติที่ใช้งานได้ด้วยตัวเอง
 *    JS โหลดไม่ขึ้นเมื่อไหร่ ผู้ใช้ยังกดเลือกไฟล์และส่งฟอร์มได้ตามปกติ
 *    ซึ่งดีกว่ากรอบลากวางสวยๆ ที่ลากแล้วไม่เกิดอะไร
 *
 * ‼️ เงื่อนไขที่บอกผู้ใช้ต้องตรงกับที่เซิร์ฟเวอร์ยอมรับจริง
 *    บอกว่ารับ 10MB แล้ว Uploader ตัดที่ 5MB คือการโกหกผู้ใช้
 *    ค่าเริ่มต้นตรงกับ core/Uploader.php · เปลี่ยนที่จุดไหนต้องเปลี่ยนอีกจุดด้วย
 *
 * ‼️ การตรวจฝั่งหน้าเป็นแค่ความสะดวก ไม่ใช่ความปลอดภัย
 *    เซิร์ฟเวอร์ต้องตรวจ mime จริงด้วย finfo ซ้ำเสมอ (security.md ข้อ 9)
 *
 * ตัวแปรที่รับ:
 *   $uploadName      ชื่อ field (บังคับ) · หลายไฟล์ให้ลงท้ายด้วย [] เอง
 *   $uploadLabel     ข้อความ label (บังคับ)
 *   $uploadAccept    นามสกุลที่รับ เป็นอาเรย์ เช่น ['jpg','png','pdf']
 *   $uploadMaxBytes  ขนาดสูงสุดต่อไฟล์
 *   $uploadMultiple  true = เลือกหลายไฟล์ได้
 *   $uploadPreview   true = แสดงรูปย่อแทนรายการชื่อไฟล์
 *   $uploadHint      คำอธิบายเพิ่มเติมใต้ label
 */

declare(strict_types=1);

$uploadName     = $uploadName     ?? 'attachment';
$uploadLabel    = $uploadLabel    ?? '';
$uploadAccept   = $uploadAccept   ?? ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
$uploadMaxBytes = $uploadMaxBytes ?? 5_242_880;
$uploadMultiple = $uploadMultiple ?? false;
$uploadPreview  = $uploadPreview  ?? false;
$uploadHint     = $uploadHint     ?? '';

$uploadId    = 'u-' . preg_replace('/[^a-z0-9_-]/i', '-', $uploadName);
$uploadMb    = round($uploadMaxBytes / 1048576, 1);
$uploadTypes = strtoupper(implode(' · ', $uploadAccept));
?>
<div class="field upload" data-upload
     data-upload-max="<?= e((string) $uploadMaxBytes) ?>"
     data-upload-accept="<?= e(implode(',', $uploadAccept)) ?>"
     data-upload-preview="<?= e($uploadPreview ? '1' : '0') ?>">

    <label for="<?= e($uploadId) ?>"><?= e($uploadLabel) ?></label>

    <?php if ($uploadHint !== ''): ?>
        <span class="hint" id="<?= e($uploadId) ?>-hint"><?= e($uploadHint) ?></span>
    <?php endif; ?>

    <?php /* ‼️ บอกล่วงหน้าเสมอว่ารับอะไร ขนาดเท่าไหร่ — ไม่ใช่ให้ผู้ใช้ลองแล้วโดนปฏิเสธ */ ?>
    <span class="hint" id="<?= e($uploadId) ?>-rules">
        <?= e(__('upload.accepted', ['types' => $uploadTypes, 'size' => $uploadMb . ' MB'])) ?>
    </span>

    <input id="<?= e($uploadId) ?>" type="file" name="<?= e($uploadName) ?>"
           accept="<?= e('.' . implode(',.', $uploadAccept)) ?>"
           <?php if ($uploadMultiple): ?>multiple<?php endif; ?>
           aria-describedby="<?= e($uploadId) ?>-rules">
</div>
<?php
/* ล้างค่าที่ไม่ควรค้าง — ฟอร์มที่มีสองช่องแนบไฟล์จะได้ไม่ยืมเงื่อนไขของช่องก่อนหน้า */
$uploadLabel    = '';
$uploadHint     = '';
$uploadMultiple = false;
$uploadPreview  = false;

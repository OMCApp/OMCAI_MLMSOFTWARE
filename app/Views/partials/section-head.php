<?php
/**
 * หัวของ section ในหน้าสาธารณะ — เม็ดหัวข้อเล็ก + หัวข้อ h2 + ประโยคนำ
 *
 * ‼️ ใช้ร่วมกันทุกบล็อกใน partials/blocks/ — ห้ามเขียน <h2> + lead เองในบล็อก
 *    เห็นรูปแบบนี้ซ้ำในบล็อกที่สองเมื่อไหร่ก็ต้องเป็นของกลาง (กฎเหล็กข้อ 18)
 *
 * ตัวแปรที่รับ:
 *   $sectionHeadEyebrow  ข้อความเล็กเหนือหัวข้อ — ไม่บังคับ
 *   $sectionHeadTitle    หัวข้อ (h2) — ว่าง = ไม่วาดอะไรเลย
 *   $sectionHeadLead     ประโยคนำ — ไม่บังคับ
 *   $sectionHeadAlign    'center' (ค่าเริ่มต้น) | 'start'
 *   $sectionHeadId       id ของ h2 สำหรับ aria-labelledby ของ section — ไม่บังคับ
 */

declare(strict_types=1);

$sectionHeadEyebrow = (string) ($sectionHeadEyebrow ?? '');
$sectionHeadTitle   = (string) ($sectionHeadTitle ?? '');
$sectionHeadLead    = (string) ($sectionHeadLead ?? '');
$sectionHeadAlign   = (string) ($sectionHeadAlign ?? 'center');
$sectionHeadId      = (string) ($sectionHeadId ?? '');

if ($sectionHeadTitle !== ''): ?>
<header class="section-head reveal" data-align="<?= e($sectionHeadAlign === 'start' ? 'start' : 'center') ?>">
    <?php if ($sectionHeadEyebrow !== ''): ?>
        <p class="eyebrow-pill"><?= e($sectionHeadEyebrow) ?></p>
    <?php endif; ?>
    <h2 class="section-title"<?php if ($sectionHeadId !== ''): ?> id="<?= e($sectionHeadId) ?>"<?php endif; ?>><?= e($sectionHeadTitle) ?></h2>
    <?php if ($sectionHeadLead !== ''): ?>
        <p class="section-lead"><?= e($sectionHeadLead) ?></p>
    <?php endif; ?>
</header>
<?php endif;

// ‼️ ล้างค่าทุกตัว — partial ใช้ขอบเขตเดียวกับผู้เรียก ค่าค้างแล้วบล็อกถัดไปได้หัวข้อของบล็อกก่อน
$sectionHeadEyebrow = '';
$sectionHeadTitle   = '';
$sectionHeadLead    = '';
$sectionHeadAlign   = 'center';
$sectionHeadId      = '';

<?php
/**
 * ช่องกรอกข้อมูล
 *
 * ‼️ ทุกช่องมี <label> ผูกกับ id จริง — placeholder ไม่ใช่ label เพราะหายไปตอนพิมพ์
 * ‼️ error แสดงใต้ช่องที่ผิด ไม่กองรวมบนสุด
 * ‼️ validate ไม่ผ่านต้องคืนค่าที่กรอกไว้ ไม่ให้ผู้ใช้กรอกใหม่หมด
 *
 * ตัวแปรที่รับ: $fieldName $fieldLabel $fieldType $fieldRequired $fieldHint $fieldOptions $fieldValue $fieldRows $fieldChecked
 *               $fieldSearch $fieldSource — dropdown ที่ค้นได้ (standard/components.md ข้อ 3ก)
 *
 * ‼️ ตัวแปรของ partial ต้องขึ้นต้นด้วยชื่อ partial เสมอ ($fieldRows ไม่ใช่ $rows)
 *    PHP include ใช้ขอบเขตตัวแปรร่วมกับหน้าที่เรียก ชื่อกลางๆ จึงทับกันเงียบๆ
 *    เคยเกิดจริง: หน้ารายการใช้ $rows เป็นข้อมูล แต่ partial นี้ตั้ง $rows = 4
 *    ฟอร์มแจ้งปัญหาใน layout มี textarea อยู่ทุกหน้า → ตารางหายทั้งระบบ
 *
 * ‼️ ค่าที่ตั้งไว้จะ "ค้าง" ถึงการ include ครั้งถัดไปในหน้าเดียวกัน
 *    ตั้งใจให้เป็นแบบนั้น เพื่อให้ตั้ง $fieldType ครั้งเดียวแล้วใช้กับหลายช่องได้
 *    แต่คนที่วน loop สร้างหลายช่อง ต้องล้างค่าที่ไม่ใช้เองทุกรอบ
 */

declare(strict_types=1);

$fieldName     = $fieldName     ?? '';
$fieldLabel    = $fieldLabel    ?? $fieldName;
$fieldType     = $fieldType     ?? 'text';
$fieldRequired = $fieldRequired ?? false;
$fieldHint     = $fieldHint     ?? '';
$fieldOptions  = $fieldOptions  ?? [];
$fieldRows     = $fieldRows     ?? 4;

/* dropdown ที่ค้นได้ — เกณฑ์อยู่ที่ standard/components.md ข้อ 3ก
   $fieldSource = คีย์ใน config/options.php → ค้นจากฝั่ง server (ใช้กับข้อมูลที่โตขึ้นเรื่อยๆ)
   ไม่ระบุ $fieldSearch → เกิน 8 ตัวเลือกเปิดช่องค้นให้เอง เพื่อไม่ต้องตัดสินใจทีละหน้า */
$fieldSource = $fieldSource ?? '';
$fieldSearch = $fieldSearch ?? ($fieldSource !== '' || count($fieldOptions) > 8);

/* ค่าที่ส่งไปเมื่อติ๊กถูก (checkbox เดี่ยว) — ไม่ระบุใช้ '1' */
$fieldChecked = $fieldChecked ?? null;

/* ‼️ ความกว้างของช่องต้องบอกความยาวที่คาดหวัง
   ช่องกว้างเต็มฟอร์มที่รับได้ 3 ตัวอักษร คือการบอกผู้ใช้ผิด
   short = เลข/รหัส · medium = ชื่อ/เบอร์ · full = ที่อยู่/ข้อความยาว */
$fieldWidth = $fieldWidth ?? 'full';
$fieldValue    = $fieldValue    ?? old($fieldName);
$fieldError    = field_error($fieldName);
$fieldId           = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $fieldName);
/* ‼️ คำอธิบายและข้อความผิดพลาดผูกกับช่องด้วย aria-describedby — screen reader อ่าน error ตอนโฟกัสช่อง */
$fieldDescribedBy  = trim(($fieldHint !== '' ? $fieldId . '-hint' : '') . ($fieldError !== null ? ' ' . $fieldId . '-error' : ''));
?>
<div class="field" data-width="<?= e($fieldWidth) ?>"<?php if ($fieldError !== null): ?> data-invalid="true"<?php endif; ?>>
    <?php if ($fieldType === 'checkbox'): ?>
        <?php /* ติ๊กเดียว — ป้ายอยู่ข้างกล่อง ไม่ใช่ข้างบน เพราะกล่องเปล่าๆ ไม่บอกว่าติ๊กอะไร */ ?>
        <label class="choice" for="<?= e($fieldId) ?>">
            <input id="<?= e($fieldId) ?>" name="<?= e($fieldName) ?>" type="checkbox"
                   value="<?= e((string) ($fieldChecked === null ? '1' : $fieldChecked)) ?>"
                   <?php if ($fieldValue): ?>checked<?php endif; ?>
                   <?php if ($fieldRequired): ?>required<?php endif; ?>
                   <?php if ($fieldDescribedBy !== ''): ?>aria-describedby="<?= e($fieldDescribedBy) ?>"<?php endif; ?><?php if ($fieldError !== null): ?> aria-invalid="true"<?php endif; ?>>
            <span class="choice-text">
                <span><?= e($fieldLabel) ?><?php if ($fieldRequired): ?><span class="required-mark" aria-hidden="true">*</span><?php endif; ?></span>
                <?php if ($fieldHint !== ''): ?>
                    <span class="hint" id="<?= e($fieldId) ?>-hint"><?= e($fieldHint) ?></span>
                <?php endif; ?>
            </span>
        </label>

    <?php elseif ($fieldType === 'radio' || $fieldType === 'checkboxes'): ?>
        <?php /* กลุ่มตัวเลือกต้องเป็น fieldset + legend ไม่ใช่ label ลอยๆ
                 ไม่งั้น screen reader อ่านตัวเลือกโดยไม่บอกว่ามันคือกลุ่มของคำถามอะไร */ ?>
        <fieldset class="choice-group">
            <legend>
                <?= e($fieldLabel) ?>
                <?php if ($fieldRequired): ?><span class="required-mark" aria-hidden="true">*</span><?php endif; ?>
            </legend>

            <?php if ($fieldHint !== ''): ?>
                <span class="hint" id="<?= e($fieldId) ?>-hint"><?= e($fieldHint) ?></span>
            <?php endif; ?>

            <?php
            $fieldChosen = $fieldType === 'checkboxes'
                ? array_map('strval', (array) ($fieldValue ?: []))
                : [(string) $fieldValue];
            ?>

            <?php foreach ($fieldOptions as $optionValue => $optionLabel): ?>
                <label class="choice" for="<?= e($fieldId . '-' . $optionValue) ?>">
                    <input id="<?= e($fieldId . '-' . $optionValue) ?>"
                           name="<?= e($fieldType === 'checkboxes' ? $fieldName . '[]' : $fieldName) ?>"
                           type="<?= e($fieldType === 'radio' ? 'radio' : 'checkbox') ?>"
                           value="<?= e((string) $optionValue) ?>"
                           <?php if (in_array((string) $optionValue, $fieldChosen, true)): ?>checked<?php endif; ?>
                           <?php if ($fieldRequired && $fieldType === 'radio'): ?>required<?php endif; ?>
                           <?php if ($fieldDescribedBy !== ''): ?>aria-describedby="<?= e($fieldDescribedBy) ?>"<?php endif; ?><?php if ($fieldError !== null): ?> aria-invalid="true"<?php endif; ?>>
                    <span class="choice-text"><?= e((string) $optionLabel) ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>

    <?php else: ?>
    <label for="<?= e($fieldId) ?>">
        <?= e($fieldLabel) ?>
        <?php if ($fieldRequired): ?><span class="required-mark" aria-hidden="true">*</span><?php endif; ?>
    </label>

    <?php if ($fieldHint !== ''): ?>
        <span class="hint" id="<?= e($fieldId) ?>-hint"><?= e($fieldHint) ?></span>
    <?php endif; ?>

    <?php if ($fieldType === 'textarea'): ?>
        <textarea id="<?= e($fieldId) ?>" name="<?= e($fieldName) ?>" rows="<?= e((string) $fieldRows) ?>"
                  <?php if ($fieldRequired): ?>required<?php endif; ?>
                  <?php if ($fieldDescribedBy !== ''): ?>aria-describedby="<?= e($fieldDescribedBy) ?>"<?php endif; ?><?php if ($fieldError !== null): ?> aria-invalid="true"<?php endif; ?>
        ><?= e((string) $fieldValue) ?></textarea>

    <?php elseif ($fieldType === 'select'): ?>
        <select id="<?= e($fieldId) ?>" name="<?= e($fieldName) ?>" <?php if ($fieldRequired): ?>required<?php endif; ?>
                <?php if ($fieldSearch): ?>data-search="true"<?php endif; ?>
                <?php if ($fieldSource !== ''): ?>data-source="<?= e(url('/admin/options/' . $fieldSource)) ?>"<?php endif; ?>
                <?php if ($fieldDescribedBy !== ''): ?>aria-describedby="<?= e($fieldDescribedBy) ?>"<?php endif; ?><?php if ($fieldError !== null): ?> aria-invalid="true"<?php endif; ?>>
            <?php foreach ($fieldOptions as $optionValue => $optionLabel): ?>
                <option value="<?= e((string) $optionValue) ?>"
                    <?php if ((string) $optionValue === (string) $fieldValue): ?>selected<?php endif; ?>>
                    <?= e((string) $optionLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php else: ?>
        <input id="<?= e($fieldId) ?>" name="<?= e($fieldName) ?>" type="<?= e($fieldType) ?>"
               value="<?= e((string) $fieldValue) ?>"
               <?php if ($fieldRequired): ?>required<?php endif; ?>
               <?php if ($fieldDescribedBy !== ''): ?>aria-describedby="<?= e($fieldDescribedBy) ?>"<?php endif; ?><?php if ($fieldError !== null): ?> aria-invalid="true"<?php endif; ?>>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($fieldError !== null): ?>
        <span class="error" id="<?= e($fieldId) ?>-error"><?= e($fieldError) ?></span>
    <?php endif; ?>
</div>
<?php
/* ‼️ ล้างค่าที่ไม่ควรค้างข้ามการ include ครั้งถัดไป
   $fieldType / $fieldRequired ตั้งใจให้ค้าง เพื่อตั้งครั้งเดียวแล้วใช้กับหลายช่องได้
   แต่ $fieldWidth / $fieldOptions / $fieldHint ถ้าค้าง ช่องถัดไปจะแคบหรือได้ตัวเลือก
   ของช่องก่อนหน้าโดยไม่มีใครสั่ง — เคยทำให้ select แคบตามช่องข้อความที่อยู่ก่อนมัน */
$fieldWidth = 'full';
$fieldOptions = [];
$fieldHint    = '';
$fieldSource  = '';
$fieldSearch  = null;
$fieldChecked = null;
// ‼️ ค่าที่กรอกไว้ต้องไม่ค้างไปช่องถัดไป — ไม่ล้างแล้วทุกช่องหลังช่องแรกได้ค่า old() ของช่องแรก (ฟอร์ม lead คืนค่าไม่ได้)
$fieldValue   = null;

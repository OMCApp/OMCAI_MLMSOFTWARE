<?php
/**
 * ข้อความที่ฝั่ง JavaScript ต้องใช้ — ส่งผ่าน data-* ให้ app.js อ่าน
 *
 * ‼️ เหตุผลที่ต้องมีไฟล์นี้
 *    JS อ่านไฟล์ภาษาของ PHP ไม่ได้ และ CSP ห้าม inline script
 *    ถ้าไม่มีทางส่งข้อความข้ามฝั่ง คนเขียนจะ hardcode ภาษาไทยลง app.js
 *    แล้ววันที่เพิ่มภาษาใหม่ ข้อความฝั่ง JS จะไม่แปลตาม — โดยไม่มีอะไรจับได้
 *
 * ‼️ ห้าม hardcode ข้อความใดๆ ใน public/assets/js/app.js
 *    ทุกคำที่ผู้ใช้เห็นต้องผ่าน __() แล้วส่งมาที่นี่ หรือของเฉพาะหน้าให้ใส่ที่ element ของหน้านั้น
 *
 * ‼️ สิ่งที่ Intl ของเบราว์เซอร์ให้ได้ ห้ามส่ง
 *    ชื่อเดือน ชื่อวัน รูปแบบวันที่ พ.ศ. — ใช้ Intl.DateTimeFormat(locale) แทน
 *    ส่งมาเองเมื่อไหร่ = มีคำแปลซ้ำสองที่ แล้ววันหนึ่งจะไม่ตรงกัน
 *
 * รวมไว้ใน layout กลางทุกตัว เพื่อให้ app.js อ่านได้จากทุกหน้า
 */

declare(strict_types=1);
?>
<span id="js-lang" hidden
      data-locale="<?= e(locale()) ?>"
      data-clear="<?= e(__('ui.clear')) ?>"
      data-search="<?= e(__('ui.search')) ?>"
      data-no-results="<?= e(__('ui.no_results')) ?>"
      data-cancel="<?= e(__('ui.cancel')) ?>"
      data-upload-drop="<?= e(__('upload.drop_here')) ?>"
      data-upload-drop-active="<?= e(__('upload.drop_active')) ?>"
      data-upload-choose="<?= e(__('upload.choose')) ?>"
      data-upload-remove="<?= e(__('upload.remove')) ?>"
      data-upload-too-large="<?= e(__('upload.too_large')) ?>"
      data-upload-bad-type="<?= e(__('upload.bad_type')) ?>"
      data-upload-uploading="<?= e(__('upload.uploading')) ?>"
      data-close="<?= e(__('ui.close')) ?>"
      data-loading="<?= e(__('ui.loading')) ?>"
      data-load-more="<?= e(__('ui.load_more')) ?>"
      data-type-more="<?= e(__('ui.type_more')) ?>"></span>

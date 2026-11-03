<?php
/**
 * ลิงก์ไอคอนของแท็บและหน้าจอโฮม — ใช้ร่วมกันทุก layout
 *
 * ‼️ ไฟล์ทั้งหมดที่นี่ generate ด้วย `php scripts/make-favicon.php` ห้ามทำด้วยมือ
 *    ต้องรันซ้ำทุกครั้งที่เปลี่ยนโลโก้หรือสีแบรนด์ใน project/DESIGN.md
 *
 * ‼️ เป็น partial กลางเพราะ layout มีมากกว่าหนึ่งตัว — เขียนแยกทีละ layout เมื่อไหร่
 *    วันที่เพิ่มขนาดใหม่จะมี layout ที่ถูกลืม แล้วหน้า login กับหน้าในระบบจะไอคอนไม่ตรงกัน
 */

declare(strict_types=1);
?>
<link rel="icon" href="<?= e(url('/assets/images/favicon.ico')) ?>" sizes="any">
<link rel="icon" type="image/png" href="<?= e(url('/assets/images/favicon-32.png')) ?>" sizes="32x32">
<link rel="apple-touch-icon" href="<?= e(url('/assets/images/apple-touch-icon.png')) ?>">

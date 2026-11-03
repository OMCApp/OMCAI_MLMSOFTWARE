<?php
/**
 * หน้าปรับปรุงระบบ
 *
 * ‼️ ไฟล์นี้ถูก include ตรงจาก public/index.php ไม่ผ่าน Controller และไม่ผ่าน layout
 *    จึงต้องเป็นหน้า HTML เต็มที่ยืนอยู่ได้เอง
 *
 * ใช้ __() ได้ เพราะ index.php ตั้งค่าภาษาเสร็จก่อนตรวจ maintenance
 * และไฟล์ภาษาอ่านจากดิสก์ ไม่ต้องพึ่งฐานข้อมูลซึ่งอาจกำลังปิดอยู่
 */

declare(strict_types=1);
?>
<!doctype html>
<html lang="<?= e(locale()) ?>" data-theme="<?= e(theme()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(__('error.maintenance_title')) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body>
<main class="centered">
    <div class="error-state">
        <h2><?= e(__('error.maintenance_title')) ?></h2>
        <p><?= e(__('error.maintenance_body')) ?></p>
    </div>
</main>
</body>
</html>

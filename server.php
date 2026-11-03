<?php
/**
 * ตัวช่วยรันบนเครื่องตัวเองเท่านั้น
 *
 *   php server.php            เปิดที่ http://localhost:8000
 *   php server.php 8080       เลือกพอร์ตเอง
 *
 * ‼️ ห้ามอัปไฟล์นี้ขึ้น server จริง และห้ามเรียกผ่านเว็บเซิร์ฟเวอร์
 *    บนเครื่องจริงต้องตั้ง document root ที่ public/ เท่านั้น
 */

declare(strict_types=1);

// ตัวไฟล์นี้ทำหน้าที่ 2 อย่างตามบริบทที่ถูกเรียก
if (PHP_SAPI === 'cli-server') {
    // ทำตัวเป็น router ให้ built-in server: ไฟล์จริงเสิร์ฟตรง ที่เหลือเข้า front controller
    $path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $file = __DIR__ . '/public' . $path;

    if ($path !== '/' && is_file($file)) {
        return false;
    }

    /* ‼️ built-in server ตั้ง SCRIPT_NAME เป็น path ที่ขอมา (/admin/login) ไม่ใช่ /index.php
          Core\Path::base() จึงคิดว่าระบบติดตั้งใต้ /admin แล้วตัดทิ้ง — route ซ้อนชั้นหาไม่เจอทั้งหมด
          ตั้งให้เหมือน Apache ที่ rewrite เข้า public/index.php */
    $_SERVER['SCRIPT_NAME']     = '/index.php';
    $_SERVER['PHP_SELF']        = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';

    require __DIR__ . '/public/index.php';

    return true;
}

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$port = (int) ($argv[1] ?? 8000);
$root = __DIR__;

echo "เปิดที่ http://localhost:{$port}\n";
echo "หยุดด้วย Ctrl+C\n\n";
echo "‼️ ตัวนี้ใช้บนเครื่องตัวเองเท่านั้น บนเครื่องจริงต้องตั้ง document root ที่ public/\n\n";

passthru(sprintf(
    '%s -S localhost:%d -t %s %s',
    escapeshellarg(PHP_BINARY),
    $port,
    escapeshellarg($root . '/public'),
    escapeshellarg(__FILE__)
));

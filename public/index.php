<?php
/**
 * ประตูเดียวเข้าระบบ — ทุก request ผ่านไฟล์นี้
 *
 * ‼️ document root ต้องชี้ที่โฟลเดอร์ public/ นี้เท่านั้น
 *    ชี้ผิดแล้ว .env, storage/ และซอร์สโค้ดทั้งหมดจะโหลดได้จากเบราว์เซอร์ทันที
 *    ตรวจด้วยการเปิด https://โดเมน/.env ในเบราว์เซอร์ — ต้องเข้าไม่ได้
 */

declare(strict_types=1);

use Core\Auth;
use Core\Database;
use Core\Lang;
use Core\Logger;
use Core\Path;
use Core\Router;
use Core\Security;
use Core\Session;
use Core\Staging;

$root = dirname(__DIR__);

require $root . '/core/autoload.php';
require $root . '/core/helpers.php';

/*
 * ‼️ ต้องโหลดที่นี่ ไม่ใช่ใน layout
 *    เพราะ Controller render "หน้า" ก่อน "layout" เสมอ
 *    ถ้าโหลดใน layout หน้าที่เรียก icon() จะพังทุกหน้า
 *    และตัวจัดการ fatal error ก็ include หน้า 500 ตรงๆ โดยไม่ผ่าน layout เช่นกัน
 */
require $root . '/app/Views/partials/icons.php';

// ----------------------------------------------------------------- ตั้งค่า

$config = require $root . '/config/config.php';
$GLOBALS['__config'] = $config;

// production ห้ามแสดง stack trace ให้ผู้ใช้เห็นเด็ดขาด
ini_set('display_errors', $config['debug'] ? '1' : '0');
error_reporting(E_ALL);

Logger::configure($config['paths']['log'], $config['debug']);
Logger::register();

Database::configure($config['db']);
Security::headers($config['https']);

Session::start($config['session']);
Session::rotateInput();

Lang::configure($config, $config['paths']['lang']);

// ‼️ ต้องอยู่ก่อนทุกอย่างที่แสดงเนื้อหา — staging ห้ามถูก index และต้องใส่รหัสก่อนเข้า
Staging::guard($config);

// ------------------------------------------------------- health check ของ CI
//
// ตอบแค่ว่าระบบขึ้นและต่อฐานข้อมูลได้ไหม ห้ามคืนรายละเอียดอื่น

if (Path::current() === '/_health') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');

    try {
        Database::selectOne('SELECT 1 AS ok');
        echo "ok\n";
    } catch (Throwable $e) {
        Logger::exception($e);
        http_response_code(503);
        echo "db unavailable\n";
    }

    exit;
}

// ------------------------------------------------------------- maintenance

if (is_file($config['paths']['maintenance']) && !str_starts_with(Path::current(), '/_migrate')) {
    http_response_code(503);
    header('Retry-After: 120');
    include $root . '/app/Views/pages/errors/maintenance.php';
    exit;
}

// ------------------------------------------------ route ลับสำหรับ migration
//
// ‼️ นี่คือปลายทางที่แก้โครงสร้างฐานข้อมูลได้ เปิดอยู่บนอินเทอร์เน็ต
//    ต้องปิดกั้นให้แน่นที่สุด — รายละเอียดใน standard/security.md ข้อ 11

if (Path::current() === '/_migrate') {
    $expected = (string) $config['migrate_token'];
    // header ก่อน — token ใน query string ไปค้างใน access log ของ web server
    $given    = (string) ($_SERVER['HTTP_X_MIGRATE_TOKEN'] ?? (isset($_GET['token']) ? $_GET['token'] : ''));

    Logger::warning('migrate_endpoint_called', ['ip' => Auth::ip()]);

    // token ไม่ตรง → ตอบ 404 ไม่ใช่ 403 เพื่อไม่ให้รู้ว่ามี endpoint นี้อยู่
    if ($expected === '' || $given === '' || !hash_equals($expected, $given)) {
        http_response_code(404);
        exit;
    }

    require $root . '/scripts/lib/Migrator.php';

    header('Content-Type: text/plain; charset=utf-8');

    try {
        $done = (new Migrator(Database::pdo(), $root . '/database/migrations'))->run();

        Logger::info('migrate_ok', ['count' => count($done), 'files' => $done]);
        echo $done === [] ? "up-to-date\n" : 'migrated ' . count($done) . "\n";

        /* ‼️ ปิดโหมดปรับปรุงที่นี่ — CI ลบไฟล์บน runner ได้อย่างเดียว ลบบน server ไม่ได้
              ถ้า migration ล้มเหลว ไม่ถึงบรรทัดนี้ ระบบจึงค้างในโหมดปรับปรุงซึ่งปลอดภัยกว่า */
        if (is_file($config['paths']['maintenance'])) {
            // ลบไม่ได้ (เช่นสิทธิ์ไฟล์ของผู้ใช้ FTP) ต้องตอบ 500 ให้ CI รู้ ไม่ใช่ 200 แล้วเว็บค้าง 503
            if (!@unlink($config['paths']['maintenance'])) {
                Logger::warning('maintenance_flag_not_removed');
                http_response_code(500);
                echo "maintenance flag could not be removed\n";
                exit;
            }
            echo "maintenance off\n";
        }
    } catch (Throwable $e) {
        // ห้ามคืนรายละเอียด error ของฐานข้อมูลกลับไปใน response
        $reference = Logger::exception($e);

        http_response_code(500);
        echo "migration failed: {$reference}\n";
    }

    exit;
}

// -------------------------------------------------------------------- CSRF

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'POST' && isset($_POST['_method'])) {
    $spoofed = strtoupper((string) $_POST['_method']);
    $method  = in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true) ? $spoofed : $method;
}

if (!Security::verifyRequest($method)) {
    Logger::warning('csrf_failed', ['path' => Path::current(), 'ip' => Auth::ip()]);

    Session::flash('danger', __('error.csrf'));

    /* กลับไปหน้าที่ส่งฟอร์ม (referer host เดียวกันเท่านั้น — กัน open redirect) · ไม่มี → path เดิม
       ‼️ ฟอร์มสาธารณะ (POST /lead/) ไม่มีหน้า GET ของตัวเอง — ส่งกลับ path เดิมจะได้ 404 */
    $csrfBack  = Path::url(Path::current());
    $csrfParts = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    $csrfHost  = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

    if (is_array($csrfParts) && $csrfHost !== ''
        && strtolower(($csrfParts['host'] ?? '') . (isset($csrfParts['port']) ? ':' . $csrfParts['port'] : '')) === $csrfHost
        && !str_starts_with((string) ($csrfParts['path'] ?? '/'), '//')) {
        $csrfBack = (string) ($csrfParts['path'] ?? '/') . (isset($csrfParts['query']) ? '?' . $csrfParts['query'] : '');
    }

    header('Location: ' . $csrfBack, true, 303);
    exit;
}

// ------------------------------------------------------------------- route

$router = new Router();

require $root . '/config/routes.php';

$router->dispatch();

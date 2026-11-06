<?php
/**
 * ตรวจความพร้อมของเซิร์ฟเวอร์ (CLI — กรณีมี SSH) · ไม่มี SSH ใช้หน้าเว็บแทน:
 *   curl -H "X-Migrate-Token: <MIGRATE_TOKEN>" https://mlmsoftware.omc.co.th/_preflight
 *
 *   php tools/launch/preflight.php          (รันในโฟลเดอร์แอปบนเซิร์ฟเวอร์ — อ่าน .env ของที่นั่น)
 *
 * อ่านอย่างเดียว ไม่พิมพ์ค่าลับ (App\Services\PreflightService) · exit 1 เมื่อมีข้อ FAIL
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = dirname(__DIR__, 2);

require $root . '/core/autoload.php';
require $root . '/core/helpers.php';

/** @var array<string, mixed> $config */
$config = require $root . '/config/config.php';
$GLOBALS['__config'] = $config;
Core\Logger::configure($config['paths']['log'], $config['debug']);
Core\Database::configure($config['db']);

$rows = (new App\Services\PreflightService())->run($config, $root);
echo App\Services\PreflightService::text($rows);
echo "(document root and HTTPS are checked by the web version: /_preflight)\n";

exit(App\Services\PreflightService::verdict($rows) === 'fail' ? 1 : 0);

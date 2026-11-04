<?php
/**
 * ชุดทดสอบ Phase 3 — CMS admin (require จาก tests/run.php ใช้ $http $check $section $pdo $header $stamp ร่วมกัน)
 *
 * ครอบคลุม: ทุก route ใต้ /admin ต้องล็อกอิน + CSRF · สิทธิ์ตามบทบาททุกโมดูล · บังคับเปลี่ยนรหัสผ่าน
 *          · ล็อกการเดารหัส (คู่/IP/username) + cookie อุปกรณ์ (มีเพดาน · หมดอายุ · ถูกยกเลิก · ถูกแก้) · session
 *          · CRUD ทุกโมดูลผ่าน HTTP + audit log · redirect loop/chain · URL เปลี่ยนแล้ว 301 อัตโนมัติ
 *          · คลังสื่อ (WebP + ขนาดย่อย · alt บังคับ · ชนิดไฟล์) · CSV ของ lead · หลายภาษา
 *
 * ‼️ ข้อมูลทดสอบทุกตัวขึ้นต้น zz-test และถูกลบตอนจบ (รวมไฟล์รูปที่สร้าง) · settings ถูกคืนค่าเดิม
 * ‼️ ถ้าตาราง pages/posts/categories/leads ยังไม่มีคอลัมน์ภาษา (migration ของ Phase 4) ชุดนี้เพิ่มให้ชั่วคราวแล้วลบออกตอนจบ
 */

declare(strict_types=1);

/** @var callable $http @var callable $check @var callable $section @var PDO $pdo @var callable $header @var string $stamp @var string $root */

use Core\Database;
use Core\DeviceCookie;

Database::configure($config['db']);
$GLOBALS['__config'] = $config;

$p3 = ['users' => [], 'pages' => [], 'posts' => [], 'categories' => [], 'media' => [], 'redirects' => [], 'faqs' => [],
       'testimonials' => [], 'case_studies' => [], 'leads' => [], 'menu_items' => [], 'menus' => [], 'files' => [], 'backups' => []];
$p3Columns = [];

// ---------------------------------------------------------------- ตั้งค่า

$settingsBefore = $pdo->query('SELECT id, setting_key, value, deleted_at FROM settings')->fetchAll();
$maxMenuId      = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM menus')->fetchColumn();
$maxItemId      = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM menu_items')->fetchColumn();

$hasColumn = static function (string $table, string $column) use ($pdo): bool {
    $q = $pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c');
    $q->execute(['t' => $table, 'c' => $column]);

    return $q->fetchColumn() !== false;
};

// คอลัมน์ภาษาของ Phase 4 — เพิ่มชั่วคราวถ้ายังไม่มี (ชื่อตามที่ตกลงกัน: locale, translation_group, landing_page, referrer)
foreach (['pages' => ['locale', 'translation_group'], 'posts' => ['locale', 'translation_group'],
          'categories' => ['locale', 'translation_group'], 'leads' => ['locale', 'landing_page', 'referrer']] as $table => $columns) {
    foreach ($columns as $column) {
        if (!$hasColumn($table, $column)) {
            $type = match ($column) {
                'locale' => "VARCHAR(10) NOT NULL DEFAULT 'th'",
                'translation_group' => 'VARCHAR(64) NULL',
                default => 'VARCHAR(500) NULL',
            };
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$type}");
            $p3Columns[] = [$table, $column];
        }
    }
}
Database::forgetColumns();

$p3Cleanup = static function () use ($pdo, &$p3, &$p3Columns, $settingsBefore, $maxMenuId, $maxItemId, $root): void {
    foreach (['pages', 'posts', 'categories', 'media', 'redirects', 'faqs', 'testimonials', 'case_studies', 'leads', 'users'] as $table) {
        foreach (array_unique($p3[$table]) as $id) {
            $pdo->prepare("DELETE FROM {$table} WHERE id = :id")->execute(['id' => $id]);
        }
    }
    $pdo->exec("DELETE FROM redirects WHERE from_path LIKE '/zz-test%' OR from_path LIKE '/en/zz-test%' OR from_path LIKE '/2025/%/zz-test%'");
    $pdo->exec("DELETE FROM pages WHERE path LIKE '/zz-test-p3%' OR path LIKE '/en/zz-test-p3%'");
    $pdo->exec("DELETE FROM posts WHERE slug LIKE 'zz-test-p3%'");
    $pdo->exec("DELETE FROM lead_notes WHERE body LIKE 'zz-test%'");
    $pdo->exec("DELETE FROM not_found_log WHERE path LIKE '/zz-test%'");
    $pdo->exec("DELETE FROM login_attempts WHERE username LIKE 'zz-test%' OR ip_address LIKE '10.99.%'");
    $pdo->prepare('DELETE FROM menu_items WHERE id > :id')->execute(['id' => $maxItemId]);
    $pdo->prepare('DELETE FROM menus WHERE id > :id')->execute(['id' => $maxMenuId]);
    // settings คืนค่าเดิมทั้งตาราง
    $pdo->exec('DELETE FROM settings');
    $insert = $pdo->prepare('INSERT INTO settings (id, setting_key, value, created_at, updated_at, deleted_at) VALUES (:id, :k, :v, NOW(), NOW(), :d)');
    foreach ($settingsBefore as $row) {
        $insert->execute(['id' => $row['id'], 'k' => $row['setting_key'], 'v' => $row['value'], 'd' => $row['deleted_at']]);
    }
    foreach ($p3['files'] as $file) {
        @unlink($file);
    }
    foreach ($p3['backups'] as $file) {
        @unlink($root . '/storage/backups/' . basename($file));
    }
    foreach ($p3Columns as [$table, $column]) {
        $pdo->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
    }
    $p3Columns = [];
};
register_shutdown_function($p3Cleanup);

$p3User = static function (string $role, string $suffix, int $mustChange = 0) use ($pdo, $stamp, &$p3): array {
    $username = 'zz-test-p3-' . $suffix . '-' . $stamp;
    $password = 'Pw-' . bin2hex(random_bytes(8));
    $pdo->prepare(
        "INSERT INTO users (username, display_name, password_hash, role, locale, theme, is_active, must_change_password, created_at, updated_at)
         VALUES (:u, :d, :h, :r, 'th', 'light', 1, :m, NOW(), NOW())"
    )->execute(['u' => $username, 'd' => 'ZZ ' . $suffix, 'h' => password_hash($password, PASSWORD_DEFAULT), 'r' => $role, 'm' => $mustChange]);
    $id = (int) $pdo->lastInsertId();
    $p3['users'][] = $id;

    return ['id' => $id, 'username' => $username, 'password' => $password];
};

$token = static function (string $jar, string $path = '/admin/login') use ($http): string {
    $res = $http('GET', $path, ['jar' => $jar]);
    preg_match('/name="_token" value="([^"]+)"/', $res['body'], $m);

    return $m[1] ?? '';
};

$login = static function (array $user, string $jar) use ($http, $token): array {
    return $http('POST', '/admin/login', ['jar' => $jar, 'post' => ['_token' => $token($jar), 'username' => $user['username'], 'password' => $user['password']]]);
};

$loggedIn = static fn (array $res): bool => $res['status'] === 302 && str_ends_with((string) ($res['headers']['location'][0] ?? ''), '/admin');

$admin  = $p3User('admin', 'admin');
$editor = $p3User('editor', 'editor');
$sales  = $p3User('sales', 'sales');

$check($loggedIn($login($admin, 'p3admin')), 'P3: admin logs in');
$check($loggedIn($login($editor, 'p3editor')), 'P3: editor logs in');
$check($loggedIn($login($sales, 'p3sales')), 'P3: sales logs in');

// token ของ session ที่ล็อกอินแล้ว (อ่านจากหน้าในระบบ)
$csrf = static fn (string $jar): string => $token($jar, '/admin/account/password');

$send = static function (string $method, string $path, string $jar, array $fields = []) use ($http, $csrf): array {
    $fields['_token'] = $csrf($jar);
    if ($method !== 'POST') {
        $fields['_method'] = $method;
    }

    return $http('POST', $path, ['jar' => $jar, 'post' => $fields]);
};

$location = static fn (array $res): string => (string) ($res['headers']['location'][0] ?? '');

// ================================================================ 1. ทุก route ใต้ /admin

$section('P3 — every /admin route requires auth (+ CSRF on writes)');

$router = new Core\Router();
(static function () use ($router, $root): void {
    require $root . '/config/routes.php';
})();

$publicAdmin = [
    'GET /admin/login', 'POST /admin/login', 'GET /admin/manual', 'GET /admin/manual/{slug}', 'POST /admin/support',
    // ค่ากำหนดของผู้ชม (ภาษา/โหมดสี) — ไม่อ่านหรือเขียนข้อมูลของระบบ ยังต้องมี CSRF
    'POST /admin/language', 'POST /admin/theme',
];

$sample = static function (string $pattern): string {
    return (string) preg_replace_callback('/\{(\w+)(?::[^}]+)?\}/', static fn (array $m): string => match ($m[1]) {
        'name'  => 'db-20200101-000000.sql',
        'key'   => 'users',
        'slug'  => 'getting-started',
        default => '999999',
    }, $pattern);
};

$adminRoutes = 0;
foreach ($router->all() as $route) {
    if (!str_starts_with($route['pattern'], '/admin')) {
        continue;
    }
    $adminRoutes++;
    $label = $route['method'] . ' ' . $route['pattern'];
    $path  = $sample($route['pattern']);

    if ($route['method'] === 'GET') {
        if (in_array($label, $publicAdmin, true)) {
            continue;
        }
        $res = $http('GET', $path);
        $check($res['status'] === 302 && str_ends_with($location($res), '/admin/login'), "anonymous {$label} → login", $res['status'] . ' ' . $location($res));
        continue;
    }

    /* ไม่มี token → ถูกปฏิเสธเสมอ (แม้เป็นแอดมิน) — public/index.php ตอบ 419 + Location กลับหน้าเดิม
       (PHP เปลี่ยนเป็น 302 เมื่อมี Location) จึงตรวจด้วยข้อความ error.csrf ที่หน้าถัดไปแสดง ไม่ใช่แค่รหัสสถานะ */
    $fields = $route['method'] === 'POST' ? [] : ['_method' => $route['method']];
    // หลักฐานว่าถูกปฏิเสธที่ด่าน CSRF จริง: index.php บันทึก csrf_failed พร้อม path ก่อนถึง router
    $logLine  = 'csrf_failed | {"path":"' . $path . '"';
    $logCount = static fn (): int => substr_count((string) @file_get_contents($root . '/storage/logs/app.log'), $logLine);
    $before   = $logCount();
    $noCsrf   = $http('POST', $path, ['jar' => 'p3admin', 'post' => $fields]);
    $check(in_array($noCsrf['status'], [302, 419], true) && $logCount() === $before + 1 && rtrim($location($noCsrf), '/') === rtrim($path, '/'),
        "{$label} without CSRF → rejected (as admin)", $noCsrf['status'] . ' ' . $location($noCsrf));

    if (in_array($label, $publicAdmin, true)) {
        continue;
    }

    // มี token แต่ไม่ได้ล็อกอิน → ไปหน้า login
    $anonToken = $token('p3anon');
    $res = $http('POST', $path, ['jar' => 'p3anon', 'post' => $fields + ['_token' => $anonToken]]);
    $check($res['status'] === 302 && str_ends_with($location($res), '/admin/login'), "anonymous {$label} (valid CSRF) → login", $res['status'] . ' ' . $location($res));
}
$check($adminRoutes > 60, 'route table enumerated', (string) $adminRoutes);

// ================================================================ 2. สิทธิ์ตามบทบาท

$section('P3 — role isolation per module');

$contentPaths = ['/admin/pages', '/admin/pages/create', '/admin/posts', '/admin/posts/create', '/admin/categories', '/admin/media',
                 '/admin/menus', '/admin/redirects', '/admin/redirects/create', '/admin/not-found', '/admin/faqs', '/admin/testimonials',
                 '/admin/case-studies'];
$leadPaths    = ['/admin/leads', '/admin/leads/export'];
$adminPaths   = ['/admin/users', '/admin/users/create', '/admin/settings', '/admin/backups', '/admin/audit-logs'];

foreach ($contentPaths as $path) {
    $check($http('GET', $path, ['jar' => 'p3admin'])['status'] === 200, "admin GET {$path} → 200");
    $check($http('GET', $path, ['jar' => 'p3editor'])['status'] === 200, "editor GET {$path} → 200");
    $check($http('GET', $path, ['jar' => 'p3sales'])['status'] === 403, "sales GET {$path} → 403");
}
foreach ($leadPaths as $path) {
    $check($http('GET', $path, ['jar' => 'p3admin'])['status'] === 200, "admin GET {$path} → 200");
    $check($http('GET', $path, ['jar' => 'p3sales'])['status'] === 200, "sales GET {$path} → 200");
    $check($http('GET', $path, ['jar' => 'p3editor'])['status'] === 403, "editor GET {$path} → 403");
}
foreach ($adminPaths as $path) {
    $check($http('GET', $path, ['jar' => 'p3admin'])['status'] === 200, "admin GET {$path} → 200");
    $check($http('GET', $path, ['jar' => 'p3editor'])['status'] === 403, "editor GET {$path} → 403");
    $check($http('GET', $path, ['jar' => 'p3sales'])['status'] === 403, "sales GET {$path} → 403");
}
// เขียนข้อมูลข้ามสิทธิ์ก็ต้องโดนกัน ไม่ใช่แค่ GET
$check($send('POST', '/admin/pages', 'p3sales', ['title' => 'x', 'path' => '/zz-test-p3-x/'])['status'] === 403, 'sales POST /admin/pages → 403');
$check($send('PATCH', '/admin/settings', 'p3editor', [])['status'] === 403, 'editor PATCH /admin/settings → 403');
$check($send('POST', '/admin/users', 'p3editor', [])['status'] === 403, 'editor POST /admin/users → 403');
$dash = $http('GET', '/admin', ['jar' => 'p3sales']);
$check($dash['status'] === 200 && !str_contains($dash['body'], '/admin/pages"'), 'sales dashboard has no content links');

// ================================================================ 3. บังคับเปลี่ยนรหัสผ่าน

$section('P3 — forced password change');

$forced = $p3User('editor', 'forced', 1);
$check($loggedIn($login($forced, 'p3forced')), 'forced user can log in');
foreach (['/admin', '/admin/pages', '/admin/manual/getting-started', '/admin/files/1'] as $path) {
    $res = $http('GET', $path, ['jar' => 'p3forced']);
    if ($path === '/admin/manual/getting-started') {
        continue; // คู่มือเปิดได้โดยไม่ล็อกอิน (ไม่อยู่ในกลุ่ม auth)
    }
    $check($res['status'] === 302 && str_ends_with($location($res), '/admin/account/password'), "forced user {$path} → change password", $res['status'] . ' ' . $location($res));
}
$check($http('GET', '/admin/account/password', ['jar' => 'p3forced'])['status'] === 200, 'forced user can open the change-password page');
$bad = $send('PATCH', '/admin/account/password', 'p3forced', ['current_password' => 'wrong', 'password' => 'NewPassword-123', 'password_confirm' => 'NewPassword-123']);
$check($bad['status'] === 302 && !str_ends_with($location($bad), '/admin'), 'wrong current password rejected');
$short = $send('PATCH', '/admin/account/password', 'p3forced', ['current_password' => $forced['password'], 'password' => 'short', 'password_confirm' => 'short']);
$check(!str_ends_with($location($short), '/admin'), 'short password rejected');
$ok = $send('PATCH', '/admin/account/password', 'p3forced', ['current_password' => $forced['password'], 'password' => 'NewPassword-123', 'password_confirm' => 'NewPassword-123']);
$check($ok['status'] === 302 && str_ends_with($location($ok), '/admin'), 'password changed → /admin', $ok['status'] . ' ' . $location($ok));
$check($http('GET', '/admin/pages', ['jar' => 'p3forced'])['status'] === 200, 'after change: admin pages reachable');
$flag = $pdo->prepare('SELECT must_change_password FROM users WHERE id = :id');
$flag->execute(['id' => $forced['id']]);
$check((int) $flag->fetchColumn() === 0, 'must_change_password cleared in DB');

// ================================================================ 4. ล็อกอิน · ล็อก · cookie อุปกรณ์ · session

$section('P3 — authentication, lockout, device cookie, session');

$jarCookie = static function (string $jar, string $name) use (&$cookieJars): string {
    $file = $cookieJars[$jar] ?? '';
    foreach (is_file($file) ? file($file) : [] as $line) {
        $parts = explode("\t", trim($line));
        if (count($parts) >= 7 && $parts[5] === $name) {
            return $parts[6];
        }
    }

    return '';
};

$devUser = $p3User('editor', 'device');
$wrong   = $http('POST', '/admin/login', ['jar' => 'p3dev0', 'post' => ['_token' => $token('p3dev0'), 'username' => $devUser['username'], 'password' => 'nope']]);
$check(!$loggedIn($wrong), 'bad password rejected');
$check($pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action = 'login_failed' AND new_values LIKE '%" . $devUser['username'] . "%'")->fetchColumn() > 0, 'failed login audited');

$beforeSession = '';
$http('GET', '/admin/login', ['jar' => 'p3dev']);
$beforeSession = $jarCookie('p3dev', session_name() ?: 'PHPSESSID');
$good = $login($devUser, 'p3dev');
$check($loggedIn($good), 'good password logs in');
$afterSession = $jarCookie('p3dev', session_name() ?: 'PHPSESSID');
$check($beforeSession !== '' && $afterSession !== '' && $beforeSession !== $afterSession, 'session id regenerated at login (fixation)');

$setCookies = implode("\n", $good['headers']['set-cookie'] ?? []);
$check(str_contains($setCookies, DeviceCookie::NAME . '='), 'device cookie issued at login');
preg_match('/' . DeviceCookie::NAME . '=[^\n]*/', $setCookies, $deviceLine);
$deviceLine = strtolower($deviceLine[0] ?? '');
$check(str_contains($deviceLine, 'httponly') && str_contains($deviceLine, 'samesite=strict'), 'device cookie HttpOnly + SameSite=Strict', $deviceLine);
$check(!str_contains($deviceLine, 'secure'), 'device cookie not Secure over HTTP (follows config https)', $deviceLine);
preg_match('/max-age=(\d+)/', $deviceLine, $maxAge);
$check(isset($maxAge[1]) && (int) $maxAge[1] <= 90 * 86400, 'device cookie lives ≤ 90 days', $maxAge[1] ?? '');
$sessionLine = strtolower((string) preg_replace('/^.*?((?:PHPSESSID|' . preg_quote(session_name() ?: 'PHPSESSID', '/') . ')=[^\n]*).*$/s', '$1', $setCookies));
$check(str_contains($sessionLine, 'httponly') && str_contains($sessionLine, 'samesite=lax'), 'session cookie HttpOnly + SameSite=Lax', $sessionLine);
$deviceValue = rawurldecode($jarCookie('p3dev', DeviceCookie::NAME));
$check(DeviceCookie::validFor($devUser['username'], $deviceValue), 'device cookie validates for its owner');
$check(!DeviceCookie::validFor($editor['username'], $deviceValue), 'device cookie is not valid for another user');

// เพดานต่อ username: 50 ครั้งจาก IP อื่น
$addFailures = static function (string $username, int $count, ?string $ip = null) use ($pdo): void {
    $insert = $pdo->prepare('INSERT INTO login_attempts (username, ip_address, attempted_at) VALUES (:u, :ip, NOW())');
    for ($i = 0; $i < $count; $i++) {
        $insert->execute(['u' => $username, 'ip' => $ip ?? '10.99.' . intdiv($i, 250) . '.' . ($i % 250)]);
    }
};
$addFailures($devUser['username'], 50);
$send('POST', '/admin/logout', 'p3dev');
$check($loggedIn($login($devUser, 'p3dev')), 'user cap reached: known device (valid cookie) still logs in');
$send('POST', '/admin/logout', 'p3dev');
$check(!$loggedIn($login($devUser, 'p3fresh')), 'user cap reached: new device is throttled');

$tampered = substr($deviceValue, 0, -1) . (str_ends_with($deviceValue, 'a') ? 'b' : 'a');
$check(!DeviceCookie::validFor($devUser['username'], $tampered), 'tampered device cookie rejected');
$tamperRes = $http('POST', '/admin/login', ['jar' => 'p3tamper', 'headers' => ['Cookie: ' . DeviceCookie::NAME . '=' . rawurlencode($tampered)],
    'post' => ['_token' => $token('p3tamper'), 'username' => $devUser['username'], 'password' => $devUser['password']]]);
$check(!$loggedIn($tamperRes), 'tampered cookie gives no exemption (HTTP)');

$row = $pdo->prepare('SELECT id, password_hash, role, is_active, device_token_version FROM users WHERE id = :id');
$row->execute(['id' => $devUser['id']]);
$devRow = $row->fetch();
$expired = DeviceCookie::make($devRow, time() - (DeviceCookie::MAX_DAYS * 86400 + 60));
$check($expired !== '' && !DeviceCookie::validFor($devUser['username'], $expired), 'expired device cookie (>90 days) rejected');

// เพดานของอุปกรณ์ที่รู้จัก — ไม่ไม่จำกัด
$addFailures($devUser['username'], 160);
$check(!$loggedIn($login($devUser, 'p3dev')), 'known device is throttled too after 200 failures (bounded exemption)');
$pdo->prepare('DELETE FROM login_attempts WHERE username = :u')->execute(['u' => $devUser['username']]);

// ยกเลิกเมื่อเปลี่ยนรหัสผ่าน / ปิดบัญชี / เปลี่ยนบทบาท / ออกจากทุกอุปกรณ์
$pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')->execute(['h' => password_hash('Changed-Pass-123', PASSWORD_DEFAULT), 'id' => $devUser['id']]);
$check(!DeviceCookie::validFor($devUser['username'], $deviceValue), 'device cookie revoked by password change');
$pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')->execute(['h' => $devRow['password_hash'], 'id' => $devUser['id']]);
$check(DeviceCookie::validFor($devUser['username'], $deviceValue), '(control) same credentials → valid again');
$pdo->prepare('UPDATE users SET is_active = 0 WHERE id = :id')->execute(['id' => $devUser['id']]);
$check(!DeviceCookie::validFor($devUser['username'], $deviceValue), 'device cookie revoked by deactivation');
$pdo->prepare('UPDATE users SET is_active = 1, role = :r WHERE id = :id')->execute(['r' => 'sales', 'id' => $devUser['id']]);
$check(!DeviceCookie::validFor($devUser['username'], $deviceValue), 'device cookie revoked by role change');
$pdo->prepare('UPDATE users SET role = :r WHERE id = :id')->execute(['r' => 'editor', 'id' => $devUser['id']]);
$devices = $send('POST', '/admin/users/' . $devUser['id'] . '/devices', 'p3admin');
$check($devices['status'] === 302, 'admin: forget all devices');
$check(!DeviceCookie::validFor($devUser['username'], $deviceValue), 'device cookie revoked by "forget all devices"');

// ล็อกคู่ username + IP (5 ครั้ง) และต่อ IP (20 ครั้ง)
$pairUser = $p3User('editor', 'pair');
$addFailures($pairUser['username'], 5, '127.0.0.1');
$check(!$loggedIn($login($pairUser, 'p3pair')), 'pair lock: 5 failures from this IP → throttled even with the right password');
$pdo->prepare('DELETE FROM login_attempts WHERE username = :u')->execute(['u' => $pairUser['username']]);
for ($i = 0; $i < 20; $i++) {
    $addFailures('zz-test-ipcap-' . $i, 1, '127.0.0.1');
}
$check(!$loggedIn($login($pairUser, 'p3pair')), 'IP cap: 20 failures from this IP over many usernames → throttled');
$pdo->exec("DELETE FROM login_attempts WHERE username LIKE 'zz-test-ipcap-%'");
$check($loggedIn($login($pairUser, 'p3pair')), '(control) after the window clears → login works');

// session ถูกยกเลิกเมื่อบัญชีถูกปิด
$pdo->prepare('UPDATE users SET is_active = 0 WHERE id = :id')->execute(['id' => $pairUser['id']]);
$revoked = $http('GET', '/admin', ['jar' => 'p3pair']);
$check($revoked['status'] === 302 && str_ends_with($location($revoked), '/admin/login'), 'deactivated user is logged out on the next request');

// ================================================================ 5. หน้า (pages)

$section('P3 — pages: CRUD, blocks, SEO, preview, revisions, 301, translations');

$pagePath = '/zz-test-p3-page-' . $stamp . '/';
$noTitle  = $send('POST', '/admin/pages', 'p3editor', ['title' => '', 'path' => $pagePath, 'status' => 'draft']);
$check($noTitle['status'] === 302 && !preg_match('#/admin/pages/\d+$#', $location($noTitle)), 'page without title rejected');
$badPath = $send('POST', '/admin/pages', 'p3editor', ['title' => 'x', 'path' => '/en/zz-test-p3-th/', 'locale' => 'th', 'status' => 'draft']);
$check(!preg_match('#/admin/pages/\d+$#', $location($badPath)), 'th page under /en/ rejected (path must match locale)');

$created = $send('POST', '/admin/pages', 'p3editor', [
    'title' => 'ZZ หน้าทดสอบ', 'path' => $pagePath, 'template' => 'default', 'status' => 'draft', 'robots' => 'index', 'locale' => 'th',
    'seo_title' => 'SEO ทดสอบ', 'meta_description' => 'คำอธิบายทดสอบ',
    'blocks' => [
        ['type' => 'hero', 'sort' => '20', 'data' => ['title' => "หัวข้อ\nบรรทัดสอง", 'bullets' => "ข้อ 1\nข้อ 2",
            'actions' => [['label' => 'ขอ Demo', 'url' => '/demo/', 'style' => 'primary'], ['label' => '', 'url' => '', 'style' => 'primary']]]],
        ['type' => 'faq', 'sort' => '10', 'data' => ['title' => 'คำถาม', 'items' => [['q' => 'ถามอะไร?', 'a' => 'ตอบนี่'], ['q' => '', 'a' => '']]]],
        ['type' => 'stats', 'sort' => '30', 'data' => []],
        ['type' => 'nonsense', 'data' => ['x' => 1]],
        ['type' => 'cta_band', 'sort' => '40', 'remove' => '1', 'data' => ['title' => 'removed']],
    ],
]);
preg_match('#/admin/pages/(\d+)$#', $location($created), $m);
$pageId = (int) ($m[1] ?? 0);
$p3['pages'][] = $pageId;
$check($pageId > 0, 'editor creates a page', $location($created));
$pageRow = $pdo->prepare('SELECT * FROM pages WHERE id = :id');
$pageRow->execute(['id' => $pageId]);
$page = $pageRow->fetch() ?: [];
$blocks = json_decode((string) ($page['blocks'] ?? '[]'), true);
$check(array_column((array) $blocks, 'type') === ['faq', 'hero', 'stats'], 'blocks ordered by sort, unknown/removed dropped', json_encode(array_column((array) $blocks, 'type')));
$check(($blocks[1]['data']['title'] ?? '') === "หัวข้อ\nบรรทัดสอง" && ($blocks[1]['data']['bullets'] ?? []) === ['ข้อ 1', 'ข้อ 2'], 'hero keys match the partial (title, bullets)');
$check(count($blocks[1]['data']['actions'] ?? []) === 1 && ($blocks[1]['data']['actions'][0] ?? []) === ['label' => 'ขอ Demo', 'url' => '/demo/', 'style' => 'primary'], 'empty repeatable rows dropped; action keys label/url/style');
$check(($blocks[0]['data']['items'] ?? []) === [['q' => 'ถามอะไร?', 'a' => 'ตอบนี่']], 'faq items use q/a (partial keys)');
$check(($blocks[2]['data'] ?? null) === [], 'stats block stores no numbers (verified settings only)');
$check(($page['locale'] ?? '') === 'th', 'page saved with locale');

$editForm = $http('GET', '/admin/pages/' . $pageId, ['jar' => 'p3editor']);
$check($editForm['status'] === 200 && str_contains($editForm['body'], 'data-block-builder') && str_contains($editForm['body'], 'data-snippet-preview'), 'edit page renders block builder + SEO preview');
$check(str_contains($editForm['body'], e('ถามอะไร?')), 'edit form shows saved block values');

$preview = $http('GET', $enc($pagePath) . '?preview=1', ['jar' => 'p3editor']);
$check($preview['status'] === 200 && str_contains($preview['body'], 'ถามอะไร?'), 'draft preview (?preview=1) renders the blocks');
$check($http('GET', $enc($pagePath))['status'] === 404, 'draft page is 404 for the public');

// เผยแพร่ แล้วเปลี่ยน path → 301 อัตโนมัติ
$base = ['title' => 'ZZ หน้าทดสอบ', 'template' => 'default', 'robots' => 'index', 'locale' => 'th', 'blocks' => $blocks];
$send('PATCH', '/admin/pages/' . $pageId, 'p3editor', array_merge($base, ['path' => $pagePath, 'status' => 'published']));
$check($http('GET', $enc($pagePath))['status'] === 200, 'published page is public');
$newPath = '/zz-test-p3-moved-' . $stamp . '/';
$moved = $send('PATCH', '/admin/pages/' . $pageId, 'p3editor', array_merge($base, ['path' => $newPath, 'status' => 'published', 'redirect_old_path' => '1']));
$check($moved['status'] === 302, 'path change saved');
$old = $http('GET', $enc($pagePath));
$check($old['status'] === 301 && str_contains(rawurldecode($location($old)), $newPath), 'old path 301 → new path', $old['status'] . ' ' . rawurldecode($location($old)));

// revisions
$revs = $pdo->prepare("SELECT id FROM revisions WHERE entity = 'page' AND entity_id = :id ORDER BY id");
$revs->execute(['id' => $pageId]);
$revIds = $revs->fetchAll(PDO::FETCH_COLUMN);
$check(count($revIds) >= 2, 'revision saved on every edit', (string) count($revIds));
$check($http('GET', '/admin/pages/' . $pageId . '/revisions/' . $revIds[0], ['jar' => 'p3editor'])['status'] === 200, 'revision view renders');
$send('PATCH', '/admin/pages/' . $pageId, 'p3editor', array_merge($base, ['title' => 'ZZ เปลี่ยนชื่อ', 'path' => $newPath, 'status' => 'published']));
$restore = $send('POST', '/admin/pages/' . $pageId . '/revisions/' . $revIds[0] . '/restore', 'p3editor');
$pageRow->execute(['id' => $pageId]);
$check($restore['status'] === 302 && ($pageRow->fetch()['title'] ?? '') === 'ZZ หน้าทดสอบ', 'restore brings the old title back');

// คำแปล
$translate = $send('POST', '/admin/pages/' . $pageId . '/translations', 'p3editor', ['locale' => 'en']);
preg_match('#/admin/pages/(\d+)$#', $location($translate), $m);
$enId = (int) ($m[1] ?? 0);
$p3['pages'][] = $enId;
$pageRow->execute(['id' => $enId]);
$enPage = $pageRow->fetch() ?: [];
$check($enId > 0 && $enId !== $pageId && ($enPage['path'] ?? '') === '/en' . $newPath && ($enPage['status'] ?? '') === 'draft' && ($enPage['locale'] ?? '') === 'en',
    'create translation: /en/ + same slug, draft, en', json_encode([$enPage['path'] ?? null, $enPage['status'] ?? null]));
$pageRow->execute(['id' => $pageId]);
$check(($pageRow->fetch()['translation_group'] ?? '') === ($enPage['translation_group'] ?? 'x'), 'translation shares the group');
$list = $http('GET', '/admin/pages?q=' . rawurlencode('zz-test-p3-moved'), ['jar' => 'p3editor']);
$check(str_contains($list['body'], '⚠ ZH'), 'list flags missing translations');
$filtered = $http('GET', '/admin/pages?locale=en&q=' . rawurlencode('zz-test-p3-moved'), ['jar' => 'p3editor']);
$check(str_contains($filtered['body'], '/en' . $newPath) && !str_contains($filtered['body'], '>' . $newPath), 'list filters by locale');

$del = $send('DELETE', '/admin/pages/' . $enId, 'p3editor');
$soft = $pdo->prepare('SELECT deleted_at FROM pages WHERE id = :id');
$soft->execute(['id' => $enId]);
$check($del['status'] === 302 && $soft->fetchColumn() !== null, 'page soft-deleted');
$audit = $pdo->prepare("SELECT action FROM audit_logs WHERE table_name = 'pages' AND record_id = :id ORDER BY id");
$audit->execute(['id' => $pageId]);
$actions = $audit->fetchAll(PDO::FETCH_COLUMN);
$check(in_array('create', $actions, true) && in_array('update', $actions, true), 'page create/update audited');
$audit->execute(['id' => $enId]);
$check(in_array('delete', $audit->fetchAll(PDO::FETCH_COLUMN), true), 'page delete audited');

// ================================================================ 6. บทความ

$section('P3 — posts: sanitize on save, 301 on slug change');

$slug = 'zz-test-p3-บทความ-' . $stamp;
$post = $send('POST', '/admin/posts', 'p3editor', [
    'title' => 'ZZ บทความ', 'slug' => $slug, 'status' => 'published', 'published_at' => '2025-03-04T10:00', 'robots' => 'index', 'locale' => 'th',
    'content' => '<h1>หัว</h1><p onclick="x()">ย่อหน้า</p><script>alert(1)</script><a href="javascript:alert(1)">ลิงก์</a>',
]);
preg_match('#/admin/posts/(\d+)$#', $location($post), $m);
$postId = (int) ($m[1] ?? 0);
$p3['posts'][] = $postId;
$check($postId > 0, 'editor creates a post');
$postRow = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
$postRow->execute(['id' => $postId]);
$saved = (string) ($postRow->fetch()['content'] ?? '');
$check(!str_contains($saved, '<script') && !str_contains($saved, 'onclick') && !str_contains($saved, 'javascript:') && !str_contains($saved, '<h1'),
    'content sanitized on save (no script/on*/javascript:/h1)', $saved);
$postUrl = '/2025/03/04/' . $slug . '/';
$check($http('GET', $enc($postUrl))['status'] === 200, 'published post at /Y/m/d/slug/');
$send('PATCH', '/admin/posts/' . $postId, 'p3editor', ['title' => 'ZZ บทความ', 'slug' => $slug . '-x', 'status' => 'published',
    'published_at' => '2025-03-04T10:00', 'robots' => 'index', 'locale' => 'th', 'content' => '<p>a</p>', 'redirect_old_path' => '1']);
$oldPost = $http('GET', $enc($postUrl));
$check($oldPost['status'] === 301 && str_contains(rawurldecode($location($oldPost)), $slug . '-x/'), 'slug change → 301 from old URL');
$check($http('GET', '/admin/posts/' . $postId, ['jar' => 'p3editor'])['status'] === 200 && str_contains($http('GET', '/admin/posts/' . $postId, ['jar' => 'p3editor'])['body'], 'data-html-editor'), 'post edit renders the HTML editor');
$trPost = $send('POST', '/admin/posts/' . $postId . '/translations', 'p3editor', ['locale' => 'en']);
$check($trPost['status'] === 302, 'post translation does not crash (created or explained)', (string) $trPost['status']);
if (preg_match('#/admin/posts/(\d+)$#', $location($trPost), $m) === 1 && (int) $m[1] !== $postId) {
    $p3['posts'][] = (int) $m[1];
}

// ================================================================ 7. หมวด

$section('P3 — categories');

$cat = $send('POST', '/admin/categories', 'p3editor', ['name' => 'ZZ หมวด', 'slug' => 'zz-test-p3-cat-' . $stamp, 'robots' => 'index', 'locale' => 'th', 'sort' => '5']);
preg_match('#/admin/categories/(\d+)$#', $location($cat), $m);
$catId = (int) ($m[1] ?? 0);
$p3['categories'][] = $catId;
$check($catId > 0, 'category created');
$dup = $send('POST', '/admin/categories', 'p3editor', ['name' => 'ZZ dup', 'slug' => 'zz-test-p3-cat-' . $stamp, 'robots' => 'index', 'locale' => 'th']);
$check(!preg_match('#/admin/categories/\d+$#', $location($dup)), 'duplicate category slug rejected');
$send('PATCH', '/admin/categories/' . $catId, 'p3editor', ['name' => 'ZZ หมวด 2', 'slug' => 'zz-test-p3-cat-' . $stamp, 'robots' => 'noindex', 'locale' => 'th']);
$catRow = $pdo->prepare('SELECT name, robots, deleted_at FROM categories WHERE id = :id');
$catRow->execute(['id' => $catId]);
$check(($catRow->fetch()['robots'] ?? '') === 'noindex', 'category updated');
$send('DELETE', '/admin/categories/' . $catId, 'p3editor');
$catRow->execute(['id' => $catId]);
$check($catRow->fetch()['deleted_at'] !== null, 'empty category deleted');

// ================================================================ 8. คลังสื่อ

$section('P3 — media library');

$tmpDir = sys_get_temp_dir();
$png    = $tmpDir . '/zz-test-p3-' . $stamp . '.png';
$img    = imagecreatetruecolor(1600, 900);
imagefill($img, 0, 0, imagecolorallocate($img, 30, 60, 200));
imagepng($img, $png);
imagedestroy($img);
$fake = $tmpDir . '/zz-test-p3-' . $stamp . '.jpg';
file_put_contents($fake, '<?php echo 1;');
$svg = $tmpDir . '/zz-test-p3-' . $stamp . '.svg';
file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

$upload = static function (string $file, string $alt, string $mime) use ($http, $csrf): array {
    return $http('POST', '/admin/media', ['jar' => 'p3editor', 'multipart' => [
        '_token' => $csrf('p3editor'), 'alt' => $alt, 'title' => 'ZZ', 'file' => new CURLFile($file, $mime, basename($file)),
    ]]);
};
$noAlt = $upload($png, '', 'image/png');
$check(!preg_match('#/admin/media/\d+$#', $location($noAlt)), 'upload without alt rejected');
$badType = $upload($fake, 'alt', 'image/jpeg');
$check(!preg_match('#/admin/media/\d+$#', $location($badType)), 'non-image disguised as .jpg rejected');
$badSvg = $upload($svg, 'alt', 'image/svg+xml');
$check(!preg_match('#/admin/media/\d+$#', $location($badSvg)), 'SVG rejected');
$up = $upload($png, 'รูปทดสอบ', 'image/png');
preg_match('#/admin/media/(\d+)$#', $location($up), $m);
$mediaId = (int) ($m[1] ?? 0);
$p3['media'][] = $mediaId;
$check($mediaId > 0, 'PNG with alt uploaded', $location($up));
$mediaRow = $pdo->prepare('SELECT * FROM media WHERE id = :id');
$mediaRow->execute(['id' => $mediaId]);
$media = $mediaRow->fetch() ?: [];
$sizes = json_decode((string) ($media['sizes'] ?? ''), true) ?: [];
$check(str_starts_with((string) ($media['path'] ?? ''), '/uploads/' . date('Y/m') . '/') && (int) $media['width'] === 1600 && (int) $media['height'] === 900,
    'stored in /uploads/YYYY/MM with width/height', (string) ($media['path'] ?? ''));
$check(isset($sizes['webp'], $sizes['480'], $sizes['768'], $sizes['1200']) && !isset($sizes['1600']), 'WebP + 480/768/1200 variants (no upscale)', json_encode($sizes));
foreach (array_merge([(string) ($media['path'] ?? '')], array_values($sizes)) as $url) {
    $p3['files'][] = $root . '/public' . $url;
}
$check(is_file($root . '/public' . ($sizes['webp'] ?? '/none')) && getimagesize($root . '/public' . $sizes['webp'])['mime'] === 'image/webp', 'WebP file exists');
$check(getimagesize($root . '/public' . ($sizes['480'] ?? '/none'))[0] === 480, '480 variant is 480px wide');
$check($http('GET', '/admin/media', ['jar' => 'p3editor'])['status'] === 200, 'media grid renders');

// ใช้รูปในหน้า → ลบไม่ได้
$send('PATCH', '/admin/pages/' . $pageId, 'p3editor', array_merge($base, ['path' => $newPath, 'status' => 'published',
    'blocks' => [['type' => 'split', 'data' => ['title' => 'x', 'image' => ['media_id' => (string) $mediaId]]]]]));
$pageRow->execute(['id' => $pageId]);
$withImage = json_decode((string) $pageRow->fetch()['blocks'], true);
$check(($withImage[0]['data']['image'] ?? []) === ['src' => $sizes['webp'], 'alt' => 'รูปทดสอบ', 'width' => 1600, 'height' => 900], 'image field stores src/alt/width/height', json_encode($withImage[0]['data']['image'] ?? null));
$blocked = $send('DELETE', '/admin/media/' . $mediaId, 'p3editor');
$mediaRow->execute(['id' => $mediaId]);
$check($mediaRow->fetch()['deleted_at'] === null, 'media in use cannot be deleted');
$check(str_contains($http('GET', '/admin/media/' . $mediaId, ['jar' => 'p3editor'])['body'], '/admin/pages/' . $pageId), 'media page lists where it is used');
$send('PATCH', '/admin/media/' . $mediaId, 'p3editor', ['alt' => '', 'title' => 'x']);
$mediaRow->execute(['id' => $mediaId]);
$check($mediaRow->fetch()['alt'] === 'รูปทดสอบ', 'clearing alt rejected on edit');
$send('PATCH', '/admin/pages/' . $pageId, 'p3editor', array_merge($base, ['path' => $newPath, 'status' => 'published', 'blocks' => []]));
$usageBefore = json_encode((new App\Services\MediaService())->usage($mediaId), JSON_UNESCAPED_UNICODE);
$mediaDelete = $send('DELETE', '/admin/media/' . $mediaId, 'p3editor');
$mediaRow->execute(['id' => $mediaId]);
$mediaDeletedAt = $mediaRow->fetch()['deleted_at'];
clearstatcache(); // ไฟล์ถูกลบโดยโปรเซสของ server — cache ของ is_file ในโปรเซสนี้ยังจำว่ามีอยู่
$check($mediaDeletedAt !== null && !is_file($root . '/public' . $sizes['webp']) && !is_file($root . '/public' . $media['path']),
    'unused media deleted with its files', $usageBefore . ' ' . $mediaDelete['status']);
foreach ([$png, $fake, $svg] as $file) {
    @unlink($file);
}
$legacyBefore = (int) $pdo->query('SELECT COUNT(*) FROM media WHERE deleted_at IS NULL')->fetchColumn();
$import = $send('POST', '/admin/media/import', 'p3editor');
$legacyAfter = (int) $pdo->query('SELECT COUNT(*) FROM media WHERE deleted_at IS NULL')->fetchColumn();
$check($import['status'] === 302 && $legacyAfter >= $legacyBefore, 'register legacy images runs (idempotent, files not moved)');
$importAgain = $send('POST', '/admin/media/import', 'p3editor');
$check((int) $pdo->query('SELECT COUNT(*) FROM media WHERE deleted_at IS NULL')->fetchColumn() === $legacyAfter, 'second import adds nothing');
foreach ($pdo->query("SELECT id FROM media WHERE alt = '' AND created_at >= NOW() - INTERVAL 1 HOUR AND path LIKE '/wp-content/%'")->fetchAll(PDO::FETCH_COLUMN) as $id) {
    $p3['media'][] = (int) $id; // ลบแถวที่ลงทะเบียนระหว่างทดสอบ (ไฟล์ไม่ถูกแตะ)
}

// ================================================================ 9. เมนู

$section('P3 — menus + SiteService reads DB');

$menuImport = $send('POST', '/admin/menus/import?location=footer&locale=zh', 'p3editor');
$items = $pdo->query("SELECT mi.id FROM menu_items mi JOIN menus m ON m.id = mi.menu_id WHERE m.location = 'footer' AND m.locale = 'zh' AND mi.deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
$check($menuImport['status'] === 302 && count($items) > 5, 'copy default footer menu (zh) into DB', (string) count($items));
$addItem = $send('POST', '/admin/menus/items?location=header&locale=zh', 'p3editor', ['label' => 'ZZ เมนู', 'url' => '/zz-test-menu/']);
$addItem2 = $send('POST', '/admin/menus/items?location=header&locale=zh', 'p3editor', ['label' => 'ZZ เมนู 2', 'url' => '/zz-test-menu-2/']);
$headerItems = $pdo->query("SELECT mi.id, mi.label FROM menu_items mi JOIN menus m ON m.id = mi.menu_id WHERE m.location = 'header' AND m.locale = 'zh' AND mi.deleted_at IS NULL ORDER BY mi.sort, mi.id")->fetchAll();
$check(count($headerItems) === 2, 'menu items added (zh header)');
$badItem = $send('POST', '/admin/menus/items?location=header&locale=zh', 'p3editor', ['label' => 'x', 'url' => 'javascript:alert(1)']);
$check(count($pdo->query("SELECT mi.id FROM menu_items mi JOIN menus m ON m.id = mi.menu_id WHERE m.locale = 'zh' AND m.location = 'header' AND mi.deleted_at IS NULL")->fetchAll()) === 2, 'javascript: URL rejected');
$send('POST', '/admin/menus/items/' . $headerItems[1]['id'] . '/move', 'p3editor', ['direction' => 'up']);
$layout = App\Services\SiteService::layout('/zh/', 'zh');
$check(array_column($layout['nav'], 'label') === ['ZZ เมนู 2', 'ZZ เมนู'], 'SiteService uses the DB menu (moved order)', json_encode(array_column($layout['nav'], 'label'), JSON_UNESCAPED_UNICODE));
$check(($layout['footer']['columns'] ?? []) !== [] && ($layout['footer']['legal'] ?? []) !== [], 'DB footer → columns + legal');
$thLayout = App\Services\SiteService::layout('/', 'th');
$check(count($thLayout['nav']) > 0, 'other locale without DB menu falls back to config');
$send('PATCH', '/admin/menus/items/' . $headerItems[0]['id'], 'p3editor', ['label' => 'ZZ แก้แล้ว', 'url' => '/zz-test-menu/']);
$check($pdo->query('SELECT label FROM menu_items WHERE id = ' . (int) $headerItems[0]['id'])->fetchColumn() === 'ZZ แก้แล้ว', 'menu item edited');
$send('DELETE', '/admin/menus/items/' . $headerItems[0]['id'], 'p3editor');
$check($pdo->query('SELECT deleted_at FROM menu_items WHERE id = ' . (int) $headerItems[0]['id'])->fetchColumn() !== null, 'menu item deleted');

// ================================================================ 10. redirect + 404

$section('P3 — redirects (loop/chain) + 404 log');

$r1 = $send('POST', '/admin/redirects', 'p3editor', ['from_path' => '/zz-test-a/', 'to_path' => '/zz-test-b/', 'code' => '301', 'match_type' => 'exact', 'enabled' => '1']);
preg_match('#/admin/redirects/(\d+)$#', $location($r1), $m);
$p3['redirects'][] = (int) ($m[1] ?? 0);
$check(($m[1] ?? 0) > 0, 'redirect A → B created');
$loop = $send('POST', '/admin/redirects', 'p3editor', ['from_path' => '/zz-test-b/', 'to_path' => '/zz-test-a/', 'code' => '301', 'match_type' => 'exact', 'enabled' => '1']);
$check(!preg_match('#/admin/redirects/\d+$#', $location($loop)), 'B → A rejected (loop)');
$self = $send('POST', '/admin/redirects', 'p3editor', ['from_path' => '/zz-test-c/', 'to_path' => '/zz-test-c/', 'code' => '301', 'match_type' => 'exact', 'enabled' => '1']);
$check(!preg_match('#/admin/redirects/\d+$#', $location($self)), 'C → C rejected (loop)');
$chain = $send('POST', '/admin/redirects', 'p3editor', ['from_path' => '/zz-test-c/', 'to_path' => '/zz-test-a/', 'code' => '301', 'match_type' => 'exact', 'enabled' => '1']);
$check(!preg_match('#/admin/redirects/\d+$#', $location($chain)), 'C → A rejected (chain, A redirects again)');
$resolved = $send('POST', '/admin/redirects', 'p3editor', ['from_path' => '/zz-test-c/', 'to_path' => '/zz-test-a/', 'code' => '301', 'match_type' => 'exact', 'enabled' => '1', 'resolve_chain' => '1']);
preg_match('#/admin/redirects/(\d+)$#', $location($resolved), $m);
$p3['redirects'][] = (int) ($m[1] ?? 0);
$check(($m[1] ?? 0) > 0 && $pdo->query('SELECT to_path FROM redirects WHERE id = ' . (int) ($m[1] ?? 0))->fetchColumn() === '/zz-test-b/', 'resolve_chain points C to the final target B');
$noSlash = $send('POST', '/admin/redirects', 'p3editor', ['from_path' => 'zz-test-d/', 'to_path' => '/', 'code' => '301', 'match_type' => 'exact', 'enabled' => '1']);
$check(!preg_match('#/admin/redirects/\d+$#', $location($noSlash)), 'from without leading / rejected');
$gone = $send('POST', '/admin/redirects', 'p3editor', ['from_path' => '/zz-test-gone/', 'code' => '410', 'match_type' => 'exact', 'enabled' => '1']);
preg_match('#/admin/redirects/(\d+)$#', $location($gone), $m);
$p3['redirects'][] = (int) ($m[1] ?? 0);
$check($http('GET', '/zz-test-gone/')['status'] === 410, '410 rule works');
$send('PATCH', '/admin/redirects/' . (int) ($m[1] ?? 0), 'p3editor', ['from_path' => '/zz-test-gone/', 'code' => '410', 'match_type' => 'exact']);
$check($http('GET', '/zz-test-gone/')['status'] === 404, 'disabled rule (edit) stops applying');

$http('GET', '/zz-test-404-' . $stamp . '/', ['headers' => ['Referer: https://example.com/from']]);
$http('GET', '/zz-test-404-' . $stamp . '/');
$nf = $http('GET', '/admin/not-found?q=' . rawurlencode('zz-test-404'), ['jar' => 'p3editor']);
$check($nf['status'] === 200 && str_contains($nf['body'], 'zz-test-404-' . $stamp) && str_contains($nf['body'], '/admin/redirects/create?from='), '404 log lists the path with a create-redirect link');
$nfId = (int) $pdo->query("SELECT id FROM not_found_log WHERE path = '/zz-test-404-{$stamp}/'")->fetchColumn();
$check($http('GET', '/admin/redirects/create?from=' . rawurlencode('/zz-test-404-' . $stamp . '/'), ['jar' => 'p3editor'])['status'] === 200, 'create-redirect form prefilled from 404');
$send('DELETE', '/admin/not-found/' . $nfId, 'p3editor');
$check($pdo->query('SELECT deleted_at FROM not_found_log WHERE id = ' . $nfId)->fetchColumn() !== null, '404 row ignored');

// ================================================================ 11. FAQ · testimonials · case studies

$section('P3 — FAQ, testimonials, case studies (consent)');

$faq = $send('POST', '/admin/faqs', 'p3editor', ['question' => 'ZZ ถาม', 'answer' => 'ZZ ตอบ', 'group_key' => 'zz-test', 'locale' => 'en', 'sort' => '1', 'status' => 'published']);
preg_match('#/admin/faqs/(\d+)$#', $location($faq), $m);
$p3['faqs'][] = (int) ($m[1] ?? 0);
$check(($m[1] ?? 0) > 0 && $pdo->query('SELECT locale FROM faqs WHERE id = ' . (int) $m[1])->fetchColumn() === 'en', 'FAQ created with locale');
$badFaq = $send('POST', '/admin/faqs', 'p3editor', ['question' => '', 'answer' => 'x', 'group_key' => 'Bad Group', 'status' => 'draft']);
$check(!preg_match('#/admin/faqs/\d+$#', $location($badFaq)), 'invalid FAQ rejected');
$send('DELETE', '/admin/faqs/' . (int) ($m[1] ?? 0), 'p3editor');

$noConsent = $send('POST', '/admin/testimonials', 'p3editor', ['person_name' => 'ZZ คน', 'quote' => 'ดีมาก', 'status' => 'published']);
$check(!preg_match('#/admin/testimonials/\d+$#', $location($noConsent)), 'publishing a testimonial without permission is rejected');
$withConsent = $send('POST', '/admin/testimonials', 'p3editor', ['person_name' => 'ZZ คน', 'quote' => 'ดีมาก', 'status' => 'published', 'consent' => '1']);
preg_match('#/admin/testimonials/(\d+)$#', $location($withConsent), $m);
$tId = (int) ($m[1] ?? 0);
$p3['testimonials'][] = $tId;
$check($tId > 0 && $pdo->query('SELECT consent_confirmed_by FROM testimonials WHERE id = ' . $tId)->fetchColumn() == $editor['id'], 'published with permission records who confirmed');
$names = array_column((new App\Services\SocialProofService())->testimonialItems(), 'name');
$check(in_array('ZZ คน', $names, true), 'testimonial block shows the permitted testimonial');
$pdo->exec('UPDATE testimonials SET consent_confirmed_at = NULL WHERE id = ' . $tId);
$check(!in_array('ZZ คน', array_column((new App\Services\SocialProofService())->testimonialItems(), 'name'), true), 'published row without permission never shows');
$unpublish = $send('PATCH', '/admin/testimonials/' . $tId, 'p3editor', ['person_name' => 'ZZ คน', 'quote' => 'ดีมาก', 'status' => 'published']);
$check($pdo->query('SELECT status FROM testimonials WHERE id = ' . $tId)->fetchColumn() === 'published' && !str_ends_with($location($unpublish), '/admin/testimonials/' . $tId), 'edit to published without permission rejected');
$send('DELETE', '/admin/testimonials/' . $tId, 'p3editor');

$cs = $send('POST', '/admin/case-studies', 'p3editor', ['title' => 'ZZ ผลงาน', 'slug' => 'zz-test-p3-cs-' . $stamp, 'status' => 'draft',
    'content' => '<p>ok</p><script>x</script>']);
preg_match('#/admin/case-studies/(\d+)$#', $location($cs), $m);
$csId = (int) ($m[1] ?? 0);
$p3['case_studies'][] = $csId;
$check($csId > 0 && !str_contains((string) $pdo->query('SELECT content FROM case_studies WHERE id = ' . $csId)->fetchColumn(), '<script'), 'case study created (content sanitized)');
$csDup = $send('POST', '/admin/case-studies', 'p3editor', ['title' => 'ZZ dup', 'slug' => 'zz-test-p3-cs-' . $stamp, 'status' => 'draft']);
$check(!preg_match('#/admin/case-studies/\d+$#', $location($csDup)), 'duplicate case study slug rejected (no DB error)', $csDup['status'] . ' ' . $location($csDup));
$csPub = $send('PATCH', '/admin/case-studies/' . $csId, 'p3editor', ['title' => 'ZZ ผลงาน', 'slug' => 'zz-test-p3-cs-' . $stamp, 'status' => 'published']);
$check($pdo->query('SELECT status FROM case_studies WHERE id = ' . $csId)->fetchColumn() === 'draft', 'case study cannot be published without permission');
$send('DELETE', '/admin/case-studies/' . $csId, 'p3editor');
$check($pdo->query('SELECT deleted_at FROM case_studies WHERE id = ' . $csId)->fetchColumn() !== null, 'case study deleted');

// ================================================================ 12. lead

$section('P3 — leads: filters, detail, status/assign, notes, CSV');

$pdo->prepare(
    "INSERT INTO leads (form_key, name, company, phone, email, message, source_url, utm, ip, status, locale, landing_page, referrer, created_at, updated_at)
     VALUES ('demo', :n, '=HYPERLINK(\"x\")', '0811111111', 'zz@example.com', 'สนใจ', '/demo/', :utm, '127.0.0.1', 'new', 'en', '/en/landing/', 'https://google.com/', NOW(), NOW())"
)->execute(['n' => 'ZZ ลูกค้า ' . $stamp, 'utm' => json_encode(['utm_source' => 'zz-test-src', 'utm_campaign' => 'launch'])]);
$leadId = (int) $pdo->lastInsertId();
$p3['leads'][] = $leadId;

$leadList = $http('GET', '/admin/leads?status=new&form_key=demo&locale=en&utm=zz-test-src&landing_page=landing&referrer=google', ['jar' => 'p3sales']);
$check($leadList['status'] === 200 && str_contains($leadList['body'], 'ZZ ลูกค้า ' . $stamp), 'sales sees the lead with status/form/locale/utm/landing/referrer filters');
$check(!str_contains($http('GET', '/admin/leads?utm=nothing-like-this', ['jar' => 'p3sales'])['body'], 'ZZ ลูกค้า ' . $stamp), 'utm filter excludes');
$detail = $http('GET', '/admin/leads/' . $leadId, ['jar' => 'p3sales']);
$check($detail['status'] === 200 && str_contains($detail['body'], 'zz-test-src') && str_contains($detail['body'], '/en/landing/'), 'detail shows UTM + landing page');
$check($http('GET', '/admin/leads/' . $leadId, ['jar' => 'p3editor'])['status'] === 403, 'editor cannot open a lead');
$send('PATCH', '/admin/leads/' . $leadId, 'p3sales', ['status' => 'contacted', 'assigned_to' => (string) $sales['id']]);
$leadNow = $pdo->query('SELECT status, assigned_to FROM leads WHERE id = ' . $leadId)->fetch();
$check($leadNow['status'] === 'contacted' && (int) $leadNow['assigned_to'] === $sales['id'], 'status + owner saved');
$send('PATCH', '/admin/leads/' . $leadId, 'p3sales', ['status' => 'contacted', 'assigned_to' => (string) $editor['id']]);
$check((int) $pdo->query('SELECT assigned_to FROM leads WHERE id = ' . $leadId)->fetchColumn() === $sales['id'], 'cannot assign to an editor');
$send('POST', '/admin/leads/' . $leadId . '/notes', 'p3sales', ['body' => 'zz-test โทรแล้ว']);
$check((int) $pdo->query("SELECT COUNT(*) FROM lead_notes WHERE lead_id = {$leadId}")->fetchColumn() === 1, 'note appended');
$check($pdo->query("SELECT COUNT(*) FROM audit_logs WHERE table_name = 'lead_notes' AND action = 'create'")->fetchColumn() > 0, 'note audited');
$csv = $http('GET', '/admin/leads/export?status=contacted&utm=zz-test-src', ['jar' => 'p3sales']);
$check($csv['status'] === 200 && str_starts_with($csv['body'], "\xEF\xBB\xBF") && str_contains($csv['body'], 'ZZ ลูกค้า ' . $stamp), 'CSV export: UTF-8 BOM + filtered rows');
$check(str_contains($csv['body'], "'=HYPERLINK"), 'CSV cells starting with = are neutralised');
$send('DELETE', '/admin/leads/' . $leadId, 'p3sales');
$check($pdo->query('SELECT deleted_at FROM leads WHERE id = ' . $leadId)->fetchColumn() !== null, 'lead soft-deleted');
$dashAdmin = $http('GET', '/admin', ['jar' => 'p3admin']);
$check($dashAdmin['status'] === 200 && str_contains($dashAdmin['body'], '/admin/leads?status=new'), 'dashboard shows the new-leads widget');

// ================================================================ 13. ตั้งค่า

$section('P3 — settings (admin)');

$badSettings = $send('PATCH', '/admin/settings', 'p3admin', ['company' => ['emails' => "not-an-email"], 'tracking' => ['ga4_id' => 'UA-1']]);
$check(!str_ends_with($location($badSettings), '/admin/settings') || $pdo->query("SELECT value FROM settings WHERE setting_key = 'tracking'")->fetchColumn() !== '{"ga4_id":"UA-1"}', 'invalid settings rejected');
$okSettings = $send('PATCH', '/admin/settings', 'p3admin', [
    'company' => ['name' => 'OMC MLM Software', 'founded' => '2009-04-03', 'phones' => "099-109-5544", 'emails' => "projectyak@hotmail.com\nsale@omc.co.th\ninfo@omc.co.th",
                  'line_id' => 'omc54', 'line_url' => 'https://line.me/ti/p/~omc54', 'facebook' => 'https://www.facebook.com/mlmsoftwarebyomc',
                  'address' => ['th' => 'ที่อยู่ทดสอบ', 'en' => 'Test address', 'zh' => '测试地址'], 'description' => ['en' => 'About EN']],
    'stats' => [['value' => '17', 'suffix' => ' ปี', 'label' => 'ประสบการณ์', 'verified' => '1'], ['value' => '200', 'label' => 'ไม่ยืนยัน']],
    'notifications' => ['lead_email' => 'sale@omc.co.th'],
    'tracking' => ['ga4_id' => 'G-ZZTEST123', 'gtm_id' => ''],
]);
$check($okSettings['status'] === 302, 'settings saved');
App\Services\SettingService::flush();
$en = App\Services\SiteService::layout('/en/', 'en');
$check(($en['contact']['emails'] ?? []) === ['projectyak@hotmail.com', 'sale@omc.co.th', 'info@omc.co.th'], 'layout shows all three emails from settings');
$check(($en['contact']['address'] ?? '') === 'Test address' && ($en['brand']['about'] ?? '') === 'About EN', 'localized address/description (en)');
$check(array_column(App\Services\SettingService::verifiedStats(), 'label') === ['ประสบการณ์'], 'only verified stats are exposed');
$statsBlock = App\Services\BlockService::prepare([['type' => 'stats']]);
$check(count($statsBlock[0]['items'] ?? []) === 1, 'stats block filled from verified settings only');
$check(App\Services\SettingService::get('organization.email') === 'projectyak@hotmail.com', 'organization schema synced from company');
$check(App\Services\TrackingService::active('production') === ['ga4' => 'G-ZZTEST123', 'gtm' => ''], 'tracking IDs active on production');
$check(App\Services\TrackingService::active('staging') === null, 'tracking never active outside production');
$publicPage = $http('GET', $enc($newPath));
$check($publicPage['status'] === 200 && !str_contains($publicPage['body'], 'tracking.js') && !str_contains($publicPage['body'], 'G-ZZTEST123'), 'non-production public page has no tracking code');
$check(!str_contains($header($publicPage, 'content-security-policy'), 'googletagmanager'), 'non-production CSP not widened');

// ================================================================ 14. ผู้ใช้ + สำรองข้อมูล

$section('P3 — users + backups (admin)');

$newUser = $send('POST', '/admin/users', 'p3admin', ['username' => 'zz-test-p3-new-' . $stamp, 'display_name' => 'ZZ ใหม่', 'role' => 'sales', 'is_active' => '1',
    'password' => 'Initial-Pass-1', 'password_confirm' => 'Initial-Pass-1']);
preg_match('#/admin/users/(\d+)$#', $location($newUser), $m);
$newId = (int) ($m[1] ?? 0);
$p3['users'][] = $newId;
$check($newId > 0 && (int) $pdo->query('SELECT must_change_password FROM users WHERE id = ' . $newId)->fetchColumn() === 1, 'admin creates a user who must change the password');
$check(!str_contains((string) $pdo->query("SELECT new_values FROM audit_logs WHERE table_name = 'users' AND record_id = {$newId} AND action = 'create'")->fetchColumn(), '$2y$'), 'password hash not written to audit log');
$dupUser = $send('POST', '/admin/users', 'p3admin', ['username' => 'zz-test-p3-new-' . $stamp, 'display_name' => 'x', 'role' => 'sales', 'password' => 'Initial-Pass-1', 'password_confirm' => 'Initial-Pass-1']);
$check(!preg_match('#/admin/users/\d+$#', $location($dupUser)), 'duplicate username rejected');
$send('PATCH', '/admin/users/' . $newId, 'p3admin', ['display_name' => 'ZZ แก้', 'role' => 'editor', 'is_active' => '1']);
$check($pdo->query('SELECT role FROM users WHERE id = ' . $newId)->fetchColumn() === 'editor', 'role changed');
$send('POST', '/admin/users/' . $newId . '/password', 'p3admin', ['password' => 'Another-Pass-2', 'password_confirm' => 'Another-Pass-2']);
$check((int) $pdo->query('SELECT must_change_password FROM users WHERE id = ' . $newId)->fetchColumn() === 1, 'reset password forces a change');
$selfDelete = $send('DELETE', '/admin/users/' . $admin['id'], 'p3admin');
$check($pdo->query('SELECT deleted_at FROM users WHERE id = ' . $admin['id'])->fetchColumn() === null, 'admin cannot delete themselves');
$send('DELETE', '/admin/users/' . $newId, 'p3admin');
$check($pdo->query('SELECT deleted_at FROM users WHERE id = ' . $newId)->fetchColumn() !== null, 'user deleted');

$backup = $send('POST', '/admin/backups', 'p3admin');
$files = glob($root . '/storage/backups/db-*.sql*') ?: [];
rsort($files);
$latest = basename($files[0] ?? '');
$p3['backups'][] = $latest;
$check($backup['status'] === 302 && $latest !== '', 'backup created', $latest);
$dl = $http('GET', '/admin/backups/' . $latest, ['jar' => 'p3admin']);
$body = str_ends_with($latest, '.gz') ? (string) gzdecode($dl['body']) : $dl['body'];
$check($dl['status'] === 200 && str_starts_with($body, 'SET NAMES utf8mb4') && str_contains($body, 'INSERT INTO `pages`'), 'backup downloads and contains data');
$check($http('GET', '/admin/backups/' . $latest, ['jar' => 'p3editor'])['status'] === 403, 'editor cannot download backups');
$check($http('GET', '/admin/backups/db-..%2F..%2F.env', ['jar' => 'p3admin'])['status'] === 404, 'backup name outside the pattern → 404');
$send('DELETE', '/admin/backups/' . $latest, 'p3admin');
$check(!is_file($root . '/storage/backups/' . $latest), 'backup deleted');
$check($pdo->query("SELECT COUNT(*) FROM audit_logs WHERE table_name = 'backups'")->fetchColumn() >= 3, 'backup create/download/delete audited');

// ================================================================ 15. หน้าจอทั้งหมด render ได้ (ไม่มี error/คีย์ภาษาหาย)

$section('P3 — screens render without missing translations');

foreach (array_merge($contentPaths, $leadPaths === [] ? [] : ['/admin/leads'], $adminPaths, ['/admin', '/admin/account/password', '/admin/menus?location=footer&locale=en']) as $path) {
    $res = $http('GET', $path, ['jar' => 'p3admin']);
    $check($res['status'] === 200 && preg_match('/\bcms\.[a-z_]+\.[a-z_0-9]+\b/', $res['body']) !== 1, "{$path} renders without raw cms.* keys", (string) $res['status']);
}

foreach (['cms-pages', 'cms-posts', 'cms-media', 'cms-menus', 'cms-redirects', 'cms-reviews', 'cms-leads', 'cms-users', 'cms-settings', 'cms-backups'] as $slug) {
    $res = $http('GET', '/admin/manual/' . $slug, ['jar' => 'p3admin']);
    $check($res['status'] === 200 && str_contains($res['body'], '/assets/manuals/cms/'), "manual {$slug} renders for admin");
}
$check($http('GET', '/admin/manual/cms-leads', ['jar' => 'p3sales'])['status'] === 200, 'sales can read the leads manual');
$check($http('GET', '/admin/manual/cms-settings', ['jar' => 'p3sales'])['status'] !== 200, 'sales cannot read the settings manual');

$p3Cleanup();
Database::forgetColumns();

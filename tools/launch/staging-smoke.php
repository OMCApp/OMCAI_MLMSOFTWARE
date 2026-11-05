<?php
/**
 * ทดสอบล็อกอินจริงบน staging ด้วยบัญชีทดสอบที่ได้รับอนุญาต — อ่านอย่างเดียว (ยกเว้นเปลี่ยนรหัสครั้งแรกถ้าระบบบังคับ)
 *
 *   QA_USER=zz-qa-sales QA_PASS='…' [QA_NEW_PASS='…'] php tools/launch/staging-smoke.php http://203.146.127.221/~omcai/mlmsoftware
 *
 * ‼️ รหัสผ่านรับทาง environment เท่านั้น — ห้ามใส่ใน argv (ค้างใน history) ห้ามเขียนลงไฟล์ · สคริปต์ไม่พิมพ์รหัสออกมา
 * ‼️ ใช้บัญชีทดสอบที่แอดมินของ OMC สร้างใน CMS (ขึ้นต้น zz-qa-) — ห้ามใช้บัญชีจริงของพนักงาน
 *    บัญชีใหม่ถูกบังคับเปลี่ยนรหัสครั้งแรก: ใส่ QA_NEW_PASS แล้วสคริปต์เปลี่ยนให้ (ครั้งถัดไปใช้ QA_PASS = รหัสใหม่)
 * ‼️ ไม่ลองรหัสผิดซ้ำ — ลองผิดครั้งเดียวเพื่อดูว่าถูกปฏิเสธ (เพดานล็อก 5 ครั้ง/IP ไม่โดน)
 *
 * ตรวจ: ล็อกอิน (CSRF) · เปลี่ยนรหัสครั้งแรก · หน้าที่บทบาทนั้นเข้าได้ = 200 · หน้าที่ห้าม = 403/302 · ข้อความ UI ไม่มี key ดิบ · ออกจากระบบแล้วเข้าไม่ได้
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !isset($argv[1])) {
    fwrite(STDERR, "usage: QA_USER=… QA_PASS=… [QA_NEW_PASS=…] php tools/launch/staging-smoke.php <base-url>\n");
    exit(2);
}

$base    = rtrim($argv[1], '/');
$user    = (string) getenv('QA_USER');
$pass    = (string) getenv('QA_PASS');
$newPass = (string) getenv('QA_NEW_PASS');

if ($user === '' || $pass === '') {
    fwrite(STDERR, "QA_USER and QA_PASS must be set in the environment\n");
    exit(2);
}
if (!str_starts_with($user, 'zz-qa-')) {
    fwrite(STDERR, "refusing: test accounts must start with zz-qa- (never a real staff account)\n");
    exit(2);
}

$jar    = tempnam(sys_get_temp_dir(), 'qa');
$passed = 0;
$failed = [];
$check  = static function (bool $ok, string $label) use (&$passed, &$failed): void {
    $ok ? $passed++ : $failed[] = $label;
    echo ($ok ? '  ok    ' : '  FAIL  ') . $label . "\n";
};
$http = static function (string $method, string $path, array $post = []) use ($base, $jar): array {
    $ch = curl_init($base . $path);
    $headers = [];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar, CURLOPT_USERAGENT => 'OMC-staging-smoke/1.0',
        CURLOPT_POSTFIELDS => $post === [] ? null : http_build_query($post),
        CURLOPT_HEADERFUNCTION => static function ($c, string $l) use (&$headers): int {
            $p = explode(':', $l, 2);
            if (count($p) === 2) {
                $headers[strtolower(trim($p[0]))] = trim($p[1]);
            }
            return strlen($l);
        },
    ]);
    $body = (string) curl_exec($ch);

    return ['status' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'location' => $headers['location'] ?? '', 'body' => $body];
};
$token = static fn (array $r): string => preg_match('/name="_token" value="([^"]+)"/', $r['body'], $m) === 1 ? html_entity_decode($m[1]) : '';

echo "staging smoke — {$base} as {$user}\n";

// 1. login form + one wrong password
$form = $http('GET', '/admin/login');
$check($form['status'] === 200 && $token($form) !== '', 'login form has a CSRF token');
$wrong = $http('POST', '/admin/login', ['_token' => $token($form), 'username' => $user, 'password' => 'definitely-wrong-' . bin2hex(random_bytes(3))]);
$check(!str_ends_with($wrong['location'], '/admin'), 'wrong password is rejected');

// 2. real login
$form  = $http('GET', '/admin/login');
$login = $http('POST', '/admin/login', ['_token' => $token($form), 'username' => $user, 'password' => $pass]);
$check($login['status'] === 302 && str_ends_with($login['location'], '/admin'), 'login succeeds → /admin');
if (!str_ends_with($login['location'], '/admin')) {
    echo "\nstop: cannot log in (check the account, or it may be locked for 15 minutes)\n";
    @unlink($jar);
    exit(1);
}

// 3. forced password change on first login
$dash = $http('GET', '/admin');
if ($dash['status'] === 302 && str_ends_with($dash['location'], '/admin/account/password')) {
    echo "  ..    first login: password change required\n";
    if ($newPass === '') {
        echo "\nstop: set QA_NEW_PASS to let the script change the password, then run again with QA_PASS = the new password\n";
        @unlink($jar);
        exit(1);
    }
    $page    = $http('GET', '/admin/account/password');
    $changed = $http('POST', '/admin/account/password', ['_token' => $token($page), '_method' => 'PATCH', 'current_password' => $pass, 'password' => $newPass, 'password_confirm' => $newPass]);
    $check($changed['status'] === 302 && str_ends_with($changed['location'], '/admin'), 'forced password change → /admin (use QA_NEW_PASS from now on)');
    $dash = $http('GET', '/admin');
}
$check($dash['status'] === 200 && str_contains($dash['body'], 'tile-grid'), 'dashboard renders');

// 4. role from the dashboard tiles → expected access
$role = str_contains($dash['body'], '/admin/pages?status=') ? (str_contains($dash['body'], '/admin/leads?status=new') ? 'admin' : 'editor') : 'sales';
echo "  ..    role detected: {$role}\n";
$expect = [
    'admin'  => ['/admin/pages' => 200, '/admin/posts' => 200, '/admin/media' => 200, '/admin/leads' => 200, '/admin/settings' => 200, '/admin/users' => 200, '/admin/audit-logs' => 200, '/admin/backups' => 200],
    'editor' => ['/admin/pages' => 200, '/admin/posts' => 200, '/admin/media' => 200, '/admin/leads' => 403, '/admin/settings' => 403, '/admin/users' => 403],
    'sales'  => ['/admin/leads' => 200, '/admin/leads/export' => 200, '/admin/pages' => 403, '/admin/posts' => 403, '/admin/settings' => 403, '/admin/users' => 403],
][$role];
foreach ($expect as $path => $status) {
    $r = $http('GET', $path);
    $check($r['status'] === $status, "{$role}: {$path} → {$status}" . ($r['status'] !== $status ? " (got {$r['status']})" : ''));
    if ($r['status'] === 200 && !str_ends_with($path, '/export')) {
        $check(preg_match('/\b(cms|common|lead|site|ui|state)\.[a-z_]+\.[a-z_]+\b/', strip_tags($r['body'])) !== 1, "{$path}: no raw translation keys");
    }
}
if ($role === 'admin') {
    $settings = $http('GET', '/admin/settings');
    $check(str_contains($settings['body'], 'id="mail"'), 'settings shows the lead e-mail status');
}

// 5. logout
$page   = $http('GET', '/admin');
$logout = $http('POST', '/admin/logout', ['_token' => $token($page)]);
$after  = $http('GET', '/admin');
$check($after['status'] === 302 && str_contains($after['location'], '/admin/login'), 'after logout /admin → login');

@unlink($jar);
printf("\n%d passed · %d failed\n", $passed, count($failed));
exit($failed === [] ? 0 : 1);

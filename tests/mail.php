<?php
/**
 * อีเมลแจ้งทีมขาย (LeadNotifier + MailService SMTP) — รันจาก tests/run.php
 *
 * ‼️ ระบบทดสอบที่ได้รับอนุญาต = tests/support/smtp-sink.php ในเครื่อง (127.0.0.1) — ไม่มีอีเมลออกไปข้างนอก
 *    เปิดแอปอีกตัวด้วย MAIL_* ชี้ sink (env ของโปรเซส — ใช้ได้เพราะ .env ในเครื่องไม่มี MAIL_*)
 *
 * ครอบคลุม: ฟอร์ม lead 3 ภาษา → อีเมลถึง sink (หัวเรื่องไทย · ลิงก์ CMS) · SMTP ล่ม → lead ไม่หาย + failed + ผู้ส่งฟอร์มเห็นสำเร็จ
 *           · หน้าแรก CMS เตือน · ปุ่มส่งซ้ำ → sent · อีเมลทดสอบในหน้าตั้งค่า · รหัส SMTP ผิด → failed พร้อมเหตุผล (ไม่มีรหัสผ่านใน error)
 */

declare(strict_types=1);

/** @var callable $check @var callable $section @var PDO $pdo @var string $root @var string $stamp */

$section('Phase 6 — lead e-mail to sales (SMTP sink · failure keeps the lead · alert · retry)');

$envOf = static fn (): array => getenv() + ['PATH' => (string) getenv('PATH')];
$mailOut  = $root . '/storage/logs/test-smtp-' . $stamp . '.jsonl';
$sinkPort = random_int(41000, 45000);
$appPort  = random_int(45001, 49000);
$sinkUser = 'zz-sink';
$sinkPass = 'zz-sink-pass-' . $stamp;
@unlink($mailOut);

$startSink = static function () use ($root, $sinkPort, $mailOut, $sinkUser, $sinkPass) {
    $proc = proc_open([PHP_BINARY, $root . '/tests/support/smtp-sink.php', (string) $sinkPort, $mailOut, $sinkUser, $sinkPass],
        [0 => ['file', 'php://stdin', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $pipes);
    for ($i = 0; $i < 30; $i++) {
        usleep(100000);
        $s = @fsockopen('127.0.0.1', $sinkPort, $e, $es, 0.2);
        if ($s !== false) {
            fclose($s);
            break;
        }
    }

    return $proc;
};
$stop = static function ($proc): void {
    if (is_resource($proc)) {
        $status = proc_get_status($proc);
        // php.exe ตัวลูกของ proc_open บน Windows — ปิดทั้ง tree ไม่งั้นพอร์ตยังค้าง
        if (PHP_OS_FAMILY === 'Windows' && !empty($status['pid'])) {
            exec('taskkill /F /T /PID ' . (int) $status['pid'] . ' 2>NUL');
        }
        proc_terminate($proc);
        proc_close($proc);
    }
};

$appEnv = $envOf() + [];
foreach (['MAIL_ENABLED' => 'true', 'MAIL_FROM' => 'zz-test-noreply@example.com', 'MAIL_DRIVER' => 'smtp', 'MAIL_HOST' => '127.0.0.1',
          'MAIL_PORT' => (string) $sinkPort, 'MAIL_USER' => $sinkUser, 'MAIL_PASS' => $sinkPass, 'MAIL_ENCRYPTION' => 'none', 'MAIL_TIMEOUT' => '3'] as $k => $v) {
    $appEnv[$k] = $v;
}
$startApp = static function (array $env) use ($root, $appPort) {
    $proc = proc_open([PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', "127.0.0.1:{$appPort}", 'server.php'],
        [0 => ['file', 'php://stdin', 'r'], 1 => ['file', $root . '/storage/logs/test-server-mail.log', 'a'], 2 => ['file', $root . '/storage/logs/test-server-mail.log', 'a']],
        $pipes, $root, $env);
    for ($i = 0; $i < 50; $i++) {
        usleep(100000);
        if (@file_get_contents("http://127.0.0.1:{$appPort}/_health") !== false) {
            break;
        }
    }

    return $proc;
};

$jars = [];
$mhttp = static function (string $method, string $url, array $opts = []) use ($appPort, &$jars, $root): array {
    $jar = $opts['jar'] ?? 'mail';
    $jars[$jar] ??= $root . '/storage/logs/test-mail-jar-' . $jar . '-' . getmypid() . '.txt';
    $ch = curl_init("http://127.0.0.1:{$appPort}" . $url);
    $headers = [];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEFILE => $jars[$jar], CURLOPT_COOKIEJAR => $jars[$jar],
        CURLOPT_POSTFIELDS => isset($opts['post']) ? http_build_query($opts['post']) : null,
        CURLOPT_HEADERFUNCTION => static function ($c, string $line) use (&$headers): int {
            $p = explode(':', $line, 2);
            if (count($p) === 2) {
                $headers[strtolower(trim($p[0]))] = trim($p[1]);
            }
            return strlen($line);
        },
    ]);
    $body = (string) curl_exec($ch);

    return ['status' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'location' => $headers['location'] ?? '', 'body' => $body];
};
$tokenOf = static fn (array $res): string => preg_match('/name="_token" value="([^"]+)"/', $res['body'], $m) === 1 ? html_entity_decode($m[1]) : '';
$messages = static function () use ($mailOut): array {
    clearstatcache();
    return is_file($mailOut) ? array_map(static fn (string $l): array => (array) json_decode($l, true), array_filter(explode("\n", (string) file_get_contents($mailOut)))) : [];
};
/** body ของข้อความ (base64) + หัวเรื่อง (=?UTF-8?B?…?=) ที่ถอดแล้ว */
$decode = static function (array $msg): array {
    [$head, $body] = array_pad(explode("\r\n\r\n", (string) ($msg['data'] ?? ''), 2), 2, '');
    $subject = preg_match('/^Subject: (.+)$/m', $head, $m) === 1 ? iconv_mime_decode(trim($m[1]), 0, 'UTF-8') : '';

    return ['subject' => (string) $subject, 'body' => (string) base64_decode(preg_replace('/\s+/', '', $body)), 'head' => $head];
};
$leadRow = static function (string $name) use ($pdo): array {
    $q = $pdo->prepare('SELECT * FROM leads WHERE name = :n ORDER BY id DESC LIMIT 1');
    $q->execute(['n' => $name]);

    return (array) ($q->fetch() ?: []);
};
$submit = static function (string $page, string $name) use ($mhttp, $tokenOf, $pdo): array {
    $pdo->prepare('DELETE FROM lead_attempts WHERE ip_address = :ip')->execute(['ip' => '127.0.0.1']);
    $form = $mhttp('GET', $page, ['jar' => 'visitor']);

    return $mhttp('POST', '/lead/', ['jar' => 'visitor', 'post' => ['_token' => $tokenOf($form), 'form_key' => 'demo', 'source_path' => $page,
        'website' => '', 'name' => $name, 'phone' => '0812345678', 'email' => 'zz-test-buyer@example.com', 'message' => 'ทดสอบ / test / 测试']]);
};

$sink = $startSink();
$app  = $startApp($appEnv);

// ---- 1. ฟอร์มทั้ง 3 ภาษา → บันทึก + อีเมลถึง sink
$names = [];
foreach (['/demo/' => 'th', '/en/demo/' => 'en', '/zh/demo/' => 'zh'] as $page => $locale) {
    $name = 'zz-test mail ' . $locale . ' ' . $stamp;
    $names[] = $name;
    $res  = $submit($page, $name);
    $row  = $leadRow($name);
    $check($res['status'] === 303 && str_contains($res['location'], 'thank-you'), "{$locale}: lead form → thank-you", $res['status'] . ' ' . $res['location']);
    $check(($row['locale'] ?? '') === $locale && ($row['notify_status'] ?? '') === 'sent' && (int) ($row['notify_attempts'] ?? 0) === 1 && ($row['notified_at'] ?? null) !== null,
        "{$locale}: lead saved, e-mail status sent", json_encode(array_intersect_key($row, array_flip(['locale', 'notify_status', 'notify_error']))));
}
$sent = $messages();
$check(count($sent) === 3, 'sink received 3 e-mails (one per lead)', (string) count($sent));
$first = $decode($sent[0] ?? []);
$check(str_contains($first['subject'], 'Lead ใหม่') && str_contains($first['subject'], 'zz-test mail th'), 'subject is Thai, UTF-8 encoded', $first['subject']);
$check(str_contains($first['body'], 'zz-test-buyer@example.com') && str_contains($first['body'], 'ทดสอบ / test / 测试')
    && preg_match('#/admin/leads/\d+#', $first['body']) === 1, 'body has contact details, message and a CMS link');
$check(($sent[0]['auth'] ?? '') === $sinkUser && ($sent[0]['from'] ?? '') === 'zz-test-noreply@example.com', 'SMTP AUTH LOGIN + MAIL FROM used');
$check(str_contains($first['head'], 'Reply-To: zz-test-buyer@example.com'), 'Reply-To = the visitor (sales can answer directly)');
$check(!str_contains(implode('', array_column($sent, 'data')), $sinkPass), 'SMTP password never appears in the message');
$zhMail = $decode($sent[2] ?? []);
$check(str_contains($zhMail['body'], 'zh') && str_contains($zhMail['subject'], 'Lead ใหม่'), 'zh lead → e-mail still in Thai for the sales team');

// ---- 2. SMTP ล่ม → lead ไม่หาย · failed · ผู้ส่งฟอร์มยังเห็นสำเร็จ
$stop($sink);
$downName = 'zz-test mail down ' . $stamp;
$names[]  = $downName;
$res      = $submit('/demo/', $downName);
$down     = $leadRow($downName);
$check($res['status'] === 303 && str_contains($res['location'], 'thank-you'), 'SMTP down: visitor still gets the thank-you page', $res['status'] . ' ' . $res['location']);
$check(($down['id'] ?? 0) > 0 && ($down['notify_status'] ?? '') === 'failed' && (string) ($down['notify_error'] ?? '') !== '',
    'SMTP down: lead is saved, status failed with the reason', (string) ($down['notify_error'] ?? ''));

// แอดมินทดสอบ (ลบตอนจบ)
$adminName = 'zz-test-mail-admin-' . $stamp;
$adminPass = 'Zz-' . bin2hex(random_bytes(8)) . '!a1';
$pdo->prepare("INSERT INTO users (username, display_name, password_hash, role, locale, theme, is_active, must_change_password, created_at, updated_at)
               VALUES (:u, 'ZZ mail admin', :h, 'admin', 'th', 'light', 1, 0, NOW(), NOW())")->execute(['u' => $adminName, 'h' => password_hash($adminPass, PASSWORD_DEFAULT)]);
$adminId = (int) $pdo->lastInsertId();
$loginPage = $mhttp('GET', '/admin/login', ['jar' => 'admin']);
$logged    = $mhttp('POST', '/admin/login', ['jar' => 'admin', 'post' => ['_token' => $tokenOf($loginPage), 'username' => $adminName, 'password' => $adminPass]]);
$check($logged['status'] === 302 && str_ends_with($logged['location'], '/admin'), 'mail test admin logs in', $logged['status'] . ' ' . $logged['location']);

$dash = $mhttp('GET', '/admin', ['jar' => 'admin']);
$check(str_contains($dash['body'], 'id="dash-mail"') && str_contains($dash['body'], 'role="alert"') && str_contains($dash['body'], '/admin/leads/notify-retry'),
    'dashboard shows a persistent alert with a resend button');
$list = $mhttp('GET', '/admin/leads?notify_status=failed', ['jar' => 'admin']);
$check(str_contains($list['body'], e($downName)) && !str_contains($list['body'], e($names[0])), 'leads list filters by e-mail status (failed)');
$detail = $mhttp('GET', '/admin/leads/' . (int) $down['id'], ['jar' => 'admin']);
$check(str_contains($detail['body'], e((string) $down['notify_error'])), 'lead detail shows why the e-mail failed');

// ---- 3. SMTP กลับมา → ปุ่มส่งซ้ำ → sent · การแจ้งเตือนหายไป
$sink  = $startSink();
$retry = $mhttp('POST', '/admin/leads/notify-retry', ['jar' => 'admin', 'post' => ['_token' => $tokenOf($mhttp('GET', '/admin/leads', ['jar' => 'admin']))]]);
$check($retry['status'] === 302 && str_ends_with($retry['location'], '/admin/leads'), 'resend → back to leads', $retry['status'] . ' ' . $retry['location']);
$after = $leadRow($downName);
$check(($after['notify_status'] ?? '') === 'sent' && (int) $after['notify_attempts'] === 2 && $after['notify_error'] === null, 'after resend: sent, 2 attempts, error cleared');
$check(!str_contains($mhttp('GET', '/admin', ['jar' => 'admin'])['body'], 'id="dash-mail"'), 'alert disappears once every lead e-mail is sent');
$check(count($messages()) === 4, 'sink has the resent e-mail', (string) count($messages()));
$audit = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE table_name = 'leads' AND record_id = :id AND action = 'update'");
$audit->execute(['id' => $down['id']]);
$check((int) $audit->fetchColumn() >= 2, 'e-mail status changes are in the audit log');

// ---- 4. อีเมลทดสอบจากหน้าตั้งค่า
$settings = $mhttp('GET', '/admin/settings', ['jar' => 'admin']);
$check(str_contains($settings['body'], 'id="mail"') && str_contains($settings['body'], '127.0.0.1'), 'settings shows the mail status (driver, host)');
$check(!str_contains($settings['body'], $sinkPass), 'settings never shows the SMTP password');
$testRes = $mhttp('POST', '/admin/settings/test-mail', ['jar' => 'admin', 'post' => ['_token' => $tokenOf($settings), 'to' => 'zz-test-check@example.com']]);
$last    = $messages()[4] ?? [];
$check($testRes['status'] === 302 && in_array('zz-test-check@example.com', (array) ($last['to'] ?? []), true) && str_contains($decode($last)['subject'], 'ทดสอบ'),
    'settings → send test e-mail reaches the address entered');
$salesTry = $mhttp('POST', '/admin/settings/test-mail', ['jar' => 'visitor', 'post' => ['to' => 'x@example.com']]);
$check($salesTry['status'] !== 200 && count($messages()) === 5, 'anonymous user cannot send a test e-mail', (string) $salesTry['status']);
$stop($app);

// ---- 5. รหัส SMTP ผิด → failed พร้อมเหตุผล (ไม่มีรหัสผ่านใน error)
$badEnv = $appEnv;
$badEnv['MAIL_PASS'] = 'wrong-password-' . $stamp;
$app     = $startApp($badEnv);
$badName = 'zz-test mail badauth ' . $stamp;
$names[] = $badName;
$submit('/en/demo/', $badName);
$bad = $leadRow($badName);
$check(($bad['notify_status'] ?? '') === 'failed' && str_contains((string) $bad['notify_error'], '535') && !str_contains((string) $bad['notify_error'], 'wrong-password'),
    'wrong SMTP password: failed with the server reply, password not stored', (string) ($bad['notify_error'] ?? ''));
$stop($app);
$stop($sink);

// ---- cleanup
$del = $pdo->prepare('DELETE FROM leads WHERE name = :n');
foreach ($names as $name) {
    $del->execute(['n' => $name]);
}
$pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $adminId]);
@unlink($mailOut);
foreach ($jars as $jarFile) {
    @unlink($jarFile);
}

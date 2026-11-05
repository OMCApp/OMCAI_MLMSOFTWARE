<?php
/**
 * SMTP ปลายทางสำหรับทดสอบในเครื่องเท่านั้น — รับอีเมลแล้วเขียนลงไฟล์ ไม่ส่งต่อไปไหน (ระบบทดสอบที่ได้รับอนุญาตของ tests/mail.php)
 *
 *   php tests/support/smtp-sink.php <port> <out-file> [<user> <pass>]
 *
 * ‼️ ฟังเฉพาะ 127.0.0.1 · ไม่มี TLS (MAIL_ENCRYPTION=none ใช้กับตัวนี้เท่านั้น)
 * แต่ละข้อความเขียนเป็น JSON หนึ่งบรรทัด: {from, to, auth, data}
 */

declare(strict_types=1);

[$port, $out, $user, $pass] = [(int) ($argv[1] ?? 0), (string) ($argv[2] ?? ''), (string) ($argv[3] ?? ''), (string) ($argv[4] ?? '')];

if ($port <= 0 || $out === '') {
    fwrite(STDERR, "usage: smtp-sink.php <port> <out-file> [<user> <pass>]\n");
    exit(2);
}

$server = stream_socket_server("tcp://127.0.0.1:{$port}", $errno, $errstr);
if ($server === false) {
    fwrite(STDERR, "listen failed: {$errstr}\n");
    exit(1);
}

while (true) {
    $c = @stream_socket_accept($server, 3600);
    if ($c === false) {
        continue;
    }
    stream_set_timeout($c, 10);
    $say  = static fn (string $line) => fwrite($c, $line . "\r\n");
    $mail = ['from' => '', 'to' => [], 'auth' => '', 'data' => ''];
    $say('220 smtp-sink ready');

    while (($line = fgets($c)) !== false) {
        $cmd = rtrim($line, "\r\n");
        $up  = strtoupper($cmd);

        if (str_starts_with($up, 'EHLO') || str_starts_with($up, 'HELO')) {
            fwrite($c, "250-smtp-sink\r\n250 AUTH LOGIN\r\n");
        } elseif ($up === 'AUTH LOGIN') {
            $say('334 VXNlcm5hbWU6');
            $u = base64_decode(rtrim((string) fgets($c)));
            $say('334 UGFzc3dvcmQ6');
            $p = base64_decode(rtrim((string) fgets($c)));
            if ($user !== '' && ($u !== $user || $p !== $pass)) {
                $say('535 5.7.8 Authentication failed');
                continue;
            }
            $mail['auth'] = $u;
            $say('235 2.7.0 Authentication successful');
        } elseif (str_starts_with($up, 'MAIL FROM:')) {
            $mail['from'] = trim(substr($cmd, 10), ' <>');
            $say('250 OK');
        } elseif (str_starts_with($up, 'RCPT TO:')) {
            $mail['to'][] = trim(substr($cmd, 8), ' <>');
            $say('250 OK');
        } elseif ($up === 'DATA') {
            $say('354 End data with <CR><LF>.<CR><LF>');
            $data = '';
            while (($l = fgets($c)) !== false && rtrim($l, "\r\n") !== '.') {
                $data .= str_starts_with($l, '..') ? substr($l, 1) : $l;
            }
            $mail['data'] = $data;
            file_put_contents($out, json_encode($mail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
            $say('250 OK queued');
            $mail = ['from' => '', 'to' => [], 'auth' => $mail['auth'], 'data' => ''];
        } elseif ($up === 'QUIT') {
            $say('221 Bye');
            break;
        } else {
            $say('250 OK');
        }
    }
    fclose($c);
}

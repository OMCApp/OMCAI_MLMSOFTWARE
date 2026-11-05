<?php
/**
 * ส่งอีเมลแจ้งเตือนภายใน (lead ใหม่) — SMTP ของกล่องอีเมลจริง หรือ mail() ของ PHP · ไม่มี dependency เพิ่ม
 *
 * ‼️ ไม่ได้ตั้งค่า (MAIL_ENABLED=false หรือไม่มี MAIL_FROM) = ไม่ส่ง ผลเป็น 'skipped'
 *    การส่งอีเมลล้มเหลวต้องไม่ทำให้ lead หาย — lead บันทึกลงฐานข้อมูลก่อนเสมอ แล้วค่อยส่ง (LeadNotifier)
 * ‼️ header ทุกตัวกรอง CR/LF (กัน header injection จากค่าที่ผู้ใช้กรอก เช่น ชื่อในหัวเรื่อง)
 * ‼️ ข้อความ error ห้ามมีรหัสผ่าน SMTP — เก็บเฉพาะรหัสตอบกลับของเซิร์ฟเวอร์
 */

declare(strict_types=1);

namespace App\Services;

use Core\Logger;

final class MailService
{
    /** ผลครั้งล่าสุด: sent | failed | skipped */
    private string $lastStatus = '';
    private string $lastError  = '';

    public static function configured(): bool
    {
        return (bool) config('mail.enabled', false) && filter_var((string) config('mail.from', ''), FILTER_VALIDATE_EMAIL) !== false
            && (config('mail.driver') !== 'smtp' || (string) config('mail.host', '') !== '');
    }

    public function lastStatus(): string
    {
        return $this->lastStatus;
    }

    public function lastError(): string
    {
        return $this->lastError;
    }

    public function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        $this->lastError = '';

        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            Logger::warning('mail_skipped_bad_recipient');

            return $this->result('failed', 'bad recipient address');
        }

        if (!self::configured()) {
            Logger::info('mail_skipped_not_configured', ['subject' => self::clean($subject)]);

            return $this->result('skipped', 'mail not configured');
        }

        $from    = self::clean((string) config('mail.from'));
        $subject = '=?UTF-8?B?' . base64_encode(self::clean($subject)) . '?=';
        $headers = [
            'From: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];

        if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false) {
            $headers[] = 'Reply-To: ' . self::clean($replyTo);
        }

        $encoded = rtrim(chunk_split(base64_encode($body), 76, "\r\n"));

        try {
            if (config('mail.driver') === 'smtp') {
                $this->smtp($from, $to, array_merge(
                    ['Date: ' . date('r'), 'To: ' . $to, 'Subject: ' . $subject, 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . self::domain($from) . '>'],
                    $headers
                ), $encoded);
            } elseif (!@mail($to, $subject, $encoded, implode("\r\n", $headers))) {
                throw new \RuntimeException('mail() returned false');
            }
        } catch (\Throwable $e) {
            Logger::warning('mail_failed', ['subject' => self::clean(html_entity_decode($subject)), 'error' => $e->getMessage()]);

            return $this->result('failed', mb_substr($e->getMessage(), 0, 250));
        }

        return $this->result('sent', '');
    }

    /**
     * SMTP ขั้นต่ำ: EHLO · STARTTLS/SSL · AUTH LOGIN · MAIL FROM · RCPT TO · DATA
     *
     * @param list<string> $headers
     */
    private function smtp(string $from, string $to, array $headers, string $body): void
    {
        $encryption = (string) config('mail.encryption', 'tls');
        $host       = (string) config('mail.host');
        $port       = (int) config('mail.port', 587);
        $timeout    = max(3, (int) config('mail.timeout', 10));
        $target     = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $socket = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]));

        if ($socket === false) {
            throw new \RuntimeException("connect {$host}:{$port} failed: {$errstr}");
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expect($socket, null, 220);
            $helo = self::domain($from);
            $this->expect($socket, 'EHLO ' . $helo, 250);

            if ($encryption === 'tls') {
                $this->expect($socket, 'STARTTLS', 220);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS handshake failed');
                }
                $this->expect($socket, 'EHLO ' . $helo, 250);
            }

            if ((string) config('mail.user', '') !== '') {
                $this->expect($socket, 'AUTH LOGIN', 334);
                $this->expect($socket, base64_encode((string) config('mail.user')), 334, 'username');
                $this->expect($socket, base64_encode((string) config('mail.pass')), 235, 'password');
            }

            $this->expect($socket, 'MAIL FROM:<' . $from . '>', 250);
            $this->expect($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->expect($socket, 'DATA', 354);

            // dot-stuffing: บรรทัดที่ขึ้นต้นด้วย "." ต้องเป็น ".." (base64 ไม่มี แต่ header อาจมี)
            $data = preg_replace('/^\./m', '..', implode("\r\n", $headers) . "\r\n\r\n" . $body);
            $this->expect($socket, $data . "\r\n.", 250);
            @fwrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
    }

    /**
     * @param resource       $socket
     * @param int|list<int>  $codes
     */
    private function expect($socket, ?string $command, int|array $codes, string $label = ''): void
    {
        if ($command !== null) {
            fwrite($socket, $command . "\r\n");
        }

        $reply = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }

        $code = (int) substr($reply, 0, 3);

        if (!in_array($code, (array) $codes, true)) {
            // ‼️ ไม่ใส่คำสั่งที่ส่งไป (อาจเป็นรหัสผ่าน base64) — ใส่แค่ชื่อขั้นตอนกับคำตอบของเซิร์ฟเวอร์
            $step = $label !== '' ? $label : ((string) strtok((string) $command, ' :') ?: 'greeting');
            throw new \RuntimeException('SMTP ' . $step . ': ' . ($reply === '' ? 'no reply (timeout)' : trim(mb_substr($reply, 0, 200))));
        }
    }

    private function result(string $status, string $error): bool
    {
        $this->lastStatus = $status;
        $this->lastError  = $error;

        return $status === 'sent';
    }

    private static function domain(string $email): string
    {
        $domain = substr((string) strrchr($email, '@'), 1);

        return preg_match('/^[a-z0-9.-]+$/i', $domain) === 1 ? $domain : 'localhost';
    }

    private static function clean(string $value): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $value));
    }
}

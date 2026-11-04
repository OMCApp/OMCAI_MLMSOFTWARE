<?php
/**
 * ส่งอีเมลแจ้งเตือนภายใน (lead ใหม่) ด้วย mail() ของ PHP — ไม่มี dependency เพิ่ม
 *
 * ‼️ ไม่ได้ตั้งค่า (MAIL_ENABLED=false หรือไม่มี MAIL_FROM) = ไม่ส่ง แต่บันทึก log ว่าข้าม
 *    การส่งอีเมลล้มเหลวต้องไม่ทำให้ lead หาย — lead บันทึกลงฐานข้อมูลก่อนเสมอ แล้วค่อยส่ง
 * ‼️ header ทุกตัวกรอง CR/LF (กัน header injection จากค่าที่ผู้ใช้กรอก เช่น ชื่อในหัวเรื่อง)
 */

declare(strict_types=1);

namespace App\Services;

use Core\Logger;

final class MailService
{
    public static function configured(): bool
    {
        return (bool) config('mail.enabled', false) && filter_var((string) config('mail.from', ''), FILTER_VALIDATE_EMAIL) !== false;
    }

    public function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            Logger::warning('mail_skipped_bad_recipient');

            return false;
        }

        if (!self::configured()) {
            Logger::info('mail_skipped_not_configured', ['subject' => self::clean($subject)]);

            return false;
        }

        $headers = [
            'From: ' . self::clean((string) config('mail.from')),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL) !== false) {
            $headers[] = 'Reply-To: ' . self::clean($replyTo);
        }

        $ok = @mail($to, '=?UTF-8?B?' . base64_encode(self::clean($subject)) . '?=', $body, implode("\r\n", $headers));

        if (!$ok) {
            Logger::warning('mail_failed', ['subject' => self::clean($subject)]);
        }

        return $ok;
    }

    private static function clean(string $value): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $value));
    }
}

<?php
/**
 * cookie อุปกรณ์ที่ลงลายเซ็น — "เครื่องนี้เคยเข้าบัญชีนี้สำเร็จ"
 *
 * ใช้แทนข้อยกเว้นที่ผูกกับ IP เดิม (project/NOTES.md) — IP เดียวกันอาจเป็นคนทั้งออฟฟิศ (NAT/CGNAT)
 * cookie ผูกกับ user_id + เวลาออก ลงลายเซ็นด้วย HMAC-SHA256 จาก APP_KEY ใน .env
 *
 * ‼️ APP_KEY ว่าง = ไม่ออก cookie และไม่เชื่อ cookie ใดๆ (ปิดแบบปลอดภัย) — ไม่มีข้อยกเว้นให้ใคร
 * ‼️ cookie นี้ไม่ใช่การล็อกอิน — แค่ทำให้เจ้าของบัญชีไม่โดนเพดาน 50 ครั้ง/username ตอนถูกยิงจากหลาย IP
 *    เพดานคู่ username+IP (5) และต่อ IP (20) ยังใช้กับทุกคน
 */

declare(strict_types=1);

namespace Core;

final class DeviceCookie
{
    public const NAME     = 'omc_device';
    public const MAX_DAYS = 90;

    private static function key(): string
    {
        return (string) config('app_key', '');
    }

    /** ค่าของ cookie สำหรับผู้ใช้นี้ — ว่างเมื่อไม่มี APP_KEY */
    public static function make(int $userId, ?int $issuedAt = null): string
    {
        $key = self::key();

        if ($key === '') {
            return '';
        }

        $payload = $userId . '.' . ($issuedAt ?? time());

        return $payload . '.' . hash_hmac('sha256', $payload, $key);
    }

    /** ออก cookie หลังล็อกอินสำเร็จ */
    public static function issue(int $userId): void
    {
        $value = self::make($userId);

        if ($value === '') {
            Logger::warning('device_cookie_disabled', ['reason' => 'APP_KEY is empty']);

            return;
        }

        if (headers_sent()) {
            return;
        }

        setcookie(self::NAME, $value, [
            'expires'  => time() + self::MAX_DAYS * 86400,
            'path'     => Path::base() === '' ? '/' : Path::base(),
            'secure'   => (bool) config('session.secure', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** user_id ที่ cookie ของ request นี้รับรอง — null ถ้าไม่มี ลายเซ็นผิด หรือหมดอายุ */
    public static function userId(?string $value = null): ?int
    {
        $value ??= isset($_COOKIE[self::NAME]) && is_string($_COOKIE[self::NAME]) ? $_COOKIE[self::NAME] : '';
        $key   = self::key();

        if ($key === '' || preg_match('/^(\d{1,20})\.(\d{1,12})\.([a-f0-9]{64})$/', $value, $m) !== 1) {
            return null;
        }

        // ‼️ เทียบลายเซ็นด้วย hash_equals — กัน timing attack
        if (!hash_equals(hash_hmac('sha256', $m[1] . '.' . $m[2], $key), $m[3])) {
            return null;
        }

        $age = time() - (int) $m[2];

        if ($age < 0 || $age > self::MAX_DAYS * 86400) {
            return null;
        }

        return (int) $m[1];
    }
}

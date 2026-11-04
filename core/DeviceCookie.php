<?php
/**
 * cookie อุปกรณ์ที่ลงลายเซ็น — "เครื่องนี้เคยเข้าบัญชีนี้สำเร็จ"
 *
 * ใช้แทนข้อยกเว้นที่ผูกกับ IP เดิม (project/NOTES.md) — IP เดียวกันอาจเป็นคนทั้งออฟฟิศ (NAT/CGNAT)
 *
 * รูปแบบ: <user_id>.<issued_at>.<hmac>
 *   hmac = HMAC-SHA256(APP_KEY, "<user_id>.<issued_at>.<ความลับของผู้ใช้>")
 *   ความลับของผู้ใช้ = sha256(password_hash | role | is_active | device_token_version)
 *
 * ‼️ ผูกกับข้อมูลรับรองของบัญชี — เปลี่ยนรหัสผ่าน · เปลี่ยนบทบาท · ปิดบัญชี · "ออกจากทุกอุปกรณ์"
 *    (เพิ่ม device_token_version) ทำให้ cookie เดิมทุกใบใช้ไม่ได้ทันที
 * ‼️ อายุ 90 วันตรวจที่ฝั่ง server จากเวลาที่อยู่ในลายเซ็น — ไม่เชื่อวันหมดอายุของเบราว์เซอร์
 * ‼️ APP_KEY ว่าง = ไม่ออก cookie และไม่เชื่อ cookie ใดๆ (ปิดแบบปลอดภัย)
 * ‼️ cookie นี้ไม่ใช่การล็อกอิน — แค่ยกเพดานต่อ username ให้เจ้าของบัญชี (Auth มีเพดานสำหรับอุปกรณ์ที่รู้จักอีกชั้น)
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

    /** @param array<string, mixed> $user ต้องมี id, password_hash, role, is_active, device_token_version */
    private static function secret(array $user): string
    {
        return hash('sha256', implode('|', [
            (string) ($user['password_hash'] ?? ''),
            (string) ($user['role'] ?? ''),
            (string) ($user['is_active'] ?? ''),
            (string) ($user['device_token_version'] ?? '0'),
        ]));
    }

    /**
     * ค่าของ cookie สำหรับผู้ใช้นี้ — ว่างเมื่อไม่มี APP_KEY
     *
     * @param array<string, mixed> $user
     */
    public static function make(array $user, ?int $issuedAt = null): string
    {
        $key = self::key();

        if ($key === '') {
            return '';
        }

        $payload = (int) $user['id'] . '.' . ($issuedAt ?? time());

        return $payload . '.' . hash_hmac('sha256', $payload . '.' . self::secret($user), $key);
    }

    /**
     * ออก cookie หลังล็อกอินสำเร็จ
     *
     * @param array<string, mixed> $user
     */
    public static function issue(array $user): void
    {
        $value = self::make($user);

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
            // ‼️ Secure ตาม config('https') — staging เป็น HTTP จึงห้าม hardcode · Strict: ไม่ต้องส่งข้ามไซต์เลย
            'secure'   => (bool) config('https', false),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    /**
     * cookie ของ request นี้รับรองว่าเป็นอุปกรณ์ของบัญชี username นี้หรือไม่
     *
     * ‼️ อ่านบัญชีจากฐานข้อมูลทุกครั้ง — บัญชีที่ถูกปิด/ลบ หรือข้อมูลรับรองเปลี่ยนแล้ว = ไม่ผ่าน
     */
    public static function validFor(string $username, ?string $value = null): bool
    {
        $value ??= isset($_COOKIE[self::NAME]) && is_string($_COOKIE[self::NAME]) ? $_COOKIE[self::NAME] : '';
        $key   = self::key();

        if ($key === '' || preg_match('/^(\d{1,20})\.(\d{1,12})\.([a-f0-9]{64})$/', $value, $m) !== 1) {
            return false;
        }

        $age = time() - (int) $m[2];

        if ($age < 0 || $age > self::MAX_DAYS * 86400) {
            return false;
        }

        $user = Database::selectOne(
            'SELECT id, password_hash, role, is_active, device_token_version FROM users
             WHERE username = :username AND deleted_at IS NULL LIMIT 1',
            ['username' => mb_substr($username, 0, 100)]
        );

        if ($user === null || (int) $user['is_active'] !== 1 || (int) $user['id'] !== (int) $m[1]) {
            return false;
        }

        // ‼️ เทียบลายเซ็นด้วย hash_equals — กัน timing attack
        return hash_equals(hash_hmac('sha256', $m[1] . '.' . $m[2] . '.' . self::secret($user), $key), $m[3]);
    }
}

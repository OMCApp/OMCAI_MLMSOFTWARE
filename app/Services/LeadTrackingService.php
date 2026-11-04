<?php
/**
 * แหล่งที่มาของ lead — UTM แบบ first-touch (cookie 30 วัน) + หน้าที่ส่งฟอร์ม + referrer
 *
 * ‼️ cookie เก็บแค่ค่า utm_* ห้าห้าตัว (ตัดความยาว · เฉพาะตัวอักษรที่ปลอดภัย) — ไม่มีข้อมูลส่วนบุคคล
 *    HttpOnly + SameSite=Lax · Secure เมื่อเป็น HTTPS · ค่าที่อ่านกลับถูกกรองซ้ำทุกครั้ง (ผู้ใช้แก้ cookie ได้)
 * ‼️ first-touch: ตั้งครั้งเดียว ไม่ทับเมื่อมี UTM ใหม่ จนกว่าจะหมดอายุ
 *    UTM ใน URL ของหน้าที่ส่งฟอร์มเองยังถูกเก็บแยกไว้ (last-touch) ใน utm.current
 */

declare(strict_types=1);

namespace App\Services;

final class LeadTrackingService
{
    public const COOKIE   = 'omc_utm';
    public const KEYS     = ['source', 'medium', 'campaign', 'term', 'content'];
    private const SECONDS = 30 * 86400;
    private const MAX_LEN = 150;

    /** @param array<string, mixed> $query $_GET ของหน้าที่เปิด */
    public static function rememberFirstTouch(array $query): void
    {
        $utm = self::pick($query);

        if ($utm === [] || isset($_COOKIE[self::COOKIE]) || headers_sent()) {
            return;
        }

        $value = (string) json_encode($utm + ['at' => date('c')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        setcookie(self::COOKIE, $value, [
            'expires'  => time() + self::SECONDS,
            'path'     => self::cookiePath(),
            'secure'   => (bool) config('https', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[self::COOKIE] = $value;
    }

    /**
     * ข้อมูลแหล่งที่มาสำหรับบันทึกพร้อม lead
     *
     * @param  array<string, mixed> $post    ค่าที่ฟอร์มส่งมา (utm_* ซ่อน — มาจาก URL ของหน้าที่ส่ง)
     * @return array{first: array<string,string>, current: array<string,string>}
     */
    public static function utm(array $post): array
    {
        $first = [];
        $raw   = (string) ($_COOKIE[self::COOKIE] ?? '');

        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            $first   = is_array($decoded) ? self::pick($decoded, '') : [];
        }

        return ['first' => $first, 'current' => self::pick($post)];
    }

    /**
     * ช่องซ่อนของฟอร์ม lead — UTM ใน URL ของหน้านี้ + referrer ภายนอกตอนเปิดหน้า (referrer ตอน POST คือหน้าเราเอง)
     *
     * @param  array<string, mixed> $query
     * @return array<string, string> name => value
     */
    public static function formFields(array $query, ?string $referrer): array
    {
        $out = [];

        foreach (self::pick($query) as $key => $value) {
            $out['utm_' . $key] = $value;
        }

        $external = self::referrer($referrer);
        $host     = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

        if ($external !== null && strtolower((string) parse_url($external, PHP_URL_HOST)) !== explode(':', $host)[0]) {
            $out['ref'] = $external;
        }

        return $out;
    }

    /** referrer ภายนอก — ตัด query string ทิ้ง (อาจมีข้อมูลส่วนบุคคลของเว็บต้นทาง) */
    public static function referrer(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('#^https?://#i', $value) !== 1) {
            return null;
        }

        $parts = parse_url($value);

        if (!is_array($parts) || !isset($parts['host'])) {
            return null;
        }

        return mb_substr($parts['scheme'] . '://' . $parts['host'] . ($parts['path'] ?? '/'), 0, 500);
    }

    /**
     * @param  array<string, mixed> $source
     * @return array<string, string>
     */
    private static function pick(array $source, string $prefix = 'utm_'): array
    {
        $out = [];

        foreach (self::KEYS as $key) {
            $value = $source[$prefix . $key] ?? null;

            if (!is_string($value)) {
                continue;
            }

            $value = trim((string) preg_replace('/[^\p{L}\p{N} ._\-+:\/|]/u', '', $value));

            if ($value !== '') {
                $out[$key] = mb_substr($value, 0, self::MAX_LEN);
            }
        }

        return $out;
    }

    private static function cookiePath(): string
    {
        $base = (string) (parse_url((string) config('url', ''), PHP_URL_PATH) ?? '');

        return rtrim($base, '/') . '/';
    }
}

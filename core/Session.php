<?php
/**
 * ‼️ ไฟล์เดียวในระบบที่แตะ $_SESSION ได้
 *
 * ทุกอย่างที่ต้องใช้ session (Auth, ภาษา, ธีม, flash message) ต้องผ่านที่นี่
 * เหตุผล: วันที่เปลี่ยนวิธีเก็บ session จะได้แก้ที่เดียว ไม่ต้องไล่ทั้งโปรเจค
 */

declare(strict_types=1);

namespace Core;

final class Session
{
    private const FLASH_KEY = '_flash';

    public static function start(array $config = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (bool) ($config['secure'] ?? false);

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $secure,
            'path'     => Path::base() === '' ? '/' : Path::base(),
        ]);

        session_start();

        self::expireFlash();
        self::enforceIdleTimeout((int) ($config['idle_timeout'] ?? 0));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** เรียกทุกครั้งหลัง login สำเร็จ เพื่อกัน session fixation */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** ล้างให้หมดจริง ไม่ใช่แค่ลบบางคีย์ */
    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    // ------------------------------------------------------------ flash

    /**
     * ข้อความที่แสดงครั้งเดียวหลัง redirect
     *
     * @param string $type success | warning | danger | info — ต้องตรงกับ token ใน DESIGN.md
     */
    public static function flash(string $type, string $message, string $reference = ''): void
    {
        $_SESSION[self::FLASH_KEY]['next'][] = [
            'type'      => $type,
            'message'   => $message,
            'reference' => $reference,
        ];
    }

    /** @return array<int, array{type:string,message:string,reference:string}> */
    public static function flashes(): array
    {
        $items = $_SESSION[self::FLASH_KEY]['now'] ?? [];
        unset($_SESSION[self::FLASH_KEY]['now']);

        return $items;
    }

    /** เลื่อนข้อความที่ตั้งไว้รอบก่อนมาเป็นของรอบนี้ */
    private static function expireFlash(): void
    {
        $_SESSION[self::FLASH_KEY]['now']  = $_SESSION[self::FLASH_KEY]['next'] ?? [];
        $_SESSION[self::FLASH_KEY]['next'] = [];
    }

    // ------------------------------------------------------------ old input

    /**
     * เก็บค่าที่ผู้ใช้กรอกไว้เมื่อ validate ไม่ผ่าน
     *
     * ‼️ ฟอร์ม 15 ช่องแล้วต้องกรอกใหม่หมดเพราะผิดช่องเดียว คือเหตุผลที่คนเลิกใช้ระบบ
     *
     * @param array<string, mixed> $input
     * @param array<string, string> $errors
     */
    public static function keepInput(array $input, array $errors = []): void
    {
        unset($input['password'], $input['password_confirm'], $input['_token'], $input['_method']);

        $_SESSION[self::FLASH_KEY]['next_input']  = $input;
        $_SESSION[self::FLASH_KEY]['next_errors'] = $errors;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION[self::FLASH_KEY]['input'][$key] ?? $default;
    }

    public static function error(string $key): ?string
    {
        return $_SESSION[self::FLASH_KEY]['errors'][$key] ?? null;
    }

    /** @return array<string, string> */
    public static function errors(): array
    {
        return $_SESSION[self::FLASH_KEY]['errors'] ?? [];
    }

    /** เรียกที่ต้น request หลัง start() เพื่อสลับรอบของ old input */
    public static function rotateInput(): void
    {
        $_SESSION[self::FLASH_KEY]['input']  = $_SESSION[self::FLASH_KEY]['next_input'] ?? [];
        $_SESSION[self::FLASH_KEY]['errors'] = $_SESSION[self::FLASH_KEY]['next_errors'] ?? [];

        $_SESSION[self::FLASH_KEY]['next_input']  = [];
        $_SESSION[self::FLASH_KEY]['next_errors'] = [];
    }

    // ------------------------------------------------------------ timeout

    private static function enforceIdleTimeout(int $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        $last = (int) ($_SESSION['_last_seen'] ?? 0);

        if ($last > 0 && (time() - $last) > $seconds) {
            self::destroy();
            self::start();
        }

        $_SESSION['_last_seen'] = time();
    }
}

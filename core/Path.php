<?php
/**
 * ตัวหา base path ของระบบ — หัวใจของ url()
 *
 * ระบบต้องเปิดได้ทั้ง 2 กรณีโดยไม่ต้องตั้งค่าอะไร
 *   · document root ชี้ที่ public/ ตรงๆ        → /login
 *   · ติดตั้งใต้ subdirectory                  → /ชื่อระบบ/login
 *
 * ‼️ เขียน path ดิบไว้ในหน้า วันที่ย้ายที่ติดตั้งลิงก์จะพังทั้งระบบและ asset โหลดไม่ขึ้น
 */

declare(strict_types=1);

namespace Core;

final class Path
{
    private static ?string $base = null;

    /** ส่วนนำหน้าที่ต้องเติมให้ทุกลิงก์ — คืนค่าว่างเมื่อ document root ชี้ที่ public/ */
    public static function base(): string
    {
        if (self::$base !== null) {
            return self::$base;
        }

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        /*
         * ‼️ ต้องแปลง backslash เป็น slash หลัง dirname() ด้วย ไม่ใช่แค่ก่อน
         *    บน Windows dirname('/index.php') คืนค่าเป็น \ ไม่ใช่ /
         *    ถ้าไม่แปลง ทุกลิงก์จะกลายเป็น "\/path" ซึ่งเบราว์เซอร์อ่านเป็นคนละโดเมน
         *    และ asset ทั้งระบบจะโหลดไม่ขึ้นบนเครื่องของทีมที่ใช้ Windows
         */
        $base = str_replace('\\', '/', dirname($script));
        $base = rtrim($base, '/');

        self::$base = ($base === '' || $base === '.') ? '' : $base;

        return self::$base;
    }

    /** ใช้ตอนทดสอบหรือตอนรันจาก CLI ที่ไม่มี SCRIPT_NAME */
    public static function setBase(string $base): void
    {
        self::$base = $base === '/' ? '' : rtrim($base, '/');
    }

    /** สร้างลิงก์ที่ใช้ได้ทุกที่ติดตั้ง */
    public static function url(string $path = '/'): string
    {
        if (preg_match('/^(?:https?:|mailto:|tel:|data:|#)/i', $path) === 1) {
            return $path;
        }

        return self::base() . '/' . ltrim($path, '/');
    }

    /** เส้นทางภายในแอปของ request ปัจจุบัน ไม่มี base path ไม่มี query string */
    public static function current(): string
    {
        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $uri  = explode('?', $uri, 2)[0];
        $base = self::base();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = '/' . trim(rawurldecode($uri), '/');

        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    /**
     * เส้นทางเต็มรวม query string — ใช้เก็บบริบทของระบบแจ้งปัญหา
     *
     * ต้องเก็บ query string ด้วย เพราะเคส "ข้อมูลแสดงผิด" มักขึ้นกับตัวกรองที่เลือกอยู่ตอนนั้น
     */
    public static function full(): string
    {
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

        return self::current() . ($query === '' ? '' : '?' . $query);
    }
}

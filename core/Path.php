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

        /*
         * ‼️ กรณีตั้ง document root ที่ public/ ไม่ได้ (เช่น http://IP/~user/ระบบ/)
         *    .htaccess ที่ root ของโปรเจค rewrite ทุก request เข้า public/ ภายใน
         *    SCRIPT_NAME จึงเป็น /ระบบ/public/index.php แต่ URL ที่ผู้ใช้เห็นคือ /ระบบ/...
         *    ถ้าไม่ตัด /public ออก ทุกลิงก์จะมี /public ติดไป และ current() จับ route ไม่ได้
         */
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        if (str_ends_with($base, '/public') && !str_starts_with($uri, $base . '/') && $uri !== $base) {
            $base = substr($base, 0, -strlen('/public'));
        }

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
     * เส้นทางที่ผู้ใช้ขอมาจริง — decode แล้ว และ **คง / ท้ายไว้** (ไม่มี base path ไม่มี query string)
     *
     * ‼️ current() ตัด / ท้ายทิ้งเพื่อให้ route หลังบ้านจับได้ทั้งสองแบบ
     *    แต่เว็บสาธารณะต้องรู้ว่ามี / ท้ายหรือไม่ เพราะ /contact กับ /contact/ คนละ URL ในสายตา Google
     *    (ไม่มี / → 301 ไปตัวที่มี — PLAN.md "Routing สาธารณะ" ข้อ 3)
     *
     * ‼️ ยุบ // ที่ซ้ำกันเหลือตัวเดียว — ไม่งั้น //evil.example ที่ถูก redirect ต่อ
     *    จะกลายเป็น Location ที่เบราว์เซอร์อ่านเป็นโดเมนอื่น (open redirect)
     *
     * เบราว์เซอร์ส่ง path ภาษาไทยมาแบบ percent-encode เสมอ — decode ที่นี่ที่เดียว
     * แล้วทั้งระบบเทียบกับค่าที่เก็บแบบ decode ในฐานข้อมูล
     */
    public static function requested(): string
    {
        $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $uri  = explode('?', $uri, 2)[0];
        $base = self::base();

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        $uri = (string) preg_replace('#/{2,}#', '/', '/' . rawurldecode($uri));

        return $uri;
    }

    /**
     * เข้ารหัส path ที่ decode แล้วกลับเป็นรูปที่ใส่ใน Location / sitemap / canonical ได้
     *
     * ‼️ เข้ารหัสทีละช่วงระหว่าง / — ห้าม rawurlencode() ทั้งก้อน ไม่งั้น / กลายเป็น %2F
     */
    public static function encode(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
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

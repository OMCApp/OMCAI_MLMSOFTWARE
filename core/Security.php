<?php
/**
 * CSRF และ security headers — ตั้งที่นี่จุดเดียวทั้งระบบ
 */

declare(strict_types=1);

namespace Core;

final class Security
{
    private const TOKEN_KEY = '_csrf';

    /** token ผูกกับ session — สร้างครั้งเดียวต่อ session */
    public static function token(): string
    {
        $token = (string) Session::get(self::TOKEN_KEY, '');

        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put(self::TOKEN_KEY, $token);
        }

        return $token;
    }

    /**
     * ‼️ เทียบด้วย hash_equals() ไม่ใช่ == เพื่อกัน timing attack
     */
    public static function verify(?string $candidate): bool
    {
        $token = (string) Session::get(self::TOKEN_KEY, '');

        if ($token === '' || $candidate === null || $candidate === '') {
            return false;
        }

        return hash_equals($token, $candidate);
    }

    /** เรียกที่ Router ก่อนส่งต่อให้ Controller ทุก request ที่เปลี่ยนข้อมูล */
    public static function verifyRequest(string $method): bool
    {
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        if (!self::verify(isset($_POST['_token']) ? (string) $_POST['_token'] : null)) {
            return false;
        }

        return self::sameOrigin();
    }

    /** ตรวจ Origin/Referer ควบคู่กับ token */
    private static function sameOrigin(): bool
    {
        $host   = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '');

        if ($host === '' || $origin === '') {
            // บาง proxy ตัด header เหล่านี้ทิ้ง — token ยังกันอยู่ชั้นหนึ่งแล้ว
            return true;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);

        return is_string($originHost) && strcasecmp($originHost, explode(':', $host)[0]) === 0;
    }

    /** สร้าง token ใหม่หลัง login เพื่อไม่ให้ token เดิมที่คนอื่นอาจรู้ยังใช้ได้ */
    public static function rotate(): void
    {
        Session::forget(self::TOKEN_KEY);
        self::token();
    }

    /**
     * ‼️ CSP ตั้งเข้มได้ง่ายเป็นพิเศษในมาตรฐานนี้ เพราะเราไม่โหลดอะไรจาก CDN อยู่แล้ว
     *    ห้ามใส่ unsafe-inline — ให้ย้าย inline script ไปไฟล์ JS แทน
     */
    public static function headers(bool $https = false): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        header(
            "Content-Security-Policy: default-src 'self'; "

            /* ‼️ blob: จำเป็นสำหรับรูปย่อของไฟล์ที่ผู้ใช้เพิ่งเลือก ก่อนอัปโหลด
               partials/upload.php ใช้ URL.createObjectURL() ซึ่งได้ blob: URL
               ไม่อนุญาตแล้วรูปย่อจะถูกบล็อกเงียบๆ — <img> ยังอยู่ในหน้าแต่ไม่มีภาพ
               เห็นได้ทางเดียวคืออ่าน console ไม่ใช่ดูว่า element มีอยู่ไหม

               ‼️ ผ่อนเฉพาะ img-src เท่านั้น ห้ามเติม blob: ลง script-src เด็ดขาด
                  blob: ใน script-src เปิดทางให้รันสคริปต์ที่ประกอบขึ้นตอนรันไทม์ */
            . "img-src 'self' data: blob:; "
            . "font-src 'self'; "
            . "style-src 'self'; "
            . "script-src 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'self'; "
            . "base-uri 'self'"
        );

        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /**
     * ขยาย CSP ของหน้าสาธารณะที่มีรหัสติดตาม (GA4/GTM) — เพิ่มเฉพาะโดเมนของ Google ไม่เพิ่ม unsafe-inline
     * ‼️ เรียกจาก PublicController เท่านั้น และเฉพาะเมื่อ TrackingService::active() — หลังบ้านไม่ได้รับผลนี้
     */
    public static function allowTracking(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Content-Security-Policy: ' . self::trackingPolicy(self::nonce(true)));
    }

    /** nonce ของ request นี้ — มีเฉพาะหน้าที่เปิด CSP ของรหัสติดตาม ('' = หน้านี้ไม่ใช้ nonce) */
    private static string $nonce = '';

    public static function nonce(bool $create = false): string
    {
        if ($create && self::$nonce === '') {
            self::$nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        }

        return self::$nonce;
    }

    /**
     * CSP ของหน้าที่มีรหัสติดตาม
     *
     * ‼️ script-src ใช้ nonce + 'strict-dynamic' — สคริปต์ที่รันได้คือที่มี nonce ของ request นี้ (app.js, tracking.js)
     *    และสคริปต์ที่สคริปต์เหล่านั้นสร้าง (gtag/gtm ของ Google) เท่านั้น · ไม่อนุญาตทั้งโดเมน googletagmanager.com
     *    (ใครก็สร้าง container บนโดเมนนั้นได้) — host ระบุเป็นไฟล์ตรงตัว /gtag/js และ /gtm.js สำหรับเบราว์เซอร์ CSP2 ที่ไม่รู้จัก strict-dynamic
     */
    public static function trackingPolicy(string $nonce): string
    {
        $collect = 'https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com';

        return "default-src 'self'; "
            . "img-src 'self' data: blob: {$collect}; "
            . "font-src 'self'; "
            . "style-src 'self'; "
            . "script-src 'nonce-{$nonce}' 'strict-dynamic' 'self' https://www.googletagmanager.com/gtag/js https://www.googletagmanager.com/gtm.js; "
            . "connect-src 'self' {$collect}; "
            . "object-src 'none'; "
            . "form-action 'self'; "
            . "frame-ancestors 'self'; "
            . "base-uri 'self'";
    }

    /**
     * ทำให้ชื่อไฟล์ปลอดภัยสำหรับใช้เป็นชื่อบนดิสก์
     *
     * ‼️ ใช้กับ "ชื่อที่จะเก็บ" เท่านั้น — ชื่อเดิมของผู้ใช้ต้องเก็บไว้ในฐานข้อมูลเพื่อแสดงผล
     *    ชื่อไฟล์ภาษาไทยและอักขระพิเศษพังบน server จริง แต่ผู้ใช้ต้องเห็นชื่อที่ตัวเองตั้ง
     */
    public static function safeFilename(string $name): string
    {
        $name = str_replace(["\0", '/', '\\'], '', $name);
        $name = (string) preg_replace('/[^A-Za-z0-9._-]+/u', '-', $name);
        $name = trim((string) preg_replace('/-+/', '-', $name), '-.');

        return $name === '' ? 'file' : mb_substr($name, 0, 100);
    }

    /**
     * ‼️ กัน CSV injection
     *    ค่าที่ขึ้นต้นด้วย = + - @ tab หรือ CR จะถูก Excel และ Google Sheets ตีความเป็นสูตร
     *    แปลว่าข้อความที่ผู้ใช้กรอกเข้าระบบ กลายเป็นคำสั่งที่รันบนเครื่องคนที่เปิดไฟล์
     */
    public static function csvCell(mixed $value): string
    {
        $text = (string) $value;

        if ($text !== '' && str_contains("=+-@\t\r", $text[0])) {
            return "'" . $text;
        }

        return $text;
    }
}

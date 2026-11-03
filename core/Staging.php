<?php
/**
 * ประตูของ environment ที่ไม่ใช่ production
 *
 * ‼️ ทุก environment ที่ไม่ใช่ production ต้องไม่ถูก index
 *    เว็บนี้กำลังแทนเว็บที่มีอันดับอยู่แล้ว — ถ้า staging ถูก index
 *    Google จะเห็นเนื้อหาซ้ำกับเว็บจริง และอาจเลือก URL ของ staging ขึ้นผลค้นหาแทน
 *
 * ‼️ ถ้าตั้ง STAGING_PASS ไว้ ทุกหน้าต้องผ่าน HTTP Basic Auth ก่อน
 *    ยกเว้น /_migrate (มี token ของตัวเอง) และ /_health (CI ใช้เช็คว่าระบบขึ้น)
 */

declare(strict_types=1);

namespace Core;

final class Staging
{
    private const OPEN_PATHS = ['/_migrate', '/_health'];

    /** @param array{env:string,staging:array{user:string,pass:string}} $config */
    public static function guard(array $config): void
    {
        if ($config['env'] === 'production') {
            return;
        }

        header('X-Robots-Tag: noindex, nofollow, noarchive', true);

        $user = $config['staging']['user'];
        $pass = $config['staging']['pass'];

        if ($pass === '' || in_array(Path::current(), self::OPEN_PATHS, true)) {
            return;
        }

        [$givenUser, $givenPass] = self::credentials();

        // hash_equals ทั้งสองค่าเสมอ ไม่หยุดที่ค่าแรก เพื่อไม่ให้เวลาตอบบอกว่าผิดที่ไหน
        $userOk = hash_equals($user, $givenUser);
        $passOk = hash_equals($pass, $givenPass);

        if ($userOk && $passOk) {
            return;
        }

        if ($givenUser !== '' || $givenPass !== '') {
            Logger::warning('staging_auth_failed', ['ip' => Auth::ip()]);
        }

        http_response_code(401);
        header('WWW-Authenticate: Basic realm="Staging", charset="UTF-8"');
        header('Content-Type: text/plain; charset=utf-8');
        echo "Authentication required\n";
        exit;
    }

    /**
     * อ่าน user/pass จาก request
     *
     * ‼️ PHP แบบ CGI/FPM ไม่ได้เติม PHP_AUTH_USER ให้ — ต้องอ่านจาก HTTP_AUTHORIZATION
     *    ซึ่ง public/.htaccess ส่งต่อมาให้
     *
     * @return array{0:string,1:string}
     */
    private static function credentials(): array
    {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            return [(string) $_SERVER['PHP_AUTH_USER'], (string) ($_SERVER['PHP_AUTH_PW'] ?? '')];
        }

        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

        if (stripos($header, 'Basic ') !== 0) {
            return ['', ''];
        }

        $decoded = base64_decode(substr($header, 6), true);

        if ($decoded === false || !str_contains($decoded, ':')) {
            return ['', ''];
        }

        [$user, $pass] = explode(':', $decoded, 2);

        return [$user, $pass];
    }
}

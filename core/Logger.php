<?php
/**
 * ตัวเขียน log กลางจุดเดียว + รหัสอ้างอิง error
 *
 * รหัสอ้างอิง (ERR-7X4K2) คือสะพานที่ทำให้ผู้ใช้แจ้งปัญหาแล้วสืบต่อได้จริง
 *   ผู้ใช้เห็นรหัส → แจ้งเข้าระบบ → แอดมินค้น log ด้วยรหัสนั้นเจอทันที
 *   และผู้ใช้ไม่เห็นข้อมูลทางเทคนิคที่ไม่ควรเห็น
 *
 * ‼️ ห้ามเรียก error_log() กระจัดกระจาย ต้องผ่านที่นี่เท่านั้น
 */

declare(strict_types=1);

namespace Core;

use Throwable;

final class Logger
{
    private static string $path  = '';
    private static bool   $debug = false;

    /** คีย์ที่ห้ามหลุดลง log เด็ดขาด */
    private const SECRET_KEYS = [
        'password', 'password_confirm', 'passwd', 'secret', 'token',
        'authorization', 'api_key', 'apikey', 'card', 'cvv', 'pin',
    ];

    public static function configure(string $path, bool $debug = false): void
    {
        self::$path  = $path;
        self::$debug = $debug;

        if ($path !== '' && !is_dir(dirname($path))) {
            @mkdir(dirname($path), 0o777, true);
        }
    }

    /** สร้างรหัสสั้นที่ไม่ซ้ำ — อ่านออกทางโทรศัพท์ได้ จึงตัดตัวที่สับสน (0/O, 1/I) ออก */
    public static function reference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code     = '';

        for ($i = 0; $i < 5; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return 'ERR-' . $code;
    }

    /** @param array<string, mixed> $context */
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    /**
     * @param  array<string, mixed> $context
     * @return string รหัสอ้างอิงที่เอาไปแสดงให้ผู้ใช้
     */
    public static function error(string $message, array $context = []): string
    {
        $reference = self::reference();
        self::write('ERROR', $message, $context + ['reference' => $reference]);

        return $reference;
    }

    /** @return string รหัสอ้างอิง */
    public static function exception(Throwable $e): string
    {
        $reference = self::reference();

        self::write('ERROR', get_class($e) . ': ' . $e->getMessage(), [
            'reference' => $reference,
            'file'      => $e->getFile() . ':' . $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);

        return $reference;
    }

    /** @param array<string, mixed> $context */
    private static function write(string $level, string $message, array $context): void
    {
        if (self::$path === '') {
            return;
        }

        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            $level,
            str_replace(["\r", "\n"], ' ', $message),
            $context === [] ? '' : ' | ' . self::encode(self::redact($context))
        );

        @file_put_contents(self::$path, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * ตัดข้อมูลอ่อนไหวออกก่อนเขียนลงไฟล์
     *
     * @param  array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = self::redact($value);
                continue;
            }

            foreach (self::SECRET_KEYS as $secret) {
                if (stripos((string) $key, $secret) !== false) {
                    $context[$key] = '[redacted]';
                    break;
                }
            }
        }

        return $context;
    }

    /** @param array<string, mixed> $context */
    private static function encode(array $context): string
    {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? '[uncodable context]' : $json;
    }

    // ------------------------------------------------------- ตัวรับ error กลาง

    public static function register(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }

            // ‼️ PHP warning ไม่ลง log และไม่ขึ้นใน console ถ้าไม่ดักไว้
            //    มันถูกพิมพ์แทรกลงใน HTML ตรงๆ ขณะที่หน้ายังตอบ 200 ปกติ
            self::write('WARNING', $message, ['file' => $file . ':' . $line]);

            return !self::$debug;
        });

        set_exception_handler(static function (Throwable $e): void {
            $reference = self::exception($e);
            self::renderFatal($reference, $e);
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();

            if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                return;
            }

            $reference = self::error($error['message'], [
                'file' => $error['file'] . ':' . $error['line'],
            ]);

            self::renderFatal($reference, null);
        });
    }

    private static function renderFatal(string $reference, ?Throwable $e): void
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "[{$reference}] " . ($e?->getMessage() ?? 'fatal error') . "\n");

            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        // บน production ห้ามแสดง stack trace ให้ผู้ใช้เห็นเด็ดขาด
        if (self::$debug && $e !== null) {
            echo '<pre>' . htmlspecialchars(
                $reference . "\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString(),
                ENT_QUOTES,
                'UTF-8'
            ) . '</pre>';

            return;
        }

        $view = dirname(__DIR__) . '/app/Views/pages/errors/500.php';

        if (is_file($view)) {
            include $view;

            return;
        }

        echo htmlspecialchars($reference, ENT_QUOTES, 'UTF-8');
    }
}

<?php
/**
 * ระบบหลายภาษา — helper กลางจุดเดียว
 *
 * ‼️ สัญญาของ __() : ผลลัพธ์เอาไปแสดงได้ตรงๆ อย่างปลอดภัย ไม่ต้อง escape ซ้ำ
 *    รักษาสัญญานี้ได้เพราะ (1) ไฟล์ภาษาห้ามมีแท็ก HTML
 *                          (2) ค่าที่แทนใน placeholder ถูก escape ให้อัตโนมัติ
 *    แต่ข้อมูลจากฐานข้อมูลที่ไม่ได้ผ่าน __() ยังต้องผ่าน e() ตามปกติ
 */

declare(strict_types=1);

namespace Core;

final class Lang
{
    private static string $locale  = 'th';
    private static string $fallback = 'th';
    private static bool   $debug   = false;
    private static string $dir     = '';

    /** @var array<int, string> */
    private static array $enabled = ['th', 'en'];

    /** @var array<string, array<string, mixed>> */
    private static array $loaded = [];

    /** @param array<string, mixed> $config */
    public static function configure(array $config, string $dir): void
    {
        self::$dir      = $dir;
        self::$enabled  = (array) ($config['locales'] ?? ['th', 'en']);
        self::$fallback = (string) ($config['default_locale'] ?? 'th');
        self::$debug    = (bool) ($config['debug'] ?? false);
        self::$locale   = self::resolve();
    }

    /**
     * ลำดับความสำคัญ — ตัวบนสุดที่มีค่าชนะ
     *   1. ?lang= ใน URL          ส่งลิงก์ให้คนอื่นดูภาษาที่ระบุ
     *   2. users.locale           ผู้ใช้ที่ล็อกอินและตั้งค่าไว้แล้ว (Auth เป็นคนใส่เข้า session)
     *   3. session                ผู้ใช้ที่กดสลับภาษาในรอบนี้
     *   4. Accept-Language        ผู้ใช้ใหม่ที่ยังไม่เคยเลือก
     *   5. ค่า default ใน config
     */
    private static function resolve(): string
    {
        $fromUrl = isset($_GET['lang']) ? (string) $_GET['lang'] : null;

        if ($fromUrl !== null && self::allowed($fromUrl)) {
            Session::put('locale', $fromUrl);

            return $fromUrl;
        }

        $fromSession = (string) Session::get('locale', '');

        if ($fromSession !== '' && self::allowed($fromSession)) {
            return $fromSession;
        }

        foreach (self::acceptedLanguages() as $candidate) {
            if (self::allowed($candidate)) {
                return $candidate;
            }
        }

        return self::$fallback;
    }

    /**
     * ‼️ ต้องเทียบกับ allowlist ก่อนใช้ทุกครั้ง
     *    ห้ามเอาค่าจาก $_GET['lang'] ไปประกอบเป็นชื่อไฟล์ตรงๆ เด็ดขาด
     *    ไม่งั้นกลายเป็นช่องอ่านไฟล์นอกโฟลเดอร์ (path traversal) ทันที
     */
    public static function allowed(string $locale): bool
    {
        return in_array($locale, self::$enabled, true);
    }

    /** @return array<int, string> */
    private static function acceptedLanguages(): array
    {
        $header = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');

        if ($header === '') {
            return [];
        }

        $out = [];

        foreach (explode(',', $header) as $part) {
            $code = strtolower(trim(explode(';', $part)[0]));
            $out[] = explode('-', $code)[0];
        }

        return $out;
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    /** @return array<int, string> */
    public static function enabled(): array
    {
        return self::$enabled;
    }

    /**
     * ใช้ภาษานี้กับ request นี้เท่านั้น — ไม่จำลง session
     *
     * ‼️ เว็บสาธารณะเลือกภาษาจาก URL (/ = th · /en/ · /zh/) ไม่ใช่จาก session หรือ Accept-Language
     *    crawler และผู้ใช้ทุกคนต้องเห็นภาษาเดียวกันที่ URL เดียวกัน (App\Services\LocaleService)
     */
    public static function useForRequest(string $locale): bool
    {
        if (!self::allowed($locale)) {
            return false;
        }

        self::$locale = $locale;

        return true;
    }

    public static function switchTo(string $locale): bool
    {
        if (!self::allowed($locale)) {
            return false;
        }

        self::$locale = $locale;
        Session::put('locale', $locale);

        return true;
    }

    /** @param array<string, mixed> $replace */
    public static function get(string $key, array $replace = []): string
    {
        $value = self::lookup($key, self::$locale);

        if ($value === null && self::$locale !== self::$fallback) {
            $value = self::lookup($key, self::$fallback);
        }

        if ($value === null) {
            // dev เห็น key ดิบเพื่อให้รู้ว่าตกหล่น
            // production ห้ามคืนค่าว่าง ไม่งั้นกลายเป็นปุ่มไม่มีข้อความโดยไม่มีใครรู้
            return self::$debug ? '⟦' . $key . '⟧' : $key;
        }

        /* ‼️ แทนคีย์ที่ยาวที่สุดก่อนเสมอ
           `:to` เป็นคำนำหน้าของ `:total` ถ้าแทน `:to` ก่อน `:total` จะถูกกินไปครึ่งหนึ่ง
           อาการจริงที่เจอ: "จากทั้งหมด 2tal รายการ" — ไม่มี error ใดๆ แจ้ง
           กระทบทุกโปรเจคที่มีคีย์ซ้อนชื่อกัน ซึ่งเกิดง่ายมากกับคีย์สั้นๆ */
        uksort($replace, static fn ($a, $b): int => strlen((string) $b) <=> strlen((string) $a));

        foreach ($replace as $name => $replacement) {
            $value = str_replace(
                ':' . $name,
                htmlspecialchars((string) $replacement, ENT_QUOTES, 'UTF-8'),
                $value
            );
        }

        return $value;
    }

    /** ปีศักราชที่บวกเพิ่มของภาษานั้น (พ.ศ. = 543) — date.year_offset เป็นตัวเลข lookup() จึงอ่านไม่ได้ */
    public static function yearOffset(string $locale): int
    {
        if (preg_match('/^[a-z]{2}(-[a-z0-9]+)?$/i', $locale) !== 1) {
            return 0;
        }

        // ภาษาของเนื้อหา (th/en/zh) ไม่จำเป็นต้องเป็นภาษาที่เปิดใน APP_LOCALES ของหลังบ้าน — อ่านไฟล์ตรง
        $path = (self::$dir !== '' ? self::$dir : dirname(__DIR__) . '/lang') . '/' . $locale . '.php';
        $data = self::$loaded[$locale] ?? [];

        if (!isset($data['date']) && is_file($path)) {
            $data = include $path;
        }

        return (int) ((is_array($data) ? $data : [])['date']['year_offset'] ?? 0);
    }

    private static function lookup(string $key, string $locale): ?string
    {
        $data  = self::load($locale);
        $value = $data;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private static function load(string $locale): array
    {
        if (isset(self::$loaded[$locale])) {
            return self::$loaded[$locale];
        }

        if (!self::allowed($locale)) {
            return self::$loaded[$locale] = [];
        }

        $path = self::$dir . '/' . $locale . '.php';
        $data = is_file($path) ? include $path : [];

        return self::$loaded[$locale] = is_array($data) ? $data : [];
    }

    // --------------------------------------------------------- วันที่และตัวเลข

    /**
     * ‼️ ฐานข้อมูลเก็บเป็นคริสต์ศักราชเสมอ แปลงเป็น พ.ศ. ตอนแสดงเท่านั้น
     *    เก็บ พ.ศ. ลงฐานข้อมูลแล้วการเรียงลำดับและการคำนวณช่วงวันจะเพี้ยนทั้งระบบ
     */
    public static function formatDate(?string $value, bool $withTime = false): string
    {
        if ($value === null || trim($value) === '' || str_starts_with($value, '0000')) {
            return '—';
        }

        $time = strtotime($value);

        if ($time === false) {
            return '—';
        }

        $year  = (int) date('Y', $time);
        $month = (int) date('n', $time);

        // ชื่อเดือนและปีอยู่ในไฟล์ภาษา ไม่ใช่ในโค้ด
        // เพิ่มภาษาใหม่จึงไม่ต้องแตะไฟล์นี้เลย
        $months = self::group('date.months_short');
        $name   = $months[$month - 1] ?? date('M', $time);

        // ปีพุทธศักราชต่างจากคริสต์ศักราช 543 — ระบุในไฟล์ภาษาว่าภาษานั้นบวกเท่าไหร่
        $offset = (int) (self::lookup('date.year_offset', self::$locale) ?? 0);

        $text = date('j', $time) . ' ' . $name . ' ' . ($year + $offset);

        return $withTime ? $text . ' ' . date('H:i', $time) : $text;
    }

    /**
     * อ่านค่าที่เป็นรายการจากไฟล์ภาษา เช่นชื่อเดือน
     *
     * @return array<int, string>
     */
    public static function group(string $key): array
    {
        foreach ([self::$locale, self::$fallback] as $locale) {
            $value = self::load($locale);

            foreach (explode('.', $key) as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    $value = null;
                    break;
                }

                $value = $value[$segment];
            }

            if (is_array($value)) {
                return array_values(array_map('strval', $value));
            }
        }

        return [];
    }

    public static function formatNumber(int|float|string|null $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, $decimals);
    }
}

<?php
/**
 * ภาษาของเว็บสาธารณะ — มาจาก URL เท่านั้น · รายการภาษาอยู่ที่ config/locales.php ที่เดียว
 *
 *   /…        ภาษาหลัก (ไทย · URL เดิมของเว็บเก่าทุกตัวอยู่ที่นี่ ไม่มี prefix)
 *   /en/…     อังกฤษ
 *   /zh/…     จีนตัวย่อ
 *
 * ‼️ ไม่อ่าน Accept-Language / IP / session สำหรับหน้าสาธารณะ และไม่ redirect ตามภาษาของเบราว์เซอร์
 *    crawler และผู้ใช้ทุกคนต้องเห็นภาษาเดียวกันที่ URL เดิม (หลังบ้านยังใช้ลำดับเดิมของ Core\Lang)
 * ‼️ ค่าในฐานข้อมูลเก็บเป็น code (th|en|zh) — แปลงเป็น lang/hreflang/og:locale ที่นี่จุดเดียว
 */

declare(strict_types=1);

namespace App\Services;

use Core\Lang;

final class LocaleService
{
    /** ‼️ ต้องตรงกับ key แรกของ config/locales.php (ใช้เป็นค่าเริ่มต้นของพารามิเตอร์ซึ่งต้องเป็นค่าคงที่) */
    public const DEFAULT = 'th';

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $config = null;

    private static string $current = self::DEFAULT;

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        if (self::$config === null) {
            self::$config = (array) require dirname(__DIR__, 2) . '/config/locales.php';
        }

        return self::$config;
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function exists(string $locale): bool
    {
        return isset(self::all()[$locale]);
    }

    /** regex ของ prefix ภาษาที่ไม่ใช่ภาษาหลัก (en|zh) — ใช้ประกาศ route */
    public static function prefixPattern(): string
    {
        $codes = array_filter(self::codes(), static fn (string $code): bool => self::prefix($code) !== '');

        return implode('|', array_map(static fn (string $code): string => preg_quote(ltrim(self::prefix($code), '/'), '#'), $codes));
    }

    /**
     * แยก prefix ภาษาออกจาก path
     *
     * @return array{0:string, 1:string} [locale, path ที่ไม่มี prefix — ขึ้นต้นด้วย /]
     */
    public static function split(string $path): array
    {
        foreach (self::all() as $locale => $row) {
            $prefix = (string) $row['prefix'];

            if ($prefix === '') {
                continue;
            }

            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                $rest = substr($path, strlen($prefix));

                return [$locale, $rest === '' ? '/' : $rest];
            }
        }

        return [self::DEFAULT, $path];
    }

    public static function localeOf(string $path): string
    {
        return self::split($path)[0];
    }

    /** '' สำหรับภาษาหลัก · '/en' · '/zh' */
    public static function prefix(string $locale): string
    {
        return (string) (self::all()[$locale]['prefix'] ?? '');
    }

    /** path ภาษาหลัก (หรือ path ไม่มี prefix) → path ของภาษานี้ */
    public static function localize(string $path, string $locale): string
    {
        return self::prefix($locale) . $path;
    }

    /** ตั้งภาษาของ request นี้ — ข้อความ UI (__()) ตามภาษาของ URL */
    public static function use(string $locale): void
    {
        self::$current = self::exists($locale) ? $locale : self::DEFAULT;
        Lang::useForRequest(self::$current);
    }

    public static function current(): string
    {
        return self::$current;
    }

    public static function htmlLang(?string $locale = null): string
    {
        return (string) (self::all()[$locale ?? self::$current]['html_lang'] ?? self::DEFAULT);
    }

    public static function ogLocale(?string $locale = null): string
    {
        return (string) (self::all()[$locale ?? self::$current]['og_locale'] ?? 'th_TH');
    }

    /** @return list<string> ฟอนต์ที่ต้อง preload ของภาษานี้ */
    public static function preloadFonts(?string $locale = null): array
    {
        return array_values((array) (self::all()[$locale ?? self::$current]['preload_fonts'] ?? []));
    }

    public static function label(string $locale): string
    {
        return (string) (self::all()[$locale]['label'] ?? strtoupper($locale));
    }

    public static function name(string $locale): string
    {
        return (string) (self::all()[$locale]['name'] ?? $locale);
    }

    /** หน้าแรกของภาษา — ปุ่มสลับภาษาใช้เมื่อหน้านี้ยังไม่มีคำแปล */
    public static function home(string $locale): string
    {
        return self::prefix($locale) . '/';
    }
}

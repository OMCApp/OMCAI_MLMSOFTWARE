<?php
/**
 * ข้อมูลของ layout เว็บสาธารณะ (header · เมนู · footer · ข้อมูลติดต่อ · ตัวสลับภาษา)
 *
 * ‼️ layouts/site.php รับข้อมูลเป็น view-model ก้อนเดียว ($site) จากที่นี่
 *    ไม่อ่าน config เอง — วันที่ย้ายเมนูไปอยู่ในตาราง menus / settings (Phase 3)
 *    แก้ที่ไฟล์นี้ไฟล์เดียว view ไม่ต้องรู้ว่าข้อมูลมาจากไหน
 *
 * ‼️ ค่าตั้งต้นอยู่ที่ config/site.php จุดเดียว · ภาษาอื่นทับเฉพาะ key ที่ระบุใน 'locales'
 * ‼️ URL ในเมนูของทุกภาษาเขียนเป็น path ภาษาไทย — แปลงเป็น path ของภาษาที่แสดงที่นี่ (localizePath)
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Page;

final class SiteService
{
    /** @var array<string, mixed>|null */
    private static ?array $defaults = null;

    /** @var array<string, array<string, string>> locale => [th path => path ของภาษานั้น] */
    private static array $pathMaps = [];

    /**
     * view-model ของ layout สาธารณะ — ทำเครื่องหมายเมนูที่ตรงกับหน้าปัจจุบันให้แล้ว
     *
     * @param  array<string, string> $translations locale => path ของหน้านี้ในภาษานั้น (ตัวสลับภาษา)
     * @return array{brand: array<string, mixed>, contact: array<string, string>, cta: array<string, string>,
     *               nav: list<array<string, mixed>>, footer: array<string, mixed>, languages: list<array<string, mixed>>,
     *               home: string, locale: string}
     */
    public static function layout(string $currentPath, ?string $locale = null, array $translations = []): array
    {
        $locale = $locale ?? LocaleService::current();
        $site   = self::forLocale($locale);
        $map    = static fn (array $entry): array => self::localizeEntry($entry, $locale);

        $nav = array_map(
            static fn (array $entry): array => self::markActive($map($entry), $currentPath),
            (array) ($site['nav'] ?? [])
        );

        $footer = (array) ($site['footer'] ?? []);

        foreach ((array) ($footer['columns'] ?? []) as $index => $column) {
            $footer['columns'][$index]['links'] = array_map($map, (array) ($column['links'] ?? []));
        }

        $footer['legal'] = array_map($map, (array) ($footer['legal'] ?? []));

        $cta = array_map('strval', (array) ($site['cta'] ?? []));
        $cta['url'] = self::localizePath((string) ($cta['url'] ?? '/demo/'), $locale);

        return [
            'brand'     => (array) ($site['brand'] ?? []),
            'contact'   => array_map('strval', (array) ($site['contact'] ?? [])),
            'cta'       => $cta,
            'nav'       => array_values($nav),
            'footer'    => $footer,
            'languages' => self::languages($locale, $translations),
            'home'      => LocaleService::home($locale),
            'locale'    => $locale,
            'html_lang' => LocaleService::htmlLang($locale),
            'preload_fonts' => LocaleService::preloadFonts($locale),
        ];
    }

    /** ผู้รับอีเมลแจ้งเตือน lead ใหม่ */
    public static function leadEmail(): string
    {
        return (string) (self::defaults()['lead_email'] ?? '');
    }

    /** วันจดทะเบียนบริษัท (Y-m-d) */
    public static function founded(): string
    {
        return (string) (self::defaults()['brand']['founded'] ?? '2009-04-03');
    }

    /** คำแนะนำบริษัทของภาษานั้น — JSON-LD Organization.description */
    public static function about(string $locale): string
    {
        return (string) (self::forLocale($locale)['brand']['about'] ?? '');
    }

    /** URL หน้านโยบายความเป็นส่วนตัวของภาษาที่แสดงอยู่ — ฟอร์ม lead ลิงก์ไปที่นี่ */
    public static function privacyUrl(?string $locale = null): string
    {
        $path = (string) (self::defaults()['privacy_url'] ?? '');

        return $path === '' ? '' : self::localizePath($path, $locale ?? LocaleService::current());
    }

    /**
     * path ภาษาไทย → path ของภาษานั้น
     *   หน้าที่มีคำแปลเผยแพร่แล้ว → path ของคำแปล (เช่น นโยบายความเป็นส่วนตัว → /en/privacy-policy/)
     *   ไม่มี → path ภาษาไทยเดิม (เช่น /blog/ ที่ยังไม่มีบทความแปล) · ไม่ใช่ path ภายใน (tel:, https:, #) → คืนตามเดิม
     */
    public static function localizePath(string $path, string $locale): string
    {
        if ($locale === LocaleService::DEFAULT || !str_starts_with($path, '/')) {
            return $path;
        }

        $map = self::pathMap($locale);

        // ไม่มีคำแปล → ลิงก์ไปหน้าภาษาไทย (ห้ามสร้างลิงก์ไป /en/... ที่ไม่มีอยู่จริง)
        return $map[$path] ?? $path;
    }

    /**
     * ปุ่มสลับภาษา — ไปหน้าเดียวกันในภาษานั้นถ้ามีคำแปล ไม่มีไปหน้าแรกของภาษานั้น
     *
     * @param  array<string, string> $translations
     * @return list<array{code:string, label:string, url:string, current:bool, lang:string}>
     */
    private static function languages(string $locale, array $translations): array
    {
        $out    = [];

        foreach (LocaleService::codes() as $code) {
            $out[] = [
                'code'    => $code,
                'label'   => LocaleService::label($code),
                'url'     => $translations[$code] ?? LocaleService::home($code),
                'current' => $code === $locale,
                'lang'    => LocaleService::htmlLang($code),
                'name'    => LocaleService::name($code),
            ];
        }

        return $out;
    }

    /** @return array<string, mixed> ค่าของภาษานี้ = ค่าไทย ทับด้วยค่าใน locales.<locale> */
    private static function forLocale(string $locale): array
    {
        $site     = self::defaults();
        $override = (array) ($site['locales'][$locale] ?? []);

        foreach ($override as $key => $value) {
            $site[$key] = is_array($value) && in_array($key, ['brand', 'contact'], true)
                ? array_replace((array) ($site[$key] ?? []), $value)
                : $value;
        }

        return $site;
    }

    /**
     * @param  array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private static function localizeEntry(array $entry, string $locale): array
    {
        if (isset($entry['url'])) {
            $entry['url'] = self::localizePath((string) $entry['url'], $locale);
        }

        foreach ((array) ($entry['children'] ?? []) as $index => $child) {
            $entry['children'][$index] = self::localizeEntry((array) $child, $locale);
        }

        return $entry;
    }

    /**
     * ‼️ query เดียวต่อ request ต่อภาษา — หน้าไทยที่มีคำแปลเผยแพร่แล้วในภาษานั้น
     *
     * @return array<string, string>
     */
    private static function pathMap(string $locale): array
    {
        if (!isset(self::$pathMaps[$locale])) {
            $rows = (new Page())->translatedPaths($locale);

            self::$pathMaps[$locale] = array_column($rows, 'tr_path', 'th_path');
        }

        return self::$pathMaps[$locale];
    }

    /** @return array<string, mixed> */
    private static function defaults(): array
    {
        if (self::$defaults === null) {
            self::$defaults = (array) require dirname(__DIR__, 2) . '/config/site.php';
        }

        return self::$defaults;
    }

    /**
     * ‼️ เทียบแบบตัด / ท้ายออกทั้งคู่ — Path::current() คืน path ไม่มี / ท้าย
     *    แต่ URL ในเมนูมี / ท้ายตามเว็บเดิม (PLAN.md: ทุก URL สาธารณะลงท้าย /)
     *
     * @param  array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private static function markActive(array $entry, string $currentPath): array
    {
        $current = self::normalize($currentPath);
        $self    = self::normalize((string) ($entry['url'] ?? ''));
        $home    = in_array($self, ['/', '/en', '/zh'], true);

        $entry['current'] = $self === $current;
        $entry['active']  = $entry['current'];

        $children = [];

        foreach ((array) ($entry['children'] ?? []) as $child) {
            $child['current'] = self::normalize((string) ($child['url'] ?? '')) === $current;
            $entry['active']  = $entry['active'] || $child['current'];
            $children[]       = $child;
        }

        // หน้าลูกที่ไม่อยู่ในเมนู (เช่น /compensation-plans/binary/ ที่ถูกตัดออกจากเมนู) ยังให้หัวข้อแม่ติดสถานะ
        if (!$entry['active'] && !$home && $self !== '' && str_starts_with($current . '/', $self . '/')) {
            $entry['active'] = true;
        }

        $entry['children'] = $children;

        return $entry;
    }

    private static function normalize(string $path): string
    {
        $path = '/' . trim(rawurldecode($path), '/');

        return $path === '/' ? '/' : $path;
    }
}

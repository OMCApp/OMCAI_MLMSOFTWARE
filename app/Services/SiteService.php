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

        // Phase 3: ข้อมูลบริษัทจาก settings (CMS) ทับค่า config ทีละช่อง · เมนูจากตาราง menus ของภาษานี้ (ว่าง = config)
        [$site['brand'], $site['contact']] = self::company($site, $locale);
        $site['nav']    = self::menuNav($locale) ?? (array) ($site['nav'] ?? []);
        $site['footer'] = self::menuFooter($locale) ?? (array) ($site['footer'] ?? []);

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
            // ค่าเดี่ยวเป็น string · phones/emails เป็น list<string> (ท้ายเว็บแสดงทุกอีเมล)
            'contact'   => array_map(
                static fn ($value) => is_array($value) ? array_values(array_map('strval', $value)) : (string) $value,
                (array) ($site['contact'] ?? [])
            ),
            'cta'       => $cta,
            // แถบติดต่อติดล่างจอมือถือ (partials/site-mobile-bar.php)
            'contact_url' => self::localizePath('/contact/', $locale),
            'nav'       => array_values($nav),
            'footer'    => $footer,
            'languages' => self::languages($locale, $translations),
            'home'      => LocaleService::home($locale),
            'locale'    => $locale,
            'html_lang' => LocaleService::htmlLang($locale),
            'preload_fonts' => LocaleService::preloadFonts($locale),
        ];
    }

    /** ผู้รับอีเมลแจ้งเตือน lead ใหม่ — CMS (ตั้งค่าเว็บ) ก่อน แล้วตกไป config */
    public static function leadEmail(): string
    {
        $fromCms = trim((string) (SettingService::group('notifications')['lead_email'] ?? ''));

        return $fromCms !== '' ? $fromCms : (string) (self::defaults()['lead_email'] ?? '');
    }

    /** วันจดทะเบียนบริษัท (Y-m-d) — CMS ก่อน แล้วตกไป config */
    public static function founded(): string
    {
        $fromCms = trim((string) (SettingService::group('company')['founded'] ?? ''));

        return $fromCms !== '' ? $fromCms : (string) (self::defaults()['brand']['founded'] ?? '2009-04-03');
    }

    /** คำแนะนำบริษัทของภาษานั้น — JSON-LD Organization.description (CMS ก่อน แล้วตกไป config) */
    public static function about(string $locale): string
    {
        $site = self::forLocale($locale);

        return (string) (self::company($site, $locale)[0]['about'] ?? '');
    }

    /** ค่าตั้งต้นใน config/site.php ของภาษาหลัก (อ่านอย่างเดียว) — หน้า Settings ใช้เติมช่องที่ยังว่าง @return array<string, mixed> */
    public static function configDefaults(): array
    {
        return self::defaults();
    }

    /** ค่าตั้งต้นของภาษานั้น (ไทย + ทับด้วย locales.<code>) @return array<string, mixed> */
    public static function configFor(string $locale): array
    {
        return self::forLocale($locale);
    }

    /**
     * แบรนด์ + ข้อมูลติดต่อ: settings.company (CMS) ทับค่าใน config ทีละช่อง — ช่องว่างไม่ทับ
     * public: JSON-LD Organization + llms.txt ใช้ชุดเดียวกับท้ายเว็บ — company(configFor($locale), $locale)
     *
     * @param  array<string, mixed> $site
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function company(array $site, string $locale): array
    {
        $brand   = (array) ($site['brand'] ?? []);
        $contact = (array) ($site['contact'] ?? []);
        $company = SettingService::group('company');
        $pick    = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

        foreach (['name' => 'name', 'legal_name' => 'legal_name', 'founded' => 'founded'] as $from => $to) {
            if ($pick($company[$from] ?? null) !== '') {
                $brand[$to] = $pick($company[$from]);
            }
        }
        if ($pick($company['description'][$locale] ?? null) !== '') {
            $brand['about'] = $pick($company['description'][$locale]);
        }

        $phones = array_values(array_filter(array_map($pick, (array) ($company['phones'] ?? [])), 'strlen'));
        $emails = array_values(array_filter(array_map($pick, (array) ($company['emails'] ?? [])), 'strlen'));

        if ($phones !== []) {
            $contact['phone']      = $phones[0];
            $contact['phone_e164'] = self::e164($phones[0]);
        }
        if ($emails !== []) {
            $contact['email'] = $emails[0];
        }

        $contact['phones'] = $phones !== [] ? $phones : array_values(array_filter([(string) ($contact['phone'] ?? '')]));
        $contact['emails'] = $emails !== [] ? $emails : array_values(array_filter([(string) ($contact['email'] ?? '')]));

        foreach (['line_id', 'line_url', 'facebook'] as $field) {
            if ($pick($company[$field] ?? null) !== '') {
                $contact[$field] = $pick($company[$field]);
            }
        }
        if ($pick($company['address'][$locale] ?? null) !== '') {
            $contact['address'] = $pick($company['address'][$locale]);
        }

        return [$brand, $contact];
    }

    /** 099-109-5544 → +66991095544 (เบอร์ไทยขึ้นต้น 0) */
    private static function e164(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '+')) {
            return '+' . $digits;
        }

        return str_starts_with($digits, '0') ? '+66' . substr($digits, 1) : $digits;
    }

    /** @return list<array<string, mixed>>|null null = ภาษานี้ยังไม่มีเมนูใน CMS */
    private static function menuNav(string $locale): ?array
    {
        $tree = self::tree('header', $locale);

        if ($tree === []) {
            return null;
        }

        $map = static function (array $node) use (&$map): array {
            $entry = ['label' => $node['label'], 'url' => $node['url']];

            if ($node['desc'] !== '') {
                $entry['desc'] = $node['desc'];
            }
            if ($node['icon'] !== '') {
                $entry['icon'] = $node['icon'];
            }
            if ($node['children'] !== []) {
                $entry['children'] = array_map($map, $node['children']);
            }

            return $entry;
        };

        return array_map($map, $tree);
    }

    /**
     * footer จาก CMS: ชั้นบนที่มีลูก = คอลัมน์ · ชั้นบนที่ไม่มีลูก = ลิงก์นโยบาย
     *
     * @return array{columns: list<array<string, mixed>>, legal: list<array<string, string>>}|null
     */
    private static function menuFooter(string $locale): ?array
    {
        $tree = self::tree('footer', $locale);

        if ($tree === []) {
            return null;
        }

        $columns = [];
        $legal   = [];

        foreach ($tree as $node) {
            if ($node['children'] === []) {
                $legal[] = ['label' => $node['label'], 'url' => $node['url']];
                continue;
            }

            $columns[] = [
                'heading' => $node['label'],
                'links'   => array_map(static fn (array $c): array => ['label' => $c['label'], 'url' => $c['url']], $node['children']),
            ];
        }

        return ['columns' => $columns, 'legal' => $legal];
    }

    /** @return array<int, array<string, mixed>> */
    private static function tree(string $location, string $locale): array
    {
        try {
            return (new MenuService())->tree($location, $locale);
        } catch (\Throwable $e) {
            // เมนูอ่านไม่ได้ต้องไม่ทำให้ทั้งเว็บล่ม — ใช้เมนูใน config แทน
            \Core\Logger::exception($e);

            return [];
        }
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

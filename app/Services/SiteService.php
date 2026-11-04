<?php
/**
 * ข้อมูลของ layout เว็บสาธารณะ (header · เมนู · footer · ข้อมูลติดต่อ)
 *
 * ‼️ layouts/site.php รับข้อมูลเป็น view-model ก้อนเดียว ($site) จากที่นี่
 *    ไม่อ่าน config เอง — view ไม่ต้องรู้ว่าข้อมูลมาจากไหน
 *
 * ‼️ ลำดับแหล่งข้อมูล (Phase 3): ฐานข้อมูล (CMS) ก่อน → ตกมาใช้ config/site.php เฉพาะที่ยังไม่ได้ตั้ง
 *    · เมนู header/footer: ตาราง menus/menu_items ตามภาษา — เมนูว่าง = ใช้ของ config
 *    · ข้อมูลติดต่อ/บริษัท: settings.company — ช่องว่าง = ใช้ของ config
 *    รูปของ view-model เหมือนเดิมทุกคีย์ (Phase 1 วาดได้โดยไม่ต้องแก้) + เพิ่ม contact.emails (list)
 */

declare(strict_types=1);

namespace App\Services;

use Core\Logger;
use Throwable;

final class SiteService
{
    /** @var array<string, mixed>|null */
    private static ?array $defaults = null;

    /**
     * view-model ของ layout สาธารณะ — ทำเครื่องหมายเมนูที่ตรงกับหน้าปัจจุบันให้แล้ว
     *
     * @return array{brand: array<string, mixed>, contact: array<string, mixed>, cta: array<string, string>,
     *               nav: list<array<string, mixed>>, footer: array<string, mixed>}
     */
    public static function layout(string $currentPath, string $locale = ''): array
    {
        $locale = TranslationService::normalize($locale);
        $site = self::defaults();
        [$brand, $contact] = self::company($site, $locale);

        $nav    = self::menuNav($locale) ?? (array) ($site['nav'] ?? []);
        $footer = self::menuFooter($locale) ?? (array) ($site['footer'] ?? []);

        return [
            'brand'   => $brand,
            'contact' => $contact,
            'cta'     => array_map('strval', (array) ($site['cta'] ?? [])),
            'nav'     => array_values(array_map(
                static fn (array $entry): array => self::markActive($entry, $currentPath),
                $nav
            )),
            'footer'  => $footer,
        ];
    }

    /** URL หน้านโยบายความเป็นส่วนตัว — ฟอร์ม lead ลิงก์ไปที่นี่ */
    public static function privacyUrl(): string
    {
        return (string) (self::defaults()['privacy_url'] ?? '');
    }

    /**
     * แบรนด์ + ข้อมูลติดต่อ: settings.company ทับค่าใน config ทีละช่อง (ช่องว่างไม่ทับ)
     *
     * @param  array<string, mixed> $site
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private static function company(array $site, string $locale): array
    {
        $brand   = (array) ($site['brand'] ?? []);
        $contact = array_map('strval', (array) ($site['contact'] ?? []));
        $company = SettingService::group('company');

        $pick = static fn (mixed $value): string => is_string($value) ? trim($value) : '';

        if ($pick($company['name'] ?? null) !== '') {
            $brand['name'] = $pick($company['name']);
        }
        if ($pick($company['legal_name'] ?? null) !== '') {
            $brand['legal_name'] = $pick($company['legal_name']);
        }
        if ($pick($company['description'][$locale] ?? null) !== '') {
            $brand['about'] = $pick($company['description'][$locale]);
        }
        if ($pick($company['founded'] ?? null) !== '') {
            $brand['founded'] = $pick($company['founded']);
        }

        $phones = array_values(array_filter(array_map($pick, (array) ($company['phones'] ?? [])), 'strlen'));
        $emails = array_values(array_filter(array_map($pick, (array) ($company['emails'] ?? [])), 'strlen'));

        if ($phones !== []) {
            $contact['phone']      = $phones[0];
            $contact['phone_e164'] = self::e164($phones[0]);
        }

        $contact['phones'] = $phones !== [] ? $phones : array_values(array_filter([$contact['phone'] ?? '']));
        $contact['emails'] = $emails !== [] ? $emails : array_values(array_filter([$contact['email'] ?? '']));

        if ($emails !== []) {
            $contact['email'] = $emails[0];
        }

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

    /** @return list<array<string, mixed>>|null null = ยังไม่มีเมนูใน CMS */
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

    /** @return array{columns: list<array<string, mixed>>, legal: list<array<string, string>>}|null */
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
        } catch (Throwable $e) {
            // เมนูอ่านไม่ได้ต้องไม่ทำให้ทั้งเว็บล่ม — ใช้เมนูใน config แทน
            Logger::exception($e);

            return [];
        }
    }

    /** ค่าตั้งต้นใน config/site.php (อ่านอย่างเดียว) — หน้า Settings ใช้เติมช่องที่ยังว่าง @return array<string, mixed> */
    public static function configDefaults(): array
    {
        return self::defaults();
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

        $entry['current'] = $self === $current;
        $entry['active']  = $entry['current'];

        $children = [];

        foreach ((array) ($entry['children'] ?? []) as $child) {
            $child['current'] = self::normalize((string) ($child['url'] ?? '')) === $current;
            $entry['active']  = $entry['active'] || $child['current'];
            $children[]       = $child;
        }

        // หน้าลูกที่ไม่อยู่ในเมนู (เช่น /compensation-plans/binary/ ที่ถูกตัดออกจากเมนู) ยังให้หัวข้อแม่ติดสถานะ
        if (!$entry['active'] && $self !== '/' && $self !== '' && str_starts_with($current . '/', $self . '/')) {
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

<?php
/**
 * ข้อมูลของ layout เว็บสาธารณะ (header · เมนู · footer · ข้อมูลติดต่อ)
 *
 * ‼️ layouts/site.php รับข้อมูลเป็น view-model ก้อนเดียว ($site) จากที่นี่
 *    ไม่อ่าน config เอง — วันที่ย้ายเมนูไปอยู่ในตาราง menus / settings (Phase 2/3)
 *    แก้ที่ไฟล์นี้ไฟล์เดียว view ไม่ต้องรู้ว่าข้อมูลมาจากไหน
 *
 * ‼️ ค่าตั้งต้นอยู่ที่ config/site.php จุดเดียว
 */

declare(strict_types=1);

namespace App\Services;

final class SiteService
{
    /** @var array<string, mixed>|null */
    private static ?array $defaults = null;

    /**
     * view-model ของ layout สาธารณะ — ทำเครื่องหมายเมนูที่ตรงกับหน้าปัจจุบันให้แล้ว
     *
     * @return array{brand: array<string, mixed>, contact: array<string, string>, cta: array<string, string>,
     *               nav: list<array<string, mixed>>, footer: array<string, mixed>}
     */
    public static function layout(string $currentPath): array
    {
        $site = self::defaults();

        $site['nav'] = array_map(
            static fn (array $entry): array => self::markActive($entry, $currentPath),
            (array) ($site['nav'] ?? [])
        );

        return [
            'brand'   => (array) ($site['brand'] ?? []),
            'contact' => array_map('strval', (array) ($site['contact'] ?? [])),
            'cta'     => array_map('strval', (array) ($site['cta'] ?? [])),
            'nav'     => array_values($site['nav']),
            'footer'  => (array) ($site['footer'] ?? []),
        ];
    }

    /** URL หน้านโยบายความเป็นส่วนตัว — ฟอร์ม lead ลิงก์ไปที่นี่ */
    public static function privacyUrl(): string
    {
        return (string) (self::defaults()['privacy_url'] ?? '');
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

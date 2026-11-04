<?php
/**
 * ค่าตั้งของเว็บ (ชื่อแบรนด์ ข้อมูลองค์กร จำนวนบทความต่อหน้า ...)
 *
 * ‼️ อ่านทั้งตารางครั้งเดียวต่อ request แล้วเก็บไว้ — หน้าเดียวเรียกหลายสิบครั้ง ห้ามยิง query ทุกครั้ง
 * ‼️ ค่าที่ยังไม่ยืนยัน (เบอร์โทร โลโก้ ตัวเลข) เป็น null — ผู้ใช้ต้องตรวจ null แล้วซ่อน ห้ามเติมค่าสมมติ
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Core\Logger;
use Throwable;

final class SettingService
{
    public const DEFAULT_BRAND = 'OMC MLM Software';

    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /**
     * อ่านด้วย key แบบจุด: get('site.brand') · get('blog.per_page', 10)
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value    = self::all()[array_shift($segments)] ?? null;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value ?? $default;
    }

    public static function brand(): string
    {
        $brand = self::get('site.brand');

        return is_string($brand) && $brand !== '' ? $brand : self::DEFAULT_BRAND;
    }

    /** ล้าง cache — ใช้หลังบันทึกค่าใหม่ และในชุดทดสอบ */
    public static function flush(): void
    {
        self::$cache = null;
    }

    /** @param array<string, mixed>|null $value */
    public function save(string $key, ?array $value): void
    {
        $model    = new Setting();
        $existing = $model->findByKey($key);
        $json     = $value === null ? null : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($existing === null) {
            $model->create(['setting_key' => $key, 'value' => $json]);
        } else {
            $model->update((int) $existing['id'], ['value' => $json]);
        }

        self::flush();
    }

    // ------------------------------------------------------------- CMS (Phase 3)

    /** กลุ่มค่าตั้งที่หน้า Settings แก้ได้ — คีย์ในตาราง settings */
    public const GROUPS = ['company', 'stats', 'trust_logos', 'notifications', 'tracking', 'site'];

    /** @return array<string, mixed> ค่าทั้งกลุ่ม (ว่าง = ยังไม่ตั้ง) */
    public static function group(string $key): array
    {
        $value = self::all()[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * ตัวเลขของบริษัทที่ "ยืนยันแล้ว" เท่านั้น — บล็อก stats แสดงชุดนี้ชุดเดียว
     *
     * @return list<array{value:float|int, prefix:string, suffix:string, label:string, icon:string}>
     */
    public static function verifiedStats(): array
    {
        $out = [];

        foreach ((array) (self::group('stats')['items'] ?? []) as $item) {
            if (!is_array($item) || empty($item['verified']) || !is_numeric($item['value'] ?? null) || (string) ($item['label'] ?? '') === '') {
                continue;
            }

            $out[] = [
                'value'  => $item['value'] + 0,
                'prefix' => (string) ($item['prefix'] ?? ''),
                'suffix' => (string) ($item['suffix'] ?? ''),
                'label'  => (string) $item['label'],
                'icon'   => (string) ($item['icon'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * โลโก้ลูกค้าที่ "ยืนยันแล้ว" (ได้รับอนุญาต) เท่านั้น — บล็อก trust_bar แสดงชุดนี้ชุดเดียว
     *
     * @return list<array{src:string, alt:string, width:?int, height:?int, url?:string}>
     */
    public static function verifiedLogos(): array
    {
        $items = array_filter(
            (array) (self::group('trust_logos')['items'] ?? []),
            static fn ($item): bool => is_array($item) && !empty($item['verified']) && (int) ($item['media_id'] ?? 0) > 0
        );

        if ($items === []) {
            return [];
        }

        $media = (new \App\Models\Media())->findMany(array_map(static fn (array $i): int => (int) $i['media_id'], $items));
        $out   = [];

        foreach ($items as $item) {
            $row = $media[(int) $item['media_id']] ?? null;

            if ($row === null) {
                continue;
            }

            $logo = MediaService::imageData($row);

            if ((string) ($item['url'] ?? '') !== '') {
                $logo['url'] = (string) $item['url'];
            }

            $out[] = $logo;
        }

        return $out;
    }

    /** คีย์ของ settings ที่อ้าง media id นี้ (ค่า media_id ใดก็ได้ในโครง JSON) @return array<int, string> */
    public static function keysReferencingMedia(int $id): array
    {
        $found = [];
        $walk  = static function (mixed $value) use (&$walk, $id): bool {
            if (!is_array($value)) {
                return false;
            }

            foreach ($value as $key => $child) {
                if (is_string($key) && str_ends_with($key, 'media_id') && (int) $child === $id) {
                    return true;
                }
                if ($walk($child)) {
                    return true;
                }
            }

            return false;
        };

        foreach (self::all() as $key => $value) {
            if ($walk($value)) {
                $found[] = (string) $key;
            }
        }

        return $found;
    }

    /**
     * ตรวจและทำความสะอาดค่าจากฟอร์ม Settings
     *
     * @param  array<string, mixed> $input
     * @return array{values: array<string, array<string, mixed>>, errors: array<string, string>}
     */
    public function fromInput(array $input): array
    {
        $errors = [];
        $text   = static fn (mixed $v): string => trim(str_replace("\r\n", "\n", is_string($v) ? $v : ''));
        $lines  = static fn (mixed $v): array => array_values(array_filter(array_map('trim', explode("\n", str_replace("\r\n", "\n", is_string($v) ? $v : ''))), 'strlen'));

        $company = (array) ($input['company'] ?? []);
        $emails  = $lines($company['emails'] ?? '');

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['company[emails]'] = 'cms.settings.error_email';
            }
        }

        $founded = $text($company['founded'] ?? '');

        if ($founded !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $founded) !== 1) {
            $errors['company[founded]'] = 'cms.settings.error_date';
        }

        $localized = static function (mixed $v) use ($text): array {
            $out = [];

            foreach (TranslationService::locales() as $locale) {
                $out[$locale] = $text(is_array($v) ? ($v[$locale] ?? '') : '');
            }

            return $out;
        };

        $values = [];
        $values['company'] = [
            'name'        => $text($company['name'] ?? ''),
            'legal_name'  => $text($company['legal_name'] ?? ''),
            'founded'     => $founded,
            'phones'      => $lines($company['phones'] ?? ''),
            'emails'      => $emails,
            'line_id'     => $text($company['line_id'] ?? ''),
            'line_url'    => $text($company['line_url'] ?? ''),
            'facebook'    => $text($company['facebook'] ?? ''),
            'address'     => $localized($company['address'] ?? []),
            'description' => $localized($company['description'] ?? []),
        ];

        foreach (['line_url', 'facebook'] as $field) {
            if ($values['company'][$field] !== '' && preg_match('#^https://#i', $values['company'][$field]) !== 1) {
                $errors['company[' . $field . ']'] = 'cms.settings.error_url';
            }
        }

        $stats = [];

        foreach ((array) ($input['stats'] ?? []) as $row) {
            if (!is_array($row) || $text($row['label'] ?? '') === '') {
                continue;
            }
            if (!is_numeric($text($row['value'] ?? ''))) {
                $errors['stats'] = 'cms.settings.error_stat_value';
                continue;
            }

            $stats[] = [
                'value'    => $text($row['value']) + 0,
                'prefix'   => $text($row['prefix'] ?? ''),
                'suffix'   => $text($row['suffix'] ?? ''),
                'label'    => $text($row['label']),
                'icon'     => $text($row['icon'] ?? ''),
                'verified' => !empty($row['verified']),
            ];
        }

        $values['stats'] = ['items' => $stats];

        $logos = [];

        foreach ((array) ($input['trust_logos'] ?? []) as $row) {
            if (!is_array($row) || (int) ($row['media_id'] ?? 0) <= 0) {
                continue;
            }

            $logos[] = ['media_id' => (int) $row['media_id'], 'url' => $text($row['url'] ?? ''), 'verified' => !empty($row['verified'])];
        }

        $values['trust_logos'] = ['items' => $logos];

        $leadEmail = $text($input['notifications']['lead_email'] ?? '');

        if ($leadEmail !== '' && filter_var($leadEmail, FILTER_VALIDATE_EMAIL) === false) {
            $errors['notifications[lead_email]'] = 'cms.settings.error_email';
        }

        $values['notifications'] = ['lead_email' => $leadEmail];

        $ga4 = strtoupper($text($input['tracking']['ga4_id'] ?? ''));
        $gtm = strtoupper($text($input['tracking']['gtm_id'] ?? ''));

        if ($ga4 !== '' && preg_match('/^G-[A-Z0-9]{4,20}$/', $ga4) !== 1) {
            $errors['tracking[ga4_id]'] = 'cms.settings.error_ga4';
        }
        if ($gtm !== '' && preg_match('/^GTM-[A-Z0-9]{4,20}$/', $gtm) !== 1) {
            $errors['tracking[gtm_id]'] = 'cms.settings.error_gtm';
        }

        $values['tracking'] = ['ga4_id' => $ga4, 'gtm_id' => $gtm];

        // site: คงค่าเดิมที่หน้านี้ไม่ได้แก้ (brand/locale) — เปลี่ยนแค่รูป OG เริ่มต้น
        $site    = self::group('site');
        $ogId    = (int) ($input['site']['default_og_image_media_id'] ?? 0);
        $ogMedia = $ogId > 0 ? (new \App\Models\Media())->find($ogId) : null;

        $site['default_og_image_media_id'] = $ogMedia !== null ? $ogId : null;
        $site['default_og_image']          = $ogMedia !== null ? (string) $ogMedia['path'] : null;

        if ($text($input['site']['brand'] ?? '') !== '') {
            $site['brand'] = $text($input['site']['brand']);
        }

        $values['site'] = $site;

        return ['values' => $values, 'errors' => $errors];
    }

    /**
     * บันทึกทุกกลุ่ม + ปรับ organization (schema.org) ให้ตรงกับข้อมูลบริษัท
     *
     * @param array<string, array<string, mixed>> $values
     */
    public function saveAll(array $values): void
    {
        \Core\Database::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                if (in_array($key, self::GROUPS, true) && $value !== self::group($key)) {
                    $this->save($key, $value);
                }
            }

            $company      = $values['company'] ?? [];
            $organization = self::group('organization');
            $next         = array_merge($organization, [
                'legal_name' => ($company['legal_name'] ?? '') !== '' ? $company['legal_name'] : ($organization['legal_name'] ?? null),
                'telephone'  => $company['phones'][0] ?? ($organization['telephone'] ?? null),
                'email'      => $company['emails'][0] ?? ($organization['email'] ?? null),
                'same_as'    => array_values(array_filter([$company['facebook'] ?? '', $company['line_url'] ?? ''], 'strlen')),
            ]);

            if ($next !== $organization) {
                $this->save('organization', $next);
            }
        });
    }

    /** @return array<string, mixed> */
    private static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];

        try {
            foreach ((new Setting())->allLive() as $row) {
                $decoded = json_decode((string) $row['value'], true);
                self::$cache[(string) $row['setting_key']] = $decoded;
            }
        } catch (Throwable $e) {
            // ค่าตั้งอ่านไม่ได้ต้องไม่ทำให้ทั้งเว็บล่ม — ใช้ค่าตั้งต้นแล้วบันทึกไว้
            Logger::exception($e);
        }

        return self::$cache;
    }
}

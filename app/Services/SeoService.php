<?php
/**
 * ข้อมูล <head> ของทุกหน้าสาธารณะ — title, description, canonical, robots, OG, Twitter
 *
 * คืนค่าเป็น "ข้อมูล" ไม่ใช่ HTML — partials/seo-head.php เป็นคนวาด (ผ่าน e() ทุกค่า)
 *
 * ‼️ กฎ (PLAN.md "SEO ที่ทุกหน้า public ต้องมี")
 *    · title = seo_title ถ้ามี ไม่มีก็ title + " | <แบรนด์>"
 *    · canonical = host ของ production (config seo.canonical_url) + path ลงท้าย / — ไม่ใช่ APP_URL
 *    · robots มาจากหน้า (index/noindex) — ห้ามใส่ noindex ตาม environment ที่นี่
 *      non-production ได้ X-Robots-Tag จาก Core\Staging อยู่แล้ว ใส่ซ้ำที่นี่คือเสี่ยงหลุดขึ้น production
 *    · noindex ที่ระบบใส่เองมีแค่: หน้า 404/410 และการดูตัวอย่าง draft (ต้องล็อกอิน)
 */

declare(strict_types=1);

namespace App\Services;

use Core\Path;

final class SeoService
{
    public const ROBOTS_INDEX   = 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
    public const ROBOTS_NOINDEX = 'noindex, follow';
    public const ROBOTS_PRIVATE = 'noindex, nofollow';

    /** host ของ production ไม่มี / ท้าย */
    public static function base(): string
    {
        $base = (string) config('seo.canonical_url', '');

        return $base !== '' ? rtrim($base, '/') : rtrim((string) config('url', ''), '/');
    }

    /** path ภายใน (decode แล้ว) → URL เต็มบน host ของ production แบบ percent-encode */
    public static function absolute(string $path): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return self::base() . Path::encode('/' . ltrim($path, '/'));
    }

    /** ลงท้าย / เสมอ ยกเว้น path ที่เป็นไฟล์ (มีจุดในช่วงสุดท้าย) */
    public static function withSlash(string $path): string
    {
        $last = (string) substr((string) strrchr('/' . $path, '/'), 1);

        return (str_ends_with($path, '/') || str_contains($last, '.')) ? $path : $path . '/';
    }

    public static function title(?string $seoTitle, string $title): string
    {
        $seoTitle = trim((string) $seoTitle);

        if ($seoTitle !== '') {
            return $seoTitle;
        }

        $brand = SettingService::brand();
        $title = trim($title);

        return ($title === '' || $title === $brand) ? $brand : $title . ' | ' . $brand;
    }

    /**
     * @param array{
     *   title:string, seo_title?:?string, description?:?string, path?:?string,
     *   canonical_override?:?string, robots?:string, private?:bool, type?:string,
     *   image?:?array, published_time?:?string, modified_time?:?string
     * } $input
     *   path = null → ไม่มี canonical (หน้า 404)
     *   private = true → noindex, nofollow (ดูตัวอย่าง draft)
     *
     * @return array<string, mixed>
     */
    public function meta(array $input): array
    {
        $title       = self::title($input['seo_title'] ?? null, $input['title']);
        $description = self::description($input['description'] ?? null);
        $path        = $input['path'] ?? null;
        $override    = trim((string) ($input['canonical_override'] ?? ''));
        $canonical   = null;

        if ($override !== '') {
            $canonical = self::absolute(self::withSlash($override));
        } elseif ($path !== null) {
            $canonical = self::absolute(self::withSlash($path));
        }

        $robots = match (true) {
            (bool) ($input['private'] ?? false)       => self::ROBOTS_PRIVATE,
            ($input['robots'] ?? 'index') === 'index' => self::ROBOTS_INDEX,
            default                                   => self::ROBOTS_NOINDEX,
        };

        $image = $input['image'] ?? null;

        if ($image === null) {
            $default = SettingService::get('site.default_og_image');
            $image   = is_string($default) && $default !== '' ? ['path' => $default, 'alt' => '', 'width' => null, 'height' => null] : null;
        }

        $type = (string) ($input['type'] ?? 'website');
        $og   = [
            'og:locale'    => (string) SettingService::get('site.locale', 'th_TH'),
            'og:site_name' => SettingService::brand(),
            'og:type'      => $type,
            'og:title'     => $title,
        ];

        if ($description !== '') {
            $og['og:description'] = $description;
        }
        if ($canonical !== null) {
            $og['og:url'] = $canonical;
        }
        if ($image !== null) {
            $og['og:image'] = self::absolute((string) $image['path']);

            if (($image['width'] ?? null) !== null && ($image['height'] ?? null) !== null) {
                $og['og:image:width']  = (string) $image['width'];
                $og['og:image:height'] = (string) $image['height'];
            }
            if ((string) ($image['alt'] ?? '') !== '') {
                $og['og:image:alt'] = (string) $image['alt'];
            }
        }
        if ($type === 'article') {
            if (($input['published_time'] ?? null) !== null) {
                $og['article:published_time'] = self::isoDate((string) $input['published_time']);
            }
            if (($input['modified_time'] ?? null) !== null) {
                $og['article:modified_time'] = self::isoDate((string) $input['modified_time']);
            }
        }

        $twitter = [
            'twitter:card'  => $image !== null ? 'summary_large_image' : 'summary',
            'twitter:title' => $title,
        ];

        if ($description !== '') {
            $twitter['twitter:description'] = $description;
        }
        if ($image !== null) {
            $twitter['twitter:image'] = $og['og:image'];
        }

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => $canonical,
            'robots'      => $robots,
            'og'          => $og,
            'twitter'     => $twitter,
            'feed'        => ['title' => SettingService::brand(), 'href' => self::absolute('/feed/')],
        ];
    }

    /** คำอธิบายเป็นข้อความล้วนบรรทัดเดียว — ตัดที่ 160 ตัวอักษรเพราะ Google ตัดตรงนั้นอยู่แล้ว */
    public static function description(?string $text, int $limit = 160): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
    }

    public static function isoDate(string $value): string
    {
        $time = strtotime($value);

        return $time === false ? $value : date('c', $time);
    }
}

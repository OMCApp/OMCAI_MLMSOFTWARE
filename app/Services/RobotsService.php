<?php
/**
 * robots.txt
 *
 * ‼️ production เท่านั้นที่เปิดให้ crawl — ทุก environment อื่น Disallow ทั้งเว็บ
 *    (คู่กับ X-Robots-Tag ของ Core\Staging — robots.txt กันการ crawl, header กันการ index)
 */

declare(strict_types=1);

namespace App\Services;

final class RobotsService
{
    public function content(string $env, string $appUrl): string
    {
        if ($env !== 'production') {
            return "User-agent: *\nDisallow: /\n";
        }

        $base = rtrim($appUrl, '/');
        $base = $base !== '' ? $base : SeoService::base();

        return "User-agent: *\n"
             . "Allow: /\n"
             . "Disallow: /admin/\n"
             . "\n"
             . 'Sitemap: ' . $base . "/sitemap.xml\n";
    }
}

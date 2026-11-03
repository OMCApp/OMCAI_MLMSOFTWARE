<?php
/**
 * ไฟล์สำหรับ crawler — sitemap, robots.txt, RSS feed
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FeedService;
use App\Services\RobotsService;
use App\Services\SitemapService;
use Core\Path;

final class SeoController extends PublicController
{
    public function sitemapIndex(): void
    {
        $this->plain((new SitemapService())->index(), 'application/xml; charset=utf-8');
    }

    public function sitemap(string $section): void
    {
        if (!in_array($section, SitemapService::SECTIONS, true)) {
            (new SiteController())->resolve();

            return;
        }

        $this->plain((new SitemapService())->urlset($section), 'application/xml; charset=utf-8');
    }

    public function robots(): void
    {
        $this->plain(
            (new RobotsService())->content((string) config('env'), (string) config('url')),
            'text/plain; charset=utf-8'
        );
    }

    public function feed(): void
    {
        // URL ของ feed เดิมคือ /feed/ — /feed ไม่มี / ท้ายตามกฎเดียวกับทุกหน้า
        if (!str_ends_with(Path::requested(), '/')) {
            $this->redirectTo('/feed/' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''), 301);
        }

        $this->plain((new FeedService())->rss(), 'application/rss+xml; charset=utf-8');
    }
}

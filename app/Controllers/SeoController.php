<?php
/**
 * ไฟล์สำหรับ crawler — sitemap, robots.txt, RSS feed
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FeedService;
use App\Services\LocaleService;
use App\Services\PostService;
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
        // sitemap-pages.xml (ภาษาหลัก) · sitemap-pages-en.xml — ส่วนที่ไม่มีแถวในภาษานั้นได้ 404
        if (!in_array($section, (new SitemapService())->available(), true)) {
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

    /** /feed/ (ไทย) · /en/feed/ · /zh/feed/ — แต่ละภาษามี feed ของตัวเอง */
    public function feed(string $locale = LocaleService::DEFAULT): void
    {
        $locale = LocaleService::exists($locale) ? $locale : LocaleService::DEFAULT;
        $path   = LocaleService::localize('/feed/', $locale);

        // ‼️ ภาษาที่ยังไม่มีบทความเผยแพร่ = ไม่มี feed (404 noindex) — ห้ามมี URL ว่างให้ index
        if ($locale !== LocaleService::DEFAULT && !(new PostService())->hasPublished($locale)) {
            (new SiteController())->resolve();

            return;
        }

        // URL ของ feed เดิมคือ /feed/ — /feed ไม่มี / ท้ายตามกฎเดียวกับทุกหน้า
        if (!str_ends_with(Path::requested(), '/')) {
            $this->redirectTo($path . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''), 301);
        }

        $this->plain((new FeedService())->rss($locale), 'application/rss+xml; charset=utf-8');
    }
}

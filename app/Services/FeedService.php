<?php
/**
 * RSS 2.0 ที่ /feed/ — เว็บเดิมมี feed นี้และมีผู้อ่าน/บริการภายนอกดึงอยู่ (PLAN.md)
 */

declare(strict_types=1);

namespace App\Services;

final class FeedService
{
    public function __construct(
        private readonly PostService $posts = new PostService(),
        private readonly CategoryService $categories = new CategoryService(),
    ) {
    }

    public function rss(string $locale = LocaleService::DEFAULT): string
    {
        $limit = max(1, min((int) SettingService::get('blog.feed_items', 20), 50));
        $items = $this->posts->latest($limit, $locale);
        $brand = SettingService::brand();
        $x     = static fn (string $v): string => SitemapService::xml($v);

        $built = $items === [] ? time() : (int) strtotime((string) max(array_column($items, 'updated_at')));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n"
             . "<channel>\n"
             . '  <title>' . $x($brand) . "</title>\n"
             . '  <link>' . $x(SeoService::absolute(LocaleService::home($locale))) . "</link>\n"
             . '  <description>' . $x((string) (SettingService::get('site.tagline') ?: $brand)) . "</description>\n"
             . '  <language>' . $x(LocaleService::htmlLang($locale)) . "</language>\n"
             . '  <lastBuildDate>' . $x(date(DATE_RSS, $built)) . "</lastBuildDate>\n"
             . '  <atom:link href="' . $x(SeoService::absolute(LocaleService::localize('/feed/', $locale))) . '" rel="self" type="application/rss+xml" />' . "\n";

        $categoryNames = $this->categories->names();

        foreach ($items as $post) {
            $link = SeoService::absolute(PostService::permalink($post));
            $id   = $post['category_id'] !== null ? (int) $post['category_id'] : null;
            $id   = $id !== null && isset($categoryNames[$id]) ? $id : null;

            $summary = SeoService::description((string) ($post['excerpt'] ?: $post['content']), 300);

            $xml .= "  <item>\n"
                  . '    <title>' . $x((string) $post['title']) . "</title>\n"
                  . '    <link>' . $x($link) . "</link>\n"
                  . '    <guid isPermaLink="true">' . $x($link) . "</guid>\n"
                  . '    <pubDate>' . $x(date(DATE_RSS, (int) strtotime((string) $post['published_at']))) . "</pubDate>\n"
                  . ($id !== null ? '    <category>' . $x((string) $categoryNames[$id]) . "</category>\n" : '')
                  . '    <description>' . $x($summary) . "</description>\n"
                  . "  </item>\n";
        }

        return $xml . "</channel>\n</rss>\n";
    }
}

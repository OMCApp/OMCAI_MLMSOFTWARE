<?php
/**
 * sitemap — index + pages / posts / categories
 *
 * ‼️ ใส่เฉพาะ URL ที่เผยแพร่แล้ว + index ได้ + ไม่มี canonical ชี้ไปที่อื่น
 *    ไม่รวม draft / noindex / URL ที่ถูก redirect (PLAN.md)
 * ‼️ <loc> ต้อง percent-encode แล้ว escape XML — path ภาษาไทยดิบใน sitemap ทำให้ Google อ่านไม่ผ่าน
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Redirect;

final class SitemapService
{
    public const SECTIONS = ['pages', 'posts', 'categories'];

    public function __construct(
        private readonly PageService $pages = new PageService(),
        private readonly PostService $posts = new PostService(),
        private readonly CategoryService $categories = new CategoryService(),
    ) {
    }

    /**
     * @return array<int, array{path:string, lastmod:string}>
     */
    public function entries(string $section): array
    {
        return match ($section) {
            'pages'      => $this->pageEntries(),
            'posts'      => array_map(
                static fn (array $row): array => ['path' => PostService::permalink($row), 'lastmod' => (string) $row['updated_at']],
                $this->posts->sitemapEntries()
            ),
            'categories' => $this->categories->sitemapEntries($this->posts->statsByCategory()),
            default      => [],
        };
    }

    public function index(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach (self::SECTIONS as $section) {
            $entries = $this->entries($section);

            // ประกาศครบทั้งสามตัวเสมอ (URL คงที่ให้ Search Console) — ตัวที่ยังว่างไม่มี lastmod
            $xml .= "  <sitemap>\n"
                  . '    <loc>' . self::xml(SeoService::absolute('/sitemap-' . $section . '.xml')) . "</loc>\n"
                  . ($entries !== []
                      ? '    <lastmod>' . self::xml(SeoService::isoDate(max(array_column($entries, 'lastmod')))) . "</lastmod>\n"
                      : '')
                  . "  </sitemap>\n";
        }

        return $xml . "</sitemapindex>\n";
    }

    public function urlset(string $section): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->entries($section) as $entry) {
            $xml .= "  <url>\n"
                  . '    <loc>' . self::xml(SeoService::absolute($entry['path'])) . "</loc>\n"
                  . '    <lastmod>' . self::xml(SeoService::isoDate($entry['lastmod'])) . "</lastmod>\n"
                  . "  </url>\n";
        }

        return $xml . "</urlset>\n";
    }

    public static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * หน้า + /blog/ (หน้ารวมบทความเป็นของระบบ ไม่ได้มาจากแถวใน pages ที่เผยแพร่เสมอไป)
     *
     * @return array<int, array{path:string, lastmod:string}>
     */
    private function pageEntries(): array
    {
        $redirected = $this->redirectedPaths();
        $out        = [];
        $hasBlog    = false;

        foreach ($this->pages->sitemapEntries() as $row) {
            $path = (string) $row['path'];

            if (isset($redirected[$path])) {
                continue;
            }

            $hasBlog = $hasBlog || $path === '/blog/';
            $out[]   = ['path' => $path, 'lastmod' => (string) $row['updated_at']];
        }

        if (!$hasBlog) {
            $latest = $this->posts->latest(1);

            if ($latest !== []) {
                $out[] = ['path' => '/blog/', 'lastmod' => (string) $latest[0]['updated_at']];
            }
        }

        return $out;
    }

    /** @return array<string, true> */
    private function redirectedPaths(): array
    {
        return array_fill_keys((new Redirect())->exactSources(), true);
    }
}

<?php
/**
 * sitemap — index + pages / posts / categories แยกตามภาษา
 *   ภาษาหลัก: sitemap-pages.xml · sitemap-posts.xml · sitemap-categories.xml (URL เดิม ประกาศเสมอ)
 *   ภาษาอื่น: sitemap-pages-en.xml ฯลฯ — มีเฉพาะส่วนที่มีแถวจริง (ไม่มี sitemap ว่างของภาษาที่ยังไม่มีเนื้อหา)
 *   ทุก <url> ของหน้าที่มีหลายภาษามี xhtml:link hreflang ครบชุด + x-default
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

    /** ชื่อ section ที่เปิดได้ตอนนี้ — ภาษาหลักครบสามตัวเสมอ · ภาษาอื่นเฉพาะตัวที่มีแถว */
    public function available(): array
    {
        $out = [];

        foreach (LocaleService::codes() as $locale) {
            foreach (self::SECTIONS as $kind) {
                $name = self::sectionName($kind, $locale);

                if ($locale === LocaleService::DEFAULT || $this->entries($name) !== []) {
                    $out[] = $name;
                }
            }
        }

        return $out;
    }

    public static function sectionName(string $kind, string $locale): string
    {
        return $locale === LocaleService::DEFAULT ? $kind : $kind . '-' . $locale;
    }

    /**
     * @return array<int, array{path:string, lastmod:string, alternates?:array}>
     */
    public function entries(string $section): array
    {
        [$kind, $locale] = array_pad(explode('-', $section, 2), 2, LocaleService::DEFAULT);

        if (!in_array($kind, self::SECTIONS, true) || !LocaleService::exists($locale)) {
            return [];
        }

        $rows = match ($kind) {
            'pages'      => $this->pageEntries($locale),
            'posts'      => array_map(
                static fn (array $row): array => ['path' => PostService::permalink($row), 'lastmod' => (string) $row['updated_at']],
                $this->posts->sitemapEntries()
            ),
            default      => $this->categories->sitemapEntries($this->posts->statsByCategory()),
        };

        return array_values(array_filter($rows, static fn (array $row): bool => LocaleService::localeOf((string) $row['path']) === $locale));
    }

    public function index(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->available() as $section) {
            $entries = $this->entries($section);

            // ภาษาหลักประกาศครบทั้งสามตัวเสมอ (URL คงที่ให้ Search Console) — ตัวที่ยังว่างไม่มี lastmod
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
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($this->entries($section) as $entry) {
            $xml .= "  <url>\n"
                  . '    <loc>' . self::xml(SeoService::absolute($entry['path'])) . "</loc>\n";

            // hreflang ของหน้าที่มีหลายภาษา — ชุดเดียวกับ <link rel="alternate"> ใน <head> (PageService::hreflangSet)
            foreach ((array) ($entry['alternates'] ?? []) as $alternate) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . self::xml((string) $alternate['hreflang'])
                      . '" href="' . self::xml(SeoService::absolute((string) $alternate['path'])) . "\" />\n";
            }

            $xml .= '    <lastmod>' . self::xml(SeoService::isoDate($entry['lastmod'])) . "</lastmod>\n"
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
    private function pageEntries(string $locale): array
    {
        $redirected = $this->redirectedPaths();
        $out        = [];
        $hasBlog    = false;

        $groups     = $this->pages->translationGroups();

        foreach ($this->pages->sitemapEntries() as $row) {
            $path = (string) $row['path'];

            if (isset($redirected[$path])) {
                continue;
            }

            $hasBlog = $hasBlog || $path === LocaleService::localize('/blog/', $locale);
            $out[]   = [
                'path'       => $path,
                'lastmod'    => (string) $row['updated_at'],
                'alternates' => PageService::hreflangSet($groups[(string) ($row['translation_group'] ?? '')] ?? []),
            ];
        }

        if (!$hasBlog) {
            $latest = $this->posts->latest(1, $locale);

            if ($latest !== []) {
                $out[] = ['path' => LocaleService::localize('/blog/', $locale), 'lastmod' => (string) $latest[0]['updated_at']];
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

<?php
/**
 * แปลงผลของ SiteResolver เป็นสิ่งที่หน้าสาธารณะต้องใช้ — ข้อมูลของ view, SEO, JSON-LD, breadcrumb
 *
 * Controller แค่ส่งผลลัพธ์นี้ให้ layout — การตัดสินใจทั้งหมด (title, canonical, robots, schema) อยู่ที่นี่
 */

declare(strict_types=1);

namespace App\Services;

use Core\Path;

final class SiteViewService
{
    public function __construct(
        private readonly PageService $pages = new PageService(),
        private readonly MediaService $media = new MediaService(),
        private readonly SeoService $seo = new SeoService(),
        private readonly SchemaService $schema = new SchemaService(),
        private readonly HtmlSanitizer $sanitizer = new HtmlSanitizer(),
        private readonly ContentImages $contentImages = new ContentImages(),
    ) {
    }

    /**
     * @param  array<string, mixed> $resolution ผลจาก SiteResolver::resolve()
     * @return array{status:int, view:string, title:string, data:array<string,mixed>, seo:array<string,mixed>,
     *               graph:array<string,mixed>, breadcrumbs:array<int,array<string,mixed>>, private:bool}
     */
    public function build(array $resolution): array
    {
        return match ($resolution['kind']) {
            'page'                => $this->page($resolution['page'], (bool) $resolution['preview']),
            'post'                => $this->post($resolution['post'], $resolution['category'], (bool) $resolution['preview']),
            'blog', 'category'    => $this->listing($resolution),
            'gone'                => $this->missing(410),
            default               => $this->missing(404),
        };
    }

    /** @param array<string, mixed> $page */
    private function page(array $page, bool $preview): array
    {
        $locale      = (string) ($page['locale'] ?? LocaleService::DEFAULT);
        $blocks      = $this->pages->blocks($page);
        $breadcrumbs = $this->pages->breadcrumbs($page, __('site.home'), LocaleService::home($locale));
        $description = SeoService::description((string) ($page['meta_description'] ?? ''));

        // hreflang เฉพาะหน้าที่ index ได้และ canonical เป็นตัวเอง — ชุดเดียวกับ sitemap
        $indexable  = !$preview && $page['robots'] === 'index' && (string) ($page['canonical_override'] ?? '') === '';
        $alternates = $indexable ? PageService::hreflangSet($this->pages->translationPaths($page)) : [];

        $seo = $this->seo->meta([
            'title'              => (string) $page['title'],
            'seo_title'          => $page['seo_title'],
            'description'        => $description,
            'path'               => (string) $page['path'],
            'canonical_override' => $page['canonical_override'],
            'robots'             => (string) $page['robots'],
            'private'            => $preview,
            'image'              => $this->media->image($page['og_image_id'] !== null ? (int) $page['og_image_id'] : null),
            'locale'             => $locale,
            'alternates'         => $alternates,
        ]);

        return $this->result(200, 'site/page', (string) $page['title'], [
            'siteTemplate' => (string) $page['template'],
            'siteBlocks'   => $this->withPartials($blocks),
            'sitePreview'  => $preview,
        ], $seo, $breadcrumbs, $preview, ['page' => $page, 'blocks' => $blocks], $locale, $this->pages->switcherPaths($page));
    }

    /**
     * @param array<string, mixed>      $post
     * @param array<string, mixed>|null $category
     */
    private function post(array $post, ?array $category, bool $preview): array
    {
        $image       = $this->media->image($post['featured_image_id'] !== null ? (int) $post['featured_image_id'] : null);
        $ogImage     = $this->media->image($post['og_image_id'] !== null ? (int) $post['og_image_id'] : null) ?? $image;
        $description = SeoService::description((string) ($post['meta_description'] ?: ($post['excerpt'] ?: $post['content'])));
        $path        = PostService::permalink($post);

        $locale      = (string) ($post['locale'] ?? LocaleService::DEFAULT);
        $breadcrumbs = [
            ['label' => __('site.home'), 'url' => LocaleService::home($locale)],
            ['label' => $this->blogLabel($locale), 'url' => LocaleService::localize('/blog/', $locale)],
        ];

        if ($category !== null) {
            $breadcrumbs[] = ['label' => (string) $category['name'], 'url' => CategoryService::path($category)];
        }

        $breadcrumbs[] = ['label' => (string) $post['title'], 'url' => null];

        $seo = $this->seo->meta([
            'title'              => (string) $post['title'],
            'seo_title'          => $post['seo_title'],
            'description'        => $description,
            'path'               => $path,
            'canonical_override' => $post['canonical_override'],
            'robots'             => (string) $post['robots'],
            'private'            => $preview,
            'type'               => 'article',
            'image'              => $ogImage,
            'published_time'     => $post['published_at'],
            'modified_time'      => $post['updated_at'],
            'locale'             => $locale,
        ]);

        return $this->result(200, 'site/post', (string) $post['title'], [
            'sitePost' => [
                'date'          => format_date($post['published_at'] !== null ? (string) $post['published_at'] : null),
                'datetime'      => $post['published_at'] !== null ? SeoService::isoDate((string) $post['published_at']) : '',
                'author'        => (string) ($post['author_name'] ?? ''),
                'category'      => $category !== null ? (string) $category['name'] : '',
                'category_url'  => $category !== null ? Path::encode(CategoryService::path($category)) : '',
                'image'         => $image,
                'content'       => $this->withBase($this->contentImages->apply($this->sanitizer->clean((string) ($post['content'] ?? '')), $image === null)),
                'related'       => $this->related($post),
                'blog_url'      => LocaleService::localize('/blog/', $locale),
            ],
            'sitePreview' => $preview,
        ], $seo, $breadcrumbs, $preview, ['post' => $post, 'category' => $category, 'image' => $ogImage], $locale, []);
    }

    /** @param array<string, mixed> $r */
    private function listing(array $r): array
    {
        $category = $r['category'];
        $page     = (int) $r['page'];
        $locale   = (string) ($r['locale'] ?? LocaleService::DEFAULT);
        $blogPage = $this->pages->liveByPath(LocaleService::localize('/blog/', $locale));

        if ($category !== null) {
            $name        = (string) $category['name'];
            $seoTitle    = $category['seo_title'];
            $description = (string) ($category['meta_description'] ?: $category['description']);
            $robots      = (string) $category['robots'];
        } else {
            $name        = $this->blogLabel($locale);
            $seoTitle    = $blogPage['seo_title'] ?? null;
            $description = (string) ($blogPage['meta_description'] ?? '');
            $robots      = (string) ($blogPage['robots'] ?? 'index');
        }

        $path = (string) $r['base'] . ($page > 1 ? 'page/' . $page . '/' : '');

        // หน้า 2 ขึ้นไปต้องมี title ไม่ซ้ำกับหน้าแรก (Search Console เตือน title ซ้ำ)
        $pageSuffix = $page > 1 ? ' – ' . __('site.page_n', ['page' => (string) $page]) : '';

        $breadcrumbs = [['label' => __('site.home'), 'url' => LocaleService::home($locale)]];

        if ($category !== null) {
            $breadcrumbs[] = ['label' => $this->blogLabel($locale), 'url' => LocaleService::localize('/blog/', $locale)];
        }

        $breadcrumbs[] = ['label' => $name, 'url' => null];

        $seo = $this->seo->meta([
            'title'       => $name . $pageSuffix,
            'seo_title'   => ($seoTitle !== null && $seoTitle !== '') ? $seoTitle . $pageSuffix : null,
            'description' => SeoService::description($description),
            'path'        => $path,
            'robots'      => $robots,
            'locale'      => $locale,
        ]);

        $items = $this->cards($r['posts'], 200);

        return $this->result(200, 'site/listing', $name, [
            'siteItems'      => $items,
            'sitePage'       => $page,
            'sitePages'      => (int) $r['pages'],
            'siteBase'       => (string) $r['base'],
            'siteListIntro'  => $page === 1 && $category !== null ? (string) ($category['description'] ?? '') : '',
            'sitePreview'    => false,
        ], $seo, $breadcrumbs, false, [], $locale, $category === null && $blogPage !== null ? $this->pages->switcherPaths($blogPage) : []);
    }

    private function missing(int $status): array
    {
        $title = $status === 410 ? __('site.gone_title') : __('error.404_title');

        $seo = $this->seo->meta([
            'title'  => $title,
            'path'   => null,
            'robots' => 'noindex',
            'locale' => LocaleService::current(),
        ]);

        return $this->result($status, 'site/not-found', $title, [
            'siteStatus'  => $status,
            'sitePreview' => false,
        ], $seo, [], false, [], LocaleService::current(), []);
    }

    /**
     * บทความหมวดเดียวกันท้ายบทความ — การ์ด (url, title, date)
     *
     * @param  array<string, mixed> $post
     * @return list<array{url:string, title:string, date:string, lead:string}>
     */
    private function related(array $post): array
    {
        return $this->cards((new PostService())->related($post, 3), 140);
    }

    /**
     * การ์ดบทความ (หน้ารวม · บทความที่เกี่ยวข้อง) — ภาพเด่นดึงทีเดียวทั้งชุด (ไม่ query ทีละบทความ)
     *
     * @param  array<int, array<string, mixed>> $posts
     * @return list<array<string, mixed>>
     */
    private function cards(array $posts, int $leadLength): array
    {
        $images = $this->media->images(array_map(
            static fn (array $post): ?int => $post['featured_image_id'] !== null ? (int) $post['featured_image_id'] : null,
            $posts
        ));
        $items  = [];

        foreach ($posts as $post) {
            $image   = $post['featured_image_id'] !== null ? ($images[(int) $post['featured_image_id']] ?? null) : null;
            $items[] = [
                'url'   => Path::encode(PostService::permalink($post)),
                'date'  => format_date((string) $post['published_at']),
                'title' => (string) $post['title'],
                'lead'  => SeoService::description((string) ($post['excerpt'] ?: $post['content']), $leadLength),
                'cover' => $image === null ? null : [
                    'src'    => $image['src'],
                    'srcset' => $image['srcset'],
                    'alt'    => $image['alt'],
                    'width'  => $image['width'],
                    'height' => $image['height'],
                ],
            ];
        }

        return $items;
    }

    private function blogLabel(string $locale): string
    {
        $blogPage = $this->pages->liveByPath(LocaleService::localize('/blog/', $locale));

        return $blogPage !== null ? (string) $blogPage['title'] : __('site.blog');
    }

    /**
     * ไฟล์ partial ของแต่ละบล็อก (Phase 1 สร้างใน partials/blocks/) — ชนิดที่ยังไม่มี partial ถูกข้าม
     * ‼️ ชนิดของบล็อกมาจากฐานข้อมูล ต้องผ่านรูปแบบที่กำหนดก่อนประกอบเป็น path ไฟล์ (กัน path traversal)
     *
     * @param  array<int, array{type:string, data:array<string,mixed>}> $blocks
     * @return array<int, array{type:string, data:array<string,mixed>, partial:?string}>
     */
    private function withPartials(array $blocks): array
    {
        $dir = dirname(__DIR__) . '/Views/partials/blocks/';

        foreach ($blocks as $index => $block) {
            $file = null;

            // ชื่อไฟล์รับได้ทั้ง trust-bar.php (kebab ตามแบบไฟล์อื่น) และ trust_bar.php (ตรงกับ key)
            if (preg_match('/^[a-z][a-z0-9_]{0,40}$/', $block['type']) === 1) {
                foreach ([str_replace('_', '-', $block['type']), $block['type']] as $name) {
                    if ($file === null && is_file($dir . $name . '.php')) {
                        $file = $dir . $name . '.php';
                    }
                }
            }

            if ($block['type'] === 'rich_text' && isset($block['data']['html'])) {
                $block['data']['html'] = $this->withBase($this->sanitizer->clean((string) $block['data']['html']));
            }

            $blocks[$index] = $block + ['partial' => $file];
        }

        return $blocks;
    }

    /**
     * @param array<string, mixed>                 $data
     * @param array<string, mixed>                 $seo
     * @param array<int, array<string, mixed>>     $breadcrumbs
     * @param array<string, mixed>                 $schemaContext
     */
    private function result(int $status, string $view, string $title, array $data, array $seo, array $breadcrumbs,
        bool $private, array $schemaContext, string $locale, array $translations): array
    {
        $graph = $this->schema->graph($schemaContext + [
            'locale'      => $locale,
            'canonical'   => $seo['canonical'],
            'title'       => $title,
            'description' => $seo['description'],
            'breadcrumbs' => $breadcrumbs,
        ]);

        return [
            'status'      => $status,
            'view'        => $view,
            'title'       => $title,
            'data'        => $data,
            'seo'         => $seo,
            'graph'       => $graph,
            'breadcrumbs' => $breadcrumbs,
            'private'     => $private,
            'locale'      => $locale,
            'switch'      => $translations,
        ];
    }

    /**
     * เติม base path ให้ลิงก์และรูปแบบ root-relative ในเนื้อหา HTML (src="/wp-content/..." · href="/...")
     *
     * ‼️ เนื้อหาที่ย้ายจากเว็บเดิมเขียน path แบบ root-relative — บน production (document root = public/)
     *    base เป็นค่าว่างจึงไม่เปลี่ยนอะไร แต่บน staging ที่ติดตั้งใต้ /~omcai/mlmsoftware รูปทุกรูปจะ 404
     *    ทำตอนแสดงผลเท่านั้น ห้ามเขียน base ลงฐานข้อมูล — ย้ายที่ติดตั้งเมื่อไหร่เนื้อหาพังทั้งหมด
     */
    private function withBase(string $html): string
    {
        $base = Path::base();

        if ($base === '' || $html === '') {
            return $html;
        }

        // เฉพาะ "/x" ไม่ใช่ "//x" (protocol-relative = โดเมนอื่น)
        $html = (string) preg_replace('#\b(src|href)="/(?!/)#', '$1="' . $base . '/', $html);

        // srcset มีหลาย URL คั่นด้วยจุลภาค — ต้องเติม base ให้ทุกตัว ไม่ใช่แค่ตัวแรก
        return (string) preg_replace_callback('#\bsrcset="([^"]*)"#', static fn (array $m): string => 'srcset="'
            . preg_replace('#(^|,\s*)/(?!/)#', '$1' . $base . '/', $m[1]) . '"', $html);
    }
}

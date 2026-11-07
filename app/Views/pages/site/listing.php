<?php
/**
 * หน้ารวมบทความ — /blog/ · /category/{slug}/ (+ /page/N/) · ผลค้นหา /blog/?q=
 *
 * ส่วนเปิดน้ำเงินหมึก (บล็อก hero ของหน้า /blog/ ใน CMS) → ตัวกรองหมวด + ช่องค้นหา → การ์ดบทความ → แถบชวนติดต่อ (หลังการ์ดที่ 4) → แบ่งหน้า
 *
 * ‼️ ตัวกรองหมวดเป็นลิงก์ไป URL หมวดเดิม (/category/…/) ไม่ใช่ ?filter= — URL ที่ index อยู่แล้วไม่เปลี่ยน และไม่เกิด URL ซ้ำ
 * ‼️ ค้นหาเป็นฟอร์ม GET ธรรมดา (ใช้ได้ไม่มี JS) · หน้าผลค้นหา noindex (SiteViewService)
 * ‼️ แถบชวนติดต่อชี้ฟอร์ม lead จริง: /contact/#lead-form (ปรึกษา) · /demo/#lead-form (นัดชม Demo)
 */

declare(strict_types=1);

use App\Services\LocaleService;
use Core\Path;

$siteItems      = array_values((array) ($siteItems ?? []));
$siteCategories = (array) ($siteCategories ?? []);
$siteCategoryId = (int) ($siteCategoryId ?? 0);
$siteSearch     = (string) ($siteSearch ?? '');
$siteBlogUrl    = (string) ($siteBlogUrl ?? '/blog/');
$siteLocale     = LocaleService::current();

$blocks = \App\Services\BlockService::prepare([['type' => 'hero'] + (array) ($siteHero ?? [])]);
include __DIR__ . '/../../partials/blocks.php';

// แถบชวนติดต่อแทรกหลังการ์ดที่ 4 (หรือท้ายรายการถ้าน้อยกว่า)
$listingSplit = min(4, count($siteItems));
?>
<div class="container blog-listing">
    <div class="blog-toolbar">
        <nav class="blog-filter" aria-label="<?= e(__('site.blog_filter')) ?>">
            <a class="blog-pill" href="<?= e(url(Path::encode($siteBlogUrl))) ?>"<?php if ($siteCategoryId === 0 && $siteSearch === ''): ?> aria-current="page"<?php endif; ?>>
                <?= icon('dashboard', 'icon-sm') ?><?= e(__('site.blog_all')) ?>
            </a>
            <?php foreach ($siteCategories as $siteCategory): ?>
                <a class="blog-pill" href="<?= e(url(Path::encode((string) $siteCategory['path']))) ?>"<?php if ((int) $siteCategory['id'] === $siteCategoryId): ?> aria-current="page"<?php endif; ?>>
                    <?= icon('book', 'icon-sm') ?><?= e((string) $siteCategory['name']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <form class="blog-search" role="search" method="GET" action="<?= e(url(Path::encode($siteBlogUrl))) ?>">
            <label class="visually-hidden" for="blog-search-q"><?= e(__('site.blog_search_label')) ?></label>
            <?= icon('search', 'icon-sm') ?>
            <input id="blog-search-q" type="search" name="q" value="<?= e($siteSearch) ?>" maxlength="100"
                   placeholder="<?= e(__('site.blog_search_placeholder')) ?>" autocomplete="off">
            <button type="submit" class="blog-search-submit"><?= e(__('site.blog_search_button')) ?></button>
        </form>
    </div>

    <?php if ($siteSearch !== ''): ?>
        <p class="blog-results" role="status">
            <?= e(__('site.blog_results', ['q' => $siteSearch, 'count' => (string) (int) ($siteTotal ?? 0)])) ?>
            <a href="<?= e(url(Path::encode($siteBlogUrl))) ?>"><?= e(__('site.blog_search_clear')) ?></a>
        </p>
    <?php endif; ?>

    <?php
    $newsItems           = array_slice($siteItems, 0, $listingSplit);
    $newsItemsEagerFirst = true; // รูปการ์ดแรกคือ LCP ของหน้ารวมบทความบนมือถือ (วัดจริง 2026-11-05)
    $filtered            = $siteSearch !== '';
    include __DIR__ . '/../../partials/news-item.php';
    ?>
</div>

<?php if ($siteItems !== []): ?>
    <?php
    $blocks = \App\Services\BlockService::prepare([[
        'type'    => 'cta_band',
        '_uid'    => 'blog-cta',
        'title'   => __('site.blog_cta_title'),
        'text'    => __('site.blog_cta_text'),
        'actions' => [
            ['label' => __('site.blog_cta_consult'), 'url' => LocaleService::localize('/contact/', $siteLocale) . '#lead-form', 'style' => 'light', 'icon' => 'phone'],
            ['label' => __('site.blog_cta_demo'), 'url' => LocaleService::localize('/demo/', $siteLocale) . '#lead-form', 'style' => 'ghost-light', 'icon' => 'calendar'],
        ],
    ]]);
    include __DIR__ . '/../../partials/blocks.php';
    ?>
<?php endif; ?>

<div class="container blog-listing">
    <?php
    $newsItems = array_slice($siteItems, $listingSplit);
    if ($newsItems !== []) {
        include __DIR__ . '/../../partials/news-item.php';
    }

    $pathPaginationBase  = (string) ($siteBase ?? '/blog/');
    $pathPaginationPage  = (int) ($sitePage ?? 1);
    $pathPaginationPages = (int) ($sitePages ?? 1);
    include __DIR__ . '/../../partials/path-pagination.php';
    ?>
</div>

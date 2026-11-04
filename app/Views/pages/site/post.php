<?php
/**
 * บทความ — H1 (ชื่อบทความ) + breadcrumb มาจาก layout (page-head) · ที่นี่วาดวันที่ หมวด ผู้เขียน รูปเด่น เนื้อหา
 *   บทความที่เกี่ยวข้อง (หมวดเดียวกัน) และแถบชวนติดต่อท้ายบทความ
 *
 * ‼️ วันที่เก็บเป็น ค.ศ. แสดงตามภาษา (ไทย = พ.ศ.) ผ่าน format_date() · <time datetime> เป็น ISO เสมอ
 */

declare(strict_types=1);

use Core\Path;

$sitePost    = (array) ($sitePost ?? []);
$sitePreview = (bool) ($sitePreview ?? false);
$siteImage   = $sitePost['image'] ?? null;
?>
<?php if ($sitePreview): ?>
    <p class="card small" role="status"><?= e(__('site.preview_notice')) ?></p>
<?php endif; ?>

<article class="post">
    <p class="post-meta">
        <?php if ($sitePost['datetime'] !== ''): ?>
            <span class="post-meta-item"><?= icon('calendar', 'icon-sm') ?><time datetime="<?= e($sitePost['datetime']) ?>"><?= e($sitePost['date']) ?></time></span>
        <?php endif; ?>
        <?php if ($sitePost['category'] !== ''): ?>
            <span class="post-meta-item"><?= icon('book', 'icon-sm') ?>
                <span class="visually-hidden"><?= e(__('site.post_category')) ?>: </span>
                <a href="<?= e(url($sitePost['category_url'])) ?>"><?= e($sitePost['category']) ?></a>
            </span>
        <?php endif; ?>
        <?php if ($sitePost['author'] !== ''): ?>
            <span class="post-meta-item"><?= icon('users', 'icon-sm') ?><?= e(__('site.post_by', ['name' => $sitePost['author']])) ?></span>
        <?php endif; ?>
    </p>

    <?php if ($siteImage !== null): ?>
        <?php /* รูปเด่นคือ LCP ของหน้าบทความ — ห้าม lazy */ ?>
        <figure class="post-cover">
            <img src="<?= e(url(Path::encode((string) $siteImage['path']))) ?>"
                 alt="<?= e((string) $siteImage['alt']) ?>"
                 <?php if ($siteImage['width'] !== null && $siteImage['height'] !== null): ?>
                 width="<?= e((string) $siteImage['width']) ?>" height="<?= e((string) $siteImage['height']) ?>"
                 <?php endif; ?>
                 fetchpriority="high" decoding="async">
        </figure>
    <?php endif; ?>

    <div class="prose post-body">
        <?php
        /* ‼️ เนื้อหาบทความเป็น HTML — ผ่าน App\Services\HtmlSanitizer (allowlist) ใน SiteViewService แล้ว
              ครอบ e() ซ้ำจะได้แท็กดิบขึ้นเป็นข้อความบนหน้า */
        echo $sitePost['content'];
        ?>
    </div>

    <p class="post-back">
        <a href="<?= e(url((string) ($sitePost['blog_url'] ?? '/blog/'))) ?>"><?= icon('chevron-left', 'icon-sm') ?><?= e(__('site.back_to_blog')) ?></a>
    </p>
</article>

<?php if ((array) ($sitePost['related'] ?? []) !== []): ?>
    <section class="post-related" aria-labelledby="post-related-title">
        <h2 id="post-related-title"><?= e(__('site.related_posts')) ?></h2>
        <?php $newsItems = (array) $sitePost['related']; include __DIR__ . '/../../partials/news-item.php'; ?>
    </section>
<?php endif; ?>

<?php
$blocks = \App\Services\BlockService::prepare([[
    'type'    => 'cta_band',
    'title'   => __('site.post_cta_title'),
    'text'    => __('site.post_cta_text'),
    'actions' => [
        ['label' => __('site.post_cta_demo'), 'url' => '/demo/', 'style' => 'light', 'icon' => 'calendar'],
        ['label' => __('site.post_cta_call'), 'url' => 'tel:+66991095544', 'style' => 'ghost-light', 'icon' => 'phone'],
    ],
]]);
include __DIR__ . '/../../partials/blocks.php';

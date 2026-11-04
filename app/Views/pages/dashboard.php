<?php
/**
 * หน้าแรกหลังบ้าน — ตัวเลขสรุป (ตามบทบาท) + ทางลัด
 *
 * ตัวแปร: $summary จาก App\Services\DashboardService::summary()
 * ‼️ ทางลัดซ่อนตามบทบาทเพื่อความสะอาดเท่านั้น — สิทธิ์จริงอยู่ที่ middleware ของ route
 */

declare(strict_types=1);

$summary = (array) ($summary ?? []);

$dashTiles = [];

if (!empty($summary['leads'])) {
    $dashTiles[] = ['icon' => 'inbox', 'value' => $summary['new_leads'], 'label' => __('cms.dashboard.new_leads'), 'url' => '/admin/leads?status=new'];
}

if (!empty($summary['content'])) {
    $dashTiles[] = ['icon' => 'file', 'value' => $summary['pages_published'], 'label' => __('cms.dashboard.pages_published'), 'url' => '/admin/pages?status=published'];
    $dashTiles[] = ['icon' => 'edit', 'value' => $summary['pages_draft'], 'label' => __('cms.dashboard.pages_draft'), 'url' => '/admin/pages?status=draft'];
    $dashTiles[] = ['icon' => 'book', 'value' => $summary['posts_published'], 'label' => __('cms.dashboard.posts_published'), 'url' => '/admin/posts?status=published'];
    $dashTiles[] = ['icon' => 'edit', 'value' => $summary['posts_draft'], 'label' => __('cms.dashboard.posts_draft'), 'url' => '/admin/posts?status=draft'];
    $dashTiles[] = ['icon' => 'warning', 'value' => $summary['not_found_7d'], 'label' => __('cms.dashboard.not_found_7d'), 'url' => '/admin/not-found'];
    $dashTiles[] = ['icon' => 'link', 'value' => $summary['redirect_hits'], 'label' => __('cms.dashboard.redirect_hits'), 'url' => '/admin/redirects?sort=hits&dir=desc'];
    $dashTiles[] = ['icon' => 'image', 'value' => $summary['media_no_alt'], 'label' => __('cms.dashboard.media_no_alt'), 'url' => '/admin/media'];
}

$dashLinks = [];

if (!empty($summary['content'])) {
    $dashLinks[] = ['icon' => 'plus', 'label' => __('cms.pages.add'), 'url' => '/admin/pages/create'];
    $dashLinks[] = ['icon' => 'plus', 'label' => __('cms.posts.add'), 'url' => '/admin/posts/create'];
    $dashLinks[] = ['icon' => 'image', 'label' => __('cms.media.upload'), 'url' => '/admin/media'];
}
$dashLinks[] = ['icon' => 'lock', 'label' => __('cms.account.title'), 'url' => '/admin/account/password'];
$dashLinks[] = ['icon' => 'book', 'label' => __('ui.manual'), 'url' => '/admin/manual'];
?>
<div class="stack">
    <section aria-labelledby="dash-numbers">
        <h2 id="dash-numbers" class="visually-hidden"><?= e(__('cms.dashboard.numbers')) ?></h2>
        <ul class="tile-grid">
            <?php foreach ($dashTiles as $dashTile): ?>
                <li>
                    <a class="tile" href="<?= e(url($dashTile['url'])) ?>">
                        <span class="tile-icon"><?= icon($dashTile['icon']) ?></span>
                        <span class="stat-value"><?= e(format_money((int) $dashTile['value'], 0)) ?></span>
                        <span class="tile-summary"><?= e($dashTile['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="card stack" aria-labelledby="dash-links">
        <h2 id="dash-links"><?= e(__('cms.dashboard.quick_links')) ?></h2>
        <div class="row">
            <?php foreach ($dashLinks as $dashLink): ?>
                <a class="btn btn-secondary" href="<?= e(url($dashLink['url'])) ?>"><?= icon($dashLink['icon']) ?><?= e($dashLink['label']) ?></a>
            <?php endforeach; ?>
        </div>
    </section>
</div>

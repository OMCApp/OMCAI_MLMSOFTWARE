<?php
/**
 * หน้ารวมบทความ — /blog/ และ /category/{slug}/ (+ /page/N/)
 */

declare(strict_types=1);

$siteItems     = (array) ($siteItems ?? []);
$siteListIntro = (string) ($siteListIntro ?? '');
?>
<div class="container">
<?php if ($siteListIntro !== ''): ?>
    <p class="page-lead"><?= e(strip_tags($siteListIntro)) ?></p>
<?php endif; ?>

<?php
$newsItems = $siteItems;
include __DIR__ . '/../../partials/news-item.php';

$pathPaginationBase  = (string) ($siteBase ?? '/blog/');
$pathPaginationPage  = (int) ($sitePage ?? 1);
$pathPaginationPages = (int) ($sitePages ?? 1);
include __DIR__ . '/../../partials/path-pagination.php';
?>
</div>

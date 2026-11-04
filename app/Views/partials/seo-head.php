<?php
/**
 * แท็ก SEO ใน <head> ของหน้าสาธารณะ — ข้อมูลมาจาก App\Services\SeoService::meta()
 *
 * ‼️ ไม่ include ตรงในหน้า — PublicController render ไฟล์นี้เป็นสตริง $seoHead ส่งให้ layout
 * ‼️ ทุกค่าผ่าน e() — title/description มาจาก CMS ซึ่งคือข้อมูลที่ผู้ใช้กรอก
 *
 * ตัวแปรที่รับ: $seoHeadMeta  [title, description, canonical, robots, og => [property => content], twitter => [...], feed => [title, href]]
 */

declare(strict_types=1);

$seoHeadMeta = (array) ($seoHeadMeta ?? []);
?>
<title><?= e((string) ($seoHeadMeta['title'] ?? '')) ?></title>
<?php if ((string) ($seoHeadMeta['description'] ?? '') !== ''): ?>
    <meta name="description" content="<?= e((string) $seoHeadMeta['description']) ?>">
<?php endif; ?>
    <meta name="robots" content="<?= e((string) ($seoHeadMeta['robots'] ?? '')) ?>">
<?php if (($seoHeadMeta['canonical'] ?? null) !== null): ?>
    <link rel="canonical" href="<?= e((string) $seoHeadMeta['canonical']) ?>">
<?php endif; ?>
<?php /* hreflang — ทุกภาษาของหน้านี้รวมตัวเอง + x-default (ไทย) · ชุดเดียวกับ sitemap */ ?>
<?php foreach ((array) ($seoHeadMeta['alternates'] ?? []) as $seoHeadAlternate): ?>
    <link rel="alternate" hreflang="<?= e((string) $seoHeadAlternate['hreflang']) ?>" href="<?= e((string) $seoHeadAlternate['href']) ?>">
<?php endforeach; ?>
<?php foreach ((array) ($seoHeadMeta['og'] ?? []) as $seoHeadProperty => $seoHeadContent): ?>
    <meta property="<?= e((string) $seoHeadProperty) ?>" content="<?= e((string) $seoHeadContent) ?>">
<?php endforeach; ?>
<?php foreach ((array) ($seoHeadMeta['twitter'] ?? []) as $seoHeadProperty => $seoHeadContent): ?>
    <meta name="<?= e((string) $seoHeadProperty) ?>" content="<?= e((string) $seoHeadContent) ?>">
<?php endforeach; ?>
<?php if (isset($seoHeadMeta['feed']['href'])): ?>
    <link rel="alternate" type="application/rss+xml" title="<?= e((string) $seoHeadMeta['feed']['title']) ?>" href="<?= e((string) $seoHeadMeta['feed']['href']) ?>">
<?php endif; ?>

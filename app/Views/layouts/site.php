<?php
/**
 * layout ของเว็บสาธารณะ (mlmsoftware.omc.co.th) — แยกจาก layouts/app.php ของหลังบ้าน
 *
 * ‼️ โหมดสว่างอย่างเดียว — ผู้ใช้สั่ง "พื้นขาว/สว่างเป็นหลัก · ไม่เอาเว็บมืด" (project/NOTES.md)
 *    data-theme="light" ตายตัว ไม่อ่าน theme() ของ session · token โหมดมืดยังมีครบเพราะหลังบ้านใช้
 *
 * ‼️ เมนูเป็นแถบบนเสมอ (design.md 5.15: เว็บสาธารณะ = header) ไม่อ่าน config('ui.nav')
 *    ค่านั้นเป็นของหลังบ้าน (sidebar)
 *
 * ตัวแปรที่รับ (Controller ส่งมาใน $data):
 *   $site            view-model จาก App\Services\SiteService::layout() — header/footer/ติดต่อ
 *   $seoHeadMeta     ข้อมูล SEO ของหน้า (App\Services\SeoService::meta()) → partials/seo-head.php
 *   $jsonLdGraph     schema ของหน้า → partials/json-ld.php
 *   $breadcrumbs     [['label','url'], ...] → page-head.php
 *   $title           ชื่อหน้า (สำรอง) · $metaDescription คำอธิบาย (สำรอง)
 *   $content         เนื้อหาของหน้า (Controller::render ใส่ให้)
 *
 * ‼️ ส่งเป็นข้อมูล ไม่ใช่ string HTML — partial เป็นคนวาดและ escape เอง (กฎเหล็กข้อ 6 · security/output-escape)
 *    ไม่มี $seoHeadMeta (เช่นหน้า /_preview) = ใช้ <title> และ description สำรองจาก $title + $metaDescription
 */

declare(strict_types=1);

$site            = (array) ($site ?? \App\Services\SiteService::layout(current_path()));
$metaDescription = (string) ($metaDescription ?? '');
$siteName        = (string) ($site['brand']['name'] ?? config('name'));
$pageBreadcrumb  = (array) ($breadcrumbs ?? []);
// ‼️ ภาษาของหน้ามาจาก URL (App\Services\LocaleService) — lang ผิด = screen reader อ่านผิดภาษา และ Google เดาภาษาผิด
$siteHtmlLang    = (string) ($site['html_lang'] ?? 'th');
?>
<!doctype html>
<html lang="<?= e($siteHtmlLang) ?>" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (isset($seoHeadMeta)): ?>
        <?php include __DIR__ . '/../partials/seo-head.php'; ?>
        <?php if (isset($jsonLdGraph)) { include __DIR__ . '/../partials/json-ld.php'; } ?>
    <?php else: ?>
        <title><?= e(($title ?? '') !== '' && ($title ?? '') !== $siteName ? $title . ' | ' . $siteName : $siteName) ?></title>
        <?php if ($metaDescription !== ''): ?>
            <meta name="description" content="<?= e($metaDescription) ?>">
        <?php endif; ?>
    <?php endif; ?>
    <?php /* ‼️ preload เฉพาะฟอนต์ที่จอแรกของภาษานี้ใช้จริง (config/locales.php) — จีนใช้ฟอนต์ระบบ ไม่ preload */ ?>
    <?php foreach ((array) ($site['preload_fonts'] ?? []) as $siteFont): ?>
        <link rel="preload" href="<?= e(url($siteFont)) ?>" as="font" type="font/woff2" crossorigin>
    <?php endforeach; ?>
    <?php include __DIR__ . '/../partials/favicon.php'; ?>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
    <?php /* GA4/GTM จาก CMS → ตั้งค่าเว็บ — วาดเฉพาะ production (Phase 3) */ ?>
    <?php if (empty($sitePreview)) { include __DIR__ . '/../partials/tracking.php'; } ?>
</head>
<body class="site-body">

<a class="skip-link" href="#main"><?= e(__('site.skip_to_content')) ?></a>

<?php include __DIR__ . '/../partials/site-header.php'; ?>

<main id="main" class="site-main" tabindex="-1">
    <div class="page" data-width="wide" data-page="public">
        <?php
        /* ‼️ หน้าที่ไม่มีบล็อก hero (บทความ นโยบาย) ใช้หัวเพจกลางเหมือนหลังบ้าน
              page-head.php เช็ค hero_rendered() เอง — หน้าที่มี hero จะไม่ได้ <h1> ซ้ำ */
        include __DIR__ . '/../partials/page-head.php';
        ?>
        <?= $content ?? '' ?>
    </div>
</main>

<?php include __DIR__ . '/../partials/site-footer.php'; ?>

<?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/js-lang.php'; ?>
<script src="<?= e(asset('/assets/js/app.js')) ?>" defer<?php if (csp_nonce() !== ''): ?> nonce="<?= e(csp_nonce()) ?>"<?php endif; ?>></script>
</body>
</html>

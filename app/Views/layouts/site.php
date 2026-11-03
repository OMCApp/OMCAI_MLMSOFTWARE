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
 *   $seo             ข้อมูล SEO ของหน้า (array) — ส่งต่อให้ช่อง SEO head ข้างล่าง
 *   $jsonLd          list ของ schema (array) — ส่งต่อให้ช่อง SEO head
 *   $title           ชื่อหน้า (สำรอง) · $metaDescription คำอธิบาย (สำรอง)
 *   $content         เนื้อหาของหน้า (Controller::render ใส่ให้)
 *
 * ‼️ ช่อง SEO head = partials/seo-head.php (Phase 2 สร้าง) — มีไฟล์เมื่อไหร่ layout include ให้เอง
 *    partial นั้นเป็นคนวาด <title> meta canonical robots OG และ <script type="application/ld+json">
 *    ไม่รับเป็น string HTML ดิบ เพราะ layout ต้องพิมพ์โดยไม่ผ่าน e() (กฎเหล็กข้อ 6 · security/output-escape)
 *    ยังไม่มีไฟล์ = ใช้ <title> และ description สำรองจาก $title + $metaDescription
 */

declare(strict_types=1);

$site            = (array) ($site ?? \App\Services\SiteService::layout(current_path()));
$seo             = (array) ($seo ?? []);
$jsonLd          = (array) ($jsonLd ?? []);
$metaDescription = (string) ($metaDescription ?? '');
$siteName        = (string) ($site['brand']['name'] ?? config('name'));
?>
<!doctype html>
<html lang="<?= e(locale()) ?>" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (is_file(__DIR__ . '/../partials/seo-head.php')): ?>
        <?php include __DIR__ . '/../partials/seo-head.php'; ?>
    <?php else: ?>
        <title><?= e(($title ?? '') !== '' && ($title ?? '') !== $siteName ? $title . ' | ' . $siteName : $siteName) ?></title>
        <?php if ($metaDescription !== ''): ?>
            <meta name="description" content="<?= e($metaDescription) ?>">
        <?php endif; ?>
    <?php endif; ?>
    <?php /* ‼️ preload เฉพาะฟอนต์ไทยสองตัวที่จอแรกใช้จริง (พาดหัว + เนื้อความ) — ลด LCP / FOUT */ ?>
    <link rel="preload" href="<?= e(url('/assets/vendor/fonts/prompt-thai-600-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(url('/assets/vendor/fonts/ibm-plex-sans-thai-thai-400-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <?php include __DIR__ . '/../partials/favicon.php'; ?>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
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
<script src="<?= e(asset('/assets/js/app.js')) ?>" defer></script>
</body>
</html>

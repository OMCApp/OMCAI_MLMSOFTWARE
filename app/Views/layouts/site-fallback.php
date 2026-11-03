<?php
/**
 * layout ชั่วคราวของเว็บสาธารณะ — ใช้จนกว่า Phase 1 ส่ง layouts/site.php (header + mega nav + footer จริง)
 *
 * สลับไปใช้ของจริง: แก้ App\Controllers\PublicController::LAYOUT บรรทัดเดียว
 *
 * สัญญาของ layout เว็บสาธารณะ (ของจริงต้องรับชุดเดียวกัน):
 *   $seoHead      string  HTML ของ <head> ที่ escape แล้วทุกค่า (partials/seo-head.php)
 *   $jsonLd       string  <script type="application/ld+json"> ที่ encode แบบปลอดภัยแล้ว (partials/json-ld.php)
 *   $content      string  HTML ของหน้า
 *   $breadcrumbs  array   [['label' => string, 'url' => ?string], ...] · ว่าง = ไม่แสดง (หน้าแรก, 404)
 *   $title        string  ชื่อหน้า → H1 ผ่าน partials/page-head.php (หน้าที่มี hero ข้ามให้เอง)
 *
 * ‼️ เว็บสาธารณะเป็นโหมดสว่างอย่างเดียว (SCOPE.md) — ไม่มีปุ่มสลับโหมด/ภาษา และไม่มีปุ่มแจ้งปัญหาของหลังบ้าน
 */

declare(strict_types=1);

$seoHead     = (string) ($seoHead ?? '');
$jsonLd      = (string) ($jsonLd ?? '');
$breadcrumbs = (array) ($breadcrumbs ?? []);
?>
<!doctype html>
<html lang="th" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    /* ‼️ $seoHead / $jsonLd เป็น HTML ที่ partial สร้างและ escape ทุกค่าแล้ว (เหมือน $content)
          ครอบ e() ซ้ำจะได้ &lt;meta ... ขึ้นเป็นข้อความบนหน้าแทนที่จะเป็นแท็ก */
    echo $seoHead;
    ?>
    <?php include __DIR__ . '/../partials/favicon.php'; ?>
    <link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
    <?php echo $jsonLd; ?>
</head>
<body>

<header class="topbar">
    <a class="brand" href="<?= e(url('/')) ?>">
        <span class="brand-name"><?= e((string) ($siteBrand ?? config('name'))) ?></span>
    </a>
</header>

<main class="content">
    <div class="page" data-width="wide" data-page="public">
        <?php
        $pageBreadcrumb = $breadcrumbs;
        include __DIR__ . '/../partials/page-head.php';
        ?>
        <?= $content ?? '' ?>
    </div>
</main>

<footer class="site-footer">
    <span><?= e((string) ($siteBrand ?? config('name'))) ?></span>
    <span class="spacer muted">&copy; <?= e(date('Y')) ?></span>
</footer>

</body>
</html>

<?php
/**
 * บทความ — H1 (ชื่อบทความ) มาจาก layout · ที่นี่วาดวันที่ หมวด รูปเด่น และเนื้อหา
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

<article class="stack">
    <p class="muted small">
        <?php if ($sitePost['datetime'] !== ''): ?>
            <time datetime="<?= e($sitePost['datetime']) ?>"><?= e($sitePost['date']) ?></time>
        <?php endif; ?>
        <?php if ($sitePost['category'] !== ''): ?>
            · <a href="<?= e(url($sitePost['category_url'])) ?>"><?= e($sitePost['category']) ?></a>
        <?php endif; ?>
        <?php if ($sitePost['author'] !== ''): ?>
            · <?= e($sitePost['author']) ?>
        <?php endif; ?>
    </p>

    <?php if ($siteImage !== null): ?>
        <?php /* รูปเด่นคือ LCP ของหน้าบทความ — ห้าม lazy */ ?>
        <img src="<?= e(url(Path::encode((string) $siteImage['path']))) ?>"
             alt="<?= e((string) $siteImage['alt']) ?>"
             <?php if ($siteImage['width'] !== null && $siteImage['height'] !== null): ?>
             width="<?= e((string) $siteImage['width']) ?>" height="<?= e((string) $siteImage['height']) ?>"
             <?php endif; ?>
             fetchpriority="high">
    <?php endif; ?>

    <div class="prose">
        <?php
        /* ‼️ เนื้อหาบทความเป็น HTML — ผ่าน App\Services\HtmlSanitizer (allowlist) ใน SiteViewService แล้ว
              ครอบ e() ซ้ำจะได้แท็กดิบขึ้นเป็นข้อความบนหน้า */
        echo $sitePost['content'];
        ?>
    </div>
</article>

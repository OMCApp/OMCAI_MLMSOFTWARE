<?php
/**
 * แบ่งหน้าแบบ path (/blog/page/2/) — ของเว็บสาธารณะ
 *
 * ‼️ ต่างจาก partials/pagination.php (หลังบ้าน) ที่ใช้ ?page= — URL ของบล็อกเดิมเป็น /page/N/
 *    และต้องคงไว้ตามเดิม (baseline มี /blog/page/2/ และ /blog/page/3/)
 *    หน้า 1 ชี้ไป base ตรงๆ ไม่ใช่ /page/1/ (ตัวนั้น 301 กลับมาที่ base)
 *
 * ตัวแปรที่รับ: $pathPaginationBase (path decode แล้ว ลงท้าย /) · $pathPaginationPage · $pathPaginationPages
 */

declare(strict_types=1);

use Core\Path;

$pathPaginationBase  = (string) ($pathPaginationBase ?? '/');
$pathPaginationPage  = max(1, (int) ($pathPaginationPage ?? 1));
$pathPaginationPages = max(1, (int) ($pathPaginationPages ?? 1));

if ($pathPaginationPages <= 1) {
    return;
}

$pathPaginationHref = static fn (int $target): string
    => url(Path::encode($target === 1 ? $pathPaginationBase : $pathPaginationBase . 'page/' . $target . '/'));
?>
<?php
// หน้าแรก · หน้าสุดท้าย · รอบหน้าปัจจุบัน ±1 — ช่องว่างที่เหลือเป็น "…" (12 หน้าก็ไม่ล้นจอมือถือ)
$pathPaginationShow = [];
for ($pathPaginationI = 1; $pathPaginationI <= $pathPaginationPages; $pathPaginationI++) {
    if ($pathPaginationI === 1 || $pathPaginationI === $pathPaginationPages || abs($pathPaginationI - $pathPaginationPage) <= 1) {
        $pathPaginationShow[] = $pathPaginationI;
    }
}
?>
<nav class="site-pagination" aria-label="<?= e(__('site.pagination')) ?>">
    <?php if ($pathPaginationPage > 1): ?>
        <a class="site-pagination-step" rel="prev" href="<?= e($pathPaginationHref($pathPaginationPage - 1)) ?>"
           aria-label="<?= e(__('site.newer')) ?>"><?= icon('chevron-left', 'icon-sm') ?></a>
    <?php endif; ?>

    <?php $pathPaginationPrev = 0; ?>
    <?php foreach ($pathPaginationShow as $pathPaginationI): ?>
        <?php if ($pathPaginationI - $pathPaginationPrev > 1): ?><span class="site-pagination-gap" aria-hidden="true">…</span><?php endif; ?>
        <a class="site-pagination-page" href="<?= e($pathPaginationHref($pathPaginationI)) ?>"
           <?php if ($pathPaginationI === $pathPaginationPage): ?>aria-current="page"<?php endif; ?>
           aria-label="<?= e(__('site.page_n', ['page' => (string) $pathPaginationI])) ?>"><?= e((string) $pathPaginationI) ?></a>
        <?php $pathPaginationPrev = $pathPaginationI; ?>
    <?php endforeach; ?>

    <?php if ($pathPaginationPage < $pathPaginationPages): ?>
        <a class="site-pagination-step" rel="next" href="<?= e($pathPaginationHref($pathPaginationPage + 1)) ?>"
           aria-label="<?= e(__('site.older')) ?>"><?= icon('chevron-right', 'icon-sm') ?></a>
    <?php endif; ?>
</nav>
<?php
unset($pathPaginationHref, $pathPaginationI, $pathPaginationShow, $pathPaginationPrev);

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
<nav class="pagination row" aria-label="<?= e(__('site.pagination')) ?>">
    <?php if ($pathPaginationPage > 1): ?>
        <a class="btn" rel="prev" href="<?= e($pathPaginationHref($pathPaginationPage - 1)) ?>"><?= e(__('site.newer')) ?></a>
    <?php endif; ?>

    <?php for ($pathPaginationI = 1; $pathPaginationI <= $pathPaginationPages; $pathPaginationI++): ?>
        <a class="btn" href="<?= e($pathPaginationHref($pathPaginationI)) ?>"
           <?php if ($pathPaginationI === $pathPaginationPage): ?>aria-current="page"<?php endif; ?>><?= e((string) $pathPaginationI) ?></a>
    <?php endfor; ?>

    <?php if ($pathPaginationPage < $pathPaginationPages): ?>
        <a class="btn" rel="next" href="<?= e($pathPaginationHref($pathPaginationPage + 1)) ?>"><?= e(__('site.older')) ?></a>
    <?php endif; ?>
</nav>
<?php
unset($pathPaginationHref, $pathPaginationI);

<?php
/**
 * แบ่งหน้า
 *
 * ‼️ หน้าปัจจุบันอยู่ใน query string ไม่ใช่ session
 *    ไม่งั้นแชร์ลิงก์ไม่ได้ กด back แล้วค่าหาย เปิดสองแท็บตีกัน
 *
 * ตัวแปรที่รับ: $paginationTotal $paginationPage $paginationPerPage
 */

declare(strict_types=1);

$paginationTotal   = (int) ($paginationTotal ?? 0);
$paginationPage    = max(1, (int) ($paginationPage ?? 1));
$paginationPerPage = max(1, (int) ($paginationPerPage ?? 25));
$paginationPages   = (int) ceil($paginationTotal / $paginationPerPage);

if ($paginationTotal === 0) {
    return;
}

$paginationLink = static function (int $target): string {
    $query = $_GET;
    $query['page'] = $target;

    return url(current_path() . '?' . http_build_query($query));
};

$paginationFrom = ($paginationPage - 1) * $paginationPerPage + 1;
$paginationTo   = min($paginationPage * $paginationPerPage, $paginationTotal);
?>
<div class="row">
    <span class="muted small">
        <?= e(__('common.showing', ['from' => (string) $paginationFrom, 'to' => (string) $paginationTo, 'total' => (string) $paginationTotal])) ?>
    </span>

    <?php if ($paginationPages > 1): ?>
        <nav class="pagination spacer" aria-label="<?= e(__('common.per_page')) ?>">
            <?php for ($paginationI = max(1, $paginationPage - 2); $paginationI <= min($paginationPages, $paginationPage + 2); $paginationI++): ?>
                <a class="btn" href="<?= e($paginationLink($paginationI)) ?>"
                   <?php if ($paginationI === $paginationPage): ?>aria-current="page"<?php endif; ?>><?= e((string) $paginationI) ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>

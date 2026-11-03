<?php
/**
 * หัวตารางที่กดเพื่อเรียงลำดับได้
 *
 * ‼️ ทุกหน้ารายงานต้องใช้ตัวนี้ ห้ามเขียนเองรายหน้า
 *    มาตรฐานบังคับให้หัวตารางกดเรียงได้ทุกตาราง จึงต้องเป็นของกลางตั้งแต่ต้น
 *
 * ‼️ ทิศทางและชื่อคอลัมน์อยู่ใน query string ไม่ใช่ session
 *    เพื่อให้แชร์ลิงก์ได้ กด back ได้ และเปิดหลายแท็บไม่ตีกัน
 *
 * ตัวแปรที่รับ: $thSortColumn $thSortLabel $thSortSort $thSortDir
 *
 * ‼️ ชื่อขึ้นต้นด้วย thSort เสมอ — PHP include ใช้ขอบเขตร่วมกับหน้าที่เรียก
 *    ชื่อกลางๆ อย่าง $label หรือ $sort จะทับตัวแปรของหน้าแบบไม่มี error แจ้ง
 */

declare(strict_types=1);

$thSortColumn = $thSortColumn ?? '';
$thSortLabel  = $thSortLabel  ?? $thSortColumn;
$thSortSort   = $thSortSort   ?? '';
$thSortDir    = $thSortDir    ?? 'desc';

$thSortActive = $thSortSort === $thSortColumn;

$thSortQuery = $_GET;
$thSortQuery['sort'] = $thSortColumn;
$thSortQuery['dir']  = ($thSortActive && $thSortDir === 'asc') ? 'desc' : 'asc';

$thSortHref = url(current_path() . '?' . http_build_query($thSortQuery));
?>
<th<?php if ($thSortActive): ?> aria-sort="<?= e($thSortDir === 'asc' ? 'ascending' : 'descending') ?>"<?php endif; ?>>
    <a href="<?= e($thSortHref) ?>">
        <?= e($thSortLabel) ?>
        <?php if (!$thSortActive): ?>
            <?= icon('sort') ?>
        <?php elseif ($thSortDir === 'asc'): ?>
            <?= icon('sort-asc') ?>
        <?php else: ?>
            <?= icon('sort-desc') ?>
        <?php endif; ?>
    </a>
</th>

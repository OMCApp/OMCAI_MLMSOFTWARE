<?php
/**
 * เส้นทางนำทาง — บอกว่าหน้านี้อยู่ใต้อะไร และกดกลับขึ้นไปได้ทีละชั้น
 *
 * ‼️ มีตั้งแต่ 3.17.0 — ก่อนหน้านั้น components.md เขียนแค่ว่า "จำเป็นเมื่อลึกเกิน 2 ชั้น" แต่โครงไม่มีให้ใช้
 *    ของที่ไม่มีในโครง AI จะไม่สร้างเอง หรือสร้างขึ้นมาเองคนละแบบในแต่ละหน้า
 *
 * ปกติไม่ต้อง include เอง — page-head.php แสดงให้เมื่อ Controller ส่ง pageBreadcrumb มา
 *
 *   return $this->render('plans/edit', [
 *       'title'          => __('plan.edit'),
 *       'pageBreadcrumb' => [
 *           ['label' => __('plan.title'), 'url' => '/plans'],
 *           ['label' => $plan['name']],                // หน้านี้ — ไม่มี url
 *       ],
 *   ]);
 *
 * ตัวแปรที่รับ:
 *   $breadcrumbItems  [['label' => string, 'url' => ?string], ...] — น้อยกว่า 2 ขั้นไม่แสดง
 */

declare(strict_types=1);

$breadcrumbItems = $breadcrumbItems ?? [];

if (count($breadcrumbItems) >= 2):
    $breadcrumbLast = count($breadcrumbItems) - 1;
?>
<nav class="breadcrumb" aria-label="<?= e(__('ui.breadcrumb')) ?>">
    <ol>
        <?php foreach ($breadcrumbItems as $breadcrumbIndex => $breadcrumbItem): ?>
            <li>
                <?php if ($breadcrumbIndex < $breadcrumbLast && ($breadcrumbItem['url'] ?? '') !== ''): ?>
                    <a href="<?= e(url((string) $breadcrumbItem['url'])) ?>"><?= e((string) ($breadcrumbItem['label'] ?? '')) ?></a>
                <?php else: ?>
                    <span aria-current="<?= e($breadcrumbIndex === $breadcrumbLast ? 'page' : 'false') ?>"><?= e((string) ($breadcrumbItem['label'] ?? '')) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
<?php
endif;

// ล้างค่าเมื่อจบ — ขอบเขตตัวแปรร่วมกับหน้าที่เรียก (components.md ข้อ 1ก)
$breadcrumbItems = [];
unset($breadcrumbLast, $breadcrumbIndex, $breadcrumbItem);

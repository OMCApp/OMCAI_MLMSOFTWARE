<?php
/**
 * บล็อก comparison_table — ตารางเปรียบเทียบ (แผน / แพ็กเกจ / ทางเลือก)
 *
 * $block = [
 *   'eyebrow', 'title' (h2), 'lead'
 *   'columns'   => list<string>   หัวคอลัมน์ (ไม่รวมคอลัมน์ป้ายแถว)
 *   'rows'      => list<['label' => string, 'values' => list<bool|string>]>
 *                  true = มี (เครื่องหมายถูก) · false = ไม่มี · string = ข้อความ
 *   'highlight' => int   index ของคอลัมน์ที่เน้น — ไม่บังคับ
 *   'caption'   => string — ไม่บังคับ (ไม่ส่งใช้ title)
 * ]
 *
 * ‼️ ถูก/ไม่มี ต้องไม่ใช้สีหรือไอคอนอย่างเดียว — มีข้อความซ่อนให้ screen reader ("มี" / "ไม่มี")
 * ‼️ จอแคบเลื่อนแนวนอนในกรอบ .table-wrap — ตารางเปรียบเทียบยุบเป็นการ์ดแล้วอ่านเทียบไม่ได้
 */

declare(strict_types=1);

$compareBlock     = (array) ($block ?? []);
$compareColumns   = array_map('strval', (array) ($compareBlock['columns'] ?? []));
$compareLines     = array_values(array_filter((array) ($compareBlock['rows'] ?? []), 'is_array'));
$compareHighlight = isset($compareBlock['highlight']) ? (int) $compareBlock['highlight'] : -1;

if ($compareColumns === [] || $compareLines === []) {
    return;
}

$compareUid = (string) ($compareBlock['_uid'] ?? 'compare');
?>
<section class="section" aria-labelledby="<?= e($compareUid) ?>-title">
    <div class="container">
        <?php
        $sectionHeadEyebrow = (string) ($compareBlock['eyebrow'] ?? '');
        $sectionHeadTitle   = (string) ($compareBlock['title'] ?? '');
        $sectionHeadLead    = (string) ($compareBlock['lead'] ?? '');
        $sectionHeadId      = $compareUid . '-title';
        include __DIR__ . '/../section-head.php';
        ?>

        <div class="table-wrap compare-wrap reveal">
            <table class="compare-table">
                <caption class="visually-hidden"><?= e((string) ($compareBlock['caption'] ?? $compareBlock['title'] ?? '')) ?></caption>
                <thead>
                    <tr>
                        <td></td>
                        <?php foreach ($compareColumns as $compareIndex => $compareHeading): ?>
                            <th scope="col"<?php if ($compareIndex === $compareHighlight): ?> class="is-highlight"<?php endif; ?>><?= e($compareHeading) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compareLines as $compareLine): ?>
                        <tr>
                            <th scope="row"><?= e((string) ($compareLine['label'] ?? '')) ?></th>
                            <?php foreach (array_keys($compareColumns) as $compareIndex): ?>
                                <?php $compareCell = ((array) ($compareLine['values'] ?? []))[$compareIndex] ?? ''; ?>
                                <td<?php if ($compareIndex === $compareHighlight): ?> class="is-highlight"<?php endif; ?>>
                                    <?php if ($compareCell === true): ?>
                                        <span class="compare-yes"><?= icon('tick') ?><span class="visually-hidden"><?= e(__('site.yes')) ?></span></span>
                                    <?php elseif ($compareCell === false): ?>
                                        <span class="compare-no"><?= icon('close', 'icon-sm') ?><span class="visually-hidden"><?= e(__('site.no')) ?></span></span>
                                    <?php else: ?>
                                        <?= e((string) $compareCell) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

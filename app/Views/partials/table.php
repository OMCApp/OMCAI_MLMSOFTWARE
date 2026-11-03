<?php
/**
 * ตารางหน้ารายงาน — component ที่ซับซ้อนที่สุดและใช้บ่อยที่สุด
 *
 * ‼️ รับ "นิยามคอลัมน์" เป็นข้อมูล ไม่ใช่ HTML (components.md 1ก กฎ 3)
 *    รับ HTML แปลว่ามีจุดที่ output ไม่ผ่าน e() และเปิดช่องให้แต่ละหน้าฉีด markup เอง
 *    ซึ่งจะพาเรากลับไปที่ปัญหาเดิมคือตารางของแต่ละหน้าหน้าตาไม่ตรงกัน
 *
 * ‼️ บนมือถือยุบเป็นการ์ด ห้าม scroll แนวนอน
 *    หัวการ์ดคือคอลัมน์ที่ตั้ง primary ไว้ · คอลัมน์อื่นแสดงเป็น "ชื่อคอลัมน์ ↔ ค่า"
 *    CSS ทำงานจาก data-primary และ data-label ที่ไฟล์นี้ใส่ให้
 *
 * ‼️ ปุ่มในแถวเป็นไอคอนเท่านั้น ห้ามใช้คำ
 *    ใช้คำแล้วคอลัมน์กว้างจนตารางอ่านไม่ออก · ทุกปุ่มมี aria-label และ tooltip เสมอ
 *
 * ตัวแปรที่รับ:
 *   $tableColumns    นิยามคอลัมน์ (บังคับ)
 *   $tableRows       ข้อมูลที่จะแสดง (บังคับ)
 *   $tableSort       คอลัมน์ที่กำลังเรียงอยู่
 *   $tableDir        asc | desc
 *   $tableActions    ปุ่มท้ายแถว
 *   $tableFiltered   true = ว่างเพราะตัวกรอง ไม่ใช่เพราะไม่มีข้อมูลเลย
 *
 * นิยาม 1 คอลัมน์
 *   key      คีย์ในแถว (บังคับ)
 *   label    หัวคอลัมน์ (บังคับ) — ผ่าน __() มาแล้ว
 *   sort     ชื่อคอลัมน์สำหรับเรียง · ใส่แล้วหัวตารางกดเรียงได้
 *   primary  true = เป็นหัวการ์ดบนมือถือ · ควรมีคอลัมน์เดียว
 *   format   text (ค่าเริ่มต้น) · date · datetime · number · badge
 *   lang     prefix สำหรับแปลค่าที่เก็บเป็น key อังกฤษในฐานข้อมูล เช่น 'support.status.'
 *
 * นิยาม 1 ปุ่มท้ายแถว
 *   icon     ชื่อไอคอนจากชุดกลาง (บังคับ)
 *   label    ข้อความของ aria-label และ tooltip (บังคับ)
 *   url      ปลายทาง · ใช้ {key} แทนค่าจากแถวได้ เช่น '/users/{id}'
 *   confirm  ใส่ชื่อคีย์ = ปุ่มนี้ต้องยืนยันก่อน โดยใช้ค่าจากคีย์นั้นเป็นชื่อรายการ
 */

declare(strict_types=1);

$tableColumns  = $tableColumns  ?? [];
$tableRows     = $tableRows     ?? [];
$tableSort     = $tableSort     ?? '';
$tableDir      = $tableDir      ?? 'desc';
$tableActions  = $tableActions  ?? [];
$tableFiltered = $tableFiltered ?? false;

/* ‼️ ไม่มีข้อมูลต้องได้สถานะว่างที่บอกว่าทำอะไรต่อได้ ไม่ใช่ตารางเปล่า
   และต้องแยกให้ออกระหว่าง "ไม่มีข้อมูลเลย" กับ "มีข้อมูลแต่ตัวกรองไม่เจอ" */
if ($tableRows === []) {
    $filtered = $tableFiltered;
    include __DIR__ . '/empty-state.php';

    $tableColumns  = [];
    $tableRows     = [];
    $tableActions  = [];
    $tableFiltered = false;

    return;
}

/** แปลงค่าดิบของ 1 ช่อง ให้เป็นข้อความที่พร้อมแสดง */
$tableCell = static function (array $tableColumn, array $tableRow): string {
    $value = $tableRow[$tableColumn['key']] ?? null;

    if ($value === null || $value === '') {
        return '—';
    }

    // ค่าที่เก็บเป็น key อังกฤษในฐานข้อมูล ต้องแปลตอนแสดงเสมอ (i18n.md ข้อ 5)
    if (isset($tableColumn['lang'])) {
        return __($tableColumn['lang'] . $value);
    }

    $text = match ($tableColumn['format'] ?? 'text') {
        'date'     => format_date((string) $value),
        'datetime' => format_date((string) $value, true),
        'number'   => format_money($value, 0),
        default    => (string) $value,
    };

    /* ‼️ คอลัมน์ที่เก็บข้อความยาว (หมายเหตุ เนื้อเรื่องที่แจ้งเข้ามา) ต้องตัด
       ปล่อยเต็มแล้วคอลัมน์เดียวกินความกว้างทั้งตาราง คอลัมน์ที่เหลือถูกบีบจนอ่านไม่ออก
       ตัดที่นี่ ไม่ใช่ที่ Controller เพราะเป็นเรื่องการแสดงผล ไม่ใช่ business logic */
    if (isset($tableColumn['limit']) && mb_strlen($text) > $tableColumn['limit']) {
        $text = mb_substr($text, 0, $tableColumn['limit']) . '…';
    }

    return $text;
};
?>
<div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <?php foreach ($tableColumns as $tableColumn): ?>
                <?php if (isset($tableColumn['sort'])): ?>
                    <?php
                    $thSortColumn = $tableColumn['sort'];
                    $thSortLabel  = $tableColumn['label'];
                    $thSortSort   = $tableSort;
                    $thSortDir    = $tableDir;
                    include __DIR__ . '/th-sort.php';
                    ?>
                <?php else: ?>
                    <th class="<?= e(($tableColumn['format'] ?? '') === 'number' ? 'num' : '') ?>">
                        <?= e($tableColumn['label']) ?>
                    </th>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($tableActions !== []): ?>
                <th><span class="visually-hidden"><?= e(__('common.actions')) ?></span></th>
            <?php endif; ?>
        </tr>
        </thead>

        <tbody>
        <?php foreach ($tableRows as $tableRow): ?>
            <tr>
                <?php foreach ($tableColumns as $tableColumn): ?>
                    <td class="<?= e(($tableColumn['format'] ?? '') === 'number' ? 'num' : '') ?>"
                        <?php if (($tableColumn['primary'] ?? false) === true): ?>
                            data-primary
                        <?php else: ?>
                            data-label="<?= e($tableColumn['label']) ?>"
                        <?php endif; ?>>
                        <?php if (($tableColumn['format'] ?? '') === 'badge'): ?>
                            <?php /* ‼️ ประเภทกับสถานะต้องต่างกันด้วยรูปทรง (design.md 5.6)
                                      badgeKind: 'type' ได้เส้นขอบคนละแบบกับสถานะ */ ?>
                            <span class="badge <?= e(isset($tableColumn['badgeKind']) ? 'badge-' . $tableColumn['badgeKind'] : '') ?>"><?= e($tableCell($tableColumn, $tableRow)) ?></span>
                        <?php else: ?>
                            <?= e($tableCell($tableColumn, $tableRow)) ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>

                <?php if ($tableActions !== []): ?>
                    <td data-label="<?= e(__('common.actions')) ?>">
                        <div class="actions">
                            <?php foreach ($tableActions as $tableAction): ?>
                                <?php
                                // แทน {key} ด้วยค่าจากแถว — ไม่ให้หน้าประกอบ URL เองเพื่อไม่ให้หลุด url()
                                $tableHref = preg_replace_callback(
                                    '/\{([a-z0-9_]+)\}/i',
                                    static fn (array $m): string => rawurlencode((string) ($tableRow[$m[1]] ?? '')),
                                    (string) ($tableAction['url'] ?? '')
                                );
                                ?>
                                <?php if (isset($tableAction['confirm'])): ?>
                                    <?php
                                    $confirmUrl     = $tableHref;
                                    $confirmTitle   = __('confirm.delete_title', [
                                        'name' => (string) ($tableRow[$tableAction['confirm']] ?? ''),
                                    ]);
                                    $confirmBody    = __('confirm.delete_body');
                                    $confirmTrigger = $tableAction['label'];
                                    $confirmIcon    = $tableAction['icon'];
                                    include __DIR__ . '/confirm.php';
                                    ?>
                                <?php else: ?>
                                    <a class="btn btn-icon" href="<?= e(url($tableHref)) ?>"
                                       aria-label="<?= e($tableAction['label']) ?>"
                                       title="<?= e($tableAction['label']) ?>">
                                        <?= icon($tableAction['icon']) ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
/* ล้างค่าที่ไม่ควรค้าง — หน้าที่มีสองตารางจะได้ไม่หยิบคอลัมน์ของตารางก่อนหน้ามาใช้ */
$tableColumns  = [];
$tableRows     = [];
$tableActions  = [];
$tableFiltered = false;

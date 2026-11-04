<?php
/**
 * หน้ารายการของ CMS — ตัวเดียวทุก resource (ตัวกรอง + ตาราง + แบ่งหน้า)
 *
 * ตัวแปร (Controller ส่งมา):
 *   $listFilters  [['name','label','type' => select|text|date,'options','value'], ...] — ช่องค้นหา q ใส่ให้เสมอ
 *   $listQ        คำค้น
 *   $listColumns  นิยามคอลัมน์ของ partials/table.php
 *   $listActions  ปุ่มท้ายแถวของ partials/table.php
 *   $rows $total $page $perPage $sort $dir
 *   $listNotice   ข้อความแจ้งเหนือตาราง — ไม่บังคับ
 *   $listLinks    ลิงก์รองเหนือตาราง [['label','url','icon']] — ไม่บังคับ
 */

declare(strict_types=1);

$listFilters = (array) ($listFilters ?? []);
$listLinks   = (array) ($listLinks ?? []);
// ปุ่มที่เปลี่ยนข้อมูล (POST + CSRF) เช่น "สร้างไฟล์สำรอง" — ลิงก์ GET ห้ามเปลี่ยนข้อมูล
$listPostActions = (array) ($listPostActions ?? []);
$listSearch  = (bool) ($listSearch ?? true);
$hasFilter   = ($listQ ?? '') !== '' || array_filter(array_column($listFilters, 'value'), static fn ($v): bool => (string) $v !== '') !== [];
?>
<div class="stack">
    <?php if ($listSearch): ?>
    <form class="card toolbar" method="GET" action="<?= e(url(current_path())) ?>">
        <?php
        $fieldName  = 'q';
        $fieldLabel = __('common.search');
        $fieldType  = 'search';
        $fieldValue = (string) ($listQ ?? '');
        $fieldWidth = 'medium';
        include __DIR__ . '/../../../partials/field.php';

        foreach ($listFilters as $listFilter) {
            $fieldName    = (string) $listFilter['name'];
            $fieldLabel   = (string) $listFilter['label'];
            $fieldType    = (string) ($listFilter['type'] ?? 'select');
            $fieldValue   = (string) ($listFilter['value'] ?? '');
            $fieldOptions = (array) ($listFilter['options'] ?? []);
            $fieldWidth   = 'medium';
            include __DIR__ . '/../../../partials/field.php';
        }
        ?>
        <button type="submit" class="btn btn-primary"><?= e(__('common.search')) ?></button>
        <a class="btn" href="<?= e(url(current_path())) ?>"><?= e(__('common.clear')) ?></a>
    </form>
    <?php endif; ?>

    <div class="card stack">
        <?php if (($listNotice ?? '') !== '' || $listLinks !== [] || $listPostActions !== []): ?>
            <div class="row">
                <?php if (($listNotice ?? '') !== ''): ?>
                    <p class="muted small"><?= e((string) $listNotice) ?></p>
                <?php endif; ?>
                <?php foreach ($listPostActions as $listPost): ?>
                    <form method="POST" action="<?= e(url((string) $listPost['url'])) ?>" class="spacer">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary" data-submit-once>
                            <?= icon((string) ($listPost['icon'] ?? 'plus')) ?><?= e((string) $listPost['label']) ?>
                        </button>
                    </form>
                <?php endforeach; ?>
                <?php foreach ($listLinks as $listLink): ?>
                    <a class="btn btn-secondary spacer" href="<?= e(url((string) $listLink['url'])) ?>">
                        <?php if (isset($listLink['icon'])): ?><?= icon((string) $listLink['icon']) ?><?php endif; ?>
                        <?= e((string) $listLink['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
        $tableColumns  = (array) ($listColumns ?? []);
        $tableActions  = (array) ($listActions ?? []);
        $tableRows     = (array) ($rows ?? []);
        $tableSort     = (string) ($sort ?? '');
        $tableDir      = (string) ($dir ?? 'desc');
        $tableFiltered = $hasFilter;
        include __DIR__ . '/../../../partials/table.php';

        $paginationTotal   = (int) ($total ?? 0);
        $paginationPage    = (int) ($page ?? 1);
        $paginationPerPage = (int) ($perPage ?? 25);
        include __DIR__ . '/../../../partials/pagination.php';
        ?>
    </div>
</div>

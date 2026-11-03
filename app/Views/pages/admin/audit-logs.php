<?php
/**
 * หน้าดูประวัติการใช้งาน — สาธิตสเปคหน้ารายงานครบทุกข้อ
 *
 * ‼️ ค่าใน old_values/new_values มาจากผู้ใช้ ต้อง escape ก่อนแสดงเสมอ
 *    ลืมเมื่อไหร่กลายเป็น stored XSS ที่ยิงใส่คนที่มีสิทธิ์สูงสุดพอดี
 */

declare(strict_types=1);

$hasFilter = implode('', $filters) !== '';
?>
<div class="stack">

    <form class="card toolbar" method="GET" action="<?= e(url(current_path())) ?>">
        <?php
        $fieldName    = 'action';
        $fieldLabel   = __('common.filter');
        $fieldType    = 'select';
        $fieldValue   = $filters['action'];
        // ‼️ ค่าเป็น key อังกฤษ แต่สิ่งที่ผู้ใช้เห็นต้องเป็นคำแปล (i18n.md ข้อ 5)
        $fieldOptions = ['' => '—'];
        foreach ($actions as $actionKey) {
            $fieldOptions[$actionKey] = __('audit.action_' . $actionKey);
        }
        include __DIR__ . '/../../partials/field.php';
        ?>

        <?php
        $fieldName    = 'from';
        $fieldLabel   = __('date.from');
        $fieldType    = 'date';
        $fieldValue   = $filters['from'];
        $fieldOptions = [];
        include __DIR__ . '/../../partials/field.php';
        ?>

        <?php
        $fieldName  = 'to';
        $fieldLabel = __('date.to');
        $fieldType  = 'date';
        $fieldValue = $filters['to'];
        include __DIR__ . '/../../partials/field.php';
        ?>

        <?php
        $fieldName    = 'per';
        $fieldLabel   = __('common.per_page');
        $fieldType    = 'select';
        $fieldValue   = (string) $perPage;
        $fieldOptions = [10 => 10, 25 => 25, 50 => 50, 100 => 100];
        include __DIR__ . '/../../partials/field.php';
        ?>

        <button type="submit" class="btn btn-primary"><?= e(__('common.search')) ?></button>
        <a class="btn" href="<?= e(url(current_path())) ?>"><?= e(__('common.clear')) ?></a>

        <div class="row spacer" data-quick-range>
            <button type="button" class="btn" data-range="today"><?= e(__('date.today')) ?></button>
            <button type="button" class="btn" data-range="week"><?= e(__('date.this_week')) ?></button>
            <button type="button" class="btn" data-range="month"><?= e(__('date.this_month')) ?></button>
            <button type="button" class="btn" data-range="6months"><?= e(__('date.six_months')) ?></button>
            <button type="button" class="btn" data-range="year"><?= e(__('date.one_year')) ?></button>
        </div>
    </form>

    <div class="card stack">
        <div class="row">
            <a class="btn btn-secondary spacer"
               href="<?= e(url(current_path() . '?' . http_build_query($_GET + ['export' => 'csv']))) ?>">
                <?= icon('download') ?><?= e(__('common.export')) ?>
            </a>
        </div>

        <?php
        $tableColumns = [
            ['key' => 'created_at', 'label' => __('common.created_at'), 'sort' => 'created_at',
             'primary' => true, 'format' => 'datetime'],
            ['key' => 'actor',      'label' => __('auth.username')],
            ['key' => 'action',     'label' => __('common.actions'), 'sort' => 'action',
             'format' => 'badge', 'lang' => 'audit.action_'],
            ['key' => 'table_name', 'label' => __('common.view'),    'sort' => 'table_name'],
            ['key' => 'record_id',  'label' => 'ID',                 'format' => 'number'],
        ];
        $tableRows     = $rows;
        $tableSort     = $sort;
        $tableDir      = $dir;
        $tableFiltered = $hasFilter;
        include __DIR__ . '/../../partials/table.php';
        ?>

        <?php if ($rows !== []): ?>
            <?php
            $paginationTotal   = $total;
            $paginationPage    = $page;
            $paginationPerPage = $perPage;
            include __DIR__ . '/../../partials/pagination.php';
            ?>
        <?php endif; ?>
    </div>
</div>

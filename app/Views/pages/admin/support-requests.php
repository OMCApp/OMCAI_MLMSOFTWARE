<?php
/**
 * รายการเรื่องที่ผู้ใช้แจ้งเข้ามา
 */

declare(strict_types=1);

/* ‼️ ห้ามใช้ชื่อที่เป็นสัญญาของ partial ($fieldValue $fieldType) เป็นตัวแปรของหน้า
   field.php ถูก include ในฟอร์มกรองข้างล่าง แล้วมันเขียนทับค่าเหล่านี้
   เคยเกิดจริงในหน้านี้: ตัวกรอง "ประเภท" อ่านค่าที่เลือกอยู่จาก $fieldType
   ซึ่งตอนนั้นถูก field.php ตั้งเป็น 'select' ไปแล้ว — ตัวกรองจึงไม่เคยจำค่าที่ผู้ใช้เลือก */
$statusOptions = ['' => '—'];
foreach (\App\Models\SupportRequest::STATUSES as $statusKey) {
    $statusOptions[$statusKey] = __('support.status_' . $statusKey);
}

$typeOptions = ['' => '—'];
foreach (\App\Models\SupportRequest::TYPES as $typeKey) {
    $typeOptions[$typeKey] = __('support.type_' . ($typeKey === 'feature_request' ? 'feature' : $typeKey));
}
?>
<div class="stack">

    <form class="card toolbar" method="GET" action="<?= e(url(current_path())) ?>">
        <?php
        $fieldName    = 'status';
        $fieldLabel   = __('common.filter');
        $fieldType    = 'select';
        $fieldValue   = $status;
        $fieldOptions = $statusOptions;
        include __DIR__ . '/../../partials/field.php';
        ?>

        <?php
        $fieldName    = 'type';
        $fieldLabel   = __('support.title');
        $fieldType    = 'select';
        $fieldValue   = $type;
        $fieldOptions = $typeOptions;
        include __DIR__ . '/../../partials/field.php';
        ?>

        <button type="submit" class="btn btn-primary"><?= e(__('common.search')) ?></button>
        <a class="btn" href="<?= e(url(current_path())) ?>"><?= e(__('common.clear')) ?></a>
    </form>

    <div class="card stack">
        <?php
        $tableColumns = [
            ['key' => 'created_at', 'label' => __('common.created_at'), 'sort' => 'created_at',
             'primary' => true, 'format' => 'datetime'],
            ['key' => 'status',  'label' => __('support.title'), 'sort' => 'status',
             'format' => 'badge', 'lang' => 'support.status_'],
            ['key' => 'message', 'label' => __('common.view'), 'limit' => 80],
        ];
        $tableRows     = $rows;
        $tableSort     = $sort;
        $tableDir      = $dir;
        $tableFiltered = ($status !== '' || $type !== '');
        $tableActions  = [
            ['icon' => 'view', 'label' => __('common.view'), 'url' => '/admin/support-requests/{id}'],
        ];
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

    <?php if ($mostAsked !== []): ?>
        <section class="card stack">
            <h2><?= e(__('manual.title')) ?></h2>
            <p class="muted small"><?= e(__('support.type_how_to')) ?></p>
            <ul class="stack">
                <?php foreach ($mostAsked as $item): ?>
                    <li>
                        <a href="<?= e(url('/manual/' . $item['manual_slug'])) ?>"><?= e($item['manual_slug']) ?></a>
                        <span class="muted small"><?= e((string) $item['total']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</div>

<?php
/**
 * เมนูหลัก
 *
 * ‼️ พับแล้วต้องย่อ ไม่ใช่ปิดหาย — ตัดข้อความออกเหลือแต่ไอคอน ผู้ใช้ยังกดใช้งานได้
 * ‼️ การซ่อนเมนูไม่ใช่การเช็คสิทธิ์ — กันจริงที่ middleware เสมอ
 */

declare(strict_types=1);

use Core\Auth;

$here = current_path();

$links = [
    ['path' => '/dashboard', 'icon' => 'dashboard', 'label' => __('common.view'), 'roles' => []],
    ['path' => '/manual', 'icon' => 'book', 'label' => __('ui.manual'), 'roles' => []],
    ['path' => '/admin/audit-logs', 'icon' => 'history', 'label' => __('state.reference'), 'roles' => ['admin']],
    ['path' => '/admin/support-requests', 'icon' => 'inbox', 'label' => __('support.title'), 'roles' => ['admin']],
];
?>
<nav class="nav" aria-label="<?= e(__('ui.menu')) ?>">
    <?php foreach ($links as $link): ?>
        <?php if ($link['roles'] !== [] && !Auth::can(...$link['roles'])) {
            continue;
        } ?>
        <a href="<?= e(url($link['path'])) ?>"
           title="<?= e($link['label']) ?>"
           <?php if (str_starts_with($here, $link['path'])): ?>aria-current="page"<?php endif; ?>>
            <?= icon($link['icon']) ?>
            <span class="nav-label"><?= e($link['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

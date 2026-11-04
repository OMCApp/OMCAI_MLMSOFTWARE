<?php
/**
 * เมนูหลัก
 *
 * ‼️ พับแล้วต้องย่อ ไม่ใช่ปิดหาย — ตัดข้อความออกเหลือแต่ไอคอน ผู้ใช้ยังกดใช้งานได้
 * ‼️ การซ่อนเมนูไม่ใช่การเช็คสิทธิ์ — กันจริงที่ middleware เสมอ
 *    บทบาทของแต่ละลิงก์ต้องตรงกับ middleware ของกลุ่ม route ใน config/routes.php
 * ‼️ ตัวแปรขึ้นต้นด้วย nav — partial นี้ถูก include ใน layout ก่อน $content ชื่อกลางๆ จะทับเนื้อหาของหน้า
 *    (เนื้อหา = ContentRoleMiddleware::ROLES · lead = LeadRoleMiddleware::ROLES · ที่เหลือ admin)
 */

declare(strict_types=1);

use App\Middlewares\ContentRoleMiddleware;
use App\Middlewares\LeadRoleMiddleware;
use Core\Auth;

$here    = current_path();
$navContentRoles = ContentRoleMiddleware::ROLES;
$navLeadRoles    = LeadRoleMiddleware::ROLES;

$links = [
    ['path' => '/admin', 'icon' => 'dashboard', 'label' => __('cms.nav.dashboard'), 'roles' => []],
    ['path' => '/admin/pages', 'icon' => 'file', 'label' => __('cms.nav.pages'), 'roles' => $navContentRoles],
    ['path' => '/admin/posts', 'icon' => 'book', 'label' => __('cms.nav.posts'), 'roles' => $navContentRoles],
    ['path' => '/admin/categories', 'icon' => 'layers', 'label' => __('cms.nav.categories'), 'roles' => $navContentRoles],
    ['path' => '/admin/media', 'icon' => 'image', 'label' => __('cms.nav.media'), 'roles' => $navContentRoles],
    ['path' => '/admin/menus', 'icon' => 'menu', 'label' => __('cms.nav.menus'), 'roles' => $navContentRoles],
    ['path' => '/admin/redirects', 'icon' => 'link', 'label' => __('cms.nav.redirects'), 'roles' => $navContentRoles],
    ['path' => '/admin/not-found', 'icon' => 'warning', 'label' => __('cms.nav.not_found'), 'roles' => $navContentRoles],
    ['path' => '/admin/faqs', 'icon' => 'help', 'label' => __('cms.nav.faqs'), 'roles' => $navContentRoles],
    ['path' => '/admin/testimonials', 'icon' => 'quote', 'label' => __('cms.nav.testimonials'), 'roles' => $navContentRoles],
    ['path' => '/admin/case-studies', 'icon' => 'chart', 'label' => __('cms.nav.case_studies'), 'roles' => $navContentRoles],
    ['path' => '/admin/leads', 'icon' => 'inbox', 'label' => __('cms.nav.leads'), 'roles' => $navLeadRoles],
    ['path' => '/admin/users', 'icon' => 'users', 'label' => __('cms.nav.users'), 'roles' => ['admin']],
    ['path' => '/admin/settings', 'icon' => 'settings', 'label' => __('cms.nav.settings'), 'roles' => ['admin']],
    ['path' => '/admin/backups', 'icon' => 'download', 'label' => __('cms.nav.backups'), 'roles' => ['admin']],
    ['path' => '/admin/audit-logs', 'icon' => 'history', 'label' => __('state.reference'), 'roles' => ['admin']],
    ['path' => '/admin/support-requests', 'icon' => 'bug', 'label' => __('support.title'), 'roles' => ['admin']],
    ['path' => '/admin/manual', 'icon' => 'help', 'label' => __('ui.manual'), 'roles' => []],
];
?>
<nav class="nav" aria-label="<?= e(__('ui.menu')) ?>">
    <?php foreach ($links as $link): ?>
        <?php if ($link['roles'] !== [] && !Auth::can(...$link['roles'])) {
            continue;
        } ?>
        <a href="<?= e(url($link['path'])) ?>"
           title="<?= e($link['label']) ?>"
           <?php /* /admin คือหน้าแรกของหลังบ้าน — ทุกหน้าขึ้นต้นด้วย /admin จึงต้องเทียบตรงตัว */ ?>
           <?php if ($link['path'] === '/admin' ? $here === '/admin' : ($here === $link['path'] || str_starts_with($here, $link['path'] . '/'))): ?>aria-current="page"<?php endif; ?>>
            <?= icon($link['icon']) ?>
            <span class="nav-label"><?= e($link['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

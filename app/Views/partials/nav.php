<?php
/**
 * เมนูหลัก
 *
 * ‼️ พับแล้วต้องย่อ ไม่ใช่ปิดหาย — ตัดข้อความออกเหลือแต่ไอคอน ผู้ใช้ยังกดใช้งานได้
 * ‼️ การซ่อนเมนูไม่ใช่การเช็คสิทธิ์ — กันจริงที่ middleware เสมอ
 *    บทบาทของแต่ละลิงก์ต้องตรงกับ middleware ของกลุ่ม route ใน config/routes.php
 *    (เนื้อหา = ContentRoleMiddleware::ROLES · lead = LeadRoleMiddleware::ROLES · ที่เหลือ admin)
 */

declare(strict_types=1);

use App\Middlewares\ContentRoleMiddleware;
use App\Middlewares\LeadRoleMiddleware;
use Core\Auth;

$here    = current_path();
$content = ContentRoleMiddleware::ROLES;
$leads   = LeadRoleMiddleware::ROLES;

$links = [
    ['path' => '/admin', 'icon' => 'dashboard', 'label' => __('cms.nav.dashboard'), 'roles' => []],
    ['path' => '/admin/pages', 'icon' => 'file', 'label' => __('cms.nav.pages'), 'roles' => $content],
    ['path' => '/admin/posts', 'icon' => 'book', 'label' => __('cms.nav.posts'), 'roles' => $content],
    ['path' => '/admin/categories', 'icon' => 'layers', 'label' => __('cms.nav.categories'), 'roles' => $content],
    ['path' => '/admin/media', 'icon' => 'image', 'label' => __('cms.nav.media'), 'roles' => $content],
    ['path' => '/admin/menus', 'icon' => 'menu', 'label' => __('cms.nav.menus'), 'roles' => $content],
    ['path' => '/admin/redirects', 'icon' => 'link', 'label' => __('cms.nav.redirects'), 'roles' => $content],
    ['path' => '/admin/not-found', 'icon' => 'warning', 'label' => __('cms.nav.not_found'), 'roles' => $content],
    ['path' => '/admin/faqs', 'icon' => 'help', 'label' => __('cms.nav.faqs'), 'roles' => $content],
    ['path' => '/admin/testimonials', 'icon' => 'quote', 'label' => __('cms.nav.testimonials'), 'roles' => $content],
    ['path' => '/admin/case-studies', 'icon' => 'chart', 'label' => __('cms.nav.case_studies'), 'roles' => $content],
    ['path' => '/admin/leads', 'icon' => 'inbox', 'label' => __('cms.nav.leads'), 'roles' => $leads],
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

<?php
/**
 * เส้นทางทั้งหมดของระบบ + ผูก middleware ตามกลุ่ม
 *
 * ‼️ การเช็คสิทธิ์ต้องทำที่ middleware ของ route หรือกลุ่ม route เท่านั้น
 *    ห้ามเช็คกระจายในแต่ละ Controller เพราะจะมีจุดที่ลืมเช็คหลุดเสมอ
 *
 * ‼️ ประกาศเป็นเส้นทางภายในแอปเสมอ (/admin/login) — Router ตัด base path ให้เองก่อนเทียบ
 *
 * ‼️ ลำดับการจับ URL ของเว็บสาธารณะ (PLAN.md "Routing สาธารณะ" — ห้ามเปลี่ยนลำดับ)
 *    1. ไฟล์จริงใน public/ — web server เสิร์ฟเองก่อนถึงไฟล์นี้
 *    2. route ระบบ — ทุกอย่างในไฟล์นี้ (/_health และ /_migrate อยู่ใน public/index.php)
 *    3-9. trailing slash → redirects → บทความ → หมวด → บล็อก → หน้า → 404
 *         อยู่ใน fallback ท้ายไฟล์ (App\Services\SiteResolver) — ห้ามแตกออกมาเป็น route ที่นี่
 *
 * ‼️ หลังบ้านทั้งหมดอยู่ใต้ /admin — URL สาธารณะ (/support/, /contact/ ...) มาจากฐานข้อมูล
 *    route หลังบ้านที่อยู่นอก /admin จะไปทับ URL ของเว็บ
 *
 * ชื่อ URL ใช้ตัวพิมพ์เล็กและ kebab-case เท่านั้น (/order-history ไม่ใช่ /orderHistory)
 */

declare(strict_types=1);

use Core\Router;

/** @var Router $router */

$router->registerMiddleware([
    'auth'    => \App\Middlewares\AuthMiddleware::class,
    'guest'   => \App\Middlewares\GuestMiddleware::class,
    'admin'   => \App\Middlewares\RoleMiddleware::class,
    'options' => \App\Middlewares\OptionAccessMiddleware::class,
    'preview' => \App\Middlewares\PreviewMiddleware::class,
    'content' => \App\Middlewares\ContentRoleMiddleware::class,
    'leads'   => \App\Middlewares\LeadRoleMiddleware::class,
]);

// ---------------------------------------------------------------- ไฟล์สำหรับ crawler

$router->get('/sitemap.xml', 'SeoController@sitemapIndex');
// sitemap แยกภาษา: sitemap-pages.xml (ไทย) · sitemap-pages-en.xml · sitemap-posts-zh.xml …
$router->get('/sitemap-{section:[a-z]+(?:-[a-z]+)?}.xml', 'SeoController@sitemap');
$router->get('/robots.txt', 'SeoController@robots');
$router->get('/feed', 'SeoController@feed');
// feed ของภาษาอื่น (/en/feed/) — รายการภาษามาจาก config/locales.php · 404 ถ้าภาษานั้นยังไม่มีบทความ
$router->get('/{locale:' . \App\Services\LocaleService::prefixPattern() . '}/feed', 'SeoController@feed');

// ฟอร์ม lead ของเว็บสาธารณะ (ทุกภาษาส่งมาที่เดียว — ภาษาอ่านจาก path ของหน้าที่ส่ง)
// ‼️ CSRF ตรวจที่ public/index.php ก่อนถึงที่นี่ · honeypot + rate limit + validate ใน App\Services\LeadService
$router->post('/lead', 'LeadController@store');

// ‼️ หน้าดูตัวอย่าง design system ระหว่างที่ยังไม่มี CMS (Phase 1)
//    ประกาศเฉพาะ environment ที่ไม่ใช่ production — production ได้ 404 จาก Router เพราะ route ไม่มีอยู่
if (config('env', 'production') !== 'production') {
    $router->get('/_preview/home', 'PreviewController@home');
}

// ---------------------------------------------------------------- หลังบ้าน (/admin)

$router->group(['prefix' => '/admin'], function (Router $router): void {
    // คู่มือเข้าถึงได้แม้ยังไม่ล็อกอิน สำหรับบทความที่ไม่จำกัดสิทธิ์
    // เหตุผลเดียวกับที่ปุ่มแจ้งปัญหาต้องแสดงในหน้า login
    $router->get('/manual', 'ManualController@index');
    $router->get('/manual/{slug}', 'ManualController@show');

    // ผู้ใช้ที่เข้าระบบไม่ได้ก็ต้องแจ้งปัญหาได้
    $router->post('/support', 'SupportController@store');

    $router->post('/language', 'PreferenceController@language');
    $router->post('/theme', 'PreferenceController@theme');

    // ------------------------------------------------------------ ยังไม่ล็อกอิน

    $router->group(['middleware' => 'guest'], function (Router $router): void {
        $router->get('/login', 'AuthController@form');
        $router->post('/login', 'AuthController@login');
    });

    // ------------------------------------------------------------ ต้องล็อกอิน

    $router->group(['middleware' => 'auth'], function (Router $router): void {
        $router->post('/logout', 'AuthController@logout');
        $router->get('/', 'HomeController@dashboard');

        // ไฟล์แนบเสิร์ฟผ่าน Controller ที่ตรวจสิทธิ์ก่อนเสมอ
        // ‼️ หา path จาก id ในฐานข้อมูล ไม่ใช่รับ path จาก URL
        $router->get('/files/{id:\d+}', 'FileController@show');

        // รายการของ dropdown ที่ค้นจากฝั่ง server — แหล่งข้อมูลและสิทธิ์อยู่ใน config/options.php
        // ‼️ คีย์ต้องมีในทะเบียนเท่านั้น และสิทธิ์ของแต่ละแหล่งเช็คที่ middleware 'options'
        $router->get('/options/{key:[a-z][a-z0-9-]*}', 'OptionController@search', ['options']);

        // เปลี่ยนรหัสผ่านของตัวเอง — ทุกบทบาท · บัญชีที่ต้องเปลี่ยนรหัสถูก AuthMiddleware พามาที่นี่
        $router->get('/account/password', 'Admin\AccountController@form');
        $router->patch('/account/password', 'Admin\AccountController@update');
    });

    // ------------------------------------------------------------ เนื้อหา (admin + editor)
    // ‼️ ชุดบทบาทอยู่ที่ ContentRoleMiddleware ที่เดียว — sales ได้ 403

    $router->group(['middleware' => ['auth', 'content']], function (Router $router): void {
        $router->get('/pages', 'Admin\PageController@index');
        $router->get('/pages/create', 'Admin\PageController@create');
        $router->post('/pages', 'Admin\PageController@store');
        $router->get('/pages/{id:\d+}', 'Admin\PageController@edit');
        $router->patch('/pages/{id:\d+}', 'Admin\PageController@update');
        $router->delete('/pages/{id:\d+}', 'Admin\PageController@destroy');
        $router->post('/pages/{id:\d+}/translations', 'Admin\PageController@translate');
        $router->get('/pages/{id:\d+}/revisions/{rid:\d+}', 'Admin\PageController@revision');
        $router->post('/pages/{id:\d+}/revisions/{rid:\d+}/restore', 'Admin\PageController@restore');

        $router->get('/posts', 'Admin\PostController@index');
        $router->get('/posts/create', 'Admin\PostController@create');
        $router->post('/posts', 'Admin\PostController@store');
        $router->get('/posts/{id:\d+}', 'Admin\PostController@edit');
        $router->patch('/posts/{id:\d+}', 'Admin\PostController@update');
        $router->delete('/posts/{id:\d+}', 'Admin\PostController@destroy');
        $router->post('/posts/{id:\d+}/translations', 'Admin\PostController@translate');
        $router->get('/posts/{id:\d+}/revisions/{rid:\d+}', 'Admin\PostController@revision');
        $router->post('/posts/{id:\d+}/revisions/{rid:\d+}/restore', 'Admin\PostController@restore');

        $router->get('/categories', 'Admin\CategoryController@index');
        $router->get('/categories/create', 'Admin\CategoryController@create');
        $router->post('/categories', 'Admin\CategoryController@store');
        $router->get('/categories/{id:\d+}', 'Admin\CategoryController@edit');
        $router->patch('/categories/{id:\d+}', 'Admin\CategoryController@update');
        $router->delete('/categories/{id:\d+}', 'Admin\CategoryController@destroy');
        $router->post('/categories/{id:\d+}/translations', 'Admin\CategoryController@translate');

        $router->get('/media', 'Admin\MediaController@index');
        $router->post('/media', 'Admin\MediaController@store');
        $router->post('/media/import', 'Admin\MediaController@import');
        $router->get('/media/{id:\d+}', 'Admin\MediaController@edit');
        $router->patch('/media/{id:\d+}', 'Admin\MediaController@update');
        $router->delete('/media/{id:\d+}', 'Admin\MediaController@destroy');

        $router->get('/menus', 'Admin\MenuController@index');
        $router->post('/menus/import', 'Admin\MenuController@import');
        $router->post('/menus/items', 'Admin\MenuController@store');
        $router->get('/menus/items/{id:\d+}', 'Admin\MenuController@edit');
        $router->patch('/menus/items/{id:\d+}', 'Admin\MenuController@update');
        $router->delete('/menus/items/{id:\d+}', 'Admin\MenuController@destroy');
        $router->post('/menus/items/{id:\d+}/move', 'Admin\MenuController@move');

        $router->get('/redirects', 'Admin\RedirectController@index');
        $router->get('/redirects/create', 'Admin\RedirectController@create');
        $router->post('/redirects', 'Admin\RedirectController@store');
        $router->get('/redirects/{id:\d+}', 'Admin\RedirectController@edit');
        $router->patch('/redirects/{id:\d+}', 'Admin\RedirectController@update');
        $router->delete('/redirects/{id:\d+}', 'Admin\RedirectController@destroy');

        $router->get('/not-found', 'Admin\RedirectController@notFoundIndex');
        $router->delete('/not-found/{id:\d+}', 'Admin\RedirectController@notFoundDismiss');

        $router->get('/faqs', 'Admin\FaqController@index');
        $router->get('/faqs/create', 'Admin\FaqController@create');
        $router->post('/faqs', 'Admin\FaqController@store');
        $router->get('/faqs/{id:\d+}', 'Admin\FaqController@edit');
        $router->patch('/faqs/{id:\d+}', 'Admin\FaqController@update');
        $router->delete('/faqs/{id:\d+}', 'Admin\FaqController@destroy');

        $router->get('/testimonials', 'Admin\TestimonialController@index');
        $router->get('/testimonials/create', 'Admin\TestimonialController@create');
        $router->post('/testimonials', 'Admin\TestimonialController@store');
        $router->get('/testimonials/{id:\d+}', 'Admin\TestimonialController@edit');
        $router->patch('/testimonials/{id:\d+}', 'Admin\TestimonialController@update');
        $router->delete('/testimonials/{id:\d+}', 'Admin\TestimonialController@destroy');

        $router->get('/case-studies', 'Admin\CaseStudyController@index');
        $router->get('/case-studies/create', 'Admin\CaseStudyController@create');
        $router->post('/case-studies', 'Admin\CaseStudyController@store');
        $router->get('/case-studies/{id:\d+}', 'Admin\CaseStudyController@edit');
        $router->patch('/case-studies/{id:\d+}', 'Admin\CaseStudyController@update');
        $router->delete('/case-studies/{id:\d+}', 'Admin\CaseStudyController@destroy');
    });

    // ------------------------------------------------------------ lead (admin + sales)

    $router->group(['middleware' => ['auth', 'leads']], function (Router $router): void {
        $router->get('/leads', 'Admin\LeadController@index');
        $router->get('/leads/export', 'Admin\LeadController@export');
        $router->post('/leads/notify-retry', 'Admin\LeadController@retryNotify');
        $router->get('/leads/{id:\d+}', 'Admin\LeadController@show');
        $router->patch('/leads/{id:\d+}', 'Admin\LeadController@update');
        $router->post('/leads/{id:\d+}/notes', 'Admin\LeadController@note');
        $router->delete('/leads/{id:\d+}', 'Admin\LeadController@destroy');
    });

    // ------------------------------------------------------------ แอดมิน

    $router->group(['middleware' => ['auth', 'admin']], function (Router $router): void {
        $router->get('/audit-logs', 'Admin\AuditLogController@index');
        $router->get('/support-requests', 'Admin\SupportRequestController@index');
        $router->get('/support-requests/{id:\d+}', 'Admin\SupportRequestController@show');
        $router->patch('/support-requests/{id:\d+}', 'Admin\SupportRequestController@update');

        $router->get('/users', 'Admin\UserController@index');
        $router->get('/users/create', 'Admin\UserController@create');
        $router->post('/users', 'Admin\UserController@store');
        $router->get('/users/{id:\d+}', 'Admin\UserController@edit');
        $router->patch('/users/{id:\d+}', 'Admin\UserController@update');
        $router->delete('/users/{id:\d+}', 'Admin\UserController@destroy');
        $router->post('/users/{id:\d+}/password', 'Admin\UserController@password');
        $router->post('/users/{id:\d+}/devices', 'Admin\UserController@devices');

        $router->get('/settings', 'Admin\SettingController@edit');
        $router->patch('/settings', 'Admin\SettingController@update');
        $router->post('/settings/test-mail', 'Admin\SettingController@testMail');

        $router->get('/backups', 'Admin\BackupController@index');
        $router->post('/backups', 'Admin\BackupController@store');
        $router->get('/backups/{name:db-[0-9-]+\.sql(?:\.gz)?}', 'Admin\BackupController@download');
        $router->delete('/backups/{name:db-[0-9-]+\.sql(?:\.gz)?}', 'Admin\BackupController@destroy');
    });
});

// ---------------------------------------------------------------- เว็บสาธารณะ (ลำดับ 3-9)
//
// ‼️ 'preview' ไม่ได้กันใครออก — แค่ตัดสินว่า ?preview=1 ของคนนี้ได้ดู draft หรือไม่

$router->fallback('SiteController@resolve', ['preview']);

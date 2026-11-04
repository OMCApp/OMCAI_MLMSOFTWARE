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
    });

    // ------------------------------------------------------------ แอดมิน

    $router->group(['middleware' => ['auth', 'admin']], function (Router $router): void {
        $router->get('/audit-logs', 'Admin\AuditLogController@index');
        $router->get('/support-requests', 'Admin\SupportRequestController@index');
        $router->get('/support-requests/{id:\d+}', 'Admin\SupportRequestController@show');
        $router->patch('/support-requests/{id:\d+}', 'Admin\SupportRequestController@update');
    });
});

// ---------------------------------------------------------------- เว็บสาธารณะ (ลำดับ 3-9)
//
// ‼️ 'preview' ไม่ได้กันใครออก — แค่ตัดสินว่า ?preview=1 ของคนนี้ได้ดู draft หรือไม่

$router->fallback('SiteController@resolve', ['preview']);

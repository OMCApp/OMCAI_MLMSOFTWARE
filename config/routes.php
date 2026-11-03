<?php
/**
 * เส้นทางทั้งหมดของระบบ + ผูก middleware ตามกลุ่ม
 *
 * ‼️ การเช็คสิทธิ์ต้องทำที่ middleware ของ route หรือกลุ่ม route เท่านั้น
 *    ห้ามเช็คกระจายในแต่ละ Controller เพราะจะมีจุดที่ลืมเช็คหลุดเสมอ
 *
 * ‼️ ประกาศเป็นเส้นทางภายในแอปเสมอ (/login) — Router ตัด base path ให้เองก่อนเทียบ
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
]);

// ---------------------------------------------------------------- สาธารณะ

$router->get('/', 'HomeController@index');

// คู่มือเข้าถึงได้แม้ยังไม่ล็อกอิน สำหรับบทความที่ไม่จำกัดสิทธิ์
// เหตุผลเดียวกับที่ปุ่มแจ้งปัญหาต้องแสดงในหน้า login
$router->get('/manual', 'ManualController@index');
$router->get('/manual/{slug}', 'ManualController@show');

// ผู้ใช้ที่เข้าระบบไม่ได้ก็ต้องแจ้งปัญหาได้
$router->post('/support', 'SupportController@store');

$router->post('/language', 'PreferenceController@language');
$router->post('/theme', 'PreferenceController@theme');

// ---------------------------------------------------------------- ยังไม่ล็อกอิน

$router->group(['middleware' => 'guest'], function (Router $router): void {
    $router->get('/login', 'AuthController@form');
    $router->post('/login', 'AuthController@login');
});

// ---------------------------------------------------------------- ต้องล็อกอิน

$router->group(['middleware' => 'auth'], function (Router $router): void {
    $router->post('/logout', 'AuthController@logout');
    $router->get('/dashboard', 'HomeController@dashboard');

    // ไฟล์แนบเสิร์ฟผ่าน Controller ที่ตรวจสิทธิ์ก่อนเสมอ
    // ‼️ หา path จาก id ในฐานข้อมูล ไม่ใช่รับ path จาก URL
    $router->get('/files/{id:\d+}', 'FileController@show');

    // รายการของ dropdown ที่ค้นจากฝั่ง server — แหล่งข้อมูลและสิทธิ์อยู่ใน config/options.php
    // ‼️ คีย์ต้องมีในทะเบียนเท่านั้น และสิทธิ์ของแต่ละแหล่งเช็คที่ middleware 'options'
    $router->get('/options/{key:[a-z][a-z0-9-]*}', 'OptionController@search', ['options']);
});

// ---------------------------------------------------------------- แอดมิน

$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function (Router $router): void {
    $router->get('/audit-logs', 'Admin\AuditLogController@index');
    $router->get('/support-requests', 'Admin\SupportRequestController@index');
    $router->get('/support-requests/{id:\d+}', 'Admin\SupportRequestController@show');
    $router->patch('/support-requests/{id:\d+}', 'Admin\SupportRequestController@update');
});

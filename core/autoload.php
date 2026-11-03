<?php
/**
 * ตัวโหลดคลาสของระบบ — แทนที่ vendor/autoload.php ของ composer
 *
 * ‼️ ทำไมไม่ใช้ composer
 *    โปรเจคนี้ไม่มี library สักตัว (`require` มีแค่ `php >= 8.1`)
 *    composer จึงถูกติดตั้งมาเพื่อทำงานเดียวคือ map namespace → โฟลเดอร์
 *    ซึ่งเขียนเองได้ในไม่กี่บรรทัด
 *
 *    แลกมาด้วยการที่ทุกคนต้องมี composer บนเครื่องก่อนจะเปิดหน้าแรกได้
 *    และ clone มาแล้วใช้ไม่ได้จนกว่าจะรัน `composer install`
 *    ซึ่งขัดกับเหตุผลที่เลือก PHP native ตั้งแต่แรก — ให้เบาและ deploy ผ่าน FTP
 *    ได้โดยไม่ต้องมี CLI บนเซิร์ฟเวอร์
 *
 * ‼️ วันที่ต้องเพิ่ม library จริง ให้เอา composer กลับมาแล้วเรียก vendor/autoload.php
 *    เพิ่มต่อจากไฟล์นี้ ไม่ต้องลบไฟล์นี้ทิ้ง — spl_autoload_register ต่อกันได้หลายตัว
 *    (และการเพิ่ม dependency ต้องขออนุญาตผู้ใช้ก่อนทุกครั้งตามกฎเหล็กข้อ 2)
 */

declare(strict_types=1);

/** namespace → โฟลเดอร์ · ต้องตรงกับที่ rule.md ข้อ 7 ระบุไว้ */
const PSR4_MAP = [
    'App\\'  => __DIR__ . '/../app/',
    'Core\\' => __DIR__ . '/../core/',
];

spl_autoload_register(static function (string $class): void {
    foreach (PSR4_MAP as $prefix => $dir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        // App\Controllers\HomeController → app/Controllers/HomeController.php
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $path     = $dir . $relative . '.php';

        if (is_file($path)) {
            require $path;
        }

        /* ‼️ ห้าม return หรือ throw ถ้าไฟล์ไม่มี
           autoloader ตัวอื่นที่ลงทะเบียนไว้ต้องได้โอกาสลองต่อ
           และ class_exists() ต้องคืน false ได้ตามปกติ ไม่ใช่ระเบิด */
        return;
    }
});

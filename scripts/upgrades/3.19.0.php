<?php
/**
 * ขั้นอัปเกรดของ 3.19.0 — dropdown ที่ค้นได้
 *
 * ไฟล์ใหม่คัดลอกให้ได้ ส่วนไฟล์ที่โปรเจคแก้เองเป็นปกติ (field.php · app.js · app.css · lang · routes)
 * ต้องรวมเอง เพราะทับแล้วงานของโปรเจคหาย
 *
 * รูปแบบของไฟล์อยู่ที่หัวไฟล์ scripts/upgrades/3.9.0.php
 */

declare(strict_types=1);

$standardRoot = dirname(__DIR__, 2);

/** คัดลอกไฟล์ใหม่ที่โปรเจคยังไม่มี — มีอยู่แล้วไม่ทับ */
$copyNew = static function (string $relative) use ($standardRoot): callable {
    return static function (string $root) use ($standardRoot, $relative): array {
        $target = $root . '/' . $relative;

        if (is_file($target)) {
            return ['ok', 'มีอยู่แล้ว'];
        }

        $source = $standardRoot . '/' . $relative;

        if (!is_file($source) || !is_dir(dirname($target))) {
            return ['manual', 'คัดลอก ' . $relative . ' จาก repo มาตรฐาน'];
        }

        copy($source, $target);

        return ['done', 'คัดลอก ' . $relative . ' แล้ว'];
    };
};

return [
    'option-registry' => [
        'title' => 'ทะเบียนแหล่งข้อมูล config/options.php',
        'run'   => $copyNew('config/options.php'),
    ],

    'option-model' => [
        'title' => 'app/Models/Option.php',
        'run'   => $copyNew('app/Models/Option.php'),
    ],

    'option-service' => [
        'title' => 'app/Services/OptionService.php',
        'run'   => $copyNew('app/Services/OptionService.php'),
    ],

    'option-middleware' => [
        'title' => 'app/Middlewares/OptionAccessMiddleware.php',
        'run'   => $copyNew('app/Middlewares/OptionAccessMiddleware.php'),
    ],

    'option-controller' => [
        'title' => 'app/Controllers/OptionController.php',
        'run'   => $copyNew('app/Controllers/OptionController.php'),
    ],

    'option-route' => [
        'title' => 'route /options/{key} และ middleware "options"',
        'run'   => static function (string $root): array {
            $file = $root . '/config/routes.php';

            if (!is_file($file)) {
                return ['manual', 'ไม่พบ config/routes.php'];
            }

            $code = (string) file_get_contents($file);

            if (str_contains($code, "'/options/")) {
                return ['ok', 'มีอยู่แล้ว'];
            }

            return ['manual',
                "เพิ่มใน config/routes.php — ในกลุ่ม middleware 'auth': "
                . "\$router->get('/options/{key:[a-z][a-z0-9-]*}', 'OptionController@search', ['options']); "
                . "และลงทะเบียน 'options' => \\App\\Middlewares\\OptionAccessMiddleware::class "
                . '· ไม่มี route นี้ dropdown ที่ค้นจากฝั่ง server จะได้ 404 ทุกครั้ง'];
        },
    ],

    'field-search' => [
        'title' => 'field.php รองรับ $fieldSearch และ $fieldSource',
        'run'   => static function (string $root): array {
            $file = $root . '/app/Views/partials/field.php';

            if (!is_file($file)) {
                return ['manual', 'ไม่พบ app/Views/partials/field.php'];
            }

            return str_contains((string) file_get_contents($file), 'fieldSource')
                ? ['ok', 'รองรับแล้ว']
                : ['manual',
                    'รวมส่วน $fieldSearch / $fieldSource ของ app/Views/partials/field.php จาก repo มาตรฐาน '
                    . '(รวมบรรทัดล้างค่าท้ายไฟล์) — ไม่ทับให้เพราะ field.php ของโปรเจคมักถูกปรับตามดีไซน์ไว้แล้ว '
                    . '· ยังไม่รวม = dropdown ที่ยาวเกิน 8 รายการยังเป็น select ธรรมดาทุกหน้า'];
        },
    ],

    'combobox-assets' => [
        'title' => 'combobox ใน app.js · สไตล์ใน app.css · คำแปล 2 คำ',
        'run'   => static function (string $root): array {
            $missing = [];

            $needs = [
                'public/assets/js/app.js'    => 'select[data-search]',
                'public/assets/css/app.css'  => '.combobox-list',
                'app/Views/partials/js-lang.php' => 'data-type-more',
            ];

            foreach ($needs as $relative => $needle) {
                $path = $root . '/' . $relative;

                if (!is_file($path) || !str_contains((string) file_get_contents($path), $needle)) {
                    $missing[] = $relative;
                }
            }

            foreach (glob($root . '/lang/*.php') ?: [] as $file) {
                if (!str_contains((string) file_get_contents($file), "'load_more'")) {
                    $missing[] = 'lang/' . basename($file);
                }
            }

            if ($missing === []) {
                return ['ok', 'มีครบ'];
            }

            return ['manual',
                'คัดลอกส่วนของ combobox จาก repo มาตรฐานเข้า ' . implode(' · ', array_unique($missing))
                . " — บล็อกท้าย app.js (select[data-search]) · บล็อก .combobox ใน app.css · "
                . "คำแปล ui.load_more และ ui.type_more พร้อม data-load-more / data-type-more ใน js-lang.php"];
        },
    ],

    'long-dropdowns' => [
        'title' => 'dropdown เดิมที่ยาวเกินเกณฑ์',
        'run'   => static function (string $root): array {
            $pages = [];

            foreach (glob($root . '/app/Views/pages/**/*.php') ?: [] as $file) {
                $content = (string) file_get_contents($file);

                // นับ <option> ต่อไฟล์แบบหยาบๆ พอให้รู้ว่าควรไปดูหน้าไหนก่อน
                if (substr_count($content, '<option') > 8) {
                    $pages[] = str_replace($root . '/', '', str_replace('\\', '/', $file));
                }
            }

            if ($pages === []) {
                return ['ok', 'ไม่พบหน้าที่เขียน option ยาวไว้ตรงในหน้า'];
            }

            return ['manual',
                'หน้าที่เขียน <option> ไว้ในหน้าเกิน 8 รายการ: ' . implode(' · ', $pages)
                . ' — ย้ายไปใช้ partials/field.php และดูเกณฑ์ที่ standard/components.md ข้อ 3ก '
                . '(เกิน 50 รายการต้องค้นจากฝั่ง server ผ่าน config/options.php)'];
        },
    ],
];

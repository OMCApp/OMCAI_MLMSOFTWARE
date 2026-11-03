<?php
/**
 * ขั้นอัปเกรดของ 3.17.0 — ช่องที่โครงเพิ่มให้แบบของผู้ใช้ (ภาพบนหัวเพจ · breadcrumb · หน้าแบบ split)
 *
 * ของเหล่านี้อยู่ใน app/ · public/ · lang/ · project/ ซึ่ง updater ไม่ทับ เพราะโปรเจคแก้ในนั้นเป็นปกติ
 * ไฟล์ใหม่ที่โปรเจคยังไม่มี (breadcrumb.php) คัดลอกให้ได้ · ไฟล์ที่มีอยู่แล้วต้องรวมเอง
 *
 * รูปแบบของไฟล์อยู่ที่หัวไฟล์ scripts/upgrades/3.9.0.php
 */

declare(strict_types=1);

// ไฟล์นี้ถูกรันจาก repo มาตรฐานที่ดึงมา — ต้นฉบับของโครงอยู่ที่นั่น
$standardRoot = dirname(__DIR__, 2);

return [
    'breadcrumb-partial' => [
        'title' => 'partials/breadcrumb.php',
        'run'   => static function (string $root) use ($standardRoot): array {
            $target = $root . '/app/Views/partials/breadcrumb.php';

            if (is_file($target)) {
                return ['ok', 'มีอยู่แล้ว'];
            }

            $source = $standardRoot . '/app/Views/partials/breadcrumb.php';

            if (!is_file($source) || !is_dir(dirname($target))) {
                return ['manual', 'คัดลอก app/Views/partials/breadcrumb.php จาก repo มาตรฐาน'];
            }

            copy($source, $target);

            return ['done', 'คัดลอก app/Views/partials/breadcrumb.php แล้ว — ใช้ได้เมื่อหัวเพจรองรับ pageBreadcrumb (ขั้นถัดไป)'];
        },
    ],

    'page-head-slots' => [
        'title' => 'หัวเพจรองรับ pageMedia และ pageBreadcrumb',
        'run'   => static function (string $root): array {
            $file = $root . '/app/Views/partials/page-head.php';

            if (!is_file($file)) {
                return ['manual', 'ไม่พบ app/Views/partials/page-head.php — คัดลอกจาก repo มาตรฐาน'];
            }

            $code = (string) file_get_contents($file);

            if (str_contains($code, 'pageMedia') && str_contains($code, 'pageBreadcrumb')) {
                return ['ok', 'รองรับแล้ว'];
            }

            return ['manual',
                'รวมส่วน $pageMedia และ $pageBreadcrumb จาก app/Views/partials/page-head.php ของ repo มาตรฐานเข้าไฟล์ของโปรเจค '
                . '— ไม่ทับให้ เพราะหัวเพจของโปรเจคอาจถูกปรับตามแบบไว้แล้ว · ถ้ามีคอมเมนต์ที่ห้ามภาพบนหัวเพจ ให้ลบออก (standard/design.md ข้อ 5.0)'];
        },
    ],

    'design-tokens' => [
        'title' => 'token layout.asideWidth · pageMediaWidth · pageMediaHeight',
        'run'   => static function (string $root): array {
            $file = $root . '/project/DESIGN.md';

            if (!is_file($file)) {
                return ['ok', 'ยังไม่มี DESIGN.md'];
            }

            $text    = (string) file_get_contents($file);
            $missing = array_values(array_filter(['asideWidth', 'pageMediaWidth', 'pageMediaHeight'],
                static fn (string $key): bool => preg_match('/^\s+' . $key . ':/m', $text) !== 1));

            if ($missing === []) {
                return ['ok', 'มีครบ'];
            }

            return ['manual',
                'เพิ่มใต้ layout: ใน project/DESIGN.md — ' . implode(' · ', array_map(
                    static fn (string $key): string => $key . ': ' . ['asideWidth' => '320px', 'pageMediaWidth' => '360px', 'pageMediaHeight' => '200px'][$key],
                    $missing
                )) . ' แล้วรัน php scripts/design-sync.php · ไม่มี token เหล่านี้ หน้า split และภาพบนหัวเพจจะไม่มีขนาด'];
        },
    ],

    'css-blocks' => [
        'title' => 'สไตล์ของ split · ภาพบนหัวเพจ · breadcrumb ใน app.css',
        'run'   => static function (string $root): array {
            $file = $root . '/public/assets/css/app.css';

            if (!is_file($file)) {
                return ['manual', 'ไม่พบ public/assets/css/app.css'];
            }

            $css     = (string) file_get_contents($file);
            $missing = array_values(array_filter(['.split-layout', '.page-media', '.breadcrumb'],
                static fn (string $class): bool => !str_contains($css, $class)));

            if ($missing === []) {
                return ['ok', 'มีครบ'];
            }

            return ['manual',
                'คัดลอกบล็อก ' . implode(' · ', $missing) . ' จาก public/assets/css/app.css ของ repo มาตรฐาน (ชั้น layout) '
                . '— design-sync.php เขียนแค่ส่วน token จึงไม่เพิ่มให้'];
        },
    ],

    'lang-breadcrumb' => [
        'title' => 'คำแปล ui.breadcrumb',
        'run'   => static function (string $root): array {
            $missing = [];

            foreach (glob($root . '/lang/*.php') ?: [] as $file) {
                if (!str_contains((string) file_get_contents($file), "'breadcrumb'")) {
                    $missing[] = basename($file);
                }
            }

            if ($missing === []) {
                return ['ok', 'มีครบ'];
            }

            return ['manual',
                "เพิ่ม 'breadcrumb' ในกลุ่ม ui ของ " . implode(' · ', $missing)
                . " (th: 'เส้นทางของหน้านี้' · en: 'Breadcrumb') — ป้ายสำหรับ screen reader"];
        },
    ],
];

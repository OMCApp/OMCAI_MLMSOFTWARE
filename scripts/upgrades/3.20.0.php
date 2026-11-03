<?php
/**
 * ขั้นอัปเกรดของ 3.20.0 — checkbox · radio · select ไม่ใช่ของ OS อีกต่อไป
 *
 * ทุกอย่างอยู่ในไฟล์ที่โปรเจคแก้เองเป็นปกติ (app.css · app.js · field.php · DESIGN.md)
 * จึงทับให้ไม่ได้ ต้องบอกทีละข้อว่าต้องรวมอะไร
 *
 * รูปแบบของไฟล์อยู่ที่หัวไฟล์ scripts/upgrades/3.9.0.php
 */

declare(strict_types=1);

/** ไฟล์นี้มีข้อความนี้อยู่หรือยัง */
$has = static fn (string $root, string $relative, string $needle): bool
    => is_file($root . '/' . $relative)
        && str_contains((string) file_get_contents($root . '/' . $relative), $needle);

return [
    'choice-styles' => [
        'title' => 'สไตล์ของ checkbox และ radio ใน app.css',
        'run'   => static function (string $root) use ($has): array {
            $missing = array_values(array_filter(
                ['input[type="checkbox"]', 'input[type="radio"]', '.choice-group'],
                static fn (string $needle): bool => !$has($root, 'public/assets/css/app.css', $needle)
            ));

            if ($missing === []) {
                return ['ok', 'มีครบ'];
            }

            return ['manual',
                'คัดลอกบล็อก "ติ๊กถูก / ตัวเลือกเดียว" จาก public/assets/css/app.css ของ repo มาตรฐาน (ชั้น components) '
                . '— ยังไม่มี = checkbox กับ radio ของทุกหน้ายังเป็นกล่องสีฟ้าของ OS ที่ไม่ตามธีมและโหมดมืด'];
        },
    ],

    'choice-tokens' => [
        'title' => 'token component.choice ใน DESIGN.md',
        'run'   => static function (string $root): array {
            $file = $root . '/project/DESIGN.md';

            if (!is_file($file)) {
                return ['ok', 'ยังไม่มี DESIGN.md'];
            }

            if (preg_match('/^\s+choice:/m', (string) file_get_contents($file)) === 1) {
                return ['ok', 'มีแล้ว'];
            }

            return ['manual',
                'เพิ่มใต้ component: ใน project/DESIGN.md — choice: { size: 20px · mark: 10px · arrowWide: 10px · arrowTall: 6px } '
                . 'แล้วรัน php scripts/design-sync.php · ไม่มี token เหล่านี้ กล่องติ๊กกับลูกศรจะไม่มีขนาด'];
        },
    ],

    'select-wrapped' => [
        'title' => 'app.js ครอบ <select> ทุกตัวเป็น listbox ของระบบ',
        'run'   => static function (string $root) use ($has): array {
            if ($has($root, 'public/assets/js/app.js', 'select:not([data-native])')) {
                return ['ok', 'ครอบแล้ว'];
            }

            $old = $has($root, 'public/assets/js/app.js', "select[data-search]");

            return ['manual',
                ($old
                    ? 'เปลี่ยนตัวเลือก select[data-search] เป็น select:not([data-native]) ในบล็อก combobox ของ public/assets/js/app.js '
                      . 'และรวมส่วนที่เพิ่มของ 3.20.0 (readOnly เมื่อค้นไม่ได้ · พิมพ์เพื่อกระโดด · data-open) '
                    : 'คัดลอกบล็อก combobox ท้าย public/assets/js/app.js จาก repo มาตรฐาน ')
                . '· ยังไม่ทำ = รายการที่กางออกมายังเป็นแถบน้ำเงินของ OS ทุกหน้า'];
        },
    ],

    'field-choice-types' => [
        'title' => 'field.php รองรับ checkbox · radio · checkboxes',
        'run'   => static function (string $root) use ($has): array {
            if (!is_file($root . '/app/Views/partials/field.php')) {
                return ['manual', 'ไม่พบ app/Views/partials/field.php'];
            }

            return $has($root, 'app/Views/partials/field.php', 'choice-group')
                ? ['ok', 'รองรับแล้ว']
                : ['manual',
                    'รวมกิ่ง checkbox / radio / checkboxes ของ app/Views/partials/field.php จาก repo มาตรฐาน '
                    . '(รวมตัวแปร $fieldChecked และบรรทัดล้างค่าท้ายไฟล์) — ไม่ทับให้เพราะ field.php ของโปรเจคมักถูกปรับไว้แล้ว'];
        },
    ],

    'inline-choices' => [
        'title' => 'หน้าที่เขียน checkbox หรือ radio เองไว้ในหน้า',
        'run'   => static function (string $root): array {
            $pages = [];

            foreach (glob($root . '/app/Views/pages/**/*.php') ?: [] as $file) {
                $content = (string) file_get_contents($file);

                if (preg_match('/<input[^>]*type=["\'](?:checkbox|radio)["\']/', $content) === 1) {
                    $pages[] = str_replace($root . '/', '', str_replace('\\', '/', $file));
                }
            }

            if ($pages === []) {
                return ['ok', 'ไม่พบ'];
            }

            return ['manual',
                'หน้าที่เขียน checkbox/radio เองไว้ในหน้า: ' . implode(' · ', $pages)
                . ' — ย้ายไปใช้ partials/field.php ($fieldType = checkbox / radio / checkboxes) '
                . 'ของที่เขียนเองไว้จะไม่ได้สไตล์ของกลุ่มตัวเลือกและพื้นที่กด 44px (กฎเหล็กข้อ 18)'];
        },
    ],
];

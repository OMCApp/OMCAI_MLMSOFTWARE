<?php
/**
 * ขั้นอัปเกรดของ 3.15.0 — scripts/ ตกเป็นของมาตรฐาน ถูกทับทุกครั้งที่อัปเดต
 *
 * รูปแบบของไฟล์อยู่ที่หัวไฟล์ scripts/upgrades/3.9.0.php
 */

declare(strict_types=1);

return [
    'project-scripts' => [
        'title' => 'สคริปต์ของโปรเจคที่วางไว้ใน scripts/ ต้องย้ายออก',
        'run'   => static function (string $root): array {
            // updater โหลดคลาสนี้จากไฟล์เดียวกันไว้แล้ว require_once จึงไม่โหลดซ้ำ
            require_once dirname(__DIR__) . '/lib/Manifest.php';

            // ขั้นนี้รันหลังทับไฟล์ ลายเซ็นในโปรเจคจึงเป็นของต้นฉบับแล้ว
            // ไฟล์ใน scripts/ ที่ไม่มีในลายเซ็น = ของที่โปรเจควางไว้เอง
            $sealed = Manifest::read($root . '/' . Manifest::FILE);
            $extra  = [];

            foreach (array_keys(Manifest::build($root)) as $path) {
                if (str_starts_with($path, 'scripts/') && !isset($sealed[$path])) {
                    $extra[] = $path;
                }
            }

            if ($extra === []) {
                return ['ok', 'ไม่มีสคริปต์ของโปรเจคใน scripts/'];
            }

            return ['manual',
                'ย้าย ' . implode(' · ', $extra) . ' ออกจาก scripts/ (เช่นไปไว้ที่ tools/) แล้วแก้ที่ที่เรียกใช้ '
                . '— ตั้งแต่ 3.15.0 scripts/ ถูกทับทุกครั้งที่อัปเดต และ check.php จะทักจนกว่าจะย้าย'];
        },
    ],
];

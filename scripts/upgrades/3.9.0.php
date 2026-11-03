<?php
/**
 * ขั้นอัปเกรดของ 3.9.0 — รูปแบบเมนูกับโหมดเริ่มต้นย้ายจาก .env ไปอยู่ใน project/DESIGN.md ที่เดียว
 *
 * ‼️ รูปแบบของไฟล์ใน scripts/upgrades/ ทุกตัว
 *    · ชื่อไฟล์คือเวอร์ชันที่ขั้นนี้เกิด — updater รันเมื่อโปรเจคข้ามเวอร์ชันนั้น (เก่ากว่า < เวอร์ชันนี้ ≤ ใหม่)
 *    · คืน array ของขั้น · ขั้นละ title + run(string $root): array{0: string, 1: string}
 *        'done'   แก้ให้แล้ว
 *        'manual' ต้องมีคนแก้ — ข้อความต้องบอกว่าตรงไหน และทำยังไง
 *        'ok'     โปรเจคนี้ไม่มีอะไรต้องทำ
 *    · ต้องรันซ้ำได้โดยไม่พัง และห้ามพิมพ์ค่าลับ — .env ให้ดูแค่ชื่อตัวแปร
 *    · แก้โค้ดของโปรเจคได้เฉพาะที่มั่นใจว่าไม่มีใครแก้ต่อจากโครงเดิม ไม่แน่ใจให้คืน 'manual'
 *
 * check.php (docs/upgrade-coverage) บังคับว่าเวอร์ชันตั้งแต่ 3.15.0 ที่มี ⚠️ ใน CHANGELOG ต้องมีไฟล์นี้
 */

declare(strict_types=1);

return [
    'env-nav-style' => [
        'title' => 'APP_NAV_STYLE และ APP_DEFAULT_THEME ใน .env ไม่ถูกอ่านแล้ว',
        'run'   => static function (string $root): array {
            $env = $root . '/.env';

            if (!is_file($env)) {
                return ['ok', 'ไม่มี .env บนเครื่องนี้'];
            }

            $found = [];

            foreach (file($env) ?: [] as $line) {
                if (preg_match('/^\s*(APP_NAV_STYLE|APP_DEFAULT_THEME)\s*=/', $line, $m) === 1) {
                    $found[$m[1]] = true;
                }
            }

            if ($found === []) {
                return ['ok', 'ไม่มีตัวแปรที่เลิกใช้'];
            }

            return ['manual',
                'ลบ ' . implode(' · ', array_keys($found)) . ' ออกจาก .env ของทุกเครื่องและบน server '
                . 'แล้วกำหนดใน project/DESIGN.md แทน (layout.nav · mode.default) จากนั้นรัน php scripts/design-sync.php '
                . '— ค่าที่ค้างใน .env ไม่ถูกอ่านแล้ว จึงไม่มีอะไรเตือนว่ามันไม่มีผล'];
        },
    ],

    'config-nav-style' => [
        'title' => "config('ui.nav_style') เปลี่ยนชื่อเป็น config('ui.nav')",
        'run'   => static function (string $root): array {
            $hits = [];

            foreach (['app', 'core', 'config'] as $dir) {
                if (!is_dir($root . '/' . $dir)) {
                    continue;
                }

                $items = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS)
                );

                foreach ($items as $item) {
                    if ($item->isFile() && str_ends_with($item->getFilename(), '.php')
                        && str_contains((string) file_get_contents($item->getPathname()), 'ui.nav_style')) {
                        $hits[] = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($root))), '/');
                    }
                }
            }

            if ($hits === []) {
                return ['ok', 'ไม่มีโค้ดที่ใช้ชื่อเดิม'];
            }

            return ['manual',
                "เปลี่ยน 'ui.nav_style' เป็น 'ui.nav' ใน " . implode(' · ', $hits)
                . ' — ชื่อเดิมคืนค่าว่างเงียบๆ แล้ว layout วาดเมนูผิดแบบโดยไม่มี error'];
        },
    ],
];

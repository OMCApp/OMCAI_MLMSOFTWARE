<?php
/**
 * ตรวจกฎใน standard/security.md
 */

declare(strict_types=1);

return [

    'sql-prepared' => [
        'title' => 'ทุก query ต้องใช้ prepared statement ห้ามต่อ string เข้า SQL',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            // ตัวแปรแทรกอยู่ในสตริงที่ส่งเข้า query/exec/prepare
            $c->scan(
                ['app/**/*.php', 'core/**/*.php'],
                '/->(?:query|exec|prepare)\s*\(\s*(["\'])[^"\']*(?:\{?\$\w+)/',
                'มีตัวแปรแทรกอยู่ใน SQL โดยตรง',
                'ใช้ placeholder แล้วส่งค่าผ่าน execute([...]) แทน'
            );

            // ต่อสตริงเข้ากับ SQL
            $c->scan(
                ['app/**/*.php', 'core/**/*.php'],
                '/->(?:query|exec|prepare)\s*\(\s*(["\'])[^"\']*\1\s*\./',
                'มีการต่อ string เข้ากับ SQL',
                'ใช้ placeholder แล้วส่งค่าผ่าน execute([...]) แทน'
            );
        },
    ],

    'sort-allowlist' => [
        'title' => 'ค่าที่ผูก placeholder ไม่ได้ (sort, dir) ต้องผ่าน allowlist ก่อนใช้',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/**/*.php', 'core/**/*.php']) as $file) {
                $content = $c->read($file);

                if (preg_match_all('/\b(ORDER\s+BY|GROUP\s+BY)\b/i', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                $hasAllowlist = preg_match('/\bin_array\s*\(/', $content) === 1;

                foreach ($m[0] as $match) {
                    $line = Ctx::lineAt($content, (int) $match[1]);
                    $text = Ctx::snippet($content, $line);

                    // มีตัวแปรอยู่ในบรรทัดเดียวกับ ORDER BY แต่ไฟล์นี้ไม่มี allowlist เลย
                    if (preg_match('/\$\w+/', $text) === 1 && !$hasAllowlist) {
                        $c->error($file, $line,
                            'เรียงลำดับด้วยค่าจากตัวแปรโดยไม่มี allowlist ในไฟล์นี้',
                            'ตรวจชื่อคอลัมน์และทิศทางกับรายการที่กำหนดไว้ในโค้ดก่อนใช้ (in_array แบบ strict)',
                            $text);
                    }
                }
            }
        },
    ],

    'output-escape' => [
        'title' => 'ทุก output ที่มาจากผู้ใช้ต้องผ่าน e() ก่อนแสดง',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/Views/**/*.php']) as $file) {
                if (str_contains($file, 'app/Views/manuals/')) {
                    continue; // เนื้อความคู่มือเป็นข้อยกเว้นตาม features/manual.md
                }

                $content = $c->read($file);

                // เฉพาะฟังก์ชันที่ escape ให้แล้วหรือคืนค่าที่ระบบสร้างเองทั้งหมด
                // ‼️ old() ไม่อยู่ในรายการนี้โดยตั้งใจ — มันคืนค่าที่ผู้ใช้กรอกมา ต้องครอบ e() เสมอ
                $allowed = 'e|__|url|icon|csrf_field|method_field|number_format|format_date|format_money';

                /*
                 * ‼️ ช่องว่างหลัง <?= ต้องจับด้วย atomic group ห้ามใช้ \s* เฉยๆ
                 *    ไม่งั้น regex จะถอยให้กินช่องว่าง 0 ตัวแล้วตรวจ lookahead ที่ตำแหน่งช่องว่างแทน
                 *    ทำให้ negative lookahead ผ่านเสมอ และจับทุกบรรทัดผิดหมด
                 *
                 * ‼️ ห้ามเขียนเครื่องหมายปิด PHP tag ในคอมเมนต์แบบ // เด็ดขาด
                 *    เพราะมันปิด PHP tag จริง ทำให้ไฟล์ขาดตรงนั้นและ parse error ที่ท้ายไฟล์
                 */
                $pattern = '/<\?=(?>\s*)(?!(?:' . $allowed . ')(?>\s*)\()/';

                if (preg_match_all($pattern, $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                // ข้อยกเว้นเดียวที่แคบที่สุด: layout รับ HTML ของหน้าที่ escape มาแล้ว
                // จึงต้องไม่ escape ซ้ำ — จำกัดเฉพาะตัวแปรชื่อ content และเฉพาะในโฟลเดอร์ layouts
                $isLayout = str_contains($file, 'app/Views/layouts/');

                foreach ($m[0] as $match) {
                    $line = Ctx::lineAt($content, (int) $match[1]);
                    $text = Ctx::snippet($content, $line);

                    if ($isLayout && preg_match('/<\?=\s*\$content\b/', $text) === 1) {
                        continue;
                    }

                    $c->error($file, $line,
                        'แสดงค่าโดยไม่ผ่าน e()',
                        'ครอบด้วย e() เสมอ — ยกเว้นค่าที่มาจาก __() ซึ่ง escape ให้แล้ว',
                        $text);
                }
            }
        },
    ],

    'csrf-token' => [
        'title' => 'ทุกฟอร์มที่เปลี่ยนข้อมูลต้องมี CSRF token',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/Views/**/*.php']) as $file) {
                $content = $c->read($file);

                if (preg_match_all('/<form\b.*?<\/form>/is', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[0] as $match) {
                    $form = (string) $match[0];

                    if (preg_match('/method\s*=\s*["\']post["\']/i', $form) !== 1) {
                        continue;
                    }
                    if (str_contains($form, 'csrf_field')) {
                        continue;
                    }

                    $line = Ctx::lineAt($content, (int) $match[1]);
                    $c->error($file, $line,
                        'ฟอร์ม POST ไม่มี CSRF token',
                        'เพิ่ม <?= csrf_field() ?> ในฟอร์ม');
                }
            }
        },
    ],

    'env-single-source' => [
        'title' => 'config/config.php เป็นที่เดียวที่อ่าน .env',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/**/*.php', 'core/**/*.php', 'config/**/*.php', 'public/**/*.php']) as $file) {
                if ($file === 'config/config.php') {
                    continue;
                }

                $content = $c->read($file);

                if (preg_match_all('/\$_ENV\b|\bgetenv\s*\(/', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[0] as $match) {
                    $line = Ctx::lineAt($content, (int) $match[1]);
                    $c->error($file, $line,
                        'อ่าน .env นอก config/config.php',
                        'ให้ config/config.php อ่านค่าแล้วส่งต่อมาแทน',
                        Ctx::snippet($content, $line));
                }
            }
        },
    ],

    'session-direct' => [
        'title' => 'core/Session.php เป็นไฟล์เดียวที่แตะ $_SESSION ได้',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/**/*.php', 'core/**/*.php']) as $file) {
                // ยกเว้นไฟล์เดียวเท่านั้น — Auth, Lang, flash, old input ทุกตัวเรียกผ่านมัน
                // ถ้ายกเว้นหลายไฟล์ วันที่เปลี่ยนวิธีเก็บ session จะต้องไล่แก้หลายที่
                if ($file === 'core/Session.php') {
                    continue;
                }

                $content = $c->read($file);

                if (preg_match_all('/\$_SESSION\s*\[/', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[0] as $match) {
                    $line = Ctx::lineAt($content, (int) $match[1]);
                    $c->error($file, $line,
                        'แตะ $_SESSION โดยตรง',
                        'ใช้ Auth::id() / Auth::role() / Auth::check() แทน — เปลี่ยนวิธีเก็บ session ทีหลังจะได้ไม่ต้องไล่แก้ทุกจุด',
                        Ctx::snippet($content, $line));
                }
            }
        },
    ],

    'weak-hash' => [
        'title' => 'ห้ามใช้ md5/sha1 กับรหัสผ่าน',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            $c->scan(
                ['app/**/*.php', 'core/**/*.php'],
                '/\b(?:md5|sha1)\s*\(/',
                'ใช้ md5/sha1 — ถ้าเกี่ยวกับรหัสผ่านถือว่าผิดกฎเหล็ก',
                'รหัสผ่านต้องใช้ password_hash() และ password_verify() เท่านั้น',
                'warn'
            );
        },
    ],

    'pdo-emulate' => [
        'title' => 'PDO ต้องตั้ง ATTR_EMULATE_PREPARES => false',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            if (!$c->exists('core/Database.php')) {
                return;
            }
            if ($c->onlyFile !== null && $c->onlyFile !== 'core/Database.php') {
                return;
            }

            $content = $c->read('core/Database.php');

            if (preg_match('/ATTR_EMULATE_PREPARES\s*=>\s*false/', $content) !== 1) {
                $c->error('core/Database.php', 0,
                    'ไม่ได้ปิด emulate prepares',
                    'ตั้ง PDO::ATTR_EMULATE_PREPARES => false — ถ้าเปิดไว้ PDO จะประกอบ query เป็น string เอง ทำให้การกัน SQL injection อ่อนลง');
            }
        },
    ],

    'csv-injection' => [
        'title' => 'ส่งออก CSV ต้องกัน formula injection',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/**/*.php', 'core/**/*.php']) as $file) {
                $content = $c->read($file);

                if (!str_contains($content, 'fputcsv')) {
                    continue;
                }

                // มาตรฐานมี helper ให้แล้ว จึงบังคับให้ใช้ตัวนั้นตรงๆ
                // แม่นกว่าการเดาจากรูปแบบโค้ด และทำให้ทุกที่กันด้วยวิธีเดียวกัน
                if (str_contains($content, 'Security::csvCell')) {
                    continue;
                }

                $offset = (int) strpos($content, 'fputcsv');
                $line   = Ctx::lineAt($content, $offset);

                $c->error($file, $line,
                    'เขียน CSV โดยไม่ผ่าน Security::csvCell()',
                    "ทุกค่าที่เขียนลง CSV ต้องผ่าน Security::csvCell() — ค่าที่ขึ้นต้นด้วย = + - @ "
                    . 'จะถูก Excel ตีความเป็นสูตร ทำให้ข้อความที่ผู้ใช้กรอกกลายเป็นคำสั่งที่รันบนเครื่องคนเปิดไฟล์',
                    Ctx::snippet($content, $line));
            }
        },
    ],

    'auth-audited' => [
        'title' => 'เหตุการณ์ login/logout ต้องลง audit log',
        'run'   => function (Ctx $c): void {
            /* ‼️ features/audit-log.md ข้อ 2 บังคับให้บันทึก
                  "การเข้าสู่ระบบและออกจากระบบ รวมถึงการล็อกอินที่ล้มเหลว ← ใช้ตรวจการเดารหัสผ่าน"

               แต่ audit log อัตโนมัติอยู่ที่ base Model ซึ่งดูแลเฉพาะ create/update/delete
               เหตุการณ์ของ auth ไม่ผ่าน Model เลย จึงหลุดไปเงียบๆ ตั้งแต่ต้น
               และไม่มีอะไรจับได้ เพราะ check.php มองไม่เห็นสิ่งที่ "ไม่ได้ถูกเขียน"

               พบตอนล็อกอินสำเร็จจริงแล้วเปิดตาราง audit_logs ดู — ว่างเปล่า
               ทั้งที่ stages/1-setup.md เขียนเกณฑ์ผ่านด่านไว้ว่า audit log ต้องทำงานแล้ว */
            if (!$c->exists('core/Auth.php')) {
                return;
            }

            $content = $c->read('core/Auth.php');

            foreach (['login', 'logout', 'login_failed'] as $action) {
                if (preg_match('/Model::audit\(\s*[\'"]' . $action . '[\'"]/', $content) === 1) {
                    continue;
                }

                $c->error('core/Auth.php', 0,
                    "ไม่ได้บันทึก audit log ของเหตุการณ์ `{$action}`",
                    "เรียก Model::audit('{$action}', 'users', ...) ที่จุดนั้น — "
                    . 'audit log อัตโนมัติอยู่ที่ base Model ซึ่งดูแลเฉพาะ create/update/delete '
                    . 'เหตุการณ์ของ auth ไม่ผ่าน Model จึงต้องเรียกเอง');
            }
        },
    ],
];

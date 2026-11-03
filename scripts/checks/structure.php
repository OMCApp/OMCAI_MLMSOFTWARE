<?php
/**
 * ตรวจกฎใน standard/rule.md — การแบ่งชั้นและ convention
 */

declare(strict_types=1);

return [

    'static-analysis' => [
        'title' => 'ถ้าเครื่องนี้ลง phpstan ไว้ โค้ดต้องผ่านมันด้วย',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }

            /* ‼️ เป็นการตรวจแบบ "มีก็บังคับ ไม่มีก็ข้าม" ตัวแรกของชุดนี้
               phpstan หนัก 23 MB ซึ่งเป็น 70% ของทั้งโปรเจค และไม่มีอะไรเรียกมันเลย
               จึงถูกถอดออกจาก composer.json ให้เป็นของเสริมที่ติดตั้งเองเมื่อต้องการ

               แต่ถอดออกเฉยๆ แปลว่ามันจะไม่ถูกรันอีกเลย ทั้งที่มันจับของที่ 60 ข้อนี้จับไม่ได้
               (เคยจับได้ว่า render() ของ make-favicon.php ชนกับ render() ของ design-sync.php
               ซึ่งเป็นชนิดบั๊กที่ไม่มีตัวไหนในชุดนี้มองเห็น)

               ทางออกคือ ไม่มีก็ไม่บ่น มีเมื่อไหร่บังคับทันที
               คนที่เลือกลงมันจึงได้ประโยชน์เต็ม โดยไม่ผลักภาระ 23 MB ให้ทุกคนในทีม */
            $bin = $c->root . '/vendor/bin/phpstan';

            if (!is_file($bin) && !is_file($bin . '.bat')) {
                return;
            }

            // โหมด --changed วิเคราะห์เฉพาะไฟล์ PHP ที่เปลี่ยน — phpstan คือการตรวจที่หนักที่สุดในชุด
            $targets = 'app core config scripts';

            if ($c->changed !== null) {
                $php = array_values(array_filter($c->changed, static fn (string $f): bool =>
                    str_ends_with($f, '.php') && preg_match('#^(app|core|config|scripts)/#', $f) === 1));

                if ($php === []) {
                    return;
                }

                $targets = implode(' ', array_map('escapeshellarg', $php));
            }

            // ‼️ รวม stderr เข้า stdout ท่อเดียว ด้วยเหตุผลเดียวกับ design/css-in-sync
            $proc = proc_open(
                'php vendor/bin/phpstan analyse ' . $targets . ' --level=3 --no-progress --error-format=raw 2>&1',
                [1 => ['pipe', 'w']],
                $pipes,
                $c->root
            );

            if (!is_resource($proc)) {
                return;
            }

            $output = (string) stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            if (proc_close($proc) === 0) {
                return;
            }

            // รูปแบบ raw คือ path:บรรทัด:ข้อความ
            foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
                if (preg_match('/^(.+?):(\d+):(.*)$/', trim($line), $m) !== 1) {
                    continue;
                }

                $file = str_replace('\\', '/', $m[1]);
                $file = ltrim(str_replace(rtrim(str_replace('\\', '/', $c->root), '/'), '', $file), '/');

                $c->error($file, (int) $m[2], trim($m[3]),
                    'phpstan จับได้ — แก้ที่โค้ด · ถ้าเชื่อว่ามันผิดจริงให้แจ้งผู้ใช้');
            }
        },
    ],

    'controller-clean' => [
        'title' => 'Controller ห้ามมี query หรือ business logic',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            $c->scan(
                ['app/Controllers/**/*.php'],
                '/\bnew\s+PDO\b|Database::|->prepare\s*\(|->query\s*\(|\bSELECT\s+.+\bFROM\b/i',
                'Controller เข้าถึงฐานข้อมูลโดยตรง',
                'ย้าย query ไป Model และ business logic ไป Service — Controller เรียกใช้อย่างเดียว'
            );
        },
    ],

    'model-clean' => [
        'title' => 'Model ทำ CRUD อย่างเดียว ห้ามมี business logic ปน',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            $c->scan(
                ['app/Models/**/*.php'],
                '/\$_POST\b|\$_GET\b|\$_FILES\b|\bheader\s*\(|\bredirect\s*\(/',
                'Model แตะ request หรือสั่ง redirect',
                'Model เข้าถึงข้อมูลอย่างเดียว — ให้ Controller ส่งค่ามาแทน'
            );
        },
    ],

    'view-no-query' => [
        'title' => 'View ห้ามเรียกฐานข้อมูลเอง',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            $c->scan(
                ['app/Views/**/*.php'],
                '/\bnew\s+PDO\b|Database::|->prepare\s*\(|\bnew\s+\w*Model\b/',
                'View เข้าถึงฐานข้อมูลเอง',
                'ให้ Controller เตรียมข้อมูลให้ครบก่อนส่งเข้า View'
            );
        },
    ],

    'raw-path' => [
        'title' => 'ทุกลิงก์และ asset ต้องผ่าน url()',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/Views/**/*.php']) as $file) {
                if (str_contains($file, 'app/Views/manuals/')) {
                    continue;
                }

                $content = $c->read($file);

                if (preg_match_all('/\b(?:href|action|src)\s*=\s*["\']\//', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[0] as $match) {
                    $line = Ctx::lineAt($content, (int) $match[1]);
                    $c->error($file, $line,
                        'เขียน path ดิบขึ้นต้นด้วย /',
                        'ใช้ <?= e(url(\'/path\')) ?> — ระบบต้องเปิดได้ทั้งที่ root และใต้ subdirectory',
                        Ctx::snippet($content, $line));
                }
            }
        },
    ],

    'inline-style' => [
        'title' => 'ห้ามมี <style> หรือ style="" ในหน้า',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/Views/**/*.php']) as $file) {
                if (str_contains($file, 'app/Views/manuals/')) {
                    continue;
                }

                $c->scan([$file], '/<style\b/i',
                    'มีแท็ก <style> ในหน้า',
                    'ย้ายไป public/assets/css/app.css ซึ่ง generate จาก project/DESIGN.md');

                $c->scan([$file], '/\bstyle\s*=\s*["\']/',
                    'มี attribute style ในหน้า',
                    'ใช้ class ที่นิยามจาก token แทน — ค่าธีมทั้งหมดต้องอยู่จุดเดียว');
            }
        },
    ],

    'single-stylesheet' => [
        'title' => 'CSS ต้องมีไฟล์เดียวคือ app.css',
        'scope' => ['css'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['public/assets/css/**/*.css']) as $file) {
                if ($file === 'public/assets/css/app.css') {
                    continue;
                }

                $c->error($file, 0,
                    'มีไฟล์ CSS แยกนอกเหนือจาก app.css',
                    'รวมเข้า app.css — แก้ธีมต้องแก้ที่เดียวแล้วเปลี่ยนทั้งระบบ');
            }
        },
    ],

    'server-php-guard' => [
        'title' => 'server.php ต้องกันไม่ให้รันบน production',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            if (!$c->exists('server.php')) {
                return;
            }
            if ($c->onlyFile !== null && $c->onlyFile !== 'server.php') {
                return;
            }

            $content = $c->read('server.php');

            if (!str_contains($content, 'php_sapi_name') && !str_contains($content, 'cli-server')) {
                $c->warn('server.php', 0,
                    'ไม่มีการกันไม่ให้ถูกเรียกผ่านเว็บเซิร์ฟเวอร์จริง',
                    'ตรวจ php_sapi_name() === \'cli-server\' แล้วหยุดทำงานถ้าไม่ใช่');
            }
        },
    ],

    'file-size' => [
        'title' => 'ไฟล์ที่ใหญ่เกินไปคือสัญญาณว่าทำหลายหน้าที่เกินไป',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/**/*.php', 'core/**/*.php']) as $file) {
                $lines = substr_count($c->read($file), "\n") + 1;

                if ($lines > 400) {
                    $c->warn($file, 0,
                        "ไฟล์ยาว {$lines} บรรทัด",
                        'แยกความรับผิดชอบออกเป็นไฟล์ย่อย — ไฟล์ใหญ่ทั้ง AI และคนอ่านพลาดง่ายขึ้น');
                }
            }
        },
    ],

    'resource-complete' => [
        'title' => 'ทุก resource ต้องครบลูป ดู/เพิ่ม/แก้ไข/ลบ',
        'run'   => function (Ctx $c): void {
            /* ‼️ นิยาม "เสร็จ" ข้อ 1 ใน CLAUDE.md บังคับข้อนี้ไว้ตั้งแต่ต้น
                  "ทำได้แค่เพิ่ม แล้วไม่มีแก้ไขกับลบ คือใช้งานจริงไม่ได้ ถือว่ายังไม่เสร็จ"

               แต่มันถูกตรวจด้วย checkbox ที่คนติ๊กเอง ซึ่งเคยติ๊กเกินความจริงมาแล้ว
               จน 3.2.0 ต้องเพิ่มกฎ [-] กับการตรวจ docs/checkbox-honest ขึ้นมา

               ข้อนี้อ่านจาก routes.php ได้ตรงๆ จึงไม่ต้องพึ่งว่าใครจำได้

               ‼️ เป็น warn ไม่ใช่ error โดยตั้งใจ
                  ระหว่างทำ phase หนึ่ง resource ย่อมยังไม่ครบลูปเป็นเรื่องปกติ
                  ถ้าทำเป็น error คนจะปิดมันทิ้ง แล้วกลายเป็นกฎที่ไม่มีอยู่จริง
                  หน้าที่ของมันคือทำให้ "ยังไม่ครบ" มองเห็นได้ตอนเขียนรายงานจบ phase */
            if (!$c->exists('config/routes.php')) {
                return;
            }

            // โครงเปล่าที่ยังไม่เริ่ม /wf ไม่มี SCOPE.md ให้ประกาศข้อยกเว้น จึงยังไม่บังคับ
            if (!$c->exists('project/SCOPE.md')) {
                return;
            }

            $scope   = $c->read('project/SCOPE.md');
            $content = $c->read('config/routes.php');
            $found   = [];

            preg_match_all(
                '/\$router->(get|post|put|patch|delete)\s*\(\s*[\'"]([^\'"]+)[\'"]/',
                $content,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $route) {
                [$all, $verb, $path] = $route;
                $segments = array_values(array_filter(explode('/', $path)));

                if ($segments === []) {
                    continue;
                }

                $name = $segments[0];
                $found[$name]['verbs'][] = $verb;
                $found[$name]['byId']    = ($found[$name]['byId'] ?? false) || str_contains($path, '{');
            }

            foreach ($found as $name => $info) {
                $verbs = $info['verbs'];

                /* นับเป็น resource เมื่อมีทั้ง route ที่อ้างถึงรายการเดี่ยว ({id})
                   และมีการเขียนข้อมูลอย่างน้อยหนึ่งแบบ — กันไม่ให้ /login /logout /theme
                   ซึ่งเป็นการกระทำ ไม่ใช่ชุดข้อมูล ถูกนับเข้ามาด้วย */
                if (($info['byId'] ?? false) === false) {
                    continue;
                }
                if (array_intersect($verbs, ['post', 'put', 'patch', 'delete']) === []) {
                    continue;
                }

                // ประกาศไว้ใน SCOPE.md ว่าจงใจไม่ทำ ถือว่าตั้งใจ ไม่ใช่ลืม
                if (str_contains($scope, '/' . $name)) {
                    continue;
                }

                $missing = [];

                if (!in_array('post', $verbs, true)) {
                    $missing[] = 'เพิ่ม (POST)';
                }
                if (array_intersect($verbs, ['put', 'patch']) === []) {
                    $missing[] = 'แก้ไข (PUT/PATCH)';
                }
                if (!in_array('delete', $verbs, true)) {
                    $missing[] = 'ลบ (DELETE)';
                }

                if ($missing === []) {
                    continue;
                }

                $c->warn('config/routes.php', 0,
                    "resource `/{$name}` ยังไม่ครบลูป — ขาด " . implode(' · ', $missing),
                    'ทำให้ครบ หรือถ้าจงใจไม่ทำ ให้เขียน `/' . $name . '` ไว้ในหัวข้อ '
                    . '"ไม่อยู่ในขอบเขต" ของ project/SCOPE.md พร้อมเหตุผล — '
                    . 'การตัดสินว่าไม่ทำ ไม่ใช่การทำแล้ว');
            }
        },
    ],

    'partial-generic-var' => [
        'title' => 'ตัวแปรของ partial ต้องขึ้นต้นด้วยชื่อ partial',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            /* ‼️ PHP include ใช้ขอบเขตตัวแปรร่วมกับหน้าที่เรียก ชื่อกลางๆ จึงทับกันแบบเงียบสนิท
               อาการจริง: partials/field.php ตั้ง $rows = 4 (จำนวนบรรทัด textarea)
               หน้ารายการใช้ $rows เป็นข้อมูลของตาราง
               ฟอร์มแจ้งปัญหาใน layout มี textarea อยู่ทุกหน้า → ตารางหายทั้งระบบ
               พร้อม error ว่า foreach() argument must be of type array, int given

               หายากมากเพราะจุดที่พังกับจุดที่ตั้งค่าอยู่คนละไฟล์และไม่มีอะไรเชื่อมให้เห็น

               ‼️ เวอร์ชันแรกของการตรวจนี้ใช้ regex หารูป `$x = $x ??` เป๊ะๆ
                  ซึ่งแปลว่ามันไม่เคยจับอะไรได้เลยแม้แต่ครั้งเดียว —
                  โค้ดจริงเขียน `$total = (int) ($total ?? 0)` ที่มี cast คั่น จึงไม่แมตช์
                  และ `$name = 'message'` ที่เป็น assignment ธรรมดาก็ไม่แมตช์
                  ทั้งที่ตัวหลังคือบั๊กที่ทำให้ฟอร์มแจ้งปัญหาส่งเรื่องไม่ได้ทั้งระบบ

                  บทเรียน: การตรวจต้องทดสอบ **รูปร่างของบั๊ก** ไม่ใช่ **รูปร่างของวิธีแก้**
                  จึงเปลี่ยนมาเดินด้วย token_get_all() แล้วจับ assignment ทุกรูปที่ขอบเขตไฟล์ */
            $generic = [
                'rows', 'total', 'items', 'item', 'actions', 'value', 'label', 'title',
                'type', 'options', 'data', 'list', 'count', 'name', 'id', 'error',
                'page', 'pages', 'perPage', 'query', 'link', 'from', 'to', 'href',
                'active', 'sort', 'dir', 'filters', 'result', 'results', 'hint', 'required',
            ];

            foreach ($c->files(['app/Views/partials/**/*.php']) as $file) {
                $base   = basename($file, '.php');
                $prefix = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $base))));

                foreach (partial_scope_assignments($c->read($file)) as [$var, $line, $text]) {
                    if (!in_array($var, $generic, true) || str_starts_with($var, $prefix)) {
                        continue;
                    }

                    $c->error($file, $line,
                        "\$$var เป็นชื่อกลางๆ ที่รั่วไปทับตัวแปรของหน้าที่เรียก",
                        "ตั้งเป็น \${$prefix}" . ucfirst($var) . ' แทน — '
                        . 'PHP include ใช้ขอบเขตร่วมกับหน้าที่เรียก ชื่อซ้ำจะทับกันแบบไม่มี error แจ้ง',
                        $text);
                }
            }
        },
    ],

    'option-source-exists' => [
        'title' => 'แหล่งข้อมูลของ dropdown ที่ค้นได้ ต้องมีอยู่ในทะเบียนจริง',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            /* ‼️ พิมพ์คีย์ผิดแล้วไม่มีอะไรฟ้องตอน render — dropdown ขึ้นปกติทุกอย่าง
               ผู้ใช้กดแล้วได้ 404 เงียบๆ ในแท็บ network ซึ่งไม่มีใครเปิดดูตอนทำงาน
               (standard/components.md ข้อ 3ก) */
            if (!$c->exists('config/options.php')) {
                return;
            }

            $registry = (array) require $c->root . '/config/options.php';

            foreach ($c->files(['app/**/*.php']) as $file) {
                $content = $c->read($file);

                if (preg_match_all('/\$fieldSource\s*=\s*[\'"]([^\'"]*)[\'"]/', $content, $m, PREG_SET_ORDER) === 0) {
                    continue;
                }

                foreach ($m as $match) {
                    if ($match[1] === '' || isset($registry[$match[1]])) {
                        continue;
                    }

                    $c->error($file, Ctx::lineAt($content, (int) strpos($content, $match[0])),
                        'แหล่งข้อมูล "' . $match[1] . '" ไม่มีอยู่ใน config/options.php',
                        'เพิ่มแหล่งนี้ในทะเบียนพร้อมคอลัมน์ที่ค้นได้และสิทธิ์ของมัน '
                        . 'หรือแก้ชื่อคีย์ให้ตรง — คีย์ที่ไม่มีในทะเบียนได้ 404 ตอนผู้ใช้กดค้น');
                }
            }
        },
    ],

    'partial-input-contract' => [
        'title' => 'ค่าที่ตั้งก่อน include partial ต้องเป็นชื่อที่ partial นั้นรับจริง',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            /* ‼️ อีกครึ่งหนึ่งของบั๊กเดียวกัน และเป็นครึ่งที่เงียบกว่า
               `partial-generic-var` ดูฝั่ง partial ว่าปล่อยชื่อกลางๆ รั่วออกมาไหม
               ข้อนี้ดูฝั่งผู้เรียกว่า "ตั้งชื่อที่ partial ไม่ได้อ่าน" หรือเปล่า

               อาการจริง 2 จุดที่หลุดมาถึงมาตรฐาน 3.2.2 โดย check.php ผ่านหมด:

               1. partials/support.php ตั้ง $name $label $type $required $hint $value
                  แต่ field.php เปลี่ยนไปอ่าน $fieldName $fieldLabel ... ตั้งแต่ 3.2.0
                  → ฟอร์มแจ้งปัญหาบนทุกหน้าของทุกโปรเจค render เป็น
                    <input name="" type="text"> ที่ไม่มี label และส่งข้อความไม่ได้เลย

               2. pages/admin/audit-logs.php ตั้ง $fieldLabel ก่อน include th-sort.php
                  ซึ่งอ่าน $thSortLabel → หัวตารางแสดงชื่อคอลัมน์ดิบภาษาอังกฤษ

               ทั้งสองจุดคือ "การเปลี่ยนชื่อรอบ 3.2.0 แก้ที่ partial แล้วลืมผู้เรียก"
               ซึ่งไม่มีอะไรจับได้เลย เพราะ PHP ไม่บ่นเรื่องตัวแปรที่ไม่มีใครใช้ */
            $inputs = [];

            foreach ($c->files(['app/Views/partials/**/*.php']) as $file) {
                $declared = [];

                // partial ประกาศ input ของตัวเองด้วยรูป $x = $x ?? ... เสมอ
                if (preg_match_all('/\$([a-zA-Z_]\w*)\s*=[^;]*\$\1\s*\?\?/', $c->read($file), $m) > 0) {
                    $declared = $m[1];
                }

                $inputs[basename($file, '.php')] = $declared;
            }

            /* ‼️ ต้องจับ include ทั้งสองรูปแบบที่ใช้จริงในโครง
                  จากหน้า:   include __DIR__ . '/../../partials/field.php'
                  จาก partial ด้วยกัน: include __DIR__ . '/dialog.php'
               เวอร์ชันแรกจับเฉพาะรูปที่มี `partials/` นำหน้า จึงไม่เห็นรูปที่สองเลย
               ทดสอบด้วยการตั้งชื่อผิดจริงแล้วชุดตรวจเงียบ — จึงต้องเทียบด้วยชื่อไฟล์แทน */
            $isInclude = static fn (string $line): bool
                => preg_match('/\b(include|require)(_once)?\b/', $line) === 1;

            foreach ($c->files(['app/Views/**/*.php']) as $file) {
                $lines = preg_split('/\R/', $c->read($file)) ?: [];

                foreach ($lines as $index => $text) {
                    if (!$isInclude($text) || preg_match('#[\'"/]([a-z0-9-]+)\.php#i', $text, $m) !== 1) {
                        continue;
                    }

                    $declared = $inputs[$m[1]] ?? null;

                    if ($declared === null || $declared === []) {
                        continue;
                    }

                    // ดูย้อนขึ้นไปจนกว่าจะเจอ include ตัวก่อนหน้า หรือครบ 12 บรรทัด
                    for ($back = $index - 1; $back >= 0 && $back > $index - 13; $back--) {
                        $line = $lines[$back];

                        if ($isInclude($line)) {
                            break;
                        }
                        if (preg_match('/^\s*\$([a-zA-Z_]\w*)\s*=(?!=)/', $line, $v) !== 1) {
                            continue;
                        }
                        if (in_array($v[1], $declared, true)) {
                            continue;
                        }

                        /* ‼️ บรรทัดที่ partial ประกาศ input ของตัวเอง ($x = $x ?? ...) ไม่ใช่
                           "ค่าที่ตั้งให้ include ถัดไป" — มันอยู่หัวไฟล์และบังเอิญอยู่เหนือ include
                           ไม่ข้ามแล้วจะได้ false positive ทุกครั้งที่ partial เรียก partial อื่น */
                        if (preg_match('/\$' . preg_quote($v[1], '/') . '\s*=[^;]*\$' . preg_quote($v[1], '/') . '\s*\?\?/', $line) === 1) {
                            continue;
                        }

                        // ตั้งชื่อที่ partial ตัวนี้ไม่อ่าน — เตือนเฉพาะเมื่อมันใกล้เคียงของจริง
                        $near = array_filter(
                            $declared,
                            fn (string $d): bool => levenshtein(strtolower($d), strtolower($v[1])) <= 6
                        );

                        if ($near === []) {
                            continue;
                        }

                        $c->error($file, $back + 1,
                            "\${$v[1]} ตั้งไว้ก่อน include {$m[1]}.php แต่ partial นั้นไม่ได้อ่านตัวแปรนี้",
                            'ใช้ชื่อที่ partial รับจริง: $' . implode(' $', $near)
                            . ' — ค่าที่ตั้งผิดชื่อจะเงียบสนิท partial จะใช้ค่า default แทนโดยไม่มี error',
                            trim($line));
                    }
                }
            }
        },
    ],
];

/**
 * คืน assignment ทุกตัวที่อยู่ "ขอบเขตไฟล์" ของ partial — ตัวที่รั่วไปหาผู้เรียกจริงๆ
 *
 * ‼️ ต้องข้ามข้างในฟังก์ชันและ closure ให้ได้
 *    pagination.php ตั้ง $query = $_GET ข้างใน closure ซึ่งไม่รั่วออกมา
 *    ถ้าจับด้วย regex ทั้งไฟล์จะได้ false positive แล้วคนจะเริ่มไม่เชื่อชุดตรวจ
 *
 * @return list<array{0:string,1:int,2:string}> [ชื่อตัวแปร, เลขบรรทัด, ข้อความบรรทัดนั้น]
 */
function partial_scope_assignments(string $code): array
{
    $tokens  = token_get_all($code);
    $lines   = preg_split('/\R/', $code) ?: [];
    $out     = [];
    $depth   = 0;
    $fnDepth = [];
    $pending = false;

    foreach ($tokens as $index => $token) {
        if (is_array($token)) {
            if ($token[0] === T_FUNCTION || $token[0] === T_FN) {
                $pending = true;
            }

            if ($token[0] !== T_VARIABLE || $fnDepth !== []) {
                continue;
            }

            // ต้องตามด้วย = ที่ไม่ใช่ == และไม่ใช่ =>
            for ($next = $index + 1; isset($tokens[$next]); $next++) {
                $peek = $tokens[$next];

                if (is_array($peek) && in_array($peek[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                if ($peek === '=') {
                    $name = ltrim($token[1], '$');

                    if ($name !== 'this') {
                        $out[] = [$name, $token[2], trim($lines[$token[2] - 1] ?? '')];
                    }
                }

                break;
            }

            continue;
        }

        if ($token === '{') {
            $depth++;

            if ($pending) {
                $fnDepth[] = $depth;
                $pending   = false;
            }

            continue;
        }

        if ($token === '}') {
            if ($fnDepth !== [] && end($fnDepth) === $depth) {
                array_pop($fnDepth);
            }

            $depth--;
        }
    }

    return $out;
}

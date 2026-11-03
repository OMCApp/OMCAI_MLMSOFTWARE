<?php
/**
 * ตรวจกฎใน standard/design.md — ค่าธีมทั้งหมดต้องมาจาก token จุดเดียว
 */

declare(strict_types=1);

/** ขอบเขตที่ design-sync.php เขียน token ลงไป — hex ในช่วงนี้เท่านั้นที่อนุญาต */
const DESIGN_TOKEN_BEGIN = 'TOKENS:BEGIN';
const DESIGN_TOKEN_END   = 'TOKENS:END';

return [

    'design-md-exists' => [
        'title' => 'ต้องมี project/DESIGN.md ก่อนออกแบบหรือแก้หน้าใดๆ',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }

            $hasViews = $c->files(['app/Views/pages/**/*.php']) !== [];

            if ($hasViews && !$c->exists('project/DESIGN.md')) {
                $c->error('project/DESIGN.md', 0,
                    'มีหน้าเว็บแล้วแต่ยังไม่มี DESIGN.md',
                    'ทำช่วง B ให้จบก่อน — ปล่อยผ่านเมื่อไหร่ ผลลัพธ์จะออกมาเป็นหน้า default ไร้เอกลักษณ์ทุกครั้ง');
            }
        },
    ],

    'design-md-decided' => [
        'title' => 'DESIGN.md ต้องถูกตัดสินใจแล้ว ไม่ใช่ค่าตั้งต้นของโครงเปล่า',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null || !$c->exists('project/DESIGN.md')) {
                return;
            }

            // โปรเจคที่ยังไม่เริ่ม /wf ยังไม่ต้องถูกบังคับ — โครงเปล่าคาดว่ายังเป็น placeholder
            if (!$c->exists('project/STATE.md')) {
                return;
            }

            $content = $c->read('project/DESIGN.md');

            if (preg_match('/^\s*status\s*:\s*placeholder\s*$/mi', $content) !== 1) {
                return;
            }

            /* ‼️ เป็น warn ไม่ใช่ error — และเหตุผลสำคัญกว่าที่คิด
               เดิมเป็น error ที่เริ่มทำงานทันทีที่มี project/STATE.md
               แต่ STATE.md ถูกสร้างตั้งแต่ "เริ่มโปรเจค" ไม่ใช่ตอน "ผ่านช่วง B"

               ผลคือตั้งแต่วินาทีที่เริ่มโปรเจค จนถึงวินาทีที่ DESIGN.md เสร็จ
               pre-commit hook บล็อกทุก commit — SCOPE.md · PLAN.md · wireframe ทุกไฟล์
               commit ไม่ได้เลยแม้แต่ครั้งเดียว ต้องทำช่วง B รวดเดียวจบหรือเสี่ยงงานหาย

               เจอตอนรันช่วง B จนจบจริงเป็นครั้งแรก — ก่อนหน้านี้ไม่มีใครเจอเพราะไม่มีใครรัน

               สิ่งที่กฎนี้ต้องการกันคือ "เขียนหน้าก่อนมีดีไซน์" ซึ่งเป็นเงื่อนไข
               ก่อนเข้าช่วง C ตามตารางท้าย flow.md ข้อ 12 อยู่แล้ว
               ไม่ใช่เงื่อนไขของการ commit ระหว่างช่วง B */
            $c->warn('project/DESIGN.md', 0,
                'ยังเป็นค่าตั้งต้นของโครงเปล่า (status: placeholder)',
                'ต้องตัดสินให้เสร็จก่อนเข้าช่วง C — ระหว่างช่วง B ยังปล่อยไว้ได้'
                . ' · ทำช่วง B ให้จบแล้วเปลี่ยน status เป็น decided'
                . ' · เข้าช่วง C ทั้งที่ยังเป็น placeholder ผลลัพธ์จะออกมาเป็นหน้าไร้เอกลักษณ์ทุกครั้ง');
        },
    ],

    'favicon-brand-match' => [
        'title' => 'favicon ต้องเป็นสีแบรนด์ปัจจุบัน ไม่ใช่สีของโครงเปล่า',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null || !$c->exists('project/DESIGN.md')) {
                return;
            }

            /* ‼️ ทำไมต้องมีข้อนี้
               `make-favicon.php` เคยเป็นแค่ checkbox ใน stages/1-setup.md ไม่มีอะไรบังคับ
               ซึ่งชนกับสิ่งที่ประวัติของมาตรฐานนี้พิสูจน์มาแล้วว่า
               กฎที่ไม่มี check.php รองรับจะถูกข้ามเสมอ

               และมันแย่กว่ากฎอื่นที่ถูกข้าม เพราะ favicon ของโครงเปล่าถูก commit มาด้วย
               โปรเจคที่ลืมรันจึงไม่เห็นอะไรพัง — แท็บแค่เป็นสีเทาของโครงไปเรื่อยๆ
               ตรงกับที่ design.md ข้อ 8.1 เตือนไว้เองว่า
               "แท็บเบราว์เซอร์ไม่ได้อยู่ในหน้า จึงไม่มีใครเห็นว่ามันยังเป็นสีของแบรนด์เดิม"

               ‼️ เทียบด้วยสีของ pixel ไม่ใช่เวลาแก้ไขไฟล์
               mtime หลัง clone ใหม่เรียงยังไงก็ได้ ใช้เป็นเกณฑ์แล้วจะ fail แบบสุ่ม */
            $content = $c->read('project/DESIGN.md');

            // โครงเปล่าที่ยังไม่ตัดสินดีไซน์ ยังไม่ต้องถูกบังคับ
            if (preg_match('/^\s*status\s*:\s*decided\s*$/mi', $content) !== 1) {
                return;
            }

            if (preg_match('/^\s*primary\s*:.*?light\s*:\s*"?(#[0-9A-Fa-f]{6})"?/mi', $content, $m) !== 1) {
                return;
            }

            $brand = strtoupper($m[1]);
            $icon  = 'public/assets/images/favicon-32.png';

            if (!$c->exists($icon)) {
                $c->error($icon, 0,
                    'ตัดสินดีไซน์แล้วแต่ยังไม่มีไฟล์ favicon',
                    'รัน `php scripts/make-favicon.php` — layout กลางอ้างไฟล์นี้อยู่ ขาดไปคือ 404 ทุกหน้า');

                return;
            }

            if (!function_exists('imagecreatefromstring')) {
                return;
            }

            $image = @imagecreatefromstring($c->read($icon));

            if ($image === false) {
                return;
            }

            $rgb = imagecolorat($image, 0, 0);
            imagedestroy($image);

            $corner = sprintf('#%02X%02X%02X', ($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255);

            if ($corner === $brand) {
                return;
            }

            $c->error($icon, 0,
                "พื้นเป็น {$corner} แต่ `brand.primary` ของโปรเจคคือ {$brand}",
                'รัน `php scripts/make-favicon.php` ใหม่ — ต้องรันซ้ำทุกครั้งที่เปลี่ยนสีแบรนด์หรือโลโก้');
        },
    ],

    'token-modes' => [
        'title' => 'ทุก token สีต้องมีค่าครบทั้งโหมดสว่างและมืด',
        'run'   => function (Ctx $c): void {
            if (!$c->exists('project/DESIGN.md')) {
                return;
            }
            if ($c->onlyFile !== null && $c->onlyFile !== 'project/DESIGN.md') {
                return;
            }

            $content = $c->read('project/DESIGN.md');

            // รับเฉพาะบรรทัดที่เป็นค่าสีในรูปแบบ { light: ..., dark: ... }
            if (preg_match_all('/^\s*([\w-]+)\s*:\s*\{([^}]*)\}\s*$/m', $content, $m, PREG_SET_ORDER) === 0) {
                return;
            }

            foreach ($m as $entry) {
                [$whole, $name, $body] = $entry;

                if (preg_match('/#[0-9a-f]{3,8}\b|rgb|oklch|hsl/i', $body) !== 1) {
                    continue; // ไม่ใช่ token สี
                }

                $hasLight = preg_match('/\blight\s*:/i', $body) === 1;
                $hasDark  = preg_match('/\bdark\s*:/i', $body) === 1;

                if ($hasLight && $hasDark) {
                    continue;
                }

                $offset = (int) strpos($content, $whole);

                $c->error('project/DESIGN.md', Ctx::lineAt($content, $offset),
                    "token `{$name}` มีค่าไม่ครบทั้งสองโหมด",
                    'ทุกระบบต้องมีโหมดสว่างและมืด — token ที่มีค่าโหมดเดียวจะทำให้อีกโหมดพัง',
                    trim($whole));
            }
        },
    ],

    'css-in-sync' => [
        'title' => 'app.css ต้องตรงกับ project/DESIGN.md',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }
            if (!$c->exists('project/DESIGN.md') || !$c->exists('scripts/design-sync.php')) {
                return;
            }

            // ‼️ รวม stderr เข้า stdout แล้วใช้ท่อเดียว ห้ามเปิดสองท่อแล้วอ่านทีละท่อ
            //    อ่าน stdout จนจบก่อนจะค้างตาย ถ้าคำสั่งเขียน stderr มากพอจนท่อเต็ม
            $proc = proc_open(
                'php scripts/design-sync.php --check 2>&1',
                [1 => ['pipe', 'w']],
                $pipes,
                $c->root
            );

            if (!is_resource($proc)) {
                return;
            }

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            if (proc_close($proc) !== 0) {
                // ‼️ ยกข้อความจากสคริปต์มาตรงๆ เพราะมันรู้ว่าไฟล์ไหนไม่ตรง
                //    เขียนทับด้วยข้อความตายตัวว่า "app.css" จะชี้ผิดไฟล์เมื่อของที่ไม่ตรงคือ config/design.php
                $first = trim(strtok(trim($output), "\n") ?: '');

                $c->error('public/assets/css/app.css', 0,
                    ltrim($first, "✗ ") !== '' ? ltrim($first, "✗ ") : 'ไฟล์ที่ generate ไม่ตรงกับ DESIGN.md',
                    'รัน `php scripts/design-sync.php` — ถ้ามีคนแก้ไฟล์ที่ generate ด้วยมือ การแก้นั้นจะหายไป แก้ที่ DESIGN.md แทน');
            }
        },
    ],

    'public-page-boldness' => [
        'title' => 'หน้าสาธารณะต้องผ่านเกณฑ์ 3 ข้อของ design.md 5.16',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }

            /* ‼️ ทำไมต้องมีข้อนี้
               ชุดตรวจทุกข้อก่อนหน้านี้พูดได้แค่ว่า "อะไรผิด" ไม่มีข้อไหนพูดได้ว่า
               "อันนี้ปลอดภัยเกินไปจนจืด" — AI ที่เดินตาม checklist จึงเลือกทางที่ปลอดภัยที่สุดเสมอ
               ซึ่งคือทางที่จืดที่สุด (design.md 5.16 · STATUS.md บันทึกไว้ตั้งแต่ 25 ส.ค.)

               3.7.1 ตั้งเกณฑ์ 3 ข้อขึ้นมาแก้เรื่องนี้ แต่ปล่อยไว้เป็นข้อความ
               ซึ่งเป็นชะตากรรมเดียวกับที่ 3.8.0 อธิบายเองว่าทำไม frontend-design ถูกข้าม
               คือ "เป็นแถวในตาราง ไม่ใช่ขั้นตอนในงาน"

               ‼️ ข้อ 1 (hero สูง 70% ของจอ) วัดจากไฟล์ตรงๆ ไม่ได้ จึงตรวจที่ token
                  ซึ่งเป็นต้นทางของความสูงจริง ส่วนความสูงที่เรนเดอร์ออกมาต้องวัดในเบราว์เซอร์
                  ตาม testing.md ข้อ 4ก — ข้อนี้เป็นกำแพงด่านแรก ไม่ใช่ด่านสุดท้าย */
            $pages = [];

            foreach ($c->files(['app/Views/pages/**/*.php']) as $file) {
                $content = $c->read($file);

                if (str_contains($content, 'partials/hero.php') || str_contains($content, 'page_public(true)')) {
                    $pages[$file] = $content;
                }
            }

            if ($pages === []) {
                return;
            }

            // เกณฑ์ 2 และ 3 — ดูรายหน้า
            foreach ($pages as $file => $content) {
                $escapes = str_contains($content, 'heroFullBleed')
                    || str_contains($content, 'bleed')
                    || str_contains($content, 'heroFull');

                if (!$escapes) {
                    $c->error($file, 0,
                        'หน้าสาธารณะแต่ไม่มีอะไรหลุดออกจากกรอบ maxWidth เลย',
                        'เกณฑ์ 5.16 ข้อ 3 — ต้องมีอย่างน้อยหนึ่งอย่างที่ชนขอบจอ '
                        . 'ใช้ $heroFullBleed = true หรือครอบ section ด้วย .bleed');
                }
            }

            // เกณฑ์ 1 — ความสูงของส่วนเปิดมาจาก token
            $design = $c->read('project/DESIGN.md');

            foreach ([
                'minHeight'       => 560,
                'minHeightMobile' => 420,
            ] as $key => $floor) {
                if (preg_match('/^\s*' . $key . '\s*:\s*(\d+)px/mi', $design, $m) !== 1) {
                    $c->error('project/DESIGN.md', 0,
                        "ไม่มี component.hero.{$key}",
                        "หน้าสาธารณะต้องมีค่านี้ — ส่วนเปิดที่ไม่มีความสูงขั้นต่ำจะยุบตามเนื้อหา");
                    continue;
                }

                if ((int) $m[1] < $floor) {
                    $c->error('project/DESIGN.md', 0,
                        "component.hero.{$key} = {$m[1]}px ซึ่งต่ำกว่า {$floor}px",
                        'เกณฑ์ 5.16 ข้อ 1 — ส่วนเปิดต้องกินอย่างน้อย 70% ของจอแรก '
                        . 'ต่ำกว่านี้คือแถบหัวเรื่อง ไม่ใช่ส่วนเปิด');
                }
            }

            // เกณฑ์ 2 — พาดหัวต้องใหญ่กว่าข้อความรองอย่างน้อย 4 เท่า
            if (preg_match('/display\s*:\s*([\d.]+)/', $design, $m) === 1) {
                $display = (float) $m[1];

                if (preg_match('/\bbase\s*:\s*([\d.]+)/', $design, $m2) === 1) {
                    $ratio = $display / max(0.001, (float) $m2[1]);

                    if ($ratio < 4) {
                        $c->error('project/DESIGN.md', 0,
                            sprintf('typography.scale.display ต่อ base = %.1f:1 ซึ่งต่ำกว่า 4:1', $ratio),
                            'เกณฑ์ 5.16 ข้อ 2 — ต่ำกว่านี้ตาจะอ่านว่าทุกอย่างสำคัญเท่ากัน');
                    }
                }
            } else {
                $c->error('project/DESIGN.md', 0,
                    'ไม่มี typography.scale.display',
                    'หน้าสาธารณะต้องมีขั้นพาดหัวที่ใหญ่กว่า 3xl — ดู design.md 5.16');
            }
        },
    ],

    'no-raw-color' => [
        'title' => 'ห้ามเขียนค่าสีดิบนอกขอบเขต token',
        'scope' => ['css', 'php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['public/assets/css/**/*.css', 'app/Views/**/*.php']) as $file) {
                if (str_contains($file, 'app/Views/manuals/')) {
                    continue;
                }

                /* ‼️ ต้องหาขอบเขต token จากบรรทัดดิบ แต่ค้นหาค่าสีจากบรรทัดที่ลบคอมเมนต์แล้ว
                   ‣ ขอบเขตต้องใช้บรรทัดดิบ เพราะ marker TOKENS:BEGIN/END ตัวมันเองอยู่ในคอมเมนต์ CSS
                     ลบคอมเมนต์ก่อนแล้วขอบเขตจะหายไป → token ทุกบรรทัดถูกรายงานเป็นสีดิบ
                   ‣ การค้นหาต้องใช้บรรทัดที่ลบคอมเมนต์แล้ว เพราะคอมเมนต์ไม่ใช่ค่าที่ออกไปถึงผู้ใช้
                     เดิมสแกนบรรทัดดิบ ทำให้เลขอ้างอิงที่ขึ้นต้นด้วย # ถูกจับเป็นสีทั้งหมด
                     เช่น `ลบ "ใบสั่งซื้อ #1042" ?` ในคอมเมนต์ หรือ `#404` ในข้อความ
                     เป็น false positive ที่ทำให้คนเริ่มไม่เชื่อชุดตรวจ ซึ่งแพงกว่ากฎที่หลวมไป

                   ทั้งสองอาเรย์มีจำนวนบรรทัดเท่ากันเสมอ เพราะตัวลบคอมเมนต์คงบรรทัดไว้ */
                $raw    = preg_split('/\R/', $c->read($file)) ?: [];
                $code   = preg_split('/\R/', strip_comments_keep_lines($c->read($file))) ?: [];
                $inToken = false;

                foreach ($raw as $index => $rawText) {
                    if (str_contains($rawText, DESIGN_TOKEN_BEGIN)) {
                        $inToken = true;
                        continue;
                    }
                    if (str_contains($rawText, DESIGN_TOKEN_END)) {
                        $inToken = false;
                        continue;
                    }
                    if ($inToken) {
                        continue;
                    }

                    $text = $code[$index] ?? $rawText;

                    if (preg_match('/#[0-9a-fA-F]{3,8}\b|\brgba?\s*\(|\bhsla?\s*\(|\boklch\s*\(/', $text) !== 1) {
                        continue;
                    }
                    // อนุญาต var() ที่อ้าง token และ currentColor
                    if (preg_match('/var\s*\(\s*--/', $text) === 1) {
                        continue;
                    }

                    $c->error($file, $index + 1,
                        'มีค่าสีดิบอยู่นอกขอบเขต token',
                        'ย้ายค่าไป project/DESIGN.md แล้วรัน `php scripts/design-sync.php` — แก้ธีมต้องแก้ที่เดียว',
                        trim($rawText));
                }
            }
        },
    ],

    'no-raw-size' => [
        'title' => 'ระยะห่างและขนาดควรมาจากสเกลใน DESIGN.md',
        'scope' => ['css'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['public/assets/css/**/*.css']) as $file) {
                $content   = $c->read($file);
                $inToken   = false;
                $inComment = false;

                foreach (preg_split('/\R/', $content) ?: [] as $index => $text) {
                    /* ‼️ ต้องดูตัวคั่นเขต token จากบรรทัดดิบก่อนตัดคอมเมนต์
                       เพราะ /* TOKENS:BEGIN *​/ ตัวมันเองเป็นคอมเมนต์
                       ตัดคอมเมนต์ก่อนแล้วจะมองไม่เห็นขอบเขต แล้วไล่ฟ้องทั้งบล็อกที่ generate มา */
                    if (str_contains($text, DESIGN_TOKEN_BEGIN)) {
                        $inToken = true;
                        continue;
                    }
                    if (str_contains($text, DESIGN_TOKEN_END)) {
                        $inToken = false;
                        continue;
                    }
                    if ($inToken) {
                        continue;
                    }

                    /* ‼️ ตัดคอมเมนต์ออกก่อนหาค่าดิบ ไม่งั้นคำอธิบายที่พูดถึงตัวเลข px ถูกฟ้องว่าเป็นค่าดิบ
                       เจอจริงตอนเขียนคอมเมนต์ว่า "ปุ่มห่างจากข้อมูล 400px" ซึ่งเป็นการเล่าอาการ
                       ชุดตรวจที่จับผิดตัวทำให้คนเลิกเชื่อผลของมัน ซึ่งอันตรายกว่าไม่มีชุดตรวจ */
                    if ($inComment) {
                        $end = strpos($text, '*/');

                        if ($end === false) {
                            continue;
                        }

                        $inComment = false;
                        $text      = substr($text, $end + 2);
                    }

                    $text = (string) preg_replace('#/\*.*?\*/#', '', $text);

                    if (str_contains($text, '/*')) {
                        $inComment = true;
                        $text      = substr($text, 0, (int) strpos($text, '/*'));
                    }

                    if (trim($text) === '' || preg_match('/var\s*\(\s*--/', $text) === 1) {
                        continue;
                    }

                    // breakpoint ไม่ใช่ระยะห่าง — เป็นจุดที่ layout เปลี่ยน ไม่ใช่ค่าธีม
                    if (preg_match('/@(?:media|container)\b/', $text) === 1) {
                        continue;
                    }

                    // 44px คือพื้นที่กดขั้นต่ำที่มาตรฐานบังคับไว้เอง ไม่ใช่ค่าที่แต่ละโปรเจคเลือก
                    // 0-2px เป็นค่าโครงสร้าง (เส้นบาง ขอบ focus)
                    if (preg_match('/\b(?!0px|1px|2px|44px)\d{1,4}px\b/', $text) !== 1) {
                        continue;
                    }

                    $c->warn($file, $index + 1,
                        'มีค่าขนาดดิบเป็น px',
                        'ใช้ var(--space-*) หรือ var(--radius-*) จากสเกลที่กำหนดใน DESIGN.md',
                        trim($text));
                }
            }
        },
    ],

    'icon-button-label' => [
        'title' => 'ปุ่มที่มีแต่ไอคอนต้องมี aria-label',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/Views/**/*.php']) as $file) {
                if (str_contains($file, 'app/Views/manuals/')) {
                    continue;
                }

                $content = $c->read($file);

                if (preg_match_all('/<(?:button|a)\b[^>]*>\s*(?:<svg\b.*?<\/svg>|<i\b[^>]*><\/i>)\s*<\/(?:button|a)>/is',
                    $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[0] as $match) {
                    if (str_contains((string) $match[0], 'aria-label')) {
                        continue;
                    }

                    $line = Ctx::lineAt($content, (int) $match[1]);

                    $c->error($file, $line,
                        'ปุ่มไอคอนล้วนไม่มี aria-label',
                        'ใส่ aria-label และ tooltip — ไม่งั้นผู้ใช้เดาไม่ออกและ screen reader อ่านไม่ได้',
                        Ctx::snippet($content, $line));
                }
            }
        },
    ],

    'layout-footer' => [
        'title' => 'layout กลางต้องมี footer',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            $layouts = $c->files(['app/Views/layouts/*.php']);

            foreach ($layouts as $file) {
                $content = $c->read($file);

                // layout ที่เป็นชิ้นส่วนย่อยไม่ต้องมี footer เอง
                if (preg_match('/<\/body>/i', $content) !== 1) {
                    continue;
                }

                if (preg_match('/<footer\b|footer\.php/i', $content) === 1) {
                    continue;
                }

                $c->error($file, 0,
                    'layout ไม่มี footer',
                    'ทุกหน้าต้องมี footer จาก layout กลาง — ข้อที่ถูกข้ามบ่อยที่สุดถ้าไม่มีใครสั่ง');
            }
        },
    ],

    'reference-compared' => [
        'title' => 'หน้าที่มีภาพแบบกำหนด ต้องถูกเทียบกับแบบก่อนส่ง',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null || !$c->exists('project/DESIGN.md')) {
                return;
            }

            /* ‼️ ผู้ใช้ออกแบบหน้า login มาเป็นภาพ แล้วได้หน้าที่ไม่เหมือนภาพเลย (3.17.0)
               AI ไม่เคยเปิดภาพตอนสร้างหน้า และไม่มีขั้นไหนเทียบหน้าจริงกับแบบ
               หน้าที่ผ่านทุกกฎแต่ไม่เหมือนแบบจึงถือว่าเสร็จได้

               ตัวตรวจนี้รู้ได้แค่ว่า "เทียบแล้ว" — มีภาพหน้าจริงคู่กับภาพแบบ และบันทึกความต่างไว้
               รู้ไม่ได้ว่าเหมือนจริงไหม ส่วนนั้นยังเป็นงานของคนดู (standard/testing.md ข้อ 4ข) */
            require_once dirname(__DIR__) . '/lib/ProjectDocs.php';

            $design = $c->read('project/DESIGN.md');
            $refs   = ProjectDocs::designRefs($design);
            $bases  = [];

            foreach ($refs as $ref) {
                $bases[ProjectDocs::refBase($ref['ref'])] = true;

                if (!$c->exists($ref['ref'])) {
                    $c->error('project/DESIGN.md', 0,
                        "ตาราง \"แบบของแต่ละหน้า\" ชี้ไปที่ {$ref['ref']} ซึ่งไม่มีอยู่",
                        'นำเข้าภาพด้วย `php scripts/design-refs.php --import <โฟลเดอร์>` หรือแก้ชื่อในตาราง');
                }
            }

            $images = $c->files(array_map(static fn (string $ext): string => ProjectDocs::REFS_DIR . '/*.' . $ext,
                ['png', 'jpg', 'jpeg', 'webp', 'gif']));

            // จัดกลุ่มตามหน้า — ภาพเสริม (@375 · -dark) อยู่กลุ่มเดียวกับภาพหลัก เตือนครั้งเดียวต่อหน้า
            $unmapped = [];

            foreach ($images as $image) {
                $base = ProjectDocs::refBase($image);

                if (!isset($bases[$base])) {
                    $unmapped[$base][] = basename($image);
                }
            }

            foreach ($unmapped as $base => $files) {
                $c->warn('project/DESIGN.md', 0,
                    "ภาพแบบ \"{$base}\" (" . implode(' · ', $files) . ') ยังไม่ได้จับคู่กับหน้าไหน',
                    'เพิ่มแถวในตาราง "## แบบของแต่ละหน้า" — ภาพที่ไม่อยู่ในตารางจะไม่ถูกเปิดดูตอนสร้างหน้า');
            }

            $state = $c->exists('project/STATE.md') ? $c->read('project/STATE.md') : '';

            if (!ProjectDocs::shipped($state)) {
                return;
            }

            foreach ($refs as $ref) {
                if (!$ref['strict']) {
                    continue;
                }

                $built = ProjectDocs::builtFor($ref['ref']);

                if (!$c->exists($built)) {
                    $c->error('project/DESIGN.md', 0,
                        "หน้า {$ref['page']} มีแบบกำหนด แต่ไม่มีภาพหน้าจริงที่ {$built}",
                        'ถ่ายหน้าจริงที่ความกว้างเดียวกับภาพแบบ แล้ววางเทียบกัน (standard/testing.md ข้อ 4ข)');
                }

                if ($ref['diff'] === '') {
                    $c->error('project/DESIGN.md', 0,
                        "หน้า {$ref['page']} ยังไม่ได้บันทึกว่าต่างจากแบบตรงไหน",
                        'เติมช่อง "ต่างจากแบบ" — ทุกความต่างต้องมีเหตุผลที่ผู้ใช้รู้แล้ว หรือเขียน `ไม่มี`');
                }
            }

            foreach (ProjectDocs::refCrops($design) as $crop) {
                if ($crop['temporary']) {
                    $c->warn('project/DESIGN.md', 0,
                        "{$crop['file']} ยังเป็นภาพที่ตัดจากภาพแบบ",
                        'ขอไฟล์ต้นฉบับจากผู้ใช้ แล้วเปลี่ยนสถานะเป็น `ใช้จริง` — หรือแจ้งในการส่งมอบว่ายังเป็นภาพชั่วคราว');
                }
            }
        },
    ],

    'cdn-forbidden' => [
        'title' => 'ห้ามโหลดอะไรจาก CDN',
        'scope' => ['php', 'css'],
        'run'   => function (Ctx $c): void {
            $c->scan(
                ['app/Views/**/*.php', 'public/assets/css/**/*.css'],
                '#(?:src|href)\s*=\s*["\']https?://|@import\s+url\s*\(\s*["\']?https?://#i',
                'โหลดไฟล์จากภายนอก',
                'ดาวน์โหลดมาเก็บใน public/assets/vendor/ หรือ fonts/ แทน — ต้องขออนุญาตผู้ใช้ก่อนเพิ่ม dependency ทุกครั้ง'
            );
        },
    ],

    'animation-fill-mode' => [
        'title' => 'animation ที่แตะ transform ห้ามใช้ fill-mode both/forwards',
        'scope' => ['css'],
        'run'   => function (Ctx $c): void {
            /* ‼️ both/forwards ทำให้ค่าที่คีย์เฟรมกำหนดค้างหลัง animation จบ รวมถึง transform
               ต่อให้ค่าสุดท้ายเป็น matrix(1,0,0,1,0,0) ที่ไม่ทำอะไรเลย
               transform ที่ไม่ใช่ none ก็สร้าง stacking context — ทุกบล็อกกลายเป็นกรงขัง z-index ของลูก

               อาการจริง: แผงปฏิทินเปิดแล้วโดนการ์ดถัดไปทับ ทั้งที่ตั้ง z-index: 30 แล้ว
               มองไม่เห็นจนกว่าจะมี dropdown ตัวแรกในระบบ ซึ่งอาจเป็นอีกหลาย phase ถัดไป */
            foreach ($c->files(['public/assets/css/**/*.css']) as $file) {
                foreach (preg_split('/\R/', $c->read($file)) ?: [] as $index => $text) {
                    if (preg_match('/^\s*(?:\/\*|\*)/', $text) === 1) {
                        continue;
                    }
                    if (preg_match('/\banimation(?:-fill-mode)?\s*:[^;]*\b(both|forwards)\b/i', $text) !== 1) {
                        continue;
                    }

                    $c->warn($file, $index + 1,
                        'animation ใช้ fill-mode ที่ทำให้ค่าค้างหลังจบ',
                        'ใช้ backwards แทน — both/forwards ทำให้ transform ค้างเป็น identity matrix '
                        . 'ซึ่งสร้าง stacking context ถาวร แล้ว dropdown ทุกตัวในระบบถูกทับ',
                        trim($text));
                }
            }
        },
    ],

    'layout-animation' => [
        'title' => 'ห้ามเคลื่อนไหวคุณสมบัติที่ทำให้คำนวณ layout ใหม่',
        'scope' => ['css'],
        'run'   => function (Ctx $c): void {
            /* ‼️ design.md หมวด motion เขียนกฎนี้ไว้เองว่า "เคลื่อนแค่ transform และ opacity เท่านั้น"
               แต่ไม่เคยมีอะไรบังคับ — เป็นกฎขั้น "ข้อความ" มาตลอด

               เคลื่อน width/height/top/margin/padding ทำให้เบราว์เซอร์คำนวณ layout ใหม่ทุกเฟรม
               ผลคือกระตุก ซึ่งเห็นชัดที่สุดกับของที่ขยับถี่ๆ เช่นแถบความคืบหน้าตอนอัปโหลดไฟล์ใหญ่

               ทางแก้: ใช้ transform: scaleX() หรือ translate() แทน แล้วตั้ง transform-origin

               เจอเพราะเครื่องมือภายนอกจับได้ว่าแถบความคืบหน้าของ upload ที่เพิ่งเขียน
               ใช้ transition: width — คือมาตรฐานละเมิดกฎของตัวเองโดยชุดตรวจไม่รู้เรื่อง */
            $layout = 'width|height|top|right|bottom|left|inset|margin|padding|flex-basis|gap';

            foreach ($c->files(['public/assets/css/**/*.css']) as $file) {
                $depth     = 0;
                $inFrames  = false;

                foreach (preg_split('/\R/', $c->read($file)) ?: [] as $index => $text) {
                    if (preg_match('/^\s*(?:\/\*|\*)/', $text) === 1) {
                        continue;
                    }

                    // ติดตามว่าอยู่ใน @keyframes หรือยัง เพื่อตรวจคุณสมบัติข้างในด้วย
                    if (preg_match('/@keyframes\b/i', $text) === 1) {
                        $inFrames = true;
                        $depth    = 0;
                    }

                    $depth += substr_count($text, '{') - substr_count($text, '}');

                    if ($inFrames && $depth <= 0) {
                        $inFrames = false;
                    }

                    $bad = false;

                    // transition ระบุชื่อคุณสมบัติตรงๆ จึงตรวจได้แม่น
                    if (preg_match('/\btransition(?:-property)?\s*:([^;]*)/i', $text, $m) === 1) {
                        $bad = preg_match('/(?<![-\w])(' . $layout . '|all)(?![-\w])/i', $m[1]) === 1;
                    }

                    // ข้างใน @keyframes คุณสมบัติที่เขียนไว้คือสิ่งที่ถูกเคลื่อนจริง
                    if (!$bad && $inFrames) {
                        $bad = preg_match('/^\s*(' . $layout . ')\s*:/i', $text) === 1;
                    }

                    if (!$bad) {
                        continue;
                    }

                    $c->error($file, $index + 1,
                        'เคลื่อนไหวคุณสมบัติที่ทำให้เบราว์เซอร์คำนวณ layout ใหม่ทุกเฟรม',
                        'ใช้ transform (scaleX / translate) หรือ opacity แทน — '
                        . 'design.md หมวด motion กำหนดว่าเคลื่อนได้แค่สองอย่างนี้',
                        trim($text));
                }
            }
        },
    ],

    'native-control-styled' => [
        'title' => 'control ของ OS ต้องถูกปั้นใหม่ ไม่ใช่ปล่อยเป็นของเบราว์เซอร์',
        'run'   => function (Ctx $c): void {
            /* ‼️ กฎข้อ 5.12 ของ components.md มีมาตั้งแต่ 3.2.0 แต่ไม่มีใครทำจริง
               ทุกโปรเจคจึงได้ checkbox/radio สีฟ้าของ Windows และรายการ select ของ OS
               กฎที่ไม่มีตัวตรวจรองรับจะถูกข้ามเสมอ — ข้อนี้คือตัวที่ทำให้มันไม่หายอีก (3.20.0) */
            $required = [
                'public/assets/css/app.css' => [
                    'input[type="checkbox"]' => 'สไตล์ของ checkbox หายไปจาก app.css',
                    'input[type="radio"]'    => 'สไตล์ของ radio หายไปจาก app.css',
                    '.combobox-list'         => 'สไตล์ของรายการ dropdown หายไปจาก app.css',
                ],
                'public/assets/js/app.js' => [
                    'select:not([data-native])' => 'app.js ไม่ได้ครอบ <select> เป็น listbox ของระบบ',
                ],
            ];

            foreach ($required as $file => $needles) {
                if (!$c->exists($file)) {
                    continue;
                }

                $content = $c->read($file);

                foreach ($needles as $needle => $message) {
                    if (str_contains($content, $needle)) {
                        continue;
                    }

                    $c->error($file, 0, $message,
                        'คัดลอกส่วนนี้กลับมาจาก repo มาตรฐาน (standard/components.md ข้อ 5.12 และ 3ข) '
                        . '— ไม่มีมันเมื่อไหร่ ผู้ใช้จะเห็นกล่องสีฟ้าของ Windows ที่ไม่ตามธีมและไม่ตามโหมดมืด');
                }
            }
        },
    ],

    'css-class-exists' => [
        'title' => 'คลาสที่ view อ้างถึงต้องมีอยู่ใน app.css จริง',
        'scope' => ['php', 'css'],
        'run'   => function (Ctx $c): void {
            /* ‼️ CSS ที่ไม่มีอยู่ไม่เคยรายงาน error — typo ของชื่อคลาสจึงเงียบสนิท
               อาการจริง: empty-state.php เรียก icon('inbox-empty', 'icon-lg')
               แต่ app.css ไม่มี .icon-lg เลย ไอคอนสถานะว่างจึงเล็กเท่าไอคอนในปุ่ม */
            $cssPath = 'public/assets/css/app.css';

            if (!$c->exists($cssPath)) {
                return;
            }

            $css = $c->read($cssPath);

            $defined = [];
            if (preg_match_all('/\.(-?[_a-zA-Z][\w-]*)/', $css, $m) > 0) {
                $defined = array_flip($m[1]);
            }

            // คลาสที่ JS เป็นคนใส่ตอนรันจึงไม่มีใน markup — ประกาศไว้ใน CSS อยู่แล้วก็พอ
            $ignore = array_flip(['js', 'no-js']);

            foreach ($c->files(['app/Views/**/*.php']) as $file) {
                foreach (preg_split('/\R/', $c->read($file)) ?: [] as $index => $text) {
                    if (preg_match_all('/class\s*=\s*["\']([^"\'<>]*)["\']/', $text, $matches) === 0) {
                        continue;
                    }

                    foreach ($matches[1] as $attr) {
                        // ข้ามค่าที่ประกอบจาก PHP — ตรวจแบบสถิตไม่ได้
                        if (str_contains($attr, '<?')) {
                            continue;
                        }

                        foreach (preg_split('/\s+/', trim($attr)) ?: [] as $class) {
                            if ($class === '' || isset($defined[$class]) || isset($ignore[$class])) {
                                continue;
                            }

                            $c->warn($file, $index + 1,
                                "คลาส .{$class} ไม่มีอยู่ใน app.css",
                                'พิมพ์ชื่อผิด หรือลืมเขียนสไตล์ให้มัน — CSS ที่ไม่มีอยู่ไม่เคยรายงาน error');
                        }
                    }
                }
            }
        },
    ],

    'contrast' => [
        'title' => 'contrast ของ token ต้องผ่านเกณฑ์ทั้งสองโหมด',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }
            if (!$c->exists('scripts/check-contrast.php') || !$c->exists('project/DESIGN.md')) {
                return;
            }

            /* ‼️ เรียกสคริปต์จริงเสมอ ห้ามเชื่อประโยคใน DESIGN.md
               เคยเขียนไว้ว่า "ตรวจแล้วทุกคู่ผ่าน" ทั้งที่จริงตก 7 คู่
               ประโยคในเอกสารไม่ใช่การตรวจ เพราะไม่มีใครรันซ้ำได้ และไม่มีอะไรบอกว่ามันหมดอายุ */
            $proc = proc_open(
                'php scripts/check-contrast.php --quiet 2>&1',
                [1 => ['pipe', 'w']],
                $pipes,
                $c->root
            );

            if (!is_resource($proc)) {
                return;
            }

            $out = (string) stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            if (proc_close($proc) === 0) {
                return;
            }

            foreach (preg_split('/\R/', trim($out)) ?: [] as $line) {
                if (!str_contains($line, 'ต้องได้')) {
                    continue;
                }

                $c->error('project/DESIGN.md', 0,
                    trim(preg_replace('/\s+/', ' ', $line) ?? $line),
                    'แก้ค่าสีที่ project/DESIGN.md แล้วรัน php scripts/design-sync.php — '
                    . 'ดูรายละเอียดด้วย php scripts/check-contrast.php');
            }
        },
    ],

    'icon-exists' => [
        'title' => 'ชื่อไอคอนที่เรียกใช้ต้องมีอยู่ในชุดกลางจริง',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            /* ‼️ icon() คืนไอคอน info ให้เงียบๆ เมื่อชื่อไม่มีอยู่ ไม่มี error ใดๆ แจ้ง
               พิมพ์ชื่อผิดจึงได้ไอคอนผิดโดยไม่มีใครรู้จนกว่าจะเปิดหน้าดู
               ตระกูลเดียวกับ .icon-lg ที่ถูกเรียกทั้งที่ไม่มีคลาสนั้นใน CSS */
            $source = 'app/Views/partials/icons.php';

            if (!$c->exists($source)) {
                return;
            }

            if (preg_match_all("/'([a-z0-9-]+)'\s*=>\s*'<(?:path|circle|rect)/", $c->read($source), $m) === 0) {
                return;
            }

            $known = array_flip($m[1]);

            foreach ($c->files(['app/**/*.php', 'config/*.php']) as $file) {
                foreach (preg_split('/\R/', $c->read($file)) ?: [] as $index => $text) {
                    $names = [];

                    if (preg_match_all("/\bicon\(\s*'([^']+)'/", $text, $calls) > 0) {
                        $names = $calls[1];
                    }
                    if (preg_match_all("/'icon'\s*=>\s*'([^']+)'/", $text, $registry) > 0) {
                        $names = array_merge($names, $registry[1]);
                    }

                    foreach ($names as $name) {
                        if (isset($known[$name])) {
                            continue;
                        }

                        $c->error($file, $index + 1,
                            "ไอคอน '{$name}' ไม่มีอยู่ในชุดกลาง",
                            'เพิ่มลง app/Views/partials/icons.php หรือใช้ชื่อที่มีอยู่ — '
                            . 'ชื่อที่ไม่มีจะกลายเป็นไอคอน info เงียบๆ โดยไม่มี error แจ้ง');
                    }
                }
            }
        },
    ],

    'page-head-required' => [
        'title' => 'ทุกหน้าต้องมีหัวเพจจาก layout กลาง',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            /* ‼️ ไม่มีหัวเพจ = ทุกอย่างในหน้ามีน้ำหนักเท่ากัน ไม่มีจุดเริ่มให้สายตา
               เป็นสาเหตุอันดับหนึ่งที่ผู้ใช้บอกว่า "หน้าดูแย่" ทั้งที่ทุกกฎอื่นผ่านหมด */
            $partial = 'app/Views/partials/page-head.php';

            if (!$c->exists($partial)) {
                $c->error($partial, 0,
                    'โครงไม่มี partial ของหัวเพจ',
                    'ของที่ไม่มีในโครง AI จะไม่สร้างขึ้นมาเอง เพราะมันไม่รู้ว่าขาด — เหมือน footer');

                return;
            }

            foreach ($c->files(['app/Views/layouts/*.php']) as $file) {
                $text = $c->read($file);

                // layout ของหน้า auth ไม่มีหัวเพจโดยตั้งใจ — หน้าเข้าสู่ระบบมีโลโก้เป็นหัวอยู่แล้ว
                if (str_contains(basename($file), 'auth')) {
                    continue;
                }

                if (!str_contains($text, 'page-head.php')) {
                    $c->error($file, 0,
                        'layout ไม่ได้ include หัวเพจ',
                        'หัวเพจต้องมาจาก layout กลางเหมือน footer ห้ามให้แต่ละหน้าเขียน <h1> เอง');
                }

                if (preg_match('/<h1\b/', (string) preg_replace('/<\?php.*?\?>/s', '', $text)) === 1
                    && !str_contains($text, 'page-head.php')) {
                    $c->error($file, 0,
                        'layout มี <h1> ของตัวเอง',
                        'ชื่อหน้าต้องอยู่ในหัวเพจที่เดียว — มีสองที่แล้วจะซ้ำ '
                        . 'และการเห็นชื่อหน้าตัวเล็กบน topbar ทำให้คนคิดว่ามีหัวเพจแล้ว จึงไม่ทำหัวเพจจริง');
                }
            }
        },
    ],
];

/**
 * ลบคอมเมนต์ออกโดยที่เลขบรรทัดยังตรงเดิม — แทนที่ตัวอักษรด้วยช่องว่าง ไม่ลบบรรทัดทิ้ง
 *
 * ‼️ ต้องคงเลขบรรทัดไว้ ไม่งั้นชุดตรวจจะชี้ผิดบรรทัด
 *    ซึ่งแย่กว่าไม่ชี้เลย เพราะคนจะไปแก้ผิดที่แล้วสรุปว่าชุดตรวจเพี้ยน
 */
function strip_comments_keep_lines(string $code): string
{
    $blank = static fn (string $text): string => preg_replace('/[^\r\n]/u', ' ', $text) ?? $text;

    // CSS ใช้ /* */ อย่างเดียว ส่วน PHP ใช้ tokenizer เพื่อไม่ให้พลาด // และ # ในสตริง
    if (!str_contains($code, '<?php')) {
        return preg_replace_callback('#/\*.*?\*/#s', fn (array $m): string => $blank($m[0]), $code) ?? $code;
    }

    $out = '';

    foreach (token_get_all($code) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            $out .= $blank($token[1]);
            continue;
        }

        $out .= is_array($token) ? $token[1] : $token;
    }

    return $out;
}

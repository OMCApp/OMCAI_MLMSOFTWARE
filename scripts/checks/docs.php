<?php
/**
 * ตรวจความสมบูรณ์ของชุดมาตรฐานเอง
 *
 * ลิงก์ตายอันตรายกว่าที่คิด — AI ถูกสั่งให้ไปอ่านแล้วหาไม่เจอ มันจะเดาเนื้อหาเอาเอง
 * ซึ่งคือต้นเหตุของความไม่สม่ำเสมอที่ workflow นี้ตั้งใจแก้
 */

declare(strict_types=1);

/** ไฟล์ที่เป็น "คำสั่ง" ของมาตรฐาน — ทุกตัวถูกอ่านเพื่อลงมือทำ ไม่ใช่อ่านเป็นประวัติ */
const DOC_FILES = ['CLAUDE.md', 'standard/**/*.md', '.claude/commands/*.md'];

return [

    'dead-command' => [
        'title' => 'คำสั่งที่เอกสารสั่งให้รันต้องมีไฟล์อยู่จริง',
        'scope' => ['md'],
        'run'   => function (Ctx $c): void {
            /* ‼️ ทำไมต้องมีการตรวจนี้แยกจาก dead-link
               `dead-link` ตรวจเฉพาะลิงก์ markdown จึงมองไม่เห็น path ที่เขียนใน backtick
               ผลคือ design.md ข้อ 8.1 สั่งให้รัน `php scripts/make-favicon.php` มาข้ามหลายเวอร์ชัน
               โดยที่ไฟล์นั้นไม่เคยมีอยู่จริง — ทุกคนจึงทำ favicon ด้วยมือ
               ซึ่งคือสิ่งที่ประโยคในกฎข้อนั้นบอกว่าจะไม่เกิด

               คำสั่งที่ชี้ไปหาไฟล์ที่ไม่มี อันตรายกว่าลิงก์ตาย เพราะคนอ่านเชื่อว่ามีเครื่องมือให้ใช้แล้ว
               จึงไม่ไปหาทางอื่น และไม่มีใครรู้ว่ามันหายไปจนกว่าจะมีคนลองรัน */
            foreach ($c->files(DOC_FILES) as $file) {
                $content = $c->read($file);

                // `php <path>.php` ทุกที่ในเอกสาร รวมทั้งในบล็อกโค้ด — ข้าม flag ที่คั่นกลาง
                if (preg_match_all('/\bphp\s+(?:-\S+\s+)*([A-Za-z0-9_.\/-]+\.php)\b/', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[1] as $index => $match) {
                    $target = (string) $match[0];

                    // path บนเครื่อง server ในตัวอย่าง cron ไม่ใช่ของเรา ตรวจไม่ได้
                    if (str_starts_with($target, '/') || str_contains($target, 'path/to')) {
                        continue;
                    }

                    if ($c->exists($target)) {
                        continue;
                    }

                    $line = Ctx::lineAt($content, (int) $m[0][$index][1]);

                    $c->error($file, $line,
                        "สั่งให้รัน `{$target}` ซึ่งไม่มีไฟล์นี้อยู่จริง",
                        'เขียนสคริปต์ให้มีจริง หรือลบคำสั่งออก — '
                        . 'คำสั่งที่รันไม่ได้ทำให้คนเชื่อว่ามีเครื่องมือแล้วจึงไม่หาทางอื่น',
                        Ctx::snippet($content, $line));
                }
            }
        },
    ],

    'feedback-usable' => [
        'title' => 'ทุกข้อใน FEEDBACK.md ต้องมีข้อมูลพอให้คนนอกบทสนทนาเอาไปแก้มาตรฐานได้',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null || !$c->exists('project/FEEDBACK.md')) {
                return;
            }

            /* ‼️ ไฟล์นี้มีค่าก็ต่อเมื่อคนที่ไม่ได้อยู่ในบทสนทนาอ่านแล้วลงมือได้
               บันทึกว่า "ตารางบนมือถือมีปัญหา" แล้วจบ = เขียนไปก็เท่านั้น
               เพราะคนที่แก้ repo มาตรฐานอาจเป็นคนละคน คนละเดือน และไม่มีบริบท

               ‼️ ช่อง "ตรวจด้วยเครื่องได้ไหม" บังคับด้วยเหตุผลเฉพาะ
               ประวัติของมาตรฐานนี้พิสูจน์ซ้ำแล้วว่ากฎที่ไม่มี check.php รองรับจะถูกข้ามเสมอ
               ข้อเสนอที่จบแค่ "เขียนกฎเพิ่ม" จึงเป็นข้อเสนอที่ยังทำไม่เสร็จ
               บังคับให้ตอบ ทำให้คนเขียนต้องคิดเรื่องนี้ตั้งแต่ตอนบันทึก ไม่ใช่ตอนแก้ */
            $required = ['เกิดอะไร', 'ข้อเสนอ', 'ตรวจด้วยเครื่องได้ไหม'];
            $content  = $c->read('project/FEEDBACK.md');
            $lines    = preg_split('/\R/', $content) ?: [];

            $heading = null;
            $line    = 0;
            $body    = '';

            $flush = function () use (&$heading, &$body, &$line, $required, $c): void {
                if ($heading === null) {
                    return;
                }

                $missing = [];

                foreach ($required as $field) {
                    if (!str_contains($body, $field)) {
                        $missing[] = $field;
                    }
                }

                if ($missing !== []) {
                    $c->error('project/FEEDBACK.md', $line,
                        'ข้อนี้ขาดช่อง: ' . implode(' · ', $missing),
                        'ดูรูปแบบที่ standard/flow.md ข้อ 9 — '
                        . 'คนที่เอาไปแก้มาตรฐานอาจเป็นคนละคน คนละเดือน และไม่มีบริบทของบทสนทนานี้',
                        trim($heading));
                }

                $heading = null;
                $body    = '';
            };

            foreach ($lines as $index => $text) {
                if (preg_match('/^##\s+\[[ x-]\]/u', $text) === 1) {
                    $flush();
                    $heading = $text;
                    $line    = $index + 1;
                    continue;
                }

                if ($heading !== null) {
                    $body .= $text . "\n";
                }
            }

            $flush();
        },
    ],

    'mandatory-plugins-wired' => [
        'title' => 'plugin ที่ skills.md บอกว่าบังคับ ต้องอยู่ในรายการที่ setup.php ตรวจ',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }

            /* ‼️ อาการที่ข้อนี้กัน — 3.8.0 ย้าย `impeccable` เป็นกลุ่มบังคับใน skills.md
               และให้ flow.md B4ก เรียกมันก่อนเข้าประตู B แต่ไม่ได้แก้ $required ใน setup.php
               ผลคือ A0 รายงานว่า "plugin กลุ่มบังคับครบ" แล้วเดินต่อ ทั้งที่ flow.md สั่งว่า
               ไม่ครบต้องหยุดรอ · โปรเจคจะไปตันตอน B4ก ซึ่งแก้แพงกว่ามาก

               เป็นหมวดเดียวกับ make-favicon.php (3.6.2) และ config('ui.nav_style') (3.7.0)
               คือของที่ประกาศไว้ในเอกสารแต่ไม่ได้ต่อสายเข้ากับกลไกที่บังคับมันจริง */
            $setup = $c->read('scripts/setup.php');

            if (!preg_match('/\$required\s*=\s*\[(.*?)\];/s', $setup, $m)) {
                $c->error('scripts/setup.php', 0,
                    'หา $required ไม่เจอ',
                    'รายการ plugin บังคับต้องอยู่ในตัวแปรชื่อนี้เพื่อให้เทียบกับ skills.md ได้');

                return;
            }

            preg_match_all("/'([a-z0-9-]+)'\s*=>/", $m[1], $found);
            $wired = $found[1];

            // ตารางกลุ่มบังคับใน skills.md — หัวข้อ "กลุ่มบังคับ" จนถึงหัวข้อถัดไป
            $skills = $c->read('standard/skills.md');

            if (!preg_match('/##[^\n]*กลุ่มบังคับ.*?\n(.*?)(?=\n## )/su', $skills, $m2)) {
                $c->error('standard/skills.md', 0,
                    'หาตารางกลุ่มบังคับไม่เจอ',
                    'ต้องมีหัวข้อที่มีคำว่า "กลุ่มบังคับ" ตามด้วยตารางที่คอลัมน์แรกเป็นชื่อ plugin');

                return;
            }

            preg_match_all('/^\|\s*`([a-z0-9-]+)`\s*\|/m', $m2[1], $m3);

            foreach ($m3[1] as $plugin) {
                if (in_array($plugin, $wired, true)) {
                    continue;
                }

                $c->error('scripts/setup.php', 0,
                    "`{$plugin}` อยู่ในกลุ่มบังคับของ skills.md แต่ไม่อยู่ใน \$required",
                    'เพิ่มลง $required — ไม่งั้น setup.php จะรายงานว่าครบทั้งที่ขาด '
                    . 'แล้วงานจะไปตันตอนที่ขั้นตอนเรียกใช้ plugin ตัวนั้น');
            }

            foreach ($wired as $plugin) {
                if (in_array($plugin, $m3[1], true)) {
                    continue;
                }

                $c->error('standard/skills.md', 0,
                    "setup.php บังคับ `{$plugin}` แต่ skills.md ไม่ได้อยู่ในกลุ่มบังคับ",
                    'ทำให้ตรงกันสองที่ — ผู้ใช้ถูกบล็อกด้วย plugin ที่เอกสารไม่ได้บอกว่าต้องมี');
            }
        },
    ],

    'version-single-source' => [
        'title' => 'เลขเวอร์ชันที่เขียนซ้ำต้องตรงกับ standard/VERSION',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }

            /* ‼️ standard/README.md เตือนเรื่องนี้ไว้เองว่า "ไฟล์นี้เคยเขียน 3.0.0 ค้างไว้ข้ามสองรุ่น
               โดยไม่มีใครเห็น" แล้วสรุปว่าเลขเวอร์ชันมีที่เดียวคือ VERSION

               แต่ความจริงมันถูกเขียนซ้ำอยู่ 3 ที่ (VERSION · CLAUDE.md บรรทัดแรก · หัวข้อบนสุดของ
               CHANGELOG) และ **ไม่มีอะไรเทียบให้ตรงกัน** — ซึ่งคือเงื่อนไขเดิมเป๊ะๆ ที่ทำให้ 3.0.0
               ค้างมาก่อน · การเขียนคำเตือนไว้ไม่ได้กันอะไรเลยถ้าไม่มีตัวจับ */
            if (!$c->exists('standard/VERSION')) {
                $c->error('standard/VERSION', 0,
                    'ไม่มีไฟล์ VERSION',
                    'ไฟล์นี้คือแหล่งเดียวของเลขเวอร์ชัน — ขาดไม่ได้');

                return;
            }

            $version = trim($c->read('standard/VERSION'));

            if (preg_match('/^\d+\.\d+\.\d+$/', $version) !== 1) {
                $c->error('standard/VERSION', 1,
                    "ค่าในไฟล์ไม่ใช่เลขเวอร์ชัน (\"{$version}\")",
                    'ต้องเป็นรูปแบบ x.y.z อย่างเดียว ไม่มีอย่างอื่นในไฟล์');

                return;
            }

            // CLAUDE.md บรรทัดแรก — ไฟล์เดียวที่ตั้งใจให้เขียนซ้ำ เพราะมันโหลดทุก session
            $claude = $c->read('CLAUDE.md');

            if (preg_match('/เวอร์ชันมาตรฐาน:\s*(\d+\.\d+\.\d+)/u', $claude, $m) !== 1) {
                $c->error('CLAUDE.md', 1,
                    'บรรทัดแรกไม่ได้ประกาศเวอร์ชันมาตรฐาน',
                    "ต้องขึ้นต้นด้วย `เวอร์ชันมาตรฐาน: {$version}`");
            } elseif ($m[1] !== $version) {
                $c->error('CLAUDE.md', 1,
                    "ประกาศเวอร์ชัน {$m[1]} แต่ standard/VERSION เป็น {$version}",
                    'แก้ CLAUDE.md ให้ตรง แล้ว generate AGENTS.md ใหม่ '
                    . "(sed 's/CLAUDE\\.md/AGENTS.md/g' CLAUDE.md > AGENTS.md)");
            }

            // หัวข้อบนสุดของ CHANGELOG ต้องเป็นเวอร์ชันที่กำลังปล่อย
            if (preg_match('/^## (\d+\.\d+\.\d+)/m', $c->read('standard/CHANGELOG.md'), $m) !== 1) {
                $c->error('standard/CHANGELOG.md', 0,
                    'หาหัวข้อเวอร์ชันบนสุดไม่เจอ',
                    'ทุกเวอร์ชันต้องมีหัวข้อรูปแบบ `## x.y.z (วันที่)`');
            } elseif ($m[1] !== $version) {
                $c->error('standard/CHANGELOG.md', 0,
                    "หัวข้อบนสุดเป็น {$m[1]} แต่ standard/VERSION เป็น {$version}",
                    'ปล่อยเวอร์ชันใหม่ต้องเพิ่มหัวข้อใน CHANGELOG ด้วย — '
                    . 'เวอร์ชันที่ไม่มีบันทึกว่าเปลี่ยนอะไร เท่ากับไม่มีใครรู้ว่าต้องแก้อะไรตอนอัปเดต');
            }

            /* README ทั้งสองไฟล์ห้ามเขียนซ้ำ ตามที่ standard/README.md ประกาศไว้เอง

               ‼️ README.md ที่รากเคยไม่อยู่ในรายการนี้ แล้วมันค้างที่ 3.6.1 ทันทีที่ปล่อย 3.6.2
                  ซึ่งเป็นอาการเดียวกับ 3.0.0 ที่การตรวจนี้ถูกเขียนขึ้นมาเพื่อกัน
                  บทเรียน: การตรวจที่ไล่ไม่ครบทุกที่ที่เขียนซ้ำ ให้ความมั่นใจผิดๆ แย่กว่าไม่มีเลย */
            foreach (['README.md', 'standard/README.md'] as $readme) {
                if (!$c->exists($readme) || !str_contains($c->read($readme), $version)) {
                    continue;
                }

                $c->error($readme, 0,
                    "เขียนเลขเวอร์ชัน {$version} ซ้ำไว้ในไฟล์",
                    'ไฟล์นี้ห้ามมีเลขเวอร์ชัน — ให้ชี้ไปที่ `standard/VERSION` แทน '
                    . 'เพราะเลขที่เขียนค้างไว้จะไม่มีใครเห็นว่ามันเก่าไปแล้ว');
            }
        },
    ],

    'route-coverage' => [
        'title' => 'กฎที่พูดถึง wireframe ต้องรู้จักช่วง B ทุกเส้นทาง',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }

            /* ‼️ ช่วง B มีสองเส้นทางตั้งแต่ 3.5.0 และ 3.6.0 ตัด wireframe ออกจากเส้นทาง ข
               แต่การแก้ครั้งนั้นไม่ได้ไล่แก้ทุกที่ที่อ้างถึง wireframe
               ที่เหลือค้างอยู่ในเงื่อนไขประตู ซึ่งแปลว่าโปรเจคที่เดินเส้นทาง ข
               เข้าช่วง C ไม่ได้ตามตัวอักษร และ C1 สั่งให้เปิดไฟล์ที่ไม่มีวันมีอยู่

               เป็นความผิดพลาดชนิดเดียวกับที่ 3.6.1 จับได้เอง (แก้เนื้อหาแล้วลืมที่อื่น)
               จึงต้องมีตัวจับ ไม่ใช่หวังว่าคนแก้จะจำได้ครบทุกที่ */
            /* 3.17.0 เพิ่มเส้นทาง ค (ผู้ใช้ออกแบบเองเป็นภาพ) — ตัวตรวจนี้คือสิ่งที่กันไม่ให้เกิดซ้ำแบบ 3.6.0
               ที่เพิ่มเส้นทางแล้วลืมแก้ที่อื่น */
            $routes = ['เส้นทาง ก', 'เส้นทาง ข', 'เส้นทาง ค'];

            foreach ($c->files(DOC_FILES) as $file) {
                $content = $c->read($file);

                if (!str_contains($content, 'wireframe')) {
                    continue;
                }

                $missing = array_values(array_filter($routes, static fn (string $r): bool => !str_contains($content, $r)));

                if ($missing === []) {
                    continue;
                }

                $c->error($file, 0,
                    'พูดถึง wireframe แต่ไม่ได้เอ่ยถึง "' . implode('" · "', $missing) . '"',
                    'wireframe มีเฉพาะเส้นทาง ก — ไฟล์ที่พูดถึงมันต้องบอกด้วยว่าเส้นทาง ข และ ค ใช้อะไรแทน '
                    . 'หรือใช้คำกลาง "แบบที่อนุมัติแล้ว" ตาม flow.md ข้อ 5');
            }
        },
    ],

    'dead-link' => [
        'title' => 'ลิงก์ในไฟล์มาตรฐานต้องชี้ไปหาไฟล์ที่มีอยู่จริง',
        'scope' => ['md'],
        'run'   => function (Ctx $c): void {
            $files = $c->files(['CLAUDE.md', 'standard/**/*.md', 'README.md']);

            foreach ($files as $file) {
                $content = $c->read($file);
                $dir     = dirname($file);

                if (preg_match_all('/\]\(([^)\s#]+)(?:\s[^)]*)?\)/', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[1] as $index => $match) {
                    $target = (string) $match[0];

                    if (preg_match('/^(?:https?:|mailto:|#)/i', $target) === 1) {
                        continue;
                    }

                    // ไฟล์ใน project/ ถูกสร้างตอนรัน /wf จึงยังไม่มีตอนตรวจ
                    $resolved = $dir === '.' ? $target : $dir . '/' . $target;
                    $resolved = preg_replace('#/\./#', '/', $resolved) ?? $resolved;

                    while (preg_match('#[^/]+/\.\./#', $resolved) === 1) {
                        $resolved = preg_replace('#[^/]+/\.\./#', '', $resolved, 1) ?? $resolved;
                    }

                    if (str_starts_with(ltrim($resolved, './'), 'project/')) {
                        continue;
                    }

                    if ($c->exists($resolved)) {
                        continue;
                    }

                    $line = Ctx::lineAt($content, (int) $m[0][$index][1]);

                    $c->error($file, $line,
                        "ลิงก์ชี้ไปหา `{$target}` ซึ่งไม่มีอยู่จริง",
                        'แก้ path หรือสร้างไฟล์ — ลิงก์ตายทำให้ AI เดาเนื้อหาเอาเอง');
                }
            }
        },
    ],

    'claude-md-size' => [
        'title' => 'CLAUDE.md ต้องสั้นพอที่จะไม่ถูกกลืน',
        'scope' => ['md'],
        'run'   => function (Ctx $c): void {
            if (!$c->exists('CLAUDE.md')) {
                $c->error('CLAUDE.md', 0,
                    'ไม่มี CLAUDE.md',
                    'ไฟล์นี้คือกฎเหล็กที่โหลดทุก session — ขาดไม่ได้');

                return;
            }

            $content = $c->read('CLAUDE.md');
            $lines   = substr_count($content, "\n") + 1;
            $bytes   = strlen($content);

            if ($lines > 180) {
                $c->warn('CLAUDE.md', 0,
                    "ยาว {$lines} บรรทัด",
                    'เกิน ~150 บรรทัดแล้วกฎจะเริ่มถูกกลืนและ AI ทำตามไม่ครบ — ย้ายรายละเอียดไปไฟล์เฉพาะด้านใน standard/');
            }

            /* ‼️ ต้องวัดขนาดจริงด้วย ไม่ใช่นับบรรทัดอย่างเดียว
               ไฟล์นี้เป็นภาษาไทยซึ่งบรรทัดยาวกว่าอังกฤษหลายเท่า และ UTF-8 ใช้ 3 ไบต์ต่อตัวอักษร
               164 บรรทัดจึงเป็น 23KB ได้ — เกณฑ์ที่นับบรรทัดอย่างเดียวจึง **วัดคนละหน่วยกับสิ่งที่มันตั้งใจกัน**
               สิ่งที่ทำให้กฎถูกกลืนคือน้ำหนักบริบทที่ต้องโหลดทุก session ไม่ใช่จำนวนบรรทัด

               เพดานตั้งไว้สูงกว่าของจริงตอนตั้งกฎราว 20% เพื่อให้เตือนตอน "กำลังโต"
               ไม่ใช่เตือนทุกครั้งที่แก้คำเดียว */
            if ($bytes > 28000) {
                $kb = round($bytes / 1024, 1);

                $c->warn('CLAUDE.md', 0,
                    "หนัก {$kb} KB ({$lines} บรรทัด)",
                    'ไฟล์นี้โหลดทุก session ก่อนเริ่มงาน — ย้ายรายละเอียดไปไฟล์เฉพาะด้านใน standard/ '
                    . 'แล้วให้ตารางข้อ 2 ชี้ไปแทน');
            }
        },
    ],

    'standard-untouched' => [
        'title' => 'ของมาตรฐาน (standard/ · scripts/ · CLAUDE.md) ห้ามถูกแก้ในโปรเจค',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }

            $manifest = 'standard/.manifest';

            if (!$c->exists($manifest)) {
                $c->warn($manifest, 0,
                    'ยังไม่มีลายเซ็นของชุดมาตรฐาน',
                    'รัน `php scripts/standard-update.php --seal` เพื่อบันทึกลายเซ็นครั้งแรก — จากนั้นการแก้ standard/ ในโปรเจคจะถูกจับได้');

                return;
            }

            // ‼️ ใช้ตัวคำนวณของกลางตัวเดียวกับที่ standard-update.php ใช้เขียนลายเซ็น
            //    เคยมีสองชุด แล้ววันที่แก้วิธี hash ที่ชุดเดียว อีกชุดคิดคนละแบบ
            //    ผลคือรายงานว่ามาตรฐานถูกแก้ทั้งชุด ทั้งที่ไม่มีใครแตะสักไฟล์
            require_once dirname(__DIR__) . '/lib/Manifest.php';

            $expected = Manifest::parse($c->read($manifest));

            // ‼️ อ่านขอบเขตจาก Manifest::MANAGED ที่เดียวกับที่ updater ใช้ทับ
            //    เดิมเขียน standard/** กับ CLAUDE.md ไว้ตรงนี้เอง พอ 3.15.0 ขยายขอบเขตไป scripts/
            //    ถ้าไม่อ่านจากที่เดียวกัน การตรวจนี้จะเฝ้าแค่ครึ่งเดียวของสิ่งที่ updater ทับ
            foreach (Manifest::build($c->root) as $file => $actual) {

                if (!isset($expected[$file])) {
                    $c->error($file, 0,
                        'มีไฟล์เพิ่มเข้ามาในส่วนที่เป็นของมาตรฐาน',
                        'ของเฉพาะโปรเจคห้ามอยู่ใน ' . implode(' · ', Manifest::MANAGED) . ' — ย้ายออก '
                        . 'หรือถ้าเป็นของที่มาตรฐานรุ่นเก่าเคยให้มาแล้วเลิกใช้ ให้ลบ · ต้องเบี่ยงเบนให้บันทึกที่ project/NOTES.md');
                    continue;
                }

                if ($expected[$file] !== $actual) {
                    $c->error($file, 0,
                        'ไฟล์ของมาตรฐานถูกแก้ในโปรเจค',
                        'คืนค่าเดิมด้วย `php scripts/standard-update.php` (ดูก่อน แล้วสั่ง --yes) — '
                        . 'อยากแก้กฎหรือสคริปต์ต้องแก้ที่ repo มาตรฐานกลางแล้วปล่อยเวอร์ชันใหม่');
                }

                unset($expected[$file]);
            }

            foreach (array_keys($expected) as $missing) {
                $c->error($missing, 0,
                    'ไฟล์ของมาตรฐานหายไป',
                    'ดึงกลับด้วย `php scripts/standard-update.php` (ดูก่อน แล้วสั่ง --yes)');
            }
        },
    ],

    'upgrade-coverage' => [
        'title' => 'ทุกเวอร์ชันที่มี ⚠️ ต้องมีสคริปต์อัปเกรด',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null || !$c->exists('standard/CHANGELOG.md')) {
                return;
            }

            /* ‼️ ⚠️ ใน CHANGELOG แปลว่า "โปรเจคที่ใช้เวอร์ชันเก่าต้องแก้"
               ก่อน 3.15.0 มี ⚠️ 23 จุด และไม่มีจุดไหนไปถึงโปรเจคที่อัปเดตเลย
               เพราะ standard-update.php ทับแค่เอกสารแล้วบอกให้ไปอ่าน CHANGELOG เอง
               ตั้งแต่ 3.15.0 เวอร์ชันที่มี ⚠️ ต้องมีขั้นอัปเกรดที่แก้ให้ หรืออย่างน้อยบอกได้ว่าต้องแก้ตรงไหน */
            // นิยามว่าอะไรนับเป็น ⚠️ อยู่ที่ Manifest::breakingNotes() ที่เดียวกับที่ updater ใช้แสดงผล
            require_once dirname(__DIR__) . '/lib/Manifest.php';

            foreach (Manifest::breakingNotes($c->read('standard/CHANGELOG.md')) as $version => $marks) {
                $version = (string) $version;

                if ($marks === [] || version_compare($version, '3.15.0', '<')) {
                    continue;
                }

                $file = "scripts/upgrades/{$version}.php";

                if (!$c->exists($file)) {
                    $c->error('standard/CHANGELOG.md', 0,
                        "เวอร์ชัน {$version} มี ⚠️ แต่ไม่มี {$file}",
                        'เขียนขั้นอัปเกรดที่แก้ให้เอง หรือตรวจแล้วบอกว่าต้องแก้ตรงไหน — '
                        . '⚠️ ที่เป็นแค่ข้อความไม่เคยไปถึงโปรเจคไหนเลย');
                }
            }

            foreach ($c->files(['scripts/upgrades/*.php']) as $file) {
                if (preg_match('/^\d+\.\d+\.\d+$/', basename($file, '.php')) !== 1) {
                    $c->error($file, 0,
                        'ชื่อไฟล์อัปเกรดต้องเป็นเลขเวอร์ชัน x.y.z',
                        'updater เลือกขั้นที่จะรันจากชื่อไฟล์ — ชื่ออื่นจะไม่ถูกรันเลย');
                    continue;
                }

                $steps = (static fn (string $path): mixed => require $path)($c->root . '/' . $file);

                if (!is_array($steps) || $steps === []) {
                    $c->error($file, 0,
                        'ไฟล์อัปเกรดต้องคืน array ของขั้น',
                        'ดูรูปแบบที่หัวไฟล์ scripts/upgrades/3.9.0.php');
                    continue;
                }

                foreach ($steps as $id => $step) {
                    if (!is_array($step) || !is_string($step['title'] ?? null) || !is_callable($step['run'] ?? null)) {
                        $c->error($file, 0,
                            "ขั้น {$id} ต้องมี title และ run",
                            'ดูรูปแบบที่หัวไฟล์ scripts/upgrades/3.9.0.php');
                    }
                }
            }
        },
    ],

    'fast-mode-debt' => [
        'title' => 'phase ที่เลื่อนการตรวจ (โหมดเร็ว) ต้องถูกตรวจย้อนหลังครบก่อนส่ง',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null || !$c->exists('project/PLAN.md')) {
                return;
            }

            /* ‼️ โหมดเร็วเลื่อนการตรวจตอนจบ phase ไปรวมที่ช่วง D — ไม่ได้ตัดทิ้ง
               งานที่เลื่อนไว้คือสิ่งที่ถูกข้ามง่ายที่สุด เพราะไม่มีใครเห็นมันอีกจนกว่าจะถึงตอนส่ง
               และประวัติของมาตรฐานนี้พิสูจน์ซ้ำแล้วว่ากฎที่ไม่มีตัวตรวจรองรับจะถูกข้ามเสมอ

               รูปแบบใน PLAN.md (standard/flow.md ข้อ 15)
                 ดีไซน์      - [x] C4ก ดีไซน์ — เทียบกับแบบแล้ว (โหมดเร็ว)      ← ต้องติ๊กเสมอ ดีไซน์ไม่เลื่อน
                 ยังไม่ตรวจ   - [-] C4ข ทดสอบที่เหลือ — เลื่อนไปช่วง D (โหมดเร็ว)
                 ตรวจแล้ว    - [x] C4ข ทดสอบที่เหลือ — ตรวจแล้วในช่วง D (โหมดเร็ว) · หัว phase เป็น ✅
               คำว่า (โหมดเร็ว) อยู่ต่อทั้งสองสถานะ เพื่อให้ยังรู้ได้ว่า phase ไหนต้องมีผลใน QA-REPORT.md

               ‼️ 3.18.0 แยก C4ก ออกมาเพราะทีมที่ใช้โหมดเร็วรอบแรกได้หน้าที่ไม่ตรงกับ wireframe
                  การตรวจดีไซน์เป็นงานของคนทำตอนจบ phase ไม่ใช่ของผู้ใช้ที่รอที่ประตู D
                  โปรเจคที่ยังใช้รูปแบบเก่า (C4 เฉยๆ) จะเข้าเงื่อนไข "ไม่มี C4ก" แล้วถูกเตือนให้ตรวจดีไซน์ย้อนหลัง */
            require_once dirname(__DIR__) . '/lib/ProjectDocs.php';

            $phases = [];

            foreach (ProjectDocs::phases($c->read('project/PLAN.md')) as $phase) {
                /* C4ก = ดีไซน์ · C4ข (และรูปแบบเก่า "C4" เฉยๆ) = การทดสอบที่เลื่อนได้ */
                $step = static fn (string $mark, callable $match): bool => array_filter($phase['steps'],
                    static fn (array $s): bool => $s['mark'] === $mark
                        && str_contains($s['text'], '(โหมดเร็ว)') && $match($s['text'])) !== [];

                $design = static fn (string $text): bool => str_starts_with($text, 'C4ก');
                $test   = static fn (string $text): bool => str_starts_with($text, 'C4') && !str_starts_with($text, 'C4ก');

                $phases[$phase['number']] = [
                    'heading'    => $phase['heading'],
                    'deferred'   => $step('-', $test),
                    'verified'   => $step('x', $test),
                    'designDone' => $step('x', $design),
                ];
            }

            if ($phases === []) {
                return;
            }

            ksort($phases);
            $first = array_key_first($phases);

            $state   = $c->exists('project/STATE.md') ? $c->read('project/STATE.md') : '';
            $shipped = ProjectDocs::shipped($state);

            $report = $c->exists('project/QA-REPORT.md') ? $c->read('project/QA-REPORT.md') : '';

            foreach ($phases as $number => $phase) {
                $label = "Phase {$number}";

                if ($phase['deferred'] && $number === $first) {
                    $c->error('project/PLAN.md', 0,
                        "{$label} เป็น phase แรกแต่เลื่อนการตรวจไว้",
                        'phase แรกต้องตรวจเต็มเสมอแม้เป็นโหมดเร็ว — มันวาง pattern ที่ทุก phase ลอกต่อ '
                        . 'pattern ผิดที่ไปรู้ตอนช่วง D แปลว่ารื้อทุก phase');
                }

                if ($phase['deferred'] && !$phase['designDone']) {
                    $c->error('project/PLAN.md', 0,
                        "{$label} เลื่อนการตรวจไว้ แต่ไม่มีบรรทัดที่บอกว่าตรวจดีไซน์แล้ว",
                        'โหมดเร็วเลื่อนการตรวจดีไซน์ไม่ได้ (standard/flow.md ข้อ 15) — เทียบหน้าจริงกับแบบที่อนุมัติแล้วทีละบล็อก '
                        . 'ตาม standard/testing.md ข้อ 2.2 แล้วเพิ่ม `- [x] C4ก ดีไซน์ — เทียบกับแบบแล้ว (โหมดเร็ว)` '
                        . '· บล็อกที่หายไปจากแบบไม่มีใครเห็นถ้าไม่เทียบตอนจบ phase');
                }

                if ($phase['deferred'] && str_contains($phase['heading'], '✅')) {
                    $c->error('project/PLAN.md', 0,
                        "{$label} ยังไม่ได้ตรวจ แต่หัวข้อเป็น ✅",
                        'ใช้ 🔨 จนกว่าช่วง D จะตรวจย้อนหลังแล้ว — ✅ แปลว่าผ่านนิยามเสร็จครบ ไม่ใช่แค่สร้างเสร็จ');
                }

                if ($phase['verified'] && preg_match('/^#+[^\n]*Phase\s*' . $number . '(?!\d)/imu', $report) !== 1) {
                    $c->error('project/PLAN.md', 0,
                        "{$label} ติ๊กว่าตรวจแล้วในช่วง D แต่ QA-REPORT.md ไม่มีหัวข้อของ phase นี้",
                        "เพิ่ม `### {$label} — ...` ใต้ `## ตรวจย้อนหลังจากโหมดเร็ว` ใน project/QA-REPORT.md "
                        . 'พร้อมผลและหลักฐาน — ติ๊กโดยไม่มีผลตรวจคือการเลื่อนที่กลายเป็นการข้าม');
                }

                if ($phase['deferred'] && $shipped) {
                    $c->error('project/PLAN.md', 0,
                        "{$label} ยังค้างการตรวจจากโหมดเร็ว แต่โปรเจคผ่านประตู D หรือเข้าช่วง E แล้ว",
                        'ตรวจย้อนหลังตาม standard/testing.md ข้อ 3.0 ให้ครบก่อนส่ง');
                }
            }
        },
    ],

    'qa-issue-state' => [
        'title' => 'ข้อผลตรวจต้องมีสถานะ และข้อต้องแก้ก่อนส่งต้องปิดก่อนผ่านประตู D',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null || !$c->exists('project/QA-REPORT.md')) {
                return;
            }

            /* ‼️ ก่อน 3.16.0 "ห้ามไปช่วง E ถ้ายังมีข้อ critical ค้าง" เป็นประโยคใน flow.md อย่างเดียว
               และ QA-REPORT.md ไม่มีช่องบอกว่าข้อไหนแก้แล้ว — จึงไม่มีทางรู้ด้วยเครื่องว่ามันค้างไหม

               แยก "แก้แล้ว รอตรวจซ้ำ" ออกจาก "ปิด" โดยตั้งใจ
               แก้แล้วยังไม่ใช่ปิด จนกว่าจะตรวจซ้ำผ่าน — ไม่งั้นข้อที่แค่ลดอาการจะดูเหมือนจบแล้ว (CLAUDE.md ข้อ 4) */
            require_once dirname(__DIR__) . '/lib/ProjectDocs.php';

            $issues  = ProjectDocs::qaIssues($c->read('project/QA-REPORT.md'));
            $state   = $c->exists('project/STATE.md') ? $c->read('project/STATE.md') : '';
            $shipped = ProjectDocs::shipped($state);

            foreach ($issues as $issue) {
                $label = trim(($issue['number'] !== '' ? "ข้อ {$issue['number']} " : '') . $issue['title']);

                if ($issue['severity'] !== 'accepted' && $issue['stateText'] === '') {
                    $c->warn('project/QA-REPORT.md', 0,
                        "\"{$label}\" ไม่มีบรรทัดสถานะ",
                        'เพิ่ม `- **สถานะ:** เจอ · กำลังแก้ · แก้แล้ว รอตรวจซ้ำ · ปิด` — บอร์ดถือว่าข้อที่ไม่มีสถานะคือเพิ่งเจอ');
                }

                if ($issue['state'] === 'closed' && $issue['severity'] !== 'accepted'
                    && trim($issue['fields']['แก้ใน'] ?? '') === '') {
                    $c->warn('project/QA-REPORT.md', 0,
                        "\"{$label}\" ปิดแล้วแต่ไม่บอกว่าแก้ใน commit ไหน",
                        'เพิ่ม `- **แก้ใน:** <hash>` · ถ้าปิดเพราะไม่ใช่ปัญหาจริง ให้เขียน `- **แก้ใน:** ไม่ต้องแก้ — เหตุผล`');
                }

                if ($shipped && $issue['severity'] === 'critical' && $issue['state'] !== 'closed') {
                    $c->error('project/QA-REPORT.md', 0,
                        "\"{$label}\" ต้องแก้ก่อนส่ง แต่ยังเป็น \""
                        . ProjectDocs::ISSUE_STATES[$issue['state']] . '" ทั้งที่ผ่านประตู D หรือเข้าช่วง E แล้ว',
                        'แก้ แล้วตรวจซ้ำให้ผ่าน แล้วค่อยเปลี่ยนเป็น `ปิด` — ห้ามผ่านประตู D ถ้ายังมีข้อต้องแก้ก่อนส่งค้าง');
                }
            }
        },
    ],

    'board-linked' => [
        'title' => 'โปรเจคที่เข้าช่วงลงมือแล้วต้องมีบอร์ดความคืบหน้า',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null || !$c->exists('project/STATE.md')) {
                return;
            }

            /* เตือน ไม่บล็อก — Artifact มีเฉพาะ session ที่ต่อบัญชี claude.ai
               AI ตัวอื่นที่ทำงานในโปรเจคเดียวกันจึงอาจสร้างบอร์ดไม่ได้อย่างชอบธรรม
               ทางออกคือเขียน `บอร์ด: ไม่มี — เหตุผล` ซึ่งทำให้ข้อนี้เงียบ และคนที่มารับช่วงรู้ว่าไม่ได้ลืม */
            $state = $c->read('project/STATE.md');

            if (preg_match('/\*\*ช่วง:\*\*\s*[CDE](?![A-Za-z])/u', $state) !== 1) {
                return;
            }

            if (preg_match('/^\s*-\s*\*\*บอร์ด:\*\*\s*(\S.*)$/mu', $state, $m) === 1
                && (preg_match('#https?://\S+#', $m[1]) === 1 || str_starts_with(trim($m[1]), 'ไม่มี'))) {
                return;
            }

            $c->warn('project/STATE.md', 0,
                'เข้าช่วงลงมือแล้วแต่ยังไม่มีลิงก์บอร์ด',
                '`php scripts/board.php` แล้วเผยแพร่เป็น Artifact · เขียน `- **บอร์ด:** <ลิงก์>` ลง STATE.md '
                . '— ถ้าสร้างไม่ได้ให้เขียน `- **บอร์ด:** ไม่มี — เหตุผล` (standard/flow.md ข้อ 16)');
        },
    ],

    'agents-md-in-sync' => [
        'title' => 'AGENTS.md ต้องตรงกับ CLAUDE.md',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }
            if (!$c->exists('AGENTS.md') || !$c->exists('CLAUDE.md')) {
                return;
            }

            // AGENTS.md คือ CLAUDE.md ที่เปลี่ยนชื่อไฟล์ตัวเองเท่านั้น สำหรับ AI ตัวอื่นที่อ่านชื่อนี้
            // ‼️ ไม่มีอะไรจับความต่างมาก่อนหน้านี้ ทั้งสองไฟล์จึงเพี้ยนจากกันได้แบบเงียบสนิท
            //    และผลคือ AI คนละตัวทำงานคนละกฎ ทั้งที่อยู่โปรเจคเดียวกัน
            $eol      = static fn (string $s): string => str_replace(["\r\n", "\r"], "\n", $s);
            $expected = str_replace('CLAUDE.md', 'AGENTS.md', $eol($c->read('CLAUDE.md')));

            if ($eol($c->read('AGENTS.md')) !== $expected) {
                $c->error('AGENTS.md', 0,
                    'AGENTS.md ไม่ตรงกับ CLAUDE.md',
                    "generate ใหม่: sed 's/CLAUDE\\.md/AGENTS.md/g' CLAUDE.md > AGENTS.md"
                    . ' — แก้กฎต้องแก้ที่ CLAUDE.md เท่านั้น');
            }
        },
    ],

    'checkbox-honest' => [
        'title' => 'ห้ามติ๊ก [x] กับขั้นที่ตัดสินว่าไม่ทำ',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }

            /* ‼️ การตัดสินว่า "ไม่ทำ" ไม่ใช่ "ทำแล้ว"
               อาการจริง: ติ๊ก [x] C3 animation แล้ววงเล็บกำกับว่า "ยังไม่มี motion เพราะ..."
               เหตุผลไม่ผิด แต่ผลคือ PLAN.md บอกว่าทำครบแล้วทั้งที่ยังไม่ได้ทำ
               ปนกันเมื่อไหร่ รายงานความคืบหน้าเชื่อถือไม่ได้ทั้งแผน — ให้ใช้ [-] แทน */
            $signals = ['ยังไม่', 'ไม่ได้ทำ', 'ข้ามไป', 'ไม่ทำ', 'skip'];

            foreach ($c->files(['project/*.md']) as $file) {
                foreach (preg_split('/\R/', $c->read($file)) ?: [] as $index => $text) {
                    if (preg_match('/^\s*[-*]?\s*\[x\]/i', $text) !== 1) {
                        continue;
                    }

                    foreach ($signals as $signal) {
                        if (!str_contains($text, $signal)) {
                            continue;
                        }

                        $c->error($file, $index + 1,
                            "ติ๊ก [x] แต่บรรทัดเดียวกันบอกว่า \"{$signal}\"",
                            'ขั้นที่ตัดสินว่าไม่ทำให้ใช้ [-] พร้อมเหตุผล — '
                            . '[x] แปลว่าทำแล้วเท่านั้น',
                            trim($text));

                        break;
                    }
                }
            }
        },
    ],

    'project-docs' => [
        'title' => 'เอกสารของโปรเจคต้องตรงกับความจริง',
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null || !$c->exists('project/PLAN.md')) {
                return;
            }

            /* ‼️ นับเฉพาะบรรทัดหัวข้อ ห้ามนับทั้งไฟล์
               เดิมใช้ substr_count ทั้งไฟล์ ซึ่งนับบรรทัดคำอธิบายสัญลักษณ์ไปด้วย
               และบรรทัดนั้นคือบรรทัดที่ **แม่แบบใน flow.md ข้อ 9 บอกให้ใส่เอง**

                   > อัปเดตล่าสุด: [วันที่] | ⬜ ยังไม่เริ่ม · 🔄 กำลังทำ · ✅ เสร็จแล้ว

               ผลคือใครก็ตามที่ทำตามแม่แบบเป๊ะๆ แล้วมาร์ค phase ที่กำลังทำ 1 อัน
               จะได้ 2 ทันทีแล้ว fail ตั้งแต่ phase แรก — เลี่ยงได้ทางเดียวคือ
               ไม่ใส่บรรทัดที่แม่แบบบอกให้ใส่ ซึ่งเป็นกฎที่ขัดกันเอง

               เจอตอนรันมาตรฐานจริงตั้งแต่ต้นเป็นครั้งแรก */
            $lines   = preg_split('/\R/', $c->read('project/PLAN.md')) ?: [];
            $working = 0;

            foreach ($lines as $line) {
                if (str_starts_with(ltrim($line), '#') && str_contains($line, '🔄')) {
                    $working++;
                }
            }

            if ($working > 1) {
                $c->error('project/PLAN.md', 0,
                    "มี phase ที่กำลังทำอยู่ {$working} รายการ",
                    'ห้ามมี 🔄 เกิน 1 รายการ — ทำทีละ phase เท่านั้น');
            }

            if (!$c->exists('project/STATE.md')) {
                $c->error('project/STATE.md', 0,
                    'มี PLAN.md แล้วแต่ไม่มี STATE.md',
                    'STATE.md คือสิ่งที่ทำให้ session ใหม่ทำงานต่อได้ — ขาดไม่ได้');
            }
        },
    ],
];

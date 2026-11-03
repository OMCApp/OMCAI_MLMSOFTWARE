<?php
/**
 * ตรวจกฎฐานข้อมูลใน standard/rule.md และ features/audit-log.md
 */

declare(strict_types=1);

return [

    'sql-charset' => [
        'title' => 'ทุกไฟล์ .sql ต้องขึ้นต้นด้วย SET NAMES utf8mb4',
        'scope' => ['sql'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['database/**/*.sql']) as $file) {
                $content = $c->read($file);

                // ตัดคอมเมนต์และบรรทัดว่างข้างบนออกก่อน แล้วดูว่าคำสั่งแรกคือ SET NAMES หรือไม่
                // ห้ามดูแค่ N ตัวอักษรแรก เพราะคอมเมนต์ภาษาไทยยาวกว่าที่คิดเสมอ
                $body = (string) preg_replace('/^(?:\s*(?:--|#)[^\n]*\n|\s*\n)+/', '', $content);

                if (preg_match('/^\s*SET\s+NAMES\s+utf8mb4/i', $body) === 1) {
                    continue;
                }

                $c->error($file, 1,
                    'ไม่มี SET NAMES utf8mb4 ที่ต้นไฟล์',
                    'เพิ่ม `SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;` บรรทัดแรก — ถ้าไม่มี แล้วมีคนรัน mysql < file.sql บน Windows ภาษาไทยจะเพี้ยนโดยไม่มี error แจ้งเลย');
            }
        },
    ],

    'no-thai-values' => [
        'title' => 'ค่า status/type/role ต้องเก็บเป็น key ภาษาอังกฤษ',
        'scope' => ['sql'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['database/**/*.sql']) as $file) {
                $content = $c->read($file);

                if (preg_match_all('/[\x{0E00}-\x{0E7F}]+/u', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                $reported = [];

                foreach ($m[0] as $match) {
                    $line = Ctx::lineAt($content, (int) $match[1]);

                    if (isset($reported[$line])) {
                        continue;
                    }
                    $reported[$line] = true;

                    $snippet = Ctx::snippet($content, $line);

                    // คอมเมนต์ภาษาไทยใน .sql อนุญาตได้
                    if (preg_match('/^\s*(--|#|\/\*)/', $snippet) === 1) {
                        continue;
                    }

                    $level = preg_match('/\b(ENUM|DEFAULT|INSERT|VALUES)\b/i', $snippet) === 1 ? 'error' : 'warn';

                    $c->{$level}($file, $line,
                        'มีข้อความไทยอยู่ในคำสั่ง SQL',
                        'เก็บเป็น key อังกฤษ (`done` ไม่ใช่ `เสร็จสิ้น`) แล้วแปลตอนแสดง — ถ้าเก็บข้อความแสดงผล วันที่แก้คำหรือเพิ่มภาษา ข้อมูลเก่าทั้งตารางใช้ไม่ได้',
                        $snippet);
                }
            }
        },
    ],

    'no-float-money' => [
        'title' => 'ห้ามใช้ FLOAT/DOUBLE กับตัวเลขที่ต้องแม่นยำ',
        'scope' => ['sql'],
        'run'   => function (Ctx $c): void {
            $c->scan(
                ['database/**/*.sql'],
                '/\b(FLOAT|DOUBLE)\b/i',
                'ใช้ FLOAT/DOUBLE ในโครงสร้างตาราง',
                'ใช้ DECIMAL หรือเก็บเป็นจำนวนเต็มหน่วยย่อย (สตางค์) — FLOAT ปัดเศษเพี้ยนแล้วยอดไม่ตรง'
            );
        },
    ],

    'audit-log-immutable' => [
        'title' => 'ตาราง audit_logs ห้ามมี updated_at หรือ deleted_at',
        'scope' => ['sql'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['database/**/*.sql']) as $file) {
                $content = $c->read($file);

                if (preg_match('/CREATE\s+TABLE[^;]*\baudit_logs\b[^;]*;/is', $content, $m, PREG_OFFSET_CAPTURE) !== 1) {
                    continue;
                }

                $table = (string) $m[0][0];
                $line  = Ctx::lineAt($content, (int) $m[0][1]);

                foreach (['updated_at', 'deleted_at'] as $column) {
                    if (preg_match('/\b' . $column . '\b/i', $table) === 1) {
                        $c->error($file, $line,
                            "ตาราง audit_logs มีคอลัมน์ {$column}",
                            'audit log ต้องแก้และลบไม่ได้ — ถ้าแก้ได้ก็ไม่มีความหมาย เพราะคนที่ทำผิดลบร่องรอยตัวเองได้');
                    }
                }
            }
        },
    ],

    'soft-delete' => [
        'title' => 'ตารางที่ผู้ใช้ลบข้อมูลได้ควรมี deleted_at',
        'scope' => ['sql'],
        'run'   => function (Ctx $c): void {
            $exempt = ['audit_logs', 'migrations', 'login_attempts', 'sessions'];

            foreach ($c->files(['database/migrations/*.sql']) as $file) {
                $content = $c->read($file);

                if (preg_match_all('/CREATE\s+TABLE[^;]*;/is', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[0] as $match) {
                    $table = (string) $match[0];

                    if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $table, $name) !== 1) {
                        continue;
                    }
                    if (in_array(strtolower($name[1]), $exempt, true)) {
                        continue;
                    }
                    if (preg_match('/\bdeleted_at\b/i', $table) === 1) {
                        continue;
                    }

                    $c->warn($file, Ctx::lineAt($content, (int) $match[1]),
                        "ตาราง `{$name[1]}` ไม่มีคอลัมน์ deleted_at",
                        'soft delete เป็นค่าเริ่มต้น — ลบจริงแล้ว audit log จะชี้ไปหาของที่ไม่มีอยู่ · ถ้าตั้งใจลบจริง ให้ระบุใน project/SCOPE.md');
                }
            }
        },
    ],

    'foreign-key-index' => [
        'title' => 'ทุกคอลัมน์ที่ลงท้ายด้วย _id ต้องมี index',
        'scope' => ['sql'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['database/migrations/*.sql']) as $file) {
                $content = $c->read($file);

                if (preg_match_all('/CREATE\s+TABLE[^;]*;/is', $content, $m, PREG_OFFSET_CAPTURE) === 0) {
                    continue;
                }

                foreach ($m[0] as $match) {
                    $table = (string) $match[0];
                    $line  = Ctx::lineAt($content, (int) $match[1]);

                    if (preg_match_all('/`?(\w+_id)`?\s+(?:BIGINT|INT|SMALLINT)/i', $table, $cols) === 0) {
                        continue;
                    }

                    // เก็บรายชื่อคอลัมน์ที่อยู่ในนิยาม index ทุกตัว
                    // ต้องอ่านข้างในวงเล็บทั้งก้อน เพราะ composite index มีหลายคอลัมน์คั่นด้วยจุลภาค
                    $indexedColumns = [];

                    if (preg_match_all('/(?:PRIMARY\s+KEY|UNIQUE(?:\s+KEY)?|FOREIGN\s+KEY|KEY|INDEX)\s*[^(]*\(([^)]*)\)/i',
                        $table, $indexes) > 0) {
                        foreach ($indexes[1] as $group) {
                            foreach (explode(',', $group) as $part) {
                                $indexedColumns[] = strtolower(trim($part, " \t`\r\n"));
                            }
                        }
                    }

                    foreach (array_unique($cols[1]) as $column) {
                        $indexed = in_array(strtolower($column), $indexedColumns, true);

                        if (!$indexed) {
                            $c->warn($file, $line,
                                "คอลัมน์ `{$column}` ไม่มี index",
                                'ทุก foreign key และคอลัมน์ที่ใช้กรอง/เรียง ต้องมี index — ปัญหานี้มองไม่เห็นตอนมีข้อมูล 3 แถว');
                        }
                    }
                }
            }
        },
    ],

    'migration-numbering' => [
        'title' => 'ไฟล์ migration ต้องเรียงด้วยเลขนำหน้าและห้ามซ้ำ',
        'scope' => ['sql'],
        'run'   => function (Ctx $c): void {
            if ($c->onlyFile !== null) {
                return;
            }

            $seen = [];

            foreach ($c->files(['database/migrations/*.sql']) as $file) {
                $name = basename($file);

                if (preg_match('/^(\d{3,})_/', $name, $m) !== 1) {
                    $c->error($file, 0,
                        'ชื่อไฟล์ไม่ได้ขึ้นต้นด้วยเลขลำดับ',
                        'ตั้งชื่อแบบ 001_create_users_table.sql เพื่อให้รันตามลำดับที่ถูกต้อง');
                    continue;
                }

                if (isset($seen[$m[1]])) {
                    $c->error($file, 0,
                        "เลขลำดับ {$m[1]} ซ้ำกับ {$seen[$m[1]]}",
                        'เลขซ้ำทำให้ลำดับการรันไม่แน่นอนระหว่างเครื่อง');
                    continue;
                }

                $seen[$m[1]] = $file;
            }
        },
    ],

    'n-plus-one' => [
        'title' => 'ห้าม query อยู่ในลูป',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            /* ‼️ วนลูปแล้ว query ในลูป = N+1
               ข้อมูล 3 แถวตอนพัฒนามองไม่เห็นอะไร พอขึ้นจริง 500 แถวกลายเป็น 501 query
               testing.md สั่งให้หาข้อนี้มาตลอด แต่สั่งให้หาตอนช่วง D
               ซึ่งเป็นตอนที่เขียนเสร็จทั้งระบบแล้ว — เจอตอนนั้นแปลว่ารื้อ ไม่ใช่แก้

               ตรวจแบบหยาบด้วยการนับวงเล็บปีกกา จึงจับได้เฉพาะรูปที่ชัดเจน
               ตัวที่ซับซ้อนกว่านี้ยังต้องพึ่งตัวนับ query ใน footer โหมด development */
            foreach ($c->files(['app/**/*.php', 'core/**/*.php']) as $file) {
                if (str_contains($file, 'core/Database.php')) {
                    continue;
                }

                $lines = preg_split('/\R/', $c->read($file)) ?: [];
                $depth = 0;
                $loops = [];

                foreach ($lines as $index => $text) {
                    if (preg_match('/\b(foreach|while|for)\s*\(/', $text) === 1) {
                        $loops[] = $depth;
                    }

                    if (preg_match('/Database::(run|select|selectOne)\s*\(/', $text) === 1 && $loops !== []) {
                        $c->warn($file, $index + 1,
                            'มี query อยู่ในลูป — เสี่ยงเป็น N+1',
                            'ดึงทีเดียวด้วย WHERE id IN (...) แล้วค่อยจับคู่ใน PHP '
                            . 'หรือใช้ JOIN — query ที่วิ่งตามจำนวนแถวคือ N+1',
                            trim($text));
                    }

                    $depth += substr_count($text, '{') - substr_count($text, '}');

                    while ($loops !== [] && $depth <= end($loops)) {
                        array_pop($loops);
                    }
                }
            }
        },
    ],

    'placeholder-reuse' => [
        'title' => 'ห้ามใช้ placeholder ชื่อเดียวกันซ้ำใน query เดียว',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            /* ‼️ ATTR_EMULATE_PREPARES = false ทำให้ MySQL นับ marker ทีละตัว
               แล้วต้องการค่าผูกเท่าจำนวนครั้งที่ marker โผล่ ไม่ใช่เท่าจำนวนชื่อ
               ใช้ชื่อซ้ำจึงได้ SQLSTATE[HY093] ทุกครั้งที่รัน

               อาการจริง: `(:except IS NULL OR id <> :except)` ทำให้เพิ่ม/แก้ผู้ใช้ 500 ทุกครั้ง
               และไม่มีอะไรจับได้เลยจนกว่าจะมีคนกดใช้จริง */
            foreach ($c->files(['app/**/*.php', 'core/**/*.php']) as $file) {
                $content = $c->read($file);

                // จับสตริงที่หน้าตาเป็น SQL แล้วนับ marker ในนั้น
                if (preg_match_all(
                    '/([\'"])((?:SELECT|INSERT|UPDATE|DELETE)\b.*?)\1/is',
                    $content,
                    $matches,
                    PREG_OFFSET_CAPTURE
                ) === 0) {
                    continue;
                }

                foreach ($matches[2] as $match) {
                    [$sql, $offset] = $match;

                    if (preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $sql, $names) === 0) {
                        continue;
                    }

                    $counts = array_count_values($names[1]);

                    foreach ($counts as $name => $times) {
                        if ($times < 2) {
                            continue;
                        }

                        $line = substr_count(substr($content, 0, $offset), "\n") + 1;

                        $c->error($file, $line,
                            ":{$name} โผล่ {$times} ครั้งใน query เดียว",
                            'ต่อเงื่อนไขเฉพาะตอนที่ต้องใช้จริง หรือตั้งชื่อ placeholder แยกกัน '
                            . '— ชื่อซ้ำทำให้ query พังด้วย SQLSTATE[HY093] ทุกครั้งที่รัน');
                    }
                }
            }
        },
    ],
];

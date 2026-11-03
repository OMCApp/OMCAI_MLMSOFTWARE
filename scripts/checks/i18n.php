<?php
/**
 * ตรวจกฎใน standard/features/i18n.md
 */

declare(strict_types=1);

/**
 * แบนอาเรย์คำแปลให้เป็น key แบบจุด เพื่อเทียบข้ามภาษาได้
 *
 * @param  array<mixed> $data
 * @return array<string, mixed>
 */
function lang_flatten(array $data, string $prefix = ''): array
{
    $out = [];

    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

        if (is_array($value)) {
            $out += lang_flatten($value, $path);
            $out[$path . '.*'] = '__group__';
            continue;
        }

        $out[$path] = $value;
    }

    return $out;
}

return [

    'no-hardcoded-text' => [
        'title' => 'ทุกข้อความที่ผู้ใช้เห็นต้องผ่าน __()',
        'scope' => ['php'],
        'run'   => function (Ctx $c): void {
            foreach ($c->files(['app/**/*.php', 'core/**/*.php']) as $file) {
                if (str_contains($file, 'app/Views/manuals/')) {
                    continue; // เนื้อความคู่มือเป็นข้อยกเว้น
                }

                $content = $c->read($file);
                if ($content === '') {
                    continue;
                }

                // ใช้ token_get_all เพื่อไม่ให้คอมเมนต์ภาษาไทยถูกจับ
                $tokens = @token_get_all($content);
                if (!is_array($tokens)) {
                    continue;
                }

                foreach ($tokens as $token) {
                    if (!is_array($token)) {
                        continue;
                    }

                    [$id, $text, $line] = $token;

                    if (!in_array($id, [T_INLINE_HTML, T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                        continue;
                    }

                    // ตรวจเฉพาะอักษรไทย — ข้อความอังกฤษที่เป็น key หรือ class ไม่ควรถูกจับ
                    if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $text) !== 1) {
                        continue;
                    }

                    // เอาเฉพาะบรรทัดที่มีภาษาไทยจริง ไม่ใช่ทั้งก้อน HTML
                    $sample = '';
                    foreach (preg_split('/\R/', $text) ?: [] as $row) {
                        if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $row) === 1) {
                            $sample = trim($row);
                            break;
                        }
                    }

                    $c->error($file, (int) $line,
                        'มีข้อความภาษาไทยเขียนตรงในโค้ด',
                        'ย้ายเข้า lang/th.php แล้วเรียกผ่าน __() — เพิ่มภาษาทีหลังจะได้ไม่ต้องไล่แก้ทุกหน้า',
                        mb_substr($sample, 0, 90));
                }
            }
        },
    ],

    'lang-parity' => [
        'title' => 'ไฟล์ภาษาทุกไฟล์ต้องมี key ครบตรงกัน',
        'run'   => function (Ctx $c): void {
            $files = $c->files(['lang/*.php']);

            if ($files === []) {
                $c->error('lang/', 0,
                    'ไม่มีโฟลเดอร์ lang/ หรือไม่มีไฟล์ภาษาเลย',
                    'มาตรฐานบังคับอย่างน้อย lang/th.php และ lang/en.php ทุกโปรเจค');

                return;
            }

            foreach (['lang/th.php', 'lang/en.php'] as $required) {
                if (!in_array($required, $files, true)) {
                    $c->error($required, 0,
                        'ขาดไฟล์ภาษาที่บังคับ',
                        'th และ en เป็นค่าบังคับทุกโปรเจค แม้ระบบจะใช้ภาษาไทยอย่างเดียวก็ตาม');
                }
            }

            /** @var array<string, array<string, mixed>> $sets */
            $sets = [];

            foreach ($files as $file) {
                $data = @include $c->root . '/' . $file;

                if (!is_array($data)) {
                    $c->error($file, 0,
                        'ไฟล์ภาษาไม่ได้ return array',
                        'ไฟล์ภาษาต้อง return array ของคำแปล');
                    continue;
                }

                $sets[$file] = lang_flatten($data);
            }

            if (count($sets) < 2) {
                return;
            }

            $reference = $sets['lang/th.php'] ?? reset($sets);
            $refName   = isset($sets['lang/th.php']) ? 'lang/th.php' : (string) array_key_first($sets);

            $allKeys = [];
            foreach ($sets as $keys) {
                $allKeys += $keys;
            }

            foreach ($sets as $file => $keys) {
                foreach (array_keys($allKeys) as $key) {
                    if (str_ends_with($key, '.*')) {
                        // โครงสร้างไม่ตรงกัน — ภาษาหนึ่งเป็นกลุ่ม อีกภาษาเป็นข้อความ
                        $base = substr($key, 0, -2);

                        if (isset($keys[$base]) && !isset($keys[$key])) {
                            $c->error($file, 0,
                                "key `{$base}` เป็นข้อความ แต่ในไฟล์อื่นเป็นกลุ่มย่อย",
                                'โครงสร้างต้องตรงกันทุกภาษา ไม่งั้นการเรียก key ย่อยจะพังเฉพาะบางภาษา');
                        }
                        continue;
                    }

                    if (!array_key_exists($key, $keys)) {
                        $c->error($file, 0,
                            "ขาด key `{$key}`",
                            'เพิ่มคำแปลให้ครบ — key ที่หายทำให้ปุ่มไม่มีข้อความบนเครื่องจริงโดยไม่มีใครรู้');
                        continue;
                    }

                    $value = $keys[$key];

                    if (is_string($value) && trim($value) === '') {
                        $c->error($file, 0,
                            "key `{$key}` มีค่าว่าง",
                            'ยังไม่ได้แปล — ค่าว่างหาไม่เจอเพราะ key มีอยู่');
                    }

                    if (is_string($value) && preg_match('/<[a-z][^>]*>/i', $value) === 1) {
                        $c->error($file, 0,
                            "key `{$key}` มีแท็ก HTML",
                            'ไฟล์ภาษาต้องเป็นข้อความเปล่า ไม่งั้นสัญญาว่า __() แสดงได้ตรงๆ อย่างปลอดภัยจะใช้ไม่ได้');
                    }

                    // placeholder ต้องตรงกับภาษาอ้างอิง
                    if ($file !== $refName && is_string($value) && isset($reference[$key]) && is_string($reference[$key])) {
                        preg_match_all('/:(\w+)/', $reference[$key], $a);
                        preg_match_all('/:(\w+)/', $value, $b);

                        $missing = array_diff($a[1], $b[1]);

                        if ($missing !== []) {
                            $c->error($file, 0,
                                "key `{$key}` ขาด placeholder: " . implode(', ', array_map(fn ($p) => ':' . $p, $missing)),
                                'ค่าที่ส่งเข้าไปจะหายเงียบๆ โดยไม่มี error ให้เห็น');
                        }
                    }
                }
            }
        },
    ],

    'thai-key' => [
        'title' => 'ห้ามใช้ข้อความไทยเป็น key ของคำแปล',
        'run'   => function (Ctx $c): void {
            $c->scan(
                ['lang/*.php'],
                '/[\'"][^\'"]*[\x{0E00}-\x{0E7F}][^\'"]*[\'"]\s*=>/u',
                'ใช้ข้อความไทยเป็น key',
                'key ต้องเป็นภาษาอังกฤษ snake_case — วันที่แก้คำไทย key จะพังทั้งระบบพร้อมกัน'
            );
        },
    ],
];

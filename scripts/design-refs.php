<?php
/**
 * ภาพแบบของผู้ใช้ (เส้นทาง ค) — นำเข้า ดูดสีจากพิกเซลจริง และดูว่าหน้าไหนเทียบกับแบบแล้ว
 *
 *   php scripts/design-refs.php --import "D:/designs/project-x"   คัดลอกทั้งโฟลเดอร์เข้า project/design/refs/
 *   php scripts/design-refs.php --palette refs/login.png           สีที่ใช้มากที่สุดในภาพ
 *   php scripts/design-refs.php --pick refs/login.png 120 340      สีของพิกเซลตรงจุดนั้น (เฉลี่ย 5×5)
 *   php scripts/design-refs.php --status                           หน้าไหนมีแบบ · เทียบแล้วหรือยัง · ภาพที่ยังไม่จับคู่
 *
 * ‼️ ทำไมต้องคัดลอก ไม่ใช่อ้าง path ที่ผู้ใช้ส่งมา
 *    path ในเครื่องของผู้ใช้ใช้ได้เฉพาะ session นั้นบนเครื่องนั้น
 *    session ใหม่หรือเพื่อนในทีมบนอีกเครื่องเปิดไม่เจอ แล้วภาพแบบหายไปเหมือนส่งในแชท
 *    ซึ่งคือสาเหตุที่หน้าออกมาไม่เหมือนแบบ — AI ไม่เคยเห็นภาพตอนสร้างหน้า (3.17.0)
 *
 * ‼️ ทำไมต้องดูดสีด้วยสคริปต์ ไม่ใช่ให้ AI ดูภาพแล้วบอก
 *    AI อ่านสีจากภาพผิดซ้ำๆ และผิดทางเดียวกัน (standard/testing.md ข้อ 4ก.3)
 *    DESIGN.md ที่สีมาจากการกะ คือดีไซน์ที่ใกล้เคียงแต่ไม่ใช่ของผู้ใช้
 *
 * path ของภาพเขียนเทียบกับ project/design/ ได้ (refs/login.png) หรือเป็น path เต็มก็ได้
 */

declare(strict_types=1);

require __DIR__ . '/lib/ProjectDocs.php';

const IMAGE_TYPES = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
const MAX_BYTES   = 25 * 1024 * 1024;

$root = dirname(__DIR__);
$args = array_slice($_SERVER['argv'] ?? [], 1);
$cmd  = $args[0] ?? '';

match ($cmd) {
    '--import'  => import_folder($root, $args[1] ?? ''),
    '--palette' => palette($root, $args[1] ?? '', (int) (option($args, '--top') ?? 8)),
    '--pick'    => pick($root, $args[1] ?? '', (int) ($args[2] ?? -1), (int) ($args[3] ?? -1), (int) (option($args, '--size') ?? 5)),
    '--status'  => status($root),
    default     => usage(),
};

// ================================================================= คำสั่ง

function import_folder(string $root, string $from): never
{
    $from = rtrim(str_replace('\\', '/', $from), '/');

    if ($from === '' || !is_dir($from)) {
        fail("ไม่พบโฟลเดอร์: {$from}", ['ส่ง path ของโฟลเดอร์ที่มีภาพแบบ เช่น --import "D:/designs/project-x"']);
    }

    $target = $root . '/' . ProjectDocs::REFS_DIR;
    $items  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS));
    $done   = ['new' => [], 'updated' => [], 'same' => []];
    $skipped = [];

    foreach ($items as $item) {
        if (!$item->isFile()) {
            continue;
        }

        $relative = ltrim(substr(str_replace('\\', '/', $item->getPathname()), strlen($from)), '/');
        $ext      = strtolower($item->getExtension());

        if (!in_array($ext, IMAGE_TYPES, true)) {
            $skipped[] = "{$relative} (ไม่ใช่ภาพ)";
            continue;
        }
        if ($item->getSize() > MAX_BYTES) {
            $skipped[] = "{$relative} (ใหญ่เกิน 25 MB)";
            continue;
        }

        $dest = $target . '/' . $relative;

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0o777, true);
        }

        if (is_file($dest)) {
            $state = sha1_file($dest) === sha1_file($item->getPathname()) ? 'same' : 'updated';
        } else {
            $state = 'new';
        }

        if ($state !== 'same') {
            copy($item->getPathname(), $dest);
        }

        $done[$state][] = $relative;
    }

    $count = array_sum(array_map('count', $done));

    if ($count === 0) {
        fail("ไม่มีภาพในโฟลเดอร์ {$from}", ['รับ ' . implode(' · ', IMAGE_TYPES)]);
    }

    line('✓ นำเข้าภาพแบบ ' . $count . ' ไฟล์ → ' . ProjectDocs::REFS_DIR . '/'
        . ' (ใหม่ ' . count($done['new']) . ' · อัปเดต ' . count($done['updated']) . ' · เหมือนเดิม ' . count($done['same']) . ')');

    foreach ($skipped as $row) {
        line("  ข้าม {$row}");
    }

    line();
    report_mapping($root);
    line();
    line('→ ขั้นต่อไป (standard/flow.md ข้อ 5 เส้นทาง ค)');
    line('  1. เขียนตาราง "## แบบของแต่ละหน้า" ใน project/DESIGN.md — ชื่อที่จับคู่ไม่ได้ ถามผู้ใช้รวมทีเดียว');
    line('  2. ดูดสีด้วย --palette และ --pick เพื่อเขียน token สี — ห้ามกะจากการดูภาพ');
    line('  3. commit โฟลเดอร์ refs/ — ภาพแบบต้องอยู่กับ repo ไม่ใช่อยู่ในเครื่องใครคนเดียว');
    exit(0);
}

function palette(string $root, string $file, int $top): never
{
    [$image, $path] = load($root, $file);

    // ย่อก่อนนับ — ภาพแบบขนาดเต็มจอมีหลายล้านพิกเซล สีที่ได้เหมือนเดิมแต่ช้ากว่าหลายสิบเท่า
    $w = imagesx($image);
    $h = imagesy($image);

    if (max($w, $h) > 480) {
        $scale = 480 / max($w, $h);
        $small = imagescale($image, max(1, (int) ($w * $scale)), max(1, (int) ($h * $scale)), IMG_NEAREST_NEIGHBOUR);
        if ($small !== false) {
            $image = $small;
            $w     = imagesx($image);
            $h     = imagesy($image);
        }
    }

    // จัดกลุ่มสีที่ต่างกันเล็กน้อย (anti-alias ของขอบตัวอักษร) เข้าด้วยกัน แล้วเฉลี่ยในกลุ่ม
    $buckets = [];
    $total   = 0;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            [$r, $g, $b, $a] = rgba($image, $x, $y);

            if ($a > 100) {
                continue;   // โปร่งใสเกินครึ่ง ไม่ใช่สีที่ตาเห็น
            }

            $key = ($r >> 3) << 10 | ($g >> 3) << 5 | ($b >> 3);
            $buckets[$key] ??= [0, 0, 0, 0];
            $buckets[$key][0] += $r;
            $buckets[$key][1] += $g;
            $buckets[$key][2] += $b;
            $buckets[$key][3]++;
            $total++;
        }
    }

    uasort($buckets, static fn (array $a, array $b): int => $b[3] <=> $a[3]);

    line("สีที่ใช้มากที่สุดใน {$path} (ย่อเหลือ {$w}×{$h} ก่อนนับ)");
    line();

    foreach (array_slice($buckets, 0, max(1, $top), true) as $bucket) {
        $n   = $bucket[3];
        $hex = hex((int) round($bucket[0] / $n), (int) round($bucket[1] / $n), (int) round($bucket[2] / $n));
        line(sprintf('  %s  %5.1f%%', $hex, $n / max(1, $total) * 100));
    }

    line();
    line('สีที่กินพื้นที่มากที่สุดมักเป็นพื้นหลังและพื้นการ์ด · สีของปุ่มและตัวอักษรมักใช้พื้นที่น้อย');
    line('ให้ใช้ --pick วัดตรงจุดที่เป็นปุ่ม ตัวอักษร และเส้นขอบ เพื่อได้ค่าของแต่ละบทบาท');
    exit(0);
}

function pick(string $root, string $file, int $x, int $y, int $size): never
{
    [$image, $path] = load($root, $file);

    $w = imagesx($image);
    $h = imagesy($image);

    if ($x < 0 || $y < 0 || $x >= $w || $y >= $h) {
        fail("จุด ({$x}, {$y}) อยู่นอกภาพ — ภาพกว้าง {$w} สูง {$h}", ['--pick <ไฟล์> <x> <y> [--size 5]']);
    }

    $half = intdiv(max(1, $size), 2);
    $sum  = [0, 0, 0];
    $n    = 0;

    for ($j = max(0, $y - $half); $j <= min($h - 1, $y + $half); $j++) {
        for ($i = max(0, $x - $half); $i <= min($w - 1, $x + $half); $i++) {
            [$r, $g, $b] = rgba($image, $i, $j);
            $sum[0] += $r;
            $sum[1] += $g;
            $sum[2] += $b;
            $n++;
        }
    }

    [$r, $g, $b] = rgba($image, $x, $y);

    line(hex((int) round($sum[0] / $n), (int) round($sum[1] / $n), (int) round($sum[2] / $n))
        . "  เฉลี่ย {$size}×{$size} รอบ ({$x}, {$y}) ใน {$path}");
    line(hex($r, $g, $b) . '  พิกเซลตรงจุดนั้นพอดี');
    line();
    line('สองค่าต่างกันมาก = จุดนั้นอยู่บนขอบหรือตัวอักษร ขยับไปกลางพื้นที่สีแล้ววัดใหม่');
    exit(0);
}

function status(string $root): never
{
    $design = (string) @file_get_contents($root . '/project/DESIGN.md');
    $refs   = ProjectDocs::designRefs($design);

    if ($refs === [] && list_refs($root) === []) {
        line('ยังไม่มีภาพแบบ — โปรเจคนี้ไม่ได้ใช้เส้นทาง ค หรือยังไม่ได้นำเข้า (--import)');
        exit(0);
    }

    line('แบบของแต่ละหน้า (project/DESIGN.md)');

    foreach ($refs as $ref) {
        $built = ProjectDocs::builtFor($ref['ref']);
        $marks = [];
        $marks[] = is_file($root . '/' . $ref['ref']) ? 'มีภาพแบบ' : '✗ ไม่พบภาพแบบ';

        if ($ref['strict']) {
            $marks[] = is_file($root . '/' . $built) ? 'ถ่ายหน้าจริงแล้ว' : 'ยังไม่ได้ถ่ายหน้าจริง';
            $marks[] = $ref['diff'] !== '' ? 'บันทึกความต่างแล้ว' : 'ยังไม่ได้บันทึกความต่าง';
        } else {
            $marks[] = 'แนวทาง — ไม่บังคับเทียบ';
        }

        line(sprintf('  %-28s %s', $ref['page'], implode(' · ', $marks)));
    }

    line();
    report_mapping($root);

    $crops = array_filter(ProjectDocs::refCrops($design), static fn (array $c): bool => $c['temporary']);

    if ($crops !== []) {
        line();
        line('ภาพชั่วคราวที่ตัดจากภาพแบบ — รอไฟล์จริง ' . count($crops) . ' ภาพ');
        foreach ($crops as $crop) {
            line("  {$crop['file']}  จาก {$crop['from']}");
        }
    }

    exit(0);
}

// ================================================================= ตัวช่วย

/** ภาพแบบหลักที่ยังไม่อยู่ในทะเบียน จัดกลุ่มตามหน้า (ภาพเสริม @375 · -dark อยู่กลุ่มเดียวกับภาพหลัก) */
function report_mapping(string $root): void
{
    $design     = (string) @file_get_contents($root . '/project/DESIGN.md');
    $registered = [];

    foreach (ProjectDocs::designRefs($design) as $ref) {
        $registered[ProjectDocs::refBase($ref['ref'])] = $ref['page'];
    }

    $groups = [];
    foreach (list_refs($root) as $file) {
        $groups[ProjectDocs::refBase($file)][] = basename($file);
    }

    ksort($groups);

    line('ภาพแบบตามหน้า');

    foreach ($groups as $base => $files) {
        $where = isset($registered[$base]) ? 'จับคู่กับ ' . $registered[$base] : 'ยังไม่จับคู่กับหน้าไหน';
        line(sprintf('  %-24s %-26s %s', $base, $where, implode(' · ', $files)));
    }
}

/** @return list<string> ภาพแบบชั้นบนสุดของ refs/ — ไม่รวม assets/ ที่เป็นภาพประกอบแยก */
function list_refs(string $root): array
{
    $out = [];

    foreach (glob($root . '/' . ProjectDocs::REFS_DIR . '/*') ?: [] as $file) {
        if (is_file($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), IMAGE_TYPES, true)) {
            $out[] = ProjectDocs::REFS_DIR . '/' . basename($file);
        }
    }

    sort($out);

    return $out;
}

/** @return array{0: GdImage, 1: string} */
function load(string $root, string $file): array
{
    if (!extension_loaded('gd')) {
        fail('PHP บนเครื่องนี้ไม่มี GD', ['เปิด extension=gd ใน php.ini']);
    }

    $candidates = [$file, $root . '/' . $file, $root . '/project/design/' . $file, $root . '/' . ProjectDocs::REFS_DIR . '/' . $file];

    foreach ($candidates as $path) {
        if (!is_file($path)) {
            continue;
        }

        $data  = (string) file_get_contents($path);
        $image = @imagecreatefromstring($data);

        if ($image === false) {
            fail("อ่านภาพไม่ได้: {$path}", ['รับ ' . implode(' · ', IMAGE_TYPES) . ' — webp ต้องมี GD ที่รองรับ webp']);
        }

        return [$image, str_replace('\\', '/', $path)];
    }

    fail("ไม่พบไฟล์: {$file}", ['เขียนเทียบกับ project/design/ ได้ เช่น refs/login.png']);
}

/** @return array{0: int, 1: int, 2: int, 3: int} */
function rgba(GdImage $image, int $x, int $y): array
{
    $c = imagecolorsforindex($image, imagecolorat($image, $x, $y));

    return [$c['red'], $c['green'], $c['blue'], $c['alpha']];
}

function hex(int $r, int $g, int $b): string
{
    return sprintf('#%02X%02X%02X', $r, $g, $b);
}

function option(array $args, string $name): ?string
{
    $i = array_search($name, $args, true);

    return $i === false ? null : ($args[$i + 1] ?? null);
}

function line(string $text = ''): void
{
    echo $text . "\n";
}

function usage(): never
{
    fail('ต้องระบุคำสั่ง', [
        '--import <โฟลเดอร์>          คัดลอกภาพแบบเข้า project/design/refs/',
        '--palette <ภาพ> [--top 8]    สีที่ใช้มากที่สุดในภาพ',
        '--pick <ภาพ> <x> <y>         สีของพิกเซลตรงจุดนั้น',
        '--status                     หน้าไหนมีแบบ เทียบแล้วหรือยัง',
    ]);
}

function fail(string $message, array $hints = []): never
{
    fwrite(STDERR, "\n✗ {$message}\n");

    foreach ($hints as $hint) {
        fwrite(STDERR, "  · {$hint}\n");
    }

    fwrite(STDERR, "\n");
    exit(1);
}

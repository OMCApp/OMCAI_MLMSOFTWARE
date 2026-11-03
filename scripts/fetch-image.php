<?php
/**
 * ดาวน์โหลดภาพจาก URL มาเก็บในโปรเจค พร้อมแปลงและย่อให้ใช้บนเว็บได้จริง
 *
 * ‼️ ทำไมต้องมีสคริปต์นี้
 *    เครื่องมือสร้างภาพ (Higgsfield, Gemini, ฯลฯ) คืน **URL บนคลาวด์** ไม่ใช่ไฟล์ในโปรเจค
 *    ปล่อยให้หน้าเว็บอ้าง URL นั้นตรงๆ คือการโหลดจาก CDN ซึ่งกฎเหล็กข้อ 2 ห้ามไว้
 *    และวันที่ลิงก์หมดอายุ รูปจะหายทั้งเว็บโดยไม่มีใครรู้ล่วงหน้า
 *
 * ‼️ และไฟล์ที่ได้มามักหนัก 3-5 MB ซึ่งใช้บนหน้าเว็บไม่ได้
 *    สคริปต์นี้ย่อและแปลงเป็น WebP ให้เสร็จในขั้นตอนเดียว
 *
 * ตัวอย่าง
 *   php scripts/fetch-image.php --url "https://..." --out hero-bottle --ratio 3:4
 *   php scripts/fetch-image.php --url "https://..." --out news-1 --ratio 16:9 --width 1200
 *
 *   ตัดจากภาพแบบของผู้ใช้ (เส้นทาง ค) — กรอบเป็นพิกเซลของภาพแบบ x,y,กว้าง,สูง
 *   php scripts/fetch-image.php --file project/design/refs/login.png --box 720,0,720,900 --out login-court
 *
 * ‼️ ภาพที่ตัดจากภาพแบบเป็นทางสำรอง ไม่ใช่ของจริง (standard/design.md ข้อ 7ก.5)
 *    ความละเอียดเท่าภาพแบบ มักแตกบนจอความละเอียดสูง และอาจติดตัวหนังสือหรือปุ่มที่วางทับมาด้วย
 *    สคริปต์จึงบันทึกลง DESIGN.md หัวข้อ "ภาพที่ตัดจากแบบ" ให้เองว่าเป็นภาพชั่วคราว — ไม่งั้นจะถูกลืมจนขึ้นใช้งานจริง
 */

declare(strict_types=1);

const OUT_DIR    = 'public/assets/images';
const MAX_BYTES  = 25 * 1024 * 1024;   // กันไฟล์ที่ใหญ่ผิดปกติ
const QUALITY    = 82;
const RATIOS     = ['1:1' => 1.0, '3:4' => 0.75, '4:3' => 4 / 3, '16:9' => 16 / 9, '9:16' => 9 / 16];
const WIDTHS     = ['1:1' => 1200, '3:4' => 1200, '4:3' => 1600, '16:9' => 1600, '9:16' => 900];

$root = dirname(__DIR__);

function fail(string $message, array $hints = []): never
{
    fwrite(STDERR, "\n✗ {$message}\n");

    foreach ($hints as $hint) {
        fwrite(STDERR, "  · {$hint}\n");
    }

    fwrite(STDERR, "\n");
    exit(1);
}

function download(string $url): string
{
    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_MAXFILESIZE    => MAX_BYTES,
    ]);

    $bytes  = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $type   = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    $error  = curl_error($curl);

    curl_close($curl);

    if ($bytes === false) {
        fail('ดาวน์โหลดไม่สำเร็จ — ' . $error);
    }

    if ($status !== 200) {
        fail("ดาวน์โหลดไม่สำเร็จ HTTP {$status}", ['ลิงก์ของเครื่องมือสร้างภาพมักหมดอายุ ลองสร้างใหม่']);
    }

    if (!str_starts_with($type, 'image/')) {
        fail("URL นี้ไม่ใช่ภาพ (content-type: {$type})");
    }

    return (string) $bytes;
}

/**
 * บันทึกภาพที่ตัดจากภาพแบบลง DESIGN.md หัวข้อ "ภาพที่ตัดจากแบบ" — สร้างหัวข้อให้ถ้ายังไม่มี
 * แถวของไฟล์เดิมถูกแทนที่ ไม่ซ้ำ
 */
function record_crop(string $root, string $output, string $from): void
{
    $path = $root . '/project/DESIGN.md';

    if (!is_file($path)) {
        return;
    }

    $text    = str_replace("\r\n", "\n", (string) file_get_contents($path));
    $heading = '## ภาพที่ตัดจากแบบ';
    $row     = "| `{$output}` | `{$from}` | ชั่วคราว — รอไฟล์จริงจากผู้ใช้ |";

    if (!str_contains($text, $heading)) {
        $text = rtrim($text) . "\n\n{$heading}\n\n"
            . "ภาพที่ตัดจากภาพแบบมาใช้แทนไปก่อน — ความละเอียดเท่าภาพแบบ ขอไฟล์ต้นฉบับแล้วเปลี่ยนสถานะเป็น `ใช้จริง` (standard/design.md ข้อ 7ก.5)\n\n"
            . "| ไฟล์ในระบบ | ตัดจาก | สถานะ |\n|---|---|---|\n";
    }

    $lines = array_values(array_filter(explode("\n", $text),
        static fn (string $line): bool => !str_starts_with(trim($line), "| `{$output}` |")));

    // ต่อท้ายแถวสุดท้ายของตารางในหัวข้อนี้
    $start = array_search($heading, $lines, true);
    $at    = count($lines);

    for ($i = (int) $start + 1; $i < count($lines); $i++) {
        if (str_starts_with($lines[$i], '## ')) {
            $at = $i;
            break;
        }
    }

    while ($at > 0 && trim($lines[$at - 1]) === '') {
        $at--;
    }

    array_splice($lines, $at, 0, [$row]);
    file_put_contents($path, implode("\n", $lines) . "\n");
}

function options(array $argv): array
{
    $out = [];

    for ($i = 1, $n = count($argv); $i < $n; $i++) {
        if (!str_starts_with($argv[$i], '--')) {
            continue;
        }

        $key = substr($argv[$i], 2);

        if (!isset($argv[$i + 1]) || str_starts_with($argv[$i + 1], '--')) {
            fail("ตัวเลือก --{$key} ต้องมีค่าตามหลัง");
        }

        $out[$key] = $argv[++$i];
    }

    return $out;
}

$opt = options($argv);

$usage = [
    'php scripts/fetch-image.php --url "https://..." --out hero-bottle --ratio 3:4',
    'php scripts/fetch-image.php --file project/design/refs/login.png --box 720,0,720,900 --out login-court',
];

if (($opt['out'] ?? '') === '' || (($opt['url'] ?? '') === '') === (($opt['file'] ?? '') === '')) {
    fail('ต้องระบุ --out และระบุ --url หรือ --file อย่างใดอย่างหนึ่ง', $usage);
}

$url  = $opt['url'] ?? '';
$file = null;

if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://'))) {
    fail('--url ต้องเป็น https:// เท่านั้น');
}

if (($opt['file'] ?? '') !== '') {
    /* ‼️ รับเฉพาะไฟล์ในโปรเจค — ภาพที่ใช้ในระบบต้องสืบย้อนได้ว่ามาจากไหนใน repo
          ภาพแบบในเครื่องผู้ใช้ให้นำเข้าก่อนด้วย design-refs.php --import */
    $real = realpath($root . '/' . ltrim(str_replace('\\', '/', $opt['file']), '/')) ?: realpath($opt['file']);
    $base = str_replace('\\', '/', (string) realpath($root));
    $file = $real === false ? null : str_replace('\\', '/', $real);

    if ($file === null || !str_starts_with($file, $base . '/') || !is_file($file)) {
        fail('--file ต้องเป็นไฟล์ในโปรเจค', ['ภาพแบบจากเครื่องของผู้ใช้ ให้นำเข้าก่อน: php scripts/design-refs.php --import <โฟลเดอร์>']);
    }
}

$box = null;

if (($opt['box'] ?? '') !== '') {
    if ($file === null || preg_match('/^(\d+),(\d+),(\d+),(\d+)$/', str_replace(' ', '', $opt['box']), $b) !== 1) {
        fail('--box ใช้กับ --file และต้องเป็น x,y,กว้าง,สูง เป็นพิกเซลของภาพต้นทาง', $usage);
    }

    $box = ['x' => (int) $b[1], 'y' => (int) $b[2], 'width' => (int) $b[3], 'height' => (int) $b[4]];
}

// --box ที่ไม่ระบุ --ratio ใช้สัดส่วนของกรอบตามที่ตัด
$ratio = $opt['ratio'] ?? ($box !== null ? null : '16:9');

if ($ratio !== null && !isset(RATIOS[$ratio])) {
    fail('--ratio ต้องเป็น ' . implode(' · ', array_keys(RATIOS)));
}

$width = (int) ($opt['width'] ?? ($ratio !== null ? WIDTHS[$ratio] : min(1600, $box['width'] ?? 1600)));

if ($width < 200 || $width > 3000) {
    fail('--width ต้องอยู่ระหว่าง 200 ถึง 3000');
}

/* ‼️ รับชื่อไฟล์อย่างเดียว ไม่รับ path — กันการเขียนทับไฟล์นอกโฟลเดอร์ที่ตั้งใจ */
$name = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $opt['out']));

if ($name === '' || $name !== strtolower(trim((string) $opt['out']))) {
    fail('--out ต้องเป็นชื่อล้วน a-z 0-9 และ - เท่านั้น ไม่ต้องใส่นามสกุลและไม่ใช่ path', [
        'ไฟล์จะถูกเก็บที่ ' . OUT_DIR . '/<ชื่อ>.webp เสมอ',
    ]);
}

foreach (['imagecreatefromstring', 'imagewebp', 'imagescale'] as $fn) {
    if (!function_exists($fn)) {
        fail("ส่วนขยาย GD ไม่มีฟังก์ชัน {$fn}()", ['เปิด extension=gd ใน php.ini']);
    }
}

// ------------------------------------------------------------------ ดาวน์โหลด หรืออ่านจากไฟล์

if ($file !== null) {
    if (filesize($file) > MAX_BYTES) {
        fail('ไฟล์ใหญ่เกิน 25 MB');
    }

    $bytes = (string) file_get_contents($file);
} else {
    $bytes = download($url);
}

$source = @imagecreatefromstring($bytes);

if ($source === false) {
    fail('ไฟล์ที่ได้ไม่ใช่ภาพที่อ่านได้');
}

// ‼️ ตัดตามกรอบก่อน — กรอบเป็นพิกเซลของภาพต้นทาง ซึ่งคือพิกัดที่อ่านได้จากภาพแบบ
if ($box !== null) {
    $fullW = imagesx($source);
    $fullH = imagesy($source);

    if ($box['width'] < 1 || $box['height'] < 1 || $box['x'] + $box['width'] > $fullW || $box['y'] + $box['height'] > $fullH) {
        fail("--box อยู่นอกภาพ — ภาพกว้าง {$fullW} สูง {$fullH}");
    }

    $boxed = imagecrop($source, $box);
    imagedestroy($source);

    if ($boxed === false) {
        fail('ตัดภาพตามกรอบไม่สำเร็จ');
    }

    $source = $boxed;
}

$srcW = imagesx($source);
$srcH = imagesy($source);

// ‼️ ห้ามขยายเกินพิกเซลที่มี — ภาพที่ตัดจากภาพแบบมักเล็ก ขยายแล้วแตกโดยไม่ได้อะไรเพิ่ม
$want  = $ratio !== null ? RATIOS[$ratio] : $srcW / $srcH;
$limit = $srcW / $srcH > $want ? (int) floor($srcH * $want) : $srcW;

if ($width > $limit) {
    fwrite(STDERR, "  ! ภาพต้นทางมีแค่ {$limit}px ในแนวกว้าง — ย่อเป้าหมายจาก {$width} เหลือ {$limit}px"
        . ' · ถ้าหน้าแสดงภาพนี้กว้างกว่านี้ ภาพจะแตก ให้ขอไฟล์ต้นฉบับจากผู้ใช้' . "\n");
    $width = max(1, $limit);
}

// ------------------------------------------------------------------ ครอปตามสัดส่วน แล้วย่อ

/* ‼️ ครอปจากกลางภาพให้ได้สัดส่วนที่ DESIGN.md กำหนด ก่อนย่อ
      ปล่อยให้ CSS ครอบด้วย object-fit อย่างเดียว แปลว่าเบราว์เซอร์ต้องโหลดพิกเซล
      ที่ไม่มีวันได้แสดงผล ซึ่งเป็นน้ำหนักที่จ่ายฟรี */
$srcAsp = $srcW / $srcH;

if ($srcAsp > $want) {
    $cropW = (int) round($srcH * $want);
    $cropH = $srcH;
} else {
    $cropW = $srcW;
    $cropH = (int) round($srcW / $want);
}

$cropped = imagecrop($source, [
    'x'      => (int) round(($srcW - $cropW) / 2),
    'y'      => (int) round(($srcH - $cropH) / 2),
    'width'  => $cropW,
    'height' => $cropH,
]);

imagedestroy($source);

if ($cropped === false) {
    fail('ครอปภาพไม่สำเร็จ');
}

$height = (int) round($width / $want);
$final  = imagescale($cropped, $width, $height, IMG_BICUBIC);

imagedestroy($cropped);

if ($final === false) {
    fail('ย่อภาพไม่สำเร็จ');
}

// ------------------------------------------------------------------ บันทึก

$outDir = $root . '/' . OUT_DIR;

if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
    fail('สร้างโฟลเดอร์ ' . OUT_DIR . ' ไม่ได้');
}

$target = $outDir . '/' . $name . '.webp';

if (!imagewebp($final, $target, QUALITY)) {
    imagedestroy($final);
    fail("เขียนไฟล์ {$target} ไม่ได้", ['ตรวจสิทธิ์การเขียนของโฟลเดอร์ ' . OUT_DIR]);
}

imagedestroy($final);

$before     = round(strlen($bytes) / 1024, 1);
$after      = round(filesize($target) / 1024, 1);
$saved      = $before > 0 ? round((1 - $after / $before) * 100) : 0;
$ratioLabel = $ratio ?? 'ตามกรอบที่ตัด';
$recorded   = false;

// ตัดจากภาพแบบของผู้ใช้ → บันทึกว่าเป็นภาพชั่วคราว ให้ชุดตรวจและบอร์ดเห็น
$refsDir = str_replace('\\', '/', (string) realpath($root)) . '/project/design/refs/';

if ($file !== null && str_starts_with($file, $refsDir)) {
    $from = 'refs/' . substr($file, strlen($refsDir))
        . ($box !== null ? " ({$box['x']},{$box['y']},{$box['width']},{$box['height']})" : '');

    record_crop($root, OUT_DIR . "/{$name}.webp", $from);
    $recorded = true;
}

echo "\n✓ เก็บภาพแล้ว\n\n";
echo "  " . OUT_DIR . "/{$name}.webp\n";
echo "  {$srcW}×{$srcH} → {$width}×{$height} ({$ratioLabel})  ·  {$before} KB → {$after} KB  (เล็กลง {$saved}%)\n\n";
echo "  ใช้ในหน้า — <img src=\"<?= e(asset('/assets/images/{$name}.webp')) ?>\" alt=\"...\">\n\n";

if ($recorded) {
    echo "  บันทึกลง project/DESIGN.md หัวข้อ \"ภาพที่ตัดจากแบบ\" ว่าเป็นภาพชั่วคราวแล้ว — ขอไฟล์ต้นฉบับจากผู้ใช้\n\n";
}

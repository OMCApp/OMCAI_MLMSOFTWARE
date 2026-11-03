<?php
/**
 * สร้าง favicon ครบชุดจากโลโก้ หรือจากตัวอักษรถ้ายังไม่มีโลโก้
 *
 *   php scripts/make-favicon.php                     หาโลโก้เองจาก public/assets/images/logo.*
 *   php scripts/make-favicon.php --source logo.png   ระบุไฟล์ต้นทางเอง
 *   php scripts/make-favicon.php --letter ก          บังคับใช้ตัวอักษร ไม่ต้องมีโลโก้
 *   php scripts/make-favicon.php --bg "#1F4FD8"      ทับสีพื้นที่อ่านมาจาก DESIGN.md
 *   php scripts/make-favicon.php --font tahoma.ttf   ระบุฟอนต์ของโหมดตัวอักษร
 *
 * ‼️ พื้นต้องทึบเสมอ — แถบแท็บของเบราว์เซอร์เป็นสีเข้มหรือสว่างตามธีมของ "เครื่องผู้ใช้"
 *    ไม่ใช่ตามธีมของระบบเรา · โลโก้พื้นโปร่งใสจะจมหายไปครึ่งหนึ่งของเครื่องที่เปิดดู
 *
 * ‼️ ต้องรันซ้ำทุกครั้งที่เปลี่ยนโลโก้หรือเปลี่ยนสีแบรนด์ใน project/DESIGN.md
 *    ไม่งั้นแท็บจะยังเป็นสีของแบรนด์เดิม ซึ่งเป็นที่ที่ลืมง่ายที่สุดเพราะมันไม่ได้อยู่ในหน้า
 */

declare(strict_types=1);

require __DIR__ . '/lib/Yaml.php';

const ICO_SIZES = [16, 32, 48];
const PAD_RATIO = 0.14;   // ระยะขอบรอบโลโก้ คิดเป็นสัดส่วนของด้าน

$root = dirname(__DIR__);
$argv = $_SERVER['argv'] ?? [];
array_shift($argv);

// ‼️ Windows คืน path เป็น \ แต่ path ที่เราประกอบเองใช้ / — ต้องมีรูปเดียวไว้ตัดคำนำหน้าตอนรายงาน
$rootUnix = rtrim(str_replace('\\', '/', $root), '/') . '/';
$relative = static fn (string $path): string => str_replace($rootUnix, '', str_replace('\\', '/', $path));

// ------------------------------------------------------------------ อ่านคำสั่ง

$opt = ['source' => null, 'letter' => null, 'bg' => null, 'ink' => null, 'font' => null];

for ($i = 0; $i < count($argv); $i++) {
    $arg = $argv[$i];

    foreach (array_keys($opt) as $key) {
        if ($arg === "--{$key}") {
            $opt[$key] = $argv[++$i] ?? null;
            continue 2;
        }
        if (str_starts_with($arg, "--{$key}=")) {
            $opt[$key] = substr($arg, strlen($key) + 3);
            continue 2;
        }
    }
}

if (!extension_loaded('gd')) {
    fail('ไม่มีส่วนขยาย GD ของ PHP', [
        'เปิด extension=gd ใน php.ini แล้วรันใหม่',
        'XAMPP มี GD มาให้อยู่แล้ว แค่ยังไม่ได้เปิดในบางเครื่อง',
    ]);
}

// --------------------------------------------------------- สีจาก DESIGN.md

$design = read_design($root);

$bg  = parse_color($opt['bg']  ?? ($design['bg']  ?? '#3F3F46')) ?? [63, 63, 70];
$ink = parse_color($opt['ink'] ?? ($design['ink'] ?? '#FFFFFF')) ?? [255, 255, 255];

// --------------------------------------------------------------- หาต้นทาง

$source = resolve_source($root, $opt['source']);
$letter = null;

if ($source === null) {
    $letter = $opt['letter'] ?? first_letter($design['name'] ?? null) ?? first_letter(app_name($root));

    if ($letter === null) {
        fail('หาต้นทางของ favicon ไม่ได้', [
            'วางไฟล์โลโก้ไว้ที่ public/assets/images/logo.png (หรือ .jpg .webp .gif)',
            'หรือระบุเอง: php scripts/make-favicon.php --source path/to/logo.png',
            'หรือใช้ตัวอักษร: php scripts/make-favicon.php --letter ก',
        ]);
    }
}

// ------------------------------------------------------------------- ลงมือ

$outDir = $root . '/public/assets/images';

if (!is_dir($outDir) && !mkdir($outDir, 0775, true) && !is_dir($outDir)) {
    fail("สร้างโฟลเดอร์ {$outDir} ไม่ได้", ['ตรวจสิทธิ์การเขียนของโฟลเดอร์ public/assets']);
}

$src  = $source !== null ? load_image($source) : null;
$font = $letter !== null ? resolve_font($root, $opt['font'], $letter) : null;

$written = [];
$pngs    = [];

/* ‼️ สร้างเฉพาะขนาดที่ partials/favicon.php อ้างถึงจริง
      เคยสร้าง icon-512.png ด้วย ซึ่งต้องมี web app manifest ถึงจะมีใครใช้ — โครงนี้ไม่มี
      ผลคือทุกโปรเจคแบกไฟล์ที่ไม่มีอะไรอ้างถึงไป 10 KB
      จะเพิ่มขนาดใหม่เมื่อไหร่ ต้องเพิ่มลิงก์ใน partial ในคอมมิตเดียวกันเสมอ */
foreach ([...ICO_SIZES, 32, 180] as $size) {
    $pngs[$size] ??= png_bytes(render_icon($size, $src, $letter, $font, $bg, $ink));
}

$targets = [
    $outDir . '/favicon.ico'          => ico_bytes(array_intersect_key($pngs, array_flip(ICO_SIZES))),
    $outDir . '/favicon-32.png'       => $pngs[32],
    $outDir . '/apple-touch-icon.png' => $pngs[180],
];

foreach ($targets as $path => $bytes) {
    if (file_put_contents($path, $bytes) === false) {
        fail("เขียนไฟล์ {$path} ไม่ได้", ['ตรวจสิทธิ์การเขียนของโฟลเดอร์ public/assets/images']);
    }

    $written[] = $relative($path);
}

// ------------------------------------------------------------------ รายงาน

$from = $source !== null
    ? 'โลโก้ ' . $relative($source)
    : "ตัวอักษร \"{$letter}\"" . ($font !== null ? ' · ฟอนต์ ' . basename($font) : '');

echo "✓ สร้าง favicon จาก{$from}\n";
echo '  พื้น ' . hex_of($bg) . ' · หมึก ' . hex_of($ink) . " (ทึบทั้งหมด)\n\n";

foreach ($written as $file) {
    echo "  {$file}\n";
}

echo "\n";
echo "layout กลางอ้างถึงไฟล์เหล่านี้อยู่แล้ว ไม่ต้องแก้อะไรเพิ่ม\n";
echo "‼️ รันซ้ำทุกครั้งที่เปลี่ยนโลโก้หรือสีแบรนด์ใน project/DESIGN.md\n";

exit(0);

// ===================================================================== ฟังก์ชัน

/**
 * ‼️ อ่านสีจาก DESIGN.md ไม่ใช่ให้คนพิมพ์เอง — ค่าธีมมีที่เดียวคือ DESIGN.md
 *    ใช้ค่าโหมดสว่างเสมอ เพราะ favicon มีได้ชุดเดียวและต้องอ่านออกบนแถบแท็บทั้งสองสี
 *
 * @return array{name?:string, bg?:string, ink?:string}
 */
function read_design(string $root): array
{
    $path = $root . '/project/DESIGN.md';

    if (!is_file($path)) {
        return [];
    }

    $front = Yaml::frontMatter((string) file_get_contents($path));

    if ($front === null) {
        return [];
    }

    $design = Yaml::parse($front);
    $name   = (string) ($design['name'] ?? '');

    return [
        // ชื่อที่ยังเป็นค่าตั้งต้นของโครงเปล่าใช้ไม่ได้ — ปล่อยให้ตกไปใช้ APP_NAME แทน
        'name' => str_starts_with($name, '[') ? '' : $name,
        'bg'   => (string) ($design['color']['brand']['primary']['light'] ?? ''),
        'ink'  => (string) ($design['color']['brand']['onPrimary']['light'] ?? ''),
    ];
}

function app_name(string $root): string
{
    $env = $root . '/.env';

    if (is_file($env) && preg_match('/^APP_NAME\s*=\s*"?([^"\r\n]+)"?/m', (string) file_get_contents($env), $m) === 1) {
        return trim($m[1]);
    }

    return '';
}

function first_letter(?string $text): ?string
{
    $text = trim((string) $text);

    if ($text === '' || $text === 'System') {
        return null;
    }

    return mb_substr($text, 0, 1, 'UTF-8');
}

function resolve_source(string $root, ?string $given): ?string
{
    if ($given !== null) {
        $path = is_file($given) ? $given : $root . '/' . ltrim($given, '/');

        if (!is_file($path)) {
            fail("ไม่พบไฟล์ต้นทาง {$given}", ['ตรวจ path อีกครั้ง — ใช้ path แบบเต็มหรือแบบอิงจากรากโปรเจคก็ได้']);
        }

        guard_svg($path);

        return $path;
    }

    foreach (['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'] as $ext) {
        $path = $root . "/public/assets/images/logo.{$ext}";

        if (is_file($path)) {
            guard_svg($path);

            return $path;
        }
    }

    return null;
}

/**
 * ‼️ GD อ่าน SVG ไม่ได้ และไม่มีทางรู้ล่วงหน้านอกจากดูนามสกุล
 *    ปล่อยให้ imagecreatefromstring คืน false เอง จะได้ข้อความ error ที่ไม่บอกอะไรเลย
 */
function guard_svg(string $path): void
{
    if (strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) !== 'svg') {
        return;
    }

    fail('GD อ่านไฟล์ SVG ไม่ได้', [
        'ส่งออกโลโก้เป็น PNG ขนาดอย่างน้อย 512×512 ก่อน แล้ววางที่ public/assets/images/logo.png',
        'SVG ยังใช้ในหน้าเว็บได้ตามปกติ — ข้อจำกัดนี้อยู่ที่ favicon อย่างเดียว',
    ]);
}

function load_image(string $path): GdImage
{
    $image = @imagecreatefromstring((string) file_get_contents($path));

    if ($image === false) {
        fail("อ่านไฟล์ภาพ {$path} ไม่ได้", [
            'รองรับ PNG · JPG · WebP · GIF — ถ้าเป็นชนิดอื่นให้แปลงเป็น PNG ก่อน',
        ]);
    }

    return $image;
}

/**
 * ‼️ โหมดตัวอักษรต้องมีไฟล์ฟอนต์จริง — GD วาดตัวไทยด้วยฟอนต์ในตัวไม่ได้
 *    ฟอนต์ของโปรเจคเก็บเป็น .woff2 ตามกฎห้าม CDN ซึ่ง GD ก็อ่านไม่ได้เช่นกัน
 *    จึงต้องไล่หา .ttf/.otf ที่มีอยู่ในเครื่องแทน
 */
function resolve_font(string $root, ?string $given, string $letter): string
{
    $candidates = [];

    if ($given !== null) {
        $candidates[] = is_file($given) ? $given : $root . '/' . ltrim($given, '/');
    }

    foreach (['ttf', 'otf'] as $ext) {
        $candidates = [...$candidates, ...(glob($root . "/public/assets/fonts/*.{$ext}") ?: [])];
    }

    $windir = $_SERVER['WINDIR'] ?? $_SERVER['SystemRoot'] ?? 'C:/Windows';

    $candidates = [...$candidates,
        // เรียงตามความครอบคลุมตัวไทย — Leelawadee UI และ Tahoma มีครบ
        $windir . '/Fonts/leelawui.ttf',
        $windir . '/Fonts/tahoma.ttf',
        $windir . '/Fonts/segoeui.ttf',
        '/System/Library/Fonts/Supplemental/Tahoma.ttf',
        '/Library/Fonts/Arial Unicode.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
    ];

    foreach ($candidates as $path) {
        if (is_file($path) && @imagettfbbox(12, 0, $path, $letter) !== false) {
            return $path;
        }
    }

    fail('หาไฟล์ฟอนต์สำหรับโหมดตัวอักษรไม่ได้', [
        'ระบุเอง: php scripts/make-favicon.php --letter ' . $letter . ' --font C:/Windows/Fonts/tahoma.ttf',
        'หรือวางโลโก้ไว้ที่ public/assets/images/logo.png แล้วรันใหม่ ซึ่งได้ผลดีกว่าอยู่แล้ว',
        'หมายเหตุ: .woff2 ใน public/assets/fonts/ ใช้ไม่ได้ GD อ่านได้เฉพาะ .ttf และ .otf',
    ]);
}

/**
 * @param array{0:int,1:int,2:int} $bg
 * @param array{0:int,1:int,2:int} $ink
 */
function render_icon(int $size, ?GdImage $src, ?string $letter, ?string $font, array $bg, array $ink): GdImage
{
    $canvas = imagecreatetruecolor($size, $size);

    // ‼️ พื้นทึบเสมอ — savealpha ปิดไว้เพื่อไม่ให้ PNG ที่ได้มีช่องโปร่งใสหลงเหลือ
    imagealphablending($canvas, true);
    imagesavealpha($canvas, false);
    imagefilledrectangle($canvas, 0, 0, $size, $size, imagecolorallocate($canvas, ...$bg));

    $box = (int) round($size * (1 - 2 * PAD_RATIO));

    if ($src !== null) {
        $sw = imagesx($src);
        $sh = imagesy($src);

        // ย่อแบบรักษาสัดส่วน แล้ววางกลาง — ยืดให้เต็มจะทำให้โลโก้บิด
        $scale = min($box / $sw, $box / $sh);
        $dw    = max(1, (int) round($sw * $scale));
        $dh    = max(1, (int) round($sh * $scale));

        imagecopyresampled(
            $canvas, $src,
            (int) round(($size - $dw) / 2), (int) round(($size - $dh) / 2), 0, 0,
            $dw, $dh, $sw, $sh
        );

        return $canvas;
    }

    $color = imagecolorallocate($canvas, ...$ink);

    /* ‼️ ต้องวัดแล้วปรับขนาดจริง ห้ามคูณสัดส่วนจากขนาดภาพตรงๆ
       ตัวอักษรไทยกินความสูงใน em box น้อยกว่าอักษรละตินมาก เพราะต้องเว้นที่ให้สระบนล่าง
       คิดจาก point size เฉยๆ จะได้ "ก" ที่เล็กกว่า "A" ที่ค่าเดียวกันอย่างเห็นได้ชัด
       วัดจาก bbox จริงแล้วปรับ ทำให้ทุกภาษาได้ขนาดตาเท่ากัน */
    $pt   = (float) $box;
    $bbox = imagettfbbox($pt, 0, (string) $font, (string) $letter);

    for ($pass = 0; $pass < 3; $pass++) {
        $w = max(1, $bbox[2] - $bbox[0]);
        $h = max(1, $bbox[1] - $bbox[7]);

        $pt  *= min($box / $w, $box / $h);
        $bbox = imagettfbbox($pt, 0, (string) $font, (string) $letter);
    }

    $textW = $bbox[2] - $bbox[0];
    $textH = $bbox[1] - $bbox[7];

    imagettftext(
        $canvas, $pt, 0,
        (int) round(($size - $textW) / 2 - $bbox[0]),
        (int) round(($size - $textH) / 2 - $bbox[7]),
        $color, (string) $font, (string) $letter
    );

    return $canvas;
}

function png_bytes(GdImage $image): string
{
    ob_start();
    imagepng($image, null, 9);

    return (string) ob_get_clean();
}

/**
 * ‼️ ICO ที่ห่อ PNG ไว้ข้างใน — เบราว์เซอร์ทุกตัวที่ยังได้รับการดูแลรองรับหมด
 *    และเลี่ยงการเขียน BMP กลับหัวพร้อม AND mask เองซึ่งพลาดง่ายและตรวจยาก
 *
 * @param array<int, string> $pngs  ขนาด => ข้อมูล PNG
 */
function ico_bytes(array $pngs): string
{
    ksort($pngs);

    $header  = pack('vvv', 0, 1, count($pngs));
    $offset  = 6 + 16 * count($pngs);
    $entries = '';
    $body    = '';

    foreach ($pngs as $size => $png) {
        $entries .= pack(
            'CCCCvvVV',
            $size >= 256 ? 0 : $size,   // 0 = 256 ตามสเปค ICO
            $size >= 256 ? 0 : $size,
            0, 0, 1, 32,
            strlen($png),
            $offset
        );

        $offset += strlen($png);
        $body   .= $png;
    }

    return $header . $entries . $body;
}

/** @return array{0:int,1:int,2:int}|null */
function parse_color(?string $value): ?array
{
    $hex = ltrim(trim((string) $value), '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    if (preg_match('/^[0-9a-f]{6}$/i', $hex) !== 1) {
        return null;
    }

    return [
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2)),
    ];
}

/** @param array{0:int,1:int,2:int} $rgb */
function hex_of(array $rgb): string
{
    return sprintf('#%02X%02X%02X', ...$rgb);
}

/** @param array<int, string> $hints */
function fail(string $message, array $hints = []): never
{
    fwrite(STDERR, "✗ {$message}\n");

    foreach ($hints as $hint) {
        fwrite(STDERR, "  · {$hint}\n");
    }

    exit(1);
}

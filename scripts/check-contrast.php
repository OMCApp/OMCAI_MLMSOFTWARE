<?php
/**
 * ตรวจ contrast ของ token สีทุกคู่ที่ใช้จริง — ทั้งสองโหมด
 *
 *   php scripts/check-contrast.php          รายงานทุกคู่
 *   php scripts/check-contrast.php --quiet   พิมพ์เฉพาะคู่ที่ตก (check.php ใช้ตัวนี้)
 *
 * exit 0 = ผ่านหมด · exit 1 = มีคู่ที่ตก
 *
 * ‼️ เหตุผลที่ไฟล์นี้ต้องมี
 *    `project/DESIGN.md` เคยเขียนว่า "ตรวจ contrast แล้วทุกคู่ 48 คู่ผ่าน 4.5:1 ทั้งสองโหมด"
 *    พอเขียนสคริปต์ตรวจจริงกับพาเลตต์เดิม **ตก 7 คู่** — ขอบช่องกรอกได้แค่ 1.85:1
 *    ซึ่งแปลว่าผู้ใช้สายตาไม่ดีหาช่องกรอกไม่เจอ
 *
 *    ประโยคในเอกสารไม่ใช่การตรวจ เพราะ
 *      · ไม่มีใครรันซ้ำได้ ต้องเชื่อคนเขียนอย่างเดียว
 *      · ตอนแก้สีรอบต่อไป ไม่มีอะไรบอกว่าคำอ้างนั้นหมดอายุแล้ว
 *      · คนเขียนนับเฉพาะคู่ "ข้อความบนพื้น" แล้วลืมคู่ "เส้นขอบของ control" ซึ่งคนละเกณฑ์กัน
 *
 * ‼️ เกณฑ์ไม่ได้มีค่าเดียว — WCAG ให้ 3:1 กับสิ่งที่ไม่ใช่ข้อความ
 *    ตั้ง 4.5 กับทุกอย่างคือการตั้งเกณฑ์ผิด และจะบังคับให้สีเข้มจางลงโดยไม่มีเหตุผลรองรับ
 */

declare(strict_types=1);

require __DIR__ . '/lib/Yaml.php';
require __DIR__ . '/lib/Report.php';

const AA_TEXT      = 4.5;   // ข้อความขนาดปกติ
const AA_LARGE     = 3.0;   // ข้อความใหญ่ (≥24px หรือ ≥19px ตัวหนา)
const AA_NON_TEXT  = 3.0;   // เส้นขอบ control · ไอคอน · แถบกราฟิก (WCAG 1.4.11)

$root  = dirname(__DIR__);
$quiet = in_array('--quiet', $_SERVER['argv'] ?? [], true);
$path  = $root . '/project/DESIGN.md';

if (!is_file($path)) {
    fwrite(STDERR, "ไม่พบ project/DESIGN.md\n");
    exit(1);
}

$front = Yaml::frontMatter((string) file_get_contents($path));

if ($front === null) {
    fwrite(STDERR, "project/DESIGN.md ไม่มี front matter\n");
    exit(1);
}

$design = Yaml::parse($front);
$colors = $design['color'] ?? [];

// ---------------------------------------------------------------- ตัวช่วยสี

/** @return array{0:float,1:float,2:float}|null คืน null ถ้าไม่ใช่สีทึบที่คำนวณได้ */
function to_rgb(string $value): ?array
{
    $value = trim($value);

    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value, $m) === 1) {
        $hex = $m[1];

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            (float) hexdec(substr($hex, 0, 2)),
            (float) hexdec(substr($hex, 2, 2)),
            (float) hexdec(substr($hex, 4, 2)),
        ];
    }

    // rgb()/rgba() ที่มีความโปร่งใส คำนวณ contrast ตรงๆ ไม่ได้ ต้องข้าม
    return null;
}

/** ความสว่างสัมพัทธ์ตามสูตรของ WCAG */
function luminance(array $rgb): float
{
    $channels = array_map(static function (float $c): float {
        $c /= 255;

        return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    }, $rgb);

    return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

function ratio(array $a, array $b): float
{
    $la = luminance($a);
    $lb = luminance($b);

    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

/** อ่านค่าสีแบบ "text.strong" ของโหมดที่ระบุ */
function token(array $colors, string $path, string $mode): ?array
{
    $node = $colors;

    foreach (explode('.', $path) as $key) {
        if (!is_array($node) || !array_key_exists($key, $node)) {
            return null;
        }

        $node = $node[$key];
    }

    if (!is_array($node) || !isset($node[$mode])) {
        return null;
    }

    return to_rgb((string) $node[$mode]);
}

// ---------------------------------------------------------------- คู่ที่ต้องตรวจ

$surfaces = ['surface.page', 'surface.card', 'surface.raised'];

/** @var array<int, array{0:string,1:string,2:float,3:string}> [หน้า, พื้น, เกณฑ์, คำอธิบาย] */
$pairs = [];

foreach ($surfaces as $bg) {
    // ข้อความ — เกณฑ์เต็ม
    foreach (['text.strong', 'text.normal', 'text.muted'] as $fg) {
        $pairs[] = [$fg, $bg, AA_TEXT, 'ข้อความขนาดปกติ'];
    }

    /* ‼️ คู่ที่คนลืมบ่อยที่สุด และเป็นคู่ที่ทำให้ผู้ใช้สายตาไม่ดีหาช่องกรอกไม่เจอ
       เป็นคนละเกณฑ์กับข้อความ (3:1 ตาม WCAG 1.4.11) จึงหลุดจากการนับด้วยมือเสมอ

       ‼️ ตรวจเฉพาะ border.strong เท่านั้น
       border.normal คือเส้นแบ่งตกแต่ง (ขอบการ์ด เส้นคั่นแถว) ซึ่ง WCAG ไม่ได้บังคับ
       และตั้งใจให้จางเพื่อไม่ให้หน้ารก — บังคับ 3:1 กับมันคือการตั้งเกณฑ์ผิด
       ชุดตรวจที่ฟ้องของที่ถูกอยู่แล้ว ทำให้คนเลิกเชื่อผลของมัน ซึ่งอันตรายกว่าไม่มีชุดตรวจ

       border.strong คือเส้นขอบที่เป็นตัวบอกขอบเขตของ control ตัวเดียว — ตัวนี้บังคับ */
    $pairs[] = ['border.strong', $bg, AA_NON_TEXT, 'เส้นขอบของ control'];

    // สิ่งที่กดได้ / บอกตำแหน่ง / บอกสถานะ — ไม่ใช่ข้อความ จึง 3:1
    foreach (['brand.primary', 'focus', 'state.success', 'state.warning', 'state.danger', 'state.info'] as $fg) {
        $pairs[] = [$fg, $bg, AA_NON_TEXT, 'ไอคอน/แถบ/วงแหวน focus'];
    }
}

// ตัวหนังสือบนปุ่มทึบ — ต้องอ่านออกจริง
$pairs[] = ['brand.onPrimary', 'brand.primary', AA_TEXT, 'ตัวหนังสือบนปุ่มหลัก'];

// พื้นปุ่มต้องแยกจากการ์ดออก ไม่งั้นปุ่มจมหาย
$pairs[] = ['brand.primary', 'surface.card', AA_NON_TEXT, 'พื้นปุ่มแยกจากการ์ด'];

// ลิงก์ในเนื้อความเป็นข้อความ จึงใช้เกณฑ์เต็ม — มีเฉพาะโปรเจคที่แยก token นี้ออกมา
if (isset($colors['brand']['link'])) {
    foreach (['surface.page', 'surface.card'] as $bg) {
        $pairs[] = ['brand.link', $bg, AA_TEXT, 'ลิงก์ในเนื้อความ'];
    }
}

// ---------------------------------------------------------------- ตรวจ

$failed  = [];
$checked = 0;
$skipped = [];

foreach (['light', 'dark'] as $mode) {
    foreach ($pairs as [$fgPath, $bgPath, $threshold, $label]) {
        $fg = token($colors, $fgPath, $mode);
        $bg = token($colors, $bgPath, $mode);

        if ($fg === null || $bg === null) {
            $skipped[] = "{$fgPath} บน {$bgPath} ({$mode})";
            continue;
        }

        $checked++;
        $value = ratio($fg, $bg);

        if ($value + 0.005 < $threshold) {
            $failed[] = [
                'mode'      => $mode,
                'pair'      => "{$fgPath} บน {$bgPath}",
                'label'     => $label,
                'value'     => $value,
                'threshold' => $threshold,
            ];
        }
    }
}

// ---------------------------------------------------------------- รายงาน

if ($failed === []) {
    if (!$quiet) {
        Report::line();
        Report::line(Report::green('✓') . " contrast ผ่านทุกคู่ — ตรวจ {$checked} คู่ (2 โหมด)");

        if ($skipped !== []) {
            Report::line(Report::dim('  ข้าม ' . count($skipped) . ' คู่ที่คำนวณไม่ได้ (สีโปร่งใสหรือ token ไม่มี)'));
        }

        Report::line();
    }

    exit(0);
}

Report::line();
Report::line(Report::bold(Report::red('✗') . ' contrast ไม่ผ่าน ' . count($failed) . " คู่ (จาก {$checked} คู่)"));
Report::line();

foreach ($failed as $row) {
    Report::line(sprintf(
        '  %-46s %s  ได้ %.2f:1  ต้องได้ %.1f  (%s)',
        $row['pair'],
        $row['mode'] === 'light' ? 'สว่าง' : 'มืด  ',
        $row['value'],
        $row['threshold'],
        $row['label']
    ));
}

Report::line();
Report::line(Report::dim('แก้ค่าที่ project/DESIGN.md แล้วรัน php scripts/design-sync.php'));
Report::line(Report::dim('เกณฑ์: ข้อความ 4.5:1 · เส้นขอบ/ไอคอน/แถบ 3:1 (WCAG 1.4.11)'));
Report::line();

exit(1);

<?php
/**
 * แปลง project/DESIGN.md → public/assets/css/app.css
 *
 *   php scripts/design-sync.php            เขียนทับขอบเขต token ใน app.css
 *   php scripts/design-sync.php --check    ตรวจว่าตรงกันไหม (ใช้ใน CI) ไม่เขียนไฟล์
 *
 * ‼️ ค่าธีมทั้งหมดมาจาก DESIGN.md จุดเดียว — ห้ามแก้ขอบเขต token ใน app.css ด้วยมือ
 *    แก้ที่ DESIGN.md แล้วรันสคริปต์นี้
 */

declare(strict_types=1);

require __DIR__ . '/lib/Yaml.php';

const TOKEN_BEGIN = '/* TOKENS:BEGIN */';
const TOKEN_END   = '/* TOKENS:END */';

$root      = dirname(__DIR__);
$checkOnly = in_array('--check', $_SERVER['argv'] ?? [], true);

$designPath = $root . '/project/DESIGN.md';
$cssPath    = $root . '/public/assets/css/app.css';

if (!is_file($designPath)) {
    fwrite(STDERR, "ไม่พบ project/DESIGN.md\n"
        . "ต้องทำช่วง B ให้จบก่อน — ปล่อยผ่านแล้วผลลัพธ์จะออกมาเป็นหน้า default ไร้เอกลักษณ์\n");
    exit(1);
}

$front = Yaml::frontMatter((string) file_get_contents($designPath));

if ($front === null) {
    fwrite(STDERR, "project/DESIGN.md ไม่มี front matter (ส่วนที่คั่นด้วย ---)\n"
        . "ดูรูปแบบที่ standard/design.md ข้อ 2\n");
    exit(1);
}

$design = Yaml::parse($front);
$errors = [];
$block  = build_tokens($design, $errors);

if ($errors !== []) {
    fwrite(STDERR, "DESIGN.md ยังไม่ถูกต้อง\n\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '  · ' . $error . "\n");
    }
    fwrite(STDERR, "\n");
    exit(1);
}

$existing = is_file($cssPath) ? (string) file_get_contents($cssPath) : '';
$updated  = splice_tokens($existing, $block, (string) ($design['name'] ?? ''));

/* ‼️ ค่าที่ DESIGN.md ตัดสินแต่ไม่ใช่ค่า CSS ต้องมาถึง runtime ด้วย
   เดิมมันถูกคัดลอกไว้ใน .env (APP_NAV_STYLE, APP_DEFAULT_THEME) ด้วยมือ
   ซึ่งแปลว่าค่าเดียวกันอยู่สองที่แล้วเถียงกันได้เงียบๆ — DESIGN.md สั่ง header
   แต่ layout วาด sidebar โดยไม่มีอะไรรายงาน เพราะในสายตาชุดตรวจมันไม่ผิด
   generate เป็นไฟล์แทนการให้คนพิมพ์ซ้ำ ตามกฎเหล็กข้อ 15 */
$configPath    = $root . '/config/design.php';
$configExisting = is_file($configPath) ? (string) file_get_contents($configPath) : '';
$configUpdated  = build_design_config($design, $errors);

if ($errors !== []) {
    fwrite(STDERR, "DESIGN.md ยังไม่ถูกต้อง\n\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '  · ' . $error . "\n");
    }
    fwrite(STDERR, "\n");
    exit(1);
}

if ($checkOnly) {
    // ‼️ เทียบโดยไม่สนใจ LF/CRLF — git บน Windows แปลงให้ตอน checkout ได้
    //    ไม่งั้น clone สดๆ บนเครื่อง Windows จะ fail ทันทีทั้งที่ไม่มีใครแก้อะไร
    $eol = static fn (string $s): string => str_replace(["\r\n", "\r"], "\n", $s);

    $stale = [];

    if ($eol($existing) !== $eol($updated)) {
        $stale[] = 'public/assets/css/app.css';
    }
    if ($eol($configExisting) !== $eol($configUpdated)) {
        $stale[] = 'config/design.php';
    }

    if ($stale === []) {
        echo "✓ app.css และ config/design.php ตรงกับ DESIGN.md แล้ว\n";
        exit(0);
    }

    fwrite(STDERR, '✗ ' . implode(' และ ', $stale) . " ไม่ตรงกับ project/DESIGN.md\n"
        . "  รัน `php scripts/design-sync.php` เพื่อ generate ใหม่\n"
        . "  ถ้ามีคนแก้ไฟล์ที่ generate ด้วยมือ การแก้นั้นจะหายไป — แก้ที่ DESIGN.md แทน\n");
    exit(1);
}

if (!is_dir(dirname($cssPath))) {
    mkdir(dirname($cssPath), 0o777, true);
}

file_put_contents($cssPath, $updated);
file_put_contents($configPath, $configUpdated);

$count = substr_count($block, '--');
echo "✓ เขียน public/assets/css/app.css แล้ว ({$count} token)\n";
echo "✓ เขียน config/design.php แล้ว (nav · โหมดเริ่มต้น)\n";
exit(0);

// ------------------------------------------------------------------ ฟังก์ชัน

/**
 * สร้างขอบเขต token ทั้งก้อน
 *
 * @param  array<string, mixed> $design
 * @param  array<int, string>   $errors
 */
function build_tokens(array $design, array &$errors): string
{
    $light = [];
    $dark  = [];

    collect_colors($design['color'] ?? [], 'color', $light, $dark, $errors);

    // token ที่ไม่ขึ้นกับโหมด
    $flat = [];
    collect_scale($design['typography']['family'] ?? [], 'font', $flat, '');
    collect_scale($design['typography']['scale'] ?? [], 'text', $flat, 'rem');
    collect_scale($design['typography']['weight'] ?? [], 'weight', $flat, '');
    collect_scale($design['typography']['lineHeight'] ?? [], 'leading', $flat, '');
    collect_scale($design['space'] ?? [], 'space', $flat, '');
    collect_scale($design['radius'] ?? [], 'radius', $flat, '');
    // ขนาดไอคอนเป็นค่าดีไซน์เหมือนระยะห่าง ต้องมาจาก DESIGN.md ไม่ใช่เขียนดิบใน CSS
    collect_scale($design['icon'] ?? [], 'icon', $flat, '');
    collect_scale($design['shadow'] ?? [], 'shadow', $flat, '');
    collect_scale($design['motion']['duration'] ?? [], 'duration', $flat, '');
    collect_scale($design['motion']['easing'] ?? [], 'ease', $flat, '');

    /* ‼️ ดีไซน์ระดับ component ต้องมาจาก DESIGN.md ด้วย ไม่ใช่แค่ token พื้นฐาน
       เดิม DESIGN.md บรรทุกแค่ สี ฟอนต์ ระยะห่าง — ส่วนรูปทรงของ component
       (แถวตารางสูงเท่าไหร่ · หัวตารางมีพื้นไหม · badge มุมมนแค่ไหน)
       ฝังอยู่ใน @layer components ของ app.css ซึ่งไม่มีอะไรแตะเลย

       ผลที่วัดได้จริงตอนทดลอง: เอาดีไซน์จากภายนอกเข้ามา
       สีกับฟอนต์เปลี่ยนตาม 100% แต่แถวตารางยังสูง 69px ทั้งที่ดีไซน์บอก 40px
       และ badge ประเภทกับสถานะกลายเป็นหน้าตาเหมือนกัน
       ทั้งที่ design.md 5.6 บังคับให้ต่างกันด้วยรูปทรง และดีไซน์ก็ทำมาให้แล้ว

       รูปแบบ: component: { table: { rowHeight: 40px } } → --table-row-height: 40px
       เพิ่มเฉพาะค่าที่พิสูจน์แล้วว่าเพี้ยนจริง ไม่ใช่เดาว่าอาจต้องใช้ */
    foreach ((array) ($design['component'] ?? []) as $group => $values) {
        if (!is_array($values)) {
            continue;
        }

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $flat['--' . kebab((string) $group) . '-' . kebab((string) $key)] = (string) $value;
        }
    }

    // ค่าใน layout ที่เป็นตัวเลข/ขนาด กลายเป็น token ทั้งหมด
    // ส่วนค่าที่เป็นตัวเลือก (density, nav) ไม่ใช่ค่า CSS จึงข้าม
    foreach ((array) ($design['layout'] ?? []) as $key => $value) {
        if (is_array($value) || in_array($key, ['density', 'nav'], true)) {
            continue;
        }

        $flat['--layout-' . kebab((string) $key)] = (string) $value;
    }

    if ($light === []) {
        $errors[] = 'ไม่พบ token สีเลย — ตรวจหัวข้อ color: ใน front matter';
    }
    if ($flat === []) {
        $errors[] = 'ไม่พบ token ของ typography / space / radius';
    }

    $default = strtolower((string) ($design['mode']['default'] ?? 'light'));

    if (!in_array($default, ['light', 'dark'], true)) {
        $errors[] = "mode.default ต้องเป็น light หรือ dark (พบ: {$default})";
        $default  = 'light';
    }

    if ($errors !== []) {
        return '';
    }

    $base  = $default === 'dark' ? $dark : $light;
    $other = $default === 'dark' ? $light : $dark;
    $flip  = $default === 'dark' ? 'light' : 'dark';

    $out  = TOKEN_BEGIN . "\n";
    $out .= "/* generate จาก project/DESIGN.md — ห้ามแก้ด้วยมือ\n";
    $out .= "   แก้ค่าที่ DESIGN.md แล้วรัน: php scripts/design-sync.php */\n\n";

    $out .= ":root {\n" . render($base + $flat) . "}\n\n";

    $out .= "/* โหมดตามที่ผู้ใช้เลือก — PHP เขียน data-theme ที่ <html> เพื่อไม่ให้หน้ากะพริบตอนโหลด */\n";
    $out .= ":root[data-theme=\"{$flip}\"] {\n" . render($other) . "}\n\n";

    $out .= "/* ยังไม่เคยเลือก — ตามค่าของเครื่อง */\n";
    $out .= "@media (prefers-color-scheme: {$flip}) {\n";
    $out .= "  :root:not([data-theme=\"{$default}\"]) {\n" . render($other, '    ') . "  }\n}\n\n";

    $out .= TOKEN_END;

    return $out;
}

/**
 * @param  array<string, mixed> $node
 * @param  array<string, string> $light
 * @param  array<string, string> $dark
 * @param  array<int, string>    $errors
 */
function collect_colors(mixed $node, string $prefix, array &$light, array &$dark, array &$errors): void
{
    if (!is_array($node)) {
        return;
    }

    // ใบสุดท้ายที่มี light/dark
    if (isset($node['light']) || isset($node['dark'])) {
        $name = '--' . kebab($prefix);

        if (!isset($node['light']) || !isset($node['dark'])) {
            $errors[] = "token `{$prefix}` มีค่าไม่ครบทั้งสองโหมด — ทุกระบบต้องมีโหมดสว่างและมืด";

            return;
        }

        $light[$name] = (string) $node['light'];
        $dark[$name]  = (string) $node['dark'];

        return;
    }

    foreach ($node as $key => $child) {
        if (is_array($child)) {
            collect_colors($child, $prefix . '.' . $key, $light, $dark, $errors);
            continue;
        }

        // สีที่เขียนเป็นค่าเดียว ไม่แยกโหมด
        $errors[] = "token `{$prefix}.{$key}` กำหนดเป็นค่าเดียว ต้องเป็น { light: ..., dark: ... }";
    }
}

/**
 * @param array<string, string> $out
 */
function collect_scale(mixed $node, string $prefix, array &$out, string $unit): void
{
    if (!is_array($node)) {
        return;
    }

    foreach ($node as $key => $value) {
        if (is_array($value)) {
            continue;
        }

        $name = '--' . $prefix . '-' . kebab((string) $key);
        $text = (string) $value;

        if ($unit !== '' && is_numeric($text)) {
            $text .= $unit;
        }

        // ชื่อฟอนต์ต้องอยู่ในเครื่องหมายคำพูด ไม่งั้นชื่อที่มีช่องว่างจะพัง
        if ($prefix === 'font' && !str_contains($text, '"') && !str_contains($text, "'")) {
            $text = '"' . $text . '"';
        }

        $out[$name] = $text;
    }
}

/**
 * ค่าที่ DESIGN.md ตัดสินแต่ runtime ต้องใช้ และไม่ใช่ค่า CSS
 *
 * @param  array<string, mixed> $design
 * @param  array<int, string>   $errors
 */
function build_design_config(array $design, array &$errors): string
{
    $nav  = (string) ($design['layout']['nav'] ?? '');
    $mode = (string) ($design['mode']['default'] ?? '');

    if (!in_array($nav, ['sidebar', 'header'], true)) {
        $errors[] = "layout.nav ต้องเป็น sidebar หรือ header (ได้ \"{$nav}\")";
        $nav = 'sidebar';
    }

    if (!in_array($mode, ['light', 'dark'], true)) {
        $errors[] = "mode.default ต้องเป็น light หรือ dark (ได้ \"{$mode}\")";
        $mode = 'light';
    }

    return <<<PHP
    <?php
    /**
     * ‼️ ไฟล์นี้ generate จาก project/DESIGN.md ห้ามแก้ด้วยมือ
     *    แก้ที่ DESIGN.md แล้วรัน `php scripts/design-sync.php`
     *
     * เก็บค่าที่ DESIGN.md ตัดสินแต่ไม่ใช่ค่า CSS จึงลง app.css ไม่ได้
     * เคยอยู่ใน .env ซึ่งแปลว่าค่าเดียวกันอยู่สองที่แล้วเถียงกันได้เงียบๆ
     */

    declare(strict_types=1);

    return [
        'nav'           => '{$nav}',
        'default_theme' => '{$mode}',
    ];

    PHP;
}

/** camelCase → kebab-case เพราะ CSS custom property ใช้ขีดกลาง */
function kebab(string $text): string
{
    $text = str_replace('.', '-', $text);

    return strtolower((string) preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '-', $text));
}

/**
 * @param array<string, string> $tokens
 */
function render(array $tokens, string $indent = '  '): string
{
    $out = '';

    foreach ($tokens as $name => $value) {
        $out .= $indent . $name . ': ' . $value . ";\n";
    }

    return $out;
}

/** แทนที่เฉพาะขอบเขต token โดยไม่แตะ CSS ที่เขียนเอง */
function splice_tokens(string $css, string $block, string $name): string
{
    if ($css !== '' && str_contains($css, TOKEN_BEGIN) && str_contains($css, TOKEN_END)) {
        $start = (int) strpos($css, TOKEN_BEGIN);
        $end   = (int) strpos($css, TOKEN_END) + strlen(TOKEN_END);

        return substr($css, 0, $start) . $block . substr($css, $end);
    }

    if ($css !== '') {
        // มีไฟล์อยู่แล้วแต่ยังไม่มีขอบเขต token — วางไว้บนสุด
        return $block . "\n\n" . ltrim($css);
    }

    return scaffold($block, $name);
}

function scaffold(string $block, string $name): string
{
    $title = $name !== '' ? $name : 'ระบบ';

    return <<<CSS
    /* {$title} — สไตล์ทั้งระบบอยู่ไฟล์นี้ไฟล์เดียว
       ค่าธีมทั้งหมดมาจาก project/DESIGN.md · ห้ามสร้างไฟล์ CSS แยกต่อหน้า */

    {$block}

    @layer reset, base, layout, components, utilities;

    @layer reset {
      *, *::before, *::after { box-sizing: border-box; }
      body, h1, h2, h3, h4, p, figure, blockquote, dl, dd { margin: 0; }
      img, picture, svg { max-width: 100%; display: block; }
      button, input, select, textarea { font: inherit; color: inherit; }
    }

    @layer base {
      html { color-scheme: light dark; }

      body {
        background: var(--color-surface-page);
        color: var(--color-text-normal);
        font-family: var(--font-body), system-ui, sans-serif;
        font-size: var(--text-base);
        line-height: var(--leading-normal);
      }

      h1, h2, h3, h4 {
        font-family: var(--font-heading), system-ui, sans-serif;
        line-height: var(--leading-tight);
      }

      /* focus ต้องเห็นชัดทุกจุดที่กดได้ — ห้าม outline: none โดยไม่ใส่แบบอื่นแทน */
      :focus-visible {
        outline: 2px solid var(--color-focus);
        outline-offset: 2px;
      }

      /* ผู้ใช้บางคนเวียนหัวจากการเคลื่อนไหว */
      @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
          animation-duration: .01ms !important;
          transition-duration: .01ms !important;
        }
      }
    }

    @layer layout {
      /* โครงหน้าและ grid */
    }

    @layer components {
      /* component กลางทั้งหมด — ดู standard/components.md */
    }

    @layer utilities {
      /* คลาสช่วยเหลือที่ใช้ซ้ำจริงเท่านั้น */
    }

    CSS;
}

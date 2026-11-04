<?php
/**
 * Phase 5 — build the SQL migration that imports the old site's 36 posts into the CMS tables.
 *
 * Usage (from the project root):
 *   php tools/migration/build-import.php            write the migration + docs/migration/post-titles.md
 *   php tools/migration/build-import.php --check    validate only, write nothing (exit 1 on problems)
 *
 * Input:  docs/migration/content.json (tools/migration/extract.php) · docs/migration/images.csv
 * Output: database/migrations/030_import_old_posts.sql · docs/migration/post-titles.md
 *
 * ‼️ The output is deterministic (no timestamps), so re-running on the same input gives the same file
 *    and the same checksum. Once 030 has been applied anywhere, NEVER regenerate it with different
 *    content — the Migrator refuses changed files. Put corrections in a new 03x migration instead.
 *
 * What it does per post
 *   · slug = last path segment, decoded, exactly as the old URL · published_at/updated_at = the old
 *     dates in Asia/Bangkok local time (the URL date comes from published_at)
 *   · title (the page H1) = the old post title (theme H1) when it is a single, complete H1 —
 *     otherwise the clean <title>. seo_title = the clean <title> (old <title> minus the WordPress
 *     keyword list). Nothing is invented; manual fixes are listed in OVERRIDES with a reason.
 *   · content = content_html → empty alt filled (author alt for the same file elsewhere, then
 *     images.csv alt_suggested without its generic prefix, then the post title) → width/height
 *     guaranteed → loading="lazy" → App\Services\HtmlSanitizer (H1 → H2, allowlist)
 *   · meta_description = old one when 50–170 chars and intact, else the rewrite in OVERRIDES
 *     (written from the article text) · excerpt = the final meta description
 *   · featured image → media row (idempotent on path) · every content image → media row
 *   · Thai text is hex (CONVERT(X'..' USING utf8mb4)) with the readable text in a comment —
 *     scripts/check.php (no-thai-values) rejects Thai on INSERT lines
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root = dirname(__DIR__, 2);

require $root . '/core/autoload.php';

$checkOnly = in_array('--check', $argv, true);

/* ------------------------------------------------------------------ manual decisions
 * Every hand-made change is here so reviewers can see it in one place (also in post-titles.md).
 *   title  — H1 override (only when the old H1 is cut off / duplicated)
 *   description — replaces a missing, too short, too long or broken old meta description
 */
const OVERRIDES = [
    // Two H1s on the live page; the theme H1 is cut off by the author ("…ต้องมี MLM Softwar").
    // Use the clean <title>, which states the same intent in full.
    '/2026/07/14/mlm-software-system/' => [
        'title_reason' => 'H1 เดิมถูกตัดท้าย ("…MLM Softwar") และมี H1 ซ้ำสองตัว — ใช้ <title> เดิมแทน',
        'title'        => 'MLM Software: 7 เหตุผลที่ธุรกิจขายตรงยุคใหม่ต้องมี',
    ],
    // 222 chars — Google cuts it at ~160
    '/2024/06/17/กิฟฟารีน-mlm-อันดับ-1-ของไทย/' => [
        'description_reason' => 'เดิมยาว 222 ตัวอักษร (ถูกตัด)',
        'description'        => '“กิฟฟารีน” ธุรกิจขายตรง MLM สัญชาติไทย มีโรงงานผลิตสินค้าเอง 3 แห่ง ทีม R&D แข็งแกร่ง และได้อันดับ 1 กลุ่มขายตรงจาก Thailand’s Most Admired Company',
    ],
    // 17 chars ("ธุรกิจขายตรง(MLM)")
    '/2024/07/15/10-ข้อ-ที่คุณอ่านแล้วจะทำ/' => [
        'description_reason' => 'เดิมสั้น 17 ตัวอักษร ("ธุรกิจขายตรง(MLM)")',
        'description'        => 'เข้าใจธุรกิจขายตรง (MLM) ใน 10 ข้อ ตั้งแต่การขายถึงผู้บริโภคโดยไม่ผ่านคนกลาง ลงทุนไม่มาก ทำควบคู่งานประจำได้ ไปจนถึงกฎหมายที่รองรับและการตรวจสอบใบอนุญาต',
    ],
    // 63 chars but the first letter is missing ("นโลกธุรกิจ…" = "ในโลกธุรกิจ…") and it stops mid-sentence
    '/2024/12/16/5-ฟีเจอร์-mlm-software-ปี-2025/' => [
        'description_reason' => 'เดิมขาดตัวอักษรแรก ("นโลกธุรกิจ…") และจบกลางประโยค',
        'description'        => 'MLM Software ปี 2025 กับ 5 ฟีเจอร์เพิ่มรายได้: บริหารเครือข่ายอัตโนมัติ แผนผลตอบแทนปรับได้ เครื่องมือการตลาดอัตโนมัติ ใช้งานบนมือถือ และรายงานวิเคราะห์เชิงลึก',
    ],
    // 340 chars
    '/2025/10/21/7-วิธี-สร้างทัศนคติ-mlm/' => [
        'description_reason' => 'เดิมยาว 340 ตัวอักษร (ถูกตัด)',
        'description'        => '7 วิธีสร้างทัศนคติแห่งความสำเร็จ (Success Mindset) สำหรับนักขายตรง MLM เช่น มองขายตรงเป็นธุรกิจระยะยาว เชื่อมั่นในผลิตภัณฑ์ และเรียนรู้พัฒนาตัวเองอยู่เสมอ',
    ],
];

/** Live H1s that are cut off by the author (review-notes.md "Title cleanup") */
const TRUNCATED_H1 = ['/2026/07/14/mlm-software-system/'];

/** Category SEO — the old category archives had only the name as description (6–10 chars) */
const CATEGORY_SEO = [
    'ข่าวขายตรง' => [
        'seo_title'        => 'ข่าวขายตรงและความรู้ธุรกิจ MLM | OMC MLM Software',
        'description'      => 'รวมบทความเรื่องธุรกิจขายตรงและ MLM ทั้งความหมายของขายตรงและตลาดแบบตรง รายชื่อผู้ประกอบธุรกิจที่จดทะเบียนกับ สคบ. แผนรายได้และค่าคอมมิชชัน เทคนิคบริหารทีมและทัศนคติของนักขายตรง ไปจนถึงการใช้ MLM Software จัดการสมาชิก สายงาน คะแนน PV/BV และสต็อก',
        'meta_description' => 'บทความธุรกิจขายตรงและ MLM: ความรู้พื้นฐาน กฎหมาย แผนรายได้ ค่าคอมมิชชัน การบริหารทีม และการใช้ MLM Software จัดการธุรกิจเครือข่ายให้เป็นระบบ',
    ],
    'ประกัน' => [
        'seo_title'        => 'บทความประกันรถยนต์และประกันชีวิต | OMC',
        'description'      => 'รวมบทความความรู้เรื่องประกันจาก OMC Broker ทั้งประกันรถยนต์แต่ละชั้น พ.ร.บ. ประกันภาคบังคับ ขั้นตอนเมื่อเกิดอุบัติเหตุ กรณีที่ประกันปฏิเสธความคุ้มครอง บริษัทประกันรถยนต์ยอดนิยม ประกันชีวิตและสุขภาพ และการใช้ประกันชีวิตลดหย่อนภาษี',
        'meta_description' => 'ความรู้เรื่องประกันจาก OMC Broker: ประกันรถยนต์ชั้น 1–3 พ.ร.บ. ขั้นตอนเมื่อรถชน เคสที่ประกันปฏิเสธความคุ้มครอง ประกันชีวิตและสุขภาพ และการลดหย่อนภาษี',
    ],
];

const DESC_MIN = 50;
const DESC_MAX = 170;

// ------------------------------------------------------------------ input

$items = json_decode((string) file_get_contents($root . '/docs/migration/content.json'), true, 512, JSON_THROW_ON_ERROR);

$csv = [];
$fh  = fopen($root . '/docs/migration/images.csv', 'rb');
$head = fgetcsv($fh, null, ',', '"', '');
while (($row = fgetcsv($fh, null, ',', '"', '')) !== false) {
    $row = array_combine($head, $row);
    $csv[$row['path_decoded']] = $row;
}
fclose($fh);

$posts = array_values(array_filter($items, static fn (array $it): bool => $it['type'] === 'post'));

$problems = [];
$problem  = static function (string $path, string $message) use (&$problems): void {
    $problems[] = "{$path}: {$message}";
};

// ------------------------------------------------------------------ helpers

function tidy(string $text): string
{
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));

    // “สมาชิกทีม MLM ” → “สมาชิกทีม MLM”
    return (string) preg_replace(['/“\s+/u', '/\s+”/u'], ['“', '”'], $text);
}

function sqlHex(string $text): string
{
    return "CONVERT(X'" . strtoupper(bin2hex($text)) . "' USING utf8mb4)";
}

/** binary-collated value for comparisons against utf8mb4_bin columns (slug, path) */
function sqlHexBin(string $text): string
{
    return sqlHex($text) . ' COLLATE utf8mb4_bin';
}

/** ASCII-only literal (dates, keys, URLs) */
function sqlAscii(string $text): string
{
    if (preg_match('/^[\x20-\x7E]*$/', $text) !== 1) {
        throw new RuntimeException('non-ASCII value passed to sqlAscii: ' . $text);
    }

    return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $text) . "'";
}

function sqlNullableInt(?int $value): string
{
    return $value === null ? 'NULL' : (string) $value;
}

/** comment text on one line, never closing a comment or starting a new statement */
function comment(string $text): string
{
    return str_replace(["\r", "\n"], ' ', $text);
}

function localDate(string $iso): string
{
    return (new DateTimeImmutable($iso))->setTimezone(new DateTimeZone('Asia/Bangkok'))->format('Y-m-d H:i:s');
}

function mimeOf(string $path): string
{
    return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png'         => 'image/png',
        'webp'        => 'image/webp',
        'gif'         => 'image/gif',
        'svg'         => 'image/svg+xml',
        default       => 'application/octet-stream',
    };
}

/** "ภาพประกอบ: X" / "ภาพประกอบบทความ X" / "ภาพหน้าปกบทความ X" → "X" (generic prefixes say nothing) */
function stripAltPrefix(string $alt): string
{
    return tidy((string) preg_replace('/^(?:ภาพประกอบบทความ|ภาพหน้าปกบทความ|ภาพประกอบ:)\s*/u', '', $alt));
}

// ------------------------------------------------------------------ author alts per file (all posts)

$authorAlts = [];

foreach ($posts as $it) {
    foreach ($it['images'] as $img) {
        $alt = tidy((string) $img['alt']);
        if ($alt !== '') {
            $key = rawurldecode($img['src']);
            $authorAlts[$key][$alt] = ($authorAlts[$key][$alt] ?? 0) + 1;
        }
    }
}

$bestAuthorAlt = static function (string $path) use ($authorAlts): ?string {
    if (!isset($authorAlts[$path])) {
        return null;
    }
    $counts = $authorAlts[$path];
    arsort($counts);

    return (string) array_key_first($counts);
};

// ------------------------------------------------------------------ build

$sanitizer = new App\Services\HtmlSanitizer();
$media     = [];   // decoded path => row
$rows      = [];
$altFills  = [];   // [post path, image path, alt, source]
$rewrites  = [];

$addMedia = static function (string $decodedPath, ?int $width, ?int $height, string $alt, string $postPath)
    use (&$media, $root, $problem): void {
    $file = $root . '/public' . $decodedPath;

    if (!is_file($file)) {
        $problem($postPath, "image file missing: public{$decodedPath}");
        return;
    }

    if ($width === null || $height === null) {
        $size   = @getimagesize($file);
        $width  = $width ?? ($size[0] ?? null);
        $height = $height ?? ($size[1] ?? null);
    }

    if (!isset($media[$decodedPath])) {
        $media[$decodedPath] = [
            'path'   => $decodedPath,
            'mime'   => mimeOf($decodedPath),
            'width'  => $width,
            'height' => $height,
            'alt'    => $alt,
            'bytes'  => (int) filesize($file),
        ];
    }
};

foreach ($posts as $it) {
    $path = (string) $it['path_decoded'];

    if (preg_match('#^/(\d{4})/(\d{2})/(\d{2})/([^/]+)/$#u', $path, $m) !== 1) {
        $problem($path, 'not a /yyyy/mm/dd/slug/ path');
        continue;
    }

    [, $y, $mo, $d, $slug] = $m;
    $published = localDate((string) $it['published_at']);
    $updated   = localDate((string) ($it['modified_at'] ?: $it['published_at']));

    if (substr($published, 0, 10) !== "{$y}-{$mo}-{$d}") {
        $problem($path, "published_at {$published} does not match the URL date");
    }

    $override  = OVERRIDES[$path] ?? [];
    $cleanTitle = tidy((string) $it['title_clean']);
    $h1s        = array_map('tidy', (array) $it['h1']);

    // H1 = old theme post title when it is single and complete; else the clean <title>
    if (isset($override['title'])) {
        $title = $override['title'];
    } elseif (count($h1s) === 1 && $h1s[0] !== '' && !in_array($path, TRUNCATED_H1, true)) {
        $title = $h1s[0];
    } else {
        $title = $cleanTitle;
    }

    $seoTitle = $cleanTitle;

    // meta description
    $oldDesc = tidy((string) ($it['meta_description'] ?? ''));
    if (isset($override['description'])) {
        $description = $override['description'];
        $rewrites[]  = [$path, $oldDesc, $description, $override['description_reason']];

        // a rewrite must fit what SeoService prints without cutting (160)
        if (mb_strlen($description) > 160) {
            $problem($path, 'rewritten meta description is ' . mb_strlen($description) . ' chars (max 160)');
        }
    } else {
        $description = $oldDesc;
    }
    $len = mb_strlen($description);
    if ($len < DESC_MIN || $len > DESC_MAX) {
        $problem($path, "meta description length {$len} outside " . DESC_MIN . '–' . DESC_MAX . ' — add an OVERRIDES entry');
    }

    // category
    $catSlugs = array_column((array) $it['categories'], 'slug');
    $catSlug  = $catSlugs[0] ?? null;
    if ($catSlug === null || !isset(CATEGORY_SEO[rawurldecode($catSlug)])) {
        $problem($path, 'unknown category ' . json_encode($catSlugs, JSON_UNESCAPED_UNICODE));
    }
    $catSlug = $catSlug !== null ? rawurldecode($catSlug) : null;

    // content: fill alt, width/height, lazy
    $doc = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body><div id="__root">' . $it['content_html'] . '</div></body></html>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    foreach (iterator_to_array($doc->getElementsByTagName('img')) as $img) {
        /** @var DOMElement $img */
        $src     = (string) $img->getAttribute('src');
        $decoded = rawurldecode((string) parse_url($src, PHP_URL_PATH));

        if (!str_starts_with($decoded, '/wp-content/uploads/')) {
            $problem($path, "image outside /wp-content/uploads/: {$src}");
        }

        $alt = tidy((string) $img->getAttribute('alt'));

        if ($alt === '') {
            $source = 'author alt of the same file in another post';
            $alt    = (string) $bestAuthorAlt($decoded);

            if ($alt === '') {
                $source = 'images.csv alt_suggested';
                $alt    = stripAltPrefix((string) ($csv[$decoded]['alt_suggested'] ?? ''));
            }
            if ($alt === '') {
                $source = 'post title';
                $alt    = $title;
            }

            $img->setAttribute('alt', $alt);
            $altFills[] = [$path, $decoded, $alt, $source];
        }

        $width  = $img->getAttribute('width') !== '' ? (int) $img->getAttribute('width') : null;
        $height = $img->getAttribute('height') !== '' ? (int) $img->getAttribute('height') : null;

        if ($width === null || $height === null) {
            $size = @getimagesize($root . '/public' . $decoded);
            if ($size === false) {
                $problem($path, "cannot size {$decoded}");
            } else {
                $width  = $width ?? $size[0];
                $height = $height ?? $size[1];
                $img->setAttribute('width', (string) $width);
                $img->setAttribute('height', (string) $height);
            }
        }

        $img->setAttribute('loading', 'lazy');
        $addMedia($decoded, $width, $height, $alt, $path);
    }

    $html = '';
    foreach ($doc->getElementById('__root')->childNodes as $child) {
        $html .= $doc->saveHTML($child);
    }
    $content = trim($sanitizer->clean($html));

    if (preg_match('/<h1\b/i', $content) === 1) {
        $problem($path, 'content still has <h1>');
    }

    // featured image
    $featured = null;
    if ((string) $it['featured_image'] !== '') {
        $featured = rawurldecode((string) parse_url((string) $it['featured_image'], PHP_URL_PATH));
        $csvRow   = $csv[$featured] ?? null;
        $addMedia(
            $featured,
            $csvRow !== null && $csvRow['width'] !== '' ? (int) $csvRow['width'] : null,
            $csvRow !== null && $csvRow['height'] !== '' ? (int) $csvRow['height'] : null,
            $title,
            $path
        );
    }

    $rows[] = [
        'path'        => $path,
        'url'         => (string) $it['url'],
        'slug'        => $slug,
        'date'        => "{$y}-{$mo}-{$d}",
        'title'       => $title,
        'seo_title'   => $seoTitle,
        'old_title'   => (string) $it['title_raw'],
        'old_h1'      => implode(' ‖ ', $h1s),
        'description' => $description,
        'old_desc'    => $oldDesc,
        'content'     => $content,
        'featured'    => $featured,
        'category'    => $catSlug,
        'author'      => tidy((string) ($it['author'] ?? '')),
        'published'   => $published,
        'updated'     => $updated,
    ];
}

if (count($rows) !== 36) {
    $problem('*', 'expected 36 posts, got ' . count($rows));
}

foreach ($media as $row) {
    if ($row['alt'] === '' || $row['width'] === null || $row['height'] === null) {
        $problem($row['path'], 'media row without alt or size');
    }
}

foreach (CATEGORY_SEO as $slug => $seo) {
    $len = mb_strlen($seo['meta_description']);
    if ($len < DESC_MIN || $len > 160) {
        $problem("/category/{$slug}/", "category meta description length {$len}");
    }
}

if ($problems !== []) {
    fwrite(STDERR, "Problems:\n  · " . implode("\n  · ", $problems) . "\n");
    exit(1);
}

usort($rows, static fn (array $a, array $b): int => [$a['published'], $a['path']] <=> [$b['published'], $b['path']]);
ksort($media, SORT_STRING);

printf("%d posts · %d media rows · %d alt texts filled · %d descriptions rewritten\n",
    count($rows), count($media), count($altFills), count($rewrites));

if ($checkOnly) {
    exit(0);
}

// ------------------------------------------------------------------ SQL

$sql = [];
$sql[] = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;';
$sql[] = '';
$sql[] = '-- Phase 5 — import the 36 posts of the old WordPress site (mlmsoftware.omc.co.th)';
$sql[] = '-- GENERATED by tools/migration/build-import.php from docs/migration/content.json — do not edit by hand.';
$sql[] = '-- ‼️ Once applied anywhere, never regenerate with different content: corrections go in a new 03x migration.';
$sql[] = '--';
$sql[] = '-- Idempotent: media by path · posts by slug + published date · category SEO only when still empty.';
$sql[] = '-- Old URLs stay exact: slug is the decoded last segment, the URL date comes from published_at (Asia/Bangkok).';
$sql[] = '-- Thai values are hex — the decoded text is in the comment above each row.';
$sql[] = '';
$sql[] = '-- ─── categories: SEO text (the old archives had only the name)';

foreach (CATEGORY_SEO as $slug => $seo) {
    $sql[] = '';
    $sql[] = '-- /category/' . $slug . '/';
    $sql[] = '--   seo_title: ' . comment($seo['seo_title']);
    $sql[] = '--   meta: ' . comment($seo['meta_description']);
    $sql[] = '--   description: ' . comment($seo['description']);
    $sql[] = 'UPDATE categories SET';
    $sql[] = '    seo_title = COALESCE(NULLIF(seo_title, \'\'), ' . sqlHex($seo['seo_title']) . '),';
    $sql[] = '    meta_description = COALESCE(NULLIF(meta_description, \'\'), ' . sqlHex($seo['meta_description']) . '),';
    $sql[] = '    description = COALESCE(NULLIF(description, \'\'), ' . sqlHex($seo['description']) . '),';
    $sql[] = '    updated_at = NOW()';
    $sql[] = 'WHERE slug = ' . sqlHexBin($slug) . ' AND deleted_at IS NULL'
        . ' AND (seo_title IS NULL OR seo_title = \'\' OR meta_description IS NULL OR meta_description = \'\' OR description IS NULL OR description = \'\');';
}

$sql[] = '';
$sql[] = '-- ─── media: every image the posts reference (files already in public/wp-content/uploads/, same path as the old site)';

foreach ($media as $row) {
    $sql[] = '';
    $sql[] = '-- ' . comment($row['path']) . '  ·  alt: ' . comment($row['alt']);
    $sql[] = 'INSERT INTO media (path, mime, width, height, alt, bytes, created_at, updated_at)';
    $sql[] = 'SELECT ' . sqlHexBin($row['path']) . ', ' . sqlAscii($row['mime']) . ', ' . sqlNullableInt($row['width']) . ', '
        . sqlNullableInt($row['height']) . ', ' . sqlHex($row['alt']) . ', ' . $row['bytes'] . ', NOW(), NOW()';
    $sql[] = 'WHERE NOT EXISTS (SELECT 1 FROM media WHERE path = ' . sqlHexBin($row['path']) . ' AND deleted_at IS NULL);';
}

$sql[] = '';
$sql[] = '-- ─── posts';

foreach ($rows as $row) {
    $slugSql = sqlHexBin($row['slug']);
    $sql[] = '';
    $sql[] = '-- ' . comment($row['path']);
    $sql[] = '--   H1: ' . comment($row['title']);
    $sql[] = '--   <title>: ' . comment($row['seo_title']);
    $sql[] = '--   meta: ' . comment($row['description']);
    $sql[] = '--   category: ' . comment((string) $row['category']) . ' · featured: ' . comment((string) $row['featured']);
    $sql[] = 'INSERT INTO posts (slug, title, excerpt, content, featured_image_id, category_id, author_name, status, published_at,';
    $sql[] = '    seo_title, meta_description, robots, legacy_url, created_at, updated_at)';
    $sql[] = 'SELECT ' . $slugSql . ',';
    $sql[] = '    ' . sqlHex($row['title']) . ',';
    $sql[] = '    ' . sqlHex($row['description']) . ',';
    $sql[] = '    ' . sqlHex($row['content']) . ',';
    $sql[] = '    ' . ($row['featured'] !== null
        ? '(SELECT id FROM media WHERE path = ' . sqlHexBin($row['featured']) . ' AND deleted_at IS NULL LIMIT 1)'
        : 'NULL') . ',';
    $sql[] = '    ' . ($row['category'] !== null
        ? '(SELECT id FROM categories WHERE slug = ' . sqlHexBin($row['category']) . ' AND deleted_at IS NULL LIMIT 1)'
        : 'NULL') . ',';
    $sql[] = '    ' . ($row['author'] !== '' ? sqlHex($row['author']) : 'NULL') . ', \'published\', ' . sqlAscii($row['published']) . ',';
    $sql[] = '    ' . sqlHex($row['seo_title']) . ', ' . sqlHex($row['description']) . ', \'index\', ' . sqlAscii($row['url']) . ',';
    $sql[] = '    ' . sqlAscii($row['published']) . ', ' . sqlAscii($row['updated']);
    $sql[] = 'WHERE NOT EXISTS (SELECT 1 FROM posts WHERE slug = ' . $slugSql . ' AND published_date = ' . sqlAscii($row['date'])
        . ' AND deleted_at IS NULL);';
}

$sql[] = '';

file_put_contents($root . '/database/migrations/030_import_old_posts.sql', implode("\n", $sql));

// ------------------------------------------------------------------ post-titles.md

$md   = [];
$md[] = '# Post titles — old vs new';
$md[] = '';
$md[] = 'Generated by `tools/migration/build-import.php` (Phase 5). One row per imported post, in publish order.';
$md[] = '';
$md[] = '- **Old `<title>`** — the live title: the intent part, then the same ~500-character WordPress keyword list on most posts (shown as `… + keyword list`).';
$md[] = '- **New `<title>` (seo_title)** — the intent part only, word for word. Nothing was shortened or reworded, so every main keyword stays.';
$md[] = '- **New H1 (title)** — the old post title shown as the H1 on the live page. The exception is a cut-off or duplicated H1; there the clean `<title>` is used instead (see "Note").';
$md[] = '';
$md[] = '| # | URL | Old `<title>` | New `<title>` (seo_title) | New H1 | Old H1 (live) | Note |';
$md[] = '|---|---|---|---|---|---|---|';

$cell = static fn (string $s): string => str_replace('|', '\\|', $s);

foreach ($rows as $i => $row) {
    $intent = $cell(explode(' - ', $row['old_title'], 2)[0]);
    $hasList = str_contains($row['old_title'], ' - ');
    $notes   = [];
    $ov      = OVERRIDES[$row['path']] ?? [];
    if (isset($ov['title_reason'])) {
        $notes[] = $ov['title_reason'];
    } elseif ($row['title'] !== $row['seo_title']) {
        $notes[] = 'H1 เดิมกับ <title> เดิมต่างกันอยู่แล้ว — คงทั้งสองตามเดิม';
    }
    $md[] = '| ' . ($i + 1) . ' | `' . $cell($row['path']) . '` | ' . $intent . ($hasList ? ' `… + keyword list`' : '')
        . ' | ' . $cell($row['seo_title']) . ' | ' . $cell($row['title']) . ' | ' . $cell($row['old_h1']) . ' | ' . $cell(implode(' · ', $notes)) . ' |';
}

$md[] = '';
$md[] = '## Meta descriptions rewritten';
$md[] = '';
$md[] = 'Kept as-is when 50–170 characters and intact. The rest were rewritten from the article text (no new claims):';
$md[] = '';
$md[] = '| URL | Why | Old | New |';
$md[] = '|---|---|---|---|';
foreach ($rewrites as [$path, $old, $new, $why]) {
    $md[] = '| `' . $cell($path) . '` | ' . $cell($why) . ' | ' . $cell($old) . ' | ' . $cell($new) . ' |';
}

$md[] = '';
$md[] = '## Alt text filled (' . count($altFills) . ' images)';
$md[] = '';
$md[] = 'Order of sources: the author\'s own alt for the same file in another post → `images.csv` `alt_suggested` without its generic prefix ("ภาพประกอบ:" / "ภาพประกอบบทความ") → the post title. Featured images use the post title.';
$md[] = '';
$md[] = '| Post | Image | Alt | Source |';
$md[] = '|---|---|---|---|';
foreach ($altFills as [$post, $img, $alt, $source]) {
    $md[] = '| `' . $cell($post) . '` | `' . $cell(basename($img)) . '` | ' . $cell($alt) . ' | ' . $source . ' |';
}
$md[] = '';

file_put_contents($root . '/docs/migration/post-titles.md', implode("\n", $md));

echo "wrote database/migrations/030_import_old_posts.sql and docs/migration/post-titles.md\n";

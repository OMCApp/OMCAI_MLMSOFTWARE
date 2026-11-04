<?php
/**
 * Phase 4 — build the SQL migration that publishes the public site's pages in every locale.
 *
 * Usage (from the project root):
 *   php tools/content/build-pages.php            validate + write database/migrations/021_publish_site_content.sql
 *   php tools/content/build-pages.php --check    validate only (exit 1 on problems)
 *
 * Input:  tools/content/<locale>/<group>.php — locales come from config/locales.php
 *         Each file returns [ '<THAI PATH>' => [title, seo_title, description, blocks, path (non-default locales), robots?, canonical?] ]
 *         Blocks are written flat (['type' => 'hero', 'title' => …]) like config/home-blocks.php.
 *
 * What it does
 *   · default-locale pages: insert the row when the path has no shell yet (thank-you), then UPDATE the content,
 *     SEO fields and status = published (the Phase 2 shells keep their id, parent and template)
 *   · other locales: insert a row per translation in the SAME translation_group as the Thai page, then update it;
 *     parent_id mirrors the Thai parent's translation (breadcrumbs: /en/ → /en/compensation-plans/ → …)
 *   · blocks → [{type, data}] JSON · faq items normalised to {question, answer} (q/a accepted)
 *   · internal links in non-default locales are rewritten to the translated path when the target has a translation
 *     (url fields + href="/…" in rich_text html) — otherwise they stay on the Thai page (no links to missing pages)
 *   · '/blog/' is skipped for non-default locales — /en/blog/ must not exist until there are translated posts
 *   · every text value is hex (CONVERT(X'..' USING utf8mb4)) — scripts/check.php (no-thai-values) rejects Thai
 *     on INSERT lines; the readable title is in the comment above each page
 *
 * ‼️ Deterministic output (no timestamps in the file). Once 021 has been applied anywhere, NEVER regenerate it
 *    with different content — the Migrator refuses changed files. Later content changes: CMS or a new 02x migration.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$root      = dirname(__DIR__, 2);
$checkOnly = in_array('--check', $argv, true);
$target    = $root . '/database/migrations/021_publish_site_content.sql';

/** @var array<string, array<string, mixed>> $locales */
$locales = require $root . '/config/locales.php';
$default = (string) array_key_first($locales);

const FORBIDDEN = [
    '传销', '金字塔', '100,000+', '99.9%', '200+', '10+ ปี', '24/7', 'J&C', 'NUTRIPLUS', 'BEAUTÉ', 'VITA MAX',
    'BIO WELLNESS', 'HERBAL LIFE', 'ไม่จำกัด', 'unlimited', 'Unlimited', 'อันดับ 1', '最好', '领先', 'Big Data',
];

/** max seo_title length and [min, max] description length (mb_strlen) per locale */
const LIMITS = [
    'th' => ['title' => 60, 'desc' => [110, 160]],
    'en' => ['title' => 60, 'desc' => [110, 160]],
    'zh' => ['title' => 32, 'desc' => [50, 100]],
];

$errors = [];
$pages  = [];   // locale => [thaiPath => page]

foreach (array_keys($locales) as $locale) {
    foreach (glob($root . '/tools/content/' . $locale . '/*.php') ?: [] as $file) {
        $data = require $file;

        foreach ($data as $thaiPath => $page) {
            if ($locale !== $default && $thaiPath === '/blog/') {
                continue; // no translated blog index until translated posts exist
            }

            $page['path']       = $locale === $default ? $thaiPath : (string) ($page['path'] ?? '');
            $page['_file']      = basename(dirname($file)) . '/' . basename($file);
            $pages[$locale][$thaiPath] = $page;
        }
    }
}

// translated path map: locale => [thai path => translated path]
$pathMap = [];

foreach ($pages as $locale => $list) {
    foreach ($list as $thaiPath => $page) {
        $pathMap[$locale][$thaiPath] = $page['path'];
    }
}

/* ------------------------------------------------------------------ helpers */

function sqlHex(string $text): string
{
    return "CONVERT(X'" . strtoupper(bin2hex($text)) . "' USING utf8mb4)";
}

function sqlHexBin(string $text): string
{
    return sqlHex($text) . ' COLLATE utf8mb4_bin';
}

function sqlAscii(string $text): string
{
    if (preg_match('/^[\x20-\x7E]*$/', $text) !== 1) {
        throw new RuntimeException('non-ASCII value passed to sqlAscii: ' . $text);
    }

    return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $text) . "'";
}

function comment(string $text): string
{
    return str_replace(["\r", "\n", '*/'], [' ', ' ', '* /'], $text);
}

/** rewrite an internal link to the translated page when there is one */
function localizeUrl(string $url, ?array $map): string
{
    if ($map === null || !str_starts_with($url, '/')) {
        return $url;
    }

    [$path, $fragment] = array_pad(explode('#', $url, 2), 2, null);

    return ($map[$path] ?? $path) . ($fragment !== null ? '#' . $fragment : '');
}

/**
 * @param  array<string, mixed> $value
 * @return array<string, mixed>
 */
function localizeLinks(array $value, ?array $map): array
{
    foreach ($value as $key => $item) {
        if (is_array($item)) {
            $value[$key] = localizeLinks($item, $map);
        } elseif (is_string($item) && $key === 'url') {
            $value[$key] = localizeUrl($item, $map);
        } elseif (is_string($item) && $key === 'html' && $map !== null) {
            $value[$key] = (string) preg_replace_callback(
                '#href="(/[^"]*)"#u',
                static fn (array $m): string => 'href="' . localizeUrl($m[1], $map) . '"',
                $item
            );
        }
    }

    return $value;
}

/** visible text of a page (for word counts / forbidden terms) */
function visibleText(array $value): string
{
    $out = '';

    foreach ($value as $key => $item) {
        if (is_array($item)) {
            $out .= ' ' . visibleText($item);
        } elseif (is_string($item) && !in_array($key, ['url', 'icon', 'type', 'key', 'style', 'form_key', 'variant', 'layout', 'visual'], true)) {
            $out .= ' ' . strip_tags($item);
        }
    }

    return $out;
}

/**
 * flat block → {type, data} · faq q/a → question/answer
 *
 * @return array{type:string, data:array<string,mixed>}
 */
function normaliseBlock(array $block): array
{
    $type = (string) ($block['type'] ?? '');
    unset($block['type']);

    if ($type === 'faq') {
        $block['items'] = array_map(
            static fn (array $item): array => [
                'question' => (string) ($item['question'] ?? $item['q'] ?? ''),
                'answer'   => (string) ($item['answer'] ?? $item['a'] ?? ''),
            ],
            (array) ($block['items'] ?? [])
        );
    }

    return ['type' => $type, 'data' => $block];
}

/* ------------------------------------------------------------------ validate */

$types = ['hero', 'trust_bar', 'stats', 'feature_grid', 'split', 'plan_cards', 'industry_grid', 'dashboard_showcase',
    'about_teaser', 'testimonials', 'faq', 'cta_band', 'rich_text', 'steps', 'comparison_table', 'lead_form'];

foreach ($pages as $locale => $list) {
    $seenTitle = [];
    $seenDesc  = [];
    $limit     = LIMITS[$locale] ?? LIMITS['en'];

    foreach ($list as $thaiPath => $page) {
        $where = "{$locale} {$thaiPath} ({$page['_file']})";

        if ($locale !== $default && !isset($pages[$default][$thaiPath])) {
            $errors[] = "{$where}: no {$default} page with this key";
        }
        if ($locale !== $default && !str_starts_with($page['path'], (string) $locales[$locale]['prefix'] . '/')) {
            $errors[] = "{$where}: path '{$page['path']}' must start with {$locales[$locale]['prefix']}/";
        }

        foreach (['title', 'seo_title', 'description'] as $field) {
            if (trim((string) ($page[$field] ?? '')) === '') {
                $errors[] = "{$where}: missing {$field}";
            }
        }

        $seo  = (string) ($page['seo_title'] ?? '');
        $desc = (string) ($page['description'] ?? '');

        if (mb_strlen($seo) > $limit['title']) {
            $errors[] = "{$where}: seo_title " . mb_strlen($seo) . " chars > {$limit['title']}";
        }
        if (!str_ends_with($seo, '| OMC')) {
            $errors[] = "{$where}: seo_title must end with '| OMC'";
        }
        if (($page['robots'] ?? 'index') === 'index' && (mb_strlen($desc) < $limit['desc'][0] || mb_strlen($desc) > $limit['desc'][1])) {
            $errors[] = "{$where}: description " . mb_strlen($desc) . " chars (want {$limit['desc'][0]}–{$limit['desc'][1]})";
        }
        if (isset($seenTitle[$seo])) {
            $errors[] = "{$where}: duplicate seo_title with {$seenTitle[$seo]}";
        }
        if (isset($seenDesc[$desc])) {
            $errors[] = "{$where}: duplicate description with {$seenDesc[$desc]}";
        }
        $seenTitle[$seo]  = $thaiPath;
        $seenDesc[$desc]  = $thaiPath;

        $heroes = 0;

        foreach ((array) ($page['blocks'] ?? []) as $index => $block) {
            if (!in_array($block['type'] ?? '', $types, true)) {
                $errors[] = "{$where}: block {$index} unknown type '" . ($block['type'] ?? '') . "'";
            }
            $heroes += ($block['type'] ?? '') === 'hero' ? 1 : 0;
        }

        if ($heroes > 1) {
            $errors[] = "{$where}: {$heroes} hero blocks (one H1 per page)";
        }

        $text = visibleText($page);

        foreach (FORBIDDEN as $term) {
            if (str_contains($text, $term)) {
                $errors[] = "{$where}: forbidden term '{$term}'";
            }
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "✗ " . count($errors) . " problem(s):\n  " . implode("\n  ", $errors) . "\n");
    exit(1);
}

foreach ($pages as $locale => $list) {
    echo "{$locale}: " . count($list) . " pages\n";
}

if ($checkOnly) {
    echo "✓ content valid (--check, nothing written)\n";
    exit(0);
}

/* ------------------------------------------------------------------ SQL */

$sql = [];
$sql[] = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;';
$sql[] = '';
$sql[] = '-- Phase 4 — publish the public site content in every locale (generated by tools/content/build-pages.php — do not edit by hand)';
$sql[] = '-- Source: tools/content/<locale>/*.php · locales: config/locales.php';
$sql[] = '-- ‼️ Never regenerate with different content after this file has been applied anywhere (Migrator checksum).';
$sql[] = '--    Later changes go through the CMS or a new 02x migration.';
$sql[] = '-- Text values are hex (scripts/check.php no-thai-values); the readable page title is in the comment above each page.';
$sql[] = '';

$sortBase = ['th' => 0, 'en' => 1000, 'zh' => 2000];

foreach ($pages as $locale => $list) {
    $map = $locale === $default ? null : ($pathMap[$locale] ?? []);
    $sql[] = '-- ============================================================ ' . $locale;

    foreach ($list as $thaiPath => $page) {
        $path      = $page['path'];
        $blocks    = array_map('normaliseBlock', localizeLinks((array) ($page['blocks'] ?? []), $map));
        $json      = (string) json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $robots    = ($page['robots'] ?? 'index') === 'noindex' ? 'noindex' : 'index';
        $canonical = isset($page['canonical']) ? localizeUrl((string) $page['canonical'], $map) : null;
        $pathSql   = sqlHexBin($path);
        $thaiSql   = sqlHexBin($thaiPath);

        $sql[] = '';
        $sql[] = '-- ' . comment($path . '  ·  ' . $page['title']);

        if ($locale === $default) {
            // shell missing (e.g. /thank-you/) → create it first
            $sql[] = 'INSERT INTO pages (path, locale, translation_group, title, template, robots, status, sort, created_at, updated_at)';
            $sql[] = 'SELECT ' . $pathSql . ', ' . sqlAscii($locale) . ', UUID(), ' . sqlHex((string) $page['title']) . ", 'default', "
                . sqlAscii($robots) . ", 'draft', 900, NOW(), NOW()";
            $sql[] = 'FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM pages WHERE path = ' . $pathSql . ' AND deleted_at IS NULL);';
        } else {
            // translation row in the Thai page's group · same template as the Thai page
            $sql[] = 'INSERT INTO pages (path, locale, translation_group, title, template, robots, status, sort, created_at, updated_at)';
            $sql[] = 'SELECT ' . $pathSql . ', ' . sqlAscii($locale) . ', th.translation_group, ' . sqlHex((string) $page['title'])
                . ', th.template, ' . sqlAscii($robots) . ", 'draft', th.sort + " . ($sortBase[$locale] ?? 3000) . ', NOW(), NOW()';
            $sql[] = 'FROM pages th WHERE th.path = ' . $thaiSql . ' AND th.deleted_at IS NULL';
            $sql[] = '  AND NOT EXISTS (SELECT 1 FROM pages x WHERE x.path = ' . $pathSql . ' AND x.deleted_at IS NULL);';
        }

        $sql[] = 'UPDATE pages SET'
            . ' title = ' . sqlHex((string) $page['title'])
            . ', seo_title = ' . sqlHex((string) $page['seo_title'])
            . ', meta_description = ' . sqlHex((string) $page['description'])
            . ', blocks = ' . sqlHex($json)
            . ', robots = ' . sqlAscii($robots)
            . ', canonical_override = ' . ($canonical !== null ? sqlHex($canonical) : 'NULL')
            . ', locale = ' . sqlAscii($locale)
            . ", status = 'published', published_at = COALESCE(published_at, NOW()), updated_at = NOW()"
            . ' WHERE path = ' . $pathSql . ' AND deleted_at IS NULL;';
    }

    $sql[] = '';
}

$sql[] = '-- Parents of translations mirror the Thai parent\'s translation (breadcrumbs per locale)';
$sql[] = 'UPDATE pages c';
$sql[] = "JOIN pages cth ON cth.translation_group = c.translation_group AND cth.locale = 'th' AND cth.deleted_at IS NULL";
$sql[] = 'JOIN pages pth ON pth.id = cth.parent_id';
$sql[] = 'JOIN pages p   ON p.translation_group = pth.translation_group AND p.locale = c.locale AND p.deleted_at IS NULL';
$sql[] = "SET c.parent_id = p.id";
$sql[] = "WHERE c.locale <> 'th' AND c.parent_id IS NULL AND c.deleted_at IS NULL;";
$sql[] = '';
$sql[] = '-- Organization facts for JSON-LD (verified: QUESTIONS.md) + default share image';
$sql[] = 'UPDATE settings SET value = ' . sqlHex((string) json_encode([
    'name'       => 'OMC MLM Software',
    'legal_name' => 'บริษัท ออนไลน์ มาร์เก็ตติ้ง คอมมิวนิเคชั่น จำกัด',
    'logo'       => '/assets/images/brand/omc-logo-horizontal.png',
    'telephone'  => '+66991095544',
    'email'      => 'sale@omc.co.th',
    'same_as'    => ['https://www.facebook.com/mlmsoftwarebyomc'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . ", updated_at = NOW() WHERE setting_key = 'organization' AND deleted_at IS NULL;";
$sql[] = 'UPDATE settings SET value = ' . sqlAscii((string) json_encode([
    'brand' => 'OMC MLM Software', 'locale' => 'th_TH', 'default_og_image' => '/assets/images/og-default.png',
], JSON_UNESCAPED_SLASHES)) . ", updated_at = NOW() WHERE setting_key = 'site' AND deleted_at IS NULL;";
$sql[] = '';

file_put_contents($target, implode("\n", $sql));

echo '✓ wrote ' . substr($target, strlen($root) + 1) . ' (' . number_format(filesize($target)) . " bytes)\n";

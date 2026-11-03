<?php
/**
 * Phase 5 — content extraction from the live WordPress site (mlmsoftware.omc.co.th).
 *
 * Usage (from the project root):
 *   php -d curl.cainfo=C:/xampp/apache/bin/curl-ca-bundle.crt tools/migration/extract.php [options]
 *
 * Options:
 *   --baseline=docs/seo/baseline-2026-11-03/pages.csv   URL source (status 200 posts + kept pages)
 *   --backup=C:/Users/Surface/Desktop/mlmss/wp-content/uploads
 *   --out=docs/migration                                 reports / JSON output
 *   --public=public                                      images go to <public>/wp-content/uploads/<same path>
 *   --limit-mb=150                                       stop copying images past this total
 *   --no-images                                          skip the image copy step
 *   --dry-run-images                                     resolve sources only, copy nothing
 *
 * Live pages are fetched one at a time (1 s apart, UA "OMC-Migration/1.0") and cached in
 * %TEMP%/omc-migration-cache (override with env OMC_MIGRATION_CACHE) so re-runs do not hit the site.
 *
 * Outputs: content.json · content-summary.md · contact-info.json · images.csv · pages-text.md
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require __DIR__ . '/lib/fetch.php';
require __DIR__ . '/lib/targets.php';
require __DIR__ . '/lib/sanitize.php';
require __DIR__ . '/lib/page.php';
require __DIR__ . '/lib/contact.php';
require __DIR__ . '/lib/images.php';
require __DIR__ . '/lib/report.php';

$root = dirname(__DIR__, 2);
$opt = [
    'baseline' => $root . '/docs/seo/baseline-2026-11-03/pages.csv',
    'backup' => 'C:/Users/Surface/Desktop/mlmss/wp-content/uploads',
    'out' => $root . '/docs/migration',
    'public' => $root . '/public',
    'limit-mb' => '150',
];
$flags = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)=(.*)$/', $a, $m)) {
        $opt[$m[1]] = $m[2];
    } elseif (str_starts_with($a, '--')) {
        $flags[substr($a, 2)] = true;
    }
}
@mkdir($opt['out'], 0777, true);

// ---------------------------------------------------------------- 1. fetch + parse
$targets = migration_targets($opt['baseline']);
fwrite(STDERR, count($targets) . " URLs to extract\n");

$records = [];
$contacts = new ContactCollector();
$logos = [];
foreach ($targets as $i => $t) {
    fwrite(STDERR, sprintf("[%d/%d] %s\n", $i + 1, count($targets), $t['path_decoded']));
    $res = migration_fetch($t['url']);
    if ($res['status'] !== 200) {
        $records[] = $t + ['problems' => ['fetch failed: HTTP ' . $res['status'] . ' ' . $res['error']]];
        continue;
    }
    $rec = parse_page($res['body'], $t);
    [$doc, $xp] = $rec['_doc'];
    unset($rec['_doc']);
    $contacts->scan($xp, $t['path_decoded']);

    // site logos / icons (header, mobile header, favicons)
    foreach ($xp->query('//*[' . xp_class('seocrawler-logo') . ' or ' . xp_class('seocrawler-mobile-logo') . ']//img') as $img) {
        $src = to_root_relative($img->getAttribute('src'));
        $logos[$src] ??= ['alt' => trim($img->getAttribute('alt')), 'role' => 'logo'];
        foreach (preg_split('/\s*,\s*/', $img->getAttribute('srcset')) as $s) {
            $u = to_root_relative(trim(explode(' ', trim($s))[0] ?? ''));
            if ($u !== '') {
                $logos[$u] ??= ['alt' => trim($img->getAttribute('alt')), 'role' => 'logo'];
            }
        }
    }
    foreach ($xp->query('//link[contains(@rel, "icon")]/@href') as $h) {
        $logos[to_root_relative($h->nodeValue)] ??= ['alt' => '', 'role' => 'icon'];
    }
    $records[] = $rec;
}

// ---------------------------------------------------------------- 2. images
$imgReport = null;
if (!isset($flags['no-images'])) {
    $mig = new ImageMigrator(
        str_replace('/', DIRECTORY_SEPARATOR, $opt['backup']),
        rtrim(str_replace('/', DIRECTORY_SEPARATOR, $opt['public']), DIRECTORY_SEPARATOR),
        (int) $opt['limit-mb'] * 1048576,
        isset($flags['dry-run-images']),
    );
    foreach ($records as $rec) {
        if (!isset($rec['content_html'])) {
            continue;
        }
        $page = $rec['path_decoded'];
        $title = $rec['title_clean'] ?: ($rec['h1'][0] ?? '');
        foreach (image_contexts($rec['content_html']) as $ic) {
            $mig->addRef($ic['src'], 'content', $ic['alt'], $page, suggest_alt($title, $ic, $rec['type']));
        }
        if ($rec['featured_image'] !== '') {
            $mig->addRef($rec['featured_image'], 'featured', '', $page, ($rec['type'] === 'post' ? 'ภาพหน้าปกบทความ ' : 'ภาพประกอบหน้า ') . $title);
        }
        if ($rec['header_background'] !== '' && $rec['header_background'] !== $rec['featured_image']) {
            $mig->addRef($rec['header_background'], 'header_background', '', $page, 'ภาพหน้าปกบทความ ' . $title);
        }
        foreach ($rec['background_images'] as $bg) {
            $mig->addRef($bg, 'background', '', $page, 'ภาพพื้นหลังส่วนหนึ่งของหน้า ' . $title);
        }
    }
    foreach ($logos as $src => $l) {
        $mig->addRef($src, $l['role'], $l['alt'], 'site-wide', $l['role'] === 'logo' ? 'โลโก้ MLM Software by OMC' : '');
    }
    $mig->addOriginals();
    $mig->run();
    $mig->writeCsv($opt['out'] . '/images.csv');

    // Fill missing width/height in content HTML from the real files (prevents layout shift).
    foreach ($records as &$rec) {
        if (!isset($rec['content_html'])) {
            continue;
        }
        $rec['content_html'] = preg_replace_callback('~<img ([^>]*)>~', function ($m) use ($mig) {
            if (str_contains($m[1], 'width=')) {
                return $m[0];
            }
            if (!preg_match('~src="([^"]*)"~', $m[1], $s) || !($d = $mig->dims(html_entity_decode($s[1])))) {
                return $m[0];
            }
            return '<img ' . $m[1] . ' width="' . $d[0] . '" height="' . $d[1] . '">';
        }, $rec['content_html']);
        foreach ($rec['images'] as &$im) {
            $d = $mig->dims($im['src']);
            $im['width'] = $d[0] ?? null;
            $im['height'] = $d[1] ?? null;
        }
        unset($im);
    }
    unset($rec);
    $imgReport = $mig;
}

// ---------------------------------------------------------------- 3. outputs
$json = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
file_put_contents($opt['out'] . '/content.json', json_encode($records, $json) . "\n");
file_put_contents($opt['out'] . '/contact-info.json', json_encode($contacts->all(), $json) . "\n");

$ocpb = ocpb_similarity($records);
file_put_contents($opt['out'] . '/content-summary.md', summary_md($records, $imgReport, $ocpb));
file_put_contents($opt['out'] . '/pages-text.md', pages_text_md($records, ['/', '/mlm-software-omc/', '/services/', '/ocpb-mlm/', '/ocpb-mlm2/']));

fwrite(STDERR, "Done. Records: " . count($records) . "\n");
if ($imgReport) {
    fwrite(STDERR, sprintf("Images: %d refs, %.1f MB%s\n", count($imgReport->refs), $imgReport->totalBytes / 1048576, $imgReport->stopped ? ' (STOPPED at limit)' : ''));
}
fwrite(STDERR, sprintf("ocpb-mlm vs ocpb-mlm2 similarity: %s\n", json_encode($ocpb, JSON_UNESCAPED_UNICODE)));
